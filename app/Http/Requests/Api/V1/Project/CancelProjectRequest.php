<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Project;

use App\Domain\Project\Enums\CancellationCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CancelProjectRequest extends FormRequest
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
            'reason_category' => ['required', new Enum(CancellationCategory::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
