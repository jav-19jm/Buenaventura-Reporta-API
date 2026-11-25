<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Insignia extends Model
{
    use HasUuids;

    protected $table = 'insignias';

    public $timestamps = false;

    protected $fillable = ['nombre', 'descripcion', 'icono', 'requisito_texto'];
}
