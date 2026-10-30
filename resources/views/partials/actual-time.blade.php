{{-- The real arrival / leaving time. It starts at "now"; reception changes it when the guest
     came or left earlier than it is encoded (up to 24 hours back).
     Needs: $field, $label (e.g. "arrival"), optional $min (earliest allowed).
     The reason box is a separate partial (partials.actual-time-reason) so it can sit anywhere. --}}
@php
    $now = now();
    $earliest = $now->copy()->subDay();
    if (! empty($min) && $min->gt($earliest)) {
        $earliest = $min;
    }
    $value = old($field) ? \Carbon\Carbon::parse(old($field)) : $now;
@endphp

<div class="actual-time" data-actual-time="{{ $field }}">
    <label for="{{ $field }}_date">Actual {{ $label }} <span class="req">*</span></label>
    <div class="input-row">
        <input id="{{ $field }}_date" type="date" value="{{ $value->format('Y-m-d') }}"
               min="{{ $earliest->format('Y-m-d') }}" max="{{ $now->format('Y-m-d') }}" required>
        <input id="{{ $field }}_time" type="time" value="{{ $value->format('H:i') }}" required>
    </div>
    <input type="hidden" name="{{ $field }}" value="{{ $value->format('Y-m-d\TH:i') }}">
    <p class="hint">Already set to now. {{ $label === 'arrival' ? 'Arrived' : 'Left' }} earlier? Use the time on the paper form (max 24 hrs back).</p>
</div>

@once
    <script>
        // joins the date and time into the hidden field; the reason box appears
        // once the time is more than 30 minutes before now
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-actual-time]').forEach(function (box) {
                const date = box.querySelector('input[type="date"]');
                const time = box.querySelector('input[type="time"]');
                const joined = box.querySelector('input[type="hidden"]');
                const reasonBox = document.querySelector('[data-reason-for="' + box.dataset.actualTime + '"]');
                const reason = reasonBox.querySelector('input');
                const shown = reasonBox.querySelector('[data-shown-time]');

                function check() {
                    joined.value = date.value + 'T' + time.value;

                    const picked = new Date(joined.value);
                    const earlier = date.value && time.value && (Date.now() - picked.getTime()) > 30 * 60 * 1000;

                    reasonBox.hidden = ! earlier;
                    reason.required = !! earlier;

                    if (earlier) {
                        shown.textContent = picked.toLocaleString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
                    }
                }

                date.addEventListener('input', check);
                time.addEventListener('input', check);
                check();
            });
        });
    </script>
@endonce
