<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Project;

use Illuminate\Foundation\Http\FormRequest;

class ApproveClosureRequest extends FormRequest
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
            'comments' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
