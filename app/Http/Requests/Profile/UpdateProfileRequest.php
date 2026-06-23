<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Any authenticated user may edit their OWN profile. The controller
        // always targets $request->user(), so there is no id to tamper with.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32'],
            // 'timezone' rule validates against PHP's IANA timezone list,
            // so an invalid value (e.g. "Mars/Phobos") is rejected.
            'timezone' => ['sometimes', 'required', 'timezone'],
        ];
    }
}
