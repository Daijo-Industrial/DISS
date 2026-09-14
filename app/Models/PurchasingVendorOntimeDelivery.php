<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchasingVendorOntimeDelivery extends Model
{
    protected $table = 'purchasing_vendor_ontime_delivery';

    public $timestamps = false;

    protected $guarded = ['id'];
}
