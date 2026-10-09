<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function edit()
    {
        return view('admin.contact', [
            'contact' => Setting::contact(),
            'bannerPhoto' => Setting::bannerPhotoUrl(),
            'bannerIsVideo' => Setting::bannerIsVideo(),
            'bannerLimitMb' => Setting::bannerLimitMb(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'facebook' => 'nullable|url:http,https|max:255',
            'messenger' => 'nullable|url:http,https|max:255',
            'contact_no' => ['nullable', 'max:50', 'regex:/^[0-9+()\-\s]+$/'],
        ], [
            'facebook.url' => 'The Facebook link must be a full address starting with https://',
            'messenger.url' => 'The Messenger link must be a full address starting with https://',
            'contact_no.regex' => 'The contact number can only have numbers, spaces, +, - and brackets.',
        ]);

        // an empty box removes that item from the footer
        foreach (Setting::CONTACT as $name) {
            Setting::updateOrCreate(['name' => $name], ['value' => $data[$name] ?? null]);
        }

        return redirect('/admin/contact')->with('success', 'Contact details saved. The public site footer is updated.');
    }

    // the photo or short video behind the banner at the top of the public home page
    public function saveBanner(Request $request): RedirectResponse
    {
        $request->validate([
            'banner_photo' => 'required|file|mimes:jpg,jpeg,png,webp,mp4,webm|max:'.(Setting::bannerLimitMb() * 1024),
        ], [
            'banner_photo.required' => 'Choose a photo or video first.',
            'banner_photo.mimes' => 'The banner must be a photo (JPG, PNG, WebP) or a video (MP4, WebM).',
            'banner_photo.max' => 'The banner file can be up to '.Setting::bannerLimitMb().' MB.',
            'banner_photo.uploaded' => 'The banner file can be up to '.Setting::bannerLimitMb().' MB.',
        ]);

        $this->deleteBannerFile();

        Setting::updateOrCreate(
            ['name' => 'banner_photo'],
            ['value' => $request->file('banner_photo')->store('banner', 'public')]
        );

        return redirect('/admin/contact')->with('success', 'Banner saved. It now shows at the top of the public home page.');
    }

    public function removeBanner(): RedirectResponse
    {
        $this->deleteBannerFile();

        Setting::where('name', 'banner_photo')->delete();

        return redirect('/admin/contact')->with('success', 'Banner removed. The banner is plain navy again.');
    }

    private function deleteBannerFile(): void
    {
        $path = Setting::where('name', 'banner_photo')->value('value');

        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
