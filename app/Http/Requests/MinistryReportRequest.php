<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MinistryReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isMinistry() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'range' => ['required', 'string', Rule::in(['today', '7_days', '30_days', '90_days', 'this_year', 'custom'])],
            'locale' => ['required', 'string', Rule::in(['ar', 'en'])],
            'date_from' => ['nullable', 'date', 'required_if:range,custom'],
            'date_to' => ['nullable', 'date', 'required_if:range,custom', 'after_or_equal:date_from', 'before_or_equal:today'],
            'sector' => ['sometimes', 'string', Rule::in(['combined', 'agriculture', 'veterinary'])],
        ];
    }

    /** @return array{key: string, from: CarbonImmutable, to: CarbonImmutable} */
    public function period(): array
    {
        $key = (string) $this->validated('range');
        $today = CarbonImmutable::today();

        return match ($key) {
            'today' => ['key' => $key, 'from' => $today->startOfDay(), 'to' => $today->endOfDay()],
            '7_days' => ['key' => $key, 'from' => $today->subDays(6)->startOfDay(), 'to' => $today->endOfDay()],
            '90_days' => ['key' => $key, 'from' => $today->subDays(89)->startOfDay(), 'to' => $today->endOfDay()],
            'this_year' => ['key' => $key, 'from' => $today->startOfYear(), 'to' => $today->endOfDay()],
            'custom' => ['key' => $key, 'from' => CarbonImmutable::parse($this->validated('date_from'))->startOfDay(), 'to' => CarbonImmutable::parse($this->validated('date_to'))->endOfDay()],
            default => ['key' => '30_days', 'from' => $today->subDays(29)->startOfDay(), 'to' => $today->endOfDay()],
        };
    }
}
