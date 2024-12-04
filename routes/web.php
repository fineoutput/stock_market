<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\TeamController; 
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Admin\CrmController;
use App\Http\Controllers\Auth\adminlogincontroller;
use App\Http\Controllers\Admin\FyersController;
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
});

//historical-data
Route::get('/highest-price-sameday/{date1}/{date2}/{symbol}', [FyersController::class, 'highest_price_sameday']);
Route::get('historical-data', [FyersController::class, 'historical_data']);
Route::get('view-historical-data-CE', [FyersController::class, 'fetchHistoricalData'])->name('view_historical_data_CE');
Route::get('view-historical-data-PE', [FyersController::class, 'fetchHistoricalData'])->name('view_historical_data_PE');
Route::get('continue-tred-{id}', [FyersController::class, 'continue_tred']) ->where('id', 'CE|PE')->name('continue-tred');

});



