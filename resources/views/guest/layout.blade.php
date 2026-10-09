<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Guest Accommodation') – Mindoro Marine Manufacturing Corporation</title>
    <link rel="icon" type="image/png" href="/images/logo.png">
    {{-- dark mode is remembered in this browser; set before the page is drawn so it does not flash white --}}
    <script>
        try {
            if (localStorage.getItem('theme') === 'dark') {
                document.documentElement.dataset.theme = 'dark';
            }
        } catch (error) {}
    </script>
    <link rel="stylesheet" href="/css/style.css?v={{ filemtime(public_path('css/style.css')) }}">
</head>
<body class="site">
    {{-- which pop-up is open when the page loads: "login", "signup" or none.
         /login and /signup open theirs; after a refused form (also "feedback" or "question")
         the same pop-up opens again. --}}
    @php($open = auth()->check() ? old('form') : ($open ?? old('form')))

    <header class="site-nav">
        <div class="site-wrap">
            <a class="site-brand" href="/">
                <img class="logo" src="/images/logo.png" alt="">
                @include('partials.company')
            </a>

            <nav>
                <a href="/#rooms">Rooms</a>
                <a href="#howModal" data-open="howModal">How to book</a>

                @auth
                    <a href="/my-reservations" class="{{ request()->is('my-reservations') ? 'active' : '' }}">My reservations</a>
                    <a class="btn primary" href="/book">Book a room</a>

                    {{-- the menu under the guest's name --}}
                    <details class="user-menu">
                        <summary>{{ Str::before(trim(auth()->user()->name), ' ') }}</summary>
                        <div class="user-menu-list">
                            <div class="user-menu-who">
                                <b>{{ auth()->user()->name }}</b>
                                <small>{{ auth()->user()->email }}</small>
                            </div>
                            <a href="#feedbackModal" data-open="feedbackModal">Send feedback</a>
                            <a href="#questionModal" data-open="questionModal">Ask a question</a>
                            <a href="#faqModal" data-open="faqModal">FAQ</a>
                            <button type="button" class="theme-toggle" role="switch" aria-checked="false">Dark mode <span>Off</span></button>
                            <form method="POST" action="/logout">
                                @csrf
                                <button type="submit">Logout</button>
                            </form>
                        </div>
                    </details>
                @else
                    <a href="#faqModal" data-open="faqModal">FAQ</a>
                    <button type="button" class="theme-toggle icon" role="switch" aria-checked="false" aria-label="Dark mode" title="Dark mode">◐</button>
                    <a href="/login" data-open="loginModal">Log in</a>
                    <a class="btn primary" href="/signup" data-open="signupModal">Sign up</a>
                @endauth
            </nav>
        </div>
    </header>

    @yield('hero')

    <main class="site-wrap site-main">
        {{-- while a pop-up is open, its messages are shown inside it instead --}}
        @if (! $open)
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
        @endif

        @yield('content')
    </main>

    {{-- the contact details on the right are filled in by the admin (Admin > Contact details) --}}
    @php($contact = \App\Models\Setting::contact())
    <footer class="site-footer">
        <div class="site-wrap">
            <div>
                @include('partials.company')
                <small>&copy; {{ date('Y') }} Mindoro Marine Manufacturing Corporation. All rights reserved.</small>
            </div>

            @if ($contact)
                <div class="site-contact">
                    <b>Contact us</b>
                    <ul>
                        @isset($contact['facebook'])
                            <li>
                                <a href="{{ $contact['facebook'] }}" target="_blank" rel="noopener noreferrer">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M13.5 22v-8h2.7l.4-3.2h-3.1V8.7c0-.9.3-1.6 1.6-1.6h1.7V4.2c-.3 0-1.3-.1-2.4-.1-2.4 0-4.1 1.5-4.1 4.2v2.5H7.5V14h2.8v8h3.2z"/></svg>
                                    Facebook
                                </a>
                            </li>
                        @endisset
                        @isset($contact['messenger'])
                            <li>
                                <a href="{{ $contact['messenger'] }}" target="_blank" rel="noopener noreferrer">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M12 2C6.4 2 2 6.1 2 11.4c0 2.9 1.3 5.4 3.4 7.1V22l3.2-1.8c1.1.3 2.200.5 3.400.5 5.600 0 10-4.100 10-9.300S17.600 2 12 2zm1 12.500-2.500-2.700-5 2.700 5.500-5.800 2.600 2.700 4.900-2.700-5.500 5.800z"/></svg>
                                    Messenger
                                </a>
                            </li>
                        @endisset
                        @isset($contact['contact_no'])
                            <li>
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $contact['contact_no']) }}">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M6.600 10.800c1.400 2.800 3.800 5.100 6.600 6.600l2.200-2.200c.3-.3.700-.4 1-.2 1.100.4 2.300.6 3.600.6.600 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.600 21 3 13.400 3 4c0-.6.400-1 1-1h3.500c.600 0 1 .4 1 1 0 1.300.2 2.500.6 3.600.1.300 0 .7-.2 1l-2.300 2.200z"/></svg>
                                    {{ $contact['contact_no'] }}
                                </a>
                            </li>
                        @endisset
                    </ul>
                </div>
            @endif
        </div>
    </footer>
    @include('partials.how-modal')
    @include('partials.faq-modal')

    @auth
        @include('partials.guest-menu-modals')
    @endauth

    @guest
        @include('partials.login-modal')
        @include('partials.signup-modal')
    @endguest

        <script>
            // Log in, sign up and "How to book" are pop-ups. A link with data-open="loginModal" (or
            // "signupModal", "howModal") opens one; the × button, Escape or a click outside closes it.
            (function () {
                function closeModals() {
                    document.querySelectorAll('.modal.show').forEach(function (modal) {
                        modal.classList.remove('show');
                    });
                }

                function openModal(id) {
                    closeModals();

                    const modal = document.getElementById(id);

                    modal.classList.add('show');
                    (modal.querySelector('input:not([type="hidden"]), textarea') || modal.querySelector('[data-close]')).focus();
                }

                document.addEventListener('click', function (event) {
                    const opener = event.target.closest('[data-open]');

                    // a click anywhere else closes the menu under the guest's name
                    document.querySelectorAll('.user-menu[open]').forEach(function (menu) {
                        if (opener || ! menu.contains(event.target)) {
                            menu.open = false;
                        }
                    });

                    if (opener) {
                        event.preventDefault();

                        // "Book this room" remembers the room, so the guest lands on it after signing up
                        if (opener.dataset.room && document.getElementById('signup_room')) {
                            document.getElementById('signup_room').value = opener.dataset.room;
                        }

                        openModal(opener.dataset.open);
                    } else if (event.target.closest('[data-close]') || event.target.classList.contains('modal')) {
                        closeModals();
                    }
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') {
                        closeModals();
                    }
                });

                const shown = document.querySelector('.modal.show');

                if (shown) {
                    openModal(shown.id);
                }
            })();

            // Dark mode: the switch is in the guest's menu (or the ◐ button for visitors).
            // The choice is remembered in this browser.
            (function () {
                function showTheme() {
                    const dark = document.documentElement.dataset.theme === 'dark';

                    document.querySelectorAll('.theme-toggle').forEach(function (button) {
                        button.setAttribute('aria-checked', dark);

                        if (button.querySelector('span')) {
                            button.querySelector('span').textContent = dark ? 'On' : 'Off';
                        }
                    });
                }

                document.querySelectorAll('.theme-toggle').forEach(function (button) {
                    button.addEventListener('click', function () {
                        const dark = document.documentElement.dataset.theme !== 'dark';

                        if (dark) {
                            document.documentElement.dataset.theme = 'dark';
                        } else {
                            delete document.documentElement.dataset.theme;
                        }

                        try {
                            localStorage.setItem('theme', dark ? 'dark' : 'light');
                        } catch (error) {}

                        showTheme();
                    });
                });

                showTheme();
            })();

            // the eye button: show or hide what is typed in the password box beside it
            function togglePassword(button) {
                const field = button.parentElement.querySelector('input');
                const show = field.type === 'password';

                field.type = show ? 'text' : 'password';
                button.classList.toggle('on', show);
                button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            }
        </script>
</body>
</html>
