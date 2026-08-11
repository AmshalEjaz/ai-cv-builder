<?php $__env->startSection('content'); ?>
<section class="section-heading page-title">
    <div>
        <p class="eyebrow">CV DETAILS</p>
        <h1><?php echo e($cv->title); ?></h1>
        <p class="muted"><?php echo e($cv->original_filename); ?></p>
    </div>
    <div class="action-group">
        <a class="button button-secondary" href="<?php echo e(route('cvs.edit', $cv)); ?>">Edit</a>
        <a class="button" href="<?php echo e(route('cvs.download', $cv)); ?>">Download PDF</a>
        <a class="button button-secondary" href="<?php echo e(route('cvs.index')); ?>">Back</a>
    </div>
</section>

<div class="cv-preview-wrapper">
    <div class="cv-preview-card">
        <?php
            $data = $cv->rendered_data ?? [];
            $accent = data_get($cv->template?->settings, 'accent', '#2563eb');
        ?>

        <style>
            .cv-preview-wrapper {
                max-width: 900px;
                margin: 0 auto;
                padding: 20px;
            }

            .cv-preview-card {
                background: #ffffff;
                border-radius: 16px;
                box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
                padding: 48px 56px;
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                color: #1a1a2e;
                border: 1px solid #eef2f6;
                transition: all 0.2s ease;
            }

            /* ===== HEADER ===== */
            .cv-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                padding-bottom: 24px;
                margin-bottom: 28px;
                border-bottom: 2px solid #f0f4f8;
                flex-wrap: wrap;
                gap: 16px;
            }

            .cv-header-left .name {
                font-size: 32px;
                font-weight: 700;
                color: <?php echo e($accent); ?>;
                margin: 0 0 4px 0;
                letter-spacing: -0.5px;
            }

            .cv-header-left .title {
                font-size: 18px;
                color: #64748b;
                margin: 0;
                font-weight: 500;
            }

            .cv-header-right {
                text-align: right;
                font-size: 14px;
                color: #475569;
                line-height: 1.6;
            }

            .cv-header-right .contact-item {
                display: block;
                margin-bottom: 2px;
            }

            .cv-header-right .contact-item strong {
                color: #1e293b;
                font-weight: 600;
            }

            /* ===== SECTIONS ===== */
            .cv-section {
                margin-bottom: 28px;
            }

            .cv-section:last-child {
                margin-bottom: 0;
            }

            .cv-section-title {
                font-size: 13px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 1.2px;
                color: <?php echo e($accent); ?>;
                margin: 0 0 12px 0;
                padding-bottom: 8px;
                border-bottom: 2px solid #f0f4f8;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .cv-section-title::before {
                content: '';
                display: inline-block;
                width: 4px;
                height: 18px;
                background: <?php echo e($accent); ?>;
                border-radius: 2px;
            }

            /* ===== SUMMARY ===== */
            .cv-summary {
                font-size: 15px;
                line-height: 1.7;
                color: #334155;
                margin: 0;
            }

            /* ===== SKILLS ===== */
            .cv-skills {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }

            .cv-skill-tag {
                background: #f1f5f9;
                color: #1e293b;
                padding: 6px 16px;
                border-radius: 20px;
                font-size: 13px;
                font-weight: 500;
                transition: all 0.15s ease;
                border: 1px solid transparent;
            }

            .cv-skill-tag:hover {
                background: <?php echo e($accent); ?>;
                color: #ffffff;
                border-color: <?php echo e($accent); ?>;
                transform: translateY(-1px);
            }

            /* ===== EXPERIENCE ===== */
            .cv-experience-item {
                margin-bottom: 20px;
                padding-bottom: 20px;
                border-bottom: 1px solid #f1f5f9;
            }

            .cv-experience-item:last-child {
                border-bottom: none;
                margin-bottom: 0;
                padding-bottom: 0;
            }

            .cv-exp-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                flex-wrap: wrap;
                gap: 8px;
                margin-bottom: 4px;
            }

            .cv-exp-position {
                font-size: 16px;
                font-weight: 600;
                color: #0f172a;
            }

            .cv-exp-company {
                font-size: 15px;
                color: #475569;
                font-weight: 500;
            }

            .cv-exp-date {
                font-size: 13px;
                color: #94a3b8;
                font-weight: 500;
                background: #f8fafc;
                padding: 2px 12px;
                border-radius: 12px;
                white-space: nowrap;
            }

            .cv-exp-description {
                font-size: 14px;
                line-height: 1.6;
                color: #475569;
                margin: 6px 0 0 0;
            }

            /* ===== EDUCATION ===== */
            .cv-education-item {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                flex-wrap: wrap;
                gap: 8px;
                padding: 12px 0;
                border-bottom: 1px solid #f1f5f9;
            }

            .cv-education-item:last-child {
                border-bottom: none;
            }

            .cv-edu-degree {
                font-size: 15px;
                font-weight: 600;
                color: #0f172a;
            }

            .cv-edu-institution {
                font-size: 14px;
                color: #475569;
                font-weight: 500;
            }

            .cv-edu-year {
                font-size: 13px;
                color: #94a3b8;
                font-weight: 500;
                background: #f8fafc;
                padding: 2px 12px;
                border-radius: 12px;
                white-space: nowrap;
            }

            /* ===== FOOTER ===== */
            .cv-footer {
                margin-top: 32px;
                padding-top: 20px;
                border-top: 2px solid #f0f4f8;
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 12px;
            }

            .cv-footer-info {
                font-size: 13px;
                color: #94a3b8;
            }

            .cv-footer-info strong {
                color: #475569;
                font-weight: 600;
            }

            .cv-footer-badge {
                background: #f1f5f9;
                color: #475569;
                padding: 4px 14px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: 500;
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }

            .cv-footer-badge .dot {
                width: 6px;
                height: 6px;
                border-radius: 50%;
                background: #22c55e;
                display: inline-block;
            }

            /* ===== RESPONSIVE ===== */
            @media (max-width: 640px) {
                .cv-preview-card {
                    padding: 24px 20px;
                }

                .cv-header {
                    flex-direction: column;
                }

                .cv-header-right {
                    text-align: left;
                    width: 100%;
                }

                .cv-header-left .name {
                    font-size: 26px;
                }

                .cv-exp-header {
                    flex-direction: column;
                }

                .cv-education-item {
                    flex-direction: column;
                }

                .cv-footer {
                    flex-direction: column;
                    align-items: flex-start;
                }
            }

            /* Print styles */
            @media print {
                .cv-preview-card {
                    box-shadow: none;
                    border: none;
                    padding: 20px;
                }
            }
        </style>

        <div class="cv-header">
            <div class="cv-header-left">
                <h1 class="name"><?php echo e(data_get($data, 'name', 'Your Name')); ?></h1>
                <?php if(data_get($data, 'title')): ?>
                    <p class="title"><?php echo e(data_get($data, 'title')); ?></p>
                <?php endif; ?>
            </div>
            <div class="cv-header-right">
                <?php if(data_get($data, 'email')): ?>
                    <span class="contact-item">✉️ <strong><?php echo e(data_get($data, 'email')); ?></strong></span>
                <?php endif; ?>
                <?php if(data_get($data, 'phone')): ?>
                    <span class="contact-item">📱 <strong><?php echo e(data_get($data, 'phone')); ?></strong></span>
                <?php endif; ?>
                <?php if(data_get($data, 'address')): ?>
                    <span class="contact-item">📍 <strong><?php echo e(data_get($data, 'address')); ?></strong></span>
                <?php endif; ?>
            </div>
        </div>

        <?php if(data_get($data, 'summary')): ?>
        <div class="cv-section">
            <h2 class="cv-section-title">Professional Summary</h2>
            <p class="cv-summary"><?php echo e(data_get($data, 'summary')); ?></p>
        </div>
        <?php endif; ?>

        <?php if(count(data_get($data, 'skills', []))): ?>
        <div class="cv-section">
            <h2 class="cv-section-title">Skills</h2>
            <div class="cv-skills">
                <?php $__currentLoopData = data_get($data, 'skills', []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $skill): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <span class="cv-skill-tag"><?php echo e($skill); ?></span>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <?php endif; ?>

       
        <?php if(count(data_get($data, 'experience', []))): ?>
        <div class="cv-section">
            <h2 class="cv-section-title">Work Experience</h2>
            <?php $__currentLoopData = data_get($data, 'experience', []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $exp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="cv-experience-item">
                    <div class="cv-exp-header">
                        <div>
                            <span class="cv-exp-position"><?php echo e(data_get($exp, 'position', 'Position')); ?></span>
                            <span class="cv-exp-company">· <?php echo e(data_get($exp, 'company', 'Company')); ?></span>
                        </div>
                        <span class="cv-exp-date">
                            <?php echo e(data_get($exp, 'start_date', '')); ?>

                            <?php if(data_get($exp, 'start_date') && data_get($exp, 'end_date')): ?> — <?php endif; ?>
                            <?php echo e(data_get($exp, 'end_date', 'Present')); ?>

                        </span>
                    </div>
                    <?php if(data_get($exp, 'description')): ?>
                        <p class="cv-exp-description"><?php echo e(data_get($exp, 'description')); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php endif; ?>

       
        <?php if(count(data_get($data, 'education', []))): ?>
        <div class="cv-section">
            <h2 class="cv-section-title">Education</h2>
            <?php $__currentLoopData = data_get($data, 'education', []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $edu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="cv-education-item">
                    <div>
                        <span class="cv-edu-degree"><?php echo e(data_get($edu, 'degree', 'Degree')); ?></span>
                        <span class="cv-edu-institution">· <?php echo e(data_get($edu, 'institution', 'Institution')); ?></span>
                    </div>
                    <?php if(data_get($edu, 'year')): ?>
                        <span class="cv-edu-year"><?php echo e(data_get($edu, 'year')); ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php endif; ?>

      
        <?php if(count(data_get($data, 'languages', []))): ?>
        <div class="cv-section">
            <h2 class="cv-section-title">Languages</h2>
            <div style="display:flex; flex-wrap:wrap; gap:12px;">
                <?php $__currentLoopData = data_get($data, 'languages', []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lang): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <span style="background:#f8fafc; padding:6px 16px; border-radius:20px; font-size:14px; color:#1e293b;">
                        <strong><?php echo e(data_get($lang, 'language')); ?></strong>
                        <?php if(data_get($lang, 'proficiency')): ?>
                            <span style="color:#94a3b8; font-weight:400;">(<?php echo e(data_get($lang, 'proficiency')); ?>)</span>
                        <?php endif; ?>
                    </span>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="cv-footer">
            <span class="cv-footer-info">
                Template: <strong><?php echo e($cv->template?->name ?? 'No template'); ?></strong>
                <?php if($cv->template?->pdf_path): ?>
                    <span style="color:#22c55e;">· PDF Design: Uploaded ✓</span>
                <?php endif; ?>
            </span>
            <span class="cv-footer-badge">
                <span class="dot"></span>
                <?php echo e($cv->status ?? 'Draft'); ?>

            </span>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?> 
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\ai-cv-builder\resources\views/cvs/show.blade.php ENDPATH**/ ?>