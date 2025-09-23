<?php

namespace App\Models\Domain\Epi\Models;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    protected $table = 'epi_assignments';
    protected $guarded = [];

    public function epi()
    {
        return $this->belongsTo(Epi::class, 'epi_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
