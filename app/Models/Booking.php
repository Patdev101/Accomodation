<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Booking extends Model
{
    // Check-in process (from the company flowchart)
    const CHECKIN_STEPS = [
        1 => 'Guest Arrives',
        2 => 'Reception',
        3 => 'Check-In Form',
        4 => 'Verification',
        5 => 'Surrender Valid ID',
        6 => 'Assign Accommodation',
        7 => 'Room Assignment',
        8 => 'Proceed to Room',
    ];

    // Check-out process (from the company flowchart)
    const CHECKOUT_STEPS = [
        1 => 'Reports to Reception',
        2 => 'Check-Out Verification',
        3 => 'Room Inspection',
        4 => 'Damages / Additional Charges',
        5 => 'Billing / Payment',
        6 => 'Return Surrendered ID',
        7 => 'Record in Guest Log',
        8 => 'Guest Leaves',
    ];

    // valid IDs accepted at check-in and for online reservations
    const ID_TYPES = ['Company ID', "Driver's License", 'National ID', 'Passport', 'UMID', 'PWD ID', 'Other Government ID'];

    // rows shown per page in the long tables (Previous / Next buttons go to the rest)
    const PER_PAGE = 5;

    protected $fillable = [
        'guest_name', 'guest_type', 'company', 'contact_no', 'email', 'room_id', 'no_of_guests',
        'check_in', 'check_in_time', 'check_out', 'check_out_time', 'status', 'remarks',
        'verified', 'id_type', 'id_number', 'id_surrendered',
        'checkout_step', 'inspection_notes', 'damage_notes', 'charges', 'charges_paid', 'id_returned', 'room_after',
        'actual_check_out', 'checkout_notes', 'guest_list', 'address',
        'billing_rate_type', 'billing_rate', 'checked_in_at', 'checkout_verified_at',
        'additional_charges', 'amount_paid', 'user_id',
        'id_photo', 'reviewed_by', 'reviewed_at', 'decline_reason', 'guest_seen_at',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'actual_check_out' => 'date',
        'verified' => 'boolean',
        'id_surrendered' => 'boolean',
        'charges_paid' => 'boolean',
        'id_returned' => 'boolean',
        'checkout_step' => 'integer',
        'guest_list' => 'array',
        'billing_rate' => 'decimal:2',
        'checked_in_at' => 'datetime',
        'checkout_verified_at' => 'datetime',
        'additional_charges' => 'array',
        'amount_paid' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'guest_seen_at' => 'datetime',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    // the guest account that made the reservation online (empty when reception made it)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // true when the room already has a reservation or a guest between the two dates
    public static function roomTaken($roomId, string $checkIn, string $checkOut): bool
    {
        return static::where('room_id', $roomId)
            // a reservation still waiting for approval holds the room too
            ->whereIn('status', ['Pending', 'Reserved', 'Checked In', 'Checking Out'])
            ->where('check_in', '<', $checkOut)
            ->where('check_out', '>', $checkIn)
            ->exists();
    }

    // css class for the status badge
    public function badge()
    {
        return [
            'Pending' => 'checking',
            'Declined' => 'cancelled',
            'Reserved' => 'reserved',
            'Checked In' => 'checked',
            'Checking Out' => 'checking',
            'Checked Out' => 'out',
            'Cancelled' => 'cancelled',
        ][$this->status] ?? 'out';
    }

    // the status in the guest's own words, shown on the public site
    public function guestStatus(): string
    {
        return [
            'Pending' => 'Waiting for approval',
            'Reserved' => 'Confirmed',
        ][$this->status] ?? $this->status;
    }

    // everyone in the group, the main guest first; each row has name, address and contact_no
    public function guests(): array
    {
        $guests = [['name' => $this->guest_name, 'address' => $this->address, 'contact_no' => $this->contact_no]];

        foreach ($this->guest_list ?? [] as $guest) {
            $guests[] = [
                'name' => $guest['name'] ?? $guest,
                'address' => $guest['address'] ?? '',
                'contact_no' => $guest['contact_no'] ?? '',
            ];
        }

        return $guests;
    }

    // a saved time shown the same way on every page, e.g. timeText('check_out_time') = "12:00 PM"
    public function timeText(string $field): string
    {
        return $this->$field ? Carbon::parse($this->$field)->format('h:i A') : '';
    }

    public function billingRateType(): string
    {
        return $this->billing_rate_type ?: 'nightly';
    }

    public function billingRateLabel(): string
    {
        return [
            'nightly' => 'Per night',
            'hourly' => 'Per hour',
            'daytour' => 'Daily',
        ][$this->billingRateType()] ?? 'Per night';
    }

    public function billingStart(): Carbon
    {
        return $this->checked_in_at
            ? Carbon::parse($this->checked_in_at)
            : Carbon::parse($this->check_in->format('Y-m-d').' '.($this->check_in_time ?: '14:00'));
    }

    public function billingEnd(): Carbon
    {
        return $this->checkout_verified_at
            ? Carbon::parse($this->checkout_verified_at)
            : Carbon::parse($this->check_out->format('Y-m-d').' '.($this->check_out_time ?: '12:00'));
    }

    public function billingUnits(): int
    {
        $start = $this->billingStart();
        $end = $this->billingEnd();

        if ($this->billingRateType() === 'hourly') {
            $seconds = max(0, $end->getTimestamp() - $start->getTimestamp());

            return max(1, (int) ceil($seconds / 3600));
        }

        $days = (int) $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay());

        return $this->billingRateType() === 'daytour'
            ? max(1, $days + 1)
            : max(1, $days);
    }

    public function accommodationCharge(): float
    {
        $rate = $this->billing_rate ?? $this->room->rate ?? 0;

        return round((float) $rate * $this->billingUnits(), 2);
    }

    public function additionalChargeTotal(): float
    {
        return round((float) ($this->charges ?? 0), 2);
    }

    public function totalAmount(): float
    {
        return round($this->accommodationCharge() + $this->additionalChargeTotal(), 2);
    }

    public function balance(): float
    {
        return round(max(0, $this->totalAmount() - (float) ($this->amount_paid ?? 0)), 2);
    }

    public function paymentStatus(): string
    {
        if ($this->totalAmount() <= 0 || (float) ($this->amount_paid ?? 0) >= $this->totalAmount()) {
            return 'Paid';
        }

        return (float) ($this->amount_paid ?? 0) > 0 ? 'Partially Paid' : 'Unpaid';
    }
}
