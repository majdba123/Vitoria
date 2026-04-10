<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FooterSetting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'about_description',
        'facebook_url',
        'instagram_url',
        'twitter_url',
        'contact_email',
        'contact_address',
    ];

    /**
     * Get the single footer settings instance (singleton row).
     */
    public static function instance(): self
    {
        $setting = self::first();
        if ($setting) {
            return $setting;
        }

        return self::create([
            'about_description' => 'Your trusted online marketplace. Discover quality products from verified vendors, delivered to your doorstep.',
        ]);
    }
}
