<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Public registration endpoint.
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            // Customers can self-register. Provider/admin accounts are created by an admin.
            'role' => ['nullable', Rule::in([User::ROLE_CUSTOMER])],
            'timezone' => ['nullable', 'string', 'max:64'],
        ];
    }
}
