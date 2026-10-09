@extends('layout')

@section('content')
    <h2>Approvals</h2>
    <p class="subtitle">Reservations made on the public site. Open the uploaded ID, check that it is valid and matches the guest, then approve or decline. The guest is told the result.</p>

    <div class="box">
        @if ($bookings->isEmpty())
            <div class="empty">No reservations are waiting for approval.</div>
        @else
            <div class="table-wrap">
                <table>
                    <tr><th>Guest</th><th>Room</th><th>Stay</th><th>Uploaded ID</th><th>Decision</th></tr>
                    @foreach ($bookings as $b)
                        <tr>
                            <td>
                                <strong>{{ $b->guest_name }}</strong><br>
                                <small class="muted">{{ $b->email }} · {{ $b->contact_no }}</small>
                                @if ($b->company)
                                    <br><small class="muted">{{ $b->company }}</small>
                                @endif
                            </td>
                            <td>
                                <b>{{ $b->room->room_no }}</b><br>
                                <small class="muted">{{ $b->room->location->name }} · {{ $b->no_of_guests }} {{ $b->no_of_guests == 1 ? 'guest' : 'guests' }}</small>
                            </td>
                            <td>
                                {{ $b->check_in->format('M d, Y') }} {{ $b->timeText('check_in_time') }}<br>
                                <small class="muted">to {{ $b->check_out->format('M d, Y') }} {{ $b->timeText('check_out_time') }}</small>
                            </td>
                            <td>
                                {{ $b->id_type }}<br>
                                @if ($b->id_photo)
                                    <a class="btn small" href="/approvals/{{ $b->id }}/id" target="_blank" rel="noopener">View ID</a>
                                @else
                                    <small class="muted">No ID uploaded</small>
                                @endif
                            </td>
                            <td>
                                <form method="POST" action="/approvals/{{ $b->id }}/approve" data-confirm="Approve the reservation of {{ $b->guest_name }} for {{ $b->room->room_no }}? Only approve after checking the ID.">
                                    @csrf
                                    <button type="submit" class="success-btn small">Approve</button>
                                </form>

                                <form class="decline-form" method="POST" action="/approvals/{{ $b->id }}/decline" data-confirm="Decline the reservation of {{ $b->guest_name }}? Reason: {decline_reason}">
                                    @csrf
                                    <input name="decline_reason" placeholder="Reason, e.g. ID photo is blurred" maxlength="255" aria-label="Reason for declining" required>
                                    <button type="submit" class="danger small">Decline</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif
    </div>
@endsection
