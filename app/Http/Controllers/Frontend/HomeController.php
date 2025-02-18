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

class HomeController extends Controller
{
    // ============================= START INDEX ============================ 
    public function index(Request $req)
    {
     
        return view('welcome')->withTitle('');
    }


    public function redirect(Request $req)
    {

        $s = $req->query('s');
        if ($s == "ok") {
            // Get IP address
            $ip = $req->ip();

            // Set timezone and get current date
            date_default_timezone_set("Asia/Calcutta");
            $cur_date = Carbon::now()->format('Y-m-d H:i:s');

            $auth_code = $req->query('auth_code');

            // Make HTTP POST request to validate auth code using Laravel's Http facade
            $response = Http::post('https://api.fyers.in/api/v2/validate-authcode', [
                'grant_type' => 'authorization_code',
                'appIdHash' => config('constants.options.HASH'),
                'code' => $auth_code
            ]);

            $r2 = json_decode($response->body());

            // Log messages (optional)
            // Log::error('CHECKING - '.$response->body());
            // Log::error('CHECKING - '.$r2->access_token);

            if ($r2->s == "ok") {
                $access_token = $r2->access_token;

                // Insert data into the database
                $data_insert = [
                    'auth_code' => trim($access_token),
                    'ip' => $ip,
                    'date' => $cur_date
                ];

                $last_id = DB::table('tbl_config')->insertGetId($data_insert);

                if ($last_id != 0) {
                    return "Data Updated Successfully";
                }
            }

            if ($r2->s == "error") {
                return $r2->message;
            }
        }
    }
     
       
    
}
