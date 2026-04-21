<?php

namespace App\Http\Requests\Agent;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AssetsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uuid'       => ['required', 'string', 'max:64'],

            // All hardware fields are nullable so partial reports are accepted.
            // Agents that can't determine a value should omit or send null.
            'cpu'        => ['nullable', 'string', 'max:255'],

            // RAM in megabytes: min 0, max 10 TB (10 485 760 MB)
            'ram'        => ['nullable', 'integer', 'min:0', 'max:10485760'],

            // Disk in gigabytes: min 0, max 1 PB (1 048 576 GB)
            'disk'       => ['nullable', 'integer', 'min:0', 'max:1048576'],

            'os_version' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'ram.max'  => 'RAM value exceeds the 10 TB limit (value must be in MB).',
            'disk.max' => 'Disk value exceeds the 1 PB limit (value must be in GB).',
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
