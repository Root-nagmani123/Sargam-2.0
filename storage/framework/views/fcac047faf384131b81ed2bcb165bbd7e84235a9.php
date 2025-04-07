<!doctype html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <title><?php echo e(config('app.name', 'Laravel')); ?></title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <!-- <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet"> -->

    <!-- Scripts -->
 

    <!-- Google font-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
    <link href="../../css2?family=Montserrat:wght@200;300;400;500;600;700;800&amp;display=swap" rel="stylesheet">
    <link rel="icon" href="<?php echo e(asset('admin_assets/images/favicon.ico')); ?>" type="image/x-icon">
  <link rel="shortcut icon" href="<?php echo e(asset('admin_assets/images/favicon.ico')); ?>" type="image/x-icon">
    <link rel="stylesheet" type="text/css" href="<?php echo e(asset('admin_assets/css/font-awesome.css')); ?>">
    <!-- ico-font-->
    <link rel="stylesheet" type="text/css" href="<?php echo e(asset('admin_assets/css/vendors/icofont.css')); ?>">
    <!-- Themify icon-->
    <link rel="stylesheet" type="text/css" href="<?php echo e(asset('admin_assets/css/vendors/themify.css')); ?>">
    <!-- Flag icon-->
    <link rel="stylesheet" type="text/css" href="<?php echo e(asset('admin_assets/css/vendors/flag-icon.css')); ?>">
    <!-- Feather icon-->
    <link rel="stylesheet" type="text/css" href="<?php echo e(asset('admin_assets/css/vendors/feather-icon.css')); ?>">
    <!-- Plugins css start-->
    <!-- Plugins css Ends-->
    <!-- Bootstrap css-->
    <link rel="stylesheet" type="text/css" href="<?php echo e(asset('admin_assets/css/vendors/bootstrap.css')); ?>">
    <!-- App css-->
    <link rel="stylesheet" type="text/css" href="<?php echo e(asset('admin_assets/css/style.css')); ?>">
    <link id="color" rel="stylesheet" href="<?php echo e(asset('admin_assets/css/color-1.css')); ?>" media="screen">
    <!-- Responsive css-->
    <link rel="stylesheet" type="text/css" href="<?php echo e(asset('admin_assets/css/responsive.css')); ?>">
</head>

<body>
    <div id="app">

        <main>
            <?php echo $__env->yieldContent('content'); ?>
        </main>
    </div>
     <!-- latest jquery-->
     <script src="<?php echo e(asset('admin_assets/js/jquery.min.js')); ?>"></script>
    <!-- Bootstrap js-->
    <script src="<?php echo e(asset('admin_assets/js/bootstrap/bootstrap.bundle.min.js')); ?>"></script>
    <!-- feather icon js-->
    <script src="<?php echo e(asset('admin_assets/js/icons/feather-icon/feather.min.js')); ?>"></script>
    <script src="<?php echo e(asset('admin_assets/js/icons/feather-icon/feather-icon.js')); ?>"></script>
    <!-- scrollbar js-->
    <!-- Sidebar jquery-->
    <script src="<?php echo e(asset('admin_assets/js/config.js')); ?>"></script>
    <!-- Plugins JS start-->
    <!-- calendar js-->
    <!-- Plugins JS Ends-->
    <!-- Theme js-->
    <script src="<?php echo e(asset('admin_assets/js/script.js')); ?>"></script>
</body>

</html><?php /**PATH C:\xampp1\htdocs\Sargam-2.0\resources\views/layouts/app.blade.php ENDPATH**/ ?>