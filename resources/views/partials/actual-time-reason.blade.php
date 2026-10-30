{{-- Shown only when the actual time (partials.actual-time) is set earlier than now.
     Needs: $field (same as the time partial), $reasonField, $label. --}}
<div class="time-reason" data-reason-for="{{ $field }}" hidden>
    <div class="time-reason-text">
        <b>The {{ $label }} time is earlier than now.</b>
        The bill will count from <b data-shown-time></b>. Write why, so the record is clear.
    </div>
    <div>
        <label for="{{ $reasonField }}">Reason <span class="req">*</span></label>
        <input id="{{ $reasonField }}" name="{{ $reasonField }}" maxlength="255" value="{{ old($reasonField) }}"
               placeholder="{{ $label === 'arrival' ? 'e.g. Arrived at night, encoded in the morning. Form no. 123' : 'e.g. Left early morning, encoded later' }}">
    </div>
</div>
