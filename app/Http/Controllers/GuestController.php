<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class GuestController extends Controller
{
    // ================= PUBLIC SITE (no account needed) =================

    public function home(Request $request)
    {
        // staff who are logged in go straight to their own side
        if ($request->user() && $request->user()->role != 'guest') {
            return redirect($request->user()->role == 'admin' ? '/admin' : '/dashboard');
        }

        return view('guest.home', [
            'locations' => $this->bookableRooms()->groupBy(fn ($room) => $room->location->name),
            // /login and /signup are this same page with their pop-up open
            'open' => $request->route('open'),
            'decisions' => $this->newDecisions($request),
        ]);
    }

    // ================= SIGN UP =================

    public function signup(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|max:100',
            'email' => 'required|email|max:150|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'terms' => 'accepted',
            'room' => 'nullable|integer',
        ], [
            'email.unique' => 'That email already has an account. Log in instead.',
            'terms.accepted' => 'You need to agree to the terms and conditions to create an account.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'guest',
            'terms_accepted_at' => now(),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        // continue to the room they were looking at, otherwise to the home page
        return redirect(empty($data['room']) ? '/' : '/book?room='.$data['room'])
            ->with('success', 'Account created. You can now reserve a room.');
    }

    // ================= RESERVE A ROOM (guest account needed) =================

    public function bookForm(Request $request)
    {
        return view('guest.book', [
            'rooms' => $this->bookableRooms(),
            'selected' => $request->query('room'),
        ]);
    }

    public function book(Request $request)
    {
        $data = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'contact_no' => 'required|max:50',
            'id_type' => ['required', Rule::in(Booking::ID_TYPES)],
            // 2 MB is the upload limit of PHP on this machine
            'id_photo' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'company' => 'nullable|max:150',
            'no_of_guests' => 'required|integer|min:1',
            'check_in_datetime' => 'required|date|after:now',
            'check_out_datetime' => 'required|date|after:check_in_datetime',
            'remarks' => 'nullable|max:255',
        ], [
            'check_in_datetime.after' => 'The check-in must be in the future.',
            'check_out_datetime.after' => 'The check-out must be after the check-in.',
            'id_photo.required' => 'Please upload a photo of your valid ID.',
            'id_photo.mimes' => 'The ID must be a JPG or PNG photo, or a PDF.',
            'id_photo.max' => 'The ID file is too big. The limit is 2 MB.',
            'id_photo.uploaded' => 'The ID file is too big. The limit is 2 MB.',
        ]);

        $room = Room::find($data['room_id']);

        if ($room->status == 'Maintenance') {
            return back()->with('error', 'That room cannot be reserved right now. Please choose another room.')->withInput();
        }

        if ($data['no_of_guests'] > $room->capacity) {
            return back()->with('error', 'Too many guests. '.$room->room_no.' is good for '.$room->capacity.'.')->withInput();
        }

        $checkIn = Carbon::parse($data['check_in_datetime']);
        $checkOut = Carbon::parse($data['check_out_datetime']);

        if (Booking::roomTaken($room->id, $checkIn->format('Y-m-d'), $checkOut->format('Y-m-d'))) {
            return back()->with('error', 'That room is already reserved during the selected dates. Please choose other dates or another room.')->withInput();
        }

        Booking::create([
            'user_id' => $request->user()->id,
            'guest_name' => $request->user()->name,
            'email' => $request->user()->email,
            'company' => $data['company'] ?? null,
            'contact_no' => $data['contact_no'],
            'room_id' => $room->id,
            'no_of_guests' => $data['no_of_guests'],
            'check_in' => $checkIn->format('Y-m-d'),
            'check_in_time' => $checkIn->format('H:i'),
            'check_out' => $checkOut->format('Y-m-d'),
            'check_out_time' => $checkOut->format('H:i'),
            'remarks' => $data['remarks'] ?? null,
            // waits for reception or the admin to check the ID
            'status' => 'Pending',
            'id_type' => $data['id_type'],
            // private storage: the ID can only be opened through /approvals/{booking}/id
            'id_photo' => $request->file('id_photo')->store('ids'),
        ]);

        return redirect('/my-reservations')->with('success', 'Reservation sent. We will check your ID and confirm it; you will get a message here and by email.');
    }

    public function reservations(Request $request)
    {
        return view('guest.reservations', [
            'bookings' => Booking::with('room')
                ->where('user_id', $request->user()->id)
                ->orderByDesc('check_in')
                ->get(),
            'decisions' => $this->newDecisions($request),
        ]);
    }

    public function cancel(Request $request, Booking $booking)
    {
        // a guest can only cancel their own reservation
        abort_unless($booking->user_id == $request->user()->id, 403);

        if (! in_array($booking->status, ['Pending', 'Reserved'])) {
            return back()->with('error', 'This reservation can no longer be cancelled.');
        }

        $booking->update(['status' => 'Cancelled']);

        return back()->with('success', 'Reservation cancelled.');
    }

    // reservations that were approved or declined since the guest last looked;
    // each one is shown once as a message on the site
    private function newDecisions(Request $request)
    {
        if (! $request->user()) {
            return collect();
        }

        $decisions = Booking::with('room')
            ->where('user_id', $request->user()->id)
            ->whereNotNull('reviewed_at')
            ->whereNull('guest_seen_at')
            ->get();

        Booking::whereIn('id', $decisions->pluck('id'))->update(['guest_seen_at' => now()]);

        return $decisions;
    }

    // rooms shown on the public site: everything except rooms under maintenance
    private function bookableRooms()
    {
        return Room::listed()->where('status', '!=', 'Maintenance')->values();
    }
}
