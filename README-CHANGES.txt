HOW TO USE THIS
================
Extract this zip into your project root (ai-cv-builder), overwriting the
matching files at these exact paths:

  app/Services/AIService.php
  app/Http/Controllers/CVController.php
  app/Http/Controllers/TemplateController.php
  app/Models/CV.php
  resources/views/cvs/pdf.blade.php
  resources/views/cvs/edit.blade.php
  resources/views/templates/form.blade.php
  database/seeders/TemplateSeeder.php

After extracting, run:
  php artisan config:clear
  php artisan cache:clear

IMPORTANT — ONE-TIME STEP FOR YOUR EXISTING TEMPLATES
=======================================================
Your 5 templates (Modern Teal, Executive Slate, Gray & Golden,
Black & White Simple, Art Director / "modern") were created before the new
"Layout" field existed. Go to Manage Templates -> Edit for each one, and in
the new "Layout" dropdown pick the matching option:

  Modern Teal              -> Modern Teal
  Executive Slate          -> Executive Slate
  Gray & Golden resume cv  -> Gray & Golden
  Black & White Simple     -> Centered Classic
  modern (Art Director)    -> Art Director

Save each. That's a one-time task (5 clicks). Any NEW template you add later
just needs its Layout picked once when you create it — the CRUD (add/edit/
delete templates) still fully works, this only adds one more field to it.

Alternatively, if you don't mind resetting your template table, you can run:
  php artisan db:seed --class=TemplateSeeder
This will (re)create the 5 templates above already correctly configured
(matched by slug), without touching any other tables.

WHAT CHANGED AND WHY
=====================
1. AIService.php
   - Added "format": "json" + temperature 0 + bigger num_ctx/num_predict so
     Ollama returns strict, complete JSON instead of chatty/partial text,
     even for long/detailed CVs.
   - Fixed a real crash bug: DateTime::createFromFormat() can return false,
     and the old code called ->format() on it directly, which throws a
     fatal \Error (not \Exception) that the old catch block couldn't catch.
   - Added a "languages" field to the schema/output (your templates all
     have a Languages section, but it wasn't being extracted before).
   - Kept your parseLocally() fallback and CLI fallback intact.

2. CVController.php
   - Removed the old code that rasterized the template's uploaded PDF and
     used it as a full-page background image behind the real CV text. That
     raster image had sample/dummy text baked into its pixels, which is why
     old template content kept mixing with the real candidate's data no
     matter what the AI returned.
   - Added AI re-processing support to update() (edit page): if you attach
     a replacement file, or tick "re-run AI", it re-runs Ollama, same as a
     fresh upload. Manual field edits in the same request still win over
     whatever AI returns.

3. TemplateController.php + templates/form.blade.php
   - Added a "Layout" dropdown to the template Add/Edit form. This is the
     field that decides which real, code-based design is used to render a
     CV — so your Template CRUD (add/edit/delete) stays fully meaningful;
     nothing is hardcoded outside of it.
   - Removed the "use PDF as background" checkbox/logic entirely, since
     that was the root cause of the mixing bug. The uploaded PDF/thumbnail
     is now purely a reference/preview image on the picker screen and is
     never used to render a user's actual CV.

4. cvs/pdf.blade.php
   - Completely rewritten. Instead of a raster background, it now has 5
     real HTML/CSS layouts (no images at all) that closely match the 5
     designs you shared: Modern Teal, Executive Slate, Gray & Golden,
     Centered Classic (Black & White Simple), and Art Director. The layout
     used is read from Template->settings->layout (set via the new CRUD
     dropdown). Every field is the real, AI-parsed candidate data — nothing
     can ever be a leftover sample/placeholder.

5. CV.php (model)
   - rendered_data now also returns "languages" so the templates can show
     the Languages section.

6. TemplateSeeder.php
   - Updated to (re)create your 5 real templates with the correct slug,
     accent color, and layout already set, in case you ever want to reset
     the templates table.

NOT INCLUDED / STILL A MANUAL STEP ON YOUR MACHINE
====================================================
- Poppler (pdftoppm) and Tesseract paths in .env — you already set these up
  correctly (PDFTOPPM_BINARY / TESSERACT_BINARY), no change needed here.
- Your Ollama model — you're on qwen2.5:3b-instruct, which is fine paired
  with the format:"json" fix above.
