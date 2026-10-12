<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Project;

use App\Domain\Project\Enums\CheckpointStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateCheckpointRequest extends FormRequest
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
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sequence_order' => ['sometimes', 'required', 'integer', 'min:1'],
            'is_required' => ['sometimes', 'required', 'boolean'],
            'status' => ['sometimes', 'required', new Enum(CheckpointStatus::class)],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
