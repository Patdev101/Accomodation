@extends('layout')

@section('content')
    <div class="no-print">
        <h2>Room assignment</h2>
        <p class="subtitle">Give this to the guest with the key. The guest may now proceed to the room.</p>
        @include('partials.steps', ['steps' => App\Models\Booking::CHECKIN_STEPS, 'current' => 9])
    </div>

    <div class="slip">
        <div class="slip-head">
            <img src="/img/logo-small.png" alt="Mindoro Marine Manufacturing Corporation logo" width="48" height="48">
            <div>
                <b>Mindoro Marine Manufacturing Corporation</b>
                Guest Accommodation – Room / Barracks Assignment
            </div>
        </div>
        <div class="slip-body">
            <div class="slip-room">
                Assigned to
                <b>{{ $booking->room->room_no }}</b>
                {{ $booking->room->location->name }}
            </div>

            <dl class="details">
                <div><dt>Guest</dt><dd>{{ $booking->guest_name }}</dd></div>
                <div><dt>Type</dt><dd>{{ $booking->guest_type }}</dd></div>
                <div><dt>Company</dt><dd>{{ $booking->company ?: '—' }}</dd></div>
                <div><dt>No. of guests</dt><dd>{{ $booking->no_of_guests }}</dd></div>
                <div><dt>Check-in</dt><dd>{{ $booking->check_in->format('M d, Y') }} {{ $booking->timeText('check_in_time') }}</dd></div>
                <div><dt>Expected check-out</dt><dd>{{ $booking->check_out->format('M d, Y') }} {{ $booking->timeText('check_out_time') }}</dd></div>
                <div><dt>Capacity</dt><dd>Good for {{ $booking->room->capacity }} guests</dd></div>
                <div><dt>Rate</dt><dd>{{ $booking->billingRateLabel() }}: ₱{{ number_format((float) $booking->billing_rate, 2) }}</dd></div>
                <div><dt>ID surrendered</dt><dd>{{ $booking->id_type ?: '—' }}</dd></div>
            </dl>

            <div class="slip-inclusions">
                <h3>Room inclusions</h3>
                @if ($booking->room->inclusionsList())
                    <ul>
                        @foreach ($booking->room->inclusionsList() as $inclusion)
                            <li>{{ $inclusion }}</li>
                        @endforeach
                    </ul>
                @else
                    <p>No inclusions listed.</p>
                @endif
            </div>

            <br>
            @include('partials.guests')

            <p class="note" style="margin:16px 0 0">
                Your ID will be returned at check-out. Please report to reception before leaving the facility.
            </p>
        </div>
    </div>

    <div class="actions no-print" style="justify-content:center; margin-top:16px">
        <button type="button" class="primary" onclick="window.print()">Print slip</button>
        <a class="btn" href="/checkin">Check in another guest</a>
        <a class="btn" href="/dashboard">Back to dashboard</a>
    </div>
@endsection
