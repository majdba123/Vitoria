<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFooterSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'about_description' => ['nullable', 'string', 'max:2000'],
            'facebook_url' => ['nullable', 'string', 'url', 'max:500'],
            'instagram_url' => ['nullable', 'string', 'url', 'max:500'],
            'twitter_url' => ['nullable', 'string', 'url', 'max:500'],
            'contact_email' => ['nullable', 'string', 'email', 'max:255'],
            'contact_address' => ['nullable', 'string', 'max:500'],
        ];
    }
}
