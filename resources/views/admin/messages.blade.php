@extends('layout')

@section('content')
    <h2>Guest messages</h2>
    <p class="subtitle">Feedback and questions that guests sent from the public site, newest first. Answer a question by emailing the guest.</p>

    <div class="box">
        @if ($messages->isEmpty())
            <div class="empty">No messages yet.</div>
        @else
            <div class="table-wrap">
                <table>
                    <tr><th>Sent</th><th>Guest</th><th>Type</th><th>Message</th></tr>
                    @foreach ($messages as $message)
                        <tr>
                            <td>
                                {{ $message->created_at->format('M d, Y') }}<br>
                                <small class="muted">{{ $message->created_at->format('h:i A') }}</small>
                                {{-- still unread when this page was opened --}}
                                @if (! $message->read_at)
                                    <span class="badge reserved">New</span>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $message->user->name }}</strong><br>
                                <a href="mailto:{{ $message->user->email }}">{{ $message->user->email }}</a>
                            </td>
                            <td><span class="badge {{ $message->type == 'question' ? 'checking' : 'free' }}">{{ \App\Models\Message::TYPES[$message->type] ?? $message->type }}</span></td>
                            <td class="message-body">{{ $message->body }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>

            {{ $messages->links('partials.pager') }}
        @endif
    </div>
@endsection
