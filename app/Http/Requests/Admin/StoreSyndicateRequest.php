<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use App\Models\Syndicate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class StoreSyndicateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $logo = $this->file('logo');

        if (! $logo instanceof UploadedFile || ! $logo->isValid()) {
            $this->files->remove('logo');
            $this->request->remove('logo');
        }
    }

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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30', 'unique:users,phone_number'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'type' => ['required', Rule::in([Category::TYPE_AGRICULTURE, Category::TYPE_VETERINARY])],
            'status' => ['required', Rule::in([Syndicate::STATUS_ACTIVE, Syndicate::STATUS_INACTIVE])],
            'logo' => ['sometimes', 'nullable', 'file', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'logo.file' => 'The uploaded file is invalid.',
            'logo.max' => 'The uploaded file may not be greater than 10 MB.',
        ];
    }
}
