<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class RescheduleBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reschedule', $this->route('booking'));
    }

    public function rules(): array
    {
        return [
            'starts_at' => ['required', 'date', 'after:now'],
        ];
    }
}
