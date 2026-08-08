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
            'cv_file' => 'nullable|file|mimes:pdf,docx|max:10240',
            'reprocess' => 'nullable|boolean',
        ]);

        $data = $request->input('data');
        if (is_array($data) && isset($data['skills']) && is_string($data['skills'])) {
            $data['skills'] = array_values(array_filter(array_map('trim', preg_split('/[,|\r\n]+/', $data['skills']) ?: [])));
        }

        $update = [
            'title' => $request->title,
            'template_id' => $request->template_id,
        ];

        // If the user attached a new/replacement CV file, or explicitly asked to
        // re-run AI on the existing file, run it back through Ollama just like
        // the initial upload does. Manual field edits (if any were also submitted
        // in this same request) are applied on top, so they always win.
        $shouldReprocess = $request->boolean('reprocess') || $request->hasFile('cv_file');

        if ($shouldReprocess) {
            try {
                if ($request->hasFile('cv_file')) {
                    $file = $request->file('cv_file');
                    $text = $this->fileParser->extractText($file);
                    $path = $this->fileParser->storeFile($file, Auth::id());
                    $update['original_filename'] = $file->getClientOriginalName();
                    $update['file_path'] = $path;
                } else {
                    // Re-run AI against the text we already extracted at upload time.
                    $text = data_get($cv->parsed_data, 'raw_text', '');
                    if ($text === '') {
                        throw new \RuntimeException('No stored CV text available to re-process. Please attach a file.');
                    }
                }

                $templateSlug = optional(Template::find($request->template_id))->slug;
                $enhancedData = $this->aiService->parseAndEnhanceCV($text, $templateSlug);

                // Manual edits submitted in the same request take priority over AI output.
                $enhancedData = is_array($data) ? array_merge($enhancedData, $data) : $enhancedData;

                $update['parsed_data'] = ['raw_text' => $text];
                $update['ai_enhanced_data'] = $enhancedData;
                $update['status'] = 'processed';
            } catch (\Throwable $e) {
                Log::error('CV re-process (edit) failed', ['cv_id' => $cv->id, 'error' => $e->getMessage()]);
                return back()->withInput()->with('error', 'Could not re-process the CV with AI: ' . $e->getMessage());
            }
        } elseif (is_array($data)) {
            $update['ai_enhanced_data'] = array_merge($cv->ai_enhanced_data ?? [], $data);
        }

        $cv->update($update);

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

        // NOTE: We intentionally do NOT render the template's thumbnail/PDF as a
        // rasterized background image anymore. Those files are Canva-style mockups
        // with sample text baked into the pixels (e.g. "Lorem ipsum", placeholder
        // names/companies), so overlaying them behind the real CV data caused the
        // old mockup content to show through / mix with the user's actual data.
        // The visual "theme" for each template is now recreated with real CSS in
        // cvs/pdf.blade.php (keyed by the template's settings->style), driven
        // entirely by $cv->rendered_data, so only genuine text ever appears.

        try {
            return Pdf::loadView('cvs.pdf', compact('cv'))
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