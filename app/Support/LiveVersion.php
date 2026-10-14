<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Location;
use App\Models\Room;

class LiveVersion
{
    /**
     * A short code that changes whenever a booking, room or location is added,
     * changed or deleted. Open pages compare it every few seconds and reload
     * when another user has changed something.
     */
    public static function current(): string
    {
        $parts = [];

        foreach ([Booking::class, Room::class, Location::class] as $model) {
            $parts[] = $model::count().'|'.$model::max('updated_at');
        }

        return md5(implode('#', $parts));
    }
}
