<?php

namespace App\Http\Requests\Availability;

use App\Models\Availability;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAvailabilityExceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Availability::class);
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'type' => ['required', Rule::in(['blocked', 'custom'])],
            // For "blocked", times are optional: empty = whole-day block.
            // For "custom", both times are required (open window override).
            'start_time' => ['nullable', 'date_format:H:i', 'required_if:type,custom'],
            'end_time' => ['nullable', 'date_format:H:i', 'required_if:type,custom', 'after:start_time'],
            'reason' => ['nullable', 'string', 'max:180'],
        ];
    }
}
