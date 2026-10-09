@extends('layout')

@section('content')
    <h2>Check-in</h2>
    <p class="subtitle">One step at a time. The guest is at reception (steps 1 and 2 are done). Click a step above to go back to it.</p>

    @if ($roomId && $rooms->firstWhere('id', $roomId))

        <p class="note" style="margin:0 0 16px">Room <b>{{ $rooms->firstWhere('id', $roomId)->room_no }}</b> ({{ $rooms->firstWhere('id', $roomId)->location->name }}) is already chosen for this check-in. You can change it in step 6.</p>

    @endif


    @include('partials.steps', ['steps' => App\Models\Booking::CHECKIN_STEPS, 'current' => 3])

    <form method="POST" action="/checkin" id="checkinForm" autocomplete="off">
        @csrf

        {{-- STEP 3 --}}
        <div class="box step" data-step="3">
            <div class="step-title">
                <span class="num">3</span>
                <h3>Fill out the check-in form</h3>
            </div>

            <label for="booking_id">Has a reservation?</label>

            <select
                id="booking_id"
                name="booking_id"
                onchange="location.href = '/checkin' + (this.value ? '?booking=' + this.value : '')"
            >
                <option value="">No reservation (walk-in guest)</option>

                @foreach ($reservations as $r)
                    <option
                        value="{{ $r->id }}"
                        {{ optional($selected)->id == $r->id ? 'selected' : '' }}
                    >
                        {{ $r->guest_name }} — {{ $r->room->room_no }} — {{ $r->check_in->format('M d, Y') }} — {{ $r->room->rateSummary() }}
                    </option>
                @endforeach
            </select>

            <p class="hint">Choosing a reservation fills in the form below.</p>

            <div class="form-grid">

                <div>
                    <label for="guest_name">
                        Guest name <span class="req">*</span>
                    </label>

                    <input
                        id="guest_name"
                        name="guest_name"
                        value="{{ old('guest_name', optional($selected)->guest_name) }}"
                        placeholder="Full name"
                        required
                    >
                </div>

                <div>
                    <label for="guest_type">
                        Guest type <span class="req">*</span>
                    </label>

                    <select id="guest_type" name="guest_type">
                        @foreach (['Visitor', 'Contractor'] as $type)
                            <option
                                {{ old('guest_type', optional($selected)->guest_type) == $type ? 'selected' : '' }}
                            >
                                {{ $type }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="company">Company</label>

                    <input
                        id="company"
                        name="company"
                        value="{{ old('company', optional($selected)->company) }}"
                        placeholder="Company name"
                    >
                </div>

                <div>
                    <label for="contact_no">
                        Contact number <span class="req">*</span>
                    </label>

                    <input
                        id="contact_no"
                        name="contact_no"
                        value="{{ old('contact_no', optional($selected)->contact_no) }}"
                        placeholder="09xx xxx xxxx"
                        required
                    >
                </div>

                <div class="full">
                    <label for="address">
                        Address <span class="req">*</span>
                    </label>

                    <input
                        id="address"
                        name="address"
                        value="{{ old('address', optional($selected)->address) }}"
                        placeholder="House no., street, barangay, city"
                        required
                    >
                </div>

                <div>
                    <label for="no_of_guests">
                        Number of guests <span class="req">*</span>
                    </label>

                    <input
                        id="no_of_guests"
                        name="no_of_guests"
                        type="number"
                        min="1"
                        value="{{ old('no_of_guests', optional($selected)->no_of_guests ?? 1) }}"
                        required
                    >
                </div>

                {{-- EXPECTED CHECK-OUT --}}
                <div>
                    <label for="check_out_date">
                        Expected check-out <span class="req">*</span>
                    </label>

                    <div class="input-row">
                        <input
                            id="check_out_date"
                            type="date"
                            min="{{ date('Y-m-d') }}"
                            value="{{ old(
                                'check_out_date',
                                $selected
                                    ? $selected->check_out->format('Y-m-d')
                                    : date('Y-m-d', strtotime('+1 day'))
                            ) }}"
                            required
                        >

                        <input
                            id="check_out_time"
                            type="time"
                            value="{{ old(
                                'check_out_time',
                                $selected && $selected->check_out_time
                                    ? substr($selected->check_out_time, 0, 5)
                                    : '12:00'
                            ) }}"
                            required
                        >
                    </div>

                    <input
                        type="hidden"
                        id="check_out_datetime"
                        name="check_out_datetime"
                        value="{{ old('check_out_datetime') }}"
                    >

                    <p class="hint">
                        Select the expected date and time the guest will leave.
                    </p>
                </div>

                {{-- GUEST LIST (shown when there are 2 or more guests) --}}
                <div class="full" id="guestListBox" hidden>
                    <label>
                        Other guests in the group <span class="req">*</span>
                    </label>

                    <div
                        class="guest-list"
                        id="guestList"
                        data-guests="{{ json_encode(array_values(old('guest_list', optional($selected)->guest_list ?? []))) }}"
                    ></div>

                    <p class="hint">
                        The guest named above is guest 1. Enter the name, address and contact number of each other guest.
                        Everyone is checked in to the room assigned in step 6.
                    </p>
                </div>

                <div class="full">
                    <label for="remarks">Purpose / notes</label>

                    <textarea
                        id="remarks"
                        name="remarks"
                        placeholder="Purpose of stay or other notes"
                    >{{ old('remarks', optional($selected)->remarks) }}</textarea>
                </div>

            </div>
        </div>

        {{-- STEP 4 --}}
        <div class="box step" data-step="4">
            <div class="step-title">
                <span class="num">4</span>
                <h3>Verification</h3>
            </div>

            <p class="muted" style="margin:0">
                Confirm that the guest is expected and the details above are correct.
            </p>

            <label class="check">
                <input
                    type="checkbox"
                    name="verified"
                    required
                    value="1"
                    {{ old('verified') ? 'checked' : '' }}
                >
                I have verified the guest's identity and the details on the form.
            </label>
        </div>

        {{-- STEP 5 --}}
        <div class="box step" data-step="5">
            <div class="step-title">
                <span class="num">5</span>
                <h3>Surrender valid ID</h3>
            </div>

            <div class="form-grid">

                <div>
                    <label for="id_type">
                        Type of ID <span class="req">*</span>
                    </label>

                    <select id="id_type" name="id_type" required>
                        <option value="">Select ID type</option>

                        @foreach (\App\Models\Booking::ID_TYPES as $type)
                            <option {{ old('id_type') == $type ? 'selected' : '' }}>
                                {{ $type }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="id_number">
                        ID number <span class="req">*</span>
                    </label>

                    <input
                        id="id_number"
                        name="id_number"
                        required
                        value="{{ old('id_number') }}"
                        autocomplete="off"
                    >
                </div>

            </div>

            <label class="check">
                <input
                    type="checkbox"
                    name="id_surrendered"
                    required
                    value="1"
                    {{ old('id_surrendered') ? 'checked' : '' }}
                >
                The guest surrendered the ID. Reception keeps it until check-out.
            </label>
        </div>

        {{-- STEP 6 --}}
        <div class="box step" data-step="6">
            <div class="step-title">
                <span class="num">6</span>
                <h3>Assign accommodation (Guest Villa / Barracks)</h3>
            </div>

            @php
                // Start on a rate the chosen room really offers. Rooms that do not
                // offer the selected rate are unselected by the script below.
                $chosenRoom = $rooms->firstWhere('id', old('room_id', optional($selected)->room_id ?? $roomId));
                $defaultRate = 'nightly';

                if ($chosenRoom && $chosenRoom->rate === null) {
                    $defaultRate = $chosenRoom->rate_hourly !== null ? 'hourly' : 'daytour';
                }
            @endphp

            <label for="billing_rate_type">Rate type <span class="req">*</span></label>
            <select
                id="billing_rate_type"
                name="billing_rate_type"
                data-start-at="{{ $selected ? $selected->check_in->format('Y-m-d').'T'.substr($selected->check_in_time ?: '14:00', 0, 5) : now()->format('Y-m-d\TH:i') }}"
                required
            >
                @foreach (['nightly' => 'Per night', 'hourly' => 'Per hour', 'daytour' => 'Daily'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('billing_rate_type', $defaultRate) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="hint">Choose the rate that applies to this stay. Every room assigned to the group must offer this rate.</p>

            <label for="room_id">
                Room <span class="req">*</span>
            </label>

            <select id="room_id" name="room_id" required>
                <option value="">Select an available room</option>

                @foreach ($rooms->groupBy('location.name') as $location => $list)
                    <optgroup label="{{ $location }}">
                        @foreach ($list as $room)
                            <option
                                value="{{ $room->id }}"
                                data-name="{{ $room->room_no }}"
                                data-capacity="{{ $room->capacity }}"
                                data-rate-nightly="{{ $room->rate ?? '' }}"
                                data-rate-hourly="{{ $room->rate_hourly ?? '' }}"
                                data-rate-daytour="{{ $room->rate_daytour ?? '' }}"
                                data-inclusions="{{ json_encode($room->inclusionsList()) }}"
                                data-location="{{ $room->location->name }}"
                                data-location="{{ $room->location->name }}"
                                {{ old('room_id', optional($selected)->room_id ?? $roomId) == $room->id ? 'selected' : '' }}
                            >
                                {{ $room->room_no }} (good for {{ $room->capacity }}) — {{ $room->rateSummary() }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>

            {{-- shown when the group does not fit in the room: what is wrong and which rooms to add --}}
            <div id="roomAdvice" class="alert" role="alert" hidden></div>

            {{-- more rooms for the same group --}}
            <div id="extraRoomsBox" hidden>
                <label>Additional rooms for this group</label>

                <div class="extra-rooms">
                    @foreach ($rooms as $room)
                        <label class="check" data-extra="{{ $room->id }}">
                            <input
                                type="checkbox"
                                name="extra_rooms[]"
                                value="{{ $room->id }}"
                                data-capacity="{{ $room->capacity }}"
                                data-name="{{ $room->room_no }}"
                                data-rate-nightly="{{ $room->rate ?? '' }}"
                                data-rate-hourly="{{ $room->rate_hourly ?? '' }}"
                                data-rate-daytour="{{ $room->rate_daytour ?? '' }}"
                                data-inclusions="{{ json_encode($room->inclusionsList()) }}"
                                {{ in_array($room->id, old('extra_rooms', [])) ? 'checked' : '' }}
                            >
                            {{ $room->room_no }} · {{ $room->location->name }} (good for {{ $room->capacity }}) — {{ $room->rateSummary() }}
                        </label>
                    @endforeach
                </div>

                <p class="hint">Guests fill the first room, then the next. Each room gets its own record and room slip.</p>
            </div>

            <div id="selectedRoomDetails" class="selected-room-details" hidden>
                <h4>Room details</h4>
                <div id="selectedRoomDetailItems"></div>
            </div>

            <div id="estimatedCost" class="estimate-box" aria-live="polite">
                <h4>Estimated accommodation cost</h4>
                <div id="estimatedCostLines"></div>
                <strong id="estimatedCostTotal">Select a rate and room to see the estimate.</strong>
                <p class="hint">Estimate only; final charges can change during the stay.</p>
            </div>

            <p class="hint">
                Only rooms that are Available are listed.

                @if ($selected && $selected->room->status != 'Available')
                    <b>
                        The reserved room {{ $selected->room->room_no }}
                        is {{ $selected->room->status }}, so pick another room.
                    </b>
                @endif
            </p>
        </div>

        <div class="box">
            <div class="actions">
                <button type="button" id="stepBack" hidden>
                    Back
                </button>

                <button type="button" class="primary" id="stepNext" hidden>
                    Next step
                </button>

                <button type="submit" class="success-btn" id="stepFinish">
                    Complete check-in
                </button>

                <a class="btn" href="/dashboard">Cancel</a>
            </div>

            <p class="hint" id="stepHint">
                Next: step 7, the room assignment slip is shown, then the guest proceeds to the room (step 8).
            </p>
        </div>
    </form>

    <div class="box">
        <h3>Currently checked in</h3>

        @if ($current->isEmpty())
            <div class="empty">No guests are currently checked in.</div>
        @else
            <div class="table-wrap">
                <table>
                    <tr>
                        <th>Guest</th>
                        <th>Company</th>
                        <th>Room</th>
                        <th>Check-in</th>
                        <th>Expected check-out</th>
                        <th></th>
                    </tr>

                    @foreach ($current as $b)
                        <tr>
                            <td>{{ $b->guest_name }}</td>
                            <td>{{ $b->company ?: '—' }}</td>
                            <td>{{ $b->room->room_no }}</td>

                            <td>
                                {{ $b->check_in->format('M d, Y') }}
                                @if ($b->check_in_time)
                                    <br>
                                    <small class="muted">{{ $b->timeText('check_in_time') }}</small>
                                @endif
                            </td>

                            <td>
                                {{ $b->check_out->format('M d, Y') }}
                                @if ($b->check_out_time)
                                    <br>
                                    <small class="muted">{{ $b->timeText('check_out_time') }}</small>
                                @endif
                            </td>

                            <td>
                                <a
                                    class="btn small"
                                    href="/checkin/{{ $b->id }}/slip"
                                >
                                    Room slip
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>

            {{ $current->links('partials.pager') }}
        @endif
    </div>

    <script>
        document.getElementById('checkinForm').addEventListener('submit', function () {
            const date = document.getElementById('check_out_date').value;
            const time = document.getElementById('check_out_time').value;

            document.getElementById('check_out_datetime').value = date + 'T' + time;
        });

        // One row (name, address, contact number) for every guest after the first.
        const guestCount = document.getElementById('no_of_guests');
        const guestListBox = document.getElementById('guestListBox');
        const guestList = document.getElementById('guestList');
        const guestFields = [
            ['name', 'full name', 150],
            ['address', 'address', 255],
            ['contact_no', 'contact number', 50],
        ];
        let guests = JSON.parse(guestList.dataset.guests || '[]');

        function showGuestList() {
            // keep what was already typed
            guestList.querySelectorAll('input').forEach(function (field) {
                guests[field.dataset.row] = guests[field.dataset.row] || {};
                guests[field.dataset.row][field.dataset.field] = field.value;
            });

            const others = Math.min(Math.max((parseInt(guestCount.value) || 1) - 1, 0), 50);

            guestList.innerHTML = '';
            guestListBox.hidden = others === 0;

            for (let i = 0; i < others; i++) {
                const row = document.createElement('div');
                row.className = 'guest-row';

                guestFields.forEach(function ([key, label, max]) {
                    const field = document.createElement('input');

                    field.name = 'guest_list[' + i + '][' + key + ']';
                    field.value = (guests[i] || {})[key] || '';
                    field.placeholder = 'Guest ' + (i + 2) + ' ' + label;
                    field.maxLength = max;
                    field.required = true;
                    field.dataset.row = i;
                    field.dataset.field = key;
                    field.setAttribute('aria-label', 'Guest ' + (i + 2) + ' ' + label);

                    row.appendChild(field);
                });

                guestList.appendChild(row);
            }
        }

        guestCount.addEventListener('input', showGuestList);
        showGuestList();

        // ---- Step 6: the group must fit in the chosen rooms ----
        const roomSelect = document.getElementById('room_id');
        const rateType = document.getElementById('billing_rate_type');
        const roomAdvice = document.getElementById('roomAdvice');
        const extraBox = document.getElementById('extraRoomsBox');
        const extraChecks = Array.from(extraBox.querySelectorAll('input[type=checkbox]'));
        const checkOutDate = document.getElementById('check_out_date');
        const checkOutTime = document.getElementById('check_out_time');
        const estimateLines = document.getElementById('estimatedCostLines');
        const estimateTotal = document.getElementById('estimatedCostTotal');
        const selectedRoomDetails = document.getElementById('selectedRoomDetails');
        const selectedRoomDetailItems = document.getElementById('selectedRoomDetailItems');

        function inclusionsFor(element) {
            try {
                return JSON.parse(element.dataset.inclusions || '[]');
            } catch (error) {
                return [];
            }
        }

        function rateAttribute(element) {
            return element.dataset['rate' + rateType.value.charAt(0).toUpperCase() + rateType.value.slice(1)];
        }

        function supportsSelectedRate(element) {
            return rateAttribute(element) !== undefined && rateAttribute(element) !== '';
        }

        function applyRateAvailability() {
            Array.from(roomSelect.options).forEach(function (option) {
                // The chosen room is never unselected: when it does not offer the
                // rate, checkRooms() explains which rates it has instead.
                if (option.value !== '') {
                    option.disabled = ! supportsSelectedRate(option) && ! option.selected;
                }
            });

            extraChecks.forEach(function (box) {
                const available = supportsSelectedRate(box);
                box.disabled = ! available;
                box.closest('label').hidden = ! available;
                if (! available) {
                    box.checked = false;
                }
            });
        }

        function updateEstimate() {
            estimateLines.innerHTML = '';
            selectedRoomDetailItems.innerHTML = '';
            const start = new Date(rateType.dataset.startAt);
            const end = new Date(checkOutDate.value + 'T' + checkOutTime.value);
            let units = 0;

            if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end <= start) {
                estimateTotal.textContent = 'Enter a valid check-out date and time.';
                return;
            }

            if (rateType.value === 'hourly') {
                units = Math.max(1, Math.ceil((end - start) / 3600000));
            } else {
                const startDay = Date.UTC(start.getFullYear(), start.getMonth(), start.getDate());
                const endDay = Date.UTC(end.getFullYear(), end.getMonth(), end.getDate());
                const days = Math.floor((endDay - startDay) / 86400000);
                units = rateType.value === 'daytour' ? Math.max(1, days + 1) : Math.max(1, days);
            }

            const selectedRooms = [];
            const mainOption = roomSelect.selectedOptions[0];
            if (mainOption && mainOption.value && supportsSelectedRate(mainOption)) {
                selectedRooms.push(mainOption);
            }
            extraChecks.forEach(function (box) {
                if (box.checked && supportsSelectedRate(box)) {
                    selectedRooms.push(box);
                }
            });

            if (selectedRooms.length === 0) {
                selectedRoomDetails.hidden = true;
                estimateTotal.textContent = 'Select a rate and room to see the estimate.';
                return;
            }

            selectedRoomDetails.hidden = false;
            let total = 0;
            selectedRooms.forEach(function (room) {
                const amount = Number(rateAttribute(room)) * units;
                total += amount;

                const detail = document.createElement('div');
                detail.className = 'selected-room-detail';
                const title = document.createElement('b');
                title.textContent = room.dataset.name + (room.dataset.location ? ' — ' + room.dataset.location : '');
                const capacity = document.createElement('span');
                capacity.textContent = 'Capacity: ' + room.dataset.capacity + ' guests';
                const selectedRate = document.createElement('span');
                selectedRate.textContent = '₱' + Number(rateAttribute(room)).toFixed(2) + ' / '
                    + (rateType.value === 'hourly' ? 'hour' : rateType.value === 'daytour' ? 'day' : 'night');
                const includes = document.createElement('span');
                const roomInclusions = inclusionsFor(room);
                includes.textContent = roomInclusions.length ? 'Inclusions: ' + roomInclusions.join(' · ') : 'No inclusions listed';
                detail.append(title, capacity, selectedRate, includes);
                selectedRoomDetailItems.appendChild(detail);

                const line = document.createElement('p');
                line.className = 'estimate-line';
                line.textContent = room.dataset.name + ' — ₱' + Number(rateAttribute(room)).toFixed(2)
                    + ' × ' + units + ' ' + (rateType.value === 'hourly' ? 'hour(s)' : rateType.value === 'daytour' ? 'day(s)' : 'night(s)')
                    + ' = ₱' + amount.toFixed(2);
                estimateLines.appendChild(line);

                if (roomInclusions.length) {
                    const included = document.createElement('p');
                    included.className = 'estimate-inclusions';
                    included.textContent = room.dataset.name + ' includes: ' + roomInclusions.join(', ');
                    estimateLines.appendChild(included);
                }
            });

            estimateTotal.textContent = 'Estimated total: ₱' + total.toFixed(2);
        }

        // which rooms to add so everyone has a bed: the smallest room that covers
        // the rest, or else the biggest rooms first. null = not enough rooms.
        function suggestExtras(need, free) {
            const picked = [];

            while (need > 0) {
                const left = free.filter(function (box) { return ! picked.includes(box); });
                const fits = left.filter(function (box) { return Number(box.dataset.capacity) >= need; })
                    .sort(function (a, b) { return a.dataset.capacity - b.dataset.capacity; })[0];
                const next = fits || left.sort(function (a, b) { return b.dataset.capacity - a.dataset.capacity; })[0];

                if (! next) {
                    return null;
                }

                picked.push(next);
                need -= Number(next.dataset.capacity);
            }

            return picked;
        }

        function checkRooms() {
            const guestsCount = parseInt(guestCount.value) || 1;
            const main = roomSelect.selectedOptions[0];
            const mainId = roomSelect.value;

            // the main room cannot also be an additional room
            extraChecks.forEach(function (box) {
                const same = box.value === mainId;
                box.closest('label').hidden = same || box.disabled;
                if (same) { box.checked = false; }
            });

            roomSelect.setCustomValidity('');
            roomAdvice.hidden = true;

            if (! mainId) {
                extraBox.hidden = true;
                updateEstimate();
                return;
            }

            // the room stays chosen, but it cannot be billed at a rate it does not offer
            if (! supportsSelectedRate(main)) {
                const rateLabels = { nightly: 'Per night', hourly: 'Per hour', daytour: 'Daily' };
                const offered = Object.keys(rateLabels)
                    .filter(function (type) {
                        return main.dataset['rate' + type.charAt(0).toUpperCase() + type.slice(1)];
                    })
                    .map(function (type) {
                        return rateLabels[type] + ' (₱' + Number(main.dataset['rate' + type.charAt(0).toUpperCase() + type.slice(1)]).toFixed(2) + ')';
                    });

                const message = 'No ' + rateLabels[rateType.value].toLowerCase() + ' rate is available for ' + main.dataset.name + '. '
                    + (offered.length ? 'Rates available for this room: ' + offered.join(', ') + '.' : 'This room has no rates set.');

                roomSelect.setCustomValidity(message);
                roomAdvice.className = 'alert error';
                roomAdvice.textContent = message + ' Choose one of those rate types to continue, or pick another room.';
                roomAdvice.hidden = false;
                extraBox.hidden = true;
                updateEstimate();
                return;
            }

            const mainBox = extraChecks.find(function (box) { return box.value === mainId; });
            const mainCapacity = Number(mainBox.dataset.capacity);
            const ticked = extraChecks.filter(function (box) { return box.checked; });
            const total = mainCapacity + ticked.reduce(function (sum, box) { return sum + Number(box.dataset.capacity); }, 0);

            extraBox.hidden = guestsCount <= mainCapacity && ticked.length === 0;

            if (total >= guestsCount) {
                if (ticked.length > 0) {
                    roomAdvice.className = 'alert success';
                    roomAdvice.textContent = (ticked.length + 1) + ' rooms for ' + guestsCount + ' guests: '
                        + [mainBox].concat(ticked).map(function (box) { return box.dataset.name; }).join(' + ') + '.';
                    roomAdvice.hidden = false;
                }
                updateEstimate();
                return;
            }

            // not enough room: refuse and suggest
            const names = [mainBox].concat(ticked).map(function (box) { return box.dataset.name; }).join(' + ');
            const free = extraChecks.filter(function (box) {
                return box.value !== mainId && ! box.checked && ! box.disabled;
            });
            const suggestion = suggestExtras(guestsCount - total, free);

            roomSelect.setCustomValidity('These rooms cannot take all ' + guestsCount + ' guests.');
            roomAdvice.className = 'alert error';
            roomAdvice.innerHTML = '';
            roomAdvice.append(names + ' is good for ' + total + ' only, but there are ' + guestsCount + ' guests. '
                + (guestsCount - total) + ' more ' + (guestsCount - total === 1 ? 'guest needs' : 'guests need') + ' a room. ');

            if (suggestion) {
                roomAdvice.append('Suggested: add ' + suggestion.map(function (box) {
                    return box.dataset.name + ' (good for ' + box.dataset.capacity + ')';
                }).join(' + ') + ', ' + (ticked.length + 1 + suggestion.length) + ' rooms in total. ');

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'small';
                button.textContent = 'Add suggested ' + (suggestion.length === 1 ? 'room' : 'rooms');
                button.addEventListener('click', function () {
                    suggestion.forEach(function (box) { box.checked = true; });
                    checkRooms();
                });
                roomAdvice.append(button);
            } else {
                roomAdvice.append('There are not enough available rooms for the whole group right now.');
            }

            roomAdvice.hidden = false;
            updateEstimate();
        }

        roomSelect.addEventListener('change', function () {
            applyRateAvailability();
            checkRooms();
        });
        rateType.addEventListener('change', function () {
            applyRateAvailability();
            checkRooms();
        });
        guestCount.addEventListener('input', checkRooms);
        extraChecks.forEach(function (box) { box.addEventListener('change', checkRooms); });
        checkOutDate.addEventListener('change', updateEstimate);
        checkOutTime.addEventListener('change', updateEstimate);
        applyRateAvailability();
        checkRooms();

        // The room picked with "Get room" must stay selected. A browser can put back
        // an older (empty) choice when the page is reloaded or reopened with Back.
        const chosenRoomId = '{{ (int) $roomId ?: '' }}';

        window.addEventListener('pageshow', function () {
            const option = chosenRoomId ? roomSelect.querySelector('option[value="' + chosenRoomId + '"]') : null;

            if (option && ! roomSelect.value) {
                // switch to a rate this room offers, so the room is allowed
                if (! supportsSelectedRate(option)) {
                    rateType.value = ['nightly', 'hourly', 'daytour'].find(function (type) {
                        return option.dataset['rate' + type.charAt(0).toUpperCase() + type.slice(1)];
                    }) || rateType.value;

                    applyRateAvailability();
                }

                roomSelect.value = chosenRoomId;
                checkRooms();
            }
        });

        // ---- One step on screen at a time (steps 3 to 6) ----
        const form = document.getElementById('checkinForm');
        const formSteps = [3, 4, 5, 6];
        let currentStep = 3;
        let reachedStep = 3;

        function stepBox(n) {
            return form.querySelector('.box.step[data-step="' + n + '"]');
        }

        function stepFields(n) {
            return Array.from(stepBox(n).querySelectorAll('input, select, textarea'));
        }

        function stepIsComplete(n) {
            return stepFields(n).every(function (field) {
                return field.checkValidity();
            });
        }

        // the first step from 3 up to "last" that still has something missing
        function firstIncomplete(last) {
            return formSteps.find(function (n) {
                return n <= last && ! stepIsComplete(n);
            });
        }

        function showStep(n) {
            currentStep = n;
            reachedStep = Math.max(reachedStep, n);

            formSteps.forEach(function (step) {
                stepBox(step).hidden = step !== n;
            });

            document.querySelectorAll('.steps li').forEach(function (item) {
                const step = Number(item.dataset.step);
                const done = step < 3 || (step <= 6 && step !== n && step <= reachedStep && stepIsComplete(step));

                item.classList.toggle('done', done);
                item.classList.toggle('now', step === n);
                item.classList.toggle('clickable', step >= 3 && step <= 6);
                item.querySelector('.num').textContent = done ? '✓' : step;
            });

            document.getElementById('stepBack').hidden = n === 3;
            document.getElementById('stepNext').hidden = n === 6;
            document.getElementById('stepFinish').hidden = n !== 6;
            document.getElementById('stepHint').hidden = n !== 6;
        }

        // show the step with something missing and point at the field
        function askToComplete(n) {
            showStep(n);

            stepFields(n).find(function (field) {
                return ! field.checkValidity();
            }).reportValidity();
        }

        function goToStep(n) {
            const missing = firstIncomplete(n - 1);

            if (missing) {
                askToComplete(missing);
            } else {
                showStep(n);
                window.scrollTo(0, 0);
            }
        }

        document.getElementById('stepNext').addEventListener('click', function () {
            goToStep(currentStep + 1);
        });

        document.getElementById('stepBack').addEventListener('click', function () {
            showStep(currentStep - 1);
            window.scrollTo(0, 0);
        });

        document.querySelectorAll('.steps li').forEach(function (item) {
            const step = Number(item.dataset.step);

            if (step >= 3 && step <= 6) {
                item.addEventListener('click', function () {
                    goToStep(step);
                });
            }
        });

        // a hidden step with something missing would silently block the save
        form.addEventListener('submit', function (event) {
            const missing = firstIncomplete(6);

            if (missing) {
                event.preventDefault();
                askToComplete(missing);
            }
        });

        form.noValidate = true;

        // after a refused save, open the step that needs fixing (or the last step)
        if ({{ $errors->any() || session()->has('error') ? 'true' : 'false' }}) {
            reachedStep = 6;
            showStep(firstIncomplete(6) || 6);
        } else {
            showStep(3);
        }
    </script>
@endsection
