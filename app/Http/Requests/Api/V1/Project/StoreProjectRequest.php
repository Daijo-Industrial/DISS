<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Project;

use App\Domain\Project\Enums\ProjectPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreProjectRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'requester_name' => ['required', 'string', 'max:150'],
            'requester_department_id' => ['required', 'integer', 'exists:departments,id'],
            'primary_pic_id' => ['required', 'integer', 'exists:users,id'],
            'priority' => ['nullable', new Enum(ProjectPriority::class)],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:start_date'],
        ];
    }
}
