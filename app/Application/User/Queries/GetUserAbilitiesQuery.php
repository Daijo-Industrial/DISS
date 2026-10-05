<?php

namespace App\Application\User\Queries;

use App\Infrastructure\Common\PermissionRegistry;
use App\Infrastructure\Persistence\Eloquent\Models\User;

class GetUserAbilitiesQuery
{
    /**
     * Get system ability matrix and metadata for inspection.
     */
    public function execute(User $user): array
    {
        $modules = PermissionRegistry::getModules();
        $signaturePerms = array_flip(PermissionRegistry::getSignatureRequiredPermissions());
        $isSuperAdmin = $user->hasRole('super-admin');

        $groupedModules = [];
        $totalPermCount = 0;

        foreach ($modules as $moduleName => $data) {
            $permList = [];
            foreach ($data['permissions'] ?? [] as $perm) {
                $totalPermCount++;
                $permList[] = [
                    'name' => $perm,
                    'label' => $this->formatPermissionLabel($perm),
                    'requires_signature' => isset($signaturePerms[$perm]),
                    'is_admin_action' => str_contains($perm, 'admin') || str_contains($perm, 'delete-forever'),
                ];
            }

            $groupedModules[] = [
                'name' => $moduleName,
                'permissions' => $permList,
                'roles' => array_keys($data['roles'] ?? []),
                'permissions_count' => count($permList),
            ];
        }

        return [
            'is_super_admin' => $isSuperAdmin,
            'user_roles' => $user->getRoleNames()->toArray(),
            'total_permissions_count' => $totalPermCount,
            'signature_permissions_count' => count($signaturePerms),
            'modules' => $groupedModules,
        ];
    }

    /**
     * Format permission key to human-readable text.
     */
    private function formatPermissionLabel(string $permission): string
    {
        // e.g. "pr.batch-approve" -> "PR: Batch Approve"
        // e.g. "fleet.inspect" -> "Fleet: Inspect"
        $parts = explode('.', $permission, 2);
        if (count($parts) === 2) {
            $prefix = strtoupper($parts[0]);
            $action = ucwords(str_replace(['-', '_'], ' ', $parts[1]));

            return "{$prefix} • {$action}";
        }

        return ucwords(str_replace(['-', '_', '.'], ' ', $permission));
    }
}
