# mPDF-compatible Noto Sans Devanagari

These are copies of `resources/fonts/mpdf/NotoSansDevanagari-*.ttf` with **3 GSUB
lookups removed** (LookupType 5, Format 3 — "contextual substitution, format 3").

## Why

mPDF 8.2's TrueType parser (`vendor/mpdf/mpdf/src/TTFontFile.php`) does not support
GSUB LookupType 5 Format 3 and throws a fatal `FontException` (the message says
"GPOS…" but it is raised from `_getGSUBtables()`). The stock Noto font therefore
**crashes** mPDF and cannot be embedded — which is why the document-form PDFs used
to fall back to GNU FreeSerif, whose Devanagari conjuncts (e.g. the `क्र` rakaar in
`क्र. सं.`) render incorrectly.

The removed lookups are not needed for conjunct formation: `rkrf`/`half`/`cjct`
(rakaar, half-forms, conjuncts) live in LookupType 1/2/4, which mPDF parses fine,
and mPDF runs its own Indic syllable reordering (`src/Shaper/Indic.php`). GPOS
(mark positioning, incl. the nukta on `ज़`) is left intact.

Used by `FcJoiningDocumentFormController::generateAndStorePdf()`, which overrides the
`freeserif` font entry (the font `autoLangToFont` maps Devanagari to) with these files.

## Do not overwrite the originals

`resources/fonts/mpdf/` is used by the headless-Chrome / Dompdf registration-PDF path
(`ReportController`), which embeds the font via `@font-face` and needs the full,
unmodified GSUB/GPOS tables. Keep both copies.

## Regenerating

    python3 - <<'PY'
    from fontTools.ttLib import TTFont
    from fontTools.ttLib.tables import otTables
    for name in ['NotoSansDevanagari-Regular','NotoSansDevanagari-Bold']:
        f=TTFont(f'resources/fonts/mpdf/{name}.ttf')
        for l in f['GSUB'].table.LookupList.Lookup:
            if l.LookupType==5 and any(getattr(st,'Format',None)==3 for st in l.SubTable):
                ss=otTables.SingleSubst(); ss.mapping={'.notdef':'.notdef'}
                l.LookupType=1; l.SubTable=[ss]; l.SubTableCount=1
        f.save(f'resources/fonts/mpdf-otl/{name}.ttf')
    PY
