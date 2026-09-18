<?php

namespace App\Policies;

use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Models\Invoice;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvoicePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can change the paid settlement status of an invoice.
     */
    public function changePaidStatus(User $user, Invoice|string|null $invoice = null): bool
    {
        return $user->hasRole('accounting-admin') || $user->can('invoice.settle');
    }
}
