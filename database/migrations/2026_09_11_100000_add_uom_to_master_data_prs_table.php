<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ponytail: add uom column if not already present
        if (! Schema::hasColumn('master_data_prs', 'uom')) {
            Schema::table('master_data_prs', function (Blueprint $table) {
                $table->string('uom')->nullable()->after('currency');
            });
        }

        // ponytail: backfill uom from historical approved PR items
        $approvedItems = DB::table('detail_purchase_requests as d')
            ->join('purchase_requests as pr', 'pr.id', '=', 'd.purchase_request_id')
            ->leftJoin('approval_requests as ar', function ($join) {
                $join->on('ar.approvable_id', '=', 'pr.id')
                    ->whereIn('ar.approvable_type', [
                        'App\\Models\\PurchaseRequest',
                        'PurchaseRequest',
                        'purchase_requests',
                    ]);
            })
            ->where(function ($q) {
                $q->where('ar.status', 'APPROVED')
                    ->orWhere('d.is_approve', 1);
            })
            ->where(function ($q) {
                $q->whereNull('pr.is_cancel')->orWhere('pr.is_cancel', 0);
            })
            ->whereNotNull('d.uom')
            ->where('d.uom', '!=', '')
            ->orderBy('d.id', 'asc') // later entries will overwrite earlier ones in array
            ->select('d.item_name', 'd.uom')
            ->get();

        // Key by item_name so latest entry wins
        $uomMap = [];
        foreach ($approvedItems as $row) {
            $name = trim((string) $row->item_name);
            $uom = trim((string) $row->uom);
            if ($name !== '' && $uom !== '') {
                $uomMap[$name] = $uom;
            }
        }

        foreach ($uomMap as $name => $uom) {
            DB::table('master_data_prs')
                ->where('name', $name)
                ->where(function ($q) {
                    $q->whereNull('uom')->orWhere('uom', '');
                })
                ->update(['uom' => $uom]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('master_data_prs', 'uom')) {
            Schema::table('master_data_prs', function (Blueprint $table) {
                $table->dropColumn('uom');
            });
        }
    }
};
