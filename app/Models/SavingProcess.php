<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavingProcess extends Model
{
    use HasFactory;

    public $fillable = ['code'];
}
