@extends('guest.layout')

@section('title', 'Book a room')

@section('content')
    <h2>Book a room</h2>
    <p class="subtitle">Reserving as <b>{{ auth()->user()->name }}</b> ({{ auth()->user()->email }}).</p>

    @if ($rooms->isEmpty())
        <div class="empty">No rooms are open for booking right now. Please check back later.</div>
    @else
        <form class="box book-form" method="POST" action="/book" enctype="multipart/form-data">
            @csrf

            <div class="form-grid">
                <div class="full">
                    <label for="room_id">Room <span class="req">*</span></label>
                    <select id="room_id" name="room_id" required>
                        <option value="">Choose a room</option>
                        @foreach ($rooms as $room)
                            <option value="{{ $room->id }}" data-capacity="{{ $room->capacity }}" {{ old('room_id', $selected) == $room->id ? 'selected' : '' }}>
                                {{ $room->room_no }} · {{ $room->location->name }} · good for {{ $room->capacity }} · {{ $room->rateSummary() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="check_in_datetime">Check-in <span class="req">*</span></label>
                    <input type="datetime-local" id="check_in_datetime" name="check_in_datetime"
                        value="{{ old('check_in_datetime', now()->addDay()->format('Y-m-d').'T14:00') }}"
                        min="{{ now()->format('Y-m-d\TH:i') }}" required>
                </div>

                <div>
                    <label for="check_out_datetime">Check-out <span class="req">*</span></label>
                    <input type="datetime-local" id="check_out_datetime" name="check_out_datetime"
                        value="{{ old('check_out_datetime', now()->addDays(2)->format('Y-m-d').'T12:00') }}"
                        min="{{ now()->format('Y-m-d\TH:i') }}" required>
                </div>

                <div>
                    <label for="no_of_guests">Number of guests <span class="req">*</span></label>
                    <input type="number" id="no_of_guests" name="no_of_guests" value="{{ old('no_of_guests', 1) }}" min="1" required>
                    <small class="hint" id="capacityHint"></small>
                </div>

                <div>
                    <label for="contact_no">Contact number <span class="req">*</span></label>
                    <input type="tel" id="contact_no" name="contact_no" value="{{ old('contact_no') }}" placeholder="e.g. 0917 123 4567" maxlength="50" autocomplete="tel" required>
                </div>

                <div>
                    <label for="id_type">Type of valid ID <span class="req">*</span></label>
                    <select id="id_type" name="id_type" required>
                        <option value="">Select ID type</option>
                        @foreach (\App\Models\Booking::ID_TYPES as $type)
                            <option {{ old('id_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="id_photo">Photo of your ID <span class="req">*</span></label>
                    <input type="file" id="id_photo" name="id_photo" accept=".jpg,.jpeg,.png,.pdf" required>
                    <small class="hint">JPG, PNG or PDF, up to 2 MB. Make sure the name and photo are clear. Only reception and the admin can see it.</small>
                </div>

                <div class="full">
                    <label for="company">Company</label>
                    <input type="text" id="company" name="company" value="{{ old('company') }}" maxlength="150" placeholder="Optional">
                </div>

                <div class="full">
                    <label for="remarks">Notes for reception</label>
                    <textarea id="remarks" name="remarks" maxlength="255" placeholder="Optional, e.g. arriving late">{{ old('remarks') }}</textarea>
                </div>
            </div>

            <p class="hint">Your reservation is confirmed after reception checks your ID. You will get a message here and by email.</p>

            <div class="actions">
                <button type="submit" class="primary">Send reservation</button>
                <a class="btn" href="/#rooms">Back to rooms</a>
            </div>
        </form>

        <script>
            // show how many guests the chosen room can take, and stop a bigger number being typed
            (function () {
                const room = document.getElementById('room_id');
                const guests = document.getElementById('no_of_guests');
                const hint = document.getElementById('capacityHint');

                function showCapacity() {
                    const capacity = room.selectedOptions[0] ? room.selectedOptions[0].dataset.capacity : '';

                    hint.textContent = capacity ? 'This room is good for up to ' + capacity + '.' : '';

                    if (capacity) {
                        guests.max = capacity;
                    } else {
                        guests.removeAttribute('max');
                    }
                }

                room.addEventListener('change', showCapacity);
                showCapacity();
            })();
        </script>
    @endif
@endsection
