<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot password - Guest Accommodation System</title>
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

            <h3>Forgot password</h3>
            <p class="login-intro">Enter your email and we will send you a link to set a new password.</p>

            @if (session('success'))
                <div class="alert success">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="error">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <div class="login-field">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="Enter your email"
                        autocomplete="email"
                        required
                        autofocus
                    >
                </div>

                <button type="submit" class="primary login-button">
                    Send link
                </button>
            </form>

            <div class="login-footer">
                <a href="{{ route('login') }}">Back to login</a>
            </div>

        </div>

    </div>

</body>
</html>
