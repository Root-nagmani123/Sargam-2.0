<fieldset>
    <div class="row">
        @foreach ($options as $key => $option)
        <div class="col-3">
            <div class="form-check py-2">
                <input type="checkbox" name="{{ $name }}" value="{{ $key }}" class="form-check-input"
                    id="{{ $id.$loop->index }}" {{ $checked ? 'checked' : '' }}>
                <label class="form-check-label" for="{{ $id.$loop->index }}">{{ $option }}</label>
            </div>
        </div>
        @endforeach
    </div>
    @error($name)
    <span class="text-danger">{{ $message }}</span>
    @enderror
</fieldset>