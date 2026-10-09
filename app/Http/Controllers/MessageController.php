<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MessageController extends Controller
{
    // a guest sends feedback or a question from the menu under their name
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(Message::TYPES))],
            'body' => 'required|min:5|max:1000',
        ], [
            'body.required' => 'Please write your message first.',
            'body.min' => 'Please write a little more, so we can understand it.',
        ]);

        Message::create($data + ['user_id' => $request->user()->id]);

        return back()->with('success', $data['type'] == 'question'
            ? 'Question sent. We will answer by email at '.$request->user()->email.'.'
            : 'Thank you. Your feedback was sent.');
    }

    // the admin reads what guests sent; opening the page marks everything as read
    public function index(): View
    {
        $messages = Message::with('user')->latest()->paginate(20);

        Message::whereNull('read_at')->update(['read_at' => now()]);

        return view('admin.messages', ['messages' => $messages]);
    }
}
