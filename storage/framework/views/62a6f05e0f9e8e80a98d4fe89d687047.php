
<?php $__env->startSection('content'); ?>
<section class="section-heading page-title">
    <div><p class="eyebrow">TEMPLATE LIBRARY</p><h1>Manage templates</h1><p class="muted">Add PDF designs to the database and update their details whenever you need.</p></div>
    <a class="button" href="<?php echo e(route('templates.create')); ?>">+ Add template</a>
</section>
<div class="card-list">
    <?php $__empty_1 = true; $__currentLoopData = $templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $template): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div class="cv-row">
            <span class="file-icon">PDF</span>
            <span><strong><?php echo e($template->name); ?></strong><small><?php echo e($template->pdf_path ? 'PDF uploaded' : 'PDF missing'); ?> · <?php echo e($template->is_active ? 'Active' : 'Inactive'); ?></small></span>
            <a class="button button-secondary button-small" href="<?php echo e(route('templates.edit', $template)); ?>">Edit</a>
            <form method="POST" action="<?php echo e(route('templates.destroy', $template)); ?>" onsubmit="return confirm('Delete this template?');">
                <?php echo csrf_field(); ?>
                <?php echo method_field('DELETE'); ?>
                <button class="button button-danger button-small" type="submit">Delete</button>
            </form>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="empty-state"><h3>No templates yet.</h3><p>Add your first PDF design.</p></div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\ai-cv-builder\resources\views/templates/manage.blade.php ENDPATH**/ ?>