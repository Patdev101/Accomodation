<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        // This page is for reservations only. Guests who are checked in or
        // checked out are on the Check-out page.
        $status = in_array($request->query('status'), ['Cancelled', 'All']) ? $request->query('status') : 'Reserved';

        $bookings = Booking::with('room')
            ->whereIn('status', $status == 'All' ? ['Reserved', 'Cancelled'] : [$status])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('guest_name', 'like', "%$search%")
                        ->orWhere('company', 'like', "%$search%")
                        ->orWhereHas('room', fn ($r) => $r->where('room_no', 'like', "%$search%"));
                });
            })
            ->orderBy('check_in')
            ->paginate(Booking::PER_PAGE)
            ->withQueryString();

        return view('bookings.index', [
            'bookings' => $bookings,
            'search' => $search,
            'status' => $status,
            'rooms' => Room::listed()->where('status', '!=', 'Maintenance'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'guest_name' => 'required|max:150',
            'company' => 'nullable|max:150',
            'contact_no' => 'nullable|max:50',
            'email' => 'nullable|email|max:150',
            'room_id' => 'required|exists:rooms,id',
            'no_of_guests' => 'required|integer|min:1',

            // Combined check-in date and time
            'check_in_datetime' => 'required|date',

            // Combined expected check-out date and time
            'check_out_datetime' => 'required|date|after:check_in_datetime',

            'remarks' => 'nullable|max:255',
        ]);

        $room = Room::find($data['room_id']);

        if ($data['no_of_guests'] > $room->capacity) {
            return back()
                ->with('error', 'Too many guests. Room capacity is '.$room->capacity.'.')
                ->withInput();
        }

        // Convert check-in date/time into the existing database fields.
        $checkIn = Carbon::parse($data['check_in_datetime']);

        $data['check_in'] = $checkIn->format('Y-m-d');
        $data['check_in_time'] = $checkIn->format('H:i');

        // Convert expected check-out date/time into the existing database fields.
        $checkOut = Carbon::parse($data['check_out_datetime']);

        $data['check_out'] = $checkOut->format('Y-m-d');
        $data['check_out_time'] = $checkOut->format('H:i');

        unset($data['check_in_datetime'], $data['check_out_datetime']);

        // Check if the room already has a booking during the selected dates.
        if (Booking::roomTaken($room->id, $data['check_in'], $data['check_out'])) {
            return back()
                ->with('error', 'That room is already reserved during the selected dates.')
                ->withInput();
        }

        $data['contact_no'] = $data['contact_no'] ?? '';

        Booking::create($data);

        return redirect('/bookings')->with('success', 'Reservation saved.');
    }

    public function cancel(Booking $booking)
    {
        if ($booking->status != 'Reserved') {
            return back()->with('error', 'Only reserved bookings can be cancelled.');
        }

        $booking->update(['status' => 'Cancelled']);

        return back()->with('success', 'Reservation cancelled.');
    }

    public function destroy(Booking $booking)
    {
        if (in_array($booking->status, ['Checked In', 'Checking Out'])) {
            return back()->with('error', 'Guest is still checked in.');
        }

        // the ID uploaded with an online reservation goes with it
        if ($booking->id_photo) {
            Storage::delete($booking->id_photo);
        }

        $booking->delete();

        return redirect('/bookings')->with('success', 'Reservation deleted.');
    }
}
