<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Project;

use App\Domain\Project\Enums\ProjectStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class TransitionStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'target_stage' => ['required', new Enum(ProjectStage::class)],
            'is_override' => ['nullable', 'boolean'],
            'reason' => ['required_if:is_override,true', 'nullable', 'string', 'max:1000'],
        ];
    }
}
