@extends('layout')

@php
    $total = $rooms->count();
    $occupied = $statuses['Occupied'] + $statuses['Check-out'];
    $percent = fn ($part, $whole) => $whole > 0 ? round($part / $whole * 100) : 0;
    $barColor = ['Available' => 'var(--green)', 'Occupied' => 'var(--red)', 'Check-out' => 'var(--orange)', 'Inspection' => 'var(--purple)', 'Cleaning' => 'var(--teal)', 'Maintenance' => '#777'];
@endphp

@section('content')
    <div class="page-head">
        <div>
            <h2>Admin Dashboard</h2>
            <p class="subtitle">{{ date('l, F j, Y') }} · Overview of the accommodation and its setup.</p>
        </div>
        <div class="actions">
            <a class="btn primary" href="/rooms/create">+ Add room</a>
            <a class="btn" href="/admin/locations">+ Add location</a>
            <a class="btn" href="/admin/users">+ Add account</a>
        </div>
    </div>

    <div class="stats">
        <div class="stat"><b>{{ $locations->count() }}</b><span>Locations</span></div>
        <div class="stat"><b>{{ $total }}</b><span>Rooms (good for {{ $capacity }} guests)</span></div>
        <div class="stat"><b>{{ $percent($occupied, $total) }}%</b><span>Occupancy ({{ $occupied }} of {{ $total }} rooms)</span></div>
        <div class="stat"><b>{{ $guestsInHouse }}</b><span>Guests staying now</span></div>
        <div class="stat"><b>{{ $users->where('role', 'reception')->where('active', true)->count() }}</b><span>Reception accounts</span></div>
    </div>

    <div class="two-col">
        <div class="box">
            <h3>Rooms by status</h3>
            @if ($total == 0)
                <div class="empty">No rooms yet.</div>
            @else
                @foreach ($statuses as $status => $count)
                    <div class="bar-row">
                        <span class="bar-label">{{ $status }}</span>
                        <span class="bar"><span style="width: {{ $percent($count, $total) }}%; background: {{ $barColor[$status] }}"></span></span>
                        <span class="bar-value">{{ $count }}</span>
                    </div>
                @endforeach
            @endif
        </div>

        <div class="box">
            <h3>Today at the front desk</h3>
            <div class="table-wrap">
                <table>
                    <tr><td>Guests checked in today</td><td><b>{{ $checkedInToday }}</b></td></tr>
                    <tr><td>Guests checked out today</td><td><b>{{ $checkedOutToday }}</b></td></tr>
                    <tr><td>Reservations waiting to arrive</td><td><b>{{ $reserved }}</b></td></tr>
                    <tr><td>IDs held at reception</td><td><b>{{ $idsHeld }}</b></td></tr>
                    <tr><td>Charges collected this month</td><td><b>₱{{ number_format($chargesThisMonth, 2) }}</b></td></tr>
                </table>
            </div>
        </div>
    </div>

    <div class="box">
        <div class="page-head">
            <h3>Locations and their rooms</h3>
            <a class="btn small" href="/admin/locations">Manage locations</a>
        </div>
        @if ($locations->isEmpty())
            <div class="empty">No locations yet. <a href="/admin/locations">Add a location first</a>, then add its rooms.</div>
        @else
            <div class="table-wrap">
                <table>
                    <tr><th>Location</th><th>Rooms</th><th>Capacity</th><th>Available</th><th>Occupied</th><th>Occupancy</th><th></th></tr>
                    @foreach ($locations as $location)
                        @php
                            $count = $location->rooms->count();
                            $used = $location->rooms->whereIn('status', ['Occupied', 'Check-out'])->count();
                        @endphp
                        <tr>
                            <td><b>{{ $location->name }}</b><br><small class="muted">{{ $location->description ?: $location->rooms->pluck('room_no')->sort(SORT_NATURAL)->implode(', ') }}</small></td>
                            <td>{{ $count }}</td>
                            <td>{{ $location->rooms->sum('capacity') }} guests</td>
                            <td>{{ $location->rooms->where('status', 'Available')->count() }}</td>
                            <td>{{ $used }}</td>
                            <td style="min-width:140px">
                                <span class="bar"><span style="width: {{ $percent($used, $count) }}%; background: var(--red)"></span></span>
                                <small class="muted">{{ $percent($used, $count) }}%</small>
                            </td>
                            <td>
                                <div class="actions">
                                    <a class="btn small" href="/rooms?location={{ $location->id }}">View rooms</a>
                                    <a class="btn small" href="/rooms/create?location={{ $location->id }}">+ Add room</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif
    </div>

    <div class="two-col">
        <div class="box">
            <h3>Recent guest activity</h3>
            @if ($recent->isEmpty())
                <div class="empty">No guest records yet.</div>
            @else
                <div class="table-wrap">
                    <table>
                        <tr><th>Guest</th><th>Room</th><th>Status</th><th>Updated</th></tr>
                        @foreach ($recent as $b)
                            <tr>
                                <td>{{ $b->guest_name }}<br><small class="muted">{{ $b->company ?: '—' }}</small></td>
                                <td>{{ $b->room->room_no }}</td>
                                <td><span class="badge {{ $b->badge() }}">{{ $b->status }}</span></td>
                                <td><small class="muted">{{ $b->updated_at->format('M d, h:i A') }}</small></td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif
        </div>

        <div class="box">
            <div class="page-head">
                <h3>Accounts</h3>
                <a class="btn small" href="/admin/users">Manage accounts</a>
            </div>
            <div class="table-wrap">
                <table>
                    <tr><th>Name</th><th>Role</th><th>Status</th></tr>
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->name }}<br><small class="muted">{{ $user->email }}</small></td>
                            <td>{{ $user->roleLabel() }}</td>
                            <td><span class="badge {{ $user->active ? 'free' : 'used' }}">{{ $user->active ? 'Active' : 'Deactivated' }}</span></td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
@endsection
