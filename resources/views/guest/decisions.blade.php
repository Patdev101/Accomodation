{{-- Message shown once to the guest after reception or the admin has approved or declined a reservation. --}}
@foreach ($decisions as $b)
    @if ($b->status == 'Declined')
        <div class="alert error">
            <b>Your reservation for {{ $b->room->room_no }} ({{ $b->check_in->format('M d') }} to {{ $b->check_out->format('M d, Y') }}) was not approved.</b><br>
            Reason: {{ $b->decline_reason }} <a href="/book?room={{ $b->room_id }}">Book again</a>
        </div>
    @else
        <div class="alert success">
            <b>Your reservation is confirmed.</b><br>
            {{ $b->room->room_no }}, {{ $b->check_in->format('M d, Y') }} {{ $b->timeText('check_in_time') }} to {{ $b->check_out->format('M d, Y') }} {{ $b->timeText('check_out_time') }}. Please bring the same valid ID when you check in.
        </div>
    @endif
@endforeach
