<!DOCTYPE html>
<html lang="zxx">

<head>
    <?php echo $__env->make('admin.layouts.pre_header', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <title><?php echo $__env->yieldContent('title'); ?></title>
</head>

<body>
    <!-- Preloader -->
    <div class="preloader">
        <img src="<?php echo e(asset('admin_assets/images/logos/favicon.ico')); ?>" alt="loader" class="lds-ripple img-fluid">
    </div>
    <div id="main-wrapper">
        <?php echo $__env->make('admin.layouts.sidebar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <div class="page-wrapper">
            <?php echo $__env->make('admin.layouts.header', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <?php echo $__env->make('admin.layouts.aside', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <div class="body-wrapper">
                <?php echo $__env->yieldContent('content'); ?>
            </div>
        </div>
    </div>
    <?php echo $__env->make('admin.layouts.footer', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
</body>

</html><?php /**PATH C:\xampp1\htdocs\Sargam-2.0\resources\views/admin/layouts/master.blade.php ENDPATH**/ ?>