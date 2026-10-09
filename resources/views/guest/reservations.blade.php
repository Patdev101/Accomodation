@extends('guest.layout')

@section('title', 'My reservations')

@section('content')
    <div class="page-head">
        <div>
            <h2>My reservations</h2>
            <p class="subtitle">A new reservation waits for approval while we check your ID. Bring the same ID to reception when you check in.</p>
        </div>
        <a class="btn primary" href="/book">+ Book a room</a>
    </div>

    @include('guest.decisions')

    <div class="box">
        @if ($bookings->isEmpty())
            <div class="empty">You have no reservations yet. <a href="/#rooms">See the rooms</a>.</div>
        @else
            <div class="table-wrap">
                <table>
                    <tr><th>Room</th><th>Check-in</th><th>Check-out</th><th>Guests</th><th>Status</th><th></th></tr>
                    @foreach ($bookings as $b)
                        <tr>
                            <td>
                                <b>{{ $b->room->room_no }}</b><br>
                                <small class="muted">{{ $b->room->location->name }}</small>
                            </td>
                            <td>
                                {{ $b->check_in->format('M d, Y') }}<br>
                                <small class="muted">{{ $b->timeText('check_in_time') }}</small>
                            </td>
                            <td>
                                {{ $b->check_out->format('M d, Y') }}<br>
                                <small class="muted">{{ $b->timeText('check_out_time') }}</small>
                            </td>
                            <td>{{ $b->no_of_guests }}</td>
                            <td>
                                <span class="badge {{ $b->badge() }}">{{ $b->guestStatus() }}</span>
                                @if ($b->status == 'Declined' && $b->decline_reason)
                                    <br><small class="muted">{{ $b->decline_reason }}</small>
                                @endif
                            </td>
                            <td>
                                @if (in_array($b->status, ['Pending', 'Reserved']))
                                    <form method="POST" action="/my-reservations/{{ $b->id }}/cancel" onsubmit="return confirm('Cancel your reservation for {{ $b->room->room_no }}?')">
                                        @csrf
                                        <button type="submit" class="danger small">Cancel</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif
    </div>
@endsection
