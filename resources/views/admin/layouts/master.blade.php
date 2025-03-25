<!DOCTYPE html>
<html lang="zxx">

<head>
    @include('admin.layouts.pre_header')
    <title>@yield('title')</title>
</head>

<body>
    <?php //@include('admin.layouts.sidebar') ?>

    <div class="container-fluid">
        <div class="main-content d-flex flex-column">
            @include('admin.layouts.header')

            <div class="content">
                @yield('content')
            </div>

            @include('admin.layouts.footer')
        </div>
    </div>

</body>

</html>