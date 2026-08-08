<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo e($cv->title); ?></title>
    <style>
        @page {
            margin: 0;
            size: A4;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            background: #ffffff;
            position: relative;
        }
        
        .pdf-container {
            position: relative;
            width: 100%;
            min-height: 100vh;
        }
        
        /* ===== BACKGROUND PDF ===== */
        .pdf-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            opacity: 0.15;
        }
        
        .pdf-background img {
            width: 100%;
            height: auto;
        }
        
        /* ===== CONTENT OVERLAY ===== */
        .pdf-content {
            position: relative;
            z-index: 1;
            padding: 40px 50px;
            min-height: 100vh;
        }
        
        /* ===== HEADER ===== */
        .cv-header {
            text-align: center;
            padding-bottom: 15px;
            margin-bottom: 20px;
            border-bottom: 3px solid #2563eb;
        }
        
        .cv-name {
            font-size: 32px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .cv-title {
            font-size: 18px;
            color: #2563eb;
            font-weight: 500;
            margin-bottom: 6px;
        }
        
        .cv-contact {
            font-size: 13px;
            color: #475569;
        }
        
        .cv-contact span {
            margin: 0 10px;
        }
        
        /* ===== SECTIONS ===== */
        .cv-section {
            margin-bottom: 16px;
        }
        
        .cv-section-title {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #2563eb;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        
        .cv-summary {
            font-size: 13px;
            line-height: 1.6;
            color: #334155;
        }
        
        /* ===== SKILLS ===== */
        .cv-skill-tag {
            display: inline-block;
            background: #f1f5f9;
            padding: 4px 14px;
            border-radius: 15px;
            font-size: 12px;
            margin: 0 5px 6px 0;
            color: #1e293b;
        }
        
        /* ===== EXPERIENCE ===== */
        .cv-exp-item {
            margin-bottom: 12px;
        }
        
        .cv-exp-header {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            margin-bottom: 2px;
        }
        
        .cv-exp-position {
            font-weight: 600;
            font-size: 14px;
            color: #0f172a;
        }
        
        .cv-exp-company {
            color: #475569;
            font-weight: 500;
        }
        
        .cv-exp-date {
            font-size: 12px;
            color: #94a3b8;
            font-weight: 500;
        }
        
        .cv-exp-desc {
            font-size: 13px;
            color: #475569;
            margin-top: 3px;
            line-height: 1.5;
            padding-left: 0;
        }
        
        /* ===== EDUCATION ===== */
        .cv-edu-item {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            padding: 5px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .cv-edu-item:last-child {
            border-bottom: none;
        }
        
        .cv-edu-degree {
            font-weight: 600;
            font-size: 14px;
        }
        
        .cv-edu-institution {
            color: #475569;
        }
        
        .cv-edu-year {
            color: #94a3b8;
            font-size: 12px;
            font-weight: 500;
        }
        
        /* ===== LANGUAGES ===== */
        .cv-lang-tag {
            display: inline-block;
            background: #f1f5f9;
            padding: 3px 14px;
            border-radius: 15px;
            font-size: 12px;
            margin: 0 5px 6px 0;
            color: #1e293b;
        }
        
        /* ===== FOOTER ===== */
        .cv-footer {
            margin-top: 25px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            font-size: 11px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php
        $data = $cv->rendered_data ?? [];
        $accent = data_get($cv->template?->settings, 'accent', '#2563eb');
    ?>

    <div class="pdf-container">
        <!-- ===== BACKGROUND IMAGE (Your Uploaded PDF Template) ===== -->
        <?php if(!empty($bgBase64)): ?>
            <div class="pdf-background">
                <img src="data:image/png;base64,<?php echo e($bgBase64); ?>" alt="Template Background" />
            </div>
        <?php endif; ?>

        <!-- ===== CONTENT OVERLAY ===== -->
        <div class="pdf-content">
            <!-- ===== HEADER ===== -->
            <div class="cv-header" style="border-bottom-color: <?php echo e($accent); ?>;">
                <div class="cv-name"><?php echo e(data_get($data, 'name', $cv->title)); ?></div>
                <?php if(data_get($data, 'title')): ?>
                    <div class="cv-title" style="color: <?php echo e($accent); ?>;"><?php echo e(data_get($data, 'title')); ?></div>
                <?php endif; ?>
                <div class="cv-contact">
                    <?php if(data_get($data, 'email')): ?>
                        <span>✉️ <?php echo e(data_get($data, 'email')); ?></span>
                    <?php endif; ?>
                    <?php if(data_get($data, 'phone')): ?>
                        <span>📱 <?php echo e(data_get($data, 'phone')); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ===== SUMMARY ===== -->
            <?php if(data_get($data, 'summary')): ?>
            <div class="cv-section">
                <div class="cv-section-title" style="color: <?php echo e($accent); ?>; border-bottom-color: <?php echo e($accent); ?>20;">Professional Summary</div>
                <div class="cv-summary"><?php echo e(data_get($data, 'summary')); ?></div>
            </div>
            <?php endif; ?>

            <!-- ===== SKILLS ===== -->
            <?php if(count(data_get($data, 'skills', []))): ?>
            <div class="cv-section">
                <div class="cv-section-title" style="color: <?php echo e($accent); ?>; border-bottom-color: <?php echo e($accent); ?>20;">Skills</div>
                <div>
                    <?php $__currentLoopData = data_get($data, 'skills', []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $skill): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <span class="cv-skill-tag"><?php echo e($skill); ?></span>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ===== EXPERIENCE ===== -->
            <?php if(count(data_get($data, 'experience', []))): ?>
            <div class="cv-section">
                <div class="cv-section-title" style="color: <?php echo e($accent); ?>; border-bottom-color: <?php echo e($accent); ?>20;">Work Experience</div>
                <?php $__currentLoopData = data_get($data, 'experience', []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $exp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="cv-exp-item">
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
                            <div class="cv-exp-desc"><?php echo e(data_get($exp, 'description')); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <?php endif; ?>

            <!-- ===== EDUCATION ===== -->
            <?php if(count(data_get($data, 'education', []))): ?>
            <div class="cv-section">
                <div class="cv-section-title" style="color: <?php echo e($accent); ?>; border-bottom-color: <?php echo e($accent); ?>20;">Education</div>
                <?php $__currentLoopData = data_get($data, 'education', []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $edu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="cv-edu-item">
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

            <!-- ===== LANGUAGES ===== -->
            <?php if(count(data_get($data, 'languages', []))): ?>
            <div class="cv-section">
                <div class="cv-section-title" style="color: <?php echo e($accent); ?>; border-bottom-color: <?php echo e($accent); ?>20;">Languages</div>
                <div>
                    <?php $__currentLoopData = data_get($data, 'languages', []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lang): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <span class="cv-lang-tag">
                            <?php echo e(data_get($lang, 'language')); ?>

                            <?php if(data_get($lang, 'proficiency')): ?>
                                (<?php echo e(data_get($lang, 'proficiency')); ?>)
                            <?php endif; ?>
                        </span>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ===== FOOTER ===== -->
            <div class="cv-footer">
                Generated by ResumeForge · <?php echo e(now()->format('F j, Y')); ?>

            </div>
        </div>
    </div>
</body>
</html><?php /**PATH C:\laragon\www\ai-cv-builder\resources\views/cvs/pdf.blade.php ENDPATH**/ ?>