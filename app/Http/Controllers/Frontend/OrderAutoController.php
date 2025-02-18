<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Redirect;
use Laravel\Sanctum\PersonalAccessToken;
use DateTime;
use Illuminate\Support\Facades\Http; // For making HTTP requests
use Illuminate\Support\Facades\DB; // For database operations
use App\adminmodel\FyersModal;
use App\Models\Order;
use App\Models\Historical;
use App\Models\Historical5min;
use App\Models\BankHistorical5min;
use App\Models\StockHistorical5min;
use DateTimeZone;

class OrderAutoController extends Controller
{


public function createOrder_CE()
    {
          // Your function logic here
        //   \Log::info('Task executed at anay CE ' . now());
        //   exit;
        //check if order exists or not
        $runningOrder = Order::where('status', 0)->where('type', 1)->where('timeframe', 2)->first();

        if($runningOrder){
            // ORDER IS IS PROCESS
            //CHECK LAST OPEN TO 
            $secondLast = Historical::wherenull('deleted_at')->where('tred_option', $runningOrder->stock)->orderBy('id', 'DESC')->skip(1)
            ->take(1)->first();

            if($secondLast){
                if($secondLast->id == $runningOrder->historic_id){
                    \Log::info('CE- SAME HISTORIC ID');
                    //NO UPDATE TO BE DONE IN ORDER
                    $iterations = 18;
                    $exit_created = 0;
                    for ($i = 0; $i < $iterations; $i++) {
                        $live_price_Stock = $this->getPriceData($runningOrder->stock_name);
                        \Log::info('CE- Live - SL ' . $live_price_Stock.','.$runningOrder->sl);
                        if($live_price_Stock < $runningOrder->sl){
                            if($exit_created == 0){
                                //  CLOSED THE TRADE
                           $pl2 = abs($runningOrder->order_price-$live_price_Stock);
                           $pl = $runningOrder->order_price-$live_price_Stock;
                           if($pl >0){
                            $profit_loss_status = 0;
                           }
                           else{
                            $profit_loss_status = 1;
                           }

                           $symbolData = DB::table('fyers')->orderBy('id', 'desc')->first();
                           if($symbolData->trading_type == 2){         
                           $this->close_positions($runningOrder->stock_name);
                           }

                                DB::table('tbl_order')
                                ->where('id', $runningOrder->id) 
                                ->update([
                                    'exit_price' => $live_price_Stock,
                                    'status' => 1, //complete
                                    'end_time' => now(),
                                    'profit_loss_status' => $profit_loss_status,
                                    'profit_loss_amt' => $pl2*$runningOrder->qty
                                ]);
                                $exit_created = 1;
                            }
                        } // IF END 

                    // Wait for 3 seconds before the next iteration
                    sleep(3);
                    } //FOR LOOP END

                }
                else{
                    //UPDATE SL AS HISTORIC ID IS CHANGED
                    $status_modify ='';
                    $fyer_new_Order_id = '';
                    \Log::info('CE(5MIN)- UPDATE SL AND HISTORIC ID AS HISTORIC ID IS CHANGED');
                    if($secondLast->open_status == 1){
                        $sl = $secondLast->close;
                    }
                    else{
                        $sl = $secondLast->open;
                    }

                    $symbolData = DB::table('fyers')->orderBy('id', 'desc')->first();
                    if($symbolData->trading_type == 2){ 
                    $modify_fyer = $this->modify_order($runningOrder->exit_order_id,$sl,$runningOrder->qty);
                    $modify_fyer2 = json_decode($modify_fyer->getContent(), true);
                    $status_modify = $modify_fyer2['status'];
                    if($status_modify == 200){
                        $fyer_new_Order_id = $modify_fyer2['orderID'];
                    }
                    }

                    DB::table('tbl_order')
                    ->where('id', $runningOrder->id) 
                    ->update([
                        'historic_id' => $secondLast->id,
                        'exit_order_id' => $fyer_new_Order_id,
                        'sl' => $sl,
                    ]);
                    \Log::info('CE(5MIN)- SL UPDATED FROM ORDERS');

                    //CHECK CURRENT PRICE AND EXIT THE TRADE
                    $iterations = 18;
                    $exit_created = 0;
                    for ($i = 0; $i < $iterations; $i++) {
                        $live_price_Stock = $this->getPriceData($runningOrder->stock_name);
                        if($live_price_Stock < $runningOrder->sl){
                            if($exit_created == 0){
                                //  CLOSED THE TRADE
                           $pl = $runningOrder->order_price-$live_price_Stock;
                           $pl2 = abs($runningOrder->order_price-$live_price_Stock);
                           if($pl >0){
                            $profit_loss_status = 0;
                           }
                           else{
                            $profit_loss_status = 1;
                           }
                           if($symbolData->trading_type == 2){         
                            $this->close_positions($runningOrder->stock_name);
                            }         

                                DB::table('tbl_order')
                                ->where('id', $runningOrder->id) 
                                ->update([
                                    'exit_price' => $live_price_Stock,
                                    'status' => 1, //complete
                                    'end_time' => now(),
                                    'profit_loss_status' => $profit_loss_status,
                                    'profit_loss_amt' => $pl2*$runningOrder->qty,
                                ]);
                                $exit_created = 1;
                            }
                        }

 // Wait for 3 seconds before the next iteration
 sleep(3);
                    } //FOR END

                } //ELSE END

            } // IF NO SECOND LAST DATA


        } //ORDER NOT RUNNING
        else{
            //CREATE NEW ORDER
            \Log::info('CE-NO ORDER RUNNING');
            $entry = 0;
            // GET NIFTY VALUE IF NIFTY IS POSITIVE OR NEGATIVE
            $nifty = $this->getPriceData('nifty');
            $nifty_current_type = $this->nifty_current(60);
           
            if($nifty_current_type){

                $symbolData = DB::table('fyers')->orderBy('id', 'desc')->first();
                if($nifty_current_type == 1){
                    // NIFTY IS GREEN/POSITIVE
                    \Log::info('CE(5MIN)-NIFTY CURRENTLY GREEN ');
                    $nifty_status = 1;
                    $symbol = $symbolData->option_ce;
                    $qty = $symbolData->lots * $symbolData->lots_size;
                    // \Log::info('NIFTY TRADING GREEN _ CE SIDE');
                }
                else{
                    // NIFTY IS RED/NEGATIVE
                    \Log::info('CE(5MIN)-NIFTY CURRENTLY RED SO EXIT');
                    $nifty_status = 2;
                    $symbol = $symbolData->option_pe;
                    exit;
                    // \Log::info('NIFTY TRADING RED _ PE SIDE');
                }

                if(empty($symbol)){
                    \Log::info('CE(5MIN)-SYMBOL NOT FOUND');
                    exit;
                }  

            //CHECK LAST OPEN TO 
            $secondLast = Historical::wherenull('deleted_at')->where('tred_option', $nifty_status)->orderBy('id', 'DESC')->skip(1)
            ->take(1)->first();
            if($secondLast->open_status == 1){

                    $last_open = $secondLast->open;
                    $last_close = $secondLast->close;
                    $iterations = 18; // Set the number of times the loop should run

                    for ($i = 0; $i < $iterations; $i++) {
                        $live_price_Stock = $this->getPriceData($symbol);
                        \Log::info('CE- Live - Open ' . $live_price_Stock.','.$last_open);
                        if($live_price_Stock > $last_open){
                            if($entry == 0){
                                $tradedTime = '';
                                $tradedPrice = '';
                                $order_ID = '';
                                $exit_order_ID = '';
                                if($symbolData->trading_type == 2){
                                    //place order on live
                                    $order_placed = $this->place_order($symbol,$qty);
                                       // Decode the JSON response
                                     $order_placed2 = json_decode($order_placed->getContent(), true);
     
                                         // Access specific fields
                                         $status = $order_placed2['status'];
                                         if($status == 200){
                                             $message = $order_placed2['message'];
                                             $tradedTime = $order_placed2['tradedTime'];
                                             $tradedPrice = $order_placed2['tradedPrice'];
                                             $order_ID = $order_placed2['orderID'];
                                             
     
                                             //create SL exit order 
                                            $exit_order_fyer =  $this->exit_order_create($symbol,$qty,$last_close,2);
                                            $exit_order_fyer2 = json_decode($exit_order_fyer->getContent(), true);
                                            $exis_status = $exit_order_fyer2['status'];
                                            if($exis_status == 200){
                                             \Log::info('CE(5MIN)- EXIT_ORDER_ID' . $exit_order_fyer2['orderID']);
                                             $exit_order_ID = $exit_order_fyer2['orderID'];
                                            }
     
                                         }
                                         else{
                                             $tradedTime = '';
                                             $tradedPrice = '';
                                             $order_ID = '';
                                         }
                                        
                                }  
                                \Log::info('CE(5MIN)- d1 ' . $symbol);        
                           $order = Order::create([
                            'stock_name' => $symbol,
                            'stock' => $nifty_status,
                            'type' => 1,
                            'timeframe' => 2,
                            'buy_price' => $last_open,
                            'order_price' => $live_price_Stock,
                            'sl' => $last_close,
                            'exit_price' => 0,
                            'status' => 0, //pending
                            'start_time' => now(),
                            'end_time' => "",
                            'qty' => $qty,
                            'profit_loss_status' => 0,
                            'profit_loss_amt' => 0,
                            'historic_id' => $secondLast->id,
                            'tradedprice' => $tradedPrice,
                            'tradedstarttime'=> $tradedTime,
                            'exit_order_id'=> $exit_order_ID,
                            'order_id'=> $order_ID,
                            'created_at' => now()
                        ]);
                        $OrderId = $order->id; // Retrieve the insert ID
                        $original_buy_price = $order->order_price;
                        $original_qty = $order->qty;
                           $entry = 1;
                            }
                            //   exit;
                        }
                        if($live_price_Stock < $last_close){
                            \Log::info('CE(5MIN)- CURRENT PRICE LOWER THAN LAST CLOSE, Exit Created at ' . now());
                         if($entry == 1){
                                        // Update data into database
                           $pl = $original_buy_price-$live_price_Stock;
                           $pl2 = abs($original_buy_price-$live_price_Stock);
                           if($pl >0){
                            $profit_loss_status = 0;
                           }
                           else{
                            $profit_loss_status = 1;
                           }  
                           $symbolData = DB::table('fyers')->orderBy('id', 'desc')->first();
                           if($symbolData->trading_type == 2){         
                           $this->close_positions($symbol);
                           }       
                                DB::table('tbl_order')
                                ->where('id', $OrderId) 
                                ->update([
                                    'exit_price' => $live_price_Stock,
                                    'status' => 1, //complete
                                    'end_time' => now(),
                                    'profit_loss_status' => $profit_loss_status,
                                    'profit_loss_amt' => $pl2*$original_qty
                                ]);
                         }
                             //   exit;
                         }
                        


    // Wait for 3 seconds before the next iteration
            sleep(3);
                    }
            } // SECOND LAST CANDLE NOT RED
            else{
                \Log::info('CE -SECOND LAST CANDLE NOT RED');
            }

            }
            else{
                \Log::info('CE(5MIN)- NOT GETTING nifty_current_type');
            }
        }


    }

    public function createOrder_PE()
    {

        //check if order exists or not
        $runningOrder = Order::where('status', 0)->where('type', 2)->where('timeframe', 2)->first();
    

        if($runningOrder){
            // ORDER IS IS PROCESS
            //CHECK LAST OPEN TO 
            $secondLast = Historical::wherenull('deleted_at')->where('tred_option', $runningOrder->stock)->orderBy('id', 'DESC')->skip(1)
            ->take(1)->first();

            if($secondLast){
                if($secondLast->id == $runningOrder->historic_id){
                    \Log::info('PE- SAME HISTORIC ID');
                    //NO UPDATE TO BE DONE IN ORDER
                    $iterations = 18;
                    $exit_created = 0;
                    for ($i = 0; $i < $iterations; $i++) {
                        $live_price_Stock = $this->getPriceData($runningOrder->stock_name);
                        \Log::info('PE- Live - SL ' . $live_price_Stock.','.$runningOrder->sl);
                        if($live_price_Stock < $runningOrder->sl){
                            if($exit_created == 0)  { 
                                //  CLOSED THE TRADE
                           $pl = $runningOrder->order_price-$live_price_Stock;
                           if($pl >0){
                            $profit_loss_status = 0;
                           }
                           else{
                            $profit_loss_status = 1;
                           }     

                           $symbolData = DB::table('fyers')->orderBy('id', 'desc')->first();
                           if($symbolData->trading_type == 2){         
                           $this->close_positions($runningOrder->stock_name);
                           }         
                                DB::table('tbl_order')
                                ->where('id', $runningOrder->id) 
                                ->update([
                                    'exit_price' => $live_price_Stock,
                                    'status' => 1, //complete
                                    'end_time' => now(),
                                    'profit_loss_status' => $profit_loss_status,
                                    'profit_loss_amt' => $pl*$runningOrder->qty,
                                    'created_at' => now()
                                ]);
                                $exit_created = 1;
                            }

                        } // IF END 

                    // Wait for 3 seconds before the next iteration
                    sleep(3);
                    } //FOR LOOP END

                }
                else{
                    //UPDATE SL AS HISTORIC ID IS CHANGED
                    $status_modify ='';
                    $fyer_new_Order_id = '';
                    \Log::info('PE(5MIN)- UPDATE SL AND HISTORIC ID AS HISTORIC ID IS CHANGED');

                    if($secondLast->open_status == 1){
                        $sl = $secondLast->close;
                    }
                    else{
                        $sl = $secondLast->open;
                    }

                    $symbolData = DB::table('fyers')->orderBy('id', 'desc')->first();
                    if($symbolData->trading_type == 2){ 
                    $modify_fyer = $this->modify_order($runningOrder->exit_order_id,$sl,$runningOrder->qty);
                    $modify_fyer2 = json_decode($modify_fyer->getContent(), true);
                    $status_modify = $modify_fyer2['status'];
                    if($status_modify == 200){
                        $fyer_new_Order_id = $modify_fyer2['orderID'];
                    }
                    }


                    DB::table('tbl_order')
                    ->where('id', $runningOrder->id) 
                    ->update([
                        'historic_id' => $secondLast->id,
                        'sl' => $sl,
                        'exit_order_id'=> $fyer_new_Order_id,
                    ]);

                    \Log::info('PE(5MIN)- SL UPDATED FROM ORDERS');

                    //CHECK CURRENT PRICE AND EXIT THE TRADE
                    $iterations = 18;
                    $exit_created = 0;
                    for ($i = 0; $i < $iterations; $i++) {
                        $live_price_Stock = $this->getPriceData($runningOrder->stock_name);
                        if($live_price_Stock < $runningOrder->sl){
                            if($exit_created == 0){
                                //  CLOSED THE TRADE
                           $pl = $runningOrder->order_price-$live_price_Stock;
                           $pl2 = abs($runningOrder->order_price-$live_price_Stock);
                           if($pl >0){
                            $profit_loss_status = 0;
                           }
                           else{
                            $profit_loss_status = 1;
                           }  
                           if($symbolData->trading_type == 2){         
                            $this->close_positions($runningOrder->stock_name);
                            }         
                                DB::table('tbl_order')
                                ->where('id', $runningOrder->id) 
                                ->update([
                                    'exit_price' => $live_price_Stock,
                                    'status' => 1, //complete
                                    'end_time' => now(),
                                    'profit_loss_status' => $profit_loss_status,
                                    'profit_loss_amt' => $pl2*$runningOrder->qty,
                                ]);
                            }
                        }

 // Wait for 3 seconds before the next iteration
 sleep(3);
                    } //FOR END

                } //ELSE END

            } // IF NO SECOND LAST DATA


        } //ORDER NOT RUNNING
        else{
            //CREATE NEW ORDER
            \Log::info('PE-NO ORDER RUNNING');
            $entry = 0;
            // GET NIFTY VALUE IF NIFTY IS POSITIVE OR NEGATIVE
            $nifty_current_type = $this->nifty_current(60);
           
            if($nifty_current_type){

                $symbolData = DB::table('fyers')->orderBy('id', 'desc')->first();
                if($nifty_current_type == 1){
                    \Log::info('PE- NIFTY CURRENTLY GREEN SO EXIT');
                    // NIFTY IS GREEN/POSITIVE
                    $nifty_status = 1;
                    $symbol = $symbolData->option_ce;
                    // \Log::info('NIFTY TRADING GREEN _ CE SIDE');
                    exit;
                }
                else{
                    // NIFTY IS RED/NEGATIVE
                    \Log::info('PE- NIFTY CURRENTLY RED - ENTRY');
                    $nifty_status = 2;
                    $symbol = $symbolData->option_pe;
                    $qty = $symbolData->lots * $symbolData->lots_size;
                    // \Log::info('NIFTY TRADING RED _ PE SIDE');
                }

                if(empty($symbol)){
                    \Log::info('PE(5MIN)-SYMBOL NOT FOUND');
                    exit;
                }   

            //CHECK LAST OPEN TO 
            $secondLast = Historical::wherenull('deleted_at')->where('tred_option', $nifty_status)->orderBy('id', 'DESC')->skip(1)
            ->take(1)->first();
            if($secondLast->open_status == 1){

                    $last_open = $secondLast->open;
                    $last_close = $secondLast->close;
                    $iterations = 18; // Set the number of times the loop should run
                    $exit_created = 0;
                    for ($i = 0; $i < $iterations; $i++) {
                        $live_price_Stock = $this->getPriceData($symbol);
                        \Log::info('PE- Live - Open ' . $live_price_Stock.','.$last_open);
                        if($live_price_Stock > $last_open){
                            
                            if($entry == 0){
                                $tradedTime = '';
                                $tradedPrice = '';
                                $order_ID = '';
                                $exit_order_ID = '';
                            if($symbolData->trading_type == 2){
                                //place order on live
                                $order_placed = $this->place_order($symbol,$qty);
                                    // Decode the JSON response
                                    $order_placed2 = json_decode($order_placed->getContent(), true);
    
                                        // Access specific fields
                                        $status = $order_placed2['status'];
                                        if($status == 200){
                                            $message = $order_placed2['message'];
                                            $tradedTime = $order_placed2['tradedTime'];
                                            $tradedPrice = $order_placed2['tradedPrice'];
                                            $order_ID = $order_placed2['orderID'];
    
                                            $exit_order_fyer =  $this->exit_order_create($symbol,$qty,$last_close,2);
                                            $exit_order_fyer2 = json_decode($exit_order_fyer->getContent(), true);
                                            $exis_status = $exit_order_fyer2['status'];
                                            if($exis_status == 200){
                                                \Log::info('PE(5MIN)- EXIT_ORDER_ID' . $exit_order_fyer2['orderID']);
                                            $exit_order_ID = $exit_order_fyer2['orderID'];
                                            }
                                        }
                                        else{
                                            $tradedTime = '';
                                            $tradedPrice = '';
                                            $order_ID = '';
                                        }
                                    
                            }     
                           \Log::info('PE- d1 ' . $symbol);
                           $order = Order::create([
                            'stock_name' => $symbol,
                            'stock' => $nifty_status,
                            'type' => 2,
                            'timeframe' => 2,
                            'buy_price' => $last_open,
                            'order_price' => $live_price_Stock,
                            'sl' => $last_close,
                            'exit_price' => 0,
                            'status' => 0, //pending
                            'start_time' => now(),
                            'end_time' => "",
                            'qty' => $qty,
                            'profit_loss_status' => 0,
                            'profit_loss_amt' => 0,
                            'historic_id' => $secondLast->id,
                            'tradedprice' => $tradedPrice,
                            'tradedstarttime'=> $tradedTime,
                            'order_id'=> $order_ID,
                            'exit_order_id'=> $exit_order_ID,
                            'created_at' => now()
                        ]);
                        $OrderId = $order->id; // Retrieve the insert ID
                        $original_buy_price = $order->order_price;
                        $original_qty = $order->qty;
                           $entry = 1;
                            }
                            //   exit;
                        }
                        if($live_price_Stock < $last_close){
                            \Log::info('PE- CURRENT PRICE LOWER THAN LAST CLOSE, Exit Created at ' . now());
                         if($entry == 1 && $exit_created == 0){
                                        // Update data into database
                           $pl = $original_buy_price-$live_price_Stock;
                           $pl2 = abs($runningOrder->order_price-$live_price_Stock);
                           if($pl >0){
                            $profit_loss_status = 0;
                           }
                           else{
                            $profit_loss_status = 1;
                           }         
                                DB::table('tbl_order')
                                ->where('id', $OrderId) 
                                ->update([
                                    'exit_price' => $live_price_Stock,
                                    'status' => 1, //complete
                                    'end_time' => now(),
                                    'profit_loss_status' => $profit_loss_status,
                                    'profit_loss_amt' => $pl2*$original_qty
                                ]);
                         }
                             //   exit;
                         }
                        


    // Wait for 3 seconds before the next iteration
            sleep(3);
                    }
            } // SECOND LAST CANDLE NOT RED
            else{
                \Log::info('PE- SECOND LAST CANDLE NOT RED');
            }

            }
            else{
                \Log::info('PE(5MIN)- NOT GETTING nifty_current_type');
            }
        }


    }

// 5min ORDER PLACING START
//stock - 1
public function createOrder_CE_5min()
    {
          // Your function logic here
        //   \Log::info('Task executed at anay CE ' . now());
        //   exit;
        //check if order exists or not
        $runningOrder = Order::where('status', 0)->where('type', 1)->where('timeframe', 1)->first();

        if($runningOrder){
            // ORDER IS IS PROCESS
            //CHECK LAST OPEN TO 
            $secondLast = Historical5min::wherenull('deleted_at')->where('tred_option', $runningOrder->stock)->orderBy('id', 'DESC')->skip(1)
            ->take(1)->first();

            if($secondLast){
                if($secondLast->id == $runningOrder->historic_id){
                    \Log::info('CE(5MIN)- SAME HISTORIC ID');
                    //NO UPDATE TO BE DONE IN ORDER
                    $iterations = 18;
                    $exit_created = 0;
                    for ($i = 0; $i < $iterations; $i++) {
                        $live_price_Stock = $this->getPriceData($runningOrder->stock_name);
                        \Log::info('CE(5MIN)- Live - SL ' . $live_price_Stock.','.$runningOrder->sl);
                        if($live_price_Stock < $runningOrder->sl){
                            if($exit_created == 0){
                                //  CLOSED THE TRADE
                           $pl2 = abs($runningOrder->order_price-$live_price_Stock);
                           $pl = $runningOrder->order_price-$live_price_Stock;
                           if($pl>0){
                            $profit_loss_status = 0;
                           }
                           else{
                            $profit_loss_status = 1;
                           }
                           $symbolData = DB::table('fyers')->orderBy('id', 'desc')->first();
                           if($symbolData->trading_type == 2){         
                           $this->close_positions($runningOrder->stock_name);
                           }
                                DB::table('tbl_order')
                                ->where('id', $runningOrder->id) 
                                ->update([
                                    'exit_price' => $live_price_Stock,
                                    'status' => 1, //complete
                                    'end_time' => now(),
                                    'profit_loss_status' => $profit_loss_status,
                                    'profit_loss_amt' => $pl2*$runningOrder->qty
                                ]);
                                $exit_created = 1;
                         } 
                        } // IF END 

                    // Wait for 3 seconds before the next iteration
                    sleep(3);
                    } //FOR LOOP END

                }
                else{
                    //UPDATE SL AS HISTORIC ID IS CHANGED
                    $status_modify ='';
                    $fyer_new_Order_id = '';
                    \Log::info('CE(5MIN)- UPDATE SL AND HISTORIC ID AS HISTORIC ID IS CHANGED');
                    if($secondLast->open_status == 1){
                        $sl = $secondLast->close;
                    }
                    else{
                        $sl = $secondLast->open;
                    }

                    $symbolData = DB::table('fyers')->orderBy('id', 'desc')->first();
                    if($symbolData->trading_type == 2){ 
                    $modify_fyer = $this->modify_order($runningOrder->exit_order_id,$sl,$runningOrder->qty);
                    $modify_fyer2 = json_decode($modify_fyer->getContent(), true);
                    $status_modify = $modify_fyer2['status'];
                    if($status_modify == 200){
                        $fyer_new_Order_id = $modify_fyer2['orderID'];
                    }
                    }
                    DB::table('tbl_order')
                    ->where('id', $runningOrder->id) 
                    ->update([
                        'historic_id' => $secondLast->id,
                        'exit_order_id' => $fyer_new_Order_id,
                        'sl' => $sl,
                    ]);
                    \Log::info('CE(5MIN)- SL UPDATED FROM ORDERS');
                    //CHECK CURRENT PRICE AND EXIT THE TRADE
                    $iterations = 18;
                    $exit_created = 0;
                    for ($i = 0; $i < $iterations; $i++) {
                        $live_price_Stock = $this->getPriceData($runningOrder->stock_name);
                        if($live_price_Stock < $runningOrder->sl){
                            if($exit_created == 0){
                                //  CLOSED THE TRADE
                           $pl = $runningOrder->order_price-$live_price_Stock;
                           $pl2 = abs($runningOrder->order_price-$live_price_Stock);
                           if($pl >0){
                            $profit_loss_status = 0;
                           }
                           else{
                            $profit_loss_status = 1;
                           }         
                          
                           if($symbolData->trading_type == 2){         
                           $this->close_positions($runningOrder->stock_name);
                           }
                                DB::table('tbl_order')
                                ->where('id', $runningOrder->id) 
                                ->update([
                                    'exit_price' => $live_price_Stock,
                                    'status' => 1, //complete
                                    'end_time' => now(),
                                    'profit_loss_status' => $profit_loss_status,
                                    'profit_loss_amt' => $pl2*$runningOrder->qty,
                                ]);
                                $exit_created = 1;
                            }
                        }

                                // Wait for 3 seconds before the next iteration
                                sleep(3);
                    } //FOR END

                } //ELSE END

            } // IF NO SECOND LAST DATA


        } //ORDER NOT RUNNING
        else{
            \Log::info('CE(5MIN)-NO ORDER RUNNING');
            //CREATE NEW ORDER
            $entry = 0;
            // GET NIFTY VALUE IF NIFTY IS POSITIVE OR NEGATIVE
            // $nifty = $this->getPriceData('nifty');
            $nifty_current_type = $this->nifty_current(60);
           
            if($nifty_current_type){
                $symbolData = DB::table('fyers')->orderBy('id', 'desc')->first();
                if($nifty_current_type == 1){
                    // NIFTY IS GREEN/POSITIVE
                    \Log::info('CE(5MIN)-NIFTY CURRENTLY GREEN ');
                    $nifty_status = 1;
                    $symbol = $symbolData->option_ce;
                    $qty = $symbolData->lots * $symbolData->lots_size;
                    // \Log::info('NIFTY TRADING GREEN _ CE SIDE');
                }
                else{
                    // NIFTY IS RED/NEGATIVE
                    \Log::info('CE(5MIN)-NIFTY CURRENTLY RED SO EXIT');
                    $nifty_status = 2;
                    $symbol = $symbolData->option_pe;
                    exit;
                    // \Log::info('NIFTY TRADING RED _ PE SIDE');
                }

            if(empty($symbol)){
                \Log::info('CE(5MIN)-SYMBOL NOT FOUND');
                exit;
            }               
            //CHECK LAST OPEN TO 
            $secondLast = Historical5min::wherenull('deleted_at')->where('tred_option', $nifty_status)->orderBy('id', 'DESC')->skip(1)
            ->take(1)->first();
            if($secondLast->open_status == 1){

                    $last_open = $secondLast->open;
                    $last_close = $secondLast->close;
                    $iterations = 18; // Set the number of times the loop should run

                    for ($i = 0; $i < $iterations; $i++) {
                        $live_price_Stock = $this->getPriceData($symbol);
                        \Log::info('CE(5MIN)- Live - Open ' . $live_price_Stock.','.$last_open);
                        if($live_price_Stock > $last_open){
                            if($entry == 0){
                                $tradedTime = '';
                                $tradedPrice = '';
                                $order_ID = '';
                                $exit_order_ID = '';
                           if($symbolData->trading_type == 2){
                               //place order on live
                               $order_placed = $this->place_order($symbol,$qty);
                                  // Decode the JSON response
                                $order_placed2 = json_decode($order_placed->getContent(), true);

                                    // Access specific fields
                                    $status = $order_placed2['status'];
                                    if($status == 200){
                                        $message = $order_placed2['message'];
                                        $tradedTime = $order_placed2['tradedTime'];
                                        $tradedPrice = $order_placed2['tradedPrice'];
                                        $order_ID = $order_placed2['orderID'];
                                        

                                        //create SL exit order 
                                       $exit_order_fyer =  $this->exit_order_create($symbol,$qty,$last_close,2);
                                       $exit_order_fyer2 = json_decode($exit_order_fyer->getContent(), true);
                                       $exis_status = $exit_order_fyer2['status'];
                                       if($exis_status == 200){
                                        \Log::info('CE(5MIN)- EXIT_ORDER_ID' . $exit_order_fyer2['orderID']);
                                        $exit_order_ID = $exit_order_fyer2['orderID'];
                                       }

                                    }
                                    else{
                                        $tradedTime = '';
                                        $tradedPrice = '';
                                        $order_ID = '';
                                    }
                                   
                           }     
                           \Log::info('CE(5MIN)- d1 ' . $symbol);
                           $order = Order::create([
                            'stock_name' => $symbol,
                            'stock' => $nifty_status,
                            'type' => 1,
                            'timeframe' => 1,
                            'buy_price' => $last_open,
                            'order_price' => $live_price_Stock,
                            'sl' => $last_close,
                            'exit_price' => 0,
                            'status' => 0, //pending
                            'start_time' => now(),
                            'end_time' => "",
                            'qty' => $qty,
                            'profit_loss_status' => 0,
                            'profit_loss_amt' => 0,
                            'historic_id' => $secondLast->id,
                            'tradedprice' => $tradedPrice,
                            'tradedstarttime'=> $tradedTime,
                            'exit_order_id'=> $exit_order_ID,
                            'order_id'=> $order_ID,
                            'created_at' => now()
                        ]);
                        $OrderId = $order->id; // Retrieve the insert ID
                        $original_buy_price = $order->order_price;
                        $original_qty = $order->qty;
                           $entry = 1;
                            }
                            //   exit;
                        }
                        if($live_price_Stock < $last_close){
                            \Log::info('CE(5MIN)- CURRENT PRICE LOWER THAN LAST CLOSE, Exit Created at ' . now());
                         if($entry == 1){
                                        // Update data into database
                           $pl = $original_buy_price-$live_price_Stock;
                           $pl2 = abs($original_buy_price-$live_price_Stock);
                           if($pl >0){
                            $profit_loss_status = 0;
                           }
                           else{
                            $profit_loss_status = 1;
                           } 
                           $symbolData = DB::table('fyers')->orderBy('id', 'desc')->first();
                           if($symbolData->trading_type == 2){         
                           $this->close_positions($symbol);
                           }
                                DB::table('tbl_order')
                                ->where('id', $OrderId) 
                                ->update([
                                    'exit_price' => $live_price_Stock,
                                    'status' => 1, //complete
                                    'end_time' => now(),
                                    'profit_loss_status' => $profit_loss_status,
                                    'profit_loss_amt' => $pl2*$original_qty
                                ]);
                         }
                             //   exit;
                         }
                        


    // Wait for 3 seconds before the next iteration
            sleep(3);
                    }
            } // SECOND LAST CANDLE NOT RED
            else{
                \Log::info('CE(5MIN) -SECOND LAST CANDLE NOT RED');
            }

            }
            else{
                \Log::info('CE(5MIN)- NOT GETTING nifty_current_type');
            }
        }


    }

    //stock - 2
    public function createOrder_PE_5min()
    {
          // Your function logic here
        //   \Log::info('Task executed at anay PE' . now());
        //   exit;
        //check if order exists or not
        $runningOrder = Order::where('status', 0)->where('type', 2)->where('timeframe', 1)->first();
    

        if($runningOrder){
            // ORDER IS IS PROCESS
            //CHECK LAST OPEN TO 
            $secondLast = Historical5min::wherenull('deleted_at')->where('tred_option', $runningOrder->stock)->orderBy('id', 'DESC')->skip(1)
            ->take(1)->first();

            if($secondLast){
                if($secondLast->id == $runningOrder->historic_id){
                    \Log::info('PE(5MIN)- SAME HISTORIC ID');
                    //NO UPDATE TO BE DONE IN ORDER
                    $iterations = 18;
                    $exit_created = 0;
                    for ($i = 0; $i < $iterations; $i++) {
                        $live_price_Stock = $this->getPriceData($runningOrder->stock_name);
                        \Log::info('PE(5MIN)- Live - SL ' . $live_price_Stock.','.$runningOrder->sl);
                        if($live_price_Stock < $runningOrder->sl){
                            if($exit_created == 0)  { 
                            //  CLOSED THE TRADE
                           $pl = $runningOrder->order_price-$live_price_Stock;
                           $pl2 = abs($runningOrder->order_price-$live_price_Stock);
                           if($pl >0){
                            $profit_loss_status = 0;
                           }
                           else{
                            $profit_loss_status = 1;
                           }
                           $symbolData = DB::table('fyers')->orderBy('id', 'desc')->first();
                           if($symbolData->trading_type == 2){         
                           $this->close_positions($runningOrder->stock_name);
                           }         
                           \Log::info('PE(5MIN) TRADE EXITED' . $live_price_Stock);
                                DB::table('tbl_order')
                                ->where('id', $runningOrder->id) 
                                ->update([
                                    'exit_price' => $live_price_Stock,
                                    'status' => 1, //complete
                                    'end_time' => now(),
                                    'profit_loss_status' => $profit_loss_status,
                                    'profit_loss_amt' => $pl2*$runningOrder->qty,
                                    'created_at' => now()
                                ]);
                                $exit_created = 1;
                            }
                        } // IF END 

                    // Wait for 3 seconds before the next iteration
                    sleep(3);
                    } //FOR LOOP END

                }
                else{
                    $status_modify ='';
                    $fyer_new_Order_id = '';
                    \Log::info('PE(5MIN)- UPDATE SL AND HISTORIC ID AS HISTORIC ID IS CHANGED');
                    //UPDATE SL AS HISTORIC ID IS CHANGED
                    if($secondLast->open_status == 1){
                        $sl = $secondLast->close;
                    }
                    else{
                        $sl = $secondLast->open;
                    }
                    $symbolData = DB::table('fyers')->orderBy('id', 'desc')->first();
                    if($symbolData->trading_type == 2){ 
                    $modify_fyer = $this->modify_order($runningOrder->exit_order_id,$sl,$runningOrder->qty);
                    $modify_fyer2 = json_decode($modify_fyer->getContent(), true);
                    $status_modify = $modify_fyer2['status'];
                    if($status_modify == 200){
                        $fyer_new_Order_id = $modify_fyer2['orderID'];
                    }
                    }
                    DB::table('tbl_order')
                    ->where('id', $runningOrder->id) 
                    ->update([
                        'historic_id' => $secondLast->id,
                        'sl' => $sl,
                        'exit_order_id'=> $fyer_new_Order_id,
                    ]);

                    \Log::info('PE(5MIN)- SL UPDATED FROM ORDERS');
                    //CHECK CURRENT PRICE AND EXIT THE TRADE
                    $iterations = 18;
                    $exit_created = 0;
                    for ($i = 0; $i < $iterations; $i++) {
                        $live_price_Stock = $this->getPriceData($runningOrder->stock_name);
                        if($live_price_Stock < $runningOrder->sl){
                            if($exit_created == 0){
                                //  CLOSED THE TRADE
                           $pl = $runningOrder->order_price-$live_price_Stock;
                           $pl2 = abs($runningOrder->order_price-$live_price_Stock);
                           if($pl >0){
                            $profit_loss_status = 0;
                           }
                           else{
                            $profit_loss_status = 1;
                           }
                           if($symbolData->trading_type == 2){         
                           $this->close_positions($runningOrder->stock_name);
                           }         
                                DB::table('tbl_order')
                                ->where('id', $runningOrder->id) 
                                ->update([
                                    'exit_price' => $live_price_Stock,
                                    'status' => 1, //complete
                                    'end_time' => now(),
                                    'profit_loss_status' => $profit_loss_status,
                                    'profit_loss_amt' => $pl2*$runningOrder->qty,
                                ]);
                                $exit_created = 1;
                            }
                        }

 // Wait for 3 seconds before the next iteration
 sleep(3);
                    } //FOR END

                } //ELSE END

            } // IF NO SECOND LAST DATA


        } //ORDER NOT RUNNING
        else{
            \Log::info('PE(5MIN)-NO ORDER RUNNING');
            //CREATE NEW ORDER
            $entry = 0;
            // GET NIFTY VALUE IF NIFTY IS POSITIVE OR NEGATIVE
            // $nifty = $this->getPriceData('nifty');
            $nifty_current_type = $this->nifty_current(60);
           
            if($nifty_current_type){
              
                $symbolData = DB::table('fyers')->orderBy('id', 'desc')->first();
                if($nifty_current_type == 1){
                    \Log::info('PE(5MIN)- NIFTY CURRENTLY GREEN SO EXIT');
                    // NIFTY IS GREEN/POSITIVE
                    $nifty_status = 1;
                    $symbol = $symbolData->option_ce;
                    
                    // \Log::info('NIFTY TRADING GREEN _ CE SIDE');
                    exit;
                }
                else{
                    // NIFTY IS RED/NEGATIVE
                    \Log::info('PE(5MIN)- NIFTY CURRENTLY RED - ENTRY');
                    $nifty_status = 2;
                    $symbol = $symbolData->option_pe;
                    $qty = $symbolData->lots * $symbolData->lots_size;
                    // \Log::info('NIFTY TRADING RED _ PE SIDE');
                }

                if(empty($symbol)){
                    \Log::info('PE(5MIN)-SYMBOL NOT FOUND');
                    exit;
                }              
            //CHECK LAST OPEN TO 
            $secondLast = Historical5min::wherenull('deleted_at')->where('tred_option', $nifty_status)->orderBy('id', 'DESC')->skip(1)
            ->take(1)->first();
            if($secondLast->open_status == 1){

                    $last_open = $secondLast->open;
                    $last_close = $secondLast->close;
                    $iterations = 18; // Set the number of times the loop should run
                    $exit_created = 0;
                    for ($i = 0; $i < $iterations; $i++) {
                        $live_price_Stock = $this->getPriceData($symbol);
                        \Log::info('PE(5MIN)- Live - Open ' . $live_price_Stock.','.$last_open);
                        if($live_price_Stock > $last_open){
                            if($entry == 0){
                                $tradedTime = '';
                                $tradedPrice = '';
                                $order_ID = '';
                                $exit_order_ID = '';
                           if($symbolData->trading_type == 2){
                               //place order on live
                               $order_placed = $this->place_order($symbol,$qty);
                                  // Decode the JSON response
                                $order_placed2 = json_decode($order_placed->getContent(), true);

                                    // Access specific fields
                                    $status = $order_placed2['status'];
                                    if($status == 200){
                                        $message = $order_placed2['message'];
                                        $tradedTime = $order_placed2['tradedTime'];
                                        $tradedPrice = $order_placed2['tradedPrice'];
                                        $order_ID = $order_placed2['orderID'];

                                        $exit_order_fyer =  $this->exit_order_create($symbol,$qty,$last_close,2);
                                        $exit_order_fyer2 = json_decode($exit_order_fyer->getContent(), true);
                                        $exis_status = $exit_order_fyer2['status'];
                                        if($exis_status == 200){
                                            \Log::info('PE(5MIN)- EXIT_ORDER_ID' . $exit_order_fyer2['orderID']);
                                         $exit_order_ID = $exit_order_fyer2['orderID'];
                                        }
                                    }
                                    else{
                                        $tradedTime = '';
                                        $tradedPrice = '';
                                        $order_ID = '';
                                    }
                                   
                           }     
                           \Log::info('PE(5MIN)- d1 ' . $symbol);
                           $order = Order::create([
                            'stock_name' => $symbol,
                            'stock' => $nifty_status,
                            'type' => 2,
                            'timeframe' => 1,
                            'buy_price' => $last_open,
                            'order_price' => $live_price_Stock,
                            'sl' => $last_close,
                            'exit_price' => 0,
                            'status' => 0, //pending
                            'start_time' => now(),
                            'end_time' => "",
                            'qty' => $qty,
                            'profit_loss_status' => 0,
                            'profit_loss_amt' => 0,
                            'historic_id' => $secondLast->id,
                            'tradedprice' => $tradedPrice,
                            'tradedstarttime'=> $tradedTime,
                            'order_id'=> $order_ID,
                            'exit_order_id'=> $exit_order_ID,
                            'created_at' => now()
                        ]);
                        $OrderId = $order->id; // Retrieve the insert ID
                        $original_buy_price = $order->order_price;
                        $original_qty = $order->qty;
                           $entry = 1;
                            }
                            //   exit;
                        }
                        if($live_price_Stock < $last_close){
                            \Log::info('PE(5MIN)- CURRENT PRICE LOWER THAN LAST CLOSE, Exit Created at ' . now());
                         if($entry == 1 && $exit_created == 0){
                                        // Update data into database
                           $pl = $original_buy_price-$live_price_Stock;
                           $pl2 = abs($runningOrder->order_price-$live_price_Stock);
                           if($pl >0){
                            $profit_loss_status = 0;
                           }
                           else{
                            $profit_loss_status = 1;
                           }         
                           $symbolData = DB::table('fyers')->orderBy('id', 'desc')->first();
                           if($symbolData->trading_type == 2){         
                           $this->close_positions($symbol);
                           }
                                DB::table('tbl_order')
                                ->where('id', $OrderId) 
                                ->update([
                                    'exit_price' => $live_price_Stock,
                                    'status' => 1, //complete
                                    'end_time' => now(),
                                    'profit_loss_status' => $profit_loss_status,
                                    'profit_loss_amt' => $pl2*$original_qty
                                ]);
                                $exit_created = 1;
                         }
                             //   exit;
                         }
                        


    // Wait for 3 seconds before the next iteration
            sleep(3);
                    }
            } // SECOND LAST CANDLE NOT RED
            else{
                \Log::info('PE(5MIN)- SECOND LAST CANDLE NOT RED');
            }

            }
            else{
                \Log::info('PE(5MIN)- NOT GETTING nifty_current_type');
            }
        }


    }

    //stock - 3
    public function createOrder_bank_CE_5min()
    {
        //check if order exists or not
        $runningOrder = Order::where('status', 0)->where('type', 3)->where('timeframe', 1)->first();

        if($runningOrder){
            // ORDER IS IS PROCESS
            //CHECK LAST OPEN TO 
            $secondLast = BankHistorical5min::wherenull('deleted_at')->where('tred_option', $runningOrder->stock)->orderBy('id', 'DESC')->skip(1)
            ->take(1)->first();

            if($secondLast){
                if($secondLast->id == $runningOrder->historic_id){
                    \Log::info('BANKCE(5MIN)- SAME HISTORIC ID');
                    //NO UPDATE TO BE DONE IN ORDER
                    $iterations = 18;
                    $exit_created = 0;
                    for ($i = 0; $i < $iterations; $i++) {
                        $live_price_Stock = $this->getPriceData($runningOrder->stock_name);
                        \Log::info('BANKCE(5MIN)- Live - SL ' . $live_price_Stock.','.$runningOrder->sl);
                        if($live_price_Stock < $runningOrder->sl){
                            if($exit_created == 0){
                                //  CLOSED THE TRADE
                           $pl2 = abs($runningOrder->order_price-$live_price_Stock);
                           $pl = $runningOrder->order_price-$live_price_Stock;
                           if($pl>0){
                            $profit_loss_status = 0;
                           }
                           else{
                            $profit_loss_status = 1;
                           }         
                                DB::table('tbl_order')
                                ->where('id', $runningOrder->id) 
                                ->update([
                                    'exit_price' => $live_price_Stock,
                                    'status' => 1, //complete
                                    'end_time' => now(),
                                    'profit_loss_status' => $profit_loss_status,
                                    'profit_loss_amt' => $pl2*$runningOrder->qty
                                ]);
                                $exit_created = 1;
                         } 
                        } // IF END 

                    // Wait for 3 seconds before the next iteration
                    sleep(3);
                    } //FOR LOOP END

                }
                else{
                    \Log::info('BANKCE(5MIN)- UPDATE SL AND HISTORIC ID AS HISTORIC ID IS CHANGED');
                    //UPDATE SL AS HISTORIC ID IS CHANGED
                    if($secondLast->open_status == 1){
                        $sl = $secondLast->close;
                    }
                    else{
                        $sl = $secondLast->open;
                    }
                    DB::table('tbl_order')
                    ->where('id', $runningOrder->id) 
                    ->update([
                        'historic_id' => $secondLast->id,
                        'sl' => $sl,
                    ]);

                    \Log::info('BANKCE(5MIN)- SL UPDATED FROM ORDERS');    
                    //CHECK CURRENT PRICE AND EXIT THE TRADE
                    $iterations = 18;
                    $exit_created = 0;
                    for ($i = 0; $i < $iterations; $i++) {
                        $live_price_Stock = $this->getPriceData($runningOrder->stock_name);
                        if($live_price_Stock < $runningOrder->sl){
                            if($exit_created == 0){
                                //  CLOSED THE TRADE
                           $pl = $runningOrder->order_price-$live_price_Stock;
                           $pl2 = abs($runningOrder->order_price-$live_price_Stock);
                           if($pl >0){
                            $profit_loss_status = 0;
                           }
                           else{
                            $profit_loss_status = 1;
                           }         
                                DB::table('tbl_order')
                                ->where('id', $runningOrder->id) 
                                ->update([
                                    'exit_price' => $live_price_Stock,
                                    'status' => 1, //complete
                                    'end_time' => now(),
                                    'profit_loss_status' => $profit_loss_status,
                                    'profit_loss_amt' => $pl2*$runningOrder->qty,
                                ]);
                                $exit_created = 1;
                            }
                        }

                                // Wait for 3 seconds before the next iteration
                                sleep(3);
                    } //FOR END

                } //ELSE END

            } // IF NO SECOND LAST DATA


        } //ORDER NOT RUNNING
        else{
            \Log::info('BANKCE(5MIN)-NO ORDER RUNNING');
            //CREATE NEW ORDER
            $entry = 0;
            // GET NIFTY VALUE IF NIFTY IS POSITIVE OR NEGATIVE
            // $nifty = $this->getPriceData('nifty');
            $nifty_current_type = $this->banknifty_current(60);
           
            if($nifty_current_type){
                $symbolData = DB::table('fyers')->orderBy('id', 'desc')->first();
                if($nifty_current_type == 1){
                    // NIFTY IS GREEN/POSITIVE
                    \Log::info('BANKCE(5MIN)-NIFTY CURRENTLY GREEN ');
                    $nifty_status = 1;
                    $symbol = $symbolData->bankoption_ce;
                    $qty = $symbolData->lots2 * $symbolData->lots2_size;
                    // \Log::info('NIFTY TRADING GREEN _ CE SIDE');
                }
                else{
                    // NIFTY IS RED/NEGATIVE
                    \Log::info('CE(5MIN)-NIFTY CURRENTLY RED SO EXIT');
                    $nifty_status = 2;
                    $symbol = $symbolData->bankoption_pe;
                    exit;
                    // \Log::info('NIFTY TRADING RED _ PE SIDE');
                }

            if(empty($symbol)){
                \Log::info('BANKCE(5MIN)-SYMBOL NOT FOUND');
                exit;
            }               
            //CHECK LAST OPEN TO 
            $secondLast = BankHistorical5min::wherenull('deleted_at')->where('tred_option', $nifty_status)->orderBy('id', 'DESC')->skip(1)
            ->take(1)->first();
            if($secondLast->open_status == 1){

                    $last_open = $secondLast->open;
                    $last_close = $secondLast->close;
                    $iterations = 18; // Set the number of times the loop should run

                    for ($i = 0; $i < $iterations; $i++) {
                        $live_price_Stock = $this->getPriceData($symbol);
                        \Log::info('BANKCE(5MIN)- Live - Open ' . $live_price_Stock.','.$last_open);
                        if($live_price_Stock > $last_open){
                            if($entry == 0){
                           \Log::info('BANKCE(5MIN)- d1 ' . $symbol);
                           $order = Order::create([
                            'stock_name' => $symbol,
                            'stock' => $nifty_status,
                            'type' => 3,
                            'timeframe' => 1,
                            'buy_price' => $last_open,
                            'order_price' => $live_price_Stock,
                            'sl' => $last_close,
                            'exit_price' => 0,
                            'status' => 0, //pending
                            'start_time' => now(),
                            'end_time' => "",
                            'qty' => $qty,
                            'profit_loss_status' => 0,
                            'profit_loss_amt' => 0,
                            'historic_id' => $secondLast->id,
                            'created_at' => now()
                        ]);
                        $OrderId = $order->id; // Retrieve the insert ID
                        $original_buy_price = $order->order_price;
                        $original_qty = $order->qty;
                           $entry = 1;
                            }
                            //   exit;
                        }
                        if($live_price_Stock < $last_close){
                            \Log::info('BANKCE(5MIN)- CURRENT PRICE LOWER THAN LAST CLOSE, Exit Created at ' . now());
                         if($entry == 1){
                                        // Update data into database
                           $pl = $original_buy_price-$live_price_Stock;
                           $pl2 = abs($original_buy_price-$live_price_Stock);
                           if($pl >0){
                            $profit_loss_status = 0;
                           }
                           else{
                            $profit_loss_status = 1;
                           }         
                                DB::table('tbl_order')
                                ->where('id', $OrderId) 
                                ->update([
                                    'exit_price' => $live_price_Stock,
                                    'status' => 1, //complete
                                    'end_time' => now(),
                                    'profit_loss_status' => $profit_loss_status,
                                    'profit_loss_amt' => $pl2*$original_qty
                                ]);
                         }
                             //   exit;
                         }
                        


    // Wait for 3 seconds before the next iteration
            sleep(3);
                    }
            } // SECOND LAST CANDLE NOT RED
            else{
                \Log::info('BANKCE(5MIN) -SECOND LAST CANDLE NOT RED');
            }

            }
            else{
                \Log::info('BANKCE(5MIN)- NOT GETTING nifty_current_type');
            }
        }


    }

    //stock - 4
    public function createOrder_bank_PE_5min()
    {
          // Your function logic here
        //   \Log::info('Task executed at anay PE' . now());
        //   exit;
        //check if order exists or not
        $runningOrder = Order::where('status', 0)->where('type', 4)->where('timeframe', 1)->first();
    

        if($runningOrder){
            // ORDER IS IS PROCESS
            //CHECK LAST OPEN TO 
            $secondLast = BankHistorical5min::wherenull('deleted_at')->where('tred_option', $runningOrder->stock)->orderBy('id', 'DESC')->skip(1)
            ->take(1)->first();

            if($secondLast){
                if($secondLast->id == $runningOrder->historic_id){
                    \Log::info('BANKPE(5MIN)- SAME HISTORIC ID');
                    //NO UPDATE TO BE DONE IN ORDER
                    $iterations = 18;
                    $exit_created = 0;
                    for ($i = 0; $i < $iterations; $i++) {
                        $live_price_Stock = $this->getPriceData($runningOrder->stock_name);
                        \Log::info('BANKPE(5MIN)- Live - SL ' . $live_price_Stock.','.$runningOrder->sl);
                        if($live_price_Stock < $runningOrder->sl){
                            if($exit_created == 0)  { 
                            //  CLOSED THE TRADE
                           $pl = $runningOrder->order_price-$live_price_Stock;
                           $pl2 = abs($runningOrder->order_price-$live_price_Stock);
                           if($pl >0){
                            $profit_loss_status = 0;
                           }
                           else{
                            $profit_loss_status = 1;
                           }         
                           \Log::info('BANKPE(5MIN) TRADE EXITED' . $live_price_Stock);
                                DB::table('tbl_order')
                                ->where('id', $runningOrder->id) 
                                ->update([
                                    'exit_price' => $live_price_Stock,
                                    'status' => 1, //complete
                                    'end_time' => now(),
                                    'profit_loss_status' => $profit_loss_status,
                                    'profit_loss_amt' => $pl2*$runningOrder->qty
                                ]);
                                $exit_created = 1;
                            }
                        } // IF END 

                    // Wait for 3 seconds before the next iteration
                    sleep(3);
                    } //FOR LOOP END

                }
                else{
                    \Log::info('BANKPE(5MIN)- UPDATE SL AND HISTORIC ID AS HISTORIC ID IS CHANGED');
                    //UPDATE SL AS HISTORIC ID IS CHANGED
                    if($secondLast->open_status == 1){
                        $sl = $secondLast->close;
                    }
                    else{
                        $sl = $secondLast->open;
                    }
                    DB::table('tbl_order')
                    ->where('id', $runningOrder->id) 
                    ->update([
                        'historic_id' => $secondLast->id,
                        'sl' => $sl,
                    ]);
                    \Log::info('BANKPE(5MIN)- SL UPDATED FROM ORDERS');
                    //CHECK CURRENT PRICE AND EXIT THE TRADE
                    $iterations = 18;
                    $exit_created = 0;
                    for ($i = 0; $i < $iterations; $i++) {
                        $live_price_Stock = $this->getPriceData($runningOrder->stock_name);
                        if($live_price_Stock < $runningOrder->sl){
                            if($exit_created == 0){
                                //  CLOSED THE TRADE
                           $pl = $runningOrder->order_price-$live_price_Stock;
                           $pl2 = abs($runningOrder->order_price-$live_price_Stock);
                           if($pl >0){
                            $profit_loss_status = 0;
                           }
                           else{
                            $profit_loss_status = 1;
                           }         
                                DB::table('tbl_order')
                                ->where('id', $runningOrder->id) 
                                ->update([
                                    'exit_price' => $live_price_Stock,
                                    'status' => 1, //complete
                                    'end_time' => now(),
                                    'profit_loss_status' => $profit_loss_status,
                                    'profit_loss_amt' => $pl2*$runningOrder->qty,
                                ]);
                                $exit_created = 1;
                            }
                        }

 // Wait for 3 seconds before the next iteration
 sleep(3);
                    } //FOR END

                } //ELSE END

            } // IF NO SECOND LAST DATA


        } //ORDER NOT RUNNING
        else{
            \Log::info('BANKPE(5MIN)-NO ORDER RUNNING');
            //CREATE NEW ORDER
            $entry = 0;
            // GET NIFTY VALUE IF NIFTY IS POSITIVE OR NEGATIVE
            // $nifty = $this->getPriceData('nifty');
            $nifty_current_type = $this->banknifty_current(60);
           
            if($nifty_current_type){
              
                $symbolData = DB::table('fyers')->orderBy('id', 'desc')->first();
                if($nifty_current_type == 1){
                    \Log::info('BANKPE(5MIN)- NIFTY CURRENTLY GREEN SO EXIT');
                    // NIFTY IS GREEN/POSITIVE
                    $nifty_status = 1;
                    $symbol = $symbolData->bankoption_ce;
                    
                    // \Log::info('NIFTY TRADING GREEN _ CE SIDE');
                    exit;
                }
                else{
                    // NIFTY IS RED/NEGATIVE
                    \Log::info('BANKPE(5MIN)- NIFTY CURRENTLY RED - ENTRY');
                    $nifty_status = 2;
                    $symbol = $symbolData->bankoption_pe;
                    $qty = $symbolData->lots2 * $symbolData->lots2_size;
                    // \Log::info('NIFTY TRADING RED _ PE SIDE');
                }

                if(empty($symbol)){
                    \Log::info('BANKPE(5MIN)-SYMBOL NOT FOUND');
                    exit;
                }              
            //CHECK LAST OPEN TO 
            $secondLast = BankHistorical5min::wherenull('deleted_at')->where('tred_option', $nifty_status)->orderBy('id', 'DESC')->skip(1)
            ->take(1)->first();
            if($secondLast->open_status == 1){

                    $last_open = $secondLast->open;
                    $last_close = $secondLast->close;
                    $iterations = 18; // Set the number of times the loop should run
                    $exit_created = 0;
                    for ($i = 0; $i < $iterations; $i++) {
                        $live_price_Stock = $this->getPriceData($symbol);
                        \Log::info('BANKPE(5MIN)- Live - Open ' . $live_price_Stock.','.$last_open);
                        if($live_price_Stock > $last_open){
                            if($entry == 0){
                           \Log::info('BANKPE(5MIN)- d1 ' . $symbol);
                           $order = Order::create([
                            'stock_name' => $symbol,
                            'stock' => $nifty_status,
                            'type' => 4,
                            'timeframe' => 1,
                            'buy_price' => $last_open,
                            'order_price' => $live_price_Stock,
                            'sl' => $last_close,
                            'exit_price' => 0,
                            'status' => 0, //pending
                            'start_time' => now(),
                            'end_time' => "",
                            'qty' => $qty,
                            'profit_loss_status' => 0,
                            'profit_loss_amt' => 0,
                            'historic_id' => $secondLast->id,
                            'created_at' => now()
                        ]);
                        $OrderId = $order->id; // Retrieve the insert ID
                        $original_buy_price = $order->order_price;
                        $original_qty = $order->qty;
                           $entry = 1;
                            }
                            //   exit;
                        }
                        if($live_price_Stock < $last_close){
                            \Log::info('BANKPE(5MIN)- CURRENT PRICE LOWER THAN LAST CLOSE, Exit Created at ' . now());
                         if($entry == 1 && $exit_created == 0){
                                        // Update data into database
                           $pl = $original_buy_price-$live_price_Stock;
                           $pl2 = abs($runningOrder->order_price-$live_price_Stock);
                           if($pl >0){
                            $profit_loss_status = 0;
                           }
                           else{
                            $profit_loss_status = 1;
                           }         
                                DB::table('tbl_order')
                                ->where('id', $OrderId) 
                                ->update([
                                    'exit_price' => $live_price_Stock,
                                    'status' => 1, //complete
                                    'end_time' => now(),
                                    'profit_loss_status' => $profit_loss_status,
                                    'profit_loss_amt' => $pl2*$original_qty
                                ]);
                                $exit_created = 1;
                         }
                             //   exit;
                         }
                        


    // Wait for 3 seconds before the next iteration
            sleep(3);
                    }
            } // SECOND LAST CANDLE NOT RED
            else{
                \Log::info('BANKPE(5MIN)- SECOND LAST CANDLE NOT RED');
            }

            }
            else{
                \Log::info('BANKPE(5MIN)- NOT GETTING nifty_current_type');
            }
        }


    }

//stock - 5
    public function createOrder_stock_CE_5min()
    {
          // Your function logic here
        //   \Log::info('Task executed at anay CE ' . now());
        //   exit;
        //check if order exists or not
        $runningOrder = Order::where('status', 0)->where('type', 5)->where('timeframe', 1)->first();

        if($runningOrder){
            // ORDER IS IS PROCESS
            //CHECK LAST OPEN TO 
            $secondLast = StockHistorical5min::wherenull('deleted_at')->where('tred_option', $runningOrder->stock)->orderBy('id', 'DESC')->skip(1)
            ->take(1)->first();

            if($secondLast){
                if($secondLast->id == $runningOrder->historic_id){
                    \Log::info('STOCKCE(5MIN)- SAME HISTORIC ID');
                    //NO UPDATE TO BE DONE IN ORDER
                    $iterations = 18;
                    $exit_created = 0;
                    for ($i = 0; $i < $iterations; $i++) {
                        $live_price_Stock = $this->getPriceData($runningOrder->stock_name);
                        \Log::info('STOCKCE(5MIN)- Live - SL ' . $live_price_Stock.','.$runningOrder->sl);
                        if($live_price_Stock < $runningOrder->sl){
                            if($exit_created == 0){
                                //  CLOSED THE TRADE
                           $pl2 = abs($runningOrder->order_price-$live_price_Stock);
                           $pl = $runningOrder->order_price-$live_price_Stock;
                           if($pl>0){
                            $profit_loss_status = 0;
                           }
                           else{
                            $profit_loss_status = 1;
                           }         
                                DB::table('tbl_order')
                                ->where('id', $runningOrder->id) 
                                ->update([
                                    'exit_price' => $live_price_Stock,
                                    'status' => 1, //complete
                                    'end_time' => now(),
                                    'profit_loss_status' => $profit_loss_status,
                                    'profit_loss_amt' => $pl2*$runningOrder->qty
                                ]);
                                $exit_created = 1;
                         } 
                        } // IF END 

                    // Wait for 3 seconds before the next iteration
                    sleep(3);
                    } //FOR LOOP END

                }
                else{
                    //UPDATE SL AS HISTORIC ID IS CHANGED
                    if($secondLast->open_status == 1){
                        $sl = $secondLast->close;
                    }
                    else{
                        $sl = $secondLast->open;
                    }
                    DB::table('tbl_order')
                    ->where('id', $runningOrder->id) 
                    ->update([
                        'historic_id' => $secondLast->id,
                        'sl' => $sl,
                    ]);

                    //CHECK CURRENT PRICE AND EXIT THE TRADE
                    $iterations = 18;
                    $exit_created = 0;
                    for ($i = 0; $i < $iterations; $i++) {
                        $live_price_Stock = $this->getPriceData($runningOrder->stock_name);
                        if($live_price_Stock < $runningOrder->sl){
                            if($exit_created == 0){
                                //  CLOSED THE TRADE
                           $pl = $runningOrder->order_price-$live_price_Stock;
                           $pl2 = abs($runningOrder->order_price-$live_price_Stock);
                           if($pl >0){
                            $profit_loss_status = 0;
                           }
                           else{
                            $profit_loss_status = 1;
                           }         
                                DB::table('tbl_order')
                                ->where('id', $runningOrder->id) 
                                ->update([
                                    'exit_price' => $live_price_Stock,
                                    'status' => 1, //complete
                                    'end_time' => now(),
                                    'profit_loss_status' => $profit_loss_status,
                                    'profit_loss_amt' => $pl2*$runningOrder->qty,
                                ]);
                                $exit_created = 1;
                            }
                        }

                                // Wait for 3 seconds before the next iteration
                                sleep(3);
                    } //FOR END

                } //ELSE END

            } // IF NO SECOND LAST DATA


        } //ORDER NOT RUNNING
        else{
            \Log::info('STOCKCE(5MIN)-NO ORDER RUNNING');
            //CREATE NEW ORDER
            $entry = 0;
            // GET NIFTY VALUE IF NIFTY IS POSITIVE OR NEGATIVE
            // $nifty = $this->getPriceData('nifty');
            $nifty_current_type = $this->nifty_current(60);
           
            if($nifty_current_type){
                $symbolData = DB::table('fyers')->orderBy('id', 'desc')->first();
                if($nifty_current_type == 1){
                    // NIFTY IS GREEN/POSITIVE
                    \Log::info('STOCKCE(5MIN)-NIFTY CURRENTLY GREEN ');
                    $nifty_status = 1;
                    $symbol = $symbolData->stockoption_ce;
                    $qty = $symbolData->lots3 * $symbolData->lots3_size;
                    // \Log::info('NIFTY TRADING GREEN _ CE SIDE');
                }
                else{
                    // NIFTY IS RED/NEGATIVE
                    \Log::info('STOCKCE(5MIN)-NIFTY CURRENTLY RED SO EXIT');
                    $nifty_status = 2;
                    $symbol = $symbolData->stockoption_pe;
                    exit;
                    // \Log::info('NIFTY TRADING RED _ PE SIDE');
                }

            if(empty($symbol)){
                \Log::info('STOCKCE(5MIN)-SYMBOL NOT FOUND');
                exit;
            }               
            //CHECK LAST OPEN TO 
            $secondLast = StockHistorical5min::wherenull('deleted_at')->where('tred_option', $nifty_status)->orderBy('id', 'DESC')->skip(1)
            ->take(1)->first();
            if($secondLast->open_status == 1){

                    $last_open = $secondLast->open;
                    $last_close = $secondLast->close;
                    $iterations = 18; // Set the number of times the loop should run

                    for ($i = 0; $i < $iterations; $i++) {
                        $live_price_Stock = $this->getPriceData($symbol);
                        \Log::info('STOCKCE(5MIN)- Live - Open ' . $live_price_Stock.','.$last_open);
                        if($live_price_Stock > $last_open){
                            if($entry == 0){
                           \Log::info('STOCKCE(5MIN)- d1 ' . $symbol);
                           $order = Order::create([
                            'stock_name' => $symbol,
                            'stock' => $nifty_status,
                            'type' => 5,
                            'timeframe' => 1,
                            'buy_price' => $last_open,
                            'order_price' => $live_price_Stock,
                            'sl' => $last_close,
                            'exit_price' => 0,
                            'status' => 0, //pending
                            'start_time' => now(),
                            'end_time' => "",
                            'qty' => $qty,
                            'profit_loss_status' => 0,
                            'profit_loss_amt' => 0,
                            'historic_id' => $secondLast->id,
                            'created_at' => now()
                        ]);
                        $OrderId = $order->id; // Retrieve the insert ID
                        $original_buy_price = $order->order_price;
                        $original_qty = $order->qty;
                           $entry = 1;
                            }
                            //   exit;
                        }
                        if($live_price_Stock < $last_close){
                            \Log::info('STOCKCE(5MIN)- CURRENT PRICE LOWER THAN LAST CLOSE, Exit Created at ' . now());
                         if($entry == 1){
                                        // Update data into database
                           $pl = $original_buy_price-$live_price_Stock;
                           $pl2 = abs($original_buy_price-$live_price_Stock);
                           if($pl >0){
                            $profit_loss_status = 0;
                           }
                           else{
                            $profit_loss_status = 1;
                           }         
                                DB::table('tbl_order')
                                ->where('id', $OrderId) 
                                ->update([
                                    'exit_price' => $live_price_Stock,
                                    'status' => 1, //complete
                                    'end_time' => now(),
                                    'profit_loss_status' => $profit_loss_status,
                                    'profit_loss_amt' => $pl2*$original_qty
                                ]);
                         }
                             //   exit;
                         }
                        


    // Wait for 3 seconds before the next iteration
            sleep(3);
                    }
            } // SECOND LAST CANDLE NOT RED
            else{
                \Log::info('STOCKCE(5MIN) -SECOND LAST CANDLE NOT RED');
            }

            }
            else{
                \Log::info('STOCKCE(5MIN)- NOT GETTING nifty_current_type');
            }
        }


    }

    //stock - 6
    public function createOrder_stock_PE_5min()
    {
          // Your function logic here
        //   \Log::info('Task executed at anay PE' . now());
        //   exit;
        //check if order exists or not
        $runningOrder = Order::where('status', 0)->where('type', 6)->where('timeframe', 1)->first();
    

        if($runningOrder){
            // ORDER IS IS PROCESS
            //CHECK LAST OPEN TO 
            $secondLast = StockHistorical5min::wherenull('deleted_at')->where('tred_option', $runningOrder->stock)->orderBy('id', 'DESC')->skip(1)
            ->take(1)->first();

            if($secondLast){
                if($secondLast->id == $runningOrder->historic_id){
                    \Log::info('STOCKPE(5MIN)- SAME HISTORIC ID');
                    //NO UPDATE TO BE DONE IN ORDER
                    $iterations = 18;
                    $exit_created = 0;
                    for ($i = 0; $i < $iterations; $i++) {
                        $live_price_Stock = $this->getPriceData($runningOrder->stock_name);
                        \Log::info('STOCKPE(5MIN)- Live - SL ' . $live_price_Stock.','.$runningOrder->sl);
                        if($live_price_Stock < $runningOrder->sl){
                            if($exit_created == 0)  { 
                            //  CLOSED THE TRADE
                           $pl = $runningOrder->order_price-$live_price_Stock;
                           $pl2 = abs($runningOrder->order_price-$live_price_Stock);
                           if($pl >0){
                            $profit_loss_status = 0;
                           }
                           else{
                            $profit_loss_status = 1;
                           }         
                           \Log::info('STOCKPE(5MIN) TRADE EXITED' . $live_price_Stock);
                                DB::table('tbl_order')
                                ->where('id', $runningOrder->id) 
                                ->update([
                                    'exit_price' => $live_price_Stock,
                                    'status' => 1, //complete
                                    'end_time' => now(),
                                    'profit_loss_status' => $profit_loss_status,
                                    'profit_loss_amt' => $pl2*$runningOrder->qty
                                ]);
                                $exit_created = 1;
                            }
                        } // IF END 

                    // Wait for 3 seconds before the next iteration
                    sleep(3);
                    } //FOR LOOP END

                }
                else{
                    //UPDATE SL AS HISTORIC ID IS CHANGED
                    if($secondLast->open_status == 1){
                        $sl = $secondLast->close;
                    }
                    else{
                        $sl = $secondLast->open;
                    }
                    DB::table('tbl_order')
                    ->where('id', $runningOrder->id) 
                    ->update([
                        'historic_id' => $secondLast->id,
                        'sl' => $sl,
                    ]);

                    //CHECK CURRENT PRICE AND EXIT THE TRADE
                    $iterations = 18;
                    $exit_created = 0;
                    for ($i = 0; $i < $iterations; $i++) {
                        $live_price_Stock = $this->getPriceData($runningOrder->stock_name);
                        if($live_price_Stock < $runningOrder->sl){
                            if($exit_created == 0){
                                //  CLOSED THE TRADE
                           $pl = $runningOrder->order_price-$live_price_Stock;
                           $pl2 = abs($runningOrder->order_price-$live_price_Stock);
                           if($pl >0){
                            $profit_loss_status = 0;
                           }
                           else{
                            $profit_loss_status = 1;
                           }         
                                DB::table('tbl_order')
                                ->where('id', $runningOrder->id) 
                                ->update([
                                    'exit_price' => $live_price_Stock,
                                    'status' => 1, //complete
                                    'end_time' => now(),
                                    'profit_loss_status' => $profit_loss_status,
                                    'profit_loss_amt' => $pl2*$runningOrder->qty,
                                ]);
                                $exit_created = 1;
                            }
                        }

 // Wait for 3 seconds before the next iteration
 sleep(3);
                    } //FOR END

                } //ELSE END

            } // IF NO SECOND LAST DATA


        } //ORDER NOT RUNNING
        else{
            \Log::info('STOCKPE(5MIN)-NO ORDER RUNNING');
            //CREATE NEW ORDER
            $entry = 0;
            // GET NIFTY VALUE IF NIFTY IS POSITIVE OR NEGATIVE
            // $nifty = $this->getPriceData('nifty');
            $nifty_current_type = $this->nifty_current(60);
           
            if($nifty_current_type){
              
                $symbolData = DB::table('fyers')->orderBy('id', 'desc')->first();
                if($nifty_current_type == 1){
                    \Log::info('STOCKPE(5MIN)- NIFTY CURRENTLY GREEN SO EXIT');
                    // NIFTY IS GREEN/POSITIVE
                    $nifty_status = 1;
                    $symbol = $symbolData->stockoption_ce;
                    
                    // \Log::info('NIFTY TRADING GREEN _ CE SIDE');
                    exit;
                }
                else{
                    // NIFTY IS RED/NEGATIVE
                    \Log::info('STOCKPE(5MIN)- NIFTY CURRENTLY RED - ENTRY');
                    $nifty_status = 2;
                    $symbol = $symbolData->stockoption_pe;
                    $qty = $symbolData->lots3 * $symbolData->lots3_size;
                    // \Log::info('NIFTY TRADING RED _ PE SIDE');
                }

                if(empty($symbol)){
                    \Log::info('STOCKPE(5MIN)-SYMBOL NOT FOUND');
                    exit;
                }              
            //CHECK LAST OPEN TO 
            $secondLast = StockHistorical5min::wherenull('deleted_at')->where('tred_option', $nifty_status)->orderBy('id', 'DESC')->skip(1)
            ->take(1)->first();
            if($secondLast->open_status == 1){

                    $last_open = $secondLast->open;
                    $last_close = $secondLast->close;
                    $iterations = 18; // Set the number of times the loop should run
                    $exit_created = 0;
                    for ($i = 0; $i < $iterations; $i++) {
                        $live_price_Stock = $this->getPriceData($symbol);
                        \Log::info('STOCKPE(5MIN)- Live - Open ' . $live_price_Stock.','.$last_open);
                        if($live_price_Stock > $last_open){
                            if($entry == 0){
                           \Log::info('STOCKPE(5MIN)- d1 ' . $symbol);
                           $order = Order::create([
                            'stock_name' => $symbol,
                            'stock' => $nifty_status,
                            'type' => 6,
                            'timeframe' => 1,
                            'buy_price' => $last_open,
                            'order_price' => $live_price_Stock,
                            'sl' => $last_close,
                            'exit_price' => 0,
                            'status' => 0, //pending
                            'start_time' => now(),
                            'end_time' => "",
                            'qty' => $qty,
                            'profit_loss_status' => 0,
                            'profit_loss_amt' => 0,
                            'historic_id' => $secondLast->id,
                            'created_at' => now()
                        ]);
                        $OrderId = $order->id; // Retrieve the insert ID
                        $original_buy_price = $order->order_price;
                        $original_qty = $order->qty;
                           $entry = 1;
                            }
                            //   exit;
                        }
                        if($live_price_Stock < $last_close){
                            \Log::info('STOCKPE(5MIN)- CURRENT PRICE LOWER THAN LAST CLOSE, Exit Created at ' . now());
                         if($entry == 1 && $exit_created == 0){
                                        // Update data into database
                           $pl = $original_buy_price-$live_price_Stock;
                           $pl2 = abs($runningOrder->order_price-$live_price_Stock);
                           if($pl >0){
                            $profit_loss_status = 0;
                           }
                           else{
                            $profit_loss_status = 1;
                           }         
                                DB::table('tbl_order')
                                ->where('id', $OrderId) 
                                ->update([
                                    'exit_price' => $live_price_Stock,
                                    'status' => 1, //complete
                                    'end_time' => now(),
                                    'profit_loss_status' => $profit_loss_status,
                                    'profit_loss_amt' => $pl2*$original_qty
                                ]);
                                $exit_created = 1;
                         }
                             //   exit;
                         }
                        


    // Wait for 3 seconds before the next iteration
            sleep(3);
                    }
            } // SECOND LAST CANDLE NOT RED
            else{
                \Log::info('STOCKPE(5MIN)- SECOND LAST CANDLE NOT RED');
            }

            }
            else{
                \Log::info('STOCKPE(5MIN)- NOT GETTING nifty_current_type');
            }
        }


    }


// 5min ORDER PLACING ENDS HERE

    private function getPriceData($symbol, $inputName = 'nifty')
    {
        $request = new \Illuminate\Http\Request();
        if($symbol == "NSE:NIFTY50-INDEX"){
        $request->merge([$inputName => $symbol]);
        $price = $this->getPrice($request);
        if ($price instanceof \Illuminate\Http\JsonResponse) {
            $priceData = json_decode($price->getContent(), true);
            if (is_array($priceData)) {
                return $priceData;
            }
        }
        $priceData = json_decode($price, true);
        if (is_array($priceData)) {
            return $priceData;
        }
        }
        elseif($symbol == "NSE:NIFTYBANK-INDEX"){
                $request->merge([$inputName => $symbol]);
                $price = $this->getPrice($request);
                if ($price instanceof \Illuminate\Http\JsonResponse) {
                    $priceData = json_decode($price->getContent(), true);
                    if (is_array($priceData)) {
                        return $priceData;
                    }
                }
                $priceData = json_decode($price, true);
                if (is_array($priceData)) {
                    return $priceData;
                }
               
        }
        elseif($symbol == "BSE:SENSEX-INDEX"){
            $request->merge([$inputName => $symbol]);
            $price = $this->getPrice($request);
            if ($price instanceof \Illuminate\Http\JsonResponse) {
                $priceData = json_decode($price->getContent(), true);
                if (is_array($priceData)) {
                    return $priceData;
                }
            }
            $priceData = json_decode($price, true);
            if (is_array($priceData)) {
                return $priceData;
            }
           
    }
        else{
             $request->merge(['isl' => $symbol]);
              $price = $this->getPrice($request);
              return $price;
        }
       
        
        return [
            'error' => 'Unexpected response format or invalid data.',
        ];
    }

    public function getPrice(Request $request)
    {
        \Log::channel('custom')->info('CALLED_GET_PRICE');
        $isl = $request->input('isl');
        $nifty = $request->input('nifty');
        $authCode = $this->authCode(); 
        if($nifty){
            // $nifty= "NSE:NIFTY50-INDEX";
            // print_r('jbuh');
            // exit;
            $url = 'https://api-t1.fyers.in/data/quotes?symbols=' . $nifty;
            $response = Http::withHeaders(['Authorization' => ''.config('constants.USER_ID').':' . $authCode,])->get($url);
            $data = json_decode($response->getBody()->getContents());
            $quoteData = $data->d[0] ?? null; 
            if ($quoteData) {
                $v = $quoteData->v; 
                
                if ($v) {
                    return response()->json([
                        'lp' => $v->lp ?? 'N/A',
                        'open_price' => $v->open_price ?? 'N/A',
                        'bid' => $v->bid ?? 'N/A'
                    ]);
                }
                
        }
        }
   
        // Get the authorization code (implement this method as needed)
        // Prepare the API endpoint
        $url = 'https://api-t1.fyers.in/data/quotes?symbols=' . $isl;
        // Make the GET request
        $response = Http::withHeaders(['Authorization' => ''.config('constants.USER_ID').':' . $authCode,])->get($url);
                
        // Decode the JSON response
        $data = json_decode($response->getBody()->getContents());
                
        // Check for errors in the response
        if (isset($data->s) && $data->s == "error") {
            sleep(2);
            $url = 'https://api-t1.fyers.in/data/quotes?symbols=' . $isl;
            // Make the GET request
            $response = Http::withHeaders(['Authorization' => ''.config('constants.USER_ID').':' . $authCode,])->get($url);
                    
            // Decode the JSON response
            $data = json_decode($response->getBody()->getContents());
            if(isset($data->s) && $data->s == "error"){
                return "err"; // Return error if API response indicates an error
            }
          
        }
                
        // Process the response
        $quoteData = $data->d[0] ?? null; // Get the first data item
                
        if ($quoteData) {
            $v = $quoteData->v; // Access the nested 'v' object
            $ask = $v->ask ?? null; // Get the ask price
            $bid = $v->bid ?? null; // Get the bid price
            $lp = $v->lp ?? null; // Get the bid price
            
            // Handle NIFTY case
            if ($isl == 'NIFTY') {
                return $v->cmd->c; // Return command c for NIFTY
            }
            if ($isl == 'BANKNIFTY') {
                return $v->cmd->c; // Return command c for NIFTY
            }
                
                // Return ask price or bid price if ask is 0
                return $lp;
            }
                
            return null; // Return null if no data found
                    
    }

    public function authCode()
    {
        // Fetch the latest auth_code from the tbl_config table
        $result = DB::table('tbl_config')->select('auth_code')->orderBy('id', 'DESC')->first(); // Get the first record
        // Return the auth_code if available, otherwise return null
        return $result ? $result->auth_code : null;
    }

    private function nifty_current($time)
    {
        \Log::channel('custom')->info('CALLED-NIFTY_CURRENT');
        $symbolData = DB::table('fyers')->orderBy('id', 'desc')->first();
        $symbol = $symbolData->index_name;
        $currentDate = date('Y-m-d');
        $startTime = config('constants.time.START_TIME');
        $endTime = config('constants.time.END_TIME');
        $startDateTime = $currentDate . ' ' . $startTime;

        $datetime = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
        // $endDateTime = '2025-01-06 10:14:00';
        $endDateTime = $datetime->format('Y-m-d H:i:s');

        $date1 =$startDateTime;
        $date2 =$endDateTime;

        $date00 = new DateTime($date1); // Your original date and time
        $date00->setTime($date00->format('H'), $date00->format('i'), 0); // Set seconds to 0
        $date100 = $date00->format('Y-m-d H:i:s');
        $date11 = new DateTime($date100, new DateTimeZone('Asia/Kolkata')); // Your date and timezone
        $d1 = $date11->getTimestamp();

       

        $date0011 = new DateTime($date2); // Your original date and time
        $date0011->setTime($date0011->format('H'), $date0011->format('i'), 0); // Set seconds to 0
        $date200 = $date0011->format('Y-m-d H:i:s');
        $date22 = new DateTime($date200, new DateTimeZone('Asia/Kolkata')); // Your date and timezone
        $d2 = $date22->getTimestamp();

        $nifty = $this->getPriceData($symbol);

        $auth_code = $this->authCode();
        $res = $time;
        $date_format = 0;
        $range_from = $d1;
        // $range_from = 1736135100;
        $range_to = $d2;
        // $range_to = 1736138640;

        $curl = curl_init();
            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api-t1.fyers.in/data/history?symbol='.$symbol.'&resolution='.$res.'&date_format='.$date_format.'&range_from='.$range_from.'&range_to='.$range_to.'&cont_flag=',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
            'Authorization: '.config('constants.USER_ID').':'.$auth_code
            ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            
            if($response){
                                    $now = new DateTime(); // Current time
                    $start = new DateTime('09:15');
                    $end = new DateTime('10:15');

                    $data = json_decode($response);
                if(isset($data->candles)){
                    $candles = $data->candles;
                }
                else{
                    \Log::info('ERROR - '.$response);
                }
                    // Check if the current time falls between 9:15 and 10:15
                    if ($now >= $start && $now <= $end) {
                        $offset = -1;
                        $lastTwoCandles = $candles[0];
                        // Extract second last and last candle data
                        $lastOpen = $lastTwoCandles[1];
                        $nifty_now = $nifty['lp'];

                    } else {
                        $lastTwoCandles = array_slice($candles, -2);
                        // Extract second last and last candle data
                        $secondLastCandle = $lastTwoCandles[0];
                        $lastCandle = $lastTwoCandles[1];
                        $lastOpen = $lastCandle[1];
                        $nifty_now = $nifty['lp'];
                        // \Log::info('NIFTY LAST CANDLE - '.$response);
                    }
              
                // \Log::info('NIFTY LAST OPEN - '.$lastOpen);
                
                if($nifty_now >= $lastOpen){
                    return 1;
                }
                else{
                    return 2;
                }
            }
           
    }

private function place_order($stockname,$qty){
    //ORDER PLACING CODE HERE
    $auth_code = $this->authCode();

    $curl = curl_init();
    curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://api-t1.fyers.in/api/v3/orders/sync',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS =>'{
    "symbol":"'.$stockname.'",
    "qty":'.$qty.',
    "type":2,
    "side":1,
    "productType":"INTRADAY",
    "limitPrice":0,
    "stopPrice":0,
    "validity":"IOC",
    "disclosedQty":0,
    "offlineOrder":false,
    "stopLoss":0,
    "takeProfit":0
    }',
    CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json',
    'Authorization: '.config('constants.USER_ID').':'.$auth_code,

    ),
    ));

    $response = curl_exec($curl);
    \Log::info('PLACEORDER - '.$response.' stock-'.$stockname.' qty-'.$qty);
    curl_close($curl);
    $r= json_decode($response);

//ORDER PLACING CODE ENDS HERE
// CHECKING ORDER PLACED AND ITS AMOUNT

if($r->s == "ok"){

$curl2 = curl_init();

curl_setopt_array($curl2, array(
  CURLOPT_URL => 'https://api.fyers.in/api/v2/orders?id='.$r->id,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
  CURLOPT_HTTPHEADER => array(
    'Authorization: '.config('constants.USER_ID').':'.$auth_code
  ),
));

$response2 = curl_exec($curl2);
\Log::info('PLACEORDER2 - '.$response2);
// log_message('error', "CASE 5 - order get details response - ".$response2);

curl_close($curl2);

$r2 = json_decode($response2);

    $status = $r2->s;

    if($status == "ok"){
      $book = $r2->orderBook;
      $time = $book[0]->orderDateTime;
      $t_price = $book[0]->tradedPrice;

      return response()->json([
        'status' => 200,
        'message' => 'success',
        'orderID' => $r->id,
        'tradedTime' => $time,
        'tradedPrice' => $t_price
            ]);
        
    } //if status ok
    else{
        return response()->json([
            'status' => 202,
            'message' => 'not ok',
            'tradedTime' => '',
            'tradedPrice' => ''
                ]);
    }

    } // if above api status ok
    else{
        return response()->json([
            'status' => 201,
            'message' => 'not ok',
            'tradedTime' => '',
            'tradedPrice' => ''
                ]);
    }
    
}// function close

private function exit_order_create($stockname,$qty,$price,$sleep){
    //ORDER PLACING CODE HERE
    $auth_code = $this->authCode();
    sleep($sleep);
    $new_price  = $price-2;
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://api-t1.fyers.in/api/v3/orders/sync',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS =>'{
      "symbol":"'.$stockname.'",
      "qty":'.$qty.',
      "type":4,
      "side":-1,
      "productType":"INTRADAY",
      "limitPrice":'.$new_price.',
      "stopPrice":'.$price.',
      "validity":"DAY",
      "disclosedQty":0,
      "offlineOrder":false,
      "stopLoss":0,
      "takeProfit":0,
      "orderTag":"tag1"
    }',
      CURLOPT_HTTPHEADER => array(
        'Authorization: '.config('constants.USER_ID').':'.$auth_code
      ),
    ));
    
    $response = curl_exec($curl);
    
    curl_close($curl);
    \Log::info('EXITORDER - '.$response);
    curl_close($curl);
    $r= json_decode($response);

//ORDER PLACING CODE ENDS HERE
// CHECKING ORDER PLACED AND ITS AMOUNT

if($r->s == "ok"){

    return response()->json([
        'status' => 200,
        'message' => 'ok',
        'tradedTime' => '',
        'tradedPrice' => '',
        'orderID' => $r->id
            ]);

    } // if above api status ok
    else{
        return response()->json([
            'status' => 201,
            'message' => 'not ok',
            'tradedTime' => '',
            'tradedPrice' => ''
                ]);
    }
    
}// function close

private function modify_order($orderID,$price,$qty){

    $auth_code = $this->authCode();
    $new_price = $price-2;
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://api-t1.fyers.in/api/v3/orders/sync',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'PATCH',
      CURLOPT_POSTFIELDS =>'{
      "id": "'.$orderID.'",
      "qty": '.$qty.',
      "type": 4,
      "side": -1,
      "limitPrice":'.$new_price.',
      "stopPrice":'.$price.'
    }',
      CURLOPT_HTTPHEADER => array(
       'Authorization: '.config('constants.USER_ID').':'.$auth_code
      ),
    ));
    
    $response = curl_exec($curl);
    \Log::info('MODIFYORDER - '.$response.' SL-'.$price);
    curl_close($curl);
    $r= json_decode($response);

    //ORDER PLACING CODE ENDS HERE
    // CHECKING ORDER PLACED AND ITS AMOUNT
    
    if($r->s == "ok"){
    
        return response()->json([
            'status' => 200,
            'message' => 'ok',
            'tradedTime' => '',
            'tradedPrice' => '',
            'orderID' => $r->id
                ]);
    
        } // if above api status ok
        else{
            return response()->json([
                'status' => 201,
                'message' => 'not ok',
                'tradedTime' => '',
                'tradedPrice' => ''
                    ]);
        }

}

private function close_positions($symbol){

    $auth_code = $this->authCode();
    $symbol2 = $symbol.'-INTRADAY';
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://api.fyers.in/api/v2/positions',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'DELETE',
      CURLOPT_POSTFIELDS =>'{"id":"'.$symbol.'"}',
      CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'Authorization: '.config('constants.USER_ID').':'.$auth_code,
      ),
    ));

    $response = curl_exec($curl);
    \Log::info('CLOSEPOSITION - '.$response);
    curl_close($curl);
    return $response;


       }

}