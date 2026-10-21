<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — Preview</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        body { background: #f0f2f5; margin: 0; }
        .preview-bar {
            position: sticky; top: 0; z-index: 100;
            background: #1c3a5e; color: #fff;
            padding: 10px 20px;
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 2px 8px rgba(0,0,0,.25);
        }
        .preview-bar .title { font-size: 15px; font-weight: 600; letter-spacing: .3px; }
        .preview-bar .actions { display: flex; gap: 10px; }
        .btn-download {
            background: #fff; color: #1c3a5e; border: none;
            padding: 7px 20px; border-radius: 6px;
            font-weight: 600; font-size: 14px; text-decoration: none;
            display: inline-flex; align-items: center; gap: 6px;
            transition: background .15s;
        }
        .btn-download:hover { background: #e8f0fa; color: #1c3a5e; }
        .btn-back {
            background: transparent; color: #fff; border: 1px solid rgba(255,255,255,.4);
            padding: 7px 16px; border-radius: 6px;
            font-size: 14px; text-decoration: none;
            display: inline-flex; align-items: center; gap: 6px;
            transition: border-color .15s;
        }
        .btn-back:hover { border-color: #fff; color: #fff; }
        iframe {
            display: block;
            width: 100%; height: calc(100vh - 52px);
            border: none;
        }
    </style>
</head>
<body>
    <div class="preview-bar">
        <span class="title">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 24 24" style="vertical-align:middle;margin-right:6px"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM6 20V4h5v7h7v9H6z"/></svg>
            {{ $title }}
        </span>
        <div class="actions">
            <a href="{{ url()->previous() }}" class="btn-back">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
                Back
            </a>
            <a href="{{ $downloadUrl }}" class="btn-download">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M5 20h14v-2H5v2zm7-18v10.17l-3.59-3.58L7 10l5 5 5-5-1.41-1.41L13 12.17V2h-1z"/></svg>
                Download PDF
            </a>
        </div>
    </div>

    <iframe src="{{ $streamUrl }}" title="{{ $title }} Preview"></iframe>
</body>
</html>
