<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Redirecting...</title>
</head>
<body>
    <p>Issue updated successfully. Redirecting...</p>
    <script>
        window.parent.location.href = '{{ $url }}';
    </script>
</body>
</html>
