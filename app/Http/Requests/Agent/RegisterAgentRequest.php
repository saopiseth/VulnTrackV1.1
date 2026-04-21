<?php

namespace App\Http\Requests\Agent;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint — no prior auth needed
    }

    public function rules(): array
    {
        return [
            'uuid'       => ['required', 'string', 'max:64'],
            'hostname'   => ['required', 'string', 'max:255'],
            'ip_address' => ['required', 'ip'],
            'os'         => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'uuid.required'       => 'A unique agent UUID is required.',
            'hostname.required'   => 'The agent hostname is required.',
            'ip_address.required' => 'The agent IP address is required.',
            'ip_address.ip'       => 'The IP address must be a valid IPv4 or IPv6 address.',
        ];
    }

    /** Return JSON errors instead of a redirect for API clients */
    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
