<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Popup extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function getImageAttribute($value)
    {
        return $value ? env("APP_STORAGE_URL", "/") . 'storage/' . $value : null;
    }
}
