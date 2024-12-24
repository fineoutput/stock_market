<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\adminmodel\FyersModal;
use App\Models\Order;
use Illuminate\Support\Facades\DB; // Import the DB facade
// use App\Models\Admin\AdminSidebar1;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\Log;


class FyersController extends Controller
{
    
    public function index(Request $req)
    {    
        $foreachData = FyersModal::wherenull('deleted_at')->latest()->get();
        $title =  "Fyers";
        return view('admin/Fyers.index', compact('foreachData','title'));
    }

    public function create(Request $req)
    {
        $data = new FyersModal();
        $title =  "Add Fyers";
        return view('admin/Fyers.create', compact('data','title'));   
    }

    public function store(Request $request)
    {
        if ($request->id === null) 
        {
                $this->validate($request, ['phone' =>'string|required',
                                           'login_id' =>'string|required',
	                                       'pin' =>'string|required',
	                                       'amount' =>'string|required',
	                                       'option_ce' =>'string|required',
	                                       'option_pe' =>'string|required',
                                           'trading_type' =>'string|required',
                                            ]);
        } else {
               $this->validate($request, ['phone' =>'string|required',
	                                      'login_id' =>'string|required',
	                                      'pin' =>'string|required',
	                                      'amount' =>'string|required',
	                                      'option_ce' =>'string|required',
	                                      'option_pe' =>'string|required',
	                                      'trading_type' =>'string|required',
                                          ]);
                    }
         if ($request->id === null) {
             $Fyers = new FyersModal();
             
         } else {
             $Fyers = FyersModal::where('id', $request->id)->first();
         }
        $userId = Auth::id();
        $Fyers->phone= $request->phone; 
 	    $Fyers->login_id= $request->login_id; 
 	    $Fyers->pin= $request->pin; 
 	    $Fyers->amount= $request->amount; 
 	    $Fyers->option_ce= $request->option_ce; 
 	    $Fyers->option_pe= $request->option_pe; 
 	    $Fyers->trading_type= $request->trading_type; 

        $Fyers->ip = $request->ip();
        $Fyers->is_active = 1;
        $Fyers->date = now()->getTimestamp();
        $Fyers->added_by = $userId;
        $Fyers->save();
        if ($Fyers) {
            if ($request->id === null) {
                return redirect()->route('Fyers.index')->with('success', 'Fyers Added Successfully!');
            } else {
                return redirect()->route('Fyers.index')->with('success', 'Fyers Updated Successfully');
            }
        } else {
                return redirect()->back()->with('error', 'Something Went Wrong');
            }
    }

    public function show(Request $req, $idd)
    {
        if ($req->session()->get('position') == "Super Admin") {
            $id = base64_decode($idd);
            $Fyers = FyersModal::findOrFail($id);
            $Fyers->is_active = !$Fyers->is_active;
            $Fyers->save();
         
            return redirect()->route('Fyers.index')->with('success', 'Status updated Successfully!');
        } else {
            return redirect()->back()->with('error', 'Sorry you dont have Permission to update admin, Only Super admin can change status');
        }
    }

    public function edit(Request $req, $idd)
    {
        $id = base64_decode($idd);
        $title = "Fyers";
        $data = FyersModal::where('id', $id)->first();
        return view('admin/Fyers.create', compact('data','title'));
    }

    public function destroy(Request $req, $idd)
    {
        if ($req->session()->get('position') == "Super Admin") {
            $id = base64_decode($idd);
            $Fyers = FyersModal::findOrFail($id);
            $Fyers->delete();
            return redirect()->route('Fyers.index')->with('success', 'Fyers deleted Successfully!');
        } else {
            return redirect()->back()->with('error', 'Sorry you dont have Permission to delete admin, Only Super admin can change status');
        }
    }

    public function getPrice(Request $request)
    {
        $isl = $request->input('isl');
        $nifty = $request->input('nifty');
        $authCode = $this->authCode(); 
        if($nifty){
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
                return $ask == 0 ? "Price - ₹".$bid : "Price - ₹".$ask;
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
                
    public function historical_data()
    {
        $response1 = $this->historical_data_option(1); // 1 => CE
        $response2 = $this->historical_data_option(2); // 2 => PE
        return response()->json([
            'response1' => json_decode($response1->getContent(), true),
            'response2' => json_decode($response2->getContent(), true),
        ]);
    }

   
    private function historical_data_option($symbolstatus)
    {
        try {
            $currentDate = date('Y-m-d');
            $startTime = config('constants.time.START_TIME');
            $endTime = config('constants.time.END_TIME');
            $startDateTime = $currentDate . ' ' . $startTime;

            $datetime = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
            $endDateTime = $datetime->format('Y-m-d H:i:s');

            $symbolData = DB::table('fyers')->orderBy('id', 'desc')->first();
            $symbol = $symbolstatus == 1 ? $symbolData->option_ce : $symbolData->option_pe;

           // print_r($symbol);
            // exit;
            $response = $this->highest_price_sameday($startDateTime, $endDateTime, $symbol,60);
            // print_r($response);
            // exit;
            $data = json_decode($response, true);
            if (!isset($data['candles']) || empty($data['candles'])) {
                return response()->json(['message' => 'No candle data found'], 404);
            }

            $candles = $data['candles'];
            $lastTwoCandles = array_slice($candles, -2);

            // Extract second last and last candle data
            $secondLastCandle = $lastTwoCandles[0];
            $lastCandle = $lastTwoCandles[1];

            // Prepare second last candle details
            $secondLastTimestamp = $secondLastCandle[0];
            $secondLastDateTime = new DateTime("@$secondLastTimestamp");
            $secondLastDateTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
            $secondLastFormattedTime = $secondLastDateTime->format('Y-m-d H:i:s');
            $secondLastOpen = $secondLastCandle[1];
            $secondLastHigh = $secondLastCandle[2];
            $secondLastLow = $secondLastCandle[3];
            $secondLastClose = $secondLastCandle[4];

            if($secondLastOpen - $secondLastClose > 0){
                $op = 1; //RED CANDLE
            }
            else{
                $op = 0;
            }
           

            // Prepare last candle details
            $lastcandleLastTimestamp = $lastCandle[0];
            $lastLastDateTime = new DateTime("@$lastcandleLastTimestamp");
            $lastLastDateTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
            $lastLastFormattedTime = $lastLastDateTime->format('Y-m-d H:i:s');
            $lastOpen = $lastCandle[1];
            $lastHigh = $lastCandle[2];
            $lastLow = $lastCandle[3];
            $lastClose = $lastCandle[4];

            if($lastOpen - $lastClose > 0){
                $opc = 1; //RED CANDLE
            }
            else{
                $opc = 0; //GREEN CANDLE
            }

            // Check if Second last data already exists
            $existsSecondLast = DB::table('historical_data')
                ->where('date', $secondLastFormattedTime)
                ->where('tred_option', $symbolstatus)
                ->exists();

            if (!$existsSecondLast) {
              
                // Insert data into database
                DB::table('historical_data')->insert([
                    'stock' => $symbol,
                    'date' => $secondLastFormattedTime,
                    'open' => $secondLastOpen,
                    'close' => $secondLastClose,
                    'high' => $secondLastCandle[2],
                    'low' => $secondLastCandle[3],
                    'open_status' => $op,
                    'tred_option' => $symbolstatus, // 1 => CE, 2 => PE
                ]);
            }
            else{
                  // Update data into database
                  DB::table('historical_data')
                  ->where('date', $secondLastFormattedTime) 
                  ->where('tred_option', $symbolstatus)
                  ->update([
                    'stock' => $symbol,
                    'date' => $secondLastFormattedTime,
                    'open' => $secondLastOpen,
                    'close' => $secondLastClose,
                    'high' => $secondLastCandle[2],
                    'low' => $secondLastCandle[3],
                    'open_status' => $op,
                    'tred_option' => $symbolstatus, // 1 => PE, 2 => CE
                ]);
            }

        // Check if last data already exists
               $existsLastData = DB::table('historical_data')
               ->where('date', $lastLastFormattedTime)
               ->where('tred_option', $symbolstatus)
               ->exists();

           if (!$existsLastData) {
               // Insert data into database
               DB::table('historical_data')->insert([
                    'stock' => $symbol,
                   'date' => $lastLastFormattedTime,
                   'open' => $lastOpen,
                   'close' => $lastClose,
                   'high' => $lastHigh,
                   'low' => $lastLow,
                   'open_status' => $opc,
                   'tred_option' => $symbolstatus, // 1 => PE, 2 => CE
               ]);
           }
           else{
                 // Update data into database
                 DB::table('historical_data')
                 ->where('date', $lastLastFormattedTime) 
                 ->where('tred_option', $symbolstatus)
                 ->update([
                    'stock' => $symbol,
                   'date' => $lastLastFormattedTime,
                   'open' => $lastOpen,
                   'close' => $lastClose,
                   'high' => $lastHigh,
                   'low' => $lastLow,
                   'open_status' => $opc,
                   'tred_option' => $symbolstatus, // 1 => PE, 2 => CE
               ]);
           }


            return response()->json([
                'message' => 'Candle data processed successfully',
                'symbol' => $symbol,
                'date' => $secondLastFormattedTime,
                'status' => "Success",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error processing candle data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function historical_data_5min()
    {
        $response1 = $this->historical_data_option_5min(1); // 1 => CE
        $response2 = $this->historical_data_option_5min(2); // 2 => PE
        return response()->json([
            'response1' => json_decode($response1->getContent(), true),
            'response2' => json_decode($response2->getContent(), true),
        ]);
    }

    private function historical_data_option_5min($symbolstatus)
    {
        try {
            $currentDate = date('Y-m-d');
            $startTime = config('constants.time.START_TIME');
            $endTime = config('constants.time.END_TIME');
            $startDateTime = $currentDate . ' ' . $startTime;

            $datetime = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
            $endDateTime = $datetime->format('Y-m-d H:i:s');

            $symbolData = DB::table('fyers')->orderBy('id', 'desc')->first();
            $symbol = $symbolstatus == 1 ? $symbolData->option_ce : $symbolData->option_pe;

           // print_r($symbol);
            // exit;
            $response = $this->highest_price_sameday($startDateTime, $endDateTime, $symbol,5);
            \Log::info('HISTORY DATA SAVE ' . $symbol.','.$endDateTime);
            // print_r($response);
            // exit;
            $data = json_decode($response, true);
            if (!isset($data['candles']) || empty($data['candles'])) {
                Log::error("No candle data found in ".$symbol);
                return response()->json(['message' => 'No candle data found'], 404);
            }

            $candles = $data['candles'];
            $lastTwoCandles = array_slice($candles, -2);

            // Extract second last and last candle data
            $secondLastCandle = $lastTwoCandles[0];
            $lastCandle = $lastTwoCandles[1];

            // Prepare second last candle details
            $secondLastTimestamp = $secondLastCandle[0];
            $secondLastDateTime = new DateTime("@$secondLastTimestamp");
            $secondLastDateTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
            $secondLastFormattedTime = $secondLastDateTime->format('Y-m-d H:i:s');
            $secondLastOpen = $secondLastCandle[1];
            $secondLastHigh = $secondLastCandle[2];
            $secondLastLow = $secondLastCandle[3];
            $secondLastClose = $secondLastCandle[4];

            if($secondLastOpen - $secondLastClose > 0){
                $op = 1; //RED CANDLE
            }
            else{
                $op = 0;
            }
           

            // Prepare last candle details
            $lastcandleLastTimestamp = $lastCandle[0];
            $lastLastDateTime = new DateTime("@$lastcandleLastTimestamp");
            $lastLastDateTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
            $lastLastFormattedTime = $lastLastDateTime->format('Y-m-d H:i:s');
            $lastOpen = $lastCandle[1];
            $lastHigh = $lastCandle[2];
            $lastLow = $lastCandle[3];
            $lastClose = $lastCandle[4];

            if($lastOpen - $lastClose > 0){
                $opc = 1; //RED CANDLE
            }
            else{
                $opc = 0; //GREEN CANDLE
            }

            \Log::info('HISTORY DATA SAVE EPOCH ' . $secondLastTimestamp.','.$lastcandleLastTimestamp);
            // Check if Second last data already exists
            $existsSecondLast = DB::table('historical_data_5min')
                ->where('timeepoch', $secondLastTimestamp)
                ->where('tred_option', $symbolstatus)
                ->exists();

            if (!$existsSecondLast) {
              
                // Insert data into database
                DB::table('historical_data_5min')->insert([
                    'stock' => $symbol,
                    'date' => $secondLastFormattedTime,
                    'open' => $secondLastOpen,
                    'close' => $secondLastClose,
                    'high' => $secondLastCandle[2],
                    'low' => $secondLastCandle[3],
                    'open_status' => $op,
                    'tred_option' => $symbolstatus, // 1 => CE, 2 => PE
                    'lastorsecondlast' => 2, // 1 for last 2 for second last
                    'timeepoch' => $secondLastTimestamp,
                ]);
            }
            else{
                  // Update data into database
                  DB::table('historical_data_5min')
                  ->where('timeepoch', $secondLastTimestamp) 
                  ->where('tred_option', $symbolstatus)
                  ->update([
                    'stock' => $symbol,
                    'date' => $secondLastFormattedTime,
                    'open' => $secondLastOpen,
                    'close' => $secondLastClose,
                    'high' => $secondLastCandle[2],
                    'low' => $secondLastCandle[3],
                    'open_status' => $op,
                    'tred_option' => $symbolstatus, //  1 => CE, 2 => PE
                ]);

                //check if order exist in table if yes then update SL
                $liveorder = Order::wherenull('deleted_at')->where('stock_name', $symbol)->where('status', 0)->where('stock', $symbolstatus)->first();
                if($liveorder != null){
                    if($op == 1){
                        $sl = $secondLastClose;
                    }
                    else{
                        $sl = $secondLastOpen;
                    }
                    Log::info("SL UPDATED BY NEW CANDLE" . $sl);
                    DB::table('tbl_order')
                    ->where('id', $liveorder->id) 
                    ->update([
                      'sl' => $sl //  1 => CE, 2 => PE
                  ]);
  

                } // if end of order exist or not


            }

        // Check if last data already exists
               $existsLastData = DB::table('historical_data_5min')
               ->where('timeepoch', $lastcandleLastTimestamp)
               ->where('tred_option', $symbolstatus)
               ->exists();

           if (!$existsLastData) {
               // Insert data into database
               \Log::info('HISTORY DATA SAVE INSERTED ' . $symbol.','.$lastcandleLastTimestamp);
               DB::table('historical_data_5min')->insert([
                    'stock' => $symbol,
                   'date' => $lastLastFormattedTime,
                   'open' => $lastOpen,
                   'close' => $lastClose,
                   'high' => $lastHigh,
                   'low' => $lastLow,
                   'open_status' => $opc,
                   'tred_option' => $symbolstatus, // 1 => PE, 2 => CE
                   'lastorsecondlast' => 1, // 1 for last 2 for second last
                   'timeepoch' => $lastcandleLastTimestamp,
               ]);
           }
           else{
                 // Update data into database
                 DB::table('historical_data_5min')
                 ->where('timeepoch', $lastcandleLastTimestamp) 
                 ->where('tred_option', $symbolstatus)
                 ->update([
                    'stock' => $symbol,
                   'date' => $lastLastFormattedTime,
                   'open' => $lastOpen,
                   'close' => $lastClose,
                   'high' => $lastHigh,
                   'low' => $lastLow,
                   'open_status' => $opc,
                   'tred_option' => $symbolstatus, // 1 => PE, 2 => CE
               ]);
           }


            return response()->json([
                'message' => 'Candle data processed successfully',
                'symbol' => $symbol,
                'date' => $secondLastFormattedTime,
                'status' => "Success",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error processing candle data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    private function highest_price_sameday($date1,$date2,$symbol,$time)
    {

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

        $auth_code = $this->authCode();
        $res = $time;
        $date_format = 0;
        $range_from = $d1;
        $range_to = $d2;

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
            'Authorization: TB70PSUQ00-100:'.$auth_code
            ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            // $res = json_decode($response);
            // $candles = $res->candles;
            // foreach($candles as $cc){
            // 	$epoch = $cc[0];
            // 	$epochTime = $epoch; // Example epoch time
            // $date = new DateTime("@$epochTime"); // The "@" symbol tells DateTime to interpret the value as a Unix timestamp
            // $date->setTimezone(new DateTimeZone('Asia/Kolkata')); // Set timezone to IST
            // echo $date->format('Y-m-d H:i:s');
            //
            // // 	$dateepoch = new DateTime();
            // // $dateepoch->setTimestamp($epoch);
            // // echo $dateepoch->format('Y-m-d H:i:s');
            // echo "<br/>";
            // echo $cc[2];
            //
            // echo "<br/>";echo "<br/>";
            // }
            // print_r($candles);
            return $response;

    }

    public function fetchHistoricalData()
    {
        $currentUrl = request()->url();
        if (str_contains($currentUrl, 'view-historical-data-PE')) {
        $historicalData = DB::table('historical_data')->where('tred_option', '=', '2')->orderBy('id', 'desc')->get();
        return view('admin.Fyers.view_historical_data', ['historicalData' => $historicalData, 'title'=>'Historical Data PE ']);
        }
        else {
            $historicalData = DB::table('historical_data')->where('tred_option', '=', '1')->orderBy('id', 'desc')->get();
            return view('admin.Fyers.view_historical_data', ['historicalData' => $historicalData, 'title'=>'Historical Data CE ']);
            }
    }

    public function fetchHistoricalData_5min()
    {
        $currentUrl = request()->url();
        if (str_contains($currentUrl, 'view-historical-data-PE_5min')) {
        $historicalData = DB::table('historical_data_5min')->where('tred_option', '=', '2')->orderBy('id', 'desc')->get();
        return view('admin.Fyers.view_historical_data', ['historicalData' => $historicalData, 'title'=>'Historical Data PE 5 Min ']);
        }
        else {
            $historicalData = DB::table('historical_data_5min')->where('tred_option', '=', '1')->orderBy('id', 'desc')->get();
            return view('admin.Fyers.view_historical_data', ['historicalData' => $historicalData, 'title'=>'Historical Data CE 5 Min ']);
            }
    }

    public function continue_tred()
    {
        $symbolData = DB::table('fyers')->orderBy('id', 'desc')->first();
        $main_symbol = 'NSE:NIFTY50-INDEX';
        $price = $this->getPriceData($main_symbol);
        $price = $price['open_price']-$price['lp'];
        if ($price < 0) 
        {
         $symbol = $symbolData->option_ce;
         $stock = 2;
        }
        else
        {
            $symbol = $symbolData->option_pe;
            $stock = 1;      
        }
        $historical_data = DB::table('historical_data')->where('tred_option',$stock)->orderBy('id', 'desc')->first();
        $previous_ord = DB::table('tbl_order')->where('stock',$stock)->orderBy('id', 'desc')->first();
        
        date_default_timezone_set('Asia/Kolkata');
        $currentDateTime = date('Y-m-d H:i:s');

        for ($i = 1; $i <= 17; $i++) {
                $price = $this->getPriceData($symbol);
                $stock_price = $price['bid'];
            if(empty($previous_ord) || !isset($previous_ord->status) || $previous_ord->status==1)
            {
                if ($stock == 2 && $stock_price > $historical_data->open) {
                    $status = 0;
                }
                if ($stock == 1 && $stock_price < $historical_data->open) {
                    $status = 0;
                }
               
             if($status == 0){
                $lot_price = $stock * 50;
                  if($lot_price <= $symbolData->amount){
                    $quantity = round($lot_price / $symbolData->amount, 2);
                    DB::table('tbl_order')->insert([
                    'stock' => $stock, 
                    'buy_price' => $stock_price, 
                    'sl' => $historical_data->close, 
                    'status' => $status, 
                    'start_time' => $currentDateTime,  
                    'qty' => $quantity,
                    'profit_loss_status' => 0,  
                    ]);    
               }
               else{
                return response()->json([
                    'message' => 'You have less amount',
                    'status' => 'success'
                ]);
                
               }
            }
            }
        $previous_ord = DB::table('tbl_order')->where('stock',$stock)->orderBy('id', 'desc')->first();
            if($previous_ord->status==0){
                $status = 0;
                if ($stock == 2 && $stock_price < $previous_ord->sl) {
                    $status = 1;
                }
                if ($stock == 1 && $stock_price > $previous_ord->sl) {
                    $status = 1;
                }  
                if($status == 1){
                    $profitLossAmount = $previous_ord->buy_price - $stock_price * $previous_ord->buy_price;
                    if ($profitLossAmount <= 0) {
                        $profitLossStatus = 1;
                    } else {
                        $profitLossStatus = 0;
                    }
                DB::table('tbl_order')
                    ->where('id', $previous_ord->id)
                    ->update([
                        'exit_price' => $stock_price,
                        'status' => $status,
                        'end_time' => $currentDateTime,
                        'profit_loss_status' => $profitLossStatus,
                        'profit_loss_amt' => $profitLossAmount,
                    ]);
                }

                // echo 'order kiya huaa hai';exit;
            }
          sleep(3);
        }
        return response()->json([
            'message' => 'Work complete',
            'status' => 'success'
        ]);
        
    }

    private function getPriceData($symbol, $inputName = 'nifty')
    {
        $request = new \Illuminate\Http\Request();
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
        return [
            'error' => 'Unexpected response format or invalid data.',
        ];
    }

}

        