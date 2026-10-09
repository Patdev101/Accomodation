{{-- "How to book" pop-up on the public site. Opened by any link with data-open="howModal". --}}
<div class="modal" id="howModal">
    <div class="login how-box" role="dialog" aria-modal="true" aria-labelledby="howTitle">

        <div class="login-header">
            <button type="button" class="close" data-close aria-label="Close">×</button>
            <h2 id="howTitle">How to book</h2>
            <p>Three short steps</p>
        </div>

        <div class="login-body">
            <div class="how-list">
                <div class="how-item">
                    <span>1</span>
                    <b>Choose a room</b>
                    <p>Compare the rooms: size, rates and inclusions.</p>
                </div>
                <div class="how-item">
                    <span>2</span>
                    <b>Create an account</b>
                    <p>Sign up with your email and agree to the terms and conditions.</p>
                    <small class="how-note">Already signed up? Skip this step and just log in.</small>
                </div>
                <div class="how-item">
                    <span>3</span>
                    <b>Reserve your dates</b>
                    <p>Pick your dates and upload a photo of your valid ID. We confirm the reservation once the ID is checked.</p>
                </div>
            </div>

            <div class="actions how-actions">
                @auth
                    <a class="btn primary" href="/book">Book a room</a>
                @else
                    <a class="btn primary" href="/signup" data-open="signupModal">Sign up to book</a>
                @endauth
                <button type="button" data-close>Close</button>
            </div>
        </div>

    </div>
</div>
