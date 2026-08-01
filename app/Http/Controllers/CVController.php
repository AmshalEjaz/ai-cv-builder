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
            $enhancedData = $this->aiService->parseAndEnhanceCV($text);
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

        return Pdf::loadView('cvs.pdf', compact('cv'))
            ->setPaper('a4')
            ->download($cv->title . '.pdf');
    }
}