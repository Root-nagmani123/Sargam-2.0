FC registration student PDF — fonts (Hindi + English)
======================================================

Place these files in **resources/fonts/mpdf/** (same folder as this README):

  NotoSansDevanagari-Regular.ttf   (required for correct Hindi + Latin in this PDF)
  NotoSansDevanagari-Bold.ttf      (optional; improves bold headings)

Download (Google Noto, raw):

  https://github.com/googlefonts/noto-fonts/raw/main/hinted/ttf/NotoSansDevanagari/NotoSansDevanagari-Regular.ttf
  https://github.com/googlefonts/noto-fonts/raw/main/hinted/ttf/NotoSansDevanagari/NotoSansDevanagari-Bold.ttf

How the PDF is built
--------------------

1. **Chrome headless** (default when `google-chrome` / `chromium` is executable) — best text shaping
   for mixed English and Hindi.

2. **Dompdf** fallback — uses the same HTML; Noto is embedded as **base64 `data:`** `@font-face` rules
   so glyphs load (relative `url(...)` paths are unreliable in Dompdf).

Environment variables (optional)
--------------------------------

  FC_REGISTRATION_PDF_ENGINE=auto|chrome|dompdf
    auto   — try Chrome first, then Dompdf (default)
    chrome — try Chrome only; falls back to Dompdf if Chrome fails
    dompdf — skip Chrome

  FC_REGISTRATION_CHROME_BIN=/full/path/to/chrome
    Override auto-detection of Chrome/Chromium.

Temp files are written under **storage/app/temp/fc-pdf/** (HTML for Chrome) and removed after render.

Dompdf font metrics cache lives under **storage/fonts** (see `config/dompdf.php`). Clear if fonts change.
