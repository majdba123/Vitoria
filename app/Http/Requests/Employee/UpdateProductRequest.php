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
            'name_ar' => ['sometimes', 'nullable', 'string', 'max:255'],
            'name_en' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
            'rejection_reason' => ['sometimes', 'nullable', 'string', 'max:2000', Rule::requiredIf(fn (): bool => $this->input('status') === 'rejected')],
        ];
    }
}
