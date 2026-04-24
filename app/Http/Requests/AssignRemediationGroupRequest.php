<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignRemediationGroupRequest extends FormRequest
{
    /**
     * Only users who can manage the assessment may assign groups.
     * The 'manage' gate is checked against the VulnAssessment route model.
     */
    public function authorize(): bool
    {
        $assessment = $this->route('vulnAssessment');

        return $assessment && $this->user()?->can('manage', $assessment);
    }

    public function rules(): array
    {
        return [
            'assigned_group_id' => ['nullable', 'integer', 'exists:user_groups,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'assigned_group_id.exists' => 'The selected user group does not exist.',
        ];
    }

    /**
     * Strip every field except assigned_group_id before validation runs.
     * This means even a crafted request cannot slip status or other fields
     * through — they are silently removed at the framework boundary.
     */
    protected function prepareForValidation(): void
    {
        $this->replace(
            $this->only(['assigned_group_id'])
        );
    }
}
