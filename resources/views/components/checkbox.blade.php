<fieldset>
    @foreach ($options as $key => $option)
    <div class="form-check py-2">
        <input type="checkbox" name="{{ $name }}" value="{{ $key }}" class="form-check-input" id="{{ $id.$loop->index }}" {{ $checked ? 'checked' : '' }}>
        <label class="form-check-label" for="{{ $id.$loop->index }}">{{ $option }}</label>
    </div>
    @endforeach
    @error($name)
        <span class="text-danger">{{ $message }}</span>
    @enderror
</fieldset>