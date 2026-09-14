<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

        <title>
            <?php if (! empty(trim($__env->yieldContent('title')))): ?>
                <?php echo $__env->yieldContent('title'); ?> - <?php echo e(site_name()); ?>

            <?php else: ?>
                <?php echo e(site_name()); ?>

            <?php endif; ?>
        </title>

        <!-- Favicon -->
        <?php if(site_favicon()): ?>
            <link rel="icon" type="image/x-icon" href="<?php echo e(site_favicon()); ?>">
        <?php endif; ?>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Lucide Icons -->
        <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

        <!-- Scripts -->
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    </head>
    <body class="font-sans antialiased bg-white text-black">
        <main>
            <?php echo $__env->yieldContent('content'); ?>
        </main>

        <script>
            if (window.lucide) {
                lucide.createIcons();
            }
        </script>
    </body>
    </html>


<?php /**PATH C:\xampp\htdocs\tesla.com\resources\views/layouts/error.blade.php ENDPATH**/ ?>