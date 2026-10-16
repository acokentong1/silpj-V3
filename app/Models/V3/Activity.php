<?php
namespace App\Models\V3;
use Illuminate\Database\Eloquent\Model;
class Activity extends Model {protected $table='v3_activities';protected $guarded=['id'];public $timestamps=true;public function purchases(){return $this->hasMany(Purchase::class,'activity_id');} public function budget(){return $this->belongsTo(Budget::class,'budget_id');}}
