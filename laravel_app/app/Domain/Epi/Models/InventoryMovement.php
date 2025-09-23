<?php

namespace App\Domain\Epi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    protected $fillable = ['epi_id','type','quantity','reason','performed_by','performed_at'];
    protected $casts = ['performed_at' => 'datetime'];

    public function epi(): BelongsTo { return $this->belongsTo(Epi::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class, 'performed_by'); }
}
