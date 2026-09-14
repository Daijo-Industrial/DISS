<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchasingVendorClaim extends Model
{
    protected $table = 'purchasing_vendor_claim';

    public $timestamps = false;

    protected $guarded = ['id'];
}
