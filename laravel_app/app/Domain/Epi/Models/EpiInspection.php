<?php

namespace App\Domain\Epi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EpiInspection extends Model
{
    protected $fillable = ['epi_id','inspected_at','inspected_by','status','remarks'];
    protected $casts = ['inspected_at' => 'date'];

    public function epi(): BelongsTo { return $this->belongsTo(Epi::class); }
}
