@extends('layout')

@section('content')
    <h2>Accounts</h2>
    <p class="subtitle">{{ auth()->user()->isSuperAdmin() ? 'Create and manage every account: admins and reception staff.' : 'Create and manage the accounts of the reception staff.' }}</p>

    <form class="box" method="POST" action="{{ $editing ? '/admin/users/'.$editing->id : '/admin/users' }}">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <h3>{{ $editing ? 'Edit account' : 'Add an account' }}</h3>
        <div class="form-grid">
            <div>
                <label for="name">Name <span class="req">*</span></label>
                <input id="name" name="name" value="{{ old('name', optional($editing)->name) }}" required>
            </div>
            <div>
                <label for="email">Email <span class="req">*</span></label>
                <input id="email" name="email" type="email" value="{{ old('email', optional($editing)->email) }}" required>
            </div>
            <div>
                <label for="role">Role <span class="req">*</span></label>
                @php
                    // editing your own account: your role is shown but cannot be changed
                    $roles = $editing && $editing->id == auth()->id()
                        ? [$editing->role => $editing->roleLabel()]
                        : auth()->user()->assignableRoles();
                @endphp
                <select id="role" name="role">
                    @foreach ($roles as $value => $label)
                        <option value="{{ $value }}" {{ old('role', optional($editing)->role ?? 'reception') == $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @if (! auth()->user()->isSuperAdmin())
                    <p class="hint">Only the super admin can create or change admin accounts.</p>
                @endif
            </div>
            <div>
                <label for="password">{{ $editing ? 'New password' : 'Password' }} @if (! $editing)<span class="req">*</span>@endif</label>
                <div class="password-field">
                    <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" {{ $editing ? '' : 'required' }}>
                    <button type="button" class="password-toggle" aria-label="Show password" onclick="togglePassword(this)">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/>
                            <circle cx="12" cy="12" r="3"/>
                            <line class="slash" x1="3" y1="3" x2="21" y2="21"/>
                        </svg>
                    </button>
                </div>
                <p class="hint">{{ $editing ? 'Leave empty to keep the current password. Fill in only to reset a forgotten password.' : 'At least 8 characters. Use the eye to check what you typed.' }}</p>
            </div>
        </div>
        <p class="hint">
            {{ $editing
                ? 'If you set a new password, give it to the person. They can change it themselves on their My account page.'
                : 'Give the email and password to the person. They can change the password themselves on their My account page after logging in.' }}
        </p>
        <br>
        <div class="actions">
            <button type="submit" class="primary">{{ $editing ? 'Save changes' : 'Create account' }}</button>
            @if ($editing)
                <a class="btn" href="/admin/users">Cancel</a>
            @endif
        </div>
    </form>

    <div class="box">
        <h3>All accounts</h3>
        <div class="table-wrap">
            <table>
                <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr>
                @foreach ($users as $user)
                    <tr>
                        <td>{{ $user->name }} @if ($user->id == auth()->id())<small class="muted">(you)</small>@endif</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->roleLabel() }}</td>
                        <td><span class="badge {{ $user->active ? 'free' : 'used' }}">{{ $user->active ? 'Active' : 'Deactivated' }}</span></td>
                        <td>
                            <div class="actions">
                                @if (auth()->user()->canManage($user))
                                    <a class="btn small" href="/admin/users/{{ $user->id }}/edit">Edit</a>
                                @else
                                    <span class="muted">Managed by the super admin</span>
                                @endif
                                @if (auth()->user()->canDeactivate($user))
                                    <form method="POST" action="/admin/users/{{ $user->id }}/toggle" data-confirm="{{ $user->active ? 'Deactivate' : 'Activate' }} {{ $user->name }}?">
                                        @csrf
                                        <button type="submit" class="small {{ $user->active ? 'danger' : 'success-btn' }}">{{ $user->active ? 'Deactivate' : 'Activate' }}</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
        <p class="hint">A deactivated account cannot log in. Its records stay in the guest log. To reset a forgotten password, click Edit and set a new one.</p>
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
@endsection
