<?php

namespace App\Domain\Epi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EpiAssignment extends Model
{
    protected $fillable = [
        'epi_id','employee_id','assigned_at','returned_at',
        'condition_on_issue','condition_on_return','notes',
    ];

    protected $casts = [
        'assigned_at' => 'date',
        'returned_at' => 'date',
    ];

    public function epi(): BelongsTo { return $this->belongsTo(Epi::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}
