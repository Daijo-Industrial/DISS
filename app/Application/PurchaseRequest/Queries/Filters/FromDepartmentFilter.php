<?php

declare(strict_types=1);

namespace App\Application\PurchaseRequest\Queries\Filters;

use Illuminate\Database\Eloquent\Builder;

/**
 * Filter by origin department (from_department column).
 */
final class FromDepartmentFilter implements PurchaseRequestFilter
{
    public function __construct(private readonly string $fromDepartment) {}

    public function apply(Builder $query): void
    {
        $query->where('purchase_requests.from_department', $this->fromDepartment);
    }
}
