@extends('layout')

@section('content')
    <h2>Public site</h2>
    <p class="subtitle">What guests see on the public site: the banner photo or video at the top of the home page and the contact details in the footer.</p>

    <div class="box">
        <h3>Banner photo or video</h3>
        <p class="hint">Shown behind the welcome message at the top of the home page only. Wide (landscape) works best; it is darkened a little so the white text stays readable. A video plays silently on repeat, so a short clip of 10 to 20 seconds is enough.</p>

        @if ($bannerPhoto && $bannerIsVideo)
            <video class="banner-preview" src="{{ $bannerPhoto }}" controls muted playsinline preload="metadata"></video>
        @elseif ($bannerPhoto)
            <img class="banner-preview" src="{{ $bannerPhoto }}" alt="Current banner photo">
        @else
            <div class="empty">No banner yet. The banner is plain navy.</div>
        @endif

        {{-- PHP on this machine decides how big an upload can be --}}
        @if ($bannerLimitMb < 20)
            <div class="alert warning">
                Uploads are limited to <b>{{ $bannerLimitMb }} MB</b> on this server, which is too small for most videos.
                To allow videos up to 50 MB, set <code>upload_max_filesize = 50M</code> and <code>post_max_size = 55M</code> in <code>{{ php_ini_loaded_file() }}</code>, then restart the server.
            </div>
        @endif

        <form method="POST" action="/admin/banner" enctype="multipart/form-data">
            @csrf
            <label for="banner_photo">{{ $bannerPhoto ? 'Replace it' : 'Upload a photo or video' }} <span class="req">*</span></label>
            <input type="file" id="banner_photo" name="banner_photo" accept=".jpg,.jpeg,.png,.webp,.mp4,.webm" required>
            <small class="hint">Photo: JPG, PNG or WebP. Video: MP4 or WebM. Up to {{ $bannerLimitMb }} MB.</small>
            <br><br>
            <div class="actions">
                <button type="submit" class="primary">{{ $bannerPhoto ? 'Replace' : 'Upload' }}</button>
            </div>
        </form>

        @if ($bannerPhoto)
            <form class="banner-remove" method="POST" action="/admin/banner" data-confirm="Remove the banner {{ $bannerIsVideo ? 'video' : 'photo' }}? The banner goes back to plain navy." data-confirm-ok="Remove">
                @csrf
                @method('DELETE')
                <button type="submit" class="danger">Remove {{ $bannerIsVideo ? 'video' : 'photo' }}</button>
            </form>
        @endif
    </div>

    <form class="box" method="POST" action="/admin/contact">
        @csrf
        @method('PUT')

        <h3>Contact details</h3>
        <p class="hint">Shown on the right side of the footer. Leave a box empty to hide that item.</p>

        <div class="form-grid">
            <div class="full">
                <label for="facebook">Facebook page link</label>
                <input type="url" id="facebook" name="facebook" value="{{ old('facebook', $contact['facebook'] ?? '') }}" placeholder="https://www.facebook.com/yourpage" maxlength="255">
                <small class="hint">Open the page on Facebook and copy the address from the browser.</small>
            </div>
            <div class="full">
                <label for="messenger">Messenger link</label>
                <input type="url" id="messenger" name="messenger" value="{{ old('messenger', $contact['messenger'] ?? '') }}" placeholder="https://m.me/yourpage" maxlength="255">
                <small class="hint">Usually https://m.me/ followed by the page's username.</small>
            </div>
            <div>
                <label for="contact_no">Contact number</label>
                <input type="tel" id="contact_no" name="contact_no" value="{{ old('contact_no', $contact['contact_no'] ?? '') }}" placeholder="e.g. 0917 123 4567" maxlength="50">
                <small class="hint">Guests on a phone can tap it to call.</small>
            </div>
        </div>
        <br>
        <div class="actions">
            <button type="submit" class="primary">Save contact details</button>
            <a class="btn" href="/" target="_blank" rel="noopener">View public site</a>
        </div>
    </form>
@endsection
