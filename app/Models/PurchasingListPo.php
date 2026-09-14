<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchasingListPo extends Model
{
    protected $table = 'purchasing_list_po';

    public $timestamps = false;

    protected $guarded = ['id'];
}
