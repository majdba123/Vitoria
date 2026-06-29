<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\NormalizesDateTimeInputs;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCouponRequest extends FormRequest
{
    use NormalizesDateTimeInputs;

    protected function prepareForValidation(): void
    {
        $this->normalizeDateTimeInputs(['starts_at', 'ends_at']);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'nullable', 'string', 'max:60'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'discount_type' => ['sometimes', 'nullable', 'string', 'in:percentage,fixed'],
            'discount_value' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
