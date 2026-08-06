<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ComplianceDepartment extends Model
{
    use HasFactory;

    protected $table = 'compliance_departments';

    protected $fillable = [
        'name',
        'head_name',
        'code',
    ];

    public function requirementAssignments(): MorphMany
    {
        return $this->morphMany(RequirementAssignment::class, 'scope');
    }

    public function requirementUploads(): MorphMany
    {
        return $this->morphMany(RequirementUpload::class, 'scope');
    }
}
