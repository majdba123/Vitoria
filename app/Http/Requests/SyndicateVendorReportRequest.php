<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class SyndicateVendorReportRequest extends VendorAnalyticsRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'range' => ['required', 'string', Rule::in(['today', '7_days', '30_days', '90_days', 'this_year', 'custom'])],
            'locale' => ['required', 'string', Rule::in(['ar', 'en'])],
            'date_from' => ['nullable', 'date', 'required_if:range,custom'],
            'date_to' => ['nullable', 'date', 'required_if:range,custom', 'after_or_equal:date_from', 'before_or_equal:today'],
        ];
    }
}
