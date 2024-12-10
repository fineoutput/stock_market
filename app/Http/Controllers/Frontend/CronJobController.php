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

class CronJobController extends Controller
{

    public function morning_job()
    {
	            $cron_command = '*/1 * * * 1-5 /usr/bin/curl --silent --compressed https://fineoutput.co.in/stock_market/public/Order/createOrder_CE';
				shell_exec('(crontab -l ; echo "'.$cron_command.'") | crontab -');

                $cron_command = '*/1 * * * 1-5 /usr/bin/curl --silent --compressed https://fineoutput.co.in/stock_market/public/Order/createOrder_PE';
				shell_exec('(crontab -l ; echo "'.$cron_command.'") | crontab -');


    }


    public function evening_job()
    {
        $job1= '*/1 * * * 1-5 /usr/bin/curl --silent --compressed https://fineoutput.co.in/stock_market/public/Order/createOrder_CE';
        $job2= '*/1 * * * 1-5 /usr/bin/curl --silent --compressed https://fineoutput.co.in/stock_market/public/Order/createOrder_PE';

        $this->deleteCronJob($job1);    
        $this->deleteCronJob($job2);    

      


    }

    private function deleteCronJob($jobToDelete) {
        // Get current crontab
        $current_crontab = shell_exec('crontab -l 2>/dev/null');
        
        // Split into lines and filter out the job to delete
        $lines = explode("\n", $current_crontab);
        $filtered_lines = array_filter($lines, function ($line) use ($jobToDelete) {
            return trim($line) !== trim($jobToDelete);
        });
        
        // Join filtered lines and update crontab
        $updated_crontab = implode("\n", $filtered_lines);
        shell_exec('echo "' . trim($updated_crontab) . '" | crontab -');
    }





}
