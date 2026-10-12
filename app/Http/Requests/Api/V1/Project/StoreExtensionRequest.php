<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Project;

use App\Domain\Project\Enums\ExtensionReasonCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreExtensionRequest extends FormRequest
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
            'new_due_date' => ['required', 'date'],
            'reason_category' => ['required', new Enum(ExtensionReasonCategory::class)],
            'reason_details' => ['required', 'string', 'max:2000'],
        ];
    }
}
