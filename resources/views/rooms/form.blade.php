@extends('layout')

@section('content')
    <h2>{{ $room->exists ? 'Edit room '.$room->room_no : 'Add room' }}</h2>
    <p class="subtitle">
        {{ $room->exists
            ? 'Update the room details, rates, and inclusions.'
            : 'Add a new room to one of your locations.' }}
    </p>

    <form id="room-form" class="room-form" method="POST" action="{{ $room->exists ? route('rooms.update', $room) : route('rooms.store') }}" enctype="multipart/form-data">
        @csrf
        @if ($room->exists)
            @method('PUT')
        @endif

        <div class="box">
            <h3>Room information</h3>
            <div class="form-grid">
                <div>
                    <label for="room_no">Room No <span class="req">*</span></label>
                    <input id="room_no" name="room_no" value="{{ old('room_no', $room->room_no) }}" placeholder="A-101" required>
                </div>
                <div>
                    <label for="location_id">Location <span class="req">*</span></label>
                    <select id="location_id" name="location_id" required>
                        <option value="">Select location</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}" @selected(old('location_id', $room->location_id) == $location->id)>{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="capacity">Capacity <span class="req">*</span></label>
                    <input id="capacity" name="capacity" type="number" min="1" value="{{ old('capacity', $room->capacity ?? 1) }}" required>
                    <p class="hint">Maximum number of guests.</p>
                </div>
                <div>
                    <label for="status">Status <span class="req">*</span></label>
                    <select id="status" name="status" required>
                        @foreach (\App\Models\Room::STATUSES as $status)
                            <option value="{{ $status }}" @selected(old('status', $room->status ?? 'Available') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="box">
            <h3>Rates</h3>
            <div class="rate-grid">
                <div>
                    <label for="rate">Per night</label>
                    <div class="input-with-prefix">
                        <span>₱</span>
                        <input id="rate" name="rate" type="number" min="0" step="0.01" value="{{ old('rate', $room->rate ?? '') }}" placeholder="0.00">
                    </div>
                </div>
                <div>
                    <label for="rate_hourly">Per hour</label>
                    <div class="input-with-prefix">
                        <span>₱</span>
                        <input id="rate_hourly" name="rate_hourly" type="number" min="0" step="0.01" value="{{ old('rate_hourly', $room->rate_hourly ?? '') }}" placeholder="0.00">
                    </div>
                </div>
                <div>
                    <label for="rate_daytour">Daily</label>
                    <div class="input-with-prefix">
                        <span>₱</span>
                        <input id="rate_daytour" name="rate_daytour" type="number" min="0" step="0.01" value="{{ old('rate_daytour', $room->rate_daytour ?? '') }}" placeholder="0.00">
                    </div>
                </div>
            </div>
            <p class="hint">Enter the rates this room offers. At least one rate is required; per night, per hour, and daily can be left empty individually.</p>
        </div>

        <div class="box">
            <h3>Inclusions</h3>
            <label for="inclusion_input">Add an inclusion</label>
            <div class="inclusion-input-row">
                <input id="inclusion_input" placeholder="e.g. Television, Wi-Fi" autocomplete="off">
                <button type="button" id="add-inclusion">+ Add</button>
            </div>
            <p class="hint">Type an item and press Enter or click Add.</p>

            <div id="inclusion-list" class="inclusion-list"></div>
            <input type="hidden" name="inclusions" id="inclusions" value="{{ old('inclusions', $room->inclusions ?? '') }}">
        </div>

        <div class="box">
            <h3>Photos</h3>
            <p class="hint">Shown to guests on the public site. The first photo is the cover of the room card. Up to {{ \App\Models\Room::MAX_PHOTOS }} photos.</p>

            @if ($room->photoUrls())
                <div class="photo-grid">
                    @foreach ($room->photoUrls() as $i => $url)
                        <label class="photo-tile">
                            <img src="{{ $url }}" alt="Photo {{ $i + 1 }} of {{ $room->room_no }}">
                            <span class="check">
                                <input type="checkbox" name="remove_photos[]" value="{{ $room->photos[$i] }}">
                                Remove
                            </span>
                        </label>
                    @endforeach
                </div>
            @endif

            <label for="photos">Add photos</label>
            <input type="file" id="photos" name="photos[]" accept=".jpg,.jpeg,.png,.webp" multiple>
            <small class="hint">JPG, PNG or WebP, up to 2 MB each. You can choose several at once.</small>
        </div>

        <div class="actions">
            <button type="submit" class="primary">{{ $room->exists ? 'Save changes' : 'Add room' }}</button>
            <a class="btn" href="{{ route('rooms.index') }}">Cancel</a>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('room-form');
            const input = document.getElementById('inclusion_input');
            const list = document.getElementById('inclusion-list');
            const hiddenInput = document.getElementById('inclusions');

            // The saved value may be a JSON array, or comma / new-line separated text.
            let inclusions = [];
            const existingValue = hiddenInput.value.trim();
            if (existingValue !== '') {
                try {
                    const parsed = JSON.parse(existingValue);
                    if (Array.isArray(parsed)) {
                        inclusions = parsed.map(item => String(item).trim()).filter(item => item !== '');
                    }
                } catch (error) {
                    inclusions = existingValue.split(/[\n,]+/).map(item => item.trim()).filter(item => item !== '');
                }
            }

            function saveInclusions() {
                hiddenInput.value = JSON.stringify(inclusions);
            }

            function renderInclusions() {
                list.innerHTML = '';

                if (inclusions.length === 0) {
                    const empty = document.createElement('div');
                    empty.className = 'inclusion-empty';
                    empty.textContent = 'No inclusions added yet.';
                    list.appendChild(empty);
                    return;
                }

                inclusions.forEach(function (item, index) {
                    const row = document.createElement('div');
                    row.className = 'inclusion-item';

                    const name = document.createElement('span');
                    name.className = 'inclusion-name';
                    name.textContent = item;

                    const removeButton = document.createElement('button');
                    removeButton.type = 'button';
                    removeButton.className = 'small';
                    removeButton.textContent = 'Remove';
                    removeButton.setAttribute('aria-label', 'Remove ' + item);
                    removeButton.addEventListener('click', function () {
                        inclusions.splice(index, 1);
                        saveInclusions();
                        renderInclusions();
                    });

                    row.appendChild(name);
                    row.appendChild(removeButton);
                    list.appendChild(row);
                });
            }

            function addInclusion() {
                const value = input.value.trim();
                input.value = '';
                if (value === '') {
                    return;
                }

                const exists = inclusions.some(item => item.toLowerCase() === value.toLowerCase());
                if (! exists) {
                    inclusions.push(value);
                    saveInclusions();
                    renderInclusions();
                }
            }

            document.getElementById('add-inclusion').addEventListener('click', function () {
                addInclusion();
                input.focus();
            });

            input.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    addInclusion();
                }
            });

            // Keep an item that was typed but not added yet.
            form.addEventListener('submit', addInclusion);

            renderInclusions();
        });
    </script>
@endsection
