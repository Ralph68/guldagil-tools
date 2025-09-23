<?php

namespace App\Models\Domain\Epi\Models;

use Illuminate\Database\Eloquent\Model;

class Epi extends Model
{
    protected $table = 'epis';   // change if your table name differs
    protected $guarded = [];

    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'epi_id');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'epi_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
