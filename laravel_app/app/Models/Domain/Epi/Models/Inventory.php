<?php

namespace App\Models\Domain\Epi\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $table = 'inventories';
    protected $guarded = [];

    protected $casts = [
        'performed_at' => 'datetime',
    ];

    public function epi()
    {
        return $this->belongsTo(Epi::class, 'epi_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
