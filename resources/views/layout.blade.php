<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Guest Accommodation – Reception</title>
    {{-- the company logo in the browser tab, bookmarks and phone home screen --}}
    <link rel="icon" href="/favicon.ico?v={{ filemtime(public_path('favicon.ico')) }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/img/logo-32.png?v={{ filemtime(public_path('img/logo-32.png')) }}">
    <link rel="icon" type="image/png" sizes="96x96" href="/img/logo-small.png?v={{ filemtime(public_path('img/logo-small.png')) }}">
    <link rel="apple-touch-icon" href="/img/logo-180.png?v={{ filemtime(public_path('img/logo-180.png')) }}">
    <link rel="stylesheet" href="/css/style.css?v={{ filemtime(public_path('css/style.css')) }}">
</head>
<body>
    <aside>
        <div class="brand">
            <img src="/img/logo-small.png" alt="Mindoro Marine Manufacturing Corporation logo" width="44" height="44">
            <div>
                <b>Guest Accommodation</b>
                <span>Mindoro Marine Mfg. Corp.</span>
            </div>
        </div>

        <nav>
            {{-- reception sees the front desk, the admin sees the setup, the super admin sees both --}}
            @if (auth()->user()->canManageSetup())
                <a href="/admin" class="{{ request()->is('admin') ? 'active' : '' }}">Admin Dashboard</a>
            @endif

            @if (auth()->user()->canUseFrontDesk())
                <a href="/dashboard" class="{{ request()->is('dashboard') ? 'active' : '' }}">{{ auth()->user()->isSuperAdmin() ? 'Front desk dashboard' : 'Dashboard' }}</a>

                <div class="group">Front desk</div>
                <a href="/checkin" class="{{ request()->is('checkin*') ? 'active' : '' }}">Check-in</a>
                <a href="/checkout" class="{{ request()->is('checkout*') ? 'active' : '' }}">Check-out</a>
                <a href="/bookings" class="{{ request()->is('bookings*') ? 'active' : '' }}">Reservations</a>
                <a href="/calendar" class="{{ request()->is('calendar') ? 'active' : '' }}">Calendar</a>

                <div class="group">Records</div>
                <a href="/reports" class="{{ request()->is('reports') ? 'active' : '' }}">Guest Log</a>
            @endif

            @if (auth()->user()->canManageSetup())
                <div class="group">Setup</div>
                <a href="/admin/locations" class="{{ request()->is('admin/locations*') ? 'active' : '' }}">Locations</a>
                <a href="/rooms" class="{{ request()->is('rooms*') ? 'active' : '' }}">Rooms</a>
                <a href="/admin/users" class="{{ request()->is('admin/users*') ? 'active' : '' }}">Accounts</a>
            @endif

            {{-- every role: your own details and password --}}
            <div class="group">Account</div>
            <a href="/account" class="{{ request()->is('account*') ? 'active' : '' }}">My account</a>
        </nav>

        <div class="user">
            {{ auth()->user()->name }}
            <small>{{ auth()->user()->roleLabel() }} · {{ auth()->user()->email }}</small>
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
    {{-- AUTO REFRESH: these pages reload by themselves when another user changes a booking or a room.
         Pages where someone types a long form (check-in, room form, accounts, locations) are left out. --}}
    @if (request()->is('dashboard', 'checkout', 'checkout/*', 'bookings', 'calendar', 'reports', 'admin', 'rooms'))
        <div class="live-notice" id="liveNotice" hidden>
            Another user made changes.
            <button type="button" class="small primary" onclick="location.reload()">Refresh now</button>
        </div>

        <script>
            (function () {
                const loadedVersion = '{{ App\Support\LiveVersion::current() }}';
                const notice = document.getElementById('liveNotice');
                let typing = false;
                let changed = false;

                // come back to the same place on the page after a reload
                try {
                    const y = sessionStorage.getItem('liveScroll:' + location.pathname);

                    sessionStorage.removeItem('liveScroll:' + location.pathname);

                    if (y !== null) {
                        window.addEventListener('load', function () {
                            window.scrollTo(0, Number(y));
                        });
                    }
                } catch (error) {}

                // once someone starts typing in a form (not a filter above a table), the page is never reloaded under them
                document.addEventListener('input', function (event) {
                    if (! event.target.closest('.search-row')) {
                        typing = true;
                    }
                });

                function busy() {
                    const active = document.activeElement;

                    return typing
                        || document.hidden
                        || document.querySelector('.modal.show') !== null
                        || (active && ['INPUT', 'SELECT', 'TEXTAREA'].includes(active.tagName));
                }

                function refreshIfChanged() {
                    if (! changed) {
                        return;
                    }

                    if (busy()) {
                        // do not interrupt: offer a button instead
                        notice.hidden = document.hidden;
                        return;
                    }

                    try {
                        sessionStorage.setItem('liveScroll:' + location.pathname, window.scrollY);
                    } catch (error) {}

                    location.reload();
                }

                setInterval(function () {
                    if (document.hidden || changed) {
                        refreshIfChanged();
                        return;
                    }

                    fetch('/live-version', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                        .then(function (response) {
                            return response.ok ? response.json() : null;
                        })
                        .then(function (data) {
                            if (data && data.version !== loadedVersion) {
                                changed = true;
                                refreshIfChanged();
                            }
                        })
                        .catch(function () {});
                }, 5000);

                // a tab that was in the background catches up when it is opened again
                document.addEventListener('visibilitychange', refreshIfChanged);
            })();
        </script>
    @endif

</body>
</html>
