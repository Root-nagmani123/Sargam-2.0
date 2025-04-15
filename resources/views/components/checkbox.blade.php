<fieldset>
    <div class="form-check py-2">
        @foreach ($options as $option)  
            <input type="checkbox" name="{{ $name }}" value="{{ $option }}" class="form-check-input" id="{{ $id.$loop->index }}" {{ $checked ? 'checked' : '' }}>
            <label class="form-check-label" for="{{ $id.$loop->index }}">{{ $option }}</label>
        @endforeach
        @error($name)
            <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>
</fieldset>