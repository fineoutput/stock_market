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

class OrderAutoController extends Controller
{


public function createOrder()
    {
          // Your function logic here
          \Log::info('Task executed at anay ' . now());
        //   exit;
        //check if order exists or not
        $runningOrder = Order::where('status', 0)->first();

        if($runningOrder){
            // ORDER IS IS PROCESS
            //CHECK LAST OPEN TO 
            $secondLast = Historical::wherenull('deleted_at')->where('tred_option', $runningOrder->stock)->orderBy('id', 'DESC')->skip(1)
            ->take(1)->first();

            if($secondLast){
                if($secondLast->id == $runningOrder->historic_id){
                    \Log::info('SAME HISTORIC ID');
                    //NO UPDATE TO BE DONE IN ORDER
                    $iterations = 18;
                    for ($i = 0; $i < $iterations; $i++) {
                        $live_price_Stock = $this->getPriceData($runningOrder->stock_name);
                        \Log::info('Live - SL ' . $live_price_Stock.','.$runningOrder->sl);
                        if($live_price_Stock < $runningOrder->sl){
                                //  CLOSED THE TRADE
                           $pl = $runningOrder->order_price-$live_price_Stock;
                           if($pl >0){
                            $profit_loss_status = 1;
                           }
                           else{
                            $profit_loss_status = 0;
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

                        } // IF END 


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
                    for ($i = 0; $i < $iterations; $i++) {
                        $live_price_Stock = $this->getPriceData($runningOrder->stock_name);
                        if($live_price_Stock < $runningOrder->sl){
                                //  CLOSED THE TRADE
                           $pl = $runningOrder->order_price-$live_price_Stock;
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
                                    'profit_loss_amt' => $pl*$runningOrder->qty,
                                ]);

                        }

 // Wait for 3 seconds before the next iteration
 sleep(3);
                    } //FOR END

                } //ELSE END

            } // IF NO SECOND LAST DATA


        } //ORDER NOT RUNNING
        else{
            //CREATE NEW ORDER
            $entry = 0;
            // GET NIFTY VALUE IF NIFTY IS POSITIVE OR NEGATIVE
            $nifty = $this->getPriceData('nifty');
           
            if($nifty){
                $nifty_current = $nifty['lp'];
                $nifty_open = $nifty['open_price'];
                // print_r($nifty_open);

                $symbolData = DB::table('fyers')->orderBy('id', 'desc')->first();
                if($nifty_current > $nifty_open){
                    // NIFTY IS GREEN/POSITIVE
                    $nifty_status = 1;
                    $symbol = $symbolData->option_ce;
                    \Log::info('NIFTY TRADING GREEN _ CE SIDE');
                }
                else{
                    // NIFTY IS RED/NEGATIVE
                    $nifty_status = 2;
                    $symbol = $symbolData->option_pe;
                    \Log::info('NIFTY TRADING RED _ PE SIDE');
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
                        \Log::info('Live - Open ' . $live_price_Stock.','.$last_open);
                        if($live_price_Stock > $last_open){
                            if($entry == 0){
                           \Log::info('d1 ' . $symbol);
                           $order = Order::create([
                            'stock_name' => $symbol,
                            'stock' => $nifty_status,
                            'buy_price' => $last_open,
                            'order_price' => $live_price_Stock,
                            'sl' => $last_close,
                            'exit_price' => 0,
                            'status' => 0, //pending
                            'start_time' => now(),
                            'end_time' => "",
                            'qty' => 100,
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
                            \Log::info('Exit Created at ' . now());
                         if($entry == 1){
                                        // Update data into database
                           $pl = $original_buy_price-$live_price_Stock;
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
                                    'profit_loss_amt' => $pl*$original_qty
                                ]);
                         }
                             //   exit;
                         }
                        


    // Wait for 3 seconds before the next iteration
            sleep(3);
                    }
            } // SECOND LAST CANDLE NOT RED
            else{
                \Log::info('SECOND LAST CANDLE NOT RED');
            }

            }
        }


    }



    private function getPriceData($symbol, $inputName = 'nifty')
    {
        $request = new \Illuminate\Http\Request();
        if($symbol == "nifty"){
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
        $isl = $request->input('isl');
        $nifty = $request->input('nifty');
        $authCode = $this->authCode(); 
        if($nifty){
            $nifty= "NSE:NIFTY50-INDEX";
            // print_r('jbuh');
            // exit;
            $url = 'https://api-t1.fyers.in/data/quotes?symbols=' . $nifty;
            $response = Http::withHeaders(['Authorization' => 'TB70PSUQ00-100:' . $authCode,])->get($url);
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
        $response = Http::withHeaders(['Authorization' => 'TB70PSUQ00-100:' . $authCode,])->get($url);
                
        // Decode the JSON response
        $data = json_decode($response->getBody()->getContents());
                
        // Check for errors in the response
        if (isset($data->s) && $data->s == "error") {
            return "err"; // Return error if API response indicates an error
        }
                
        // Process the response
        $quoteData = $data->d[0] ?? null; // Get the first data item
                
        if ($quoteData) {
            $v = $quoteData->v; // Access the nested 'v' object
            $ask = $v->ask ?? null; // Get the ask price
            $bid = $v->bid ?? null; // Get the bid price
            
            // Handle NIFTY case
            if ($isl == 'NIFTY') {
                return $v->cmd->c; // Return command c for NIFTY
            }
                
                // Return ask price or bid price if ask is 0
                return $ask == 0 ? "Price - ₹".$bid :$ask;
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
    
}