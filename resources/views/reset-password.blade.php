<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Set password - Guest Accommodation System</title>
    {{-- the company logo in the browser tab, bookmarks and phone home screen --}}
    <link rel="icon" href="/favicon.ico?v={{ filemtime(public_path('favicon.ico')) }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/img/logo-32.png?v={{ filemtime(public_path('img/logo-32.png')) }}">
    <link rel="icon" type="image/png" sizes="96x96" href="/img/logo-small.png?v={{ filemtime(public_path('img/logo-small.png')) }}">
    <link rel="apple-touch-icon" href="/img/logo-180.png?v={{ filemtime(public_path('img/logo-180.png')) }}">
    <link rel="stylesheet" href="/css/style.css?v={{ filemtime(public_path('css/style.css')) }}">
</head>

<body class="login-page">

    <div class="login">

        <div class="login-header">
            <img src="/img/logo.png" alt="Mindoro Marine Manufacturing Corporation logo" width="84" height="84">
            <h2>Guest Accommodation System</h2>
            <p>Reception System</p>
        </div>

        <div class="login-body">

            <h3>Set your password</h3>
            <p class="login-intro">Choose the password you will use to log in.</p>

            @if (session('error'))
                <div class="error">
                    {{ session('error') }}
                    <a href="{{ route('password.request') }}">Get a new link</a>
                </div>
            @endif

            @if ($errors->any())
                <div class="error">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="login-field">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email', $email) }}"
                        autocomplete="email"
                        required
                        readonly
                    >
                </div>

                <div class="login-field">
                    <label for="password">New password</label>
                    <div class="password-field">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="At least 8 characters"
                            autocomplete="new-password"
                            minlength="8"
                            required
                            autofocus
                        >
                        <button type="button" class="password-toggle" aria-label="Show password" onclick="togglePassword(this)">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/>
                                <circle cx="12" cy="12" r="3"/>
                                <line class="slash" x1="3" y1="3" x2="21" y2="21"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="login-field">
                    <label for="password_confirmation">Repeat the password</label>
                    <div class="password-field">
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            autocomplete="new-password"
                            minlength="8"
                            required
                        >
                        <button type="button" class="password-toggle" aria-label="Show password" onclick="togglePassword(this)">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/>
                                <circle cx="12" cy="12" r="3"/>
                                <line class="slash" x1="3" y1="3" x2="21" y2="21"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="primary login-button">
                    Save password
                </button>
            </form>

            <div class="login-footer">
                <a href="{{ route('login') }}">Back to login</a>
            </div>

        </div>

    </div>

    <script>
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
