<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReceptionController extends Controller
{
    // ================= CHECK-IN PROCESS =================
    // 1 Guest arrives -> 2 Reception -> 3 Check-in form -> 4 Verification
    // -> 5 Surrender valid ID -> 6 Assign accommodation -> 7 Room assignment -> 8 Proceed to room

    public function checkinForm(Request $request)
    {
        $selected = Booking::where('status', 'Reserved')->find($request->query('booking'));

        return view('checkin', [
            'reservations' => Booking::with('room')
                ->where('status', 'Reserved')
                ->orderBy('check_in')
                ->get(),

            'rooms' => Room::listed('Available'),

            'selected' => $selected,

            // the room picked with "Get room" on the dashboard
            'roomId' => $request->query('room'),

            'current' => Booking::with('room')
                ->where('status', 'Checked In')
                ->orderBy('check_out')
                ->paginate(Booking::PER_PAGE)
                ->withQueryString(),
        ]);
    }

    public function checkin(Request $request)
    {
        $data = $request->validate([
            'booking_id' => 'nullable|exists:bookings,id',

            // Step 3: check-in form
            'guest_name' => 'required|max:150',
            'guest_type' => 'required|in:Visitor,Contractor',
            'company' => 'nullable|max:150',
            'contact_no' => 'required|max:50',
            'address' => 'required|max:255',
            'no_of_guests' => 'required|integer|min:1',

            // Names of the other guests when the group is 2 or more
            'guest_list' => 'nullable|array',
            'guest_list.*.name' => 'required|max:150',
            'guest_list.*.address' => 'required|max:255',
            'guest_list.*.contact_no' => 'required|max:50',

            // Combined expected check-out date + time
            'check_out_datetime' => 'required|date|after:now',

            'remarks' => 'nullable|max:255',

            // Step 4: verification
            'verified' => 'accepted',

            // Step 5: surrender valid ID
            'id_type' => 'required|max:50',
            'id_number' => 'required|max:50',
            'id_surrendered' => 'accepted',

            // Step 6: assign accommodation
            'room_id' => 'required|exists:rooms,id',
            'billing_rate_type' => 'required|in:nightly,hourly,daytour',

            // More rooms when the group does not fit in one room
            'extra_rooms' => 'nullable|array',
            'extra_rooms.*' => 'exists:rooms,id',
        ], [
            'verified.accepted' => 'Step 4: Please verify the guest first.',
            'id_surrendered.accepted' => 'Step 5: The guest must surrender a valid ID.',
            'id_type.required' => 'Step 5: Select the type of ID.',
            'id_number.required' => 'Step 5: Enter the ID number.',
            'room_id.required' => 'Step 6: Assign a room or barracks.',
            'check_out_datetime.required' => 'Step 3: Enter the expected check-out date and time.',
            'check_out_datetime.after' => 'Step 3: Expected check-out must be in the future.',
            'guest_list.*.*.required' => 'Step 3: Enter the name, address and contact number of every guest in the group.',
        ], [
            'guest_name' => 'guest name',
            'contact_no' => 'contact number',
        ]);

        // Convert the combined expected check-out date/time
        // into the existing database fields.
        $checkOut = Carbon::parse($data['check_out_datetime']);

        $data['check_out'] = $checkOut->format('Y-m-d');
        $data['check_out_time'] = $checkOut->format('H:i');

        unset($data['check_out_datetime']);

        // A group of 2 or more needs one name for every other guest.
        $data['guest_list'] = array_values($data['guest_list'] ?? []);

        if (count($data['guest_list']) != $data['no_of_guests'] - 1) {
            return back()
                ->with('error', 'Step 3: Enter the name, address and contact number of every guest in the group.')
                ->withInput();
        }

        $room = Room::find($data['room_id']);

        if ($room->status != 'Available') {
            return back()
                ->with('error', $room->room_no.' is not available ('.$room->status.').')
                ->withInput();
        }

        // The other rooms chosen for the same group (never the main room twice).
        $extraIds = array_diff(array_unique($data['extra_rooms'] ?? []), [$room->id]);
        $extraRooms = Room::whereIn('id', $extraIds)->get();
        unset($data['extra_rooms']);

        $rateField = [
            'nightly' => 'rate',
            'hourly' => 'rate_hourly',
            'daytour' => 'rate_daytour',
        ][$data['billing_rate_type']];

        foreach (collect([$room])->concat($extraRooms) as $place) {
            if ($place->$rateField === null) {
                return back()
                    ->with('error', $place->room_no.' does not have a '.$data['billing_rate_type'].' rate.')
                    ->withInput();
            }
        }

        foreach ($extraRooms as $extra) {
            if ($extra->status != 'Available') {
                return back()
                    ->with('error', $extra->room_no.' is not available ('.$extra->status.').')
                    ->withInput();
            }
        }

        // The group must fit in the chosen rooms. If not, the check-in is refused
        // and the system suggests available rooms that can take everyone.
        $rooms = collect([$room])->concat($extraRooms);
        $guests = $data['no_of_guests'];

        if ($guests > $rooms->sum('capacity')) {
            return back()
                ->with('error', $this->notEnoughRoomMessage($guests, $rooms))
                ->withInput();
        }

        $bookingId = $data['booking_id'] ?? null;

        unset($data['booking_id']);

        $data['verified'] = true;
        $data['id_surrendered'] = true;
        $data['status'] = 'Checked In';
        $data['checked_in_at'] = now();
        $billingRateType = $data['billing_rate_type'];
        unset($data['billing_rate_type']);

        if (! $bookingId) {
            // Walk-in guest.
            // Actual check-in date and time are recorded automatically
            // when the receptionist completes the check-in.
            $data['check_in'] = date('Y-m-d');
            $data['check_in_time'] = date('H:i');
        }

        // Share the guests among the rooms: fill the first room, then the next.
        // Everyone is one list: the main guest first, then the others.
        $everyone = array_merge(
            [['name' => $data['guest_name'], 'address' => $data['address'], 'contact_no' => $data['contact_no']]],
            $data['guest_list']
        );
        $roomNames = $rooms->pluck('room_no')->implode(', ');
        $summary = [];
        $booking = null;

        foreach ($rooms as $index => $place) {
            $group = array_splice($everyone, 0, $place->capacity);

            if (count($group) == 0) {
                break; // more rooms were ticked than needed
            }

            // the first guest in each room is the name on that room's record
            $row = array_merge($data, [
                'room_id' => $place->id,
                'billing_rate_type' => $billingRateType,
                'billing_rate' => $place->$rateField,
                'guest_name' => $group[0]['name'],
                'address' => $group[0]['address'],
                'contact_no' => $group[0]['contact_no'],
                'guest_list' => array_slice($group, 1),
                'no_of_guests' => count($group),
            ]);

            if ($rooms->count() > 1) {
                $row['remarks'] = trim(($data['remarks'] ?? '').' Group of '.$guests.' with '.$data['guest_name'].' ('.$roomNames.').');
            }

            if ($index == 0) {
                // Existing reservation keeps its original scheduled check-in date/time.
                $booking = $bookingId ? Booking::find($bookingId) : new Booking;
                $booking->fill($row)->save();
            } else {
                // the ID is held once, under the main guest
                $row['id_surrendered'] = false;
                $row['check_in'] = $booking->check_in;
                $row['check_in_time'] = $booking->check_in_time;
                Booking::create($row);
            }

            $place->update(['status' => 'Occupied']);
            $summary[] = $place->room_no.' ('.count($group).' '.(count($group) == 1 ? 'guest' : 'guests').')';
        }

        // Step 7: show the room assignment slip.
        return redirect('/checkin/'.$booking->id.'/slip')
            ->with('success', count($summary) > 1
                ? 'Check-in complete. '.count($summary).' rooms booked: '.implode(', ', $summary).'.'
                : 'Check-in complete.');
    }

    // Why the group does not fit, and which available rooms would take everyone.
    private function notEnoughRoomMessage(int $guests, $chosen)
    {
        $message = $chosen->pluck('room_no')->implode(' + ').' is good for '.$chosen->sum('capacity').' only, but there are '.$guests.' guests. ';

        $suggestion = $this->suggestRooms($guests, $chosen->first());

        if ($suggestion === null) {
            return $message.'There are not enough available rooms for the whole group right now.';
        }

        $text = $suggestion->map(fn ($room) => $room->room_no.' (good for '.$room->capacity.')')->implode(' + ');

        return $message.'Suggested: '.$text.', '.$suggestion->count().' '.($suggestion->count() == 1 ? 'room' : 'rooms').' in total. Choose them in step 6.';
    }

    // Available rooms that can take the whole group: one room if it fits,
    // otherwise the chosen room plus the fewest other rooms. Null = not possible.
    private function suggestRooms(int $guests, Room $main)
    {
        $available = Room::listed('Available');

        // one room that fits everyone (the smallest that does)
        $single = $available->where('capacity', '>=', $guests)->sortBy('capacity')->first();
        if ($single) {
            return collect([$single]);
        }

        $picked = collect([$main]);
        $others = $available->where('id', '!=', $main->id);

        while ($picked->sum('capacity') < $guests) {
            $need = $guests - $picked->sum('capacity');
            $left = $others->whereNotIn('id', $picked->pluck('id'));

            // the smallest room that covers the rest, or else the biggest room left
            $next = $left->where('capacity', '>=', $need)->sortBy('capacity')->first()
                ?? $left->sortByDesc('capacity')->first();

            if (! $next) {
                return null;
            }

            $picked->push($next);
        }

        return $picked;
    }

    // ================= ROOM ASSIGNMENT =================

    // Step 7 and 8: room / barracks assignment slip
    public function slip(Booking $booking)
    {
        return view('slip', [
            'booking' => $booking->load('room'),
        ]);
    }

    // ================= CHECK-OUT PROCESS =================
    // 1 Reports to reception -> 2 Check-out verification -> 3 Room inspection
    // -> 4 Additional charges -> 5 Billing / payment -> 6 Return ID
    // -> 7 Record check-out in guest log -> 8 Guest leaves

    // Guests in house on top, then the checked-out guests with their own search and sorting.
    public function checkoutList(Request $request)
    {
        $search = $request->query('search');
        $sort = $request->query('sort', 'recent');

        $history = Booking::with('room')
            ->where('status', 'Checked Out')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('guest_name', 'like', "%$search%")
                        ->orWhere('company', 'like', "%$search%")
                        ->orWhereHas('room', fn ($r) => $r->where('room_no', 'like', "%$search%"));
                });
            })
            // The most recent check-out is first unless reception picks another order.
            ->when($sort == 'oldest', fn ($q) => $q->orderBy('actual_check_out')->orderBy('updated_at'))
            ->when($sort == 'guest', fn ($q) => $q->orderBy('guest_name'))
            ->when($sort == 'room', fn ($q) => $q->orderBy(Room::select('room_no')->whereColumn('rooms.id', 'bookings.room_id')))
            ->when(! in_array($sort, ['oldest', 'guest', 'room']), fn ($q) => $q->orderByDesc('actual_check_out')->orderByDesc('updated_at'))
            ->paginate(Booking::PER_PAGE)
            ->withQueryString()
            ->fragment('checked-out');

        return view('checkout', [
            // the guest who should leave soonest (or is overdue) is first
            'current' => Booking::with('room')
                ->whereIn('status', ['Checked In', 'Checking Out'])
                ->orderBy('check_out')
                ->get(),

            'history' => $history,
            'search' => $search,
            'sort' => $sort,
        ]);
    }

    public function checkoutProcess(Booking $booking)
    {
        if (! in_array($booking->status, ['Checked In', 'Checking Out', 'Checked Out'])) {
            return redirect('/checkout')
                ->with('error', 'This guest is not checked in.');
        }

        return view('checkout-process', [
            'booking' => $booking->load('room'),
        ]);
    }

    // Step 2: check-out verification
    // Room goes to "Check-out"
    public function verifyCheckout(Request $request, Booking $booking)
    {
        if ($booking->status != 'Checked In') {
            return back()->with('error', 'This guest is not checked in.');
        }

        $booking->update([
            'status' => 'Checking Out',
            'checkout_step' => 2,
            'checkout_verified_at' => now(),
        ]);

        $booking->room->update([
            'status' => 'Check-out',
        ]);

        return back()->with('success', 'Verified. Next: inspect the room.');
    }

    // Steps 3 and 4: room inspection and additional charges
    // Room goes to "Inspection"
    public function inspect(Request $request, Booking $booking)
    {
        if ($booking->status != 'Checking Out' || $booking->checkout_step != 2) {
            return back()->with('error', 'Do the check-out verification first.');
        }

        $submittedLines = $request->input('additional_charges', []);
        $lines = is_array($submittedLines)
            ? array_values(array_filter($submittedLines, function ($line) {
                if (! is_array($line)) {
                    return false;
                }

                $description = $line['description'] ?? '';
                $amount = $line['amount'] ?? null;

                return (is_string($description) && trim($description) !== '')
                    || (is_numeric($amount) && (float) $amount > 0);
            }))
            : $submittedLines;
        $request->merge(['additional_charges' => $lines]);

        $data = $request->validate([
            'inspection_notes' => 'nullable|max:255',
            'damage_notes' => 'nullable|max:255',
            'additional_charges' => 'nullable|array|max:20',
            'additional_charges.*.category' => 'required|in:Extra service,Damage,Other',
            'additional_charges.*.description' => 'required|string|max:150',
            'additional_charges.*.amount' => 'required|numeric|min:0',
            'room_after' => 'required|in:Cleaning,Maintenance',
        ]);

        $data['additional_charges'] = $data['additional_charges'] ?? [];
        $data['charges'] = round(array_sum(array_column($data['additional_charges'], 'amount')), 2);
        $data['amount_paid'] = 0;
        $data['checkout_step'] = 4;

        // Nothing to pay, so billing is already settled.
        if ($booking->accommodationCharge() + $data['charges'] <= 0) {
            $data['charges_paid'] = true;
            $data['checkout_step'] = 5;
        } else {
            $data['charges_paid'] = false;
        }

        $booking->update($data);

        $booking->room->update([
            'status' => 'Inspection',
        ]);

        return back()->with('success', 'Inspection saved.');
    }

    // Step 5: record a payment toward the bill
    public function settle(Request $request, Booking $booking)
    {
        if ($booking->status != 'Checking Out' || $booking->checkout_step != 4) {
            return back()->with('error', 'Nothing to settle at this step.');
        }

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $payment = round((float) $data['amount'], 2);
        $balance = $booking->balance();

        if ($payment > $balance) {
            return back()->with('error', 'Payment cannot be more than the remaining balance of ₱'.number_format($balance, 2).'.');
        }

        $amountPaid = round((float) $booking->amount_paid + $payment, 2);
        $fullyPaid = $amountPaid >= $booking->totalAmount();

        $booking->update([
            'amount_paid' => $amountPaid,
            'charges_paid' => $fullyPaid,
            'checkout_step' => $fullyPaid ? 5 : 4,
        ]);

        if (! $fullyPaid) {
            return back()->with('success', 'Payment recorded. Remaining balance: ₱'.number_format($booking->fresh()->balance(), 2).'. Full payment is required before the ID can be returned.');
        }

        return back()->with('success', 'Bill paid in full. Next: return the ID.');
    }

    // Step 6: return the surrendered ID
    public function returnId(Booking $booking)
    {
        if ($booking->checkout_step != 5) {
            return back()->with('error', 'Settle the charges first.');
        }

        $booking->update([
            'id_returned' => true,
            'checkout_step' => 6,
        ]);

        return back()->with('success', 'ID returned. Next: record the check-out.');
    }

    // Step 7 and 8: record check-out in the guest log
    // Room goes to Cleaning / Maintenance
    public function recordCheckout(Request $request, Booking $booking)
    {
        if ($booking->checkout_step != 6) {
            return back()->with('error', 'Return the ID first.');
        }

        $booking->update([
            'status' => 'Checked Out',
            'checkout_step' => 8,
            'actual_check_out' => $booking->checkout_verified_at
                ? $booking->checkout_verified_at->toDateString()
                : date('Y-m-d'),
            'checkout_notes' => $request->input('checkout_notes'),
        ]);

        $booking->room->update([
            'status' => $booking->room_after ?: 'Cleaning',
        ]);

        return back()->with(
            'success',
            'Check-out recorded in the guest log. The guest may leave.'
        );
    }

    // ================= CALENDAR =================

    public function calendar(Request $request)
    {
        $month = preg_match(
            '/^\d{4}-\d{2}$/',
            (string) $request->query('month')
        )
            ? $request->query('month')
            : date('Y-m');

        $first = Carbon::parse($month.'-01');
        $last = $first->copy()->endOfMonth();

        $bookings = Booking::with('room')
            ->whereNotIn('status', ['Cancelled', 'Declined'])
            ->where(function ($q) use ($first, $last) {
                $q->whereBetween(
                    'check_in',
                    [$first->toDateString(), $last->toDateString()]
                )
                    ->orWhereBetween(
                        'check_out',
                        [$first->toDateString(), $last->toDateString()]
                    );
            })
            ->get();

        // Show scheduled arrival and departure details on their respective dates.
        $events = [];

        foreach ($bookings as $b) {
            $arrivalDate = $b->check_in->toDateString();
            $departureDate = $b->check_out->toDateString();

            if ($b->check_in->betweenIncluded($first, $last)) {
                $events[$arrivalDate][] = [
                    'type' => 'arrival',
                    'label' => ['Reserved' => 'Reservation', 'Pending' => 'Awaiting approval'][$b->status] ?? 'Arrival',
                    'guest' => $b->guest_name,
                    'room' => $b->room->room_no,
                    'time' => $b->timeText('check_in_time') ?: 'Time not set',
                    'status' => $b->status,
                    'booking_id' => $b->id,
                ];
            }

            if ($b->check_out->betweenIncluded($first, $last)) {
                $events[$departureDate][] = [
                    'type' => 'departure',
                    'label' => 'Expected check-out',
                    'guest' => $b->guest_name,
                    'room' => $b->room->room_no,
                    'time' => $b->timeText('check_out_time') ?: 'Time not set',
                    'status' => $b->status,
                    'booking_id' => $b->id,
                ];
            }
        }

        return view('calendar', [
            'first' => $first,
            'events' => $events,
            'arrivalCount' => collect($events)->flatten(1)->where('type', 'arrival')->count(),
            'departureCount' => collect($events)->flatten(1)->where('type', 'departure')->count(),
            'prev' => $first->copy()->subMonth()->format('Y-m'),
            'next' => $first->copy()->addMonth()->format('Y-m'),
        ]);
    }

    // ================= GUEST LOG / REPORTS =================

    public function reports()
    {
        $rooms = Room::all();

        $bookings = Booking::with('room')
            // online requests that are still waiting or were declined never stayed
            ->whereNotIn('status', ['Reserved', 'Cancelled', 'Pending', 'Declined'])
            ->orderByDesc('check_in')
            ->paginate(Booking::PER_PAGE)
            ->fragment('guest-log');

        $utilization = [];

        foreach (Room::STATUSES as $status) {
            $count = $rooms->where('status', $status)->count();

            $utilization[$status] = [
                $count,
                $rooms->count()
                    ? round($count / $rooms->count() * 100)
                    : 0,
            ];
        }

        return view('reports', [
            'bookings' => $bookings,
            'utilization' => $utilization,

            // counts for the tiles on top (the table below only holds one page)
            'totals' => [
                'stays' => $bookings->total(),
                'inHouse' => Booking::whereIn('status', ['Checked In', 'Checking Out'])->count(),
                'checkedOut' => Booking::where('status', 'Checked Out')->count(),
            ],

            'idsHeld' => Booking::where('id_surrendered', true)
                ->where('id_returned', false)
                ->whereIn('status', ['Checked In', 'Checking Out'])
                ->count(),
        ]);
    }

    public function export()
    {
        $bookings = Booking::with('room')
            ->orderBy('id')
            ->get();

        return response()->streamDownload(function () use ($bookings) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'ID',
                'Guest',
                'Type',
                'Company',
                'Contact',
                'Room',
                'Guests',
                'Check-in',
                'Expected Check-out',
                'Actual Check-out',
                'ID Type',
                'ID Number',
                'ID Returned',
                'Rate Type',
                'Accommodation Charges',
                'Additional Charges',
                'Total Amount',
                'Amount Paid',
                'Balance',
                'Payment Status',
                'Status',
            ]);

            foreach ($bookings as $b) {
                fputcsv($out, [
                    $b->id,
                    $b->guest_name,
                    $b->guest_type,
                    $b->company,
                    $b->contact_no,
                    $b->room->room_no,
                    $b->no_of_guests,
                    $b->check_in->toDateString().' '.$b->check_in_time,
                    $b->check_out->toDateString().' '.$b->check_out_time,
                    optional($b->actual_check_out)->toDateString(),
                    $b->id_type,
                    $b->id_number,
                    $b->id_returned ? 'Yes' : 'No',
                    $b->billingRateLabel(),
                    $b->accommodationCharge(),
                    $b->additionalChargeTotal(),
                    $b->totalAmount(),
                    $b->amount_paid,
                    $b->balance(),
                    $b->paymentStatus(),
                    $b->status,
                ]);
            }

            fclose($out);
        }, 'guest-log-'.date('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function bill(Booking $booking)
    {
        if ($booking->status === 'Cancelled') {
            return redirect('/bookings')->with('error', 'Cancelled reservations do not have a bill.');
        }

        if ($booking->status === 'Reserved' && $booking->room->rate === null) {
            return redirect('/bookings')->with('error', 'This room has no nightly rate. Choose the stay rate during check-in to create an estimate.');
        }

        return view('billing', [
            'booking' => $booking->load('room'),
            'isEstimate' => $booking->status !== 'Checked Out' && $booking->checkout_step < 4,
        ]);
    }
}
