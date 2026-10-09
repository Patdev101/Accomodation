<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Notifications\ReservationReviewed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApprovalController extends Controller
{
    // online reservations waiting for reception or the admin to check the uploaded ID
    public function index(): View
    {
        return view('approvals', [
            'bookings' => Booking::with('room')
                ->where('status', 'Pending')
                ->orderBy('created_at')
                ->get(),
        ]);
    }

    // the uploaded ID is kept in private storage; only staff and the guest who sent it can open it
    public function idPhoto(Request $request, Booking $booking): StreamedResponse
    {
        abort_if($request->user()->role == 'guest' && $booking->user_id != $request->user()->id, 403);
        abort_unless($booking->id_photo && Storage::exists($booking->id_photo), 404);

        return Storage::response($booking->id_photo);
    }

    public function approve(Request $request, Booking $booking): RedirectResponse
    {
        if ($booking->status != 'Pending') {
            return back()->with('error', 'This reservation was already reviewed.');
        }

        $this->review($request, $booking, 'Reserved');

        return back()->with('success', 'Reservation approved. '.$booking->guest_name.' was sent a confirmation.');
    }

    public function decline(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate(['decline_reason' => 'required|max:255'], [
            'decline_reason.required' => 'Please give the reason, so the guest knows what to fix.',
        ]);

        if ($booking->status != 'Pending') {
            return back()->with('error', 'This reservation was already reviewed.');
        }

        $this->review($request, $booking, 'Declined', $data['decline_reason']);

        return back()->with('success', 'Reservation declined. '.$booking->guest_name.' was told why.');
    }

    // save the decision and tell the guest (email, plus a message on the site when they next open it)
    private function review(Request $request, Booking $booking, string $status, ?string $reason = null): void
    {
        $booking->update([
            'status' => $status,
            'decline_reason' => $reason,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'guest_seen_at' => null,
        ]);

        // a mail problem must not undo the decision
        rescue(fn () => $booking->user?->notify(new ReservationReviewed($booking)));
    }
}
