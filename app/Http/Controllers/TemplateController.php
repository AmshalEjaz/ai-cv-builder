<?php

namespace App\Http\Controllers;

use App\Models\Template;

class TemplateController extends Controller
{
    public function index()
    {
        $templates = Template::where('is_active', true)->latest()->get();

        return view('templates.index', compact('templates'));
    }

    public function preview(Template $template)
    {
        abort_unless($template->is_active, 404);

        return view('templates.preview', compact('template'));
    }
}
