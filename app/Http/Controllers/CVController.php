<?php

namespace App\Http\Controllers;

use App\Models\CV;
use App\Models\Template;
use App\Models\User;
use App\Services\FileParserService;
use App\Services\AIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

// helper for pdftoppm resolution
use Illuminate\Support\Facades\File;

class CVController extends Controller
{
    public function __construct(
        protected FileParserService $fileParser,
        protected AIService $aiService,
    ) {
    }

    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $cvs = $user->cvs()->with('template')->latest()->get();

        return view('cvs.index', compact('cvs'));
    }

    public function create(): View
    {
        $templates = Template::where('is_active', true)->get();
        return view('cvs.create', compact('templates'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'cv_file' => 'required|file|mimes:pdf,docx|max:10240',
            'template_id' => 'required|exists:templates,id'
        ]);

        $file = $request->file('cv_file');
        
        try {
            $text = $this->fileParser->extractText($file);
        } catch (\RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }
        
        // Store file
        $path = $this->fileParser->storeFile($file, Auth::id());

        // Create CV record
        $cv = CV::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'template_id' => $request->template_id,
            'status' => 'pending'
        ]);

        // Process with AI
        try {
            $templateSlug = optional(Template::find($request->template_id))->slug;
            $enhancedData = $this->aiService->parseAndEnhanceCV($text, $templateSlug);
            $cv->update([
                'parsed_data' => ['raw_text' => $text],
                'ai_enhanced_data' => $enhancedData,
                'status' => 'processed'
            ]);
        } catch (\Exception $e) {
            $cv->update(['status' => 'error']);
        }

        return redirect()->route('cvs.show', $cv)
            ->with('success', 'CV uploaded and processed successfully!');
    }

    public function show(CV $cv): View
    {
        $this->authorize('view', $cv);
        $templates = Template::where('is_active', true)->get();
        return view('cvs.show', compact('cv', 'templates'));
    }

    public function edit(CV $cv): View
    {
        $this->authorize('update', $cv);
        $templates = Template::where('is_active', true)->get();
        return view('cvs.edit', compact('cv', 'templates'));
    }

    public function update(Request $request, CV $cv)
    {
        $this->authorize('update', $cv);

        $request->validate([
            'title' => 'required|string|max:255',
            'template_id' => 'required|exists:templates,id',
            'data' => 'nullable|array',
            'data.name' => 'nullable|string|max:255',
            'data.title' => 'nullable|string|max:255',
            'data.email' => 'nullable|email|max:255',
            'data.phone' => 'nullable|string|max:50',
            'data.summary' => 'nullable|string|max:5000',
            'data.skills' => 'nullable|string|max:2000',
        ]);

        $data = $request->input('data');
        if (is_array($data)) {
            if (isset($data['skills']) && is_string($data['skills'])) {
                $data['skills'] = array_values(array_filter(array_map('trim', preg_split('/[,|\r\n]+/', $data['skills']) ?: [])));
            }

            $cv->update([
                'title' => $request->title,
                'ai_enhanced_data' => array_merge($cv->ai_enhanced_data ?? [], $data),
                'template_id' => $request->template_id
            ]);
        } else {
            $cv->update([
                'title' => $request->title,
                'template_id' => $request->template_id
            ]);
        }

        return redirect()->route('cvs.show', $cv)
            ->with('success', 'CV updated successfully!');
    }

    public function destroy(CV $cv)
    {
        $this->authorize('delete', $cv);
        // Delete file
        Storage::disk('public')->delete($cv->file_path);
        $cv->delete();

        return redirect()->route('cvs.index')
            ->with('success', 'CV deleted successfully!');
    }

    public function enhanceDescription(Request $request, CV $cv)
    {
        $this->authorize('update', $cv);

        $request->validate([
            'text' => 'required|string',
            'type' => 'required|in:experience,summary,skill'
        ]);

        $enhancedText = $this->aiService->improveDescription(
            $request->text,
            $request->type
        );

        return response()->json([
            'success' => true,
            'enhanced_text' => $enhancedText
        ]);
    }

    public function download(CV $cv)
    {
        $this->authorize('view', $cv);

        $cv->load('template');

        // Precompute template background image base64 to avoid complex Blade logic.
        $bgBase64 = null;

        // Prepare pdftoppm path candidates (so we can render a template PDF if the admin opted in)
        $pdftoppmCandidates = [
            'pdftoppm',
            'C:\\ProgramData\\chocolatey\\lib\\poppler\\tools\\poppler\\bin\\pdftoppm.exe',
            'C:\\tools\\poppler\\Library\\bin\\pdftoppm.exe',
        ];
        $resolvedPdftoppm = null;
        foreach ($pdftoppmCandidates as $c) {
            if (is_file($c)) { $resolvedPdftoppm = $c; break; }
            // try where on Windows
            if (strtoupper(substr(PHP_OS,0,3)) === 'WIN') {
                $out = null; @exec('where "' . $c . '" 2>nul', $out);
                if (!empty($out) && is_file($out[0])) { $resolvedPdftoppm = $out[0]; break; }
            }
        }
        if (!$resolvedPdftoppm) $resolvedPdftoppm = 'pdftoppm';

        try {
            if ($cv->template) {
                $thumbnail = $cv->template->thumbnail;
                $pdfPath = $cv->template->pdf_path;

                if ($thumbnail) {
                    $thumbPath = public_path(ltrim($thumbnail, '/'));
                    if (file_exists($thumbPath)) {
                        $bgBase64 = base64_encode(file_get_contents($thumbPath));
                    }
                }

                // If the template is explicitly marked to use PDF as background,
                // render the first page to PNG using pdftoppm and use that image.
                if ($cv->template->use_pdf_background && $pdfPath) {
                    try {
                        $absPdf = public_path(ltrim($pdfPath, '/'));
                        if (file_exists($absPdf)) {
                            $tmpDir = storage_path('app/template_bg/' . uniqid());
                            if (!is_dir($tmpDir)) mkdir($tmpDir, 0755, true);
                            $prefix = $tmpDir . DIRECTORY_SEPARATOR . 'bg';
                            // Render first page only
                            $cmd = sprintf('%s -png -f 1 -l 1 %s %s', escapeshellarg($resolvedPdftoppm), escapeshellarg($absPdf), escapeshellarg($prefix));
                            $r = Process::run($cmd);
                            Log::info('Template pdftoppm render', ['cmd' => $cmd, 'out' => $r->output(), 'err' => $r->error(), 'code' => $r->exitCode()]);
                            $png = $prefix . '-1.png';
                            if (is_file($png)) {
                                $bgBase64 = base64_encode(file_get_contents($png));
                            }
                            // cleanup
                            @unlink($png);
                            @rmdir($tmpDir);
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Failed to render template PDF to image', ['cv_id' => $cv->id, 'error' => $e->getMessage()]);
                    }
                }
                
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to prepare template background', ['cv_id' => $cv->id, 'error' => $e->getMessage()]);
            $bgBase64 = null;
        }


        try {
            return Pdf::loadView('cvs.pdf', compact('cv', 'bgBase64'))
                ->setPaper('a4')
                ->download($cv->title . '.pdf');
        } catch (\Throwable $e) {
            Log::error('CV download failed', [
                'cv_id' => $cv->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Could not generate PDF. The error has been logged.');
        }
    }
}