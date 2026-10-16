<?php
namespace App\Models\V3;
use Illuminate\Database\Eloquent\Model;
class Document extends Model {protected $table='v3_documents';protected $guarded=['id'];public $timestamps=true;protected $casts=['snapshot'=>'array'];}
