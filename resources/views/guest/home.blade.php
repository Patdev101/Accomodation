@extends('guest.layout')

@section('title', 'Welcome')

@section('hero')
    {{-- the admin can put a photo or a short video behind this banner (Admin > Banner and contact) --}}
    @php($bannerPhoto = \App\Models\Setting::bannerPhotoUrl())
    @php($bannerIsVideo = \App\Models\Setting::bannerIsVideo())
    <section class="hero {{ $bannerPhoto ? 'with-photo' : '' }} {{ $bannerIsVideo ? 'with-video' : '' }}" @if ($bannerPhoto && ! $bannerIsVideo) style="--banner: url('{{ $bannerPhoto }}')" @endif>
        @if ($bannerIsVideo)
            {{-- silent and on repeat; it is decoration, so screen readers skip it --}}
            <video class="hero-video" src="{{ $bannerPhoto }}" autoplay muted loop playsinline preload="metadata" aria-hidden="true" tabindex="-1"></video>
            <button type="button" class="hero-pause" aria-pressed="false">Pause video</button>
        @endif        <div class="site-wrap">
            @auth
                {{-- a logged-in guest is greeted by first name (the first word of their full name) --}}
                <h1>Welcome, {{ Str::before(trim(auth()->user()->name), ' ') }}!</h1>
                <p>Good to see you. Choose a room below to reserve your stay, or open My reservations to see the ones you already have.</p>
            @else
                <h1>A comfortable place to stay while you work or visit</h1>
                <p>Guest villas and barracks inside the compound, for visitors, contractors and company guests. Look around first; you only need an account when you are ready to reserve.</p>
            @endauth
            <div class="actions">
                <a class="btn hero-btn" href="#rooms">View rooms</a>
                @guest
                    <a class="btn hero-btn outline" href="/signup" data-open="signupModal">Sign up to book</a>
                @else
                    <a class="btn hero-btn outline" href="/book">Book a room</a>
                @endguest
            </div>
        </div>
    </section>
@endsection

@section('content')
    @include('guest.decisions')

    <section class="site-section" id="rooms">
        <h2>Our rooms</h2>
        <p class="subtitle">Rates and what is included in each room.</p>

        @forelse ($locations as $location => $rooms)
            {{-- one carousel per location: the rooms sit in one row that slides sideways --}}
            <div class="carousel">
                <div class="carousel-head">
                    <h3 class="location-title">{{ $location }} <small>{{ $rooms->count() }} {{ $rooms->count() == 1 ? 'room' : 'rooms' }}</small></h3>
                    <div class="carousel-buttons">
                        <button type="button" class="carousel-prev" aria-label="Previous {{ $location }} rooms">‹</button>
                        <button type="button" class="carousel-next" aria-label="Next {{ $location }} rooms">›</button>
                    </div>
                </div>

            <div class="room-list" tabindex="0" aria-label="{{ $location }} rooms">
                @foreach ($rooms as $room)
                    <div class="room-item">
                        {{-- the cover photo opens the photo pop-up; the admin adds photos on the room form --}}
                        @if ($room->photoUrls())
                            <button type="button" class="room-photo" data-photos='@json($room->photoUrls())' data-room="{{ $room->room_no }}" aria-label="See the photos of {{ $room->room_no }}">
                                <img src="{{ $room->photoUrls()[0] }}" alt="{{ $room->room_no }}" loading="lazy">
                                <span>{{ count($room->photos) }} {{ count($room->photos) == 1 ? 'photo' : 'photos' }}</span>
                            </button>
                        @else
                            <div class="room-photo none">No photos yet</div>
                        @endif
                        <div class="room-item-head">
                            <b>{{ $room->room_no }}</b>
                            <span>Good for {{ $room->capacity }} {{ $room->capacity == 1 ? 'guest' : 'guests' }}</span>
                        </div>

                        <ul class="room-item-rates">
                            @forelse ($room->rates() as $label => $rate)
                                <li><span>{{ $label }}</span> <b>₱{{ number_format((float) $rate, 2) }}</b></li>
                            @empty
                                <li><span>Ask reception for the rate</span></li>
                            @endforelse
                        </ul>

                        @if ($room->inclusionsList())
                            <div class="room-inclusion-list">
                                @foreach ($room->inclusionsList() as $item)
                                    <span class="room-item-tag">{{ $item }}</span>
                                @endforeach
                            </div>
                        @endif

                        {{-- booking needs an account, so visitors are sent to sign up first --}}
                        @auth
                            <a class="btn primary" href="/book?room={{ $room->id }}">Book this room</a>
                        @else
                            <a class="btn primary" href="/signup?room={{ $room->id }}" data-open="signupModal" data-room="{{ $room->id }}">Book this room</a>
                        @endauth
                    </div>
                @endforeach
            </div>
            </div>
        @empty
            <div class="empty">No rooms are open for booking right now. Please check back later.</div>
        @endforelse
    </section>

    @if ($bannerIsVideo)
        <script>
            // Banner video: the button pauses and plays it. It starts paused for people
            // who asked their device for less motion.
            (function () {
                const video = document.querySelector('.hero-video');
                const button = document.querySelector('.hero-pause');

                function show() {
                    button.textContent = video.paused ? 'Play video' : 'Pause video';
                    button.setAttribute('aria-pressed', video.paused);
                }

                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    video.removeAttribute('autoplay');
                    video.pause();
                }

                button.addEventListener('click', function () {
                    if (video.paused) {
                        video.play();
                    } else {
                        video.pause();
                    }
                });

                video.addEventListener('play', show);
                video.addEventListener('pause', show);
                show();
            })();
        </script>
    @endif

    {{-- Photo pop-up: opened by the cover photo of a room card --}}
    <div class="modal" id="photoModal">
        <div class="login photo-box" role="dialog" aria-modal="true" aria-labelledby="photoTitle">
            <div class="login-header">
                <button type="button" class="close" data-close aria-label="Close">×</button>
                <h2 id="photoTitle">Room photos</h2>
                <p id="photoCount"></p>
            </div>

            <div class="login-body">
                <div class="photo-stage">
                    <button type="button" id="photoPrevious" aria-label="Previous photo">‹</button>
                    <img id="photoImage" src="" alt="">
                    <button type="button" id="photoNext" aria-label="Next photo">›</button>
                </div>

                <div class="actions how-actions">
                    <button type="button" data-close>Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Photo pop-up: ‹ › or the left / right arrow keys go through the photos of one room.
        (function () {
            const modal = document.getElementById('photoModal');
            const image = document.getElementById('photoImage');
            let photos = [];
            let current = 0;
            let room = '';

            function show(index) {
                current = (index + photos.length) % photos.length;
                image.src = photos[current];
                image.alt = 'Photo ' + (current + 1) + ' of ' + room;
                document.getElementById('photoCount').textContent = 'Photo ' + (current + 1) + ' of ' + photos.length;
            }

            document.querySelectorAll('button.room-photo').forEach(function (button) {
                button.addEventListener('click', function () {
                    photos = JSON.parse(button.dataset.photos);
                    room = button.dataset.room;

                    document.getElementById('photoTitle').textContent = room;
                    document.getElementById('photoPrevious').hidden = photos.length < 2;
                    document.getElementById('photoNext').hidden = photos.length < 2;

                    show(0);
                    modal.classList.add('show');
                    modal.querySelector('[data-close]').focus();
                });
            });

            document.getElementById('photoPrevious').addEventListener('click', function () { show(current - 1); });
            document.getElementById('photoNext').addEventListener('click', function () { show(current + 1); });

            document.addEventListener('keydown', function (event) {
                if (! modal.classList.contains('show')) {
                    return;
                }

                if (event.key === 'ArrowLeft') {
                    show(current - 1);
                } else if (event.key === 'ArrowRight') {
                    show(current + 1);
                }
            });
        })();
    </script>

    <script>
        // Room carousels: the arrows slide the row by about one screen of cards.
        // The arrows are hidden when all the rooms of a location already fit, and greyed out at each end.
        // Swiping (phone) and the arrow keys also work, because the row is an ordinary scrolling box.
        document.querySelectorAll('.carousel').forEach(function (carousel) {
            const list = carousel.querySelector('.room-list');
            const previous = carousel.querySelector('.carousel-prev');
            const next = carousel.querySelector('.carousel-next');

            function update() {
                const furthest = list.scrollWidth - list.clientWidth;

                carousel.classList.toggle('fits', furthest <= 1);
                previous.disabled = list.scrollLeft <= 1;
                next.disabled = list.scrollLeft >= furthest - 1;
            }

            function slide(direction) {
                list.scrollBy({ left: direction * list.clientWidth * 0.9, behavior: 'smooth' });
            }

            previous.addEventListener('click', function () { slide(-1); });
            next.addEventListener('click', function () { slide(1); });
            list.addEventListener('scroll', update);
            window.addEventListener('resize', update);
            update();
        });
    </script>

@endsection
