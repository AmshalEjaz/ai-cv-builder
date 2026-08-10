
<?php $__env->startSection('content'); ?>
<div class="form-page">
    <p class="eyebrow">EDIT DOCUMENT</p>
    <h1>Update your CV</h1>
    <?php ($data = $cv->ai_enhanced_data ?? []); ?>
    <form method="POST" action="<?php echo e(route('cvs.update', $cv)); ?>" class="panel form-stack" enctype="multipart/form-data">
        <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
        <label>CV title<input type="text" name="title" value="<?php echo e(old('title', $cv->title)); ?>" required></label>
        <label>Full name<input type="text" name="data[name]" value="<?php echo e(old('data.name', data_get($data, 'name'))); ?>"></label>
        <label>Professional title<input type="text" name="data[title]" value="<?php echo e(old('data.title', data_get($data, 'title'))); ?>"></label>
        <label>Email<input type="email" name="data[email]" value="<?php echo e(old('data.email', data_get($data, 'email'))); ?>"></label>
        <label>Phone<input type="text" name="data[phone]" value="<?php echo e(old('data.phone', data_get($data, 'phone'))); ?>"></label>
        <label>Professional summary<textarea name="data[summary]" rows="5"><?php echo e(old('data.summary', data_get($data, 'summary'))); ?></textarea></label>
        <label>Skills <small class="field-help">Separate skills with commas</small><input type="text" name="data[skills]" value="<?php echo e(old('data.skills', implode(', ', data_get($data, 'skills', [])))); ?>"></label>
        <label>Template<select name="template_id" required><?php $__currentLoopData = $templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $template): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($template->id); ?>" <?php if($cv->template_id === $template->id): echo 'selected'; endif; ?>><?php echo e($template->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>

        <hr>
        <label>Replace CV file <small class="field-help">Optional — upload a new/updated PDF or DOCX and AI will re-parse it. The fields above (if filled) will still override whatever AI returns.</small>
            <input type="file" name="cv_file" accept=".pdf,.docx">
        </label>
        <label class="check">
            <input type="checkbox" name="reprocess" value="1">
            Re-run AI on the existing file (no new upload) — use this if you only edited fields above but still want AI to re-clean/re-enhance the rest
        </label>

        <button class="button" type="submit">Save changes</button>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\ai-cv-builder\resources\views/cvs/edit.blade.php ENDPATH**/ ?>