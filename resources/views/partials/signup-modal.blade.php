{{-- Sign up pop-up on the public site. Opened by any link with data-open="signupModal";
     data-room="5" on the link remembers the room the visitor wanted to book. --}}
<div class="modal {{ $open == 'signup' ? 'show' : '' }}" id="signupModal">
    <div class="login signup" role="dialog" aria-modal="true" aria-labelledby="signupTitle">

        <div class="login-header">
            <button type="button" class="close" data-close aria-label="Close">×</button>
            <img class="logo" src="/images/logo.png" alt="">
            @include('partials.company', ['dark' => true])
            <h2 id="signupTitle">Guest Accommodation</h2>
            <p>Create your guest account</p>
        </div>

        <div class="login-body">

            <h3>Sign up to book</h3>
            <p class="login-intro">It takes a minute. You only do this once.</p>

            @if ($open == 'signup' && $errors->any())
                <div class="error">
                    @foreach ($errors->all() as $e)
                        {{ $e }}<br>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="/signup">
                @csrf
                <input type="hidden" name="form" value="signup">
                <input type="hidden" name="room" id="signup_room" value="{{ old('room', request()->query('room')) }}">

                <div class="login-field">
                    <label for="signup_name">Full name</label>
                    <input type="text" id="signup_name" name="name" value="{{ old('name') }}" placeholder="e.g. Juan Dela Cruz" autocomplete="name" maxlength="100" required>
                </div>

                <div class="login-field">
                    <label for="signup_email">Email</label>
                    <input type="email" id="signup_email" name="email" value="{{ $open == 'signup' ? old('email') : '' }}" placeholder="you@example.com" autocomplete="email" maxlength="150" required>
                </div>

                <div class="login-field">
                    <label for="signup_password">Password</label>
                    <input type="password" id="signup_password" name="password" placeholder="At least 8 characters" autocomplete="new-password" minlength="8" required>
                </div>

                <div class="login-field">
                    <label for="signup_password_confirmation">Repeat password</label>
                    <input type="password" id="signup_password_confirmation" name="password_confirmation" placeholder="Type it again" autocomplete="new-password" minlength="8" required>
                </div>

                <div class="login-field">
                    <label>Terms and conditions</label>
                    <div class="terms-box" tabindex="0">
                        @include('guest.terms')
                    </div>
                    <label class="check">
                        <input type="checkbox" name="terms" value="1" {{ old('terms') ? 'checked' : '' }} required>
                        <span>I have read and agree to the terms and conditions.</span>
                    </label>
                </div>

                <button type="submit" class="primary login-button">Create account</button>
            </form>

            <div class="login-footer">
                Already have an account? <a href="/login" data-open="loginModal">Log in</a>
            </div>

        </div>

    </div>
</div>
