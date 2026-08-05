<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TemplateController extends Controller
{
    public function index(): View
    {
        $templates = Template::where('is_active', true)->latest()->get();

        return view('templates.index', compact('templates'));
    }

    public function preview(Template $template)
    {
        abort_unless($template->is_active, 404);

        return view('templates.preview', compact('template'));
    }

    public function manage(): View
    {
        $templates = Template::latest()->get();

        return view('templates.manage', compact('templates'));
    }

    public function create(): View
    {
        return view('templates.form', ['template' => new Template()]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $template = new Template($data);
        $this->storeAssets($request, $template);
        $template->save();

        return redirect()->route('templates.manage')->with('success', 'Template added successfully.');
    }

    public function edit(Template $template): View
    {
        return view('templates.form', compact('template'));
    }

    public function update(Request $request, Template $template)
    {
        $data = $this->validatedData($request, $template);
        $template->fill($data);
        $this->storeAssets($request, $template);
        $template->save();

        return redirect()->route('templates.manage')->with('success', 'Template updated successfully.');
    }

    public function destroy(Template $template)
    {
        if ($template->cvs()->exists()) {
            return redirect()->route('templates.manage')
                ->with('error', 'This template cannot be deleted because it is already used by a CV. Deactivate it instead.');
        }

        foreach ([$template->pdf_path, $template->thumbnail] as $assetPath) {
            if ($assetPath && str_starts_with($assetPath, 'images/templates/')) {
                $absolutePath = public_path($assetPath);
                if (is_file($absolutePath)) {
                    unlink($absolutePath);
                }
            }
        }

        $template->delete();

        return redirect()->route('templates.manage')->with('success', 'Template deleted successfully.');
    }

    private function validatedData(Request $request, ?Template $template = null): array
    {
        $slugRule = 'required|string|max:255|unique:templates,slug';
        if ($template) {
            $slugRule .= ',' . $template->id;
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => $slugRule,
            'description' => 'nullable|string|max:1000',
            'accent' => 'required|string|max:20',
            'is_active' => 'nullable|boolean',
            'use_pdf_background' => 'nullable|boolean',
            'pdf' => ($template?->pdf_path ? 'nullable' : 'required') . '|file|mimes:pdf|max:10240',
            'thumbnail_file' => 'nullable|file|mimes:png,jpg,jpeg,webp|max:5120',
        ]);

        return [
            'name' => $request->string('name')->toString(),
            'slug' => Str::slug($request->string('slug')->toString()),
            'description' => $request->string('description')->toString(),
            'settings' => ['accent' => $request->string('accent')->toString()],
            'is_active' => $request->boolean('is_active'),
            'use_pdf_background' => $request->boolean('use_pdf_background'),
        ];
    }

    private function storeAssets(Request $request, Template $template): void
    {
        if ($request->hasFile('pdf')) {
            $template->pdf_path = $this->storePublicAsset($request, 'pdf');
        }

        if ($request->hasFile('thumbnail_file')) {
            $template->thumbnail = $this->storePublicAsset($request, 'thumbnail_file');
        }
    }

    private function storePublicAsset(Request $request, string $field): string
    {
        $file = $request->file($field);
        $directory = public_path('images/templates');
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = Str::slug($originalName ?: 'template') . '.' . $extension;
        $counter = 1;

        while (is_file($directory . DIRECTORY_SEPARATOR . $filename)) {
            $filename = Str::slug($originalName ?: 'template') . '-' . $counter . '.' . $extension;
            $counter++;
        }

        $file->move($directory, $filename);

        return 'images/templates/' . $filename;
    }
}
