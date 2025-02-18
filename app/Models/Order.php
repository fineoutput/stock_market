<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $table = 'tbl_order';
    protected $fillable = [
        'stock_name',
        'stock',
        'type',
        'timeframe',
        'buy_price',
        'order_price',
        'sl',
        'exit_price',
        'status',
        'start_time',
        'end_time',
        'qty',
        'profit_loss_status',
        'profit_loss_amt',
        'tradedprice',
        'tradedstarttime',
        'tradedendtime',
        'order_id',
        'exit_order_id',
        'tradedsellprice'
    ];
}
