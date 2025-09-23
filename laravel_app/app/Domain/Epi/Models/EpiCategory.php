<?php

namespace App\Domain\Epi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EpiCategory extends Model
{
    protected $fillable = ['name','code','description'];

    public function epis(): HasMany
    {
        return $this->hasMany(Epi::class, 'epi_category_id');
    }
}
