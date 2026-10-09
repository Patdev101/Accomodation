{{-- Frequently asked questions pop-up on the public site. Edit the questions and answers here. --}}
<div class="modal" id="faqModal">
    <div class="login how-box" role="dialog" aria-modal="true" aria-labelledby="faqTitle">

        <div class="login-header">
            <button type="button" class="close" data-close aria-label="Close">×</button>
            <h2 id="faqTitle">Frequently asked questions</h2>
            <p>Click a question to see the answer</p>
        </div>

        <div class="login-body">
            <div class="faq-list">
                @foreach ([
                    'How do I reserve a room?' => 'Choose a room, pick your check-in and check-out, upload a photo of your valid ID and send the reservation. You need to be logged in to your guest account.',
                    'When is my reservation confirmed?' => 'After reception or the admin has checked your uploaded ID. You get a message on this site and an email when it is confirmed or declined.',
                    'Which IDs are accepted?' => implode(', ', \App\Models\Booking::ID_TYPES).'. The photo must be clear, and you bring the same ID when you check in.',
                    'Can I cancel my reservation?' => 'Yes. Open My reservations and press Cancel, any time before you check in.',
                    'My reservation was declined. What now?' => 'The reason is shown in My reservations. Fix it (most often a clearer ID photo) and send a new reservation.',
                    'How and when do I pay?' => 'At reception when you check out. The bill is the room rate for your stay, plus any extra services or damages.',
                    'What happens to my ID at check-in?' => 'Reception keeps your valid ID during your stay and returns it when you check out.',
                ] as $question => $answer)
                    <details>
                        <summary>{{ $question }}</summary>
                        <p>{{ $answer }}</p>
                    </details>
                @endforeach
            </div>

            <div class="actions how-actions">
                @auth
                    <a class="btn primary" href="#questionModal" data-open="questionModal">Ask a question</a>
                @endauth
                <button type="button" data-close>Close</button>
            </div>
        </div>

    </div>
</div>
