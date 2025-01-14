<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\adminmodel\FyersModal;
use App\Models\Order;
use App\Models\Historical;
use Illuminate\Support\Facades\DB; // Import the DB facade
// use App\Models\Admin\AdminSidebar1;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use DateTime;
use DateTimeZone;
use Carbon\Carbon;


class OrderController extends Controller
{
    public function viewOrder()
    {
        $Orders = Order::orderBY("id","DESC")->get();

        $count['todayOrdersCount'] = Order::whereDate('created_at', Carbon::today())->count();

        // Calculate total profit or loss for today's orders
$cc= Order::whereDate('created_at', Carbon::today())->get();
$p_amount = 0;
foreach($cc  as $cc2){
$pl = $cc2->profit_loss_status;
if($pl == 1){
    $p_amount = $p_amount + $cc2->profit_loss_amt;
}
if($pl == 0){
    $p_amount = $p_amount - $cc2->profit_loss_amt;
}
}

$count['todayProfitLoss'] = $p_amount;
// Assign profit or loss status
$count['profitOrLoss'] = $p_amount > 0 ? 'Profit' : ($p_amount < 0 ? 'Loss' : 'Neutral');
// Assign profit or loss status
        



        $title =  "Orders";
        return view('admin/order/view_order', ['data' => $Orders, 'title' => $title, 'count' => $count]);

    }

    public function createOrder()
    {
          // Your function logic here
        //   \Log::info('Task executed at anay ' . now());
        //   exit;
        //check if order exists or not
        $runningOrder = Order::where('status', 0)->first();

        if($runningOrder){
            // ORDER IS IS PROCESS

        }
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
                }
                else{
                    // NIFTY IS RED/NEGATIVE
                    $nifty_status = 0;
                    $symbol = $symbolData->option_pe;
                }

            //CHECK LAST OPEN TO 
            $secondLast = Historical::wherenull('deleted_at')->where('tred_option', $nifty_status)->skip(1)
            ->take(1)->first();
            if($secondLast){

                    $last_open = $secondLast->open;
                    $last_close = $secondLast->close;
                    $iterations = 18; // Set the number of times the loop should run

                    for ($i = 0; $i < $iterations; $i++) {
                        $live_price_Stock = $this->getPriceData($symbol);

                        if($live_price_Stock > $last_open){
                            if($entry == 0){
                           \Log::info('Entry Created at ' . now());
                           $entry = 1;
                            }
                            //   exit;
                        }
                        if($live_price_Stock < $last_close){
                            \Log::info('Exit Created at ' . now());
                         $entry= 0;
                             //   exit;
                         }
                        


    // Wait for 3 seconds before the next iteration
            sleep(3);
                    }
            }

            }
        }


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
                return $ask == 0 ? "Price - ₹".$bid : "Price - ₹".$ask;
            }
                
            return null; // Return null if no data found
                    
    }
    public function deleteOrder($idd, Request $req)
    {
        $id = base64_decode($idd);
        $OrderData = Order::where('id', $id)->first();
        if (!empty($OrderData)) {
            $OrderData->delete();
            // if (!empty($img)) {
            // 	unlink($img);
            // }
            return Redirect('/viewOrder')->with('success', 'Order Deleted Successfully.');
        } else {
            return Redirect('/viewOrder')->with('error', 'Some Error Occurred.');
        }

    }   

    public function authCode()
    {
        // Fetch the latest auth_code from the tbl_config table
        $result = DB::table('tbl_config')->select('auth_code')->orderBy('id', 'DESC')->first(); // Get the first record
        // Return the auth_code if available, otherwise return null
        return $result ? $result->auth_code : null;
    }
    


}