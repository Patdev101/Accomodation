{{-- Pop-ups opened from the menu under the guest's name: Send feedback, Ask a question and FAQ. --}}
@foreach (['feedback' => ['Send feedback', 'Tell us what went well or what we can improve', 'Your feedback', 'e.g. The booking form was easy to use, but...', 'Send feedback'],
           'question' => ['Ask a question', 'About the website or your reservation', 'Your question', 'e.g. Can I change the dates of my reservation?', 'Send question']] as $type => [$title, $intro, $label, $placeholder, $button])
    <div class="modal {{ $open == $type ? 'show' : '' }}" id="{{ $type }}Modal">
        <div class="login signup" role="dialog" aria-modal="true" aria-labelledby="{{ $type }}Title">

            <div class="login-header">
                <button type="button" class="close" data-close aria-label="Close">×</button>
                <h2 id="{{ $type }}Title">{{ $title }}</h2>
                <p>{{ $intro }}</p>
            </div>

            <div class="login-body">
                @if ($open == $type && $errors->any())
                    <div class="error">
                        @foreach ($errors->all() as $e)
                            {{ $e }}<br>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="/messages">
                    @csrf
                    <input type="hidden" name="form" value="{{ $type }}">
                    <input type="hidden" name="type" value="{{ $type }}">

                    <div class="login-field">
                        <label for="{{ $type }}_body">{{ $label }}</label>
                        <textarea id="{{ $type }}_body" name="body" placeholder="{{ $placeholder }}" minlength="5" maxlength="1000" rows="5" required>{{ $open == $type ? old('body') : '' }}</textarea>
                        @if ($type == 'question')
                            <small class="hint">We answer by email at {{ auth()->user()->email }}. The FAQ may already have your answer.</small>
                        @endif
                    </div>

                    <div class="actions how-actions">
                        <button type="submit" class="primary">{{ $button }}</button>
                        <button type="button" data-close>Cancel</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
@endforeach
