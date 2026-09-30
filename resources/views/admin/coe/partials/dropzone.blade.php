{{-- COE — file dropzone (drag & drop or pick). Behaviour is CoeGrid.dropzone()
     in public/js/coe-grid.js, which finds its parts by class inside the root.
     Params:
       $id     — root id (required); the file input is "{$id}Input"
       $field  — file input name
       $accept — <input accept> value
       $hint   — the "File type: …" line
       $icon   — bootstrap-icon for the chosen-file chip (default bi-file-earmark) --}}
<div class="coe-dropzone" id="{{ $id }}">
    <i class="bi bi-cloud-arrow-up coe-dropzone__icon" aria-hidden="true"></i>
    <p class="coe-dropzone__title">Drop file here</p>
    <p class="coe-dropzone__hint">{{ $hint }}</p>

    <div class="coe-dropzone__or">Or</div>

    <label class="coe-dropzone__pick mb-0" for="{{ $id }}Input">
        <i class="bi bi-cloud-arrow-up" aria-hidden="true"></i><span>Upload</span>
    </label>
    <input type="file" id="{{ $id }}Input" name="{{ $field }}" class="visually-hidden coe-dropzone__input"
           accept="{{ $accept }}">

    <div class="coe-dropzone__file d-none">
        <i class="bi {{ $icon ?? 'bi-file-earmark' }} text-primary" aria-hidden="true"></i>
        <span class="coe-dropzone__name"></span>
        <button type="button" class="coe-dropzone__clear" aria-label="Remove file">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </div>
    <p class="coe-dropzone__error d-none" role="alert"></p>
</div>
