
<?php $__env->startSection('content'); ?>
<section class="section-heading page-title">
     <div>
          <p class="eyebrow">DESIGN YOUR STORY</p>
          <h1>Choose a template</h1>
          <p class="muted">Your uploaded CV content stays the same. Pick a visual style, then AI helps polish the wording.</p>
     </div>
     <div class="action-group">
          <a class="button button-secondary" href="<?php echo e(route('templates.manage')); ?>">Manage templates</a>
          <a class="button" href="<?php echo e(route('templates.create')); ?>">+ Add template</a>
     </div>
</section>
<div class="template-grid"><?php $__empty_1 = true; $__currentLoopData = $templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $template): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php ($settings = $template->settings ?? []); ?><a class="template-card" href="<?php echo e(route('templates.preview', $template)); ?>">
          <div class="template-preview" style="--template-accent: <?php echo e($settings['accent'] ?? '#167a74'); ?>">
               <?php if($template->thumbnail): ?>
                    <img src="<?php echo e($template->thumbnail_url); ?>" alt="<?php echo e($template->name); ?> preview">
               <?php else: ?>
                    <b><?php echo e(strtoupper(substr($template->name, 0, 1))); ?></b><i></i><i></i><i class="short"></i>
               <?php endif; ?>
          </div>
          <h3><?php echo e($template->name); ?></h3>
          <p><?php echo e($template->description); ?></p><span class="template-link">Preview template →</span>
     </a><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><div class="empty-state">
          <h3>No templates available yet</h3>
          <p>Run the database seeder to add the starter templates.</p>
     </div><?php endif; ?></div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\ai-cv-builder\resources\views/templates/index.blade.php ENDPATH**/ ?>