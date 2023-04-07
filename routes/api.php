<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CleanController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\NasiyaController;
use App\Http\Controllers\Api\FilialController;
use App\Http\Controllers\Api\CostumerController;
use App\Http\Controllers\Api\RecallController;
use App\Http\Controllers\Api\DavomatController;
use App\Http\Controllers\Api\QadoqlashController;
use App\Http\Controllers\Api\TokchaController;
use App\Http\Controllers\Api\MillatController;
use App\Http\Controllers\Api\KirimController;
use App\Http\Controllers\Api\KvitansiyaController;
use App\Http\Controllers\Api\DarvozaController;
use App\Http\Controllers\Api\SmsSendedController;
use App\Http\Controllers\Api\XizmatlarController;
use App\Http\Controllers\Api\CallController;
use App\Http\Controllers\Api\CommonController;
use App\Http\Controllers\Api\KorsatkichController;
use App\Http\Controllers\Api\BuyorderController;
use App\Http\Controllers\Api\OmborController;
use App\Http\Controllers\Api\ManbalarController;
use App\Http\Controllers\FaceIdController;
use App\Http\Controllers\FingerprintController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::get('/login', function () {
    return null;
});

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('/image/url', [CommonController::class, 'image']);

    Route::get('/clean/transport', [CleanController::class, 'transport']);
    Route::put('/clean/edit_price/{order_id}', [CleanController::class, 'cleanPrice']);
    Route::get('/clean/tayyor', [CleanController::class, 'tayyor']);
    Route::post('/clean/create/{order_id}', [CleanController::class, 'store']);
    Route::get('/clean/{costumer_id}/{order_id}', [CleanController::class, 'cleans']);
    Route::get('/clean/joriy', [CleanController::class, 'joriy']);
    Route::get('/clean/joriy-filter', [CleanController::class, 'joriyFilter']);
    Route::get('/joriy_one/{order_id}', [CleanController::class, 'joriyOne']);

    Route::get('/clean_count/{order_id}', [CleanController::class, 'cleanCount']);

    Route::post('/clean/yuvildi/{clean_id}', [CleanController::class, 'yuvildi']);

    Route::resource('/clean', CleanController::class);

    Route::post('otest', [OrderController::class, 'test']);

    Route::post('/order/add_one_product/{order_id}/{x_id}', [OrderController::class, 'addOne']);
    Route::get('/order/new', [OrderController::class, 'new']);
    Route::post('/order/new', [OrderController::class, 'orderCancle']);
    Route::post('/order/confirmation/{order_id}', [OrderController::class, 'confirmation']);
    Route::get('/order/confirmation/{order_id}', [OrderController::class, 'confirmationGET']);
    Route::post('/order/volume/{order_id}', [OrderController::class, 'volume']);
    Route::post('/order/add/{costumer_id}', [OrderController::class, 'add']);
    Route::get('/order/qabul', [OrderController::class, 'qabul']);
    Route::post('/order/razmer/{order_id}', [OrderController::class, 'razmer']);
    Route::get('/order/razmer/{order_id}', [OrderController::class, 'razmerGet']);
    Route::get('/order/washshing/{order_id}', [OrderController::class, 'washshing']);
    Route::get('order/yakuniy/{order_id}', [OrderController::class, 'yakunGet']);
    Route::get('/order/yuvish', [OrderController::class, 'yuvish']);
    Route::post('order/yuvildi/{clean_id}', [OrderController::class, 'yuvildi']);
    Route::post('/order/cancel/{order_id}', [OrderController::class, 'cancel']);
    Route::post('/order_item/cancel/{order_id}', [OrderController::class, 'item_cancel']);
    Route::put('/order/driver/{order_id}', [OrderController::class, 'driver']);
    Route::get('/order/talking', [OrderController::class, 'talking']);
    Route::post('/order/yakun/{id}', [OrderController::class, 'yakun']);
    Route::get('/order/transport_one/{id}', [CleanController::class, 'transportone']);
    Route::post('/order/end/{order_id}', [OrderController::class, 'end']);
    Route::get('order/cleans/{order_id}', [OrderController::class, 'cleans']);
    Route::post('order/tartib', [OrderController::class, 'tartib']);
    Route::get('orders/{costumer_id}', [OrderController::class, 'orders']);
    Route::get('/transport/order_count', [OrderController::class, 'orderCount']);
    Route::get('/order/transport', [OrderController::class, 'transportOrder']);
    Route::get('/order_one/{order_id}', [OrderController::class, 'oneOrder']);
    Route::get('/order/yetkazish', [OrderController::class, 'yetkazish']);
    Route::get('/order/yetkazish_transport/{id}', [OrderController::class, 'yetkazishTransport']);
    Route::post('/order/yetkazish_bekor/{order_id}', [OrderController::class, 'yetkazishbekor']);
    Route::get('/order/qayta', [OrderController::class, 'qayta']);
    Route::get('/order/qayta_view/{order_id}', [OrderController::class, 'qaytaview']);
    Route::post('/order/qayta_view/{order_id}', [OrderController::class, 'qaytaviewstore']);
    Route::get('/order/yakunlash/{id}', [OrderController::class, 'yakunlash']);
    Route::get('/order/foiz/{order_id}', [OrderController::class, 'foiz']);
    Route::post('/order/add_product/{order_id}', [OrderController::class, 'addProduct']);

    Route::post('order/createNow', [OrderController::class, 'createNow']);
    Route::resource('/order', OrderController::class);

    Route::get('/nasiya/filter', [NasiyaController::class, 'filter']);
    Route::get('/nasiya/search', [NasiyaController::class, 'search']);
    //    Route::get('/nasiya/',[NasiyaController::class,'']);
    Route::resource('/nasiya', NasiyaController::class);
    Route::post('/nasiya/{nasiya_id}', [NasiyaController::class, 'nasiyaol']);
    Route::post('/nasiya/kechish/{nasiya_id}', [NasiyaController::class, 'kechish']);
    Route::post('nasiya/end/{nasiya_id}', [NasiyaController::class, 'end']);
    Route::resource('/filial', FilialController::class);

    Route::resource('/costumer', CostumerController::class);
    Route::group(['prefix' => '/costumer/'], function () {
        Route::post('/add', [CostumerController::class, 'add']);
        Route::get('/{costumer_id}/orders', [CostumerController::class, 'orders']);
        Route::get('/{costumer_id}/nasiya', [CostumerController::class, 'nasiya']);
        Route::get('{costumer_id}/pullar', [CostumerController::class, 'pullar']);
        Route::get('/{costumer_id}/calls', [CostumerController::class, 'calls']);
        Route::post('/{costumer_id}/called', [CostumerController::class, 'called']);
        Route::get('/has/{phone}', [CostumerController::class, 'filterPhone']);
        Route::get('/callreport/{type}', [CostumerController::class, 'recall']);
    });

    Route::get('/recalls/recall', [RecallController::class, 'recall']);
    Route::post('/recalls/talking/{order_id}', [RecallController::class, 'talkinguser']);
    Route::get('/recalls/talking', [RecallController::class, 'talking']);
    Route::get('/recalls/calling', [RecallController::class, 'calling']);
    Route::get('/recalls/talking6', [RecallController::class, 'talking6']);
    Route::post('/recalls/talking6', [RecallController::class, 'talkingg6']);
    Route::post('/recall/one_user', [RecallController::class, 'oneUser']);
    Route::resource('/recalls', RecallController::class);



    Route::resource('/davomat', DavomatController::class);
    Route::group(['prefix' => '/davomat'], function () {
        Route::get('/{id}/keldi', [DavomatController::class, 'keldi']);
        Route::get('/{id}/ketdi', [DavomatController::class, 'ketdi']);
        Route::post('/finger/{user_id}/keldi', [DavomatController::class, 'fingerKeldi']);
        Route::post('/finger/{user_id}/ketdi', [DavomatController::class, 'fingerKetdi']);
    });

    Route::get('/qadoqlash/qayta/', [QadoqlashController::class, 'qayta']);
    Route::post('qadoqlash/qadoqlash/{order_id}', [QadoqlashController::class, 'qadoqlash']);
    Route::resource('/qadoqlash', QadoqlashController::class);

    Route::resource('/tokcha', TokchaController::class);

    Route::post('/tokcha', [TokchaController::class, 'store']);
    //Route::post('/millat/',[MillatController::class,'store']);

    Route::resource('/millat', MillatController::class);

    Route::resource('/kirim', KirimController::class);

    Route::post('/kvitansiya/qaytakel/{order_id}', [KvitansiyaController::class, 'qaytakel']);
    Route::post('/kvitansiya/{order_id}/create', [KvitansiyaController::class, 'store']);
    Route::get('/kvitansiya/kvitansiya', [KvitansiyaController::class, 'kvitansiya']);

    Route::resource('/darvoza', DarvozaController::class);

    Route::resource('/sms', SmsSendedController::class);
    Route::post('/sms/activate/{id}', [SmsSendedController::class, 'update']);

    Route::resource('/xizmatlar', XizmatlarController::class);

    Route::resource('/call', CallController::class);

    Route::get('common_settings', [CommonController::class, 'setting']);
    Route::get('footer_count', [CommonController::class, 'footercount']);
    Route::get('footer_count_id', [CommonController::class, 'footercountId']);

    Route::get('profile', [CommonController::class, 'profile']);

    Route::get('/holat_paneli', [CommonController::class, 'holat']);
    Route::get('/holat_paneli_for_transport', [CommonController::class, 'holat_transport']);

    Route::get('/transport', [CommonController::class, 'transport']);
    Route::get('/operator', [CommonController::class, 'operator']);
    Route::get('/buyorder', [BuyorderController::class, 'index']);
    Route::post('/buyorder/{order_id}', [BuyorderController::class, 'add']);


    Route::get('/korsatkich', [KorsatkichController::class, 'index']);
    Route::get('/korsatkich/kassaga', [KorsatkichController::class, 'kassaga']);
    Route::get('/korsatkich/clean_hisobot', [KorsatkichController::class, 'cleans']);
    Route::get('/korsatkich/yuvildi', [KorsatkichController::class, 'yuvildi']);
    Route::get('/korsatkich/yuvildi/hodim/{id}', [KorsatkichController::class, 'hodim'])->whereNumber('id');
    Route::get('/korsatkich/recall', [KorsatkichController::class, 'recall']);
    Route::get('/korsatkich/recall6', [KorsatkichController::class, 'recall6']);
    Route::get('/korsatkich/qayta', [KorsatkichController::class, 'qayta']);
    Route::get('/korsatkich/kechilgan', [KorsatkichController::class, 'kechilgan']);
    Route::get('/korsatkich/topshirildi', [KorsatkichController::class, 'topshirildi']);
    Route::get('/korsatkich/topshirildi/hodim/{id}', [KorsatkichController::class, 'topshirildiHodim']);
    Route::get('/korsatkich/topshirildi_transport', [KorsatkichController::class, 'topshirildiTransport']);
    Route::get('/korsatkich/qadoqlandi', [KorsatkichController::class, 'qadoqlandi']);
    Route::get('/korsatkich/qadoqlandi/hodim/{id}', [KorsatkichController::class, 'hodimQadoqlandi'])->whereNumber('id');
    Route::get('/korsatkich/joyida', [KorsatkichController::class, 'joyida']);
    Route::get('/korsatkich/mahsulot', [KorsatkichController::class, 'mahsulot']);
    Route::get('/korsatkich/bekor', [KorsatkichController::class, 'bekor']);
    Route::get('/korsatkich/olibkelindi', [KorsatkichController::class, 'olibkelindi']);
    Route::get('/korsatkich/mahsulot_all', [KorsatkichController::class, 'mahsulot_by_transport']);
    Route::get('/korsatkich/qabul/{id}', [KorsatkichController::class, 'qabul']);
    Route::get('/korsatkich/mijoz', [KorsatkichController::class, 'mijoz']);
    Route::get('/korstkich/buyurtma', [KorsatkichController::class, 'buyurtma']);
    Route::get('/korstkich/nasiya', [KorsatkichController::class, 'nasiya']);
    Route::get('/korsatkich/kpi', [KorsatkichController::class, 'kpi']);
    Route::get('/korsatkich/maosh/', [KorsatkichController::class, 'maosh']);

    Route::resource('/ombor', OmborController::class);
    Route::post('/ombor/tartib/{id}', [OmborController::class, 'tartib']);
    Route::post('ombor/to/{order_id}', [OmborController::class, 'to']);

    Route::get('orders_by_status/{status}', [OrderController::class, 'qadoqlash']);
    Route::get('/getme', [CommonController::class, 'getme']);
    Route::put('/setting', [CommonController::class, 'updateSetting']);

    Route::get('/search', [CommonController::class, 'search']);
    Route::get('filial_work_year', [CommonController::class, 'workdate']);
    Route::get('payment_methods', [CommonController::class, 'payment']);

    Route::get('/manba', [ManbalarController::class, 'index']);
    Route::get('/filter', [CommonController::class, 'filter']);
    Route::get('/sub', [CommonController::class, 'sub']);

    // Faceid ma`lumotlari olish
    Route::get('fingerprint', [FingerprintController::class, 'index']);
    Route::post('fingerprint', [FingerprintController::class, 'store']);
});

Route::post('face-id', [FaceIdController::class, 'index'])->name('faceid');
