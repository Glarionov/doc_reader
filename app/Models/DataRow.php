<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataRow extends Model
{
    use HasFactory;

    public $fillable = ['doc_id', 'name', 'date'];
}
