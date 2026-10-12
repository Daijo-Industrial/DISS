<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Project;

use Illuminate\Foundation\Http\FormRequest;

class ResumeProjectRequest extends FormRequest
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
            'extend_due_date' => ['required', 'boolean'],
        ];
    }
}
