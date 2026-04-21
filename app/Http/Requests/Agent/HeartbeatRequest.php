<?php

namespace App\Http\Requests\Agent;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class HeartbeatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth is handled by the VerifyAgentToken middleware
    }

    public function rules(): array
    {
        return [
            'uuid'       => ['required', 'string', 'max:64'],
            // IP update is optional — agents behind NAT/DHCP may change address
            'ip_address' => ['nullable', 'ip'],
        ];
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
