<?php

namespace App\Domain\Epi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'employee_number','first_name','last_name','email','department','is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function assignments(): HasMany { return $this->hasMany(EpiAssignment::class); }
}
