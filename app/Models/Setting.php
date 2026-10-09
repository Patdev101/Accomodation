<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    // contact details shown in the footer of the public site; the admin fills these in
    const CONTACT = ['facebook', 'messenger', 'contact_no'];

    protected $primaryKey = 'name';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['name', 'value'];

    /**
     * Web address of the photo behind the banner at the top of the public home page,
     * or null when the admin has not uploaded one.
     */
    public static function bannerPhotoUrl(): ?string
    {
        $path = static::where('name', 'banner_photo')->value('value');

        return $path ? Storage::disk('public')->url($path) : null;
    }

    // true when the banner file is a video (MP4 or WebM) instead of a photo
    public static function bannerIsVideo(): bool
    {
        return in_array(strtolower(pathinfo((string) static::bannerPhotoUrl(), PATHINFO_EXTENSION)), ['mp4', 'webm']);
    }

    /**
     * The biggest banner file that can be uploaded, in MB: 50 MB for this site,
     * or less when PHP on this machine allows less (upload_max_filesize and post_max_size in php.ini).
     */
    public static function bannerLimitMb(): int
    {
        $megabytes = fn (string $setting): float => ini_parse_quantity(ini_get($setting) ?: '0') / 1048576;

        return (int) max(1, floor(min(50, $megabytes('upload_max_filesize'), $megabytes('post_max_size'))));
    }

    // the contact details that have been filled in, e.g. ['facebook' => 'https://...']
    public static function contact(): array
    {
        return static::whereIn('name', self::CONTACT)
            ->whereNotNull('value')
            ->pluck('value', 'name')
            ->all();
    }
}
