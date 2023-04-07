<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Costumers;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\Buyorder;
use Illuminate\Support\Facades\Auth;
use SebastianBergmann\ObjectReflector\ObjectReflector;


class BuyorderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        $limit = $request->get('limit');
        $limit_count = 50;
        if ($limit) {
            $limit_count = $limit;
        }

        $the_mintaqa = \Auth::user()->filial->mintaqa_id;

        $buyorder = Order::query();
        $buyorder = $buyorder
            ->where(['order_filial_id' => \Auth::user()->filial_id, 'order_status' => "kutish", 'mintaqa_id' => $the_mintaqa])
            ->orWhere(['order_filial_id' => 0, 'order_status' => "kutish", 'mintaqa_id' => $the_mintaqa])
            ->with('custumer')
            ->orderBy('order_date')
            ->paginate($limit_count);
        return $buyorder;
    }
    public function add(Request $request, $order_id)
    {
        $sum = $request->input('discount_summa');
        $foiz = $request->input('discount_foiz');
        $driver = $request->input('driver');
        $order = Order::where(['order_id' => $order_id])->first();
        $costumer = $order->custumer;

        if ($costumer)
            $costumer_one = Costumers::where(['costumer_phone_1' => $costumer->first()->costumer_phone_1, 'costumers_filial_id' => Auth::user()->filial_id]);

        $buy_narx = Buyorder::find(1);

        //        $order->order_skidka_foiz = $foiz;
        //        $order->order_skidka_sum = $sum;
        //
        //        $order->order_status = 'keltirish';
        //        $order->operator_id = Auth::id();
        //        $order->mintaqa_id = \auth()->user()->filial->mintaqa_id;

        if ($driver == '')
            $driver = 'hamma';

        if ($costumer_one->count() > 0) {
            $cos = $costumer_one->update([
                'costumer_status' => 'keltirish'
            ]);
            $order->costumer_id = $cos->id;
            $ord_costumer_id = $costumer_one->first()->id;
        } else {
            $costumr =  Costumers::create([
                'orienter' => $costumer_one->first()->orienter,
                'costumer_source' => $costumer_one->first()->costumer_source,
                'costumer_name' => $costumer_one->first()->costumer_name,
                'costumer_addres' => $costumer_one->first()->costumer_addres,
                'costumer_turi' => $costumer_one->first()->costumer_turi,
                'manba' => $costumer_one->first()->manba,
                'costumer_phone_1' => $costumer_one->first()->costumer_phone_1,
                'costumers_filial_id' => Auth::user()->filial_id,
                'mintaqa_id' => Auth::user()->filial->mintaqa_id,
                'saygak_id' => 0,
                'costumer_status' => 'keltirish'
            ]);
            if ($costumr) {
                $order->costumer_id = $costumr->id;
            }
        }

        $order_last = Order::query();
        $order_last = $order_last->where(['order_filial_id' => Auth::user()->filial_id])
            ->whereYear('order_date', '=', date('Y'))->max('nomer');

        if ($order_last)
            $nomer = $order_last;
        else
            $nomer = 0;



        if ($order->save())
            return response(['message' => 'buyurtma olindi']);
    }
}
