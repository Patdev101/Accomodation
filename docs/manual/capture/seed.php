<?php
use App\Models\Location;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

if (config('database.default') !== 'sqlite') { throw new Exception('wrong database'); }

foreach ([
    ['Super Admin', 'superadmin@example.com', 'superadmin'],
    ['Administrator', 'admin@example.com', 'admin'],
    ['Reception Desk', 'reception@example.com', 'reception'],
    ['Ana Reyes', 'ana.reyes@example.com', 'reception'],
] as [$name, $email, $role]) {
    User::create(['name' => $name, 'email' => $email, 'password' => Hash::make('password'), 'role' => $role, 'active' => true]);
}

$villa = Location::create(['name' => 'Guest Villa', 'description' => 'Two-storey villa for visitors']);
$barracks = Location::create(['name' => 'Barracks', 'description' => 'Shared rooms for contractors']);

$rooms = [
    [$villa, 'Villa 1', 2, 1500, null, null, ['Air conditioning', 'TV', 'Wi-Fi', 'Hot shower']],
    [$villa, 'Villa 2', 4, 2000, 250, null, ['Air conditioning', 'TV', 'Wi-Fi', 'Mini refrigerator']],
    [$villa, 'Villa 3', 2, 1500, null, null, ['Air conditioning', 'Wi-Fi', 'Hot shower']],
    [$villa, 'Villa 4', 6, 2500, null, 1800, ['Air conditioning', 'TV', 'Wi-Fi', 'Kitchenette']],
    [$barracks, 'B-101', 8, 500, null, null, ['Bed space', 'Electric fan', 'Shared bathroom']],
    [$barracks, 'B-102', 8, 500, null, null, ['Bed space', 'Electric fan', 'Shared bathroom']],
    [$barracks, 'B-103', 6, 450, null, 300, ['Bed space', 'Electric fan', 'Locker']],
    [$barracks, 'B-104', 4, 400, null, null, ['Bed space', 'Electric fan', 'Locker']],
    [$barracks, 'B-105', 4, null, null, 350, ['Bed space', 'Utilities', 'Maintenance & cleaning']],
    [$barracks, 'B-106', 4, 400, null, null, ['Bed space', 'Electric fan']],
];
foreach ($rooms as [$location, $no, $capacity, $night, $hour, $day, $inclusions]) {
    Room::create([
        'location_id' => $location->id, 'room_no' => $no, 'capacity' => $capacity,
        'rate' => $night, 'rate_hourly' => $hour, 'rate_daytour' => $day,
        'status' => 'Available', 'inclusions' => json_encode($inclusions),
    ]);
}
Room::where('room_no', 'B-102')->update(['status' => 'Cleaning']);
Room::where('room_no', 'B-103')->update(['status' => 'Maintenance']);
echo User::count(), ' users, ', Location::count(), ' locations, ', Room::count(), ' rooms', PHP_EOL;
