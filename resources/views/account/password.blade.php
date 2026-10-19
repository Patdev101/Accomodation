@extends('layout')

@php
    $me = auth()->user();
    $initials = collect(preg_split('/\s+/', trim($me->name)))->filter()->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
    $askWho = $me->isSuperAdmin() ? 'another super admin' : ($me->role === 'admin' ? 'the super admin' : 'an admin');
@endphp

@section('content')
    <div class="account-page">
        <h2>My account</h2>
        <p class="subtitle">Your account details, and where you change your own password.</p>

        {{-- who you are --}}
        <div class="box profile-card">
            <div class="profile-head">
                <span class="profile-avatar" aria-hidden="true">{{ $initials }}</span>
                <div class="profile-main">
                    <b>{{ $me->name }}</b>
                    <span class="badge {{ $me->canManageSetup() ? 'reserved' : 'free' }}">{{ $me->roleLabel() }}</span>
                </div>
            </div>

            <dl class="profile-facts">
                <div><dt>Email (used to log in)</dt><dd>{{ $me->email }}</dd></div>
                <div><dt>Account created</dt><dd>{{ optional($me->created_at)->format('M d, Y') ?? '—' }}</dd></div>
                <div><dt>Status</dt><dd><span class="badge free">Active</span></dd></div>
            </dl>

            <p class="hint profile-note">To change your name or email, ask {{ $askWho }}.</p>
        </div>

        {{-- change your own password --}}
        <form class="box password-box" method="POST" action="/account/password" id="passwordForm">
            @csrf
            @method('PUT')

            <div class="password-form">
                <h3>Change password</h3>

                @foreach ([
                    'current_password' => ['Current password', 'current-password'],
                    'password' => ['New password', 'new-password'],
                    'password_confirmation' => ['Repeat the new password', 'new-password'],
                ] as $field => [$label, $autocomplete])
                    <label for="{{ $field }}">{{ $label }} <span class="req">*</span></label>
                    <div class="password-field">
                        <input id="{{ $field }}" name="{{ $field }}" type="password" autocomplete="{{ $autocomplete }}" {{ $field === 'current_password' ? '' : 'minlength=8' }} required>
                        <button type="button" class="password-toggle" aria-label="Show password" onclick="togglePassword(this)">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/>
                                <circle cx="12" cy="12" r="3"/>
                                <line class="slash" x1="3" y1="3" x2="21" y2="21"/>
                            </svg>
                        </button>
                    </div>
                @endforeach

                <div class="actions account-actions">
                    <button type="submit" class="primary" id="savePassword">Save new password</button>
                    <span class="save-why" id="saveWhy" aria-live="polite"></span>
                </div>
            </div>

            {{-- what the new password needs; each line turns green as it is met --}}
            <div class="password-rules" aria-live="polite">
                <h4>Your new password</h4>
                <ul>
                    <li data-rule="length">Has at least 8 characters</li>
                    <li data-rule="different">Is different from your current password</li>
                    <li data-rule="match">Is typed the same in both boxes</li>
                </ul>
                <p class="hint">Tip: a short sentence or three unrelated words is easy to remember and hard to guess. Do not reuse the password of your email.</p>
            </div>
        </form>
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

        (function () {
            const current = document.getElementById('current_password');
            const fresh = document.getElementById('password');
            const repeat = document.getElementById('password_confirmation');
            const save = document.getElementById('savePassword');
            const why = document.getElementById('saveWhy');

            function check() {
                const rules = {
                    length: fresh.value.length >= 8,
                    different: fresh.value !== '' && fresh.value !== current.value,
                    match: repeat.value !== '' && repeat.value === fresh.value,
                };

                Object.entries(rules).forEach(function ([rule, met]) {
                    const item = document.querySelector('[data-rule="' + rule + '"]');

                    item.classList.toggle('met', met);
                    // once something is typed, a rule that is still not met turns red
                    item.classList.toggle('bad', ! met && fresh.value !== '');
                });

                // the button only works once everything is right; the line beside it says why not
                const ready = current.value !== '' && Object.values(rules).every(Boolean);

                save.disabled = ! ready;
                why.textContent = ready || (fresh.value === '' && repeat.value === '')
                    ? ''
                    : (current.value === '' ? 'Type your current password first.' : 'Fix the red item on the right to save.');
            }

            [current, fresh, repeat].forEach(function (field) {
                field.addEventListener('input', check);
            });

            check();
        })();
    </script>
@endsection
