<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Guest Accommodation – Reception</title>
    <link rel="icon" type="image/png" href="/images/logo.png">
    <link rel="stylesheet" href="/css/style.css?v={{ filemtime(public_path('css/style.css')) }}">
</head>
<body>
    <aside>
        <div class="brand">
            <div class="brand-row">
                <img class="logo" src="/images/logo.png" alt="">
                @include('partials.company', ['dark' => true])
            </div>
            <span>Guest Accommodation</span>
        </div>

        <nav>
            {{-- online reservations waiting for an ID check; both reception and the admin can review them --}}
            @php($pendingApprovals = \App\Models\Booking::where('status', 'Pending')->count())
            @if (auth()->user()->role == 'reception')
            <a href="/dashboard" class="{{ request()->is('dashboard') ? 'active' : '' }}">Dashboard</a>

            <div class="group">Front desk</div>
            <a href="/checkin" class="{{ request()->is('checkin*') ? 'active' : '' }}">Check-in</a>
            <a href="/checkout" class="{{ request()->is('checkout*') ? 'active' : '' }}">Check-out</a>
            <a href="/bookings" class="{{ request()->is('bookings*') ? 'active' : '' }}">Reservations</a>
            <a href="/approvals" class="{{ request()->is('approvals') ? 'active' : '' }}">Approvals @if ($pendingApprovals)<span class="nav-count">{{ $pendingApprovals }}</span>@endif</a>
            <a href="/calendar" class="{{ request()->is('calendar') ? 'active' : '' }}">Calendar</a>

            <div class="group">Records</div>
            <a href="/reports" class="{{ request()->is('reports') ? 'active' : '' }}">Guest Log</a>

            @else
                <a href="/admin" class="{{ request()->is('admin') ? 'active' : '' }}">Admin Dashboard</a>
                <a href="/approvals" class="{{ request()->is('approvals') ? 'active' : '' }}">Approvals @if ($pendingApprovals)<span class="nav-count">{{ $pendingApprovals }}</span>@endif</a>

                <div class="group">Setup</div>
                <a href="/admin/locations" class="{{ request()->is('admin/locations*') ? 'active' : '' }}">Locations</a>
                <a href="/rooms" class="{{ request()->is('rooms*') ? 'active' : '' }}">Rooms</a>
                <a href="/admin/users" class="{{ request()->is('admin/users*') ? 'active' : '' }}">Accounts</a>

                <div class="group">Public site</div>
                <a href="/admin/contact" class="{{ request()->is('admin/contact') ? 'active' : '' }}">Banner and contact</a>
                @php($newMessages = \App\Models\Message::whereNull('read_at')->count())
                <a href="/admin/messages" class="{{ request()->is('admin/messages') ? 'active' : '' }}">Guest messages @if ($newMessages)<span class="nav-count">{{ $newMessages }}</span>@endif</a>
            @endif
        </nav>

        <div class="user">
            {{ auth()->user()->name }}
            <small>{{ auth()->user()->role == 'admin' ? 'Admin' : 'Reception' }} · {{ auth()->user()->email }}</small>
            <form method="POST" action="/logout">
                @csrf
                <button type="submit" class="small">Logout</button>
            </form>
        </div>
    </aside>

    <main>
        @if (session('success'))
            <div class="alert success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert error">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert error">
                <b>Please fix the following:</b><br>
                @foreach ($errors->all() as $e)
                    • {{ $e }}<br>
                @endforeach
            </div>
        @endif

        @yield('content')
    </main>

    {{-- "Are you sure?" pop-up. A form with data-confirm="question" shows it before it is sent.
         {field} in the question is replaced with what is typed in that field of the form.
         data-confirm-ok="text" changes the text of the confirm button (default: the text of the clicked button). --}}
    <div class="modal" id="confirmModal">
        <div class="modal-box confirm-box" role="alertdialog" aria-modal="true" aria-labelledby="confirmTitle" aria-describedby="confirmMessage">
            <h3 id="confirmTitle">Please confirm</h3>
            <p id="confirmMessage"></p>
            <div class="actions">
                <button type="button" class="primary" id="confirmOk">Confirm</button>
                <button type="button" id="confirmCancel">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('confirmModal');
            const okButton = document.getElementById('confirmOk');
            let waitingForm = null;
            let cameFrom = null;

            function closeConfirm() {
                modal.classList.remove('show');
                waitingForm = null;

                if (cameFrom) {
                    cameFrom.focus();
                }
            }

            document.addEventListener('submit', function (event) {
                const form = event.target;

                if (! form.dataset.confirm) {
                    return;
                }

                event.preventDefault();

                // amounts typed in the form are shown as 1,234.50
                document.getElementById('confirmMessage').textContent = form.dataset.confirm.replace(/\{(\w+)\}/g, function (whole, name) {
                    const field = form.elements[name];

                    if (! field) {
                        return whole;
                    }

                    return field.type === 'number'
                        ? Number(field.value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                        : field.value;
                });

                // the confirm button looks and reads like the button that was clicked
                const submitButton = event.submitter || form.querySelector('[type="submit"]');

                okButton.textContent = form.dataset.confirmOk || (submitButton ? submitButton.textContent.trim() : 'Confirm');
                okButton.className = ['danger', 'success-btn'].find(function (style) {
                    return submitButton && submitButton.classList.contains(style);
                }) || 'primary';

                waitingForm = form;
                cameFrom = submitButton;
                modal.classList.add('show');
                okButton.focus();
            });

            okButton.addEventListener('click', function () {
                if (waitingForm) {
                    okButton.disabled = true;
                    waitingForm.submit();
                }
            });

            document.getElementById('confirmCancel').addEventListener('click', closeConfirm);

            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeConfirm();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && modal.classList.contains('show')) {
                    closeConfirm();
                }
            });
        })();
    </script>
</body>
</html>
