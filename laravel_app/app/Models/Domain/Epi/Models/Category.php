<?php

namespace App\Models\Domain\Epi\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'epi_categories';
    protected $guarded = []; // adjust if you prefer fillable
}
