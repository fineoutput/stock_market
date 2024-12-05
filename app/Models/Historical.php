<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Historical extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'historical_data';
    protected $fillable = [
        'open',
        'close',
        'high',
        'low',
        'open_status',
        'tred_option',
        'date'
    ];
}
