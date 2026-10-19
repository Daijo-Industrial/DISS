<?php

namespace App\Models;

use App\Models\InspectionForm\UsesInspectionConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InspectionJudgement extends Model
{
    use HasFactory, UsesInspectionConnection;

    protected $fillable = [
        'detail_inspection_report_document_number',
        'pass_quantity',
        'reject_quantity',
    ];
}
