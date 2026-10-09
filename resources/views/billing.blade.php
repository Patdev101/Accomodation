@extends('layout')

@section('content')
    <div class="page-head no-print">
        <div>
            <h2>{{ $isEstimate ? 'Estimated bill' : 'Guest bill' }}</h2>
            <p class="subtitle">Booking #{{ $booking->id }}</p>
        </div>
        <div class="actions">
            <button type="button" class="primary" onclick="window.print()">Print bill</button>
            <a class="btn" href="{{ in_array($booking->status, ['Checked In', 'Checking Out', 'Checked Out']) ? '/checkout/'.$booking->id : '/bookings' }}">Back</a>
        </div>
    </div>

    @if ($isEstimate)
        <div class="alert warning no-print">This is an estimate. Additional charges are confirmed during the room inspection.</div>
    @endif

    <div class="box invoice">
        <div class="invoice-heading">
            <div>
                <h2>Guest Accommodation</h2>
                <p>Mindoro Marine Manufacturing Corporation</p>
            </div>
            <div class="invoice-number">
                <b>{{ $isEstimate ? 'ESTIMATE' : 'BILL' }} #{{ str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT) }}</b>
                <span>{{ now()->format('M d, Y h:i A') }}</span>
                <span class="badge {{ $booking->paymentStatus() === 'Paid' ? 'free' : ($booking->paymentStatus() === 'Partially Paid' ? 'checkout' : 'used') }}">
                    {{ $booking->paymentStatus() }}
                </span>
            </div>
        </div>

        <h3>Guest and stay details</h3>
        <dl class="details">
            <div><dt>Guest / group</dt><dd>{{ $booking->guest_name }}@if ($booking->no_of_guests > 1) ({{ $booking->no_of_guests }} guests)@endif</dd></div>
            <div><dt>Company</dt><dd>{{ $booking->company ?: '—' }}</dd></div>
            <div><dt>Room</dt><dd>{{ $booking->room->room_no }} — {{ $booking->room->location->name }}</dd></div>
            <div><dt>Check-in</dt><dd>{{ $booking->billingStart()->format('M d, Y h:i A') }}</dd></div>
            <div><dt>{{ $booking->checkout_verified_at ? 'Check-out' : 'Expected check-out' }}</dt><dd>{{ $booking->billingEnd()->format('M d, Y h:i A') }}</dd></div>
        </dl>

        @if ($booking->no_of_guests > 1)
            <p><b>Group:</b> {{ collect($booking->guests())->pluck('name')->implode(', ') }}</p>
        @endif

        <h3>Bill details</h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Description</th><th>Calculation</th><th>Amount</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Accommodation ({{ $booking->billingRateLabel() }})</td>
                        <td>₱{{ number_format((float) ($booking->billing_rate ?? $booking->room->rate ?? 0), 2) }} × {{ $booking->billingUnits() }} {{ $booking->billingRateType() === 'hourly' ? 'hour(s)' : ($booking->billingRateType() === 'daytour' ? 'day(s)' : 'night(s)') }}</td>
                        <td>₱{{ number_format($booking->accommodationCharge(), 2) }}</td>
                    </tr>
                    @forelse ($booking->additional_charges ?? [] as $line)
                        <tr>
                            <td>{{ $line['category'] ?? 'Other' }}: {{ $line['description'] ?? 'Additional charge' }}</td>
                            <td>—</td>
                            <td>₱{{ number_format((float) ($line['amount'] ?? 0), 2) }}</td>
                        </tr>
                    @empty
                        @if ($booking->additionalChargeTotal() > 0)
                            <tr><td>Additional charges</td><td>{{ $booking->damage_notes ?: 'Other charges' }}</td><td>₱{{ number_format($booking->additionalChargeTotal(), 2) }}</td></tr>
                        @endif
                    @endforelse
                </tbody>
                <tfoot>
                    <tr><th colspan="2">Accommodation charge</th><th>₱{{ number_format($booking->accommodationCharge(), 2) }}</th></tr>
                    <tr><th colspan="2">Additional charges</th><th>₱{{ number_format($booking->additionalChargeTotal(), 2) }}</th></tr>
                    <tr><th colspan="2">Total amount</th><th>₱{{ number_format($booking->totalAmount(), 2) }}</th></tr>
                    <tr><th colspan="2">Amount paid</th><th>₱{{ number_format((float) $booking->amount_paid, 2) }}</th></tr>
                    <tr><th colspan="2">Balance</th><th>₱{{ number_format($booking->balance(), 2) }}</th></tr>
                </tfoot>
            </table>
        </div>

        <p class="invoice-note">Payment status: <b>{{ $booking->paymentStatus() }}</b></p>
        <p class="hint">This bill does not include taxes or online payment processing.</p>
    </div>
@endsection
