<?php

namespace App\Models\V3;

use Illuminate\Database\Eloquent\Model;

class Component extends Model
{
    protected $table = 'v3_components';

    protected $guarded = ['id'];

    public $timestamps = false;
}
