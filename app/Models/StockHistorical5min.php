<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockHistorical5min extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'stock_historical_data_5min';
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
