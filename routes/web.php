<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\RmentryController;
use App\Http\Controllers\SupplierSetupController;
use App\Http\Controllers\MaterialSetupController;
use App\Http\Controllers\QtfSetupController;
use App\Http\Controllers\WipentryController;
use App\Http\Controllers\BackwardController;
use App\Http\Controllers\ForwardController;
use App\Http\Controllers\AdjustmentController;
use App\Http\Controllers\PackageEntryController;
use App\Http\Controllers\ShipmentEntryController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\BlendingController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\GuidanceController;
use App\Http\Controllers\StorageSetupController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\RmreportController;
use App\Http\Controllers\PlantSetupController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::group(['middleware' => ['auth']], function() {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/admin', [AdminController::class, 'index'])->name('admin');

    Route::resource('dashboard', HomeController::class);
    Route::resource('admin', AdminController::class);

    Route::resource('user', UserController::class);
    Route::resource('permission', PermissionController::class);
    Route::resource('role', RoleController::class);

    Route::resource('rmentry', RmentryController::class);
    Route::resource('wipentry', WipentryController::class);
    Route::resource('packageentry', PackageEntryController::class);
    Route::resource('shipmententry', ShipmentEntryController::class);

    Route::resource('suppliersetup', SupplierSetupController::class);
    Route::resource('materialsetup', MaterialSetupController::class);
    Route::resource('qtfsetup', QtfSetupController::class);

    Route::resource('backward', BackwardController::class);
    Route::resource('forward', ForwardController::class);
    Route::resource('adjustment', AdjustmentController::class);

    Route::resource('tsreport', ReportController::class);
    Route::resource('stock', StockController::class);

    Route::resource('blending', BlendingController::class);
    Route::resource('transfer', TransferController::class);

    Route::resource('guidance', GuidanceController::class);
    Route::resource('storagesetup', StorageSetupController::class);
    Route::resource('plantsetup', PlantSetupController::class);

    Route::resource('rmreport', RmreportController::class);

    Route::post('/send-email', [EmailController::class, 'sendEmail']);

});
