<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Room extends Model
{
    // Room status flow: Occupied -> Check-out -> Inspection -> Cleaning / Maintenance -> Available
    const STATUSES = ['Available', 'Occupied', 'Check-out', 'Inspection', 'Cleaning', 'Maintenance'];

    protected $fillable = [
        'room_no',
        'location_id',
        'capacity',
        'rate',
        'rate_hourly',
        'rate_daytour',
        'status',
        'inclusions',
        'photos',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'rate_hourly' => 'decimal:2',
        'rate_daytour' => 'decimal:2',
        'photos' => 'array',
    ];

    // Every room query also loads its location
    protected $with = ['location'];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    // Rooms sorted by location name, then room number
    public static function listed($status = null)
    {
        return static::when($status, fn ($q) => $q->where('status', $status))
            ->get()
            ->sortBy(
                fn ($room) => $room->location->name.' '.$room->room_no,
                SORT_NATURAL
            )
            ->values();
    }

    // CSS class for the room card and status badge
    public function css()
    {
        return [
            'Available' => 'free',
            'Occupied' => 'used',
            'Check-out' => 'checkout',
            'Inspection' => 'inspection',
            'Cleaning' => 'clean',
            'Maintenance' => 'maintenance',
        ][$this->status] ?? 'clean';
    }

    // most photos one room can have
    const MAX_PHOTOS = 8;

    /**
     * Web addresses of the room's photos, for the public site.
     *
     * @return array<int, string>
     */
    public function photoUrls(): array
    {
        return array_map(fn (string $path) => Storage::disk('public')->url($path), $this->photos ?? []);
    }

    public function rateSummary(): string
    {
        $configured = [];

        foreach ($this->rates() as $label => $rate) {
            $configured[] = $label.': ₱'.number_format((float) $rate, 2);
        }

        return $configured ? implode(' · ', $configured) : 'No rates set';
    }

    public function rates(): array
    {
        $rates = [
            'Per night' => $this->rate,
            'Per hour' => $this->rate_hourly,
            'Daily' => $this->rate_daytour,
        ];

        $configured = [];

        foreach ($rates as $label => $rate) {
            if ($rate !== null && $rate !== '') {
                $configured[$label] = $rate;
            }
        }

        return $configured;
    }

    public function inclusionsList(): array
    {
        $value = $this->inclusions;

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        $items = is_array($decoded)
            ? $decoded
            : preg_split('/[\r\n,]+/', $value);

        return array_values(array_unique(array_filter(
            array_map(fn ($item) => trim((string) $item), $items),
            fn ($item) => $item !== ''
        )));
    }
}
