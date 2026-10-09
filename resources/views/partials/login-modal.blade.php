{{-- Log in pop-up on the public site. Opened by any link with data-open="loginModal". --}}
<div class="modal {{ $open == 'login' ? 'show' : '' }}" id="loginModal">
    <div class="login" role="dialog" aria-modal="true" aria-labelledby="loginTitle">

        <div class="login-header">
            <button type="button" class="close" data-close aria-label="Close">×</button>
            <img class="logo" src="/images/logo.png" alt="">
            @include('partials.company', ['dark' => true])
            <h2 id="loginTitle">Guest Accommodation</h2>
            <p>Log in to your account</p>
        </div>

        <div class="login-body">

            <h3>Welcome Back</h3>
            <p class="login-intro">Please login to continue.</p>

            @if ($open == 'login' && session('success'))
                <div class="alert success">{{ session('success') }}</div>
            @endif

            @if ($open == 'login' && (session('error') || $errors->any()))
                <div class="error">
                    {{ session('error') }}
                    @foreach ($errors->all() as $e)
                        {{ $e }}<br>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="/login">
                @csrf
                <input type="hidden" name="form" value="login">

                <div class="login-field">
                    <label for="login_email">Email</label>
                    <input type="email" id="login_email" name="email" value="{{ $open == 'login' ? old('email') : '' }}" placeholder="Enter your email" autocomplete="email" required>
                </div>

                <div class="login-field">
                    <label for="login_password">Password</label>
                    <div class="password-field">
                        <input type="password" id="login_password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
                        <button type="button" class="password-toggle" aria-label="Show password" onclick="togglePassword(this)">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/>
                                <circle cx="12" cy="12" r="3"/>
                                <line class="slash" x1="3" y1="3" x2="21" y2="21"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="primary login-button">Login</button>
            </form>

            <div class="login-footer">
                <a href="{{ route('password.request') }}">Forgot password?</a>
            </div>

            <div class="login-footer">
                New guest? <a href="/signup" data-open="signupModal">Create an account</a>
            </div>

        </div>

    </div>
</div>
