<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ← was false, must be true!
    }

    public function rules(): array
    {
        return [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'phone'    => [
                'nullable',
                'string',
                'regex:/^(?:\+212|0)([ \-]?)(?:5|6|7)\d{8}$/',
            ],
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'                  => 'Le nom est obligatoire',
            'name.max'                       => 'Le nom ne doit pas dépasser 255 caractères',
            'email.required'                 => 'L\'adresse email est obligatoire',
            'email.email'                    => 'L\'adresse email n\'est pas valide',
            'email.unique'                   => 'Cette adresse email est déjà utilisée',
            'phone.regex'                    => 'Le numéro de téléphone marocain n\'est pas valide (ex: 0612345678 ou +212612345678)',
            'phone.unique'                   => 'Ce numéro de téléphone est déjà utilisé',
            'password.required'              => 'Le mot de passe est obligatoire',
            'password.min'                   => 'Le mot de passe doit contenir au moins 8 caractères',
            'password.confirmed'             => 'Les mots de passe ne correspondent pas',
            'password_confirmation.required' => 'Veuillez confirmer votre mot de passe',
        ];
    }
}
