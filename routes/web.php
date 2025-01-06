<?php

use App\Models\Order;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\TeamController; 
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Admin\CrmController;
use App\Http\Controllers\Auth\adminlogincontroller;
use App\Http\Controllers\Admin\FyersController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Frontend\OrderAutoController;
use App\Http\Controllers\Frontend\CronJobController;
// use App\Http\Controllers\Admin\LoginController;
// use App\Http\Controllers\Admin\SliderController;
// use App\Http\Controllers\Admin\UserController;
// use App\Http\Controllers\Admin\CategoryController;
// use App\Http\Controllers\Admin\ProductController;
// use App\Http\Controllers\Admin\ContactUsController;
// use App\Http\Controllers\Admin\OrderController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/clear-cache', function () {
//     $exitCode = Artisan::call('cache:clear');
//     // $exitCode = Artisan::call('route:clear');
//     // $exitCode = Artisan::call('config:clear');
//     // $exitCode = Artisan::call('view:clear');
//     // return what you want
// });
//=========================================== FRONTEND =====================================================

Route::group(['prefix' => '/'], function () {
    Route::get('/', [HomeController::class, 'index'])->name('/');
});


Route::get('/redirect', [HomeController::class, 'redirect'])->name('redirect');
Route::get('/getPrice', [FyersController::class, 'getPrice'])->name('getPrice');

//======================================= ADMIN ===================================================
Route::group(['prifix' => 'admin'], function () {
    Route::group(['middleware'=>'admin.guest'],function(){

        Route::get('/admin_index', [adminlogincontroller::class, 'admin_login'])->name('admin_login');
        Route::post('/login_process', [adminlogincontroller::class, 'admin_login_process'])->name('admin_login_process');

    });
Route::group(['middleware'=>'admin.auth'],function(){

 Route::get('/index', [TeamController::class, 'admin_index'])->name('admin_index');
 Route::get('/logout', [adminlogincontroller::class, 'admin_logout'])->name('admin_logout');
 Route::get('/profile', [adminlogincontroller::class, 'admin_profile'])->name('admin_profile');
 Route::get('/view_change_password', [adminlogincontroller::class, 'admin_change_pass_view'])->name('view_change_password');
 Route::post('/admin_change_password', [adminlogincontroller::class, 'admin_change_password'])->name('admin_change_password');

        // Admin Team ------------------------

Route::get('/view_team', [TeamController::class, 'view_team'])->name('view_team');
Route::get('/add_team_view', [TeamController::class, 'add_team_view'])->name('add_team_view');
Route::post('/add_team_process', [TeamController::class, 'add_team_process'])->name('add_team_process');
Route::get('/UpdateTeamStatus/{status}/{id}', [TeamController::class, 'UpdateTeamStatus'])->name('UpdateTeamStatus');
Route::get('/deleteTeam/{id}', [TeamController::class, 'deleteTeam'])->name('deleteTeam');



// Admin CRM settings ------------------------
Route::get('/add_settings', [CrmController::class, 'add_settings'])->name('add_settings');
Route::get('/view_settings', [CrmController::class, 'view_settings'])->name('view_settings');
Route::get('/update_settings/{id}', [CrmController::class, 'update_settings'])->name('update_settings');
Route::post('/add_settings_process', [CrmController::class, 'add_settings_process'])->name('add_settings_process');
Route::post('/update_settings_process/{id}', [CrmController::class, 'update_settings_process'])->name('update_settings_process');
Route::get('/deletesetting/{id}', [CrmController::class, 'deletesetting'])->name('deletesetting');


//---Fyers ----
Route::resource('Fyers', FyersController::class);
Route::get('/Fyers/update/{id}', [FyersController::class,'updateStatus'])->name('Fyers.update.status');
Route::delete('/Fyers/destroy/{id}', [FyersController::class,'destroy'])->name('Fyers.destroy');


//---Orders ----
// Route::resource('Order', OrderController::class);
Route::get('/Order/viewOrder', [OrderController::class,'viewOrder'])->name('viewOrder');
// Route::get('/Order/createOrder', [OrderController::class,'createOrder'])->name('createOrder');
// Route::get('/Order/getPriceData/{id}', [OrderController::class,'getPriceData'])->name('getPriceData');
// Route::delete('/Fyers/destroy/{id}', [FyersController::class,'destroy'])->name('Fyers.destroy');
});

//historical-data
Route::get('/highest-price-sameday/{date1}/{date2}/{symbol}/{time}', [FyersController::class, 'highest_price_sameday']);

//nifty
Route::get('historical-data', [FyersController::class, 'historical_data']);
Route::get('historical-data-5min', [FyersController::class, 'historical_data_5min']);
Route::get('view-historical-data-CE', [FyersController::class, 'fetchHistoricalData'])->name('view_historical_data_CE');
Route::get('view-historical-data-PE', [FyersController::class, 'fetchHistoricalData'])->name('view_historical_data_PE');
Route::get('view-historical-data-CE_5min', [FyersController::class, 'fetchHistoricalData_5min'])->name('view_historical_data_CE_5min');
Route::get('view-historical-data-PE_5min', [FyersController::class, 'fetchHistoricalData_5min'])->name('view_historical_data_PE_5min');
//banknifty
Route::get('bank-historical-data', [FyersController::class, 'bank_historical_data']);
Route::get('bank-historical-data-5min', [FyersController::class, 'bank_historical_data_5min']);
Route::get('view-bank-historical-data-CE', [FyersController::class, 'fetchbankHistoricalData'])->name('view_bank_historical_data_CE');
Route::get('view-bank-historical-data-PE', [FyersController::class, 'fetchbankHistoricalData'])->name('view_bank_historical_data_PE');
Route::get('view-bank-historical-data-CE_5min', [FyersController::class, 'fetchbankHistoricalData_5min'])->name('view_bank_historical_data_CE_5min');
Route::get('view-bank-historical-data-PE_5min', [FyersController::class, 'fetchbankHistoricalData_5min'])->name('view_bank_historical_data_PE_5min');
//stock options
Route::get('stock-historical-data', [FyersController::class, 'stock_historical_data']);
Route::get('stock-historical-data-5min', [FyersController::class, 'stock_historical_data_5min']);
Route::get('view-stock-historical-data-CE', [FyersController::class, 'fetchstockHistoricalData'])->name('view_stock_historical_data_CE');
Route::get('view-stock-historical-data-PE', [FyersController::class, 'fetchstockHistoricalData'])->name('view_stock_historical_data_PE');
Route::get('view-stock-historical-data-CE_5min', [FyersController::class, 'fetchstockHistoricalData_5min'])->name('view_stock_historical_data_CE_5min');
Route::get('view-stock-historical-data-PE_5min', [FyersController::class, 'fetchstockHistoricalData_5min'])->name('view_stock_historical_data_PE_5min');

Route::get('continue-tred', [FyersController::class, 'continue_tred'])->name('continue-tred');
Route::get('historical-data-1m', [FyersController::class, 'historical_data_1m']);


Route::get('/Order/createOrder_CE', [OrderAutoController::class,'createOrder_CE'])->name('createOrder_CE');
Route::get('/Order/createOrder_PE', [OrderAutoController::class,'createOrder_PE'])->name('createOrder_PE');

Route::get('/Order/createOrder_CE_5min', [OrderAutoController::class,'createOrder_CE_5min'])->name('createOrder_CE_5min');
Route::get('/Order/createOrder_PE_5min', [OrderAutoController::class,'createOrder_PE_5min'])->name('createOrder_PE_5min');

// Route::get('/Order/nifty_current/{time}', [OrderAutoController::class,'nifty_current'])->name('nifty_current');

Route::get('/CronJobController/morning_job', [CronJobController::class,'morning_job'])->name('morning_job');
Route::get('/CronJobController/evening_job', [CronJobController::class,'evening_job'])->name('evening_job');

});



