<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Project;

use App\Domain\Project\Enums\ProjectStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class RejectClosureRequest extends FormRequest
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
            'comments' => ['required', 'string', 'max:1000'],
            'return_stage' => ['nullable', new Enum(ProjectStage::class)],
        ];
    }
}
