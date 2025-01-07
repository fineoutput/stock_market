<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankHistorical5min extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'bank_historical_data_5min';
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
