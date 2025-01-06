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
	            // $cron_command = '*/1 * * * 1-5 /usr/bin/curl --silent --compressed https://fineoutput.co.in/stock_market/public/Order/createOrder_CE';
				// shell_exec('(crontab -l ; echo "'.$cron_command.'") | crontab -');

                // $cron_command = '*/1 * * * 1-5 /usr/bin/curl --silent --compressed https://fineoutput.co.in/stock_market/public/Order/createOrder_PE';
				// shell_exec('(crontab -l ; echo "'.$cron_command.'") | crontab -');

                //nifty historic data cron job
                $cron_command = '0 * * * 1-5 /usr/bin/curl --silent --compressed https://fineoutput.co.in/stock_market/public/historical-data';
				shell_exec('(crontab -l ; echo "'.$cron_command.'") | crontab -');

                $cron_command = '*/5 * * * 1-5 /usr/bin/curl --silent --compressed https://fineoutput.co.in/stock_market/public/historical-data-5min';
				shell_exec('(crontab -l ; echo "'.$cron_command.'") | crontab -');

                 //Bank nifty historic data cron job
                $cron_command = '0 * * * 1-5 /usr/bin/curl --silent --compressed https://fineoutput.co.in/stock_market/public/bank-historical-data';
				shell_exec('(crontab -l ; echo "'.$cron_command.'") | crontab -');

                $cron_command = '*/5 * * * 1-5 /usr/bin/curl --silent --compressed https://fineoutput.co.in/stock_market/public/bank-historical-data-5min';
				shell_exec('(crontab -l ; echo "'.$cron_command.'") | crontab -');

                //stock historic data cron job
                $cron_command = '0 * * * 1-5 /usr/bin/curl --silent --compressed https://fineoutput.co.in/stock_market/public/stock-historical-data';
				shell_exec('(crontab -l ; echo "'.$cron_command.'") | crontab -');

                $cron_command = '*/5 * * * 1-5 /usr/bin/curl --silent --compressed https://fineoutput.co.in/stock_market/public/stock-historical-data-5min';
				shell_exec('(crontab -l ; echo "'.$cron_command.'") | crontab -');

                 //order placing and checking cron job
                $cron_command = '*/1 * * * 1-5 /usr/bin/curl --silent --compressed https://fineoutput.co.in/stock_market/public/Order/createOrder_CE_5min';
				shell_exec('(crontab -l ; echo "'.$cron_command.'") | crontab -');

                $cron_command = '*/1 * * * 1-5 /usr/bin/curl --silent --compressed https://fineoutput.co.in/stock_market/public/Order/createOrder_PE_5min';
				shell_exec('(crontab -l ; echo "'.$cron_command.'") | crontab -');

               


    }


    public function evening_job()
    {
        // $job1= '*/1 * * * 1-5 /usr/bin/curl --silent --compressed https://fineoutput.co.in/stock_market/public/Order/createOrder_CE';
        // $job2= '*/1 * * * 1-5 /usr/bin/curl --silent --compressed https://fineoutput.co.in/stock_market/public/Order/createOrder_PE';

        $job1= '*/1 * * * 1-5 /usr/bin/curl --silent --compressed https://fineoutput.co.in/stock_market/public/Order/createOrder_CE_5min';
        $job2= '*/1 * * * 1-5 /usr/bin/curl --silent --compressed https://fineoutput.co.in/stock_market/public/Order/createOrder_PE_5min';

        //nifty historic data cron job remove
        $job3= '0 * * * 1-5 /usr/bin/curl --silent --compressed https://fineoutput.co.in/stock_market/public/historical-data';
        $job4= '*/5 * * * 1-5 /usr/bin/curl --silent --compressed https://fineoutput.co.in/stock_market/public/historical-data-5min';

         //bank nifty historic data cron job remove
         $job5= '0 * * * 1-5 /usr/bin/curl --silent --compressed https://fineoutput.co.in/stock_market/public/bank-historical-data';
         $job6= '*/5 * * * 1-5 /usr/bin/curl --silent --compressed https://fineoutput.co.in/stock_market/public/bank-historical-data-5min';


         
          //Stock option historic data cron job remove
        $job7= '0 * * * 1-5 /usr/bin/curl --silent --compressed https://fineoutput.co.in/stock_market/public/stock-historical-data';
        $job8= '*/5 * * * 1-5 /usr/bin/curl --silent --compressed https://fineoutput.co.in/stock_market/public/stock-historical-data-5min';

        $this->deleteCronJob($job1);    
        $this->deleteCronJob($job2);    
        $this->deleteCronJob($job3);    
        $this->deleteCronJob($job4);    
        $this->deleteCronJob($job5);    
        $this->deleteCronJob($job6);    
        $this->deleteCronJob($job7);    
        $this->deleteCronJob($job8);    

      


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
