@extends('layout')

@php
    // the step reception is doing now (1 to 8, 9 = all done)
    $done = $booking->checkout_step;
    $current = $booking->status == 'Checked Out' ? 9 : max(2, $done + 1);

    // box style for a step: finished, doing now, or not yet
    $state = fn ($from, $to = null) => $current > ($to ?? $from) ? 'done' : ($current >= $from ? '' : 'locked');
@endphp

@section('content')
    <div class="page-head">
        <div>
            <h2>Check-out: {{ $booking->guest_name }}</h2>
            <p class="subtitle">Follow the steps in order.</p>
        </div>
        <div class="actions">
            <a class="btn primary" href="/billing/{{ $booking->id }}">View bill</a>
            <a class="btn" href="/checkout">Back to list</a>
        </div>
    </div>

    @include('partials.steps', ['steps' => App\Models\Booking::CHECKOUT_STEPS, 'current' => $current])

    <div class="box">
        <dl class="details">
            <div><dt>Guest</dt><dd>{{ $booking->guest_name }}</dd></div>
            <div><dt>Company</dt><dd>{{ $booking->company ?: '—' }}</dd></div>
            <div><dt>Room</dt><dd>{{ $booking->room->room_no }} ({{ $booking->room->location->name }})</dd></div>
            <div><dt>Room status</dt><dd><span class="badge {{ $booking->room->css() }}">{{ $booking->room->status }}</span></dd></div>
            <div><dt>Checked in</dt><dd>{{ $booking->billingStart()->format('M d, Y h:i A') }}</dd></div>
            <div><dt>ID held</dt><dd>{{ $booking->id_type ?: '—' }} {{ $booking->id_number }}</dd></div>
        </dl>
    </div>

    <div class="box">
        <h3>Guests in this room</h3>
        @include('partials.guests')
    </div>

    {{-- STEP 2 --}}
    <div class="box step {{ $state(2) }}">
        <div class="step-title"><span class="num">{{ $current > 2 ? '✓' : 2 }}</span><h3>Check-out verification</h3></div>
        @if ($current == 2)
            <form method="POST" action="/checkout/{{ $booking->id }}/verify">
                @csrf
                <p style="margin-top:0">Confirm that this is <b>{{ $booking->guest_name }}</b>, staying in <b>{{ $booking->room->room_no }}</b>, and they are checking out.</p>
                <div class="checkout-time">
                    @include('partials.actual-time', ['field' => 'actual_check_out', 'label' => 'check-out', 'min' => $booking->billingStart()])
                    @include('partials.actual-time-reason', ['field' => 'actual_check_out', 'reasonField' => 'checkout_time_reason', 'label' => 'check-out'])
                </div>
                <button type="submit" class="primary">Confirm and continue</button>
                <p class="hint">The room status changes to Check-out.</p>
            </form>
        @else
            <p class="muted" style="margin:0">Guest and room verified.</p>
        @endif
    </div>

    {{-- STEP 3 and 4 --}}
    <div class="box step {{ $state(3, 4) }}">
        <div class="step-title"><span class="num">{{ $current > 4 ? '✓' : '3' }}</span><h3>Room inspection and additional charges</h3></div>
        @if ($current == 3)
            <form method="POST" action="/checkout/{{ $booking->id }}/inspect">
                @csrf
                <div class="form-grid">
                    <div class="full">
                        <label for="inspection_notes">Step 3: Inspection notes</label>
                        <input id="inspection_notes" name="inspection_notes" value="{{ old('inspection_notes') }}" placeholder="e.g. Room in good condition, key returned">
                    </div>
                    <div>
                        <label for="damage_notes">Inspection notes about damages or unpaid charges</label>
                        <input id="damage_notes" name="damage_notes" value="{{ old('damage_notes') }}" placeholder="Leave empty if none">
                    </div>
                    <div>
                        <label for="room_after">After check-out, the room needs <span class="req">*</span></label>
                        <select id="room_after" name="room_after">
                            <option value="Cleaning">Cleaning</option>
                            <option value="Maintenance">Maintenance (repair needed)</option>
                        </select>
                    </div>
                    <div class="full">
                        <label>Additional charges</label>
                        <div id="additionalChargeLines" data-next-index="{{ count(old('additional_charges', $booking->additional_charges ?? [])) }}">
                            @foreach (old('additional_charges', $booking->additional_charges ?? []) as $index => $line)
                                <div class="billing-charge-line">
                                    <select name="additional_charges[{{ $index }}][category]" aria-label="Charge category">
                                        @foreach (['Extra service', 'Damage', 'Other'] as $category)
                                            <option value="{{ $category }}" @selected(($line['category'] ?? '') === $category)>{{ $category }}</option>
                                        @endforeach
                                    </select>
                                    <input name="additional_charges[{{ $index }}][description]" value="{{ $line['description'] ?? '' }}" placeholder="Description" aria-label="Charge description">
                                    <div class="input-with-prefix">
                                        <span>₱</span>
                                        <input name="additional_charges[{{ $index }}][amount]" type="number" min="0" step="0.01" value="{{ $line['amount'] ?? '' }}" placeholder="0.00" aria-label="Charge amount">
                                    </div>
                                    <button type="button" class="danger small remove-charge">Remove</button>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="small" id="addChargeLine">+ Add charge</button>
                        <p class="hint">Add extra services, damages, or other charges. Leave empty if there are none.</p>
                    </div>
                </div>
                <br>
                <button type="submit" class="primary">Save inspection and charges</button>
                <p class="hint">The room status changes to Inspection.</p>
            </form>
        @elseif ($current > 4)
            <dl class="details">
                <div><dt>Inspection</dt><dd>{{ $booking->inspection_notes ?: 'No notes' }}</dd></div>
                <div><dt>Damages / unpaid</dt><dd>{{ $booking->damage_notes ?: 'None' }}</dd></div>
                <div><dt>Additional charges</dt><dd>₱{{ number_format($booking->additionalChargeTotal(), 2) }}</dd></div>
                <div><dt>Room needs</dt><dd>{{ $booking->room_after }}</dd></div>
            </dl>
        @else
            <p class="muted" style="margin:0">Waiting for the verification.</p>
        @endif
    </div>

    {{-- STEP 5 --}}
    <div class="box step {{ $state(5) }}">
        <div class="step-title"><span class="num">{{ $current > 5 ? '✓' : 5 }}</span><h3>Billing and payment</h3></div>
        @if ($current == 5)
            <div class="bill-summary">
                <div class="bill-row"><span>Accommodation</span><span>₱{{ number_format($booking->accommodationCharge(), 2) }}</span></div>
                <div class="bill-row"><span>Additional charges</span><span>₱{{ number_format($booking->additionalChargeTotal(), 2) }}</span></div>
                <div class="bill-row total"><span>Total amount</span><b>₱{{ number_format($booking->totalAmount(), 2) }}</b></div>
                <div class="bill-row"><span>Amount paid</span><span>₱{{ number_format((float) $booking->amount_paid, 2) }}</span></div>
                <div class="bill-row balance">
                    <span>Balance due <span class="badge {{ $booking->paymentStatus() == 'Paid' ? 'free' : ($booking->paymentStatus() == 'Unpaid' ? 'used' : 'checkout') }}">{{ $booking->paymentStatus() }}</span></span>
                    <b>₱{{ number_format($booking->balance(), 2) }}</b>
                </div>
            </div>

            <form class="payment-form" method="POST" action="/checkout/{{ $booking->id }}/settle" data-confirm="Record a payment of ₱{amount} from {{ $booking->guest_name }}?">
                @csrf
                <label for="payment_amount">Payment received <span class="req">*</span></label>
                <div class="payment-row">
                    <div class="input-with-prefix">
                        <span>₱</span>
                        <input id="payment_amount" name="amount" type="number" min="0.01" max="{{ number_format($booking->balance(), 2, '.', '') }}" step="0.01" value="{{ old('amount', number_format($booking->balance(), 2, '.', '')) }}" required>
                    </div>
                    <button type="submit" class="success-btn">Record payment</button>
                </div>
                <p class="hint">Full payment is required before reception can return the ID. A partial payment stays on the bill.</p>
            </form>
        @elseif ($current > 5)
            <p class="muted" style="margin:0">Paid in full: ₱{{ number_format($booking->totalAmount(), 2) }}.</p>
        @else
            <p class="muted" style="margin:0">Waiting for the inspection.</p>
        @endif
    </div>

    {{-- STEP 6 --}}
    <div class="box step {{ $state(6) }}">
        <div class="step-title"><span class="num">{{ $current > 6 ? '✓' : 6 }}</span><h3>Return surrendered ID</h3></div>
        @if ($current == 6)
            <p style="margin-top:0">Return to the guest: <b>{{ $booking->id_type ?: 'ID' }} {{ $booking->id_number }}</b></p>
            <form method="POST" action="/checkout/{{ $booking->id }}/return-id">
                @csrf
                <button type="submit" class="primary">ID returned to guest</button>
            </form>
        @elseif ($current > 6)
            <p class="muted" style="margin:0">ID returned to the guest.</p>
        @else
            <p class="muted" style="margin:0">The ID is returned after the full bill is paid.</p>
        @endif
    </div>

    {{-- STEP 7 and 8 --}}
    <div class="box step {{ $state(7, 8) }}">
        <div class="step-title"><span class="num">{{ $current > 8 ? '✓' : 7 }}</span><h3>Record check-out in guest log</h3></div>
        @if ($current == 7)
            <form method="POST" action="/checkout/{{ $booking->id }}/record" data-confirm="Record the check-out of {{ $booking->guest_name }}?">
                @csrf
                <label for="checkout_notes">Final notes</label>
                <input id="checkout_notes" name="checkout_notes" placeholder="Optional">
                <br><br>
                <button type="submit" class="success-btn big">Record check-out</button>
                <p class="hint">The guest may then leave the facility (step 8). The room goes to {{ $booking->room_after }}.</p>
            </form>
        @elseif ($current > 8)
            <p style="margin:0">
                <b>Checked out on {{ $booking->actual_check_out->format('M d, Y') }}.</b> The guest has left the facility.
                {{ $booking->checkout_notes }}
            </p>
            <div class="actions" style="margin-top:12px">
                <a class="btn" href="/reports">Open guest log</a>
                <a class="btn" href="/dashboard">Back to dashboard</a>
            </div>
        @else
            <p class="muted" style="margin:0">Last step, after the ID is returned.</p>
        @endif
    </div>

    @if ($current == 3)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const lines = document.getElementById('additionalChargeLines');
                const addButton = document.getElementById('addChargeLine');

                function addLine() {
                    const index = Number(lines.dataset.nextIndex);
                    lines.dataset.nextIndex = index + 1;

                    const row = document.createElement('div');
                    row.className = 'billing-charge-line';
                    row.innerHTML = '<select name="additional_charges[' + index + '][category]" aria-label="Charge category" required>'
                        + '<option>Extra service</option><option>Damage</option><option>Other</option></select>'
                        + '<input name="additional_charges[' + index + '][description]" placeholder="Description" aria-label="Charge description" required>'
                        + '<div class="input-with-prefix"><span>₱</span><input name="additional_charges[' + index + '][amount]" type="number" min="0" step="0.01" placeholder="0.00" aria-label="Charge amount" required></div>'
                        + '<button type="button" class="danger small remove-charge">Remove</button>';
                    lines.appendChild(row);
                }

                addButton.addEventListener('click', addLine);
                lines.addEventListener('click', function (event) {
                    if (event.target.classList.contains('remove-charge')) {
                        event.target.closest('.billing-charge-line').remove();
                    }
                });
            });
        </script>
    @endif
@endsection
