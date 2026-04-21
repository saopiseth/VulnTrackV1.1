<?php

namespace App\Http\Requests\Agent;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SoftwareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uuid'     => ['required', 'string', 'max:64'],

            // software array is required but may be empty (means "nothing installed")
            'software' => ['required', 'array', 'max:10000'],

            // Per-item rules
            'software.*.name'         => ['required', 'string', 'max:500'],
            'software.*.version'      => ['required', 'string', 'max:100'],
            'software.*.installed_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'software.required'         => 'The software array is required (send an empty array to clear).',
            'software.max'              => 'The software list may not exceed 10,000 entries per request.',
            'software.*.name.required'  => 'Each software entry must include a name.',
            'software.*.name.max'       => 'Software name may not exceed 500 characters.',
            'software.*.version.required' => 'Each software entry must include a version.',
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
