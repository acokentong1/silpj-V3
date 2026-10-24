<?php

namespace App\Models\V3;

use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    protected $table = 'v3_budgets';

    protected $guarded = ['id'];

    public $timestamps = true;
}
