<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'       => 'required|email',
            'password'    => 'required|string',
            'device_id'   => 'required|string',
            'device_name' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'     => 'L\'adresse email est obligatoire',
            'email.email'        => 'L\'adresse email n\'est pas valide',
            'password.required'  => 'Le mot de passe est obligatoire',
            'device_id.required' => 'L\'identifiant de l\'appareil est obligatoire',
        ];
    }
}