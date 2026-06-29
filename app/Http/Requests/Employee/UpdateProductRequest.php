<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
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
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'icon' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
            'image' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
            'status' => ['sometimes', 'nullable', Rule::in(['pending', 'approved', 'rejected'])],
            'rejection_reason' => ['sometimes', 'nullable', 'string', 'max:2000', Rule::requiredIf(fn (): bool => $this->input('status') === 'rejected')],
        ];
    }
}
