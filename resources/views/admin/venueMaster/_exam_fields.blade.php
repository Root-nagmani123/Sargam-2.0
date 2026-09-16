{{--
    COE Examination - venue capacity fields (BRD "Venue Master").
    $venue is absent on create; every field is optional so existing venues
    (Timetable/Attendance/Calendar) keep working without them.
--}}
@php($venue = $venue ?? null)

<div class="row">
    <div class="col-md-4">
        <div class="mb-3">
            <label for="building_master_pk" class="form-label">Building</label>
            <select class="form-select @error('building_master_pk') is-invalid @enderror"
                id="building_master_pk" name="building_master_pk">
                <option value="">Select Building</option>
                @foreach($buildings as $building)
                    <option value="{{ $building->pk }}"
                        {{ (string) old('building_master_pk', $venue->building_master_pk ?? '') === (string) $building->pk ? 'selected' : '' }}>
                        {{ $building->building_name }}
                    </option>
                @endforeach
            </select>
            @error('building_master_pk')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="mb-3">
            <label for="floor_master_pk" class="form-label">Floor</label>
            <select class="form-select @error('floor_master_pk') is-invalid @enderror"
                id="floor_master_pk" name="floor_master_pk">
                <option value="">Select Floor</option>
                @foreach($floors as $floor)
                    <option value="{{ $floor->pk }}"
                        {{ (string) old('floor_master_pk', $venue->floor_master_pk ?? '') === (string) $floor->pk ? 'selected' : '' }}>
                        {{ $floor->floor_name }}
                    </option>
                @endforeach
            </select>
            @error('floor_master_pk')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="mb-3">
            <label for="room_number" class="form-label">Room Number</label>
            <input type="text" class="form-control @error('room_number') is-invalid @enderror"
                id="room_number" name="room_number"
                value="{{ old('room_number', $venue->room_number ?? '') }}"
                placeholder="eg. 101">
            @error('room_number')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="mb-3">
            <label for="capacity" class="form-label">Capacity</label>
            <input type="number" min="0" class="form-control @error('capacity') is-invalid @enderror"
                id="capacity" name="capacity"
                value="{{ old('capacity', $venue->capacity ?? '') }}"
                placeholder="eg. 30">
            @error('capacity')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="mb-3">
            <label for="laptop_capacity" class="form-label">Laptop Capacity</label>
            <input type="number" min="0" class="form-control @error('laptop_capacity') is-invalid @enderror"
                id="laptop_capacity" name="laptop_capacity"
                value="{{ old('laptop_capacity', $venue->laptop_capacity ?? '') }}"
                placeholder="eg. 30">
            @error('laptop_capacity')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="mb-3">
            <label for="seating_capacity" class="form-label">Seating Capacity</label>
            <input type="number" min="0" class="form-control @error('seating_capacity') is-invalid @enderror"
                id="seating_capacity" name="seating_capacity"
                value="{{ old('seating_capacity', $venue->seating_capacity ?? '') }}"
                placeholder="eg. 30">
            @error('seating_capacity')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>
