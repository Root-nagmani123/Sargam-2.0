{{--
    Shown instead of a PDF that DomPDF cannot render.

    Print opens in a new tab (window.open) and Download navigates the current one,
    so this has to be a standalone page rather than a redirect-with-toast — a
    flash message would land on a tab the user is not looking at, or on a tab with
    no grid behind it. It carries no layout for the same reason.

    @see \App\Http\Controllers\Mess\Concerns\RaisesExportLimits::guardPdfRowCount()
--}}
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $reportTitle }} — too large for PDF</title>
    <style>
        :root { color-scheme: light; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: #f4f6f8;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: #1d2939;
        }
        .card {
            max-width: 34rem;
            width: 100%;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(16, 24, 40, .1);
            padding: 2rem;
        }
        h1 { margin: 0 0 .75rem; font-size: 1.25rem; color: #004384; }
        p { margin: 0 0 1rem; line-height: 1.55; }
        .count { font-weight: 600; }
        ul { margin: 0 0 1.25rem; padding-left: 1.15rem; line-height: 1.65; }
        .back {
            display: inline-block;
            padding: .55rem 1.1rem;
            border: 1px solid #d0d5dd;
            border-radius: 8px;
            background: #fff;
            color: #004384;
            text-decoration: none;
            font-weight: 500;
        }
        .back:hover { background: #f2f7fc; border-color: #004384; }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ $reportTitle }} is too large for a PDF</h1>

        <p>
            This report currently holds
            <span class="count">{{ number_format($rowCount) }}</span> rows.
            The PDF layout can hold up to
            <span class="count">{{ number_format($maxRows) }}</span>.
        </p>

        <p>Nothing has been cut short — you have two ways to get the full data:</p>

        <ul>
            <li>Narrow the filters or the search on the grid, then print again.</li>
            <li>Use <strong>Download &rsaquo; CSV</strong> or <strong>Excel (.xlsx)</strong>, which have no row limit.</li>
        </ul>

        <a class="back" href="javascript:history.back()">Go back</a>
    </div>
</body>
</html>
