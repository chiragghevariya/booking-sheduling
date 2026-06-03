<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class DeclineBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('decline', $this->route('booking'));
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:300'],
        ];
    }
}
