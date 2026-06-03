<?php

namespace App\Http\Requests\Availability;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAvailabilityExceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('updateException', $this->route('exception'));
    }

    public function rules(): array
    {
        return [
            'date' => ['sometimes', 'date_format:Y-m-d'],
            'type' => ['sometimes', Rule::in(['blocked', 'custom'])],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'reason' => ['nullable', 'string', 'max:180'],
        ];
    }
}
