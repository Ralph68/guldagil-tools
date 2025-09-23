<?php

namespace App\Domain\Epi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Epi extends Model
{
    protected $fillable = [
        'epi_category_id','reference','label','size','brand',
        'serial_number','purchase_date','expiration_date',
        'stock_quantity','is_active',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'expiration_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo { return $this->belongsTo(EpiCategory::class, 'epi_category_id'); }
    public function assignments(): HasMany { return $this->hasMany(EpiAssignment::class); }
    public function inspections(): HasMany { return $this->hasMany(EpiInspection::class); }
    public function movements(): HasMany { return $this->hasMany(InventoryMovement::class); }
}
