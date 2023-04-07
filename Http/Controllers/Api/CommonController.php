<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Clean;
use App\Models\Costumers;
use App\Models\Order;
use App\Models\User;
use App\Models\Xizmatlar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CommonController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }


    public function setting()
    {
        return response([
            'adding_nation' => 0,
            'barcode' => Auth::user()->filial->barcode,
            'changing_coast' => Auth::user()->filial->changing_coast,
            'order_brak' => Auth::user()->filial->order_brak,
            'order_dog' => Auth::user()->filial->order_dog,
            'sizing' => Auth::user()->filial->sizing,
            'transfer_driver' => Auth::user()->filial->transfer_driver,
            'tokcha' => Auth::user()->filial->stilaj == 1 ? true : false,
            'majburiy_chegirma' => Auth::user()->filial->costumer_input,
            'stylaj' => Auth::user()->filial->stilaj == 1 ? true : false,
        ]);
    }


    public function image()
    {
        return response([
            'logi' => Auth::user()->filial->logo,
            'logi_mini' => Auth::user()->filial->mini_logo
        ]);
    }
    public function profile()
    {
        $array = [];

        $array[] = Auth::user();
        $array['filial'] = Auth::user()->filial;
        $array['country'] = Auth::user()->filial->country;
        unset($array['filial']);
        unset($array['country']);
        return $array[0];
    }
    public function holat()
    {
        $clean = Clean::query();
        $cleaning = $clean->where(['clean_filial_id' => Auth::user()->filial_id, 'clean_status' => 'yuvilmoqda', 'clean_status' => 'olchov', 'clean_status' => 'qayta yuvish'])->count();
        $quridi = $clean->where(['clean_filial_id' => Auth::user()->filial_id, 'clean_status' => 'quridi', 'clean_status' => 'qayta quridi'])->count();
        $qadoq = $clean->where(['clean_filial_id' => Auth::user()->filial_id, 'clean_status' => 'qadoqlandi', 'clean_status' => 'qayta qadoqlandi'])->count();
        return response([
            "yuvilmaganlar_soni" => $cleaning,
            "qadoqlanmaganlar_soni" => $quridi,
            "topshirilmaganlar_soni" => $qadoq
        ], 200);
    }
    public function holat_transport()
    {
        $order = Order::query();
        $new_order = $order->where(['order_filial_id' => Auth::user()->filial_id, 'order_status' => 'keltirish'])->count();
        $ombor = $order->where(['order_filial_id' => Auth::user()->filial_id, 'order_status' => 'ombor'])->count();
        $orderlar = $order->where(['order_filial_id' => Auth::user()->filial_id])->where('order_status', '!=', 'topshirildi')->count();

        return response([
            "yangi_buyurtmalar" => $new_order,
            "ombordagilar_soni" => $ombor,
            "tayyor_buyurtmalar" => $orderlar
        ]);
    }
    public function transport()
    {
        $transport = User::query();
        return $transport->where(['filial_id' => Auth::user()->filial_id, 'role' => 'transport'])->get();
    }
    public function operator()
    {

        $transport = User::query();
        return $transport->select(['id', 'fullname'])
            ->where(['filial_id' => Auth::user()->filial_id, 'role' => 'operator'])
            ->withCount('ord')
            ->get();
    }
    public function footercount(Request $request)
    {
        $action = $request->get('action');
        $from = $request->get('from');
        $to = $request->get('to');
        $id = $request->get('id');
        $xizmat = Xizmatlar::query();

        if (!$from)
            $from = date('Y-m-d H:i:s', strtotime(date('Y-m-d' . ' -20 day')));
        if (!$to)
            $to = date('Y-m-d H:i:s');


        $xizmat = $xizmat->where(['filial_id' => Auth::user()->filial_id, 'status' => 'active'])->get();

        if ($action == 'qadoqlash') {
            $al = [];
            $i = 0;
            foreach ($xizmat as $item) {
                $modelClean = Clean::query()
                    ->when(!empty($id), function ($query) use ($id) {
                        $query->where('user_id', $id);
                    })
                    ->where('clean_filial_id', Auth::user()->filial_id)
                    ->where('clean_product', $item->xizmat_id)
                    ->where(function ($query) {
                        $query->where('clean_status', 'qadoqlandi')
                            ->orWhere('clean_status', 'qayta qadoqlandi');
                    })
                    ->whereDate('clean_date', '>=', $from)
                    ->whereDate('clean_date', '<=', $to);
                $al[$i] = [
                    'name' => $item->xizmat_turi,
                    'soni' => $modelClean->count(),
                    'xajmi' => $modelClean->sum('clean_hajm'),
                    'olchov' => $item->olchov
                ];
                $i++;
            }
            return $al;
        }
        if ($action == 'topshirildi') {
            $al = [];
            $i = 0;
            foreach ($xizmat as $item) {
                $modelClean = Clean::query()
                    ->when(!empty($id), function ($query) use ($id) {
                        $query->where('user_id', $id);
                    })
                    ->where('clean_filial_id', Auth::user()->filial_id)
                    ->where('clean_product', $item->xizmat_id)
                    ->where('clean_status', 'topshirildi')
                    ->whereDate('clean_date', '>=', $from)
                    ->whereDate('clean_date', '<=', $to);
                $al[$i] = [
                    'name' => $item->xizmat_turi,
                    'soni' => $modelClean->count(),
                    'xajmi' => $modelClean->sum('clean_hajm'),
                    'olchov' => $item->olchov
                ];
                $i++;
            }
            return $al;
        }

        if ($action == 'qayta') {
            $al = [];
            $i = 0;
            foreach ($xizmat as $item) {
                $al[$i] = [
                    'name' => $item->xizmat_turi,
                    'soni' => Clean::where([
                        'clean_filial_id' => Auth::user()->filial_id,
                        'clean_product' => $item->xizmat_id
                    ])
                        ->whereDate('qayta_sana', '>=', $from)
                        ->whereDate('qayta_sana', '<=', $to)
                        ->count(),
                    'xajmi' => Clean::where(['clean_filial_id' => Auth::user()->filial_id, 'clean_product' => $item->xizmat_id])
                        ->whereDate('qayta_sana', '>=', $from)
                        ->whereDate('qayta_sana', '<=', $to)
                        ->sum('clean_hajm'),
                    'olchov' => $item->olchov
                ];
                $i++;
            }
            return $al;
        }
        if ($action == 'yuvildi') {
            $al = [];
            $i = 0;
            foreach ($xizmat as $item) {
                $modelClean = Clean::query()
                    ->when(!empty($id), function ($query) use ($id) {
                        $query->where('user_id', $id);
                    })
                    ->where('clean_filial_id', Auth::user()->filial_id)
                    ->where('clean_product', $item->xizmat_id)
                    ->where('clean_status', 'quridi')
                    ->whereDate('clean_date', '>=', $from)
                    ->whereDate('clean_date', '<=', $to);
                $al[$i] = [
                    'name' => $item->xizmat_turi,
                    'soni' => $modelClean->count(),
                    'xajmi' => $modelClean->sum('clean_hajm'),
                    'olchov' => $item->olchov
                ];
                $i++;
            }
            return $al;

            // $data = Xizmatlar::where(['filial_id' => Auth::user()->filial_id, 'status' => 'active'])
            // ->
            // ->get()
            // ->toArray();

            // dd($al, $data);
        }
    }
    public function footercountId(Request $request)
    {
        $action = $request->get('action');
        $from = $request->get('from');
        $to = $request->get('to');
        $id = $request->get('id');
        $xizmat = Xizmatlar::query();

        if (!$from)
            $from = date('Y-m-d H:i:s', strtotime(date('Y-m-d' . ' -20 day')));
        if (!$to)
            $to = date('Y-m-d H:i:s');


        $xizmat = $xizmat->where(['filial_id' => Auth::user()->filial_id, 'status' => 'active'])->get();

        if ($action == 'qadoqlash') {
            $al = [];
            $i = 0;
            foreach ($xizmat as $item) {
                $modelClean = Clean::query()
                    ->where('user_id', $id)
                    ->where('clean_filial_id', Auth::user()->filial_id)
                    ->where('clean_product', $item->xizmat_id)
                    ->where(function ($query) {
                        $query->where('clean_status', 'qadoqlandi')
                            ->orWhere('clean_status', 'qayta qadoqlandi');
                    })
                    ->whereDate('clean_date', '>=', $from)
                    ->whereDate('clean_date', '<=', $to);
                $al[$i] = [
                    'name' => $item->xizmat_turi,
                    'soni' => $modelClean->count(),
                    'xajmi' => $modelClean->sum('clean_hajm'),
                    'olchov' => $item->xizmat_turi
                ];
                $i++;
            }
            return $al;
        }
        if ($action == 'topshirildi') {
            $al = [];
            $i = 0;
            foreach ($xizmat as $item) {
                $al[$i] = [
                    'name' => $item->xizmat_turi,
                    'soni' => Clean::where([
                        'clean_filial_id' => Auth::user()->filial_id,
                        'clean_product' => $item->xizmat_id
                    ])
                        ->where('clean_status', 'topshirildi')
                        ->whereDate('top_sana', '>=', $from)
                        ->whereDate('top_sana', '<=', $to)
                        ->count(),
                    'xajmi' => Clean::where(['clean_filial_id' => Auth::user()->filial_id, 'clean_product' => $item->xizmat_id])
                        ->where('clean_status', 'topshirildi')
                        ->whereDate('top_sana', '>=', $from)
                        ->whereDate('top_sana', '<=', $to)
                        ->sum('clean_hajm'),
                    'olchov' => $item->xizmat_turi
                ];
                $i++;
            }
            return $al;
        }

        if ($action == 'qayta') {
            $al = [];
            $i = 0;
            foreach ($xizmat as $item) {
                $al[$i] = [
                    'name' => $item->xizmat_turi,
                    'soni' => Clean::where([
                        'clean_filial_id' => Auth::user()->filial_id,
                        'clean_product' => $item->xizmat_id
                    ])
                        ->whereDate('qayta_sana', '>=', $from)
                        ->whereDate('qayta_sana', '<=', $to)
                        ->count(),
                    'xajmi' => Clean::where(['clean_filial_id' => Auth::user()->filial_id, 'clean_product' => $item->xizmat_id])
                        ->whereDate('qayta_sana', '>=', $from)
                        ->whereDate('qayta_sana', '<=', $to)
                        ->sum('clean_hajm'),
                    'olchov' => $item->olchov
                ];
                $i++;
            }
            return $al;
        }
        if ($action == 'yuvildi') {
            $al = [];
            $i = 0;
            foreach ($xizmat as $item) {
                $modelClean = Clean::query()
                    ->where('user_id', $id)
                    ->where('clean_filial_id', Auth::user()->filial_id)
                    ->where('clean_product', $item->xizmat_id)
                    ->where('clean_status', 'quridi')
                    ->whereDate('clean_date', '>=', $from)
                    ->whereDate('clean_date', '<=', $to);
                $al[$i] = [
                    'name' => $item->xizmat_turi,
                    'soni' => $modelClean->count(),
                    'xajmi' => $modelClean->sum('clean_hajm'),
                    'olchov' => $item->xizmat_turi
                ];
                $i++;
            }
            return $al;

            // $data = Xizmatlar::where(['filial_id' => Auth::user()->filial_id, 'status' => 'active'])
            // ->
            // ->get()
            // ->toArray();

            // dd($al, $data);
        }
    }
    public function getme()
    {
        return Auth::user();
    }

    public function updateSetting(Request $request)
    {
        $user = User::where('id', Auth::id());
        $first = clone $user->first();
        if ($request->input('password'))
            $hash  = Hash::make($request->input('password'));
        else
            $hash = Auth::user()->password_hash;

        $user->update([
            'fullname' => $request->input('fullname') ? $request->input('fullname') : $first->fullname,
            'phone' => $request->input('phone') ? $request->input('phone') : $first->phone,
            'username' => $request->input('username') ? $request->input('username') : $first->username,
            'password_hash' => $hash
        ]);
        return response(['message' => 'success updated'], 200);
    }
    public function search(Request $request)
    {
        $status = $request->get('status');
        $data = $request->get('data');
        $date = $request->get('date');
        if ($status == 'kvitansiya') {
            $kvitansiya = Order::query();
            $kvitansiya = $kvitansiya
                ->where(['order_filial_id' => Auth::user()->filial_id, 'nomer' => $data])
                ->whereYear('order_date', '=', $date)
                ->with('custumer')
                ->with('operator:id,fullname')
                ->with('driver:id,fullname')
                ->first();

            if (!$kvitansiya)
                return response(['message' => 'Not found order'], 404);

            $clean_count = Clean::query()->where(['clean_filial_id' => Auth::user()->filial_id])->where('order_id', $kvitansiya->order_id);

            $yuvishdagilar = clone $clean_count;
            $qadoqlashdagilar = clone $clean_count;
            $topshirishdagilar = clone $clean_count;

            $kvitansiya['yuvishdagilar'] = $yuvishdagilar->where(function ($query) {
                $query->where('clean_status', 'yuvilmoqda')->orWhere('clean_status', 'olchov')->orWhere('clean_status', 'qayta yuvish');
            })->count();
            $kvitansiya['qadoqlashdagilar'] = $qadoqlashdagilar->where(function ($query) {
                $query->where('clean_status', 'quridi')->orWhere('clean_status', 'qayta quridi');
            })->count();
            $kvitansiya['topshirishdagilar'] = $topshirishdagilar->where(function ($query) {
                $query->where('clean_status', 'qadoqlandi')->orWhere('clean_status', 'qayta qadoqlandi');
            })->count();

            return  response($kvitansiya);
        } else if ($status == 'costumer') {
            $costumer = Costumers::query();
            $costumer = $costumer
                ->where('costumers_filial_id', Auth::user()->filial_id)
                ->where('costumer_name', 'like', '%' . $data . '%')
                ->orWhere('costumers_filial_id', Auth::user()->filial_id)
                ->where('costumer_phone_1', 'like', '%' . $data . '%')
                ->with(['orders' => function ($query) {
                    $query->select(['nomer', 'costumer_id']);
                }])
                ->get();
            $all = [];
            foreach ($costumer as $item) {
                $order = Order::where(['order_filial_id' => Auth::user()->filial_id, 'costumer_id' => $item->costumer_id])
                    ->orderByDesc('order_id')
                    ->first();
                if ($order) {

                    if ($order->order_status == 'keltirish') {
                        $qabil_qilinadigon = 1;
                        $tasdiqlanadigon = 0;
                    } else if ($order->order_status == 'qabul qilindi' or $order->order_status = 'joyida_yuvish') {
                        $qabil_qilinadigon = 0;
                        $tasdiqlanadigon = 1;
                    } else {
                        $qabil_qilinadigon = 0;
                        $tasdiqlanadigon = 0;
                    }
                } else {
                    $qabil_qilinadigon = 0;
                    $tasdiqlanadigon = 0;
                }
                $item = $item->toArray();
                $all[] = array_merge($item, ['qabul' => $qabil_qilinadigon, 'tasdiq' => $tasdiqlanadigon]);
            }
            return  $all;
        } else if ($status == 'barcode') {
            $barcode = Clean::where(['clean_filial_id' => Auth::user()->filial_id, 'barcode' => $data])->first();
            if (!$barcode)
                return  response([
                    'message' => 'not found barce',
                ]);
            return  ['message' => 'order', 'order_id' => $barcode->order_id];
        } else
            return response(['messag' => 'status empty']);
    }
    public function workdate()
    {
        $date =  Auth::user()->filial->filial_work_date;
        $res = new \DateTime($date);
        $year = $res->format('Y');
        $all = [];
        $all[] = (int)$year;
        $difference =  date('Y') - $year;
        $k = 1;
        for ($i = 0; $i < $difference; $i++) {
            if (date('Y') - $year == 0)
                break;
            $all[] = $year + $k;
            $k++;
        }
        return $all;
    }
    public function payment()
    {
        return [
            'naqd',
            'click',
            'Terminal-bank'
        ];
    }
    public function filter(Request  $request)
    {
        $status = $request->get('status');

        if ($status == 'transport') {
            $order = Order::query();
            $order = $order->where(['order_filial_id' => Auth::user()->filial_id, 'order_status' => 'keltirish'])
                ->where('tartib_raqam', '>', 0);
            $all_count = $order->count();
            $own_count = $order->where(['own' => 1])->count();
            $joyida = $order->where(['joyida' => 1])->count();
            $transport = User::where(['filial_id' => Auth::user()->filial_id, 'role' => 'transport'])->get();

            $all = [];
            $all[] = [
                'id' => -2,
                'fullname' => 'Barchasi',
                'count' => $all_count
            ];
            $all[] = [
                'id' => -1,
                'fullname' => 'O`zi olib ketadi',
                'count' => $own_count
            ];
            $all[] = [
                'id' => 0,
                'fullname' => 'joyida',
                'count' => $joyida
            ];
            foreach ($transport as $item) {
                $count = clone $order;
                $all[] = [
                    'id' => $item->id,
                    'fullname' => $item->fullname,
                    'count' => $count->where(['order_driver' => $item->id])->count()
                ];
            }
            return $all;
        }
    }
    public function sub()
    {
        //        ->addSelect(['order_id'=>Order::query()
        //        ->select('order_id')
        //        ->whereColumn('order_id','orders.order_id')->limit(1)->get()])
        //            ->selectSub('SELECT orders.* from orders where orders.order_id = clean.order_id','orders')

        $ord = DB::table('orders')->select('order_id as o_id', 'order_date');
        //        $clean = Clean::query();
        //        $clean = $clean
        //            ->select('clean.order_id', 'clean.reclean_driver', 'clean.costumer_id')
        //            ->where('clean_status', 'qayta keltirish')
        //            ->where('reclean_place', 3)
        //            ->where('clean_filial_id', Auth::user()->filial_id)
        //            ->groupBy(["order_id", "reclean_driver", 'costumer_id'])
        //            ->join('orders',function ($join){
        //                $join->on('orders.order_id','=','clean.order_id');
        //            })
        //            ->get();
        //        return $clean;

        $clean = DB::table('clean')
            //            ->select('clean.order_id','clean.clean_status','clean.reclean_place','clean.clean_filial_id')
            ->select('clean.order_id', 'clean.reclean_driver as driver', 'clean.costumer_id', 'orders.*')
            ->leftJoin('orders', 'clean.order_id', '=', 'orders.order_id')
            ->where('clean_status', 'qayta keltirish')
            ->where('reclean_place', 3)
            ->where('clean_filial_id', Auth::user()->filial_id)
            ->groupBy('clean.order_id', 'clean.clean_status', 'clean.reclean_place', 'clean.clean_filial_id')
            //            ->groupBy(["clean.order_id", "clean.reclean_driver", 'clean.costumer_id'])
            ->get();
        return response()->json($clean);
    }
}
