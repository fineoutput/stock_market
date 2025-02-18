<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Admin\OrderController;

class RunEveryMinute extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run:every-minute';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run a specific function every minute';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
         // Place the function you want to execute here
          // Instantiate the controller or use dependency injection
              $controller = new OrderController();

          // Call the function in the controller
             $response = $controller->createOrder();
             return 0;
    }
}