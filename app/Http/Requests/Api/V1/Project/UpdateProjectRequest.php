<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Project;

use App\Domain\Project\Enums\ProjectPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateProjectRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'requester_name' => ['sometimes', 'required', 'string', 'max:150'],
            'requester_department_id' => ['sometimes', 'required', 'integer', 'exists:departments,id'],
            'primary_pic_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'priority' => ['sometimes', 'required', new Enum(ProjectPriority::class)],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['sometimes', 'required', 'date'],
        ];
    }
}
