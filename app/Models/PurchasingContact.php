<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchasingContact extends Model
{
    use HasFactory;

    protected $table = 'purchasing_contacts';

    public $timestamps = false;

    protected $guarded = ['id'];
}
