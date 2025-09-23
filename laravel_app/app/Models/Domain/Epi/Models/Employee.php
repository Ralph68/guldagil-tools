<?php

namespace App\Models\Domain\Epi\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $table = 'employees';
    protected $guarded = [];

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'employee_id');
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'employee_id');
    }
}
