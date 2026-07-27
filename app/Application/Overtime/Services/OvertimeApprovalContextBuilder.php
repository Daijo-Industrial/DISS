<?php

namespace App\Application\Overtime\Services;

use App\Domain\Overtime\Models\OvertimeForm;

final class OvertimeApprovalContextBuilder
{
    public function build(OvertimeForm $form): array
    {
        return [
            'department_id' => (int) $form->dept_id,
            'branch' => $form->branch?->value ?? '',
            'is_design' => (bool) $form->is_design,
        ];
    }
}
