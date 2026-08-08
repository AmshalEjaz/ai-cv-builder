<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($title ?? 'ResumeForge'); ?></title>
    <link rel="icon" type="image/png" href="<?php echo e(asset('images/1.png')); ?>">
    <link rel="apple-touch-icon" href="<?php echo e(asset('images/1.png')); ?>">
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="app-shell">
    <header class="topbar">
        <a class="brand" href="<?php echo e(auth()->check() ? route('dashboard') : route('home')); ?>">
            <span class="brand-mark">R</span> ResumeForge
        </a>
        <?php if(auth()->guard()->check()): ?>
            <button class="menu-toggle" type="button" data-menu-toggle aria-label="Open navigation" aria-expanded="false">☰</button>
            <nav class="nav-links authenticated-nav" data-mobile-nav>
                <a href="<?php echo e(route('dashboard')); ?>">Dashboard</a>
                <a href="<?php echo e(route('cvs.index')); ?>">My CVs</a>
                <a href="<?php echo e(route('templates.index')); ?>">Templates</a>
                <a href="<?php echo e(route('templates.manage')); ?>">Manage templates</a>
                <span class="user-name"><?php echo e(auth()->user()->name); ?></span>
                <form method="POST" action="<?php echo e(route('logout')); ?>"><?php echo csrf_field(); ?> <button class="link-button">Logout</button></form>
            </nav>
        <?php else: ?>
            <nav class="nav-links"><a href="<?php echo e(route('login')); ?>">Login</a><a class="button button-small" href="<?php echo e(route('register')); ?>">Get started</a></nav>
        <?php endif; ?>
    </header>
    <main class="page-container">
        <?php if(session('success')): ?> <div class="toast toast-success" data-toast><span class="toast-icon">✓</span><span><?php echo e(session('success')); ?></span><button type="button" data-toast-close aria-label="Close">×</button></div> <?php endif; ?>
        <?php if(session('info')): ?> <div class="toast toast-info" data-toast><span class="toast-icon">i</span><span><?php echo e(session('info')); ?></span><button type="button" data-toast-close aria-label="Close">×</button></div> <?php endif; ?>
        <?php if(session('error')): ?> <div class="toast toast-error" data-toast><span class="toast-icon">!</span><span><?php echo e(session('error')); ?></span><button type="button" data-toast-close aria-label="Close">×</button></div> <?php endif; ?>
        <?php if($errors->any()): ?> <div class="toast toast-error" data-toast><span class="toast-icon">!</span><span><?php echo e($errors->first()); ?></span><button type="button" data-toast-close aria-label="Close">×</button></div> <?php endif; ?>
        <?php echo $__env->yieldContent('content'); ?>
    </main>
</body>
</html>
<?php /**PATH C:\laragon\www\ai-cv-builder\resources\views/layouts/app.blade.php ENDPATH**/ ?>