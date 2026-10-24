<?php

namespace App\Models\V3;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $casts = ['submitted_at' => 'datetime'];

    protected $table = 'v3_purchases';

    protected $guarded = ['id'];

    public function items()
    {
        return $this->hasMany(Item::class, 'purchase_id')->orderBy('urutan');
    }

    public function components()
    {
        return $this->hasMany(Component::class, 'purchase_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'purchase_id')->orderByDesc('id');
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function editable()
    {
        return in_array($this->status, ['draf', 'revisi']);
    }
}
