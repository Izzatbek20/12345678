<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Buyurtma;
use App\Models\Chegirma;
use App\Models\Clean;
use App\Models\Costumers;
use App\Models\Davomat;
use App\Models\Kpi;
use App\Models\MijozKirim;
use App\Models\Nasiya;
use App\Models\NasiyaBelgilash;
use App\Models\Recall;
use App\Models\User;
use App\Models\Xizmatlar;
use App\Models\KpiHisob;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use mysql_xdevapi\Expression;
use function Symfony\Component\String\b;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $limit = $request->get('limit');
        $limit_count = 50;
        if ($limit) {
            $limit_count = $limit;
        }

        //        $order = Order::paginate($limit_count);
        $order = Order::query();
        $order = $order->where(['order_filial_id' => Auth::user()->filial_id])
            //            ->with('millat')
            ->paginate($limit_count);
        return response($order, 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    public function createNow(Request $request)
    {
        // Order yili bo`yicha nomer eng kottalarini olamiz
        $order_last = Order::query();
        $order_last = $order_last->where(['order_filial_id' => Auth::user()->filial_id])->whereYear('order_date', date('Y'))->max('nomer');
        if ($order_last) {
            $nomer = $order_last;
        } else {
            $nomer = 0;
        }


        $xizmatlar = Xizmatlar::where(['filial_id' => Auth::user()->filial_id])->get();
        $xizmatlar_array = [];
        $validate_chegirma = 0;

        //        for ($i = 0;$i<count($request->input('xizmatlar'));$i++)
        //        {
        //            $explode = explode(':',$request->xizmatlar[$i]);
        //            $xizmatlar_array[$explode[0]] = $explode[1];
        //        }
        foreach ($request->input('xizmatlar') as $item) {
            $xizmatlar_array['xizmat' . $item['id']] = $item['narx'];
        }
        foreach ($xizmatlar as $xizmat) {
            $validate_chegirma++;
            if (!in_array('xizmat' . $xizmat->xizmat_id, $xizmatlar_array)) {
                break;
            }
            $b_name = $xizmatlar_array['xizmat' . $xizmat->xizmat_id];
            if ($b_name)
                $validate_chegirma += 1;
        }
        if ($request->input('order_skidka_sum') > 0 or $request->input('order_skidka_foiz') > 0)
            $validate_chegirma++;

        if ($validate_chegirma == 0) {
            return response(['message' => 'Hech bo`lmaganda bitta mahsulot narxini kiriting']);
        }
        if (Auth::user()->role == 'admin_operator' || Auth::user()->role == 'operator') {
            $operator_ozroq_kpi_ehtimoli = 0;
            foreach ($xizmatlar as $xizmat) {
                $b_name = $xizmatlar_array['xizmat' . $xizmat->xizmat_id];
                if (!in_array('xizmat' . $xizmat->xizmat_id, $xizmatlar_array)) {
                    break;
                }

                if ($b_name && $b_name < $xizmat->operator_kpi_line) {
                    $operator_ozroq_kpi_ehtimoli += 1;
                }
            }

            if ($request->input('order_skidka_foiz') and $request->input('order_skidka_foiz') > 0) {
                $operator_ozroq_kpi_ehtimoli += 1;
            }
            if ($request->input('order_skidka_sum') and $request->input('order_skidka_sum') > 0) {
                $operator_ozroq_kpi_ehtimoli += 1;
            }
            $kpi_xisob = KpiHisob::query();
            $kpi_summa = 0;
            if ($operator_ozroq_kpi_ehtimoli > 0) {
                $kpi_summa = Auth::user()->ozroq_kpi;
            } else {
                $kpi_summa = Auth::user()->kpi;
            }
            $oylik_kpi = Auth::user()->oylik += $kpi_summa;
            if ($kpi_summa > 0) {
                $kpi_xisob->create([
                    'user_id' => Auth::id(),
                    'summa' => $kpi_summa,
                    'filial_id' => Auth::user()->filial_id,
                    'date' => \date('Y-m-d H:i:s'),
                    'clean_id' => 0,
                ]);
                User::find(auth()->user()->id)->update(['oylik' => $oylik_kpi]);
            }
        }
        $order = Order::create([
            'costumer_id' => $request->input('costumer_id'),
            'mintaqa_id' => $request->input('mintaqa_id'),
            'order_filial_id' => Auth::user()->filial_id,
            'nomer' => $nomer + 1,
            'avans' => 0,
            'avans_type' => 'bosh',
            'order_skidka_foiz' => $request->input('order_skidka_foiz'),
            'order_skidka_sum' => $request->input('order_skidka_sum'),
            'ch_foiz' => $request->input('order_skidka_foiz'),
            'ch_sum' => $request->input('order_skidka_sum'),
            'order_date' => date('Y-m-d H:i:s'),
            'olibk_sana' => date('Y-m-d H:i:s'),
            'izoh' => $request->izoh,
            'izoh2' => '',
            'izoh3' => '',
            'order_price' => 0,
            'order_price_status' => '-',
            'order_last_price' => 0,
            'order_status' => 'keltirish',
            'operator_id' => Auth::id(),
            'order_driver' => $request->input('order_driver'),
            'finish_driver' => 0,
            'geoplugin_longitude' => '_',
            'geoplugin_latitude' => '_',
            'tartib_raqam' => 0,
            'dog' => 'yo`q',
            'brak' => 'yo`q',
            'saygak_id' => 0,
            'own' => 0,
            'joyida' => 0,
            'called' => 0,
            'last_operator_id' => 0,
            'last_izoh' => '',
            'talk_type' => '',
            'last_operator_id2' => 0,
            'last_izoh2' => '',
            'talk_type2' => '',
            'talk_date2' => date('Y-m-d'),
            'ombor_user' => 0,
            'topshir_sana' => \date('Y-m-d'),
            'top_sana' => date('Y-m-d'),
            'talk_date' => date('Y-m-d'),
            'haydovchi_satus' => 0,
        ]);
        if ($order) {

            foreach ($xizmatlar as $xizmat) {

                $b_name = $xizmatlar_array['xizmat' . $xizmat->xizmat_id];
                if ($b_name > 0 and $b_name < $xizmat->narx) {
                    $new_chegirma = Chegirma::query();
                    $ch_sum = $xizmat->narx - $b_name;
                    $new_chegirma->create([
                        'order_id' => $order->order_id,
                        'xizmat_id' => $xizmat->xizmat_id,
                        'summa' => $ch_sum,
                    ]);
                }
                if (!in_array('xizmat' . $xizmat->xizmat_id, $xizmatlar_array))
                    break;
            }
            return response(['message' => 'Order created']);
        } else {
            return response(['message' => 'not created']);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        return response('aaaaa');
        $order_last = Order::query();
        $order_last = $order_last->where(['order_filial_id' => Auth::user()->filial_id])->whereYear('order_date', date('Y'))->max('nomer');
        if ($order_last) {
            $nomer = $order_last;
        } else {
            $nomer = 0;
        }


        $xizmatlar = Xizmatlar::where(['filial_id' => Auth::user()->filial_id])->get();
        $xizmatlar_array = [];
        $validate_chegirma = 0;

        //        for ($i = 0;$i<count($request->input('xizmatlar'));$i++)
        //        {
        //            $explode = explode(':',$request->xizmatlar[$i]);
        //            $xizmatlar_array[$explode[0]] = $explode[1];
        //        }
        foreach ($request->input('xizmatlar') as $item) {
            $xizmatlar_array['xizmat' . $item['id']] = $item['narx'];
        }
        foreach ($xizmatlar as $xizmat) {
            $validate_chegirma++;
            if (!in_array('xizmat' . $xizmat->xizmat_id, $xizmatlar_array)) {
                break;
            }
            $b_name = $xizmatlar_array['xizmat' . $xizmat->xizmat_id];
            if ($b_name)
                $validate_chegirma += 1;
        }
        if ($request->order_skidka_sum > 0 or $request->order_skidka_foiz > 0)
            $validate_chegirma++;

        if ($validate_chegirma == 0) {
            return response(['message' => 'Hech bo`lmaganda bitta mahsulot narxini kiriting']);
        }
        if (Auth::user()->role == 'admin_operator' || Auth::user()->role == 'operator') {
            $operator_ozroq_kpi_ehtimoli = 0;
            foreach ($xizmatlar as $xizmat) {
                $b_name = $xizmatlar_array['xizmat' . $xizmat->xizmat_id];
                if (!in_array('xizmat' . $xizmat->xizmat_id, $xizmatlar_array)) {
                    break;
                }

                if ($b_name && $b_name < $xizmat->operator_kpi_line) {
                    $operator_ozroq_kpi_ehtimoli += 1;
                }
            }

            if ($request->order_skidka_foiz and $request->order_skidka_foiz > 0) {
                $operator_ozroq_kpi_ehtimoli += 1;
            }
            if ($request->order_skidka_sum and $request->order_skidka_sum > 0) {
                $operator_ozroq_kpi_ehtimoli += 1;
            }
            $kpi_xisob = KpiHisob::query();
            $kpi_summa = 0;
            if ($operator_ozroq_kpi_ehtimoli > 0) {
                $kpi_summa = Auth::user()->ozroq_kpi;
            } else {
                $kpi_summa = Auth::user()->kpi;
            }
            $oylik_kpi = Auth::user()->oylik += $kpi_summa;
            if ($kpi_summa > 0) {
                $kpi_xisob->create([
                    'user_id' => Auth::id(),
                    'summa' => $kpi_summa,
                    'filial_id' => Auth::user()->filial_id,
                    'date' => \date('Y-m-d H:i:s'),
                    'clean_id' => 0,
                ]);
                User::find(Auth::user()->id)->update(['oylik' => $oylik_kpi]);
            }
        }
        $order = Order::create([
            'costumer_id' => $request->costumer_id,
            'mintaqa_id' => $request->mintaqa_id,
            'order_filial_id' => Auth::user()->filial_id,
            'nomer' => $nomer + 1,
            'avans' => 0,
            'avans_type' => 'bosh',
            'order_skidka_foiz' => $request->order_skidka_foiz,
            'order_skidka_sum' => $request->order_skidka_sum,
            'ch_foiz' => $request->order_skidka_foiz,
            'ch_sum' => $request->order_skidka_sum,
            'order_date' => date('Y-m-d H:i:s'),
            'olibk_sana' => date('Y-m-d H:i:s'),
            'izoh' => $request->izoh,
            'izoh2' => '',
            'izoh3' => '',
            'order_price' => 0,
            'order_price_status' => '-',
            'order_last_price' => 0,
            'order_status' => 'keltirish',
            'operator_id' => Auth::id(),
            'order_driver' => $request->order_driver,
            'finish_driver' => 0,
            'geoplugin_longitude' => '_',
            'geoplugin_latitude' => '_',
            'tartib_raqam' => 0,
            'dog' => 'yo`q',
            'brak' => 'yo`q',
            'saygak_id' => 0,
            'own' => 0,
            'joyida' => 0,
            'called' => 0,
            'last_operator_id' => 0,
            'last_izoh' => '',
            'talk_type' => '',
            'last_operator_id2' => 0,
            'last_izoh2' => '',
            'talk_type2' => '',
            'talk_date2' => date('Y-m-d'),
            'ombor_user' => 0,
            'topshir_sana' => \date('Y-m-d'),
            'top_sana' => date('Y-m-d'),
            'talk_date' => date('Y-m-d'),
            'haydovchi_satus' => 0,
        ]);
        if ($order) {
            foreach ($xizmatlar as $xizmat) {

                $b_name = $xizmatlar_array['xizmat' . $xizmat->xizmat_id];
                if ($b_name > 0 and $b_name < $xizmat->narx) {
                    $new_chegirma = Chegirma::query();
                    $ch_sum = $xizmat->narx - $b_name;
                    $new_chegirma->create([
                        'order_id' => $order->id,
                        'xizmat_id' => $xizmat->xizmat_id,
                        'summa' => $ch_sum,
                    ]);
                }
                if (!in_array('xizmat' . $xizmat->xizmat_id, $xizmatlar_array))
                    break;
            }
            return "Order created";
        } else {
            return response(['message' => 'not created']);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $order = Order::query();
        $order = $order->where(['order_id' => $id, 'order_filial_id' => Auth::user()->filial_id])->first();
        if (!$order) {
            return response(['message' => 'topilmadi']);
        }
        $order = $order->where(['order_id' => $id, 'order_filial_id' => Auth::user()->filial_id]);

        return response($order->with(['custumer', 'operator', 'cleans'])->first());
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function orders($costumer_id)
    {
        $costumer = Costumers::where(['costumers_filial_id' => Auth::user()->filial_id, 'id' => $costumer_id])->first();
        if (!$costumer) {
            return response([
                'message' => 'Costumer not found'
            ]);
        }

        $orders = Order::where(['order_filial_id' => Auth::user()->filial_id, 'costumer_id' => $costumer->id])->get();
        return $orders;
    }

    public function cleans($order_id)
    {
        $cleans = Order::query();
        $cleans = $cleans->where(['order_filial_id' => Auth::user()->filial_id, 'order_id' => $order_id]);
        if (!$cleans->first()) {
            return response([
                'message' => 'Not forund'
            ], 404);
        }

        if (Auth::user()->role == 'operator') {
            return response($cleans
                // ->with(['cleans' => function ($query) {
                //     $query->where('clean_status', 'qadoqlandi')->orwhere('clean_status', 'qayta qadoqlandi');
                // }])
                ->with('cleans')
                ->with('cleans.xizmat')
                ->with('cleans.tokcha')
                ->with('cleans.qadoqladi:id,fullname')
                ->with('cleans.yuvdi:id,fullname')
                ->with('custumer')
                ->first(), 200);
        }
        if (Auth::user()->role == 'tayorlov' or Auth::user()->role == 'yuvish') {
            return response($cleans
                // ->with(['cleans' => function ($query) {
                //     $query->where('clean_status', 'qadoqlandi')->orwhere('clean_status', 'qayta qadoqlandi');
                // }])
                ->with('cleans')
                ->with('cleans.xizmat')
                ->with('cleans.tokcha')
                ->with('cleans.qadoqladi:id,fullname')
                ->with('cleans.yuvdi:id,fullname')
                ->with('custumer')
                ->first(), 200);
        } else {
            return response($cleans
                // ->with(['cleans' => function ($query) {
                //     $query->where('clean_status', 'qadoqlandi')->orwhere('clean_status', 'qayta qadoqlandi');
                // }])
                ->with('cleans')
                ->with('cleans.xizmat')
                ->with('cleans.tokcha')
                ->with('cleans.qadoqladi:id,fullname')
                ->with('cleans.yuvdi:id,fullname')
                ->with('custumer')
                ->first(), 200);
        }
    }

    public function tartib(Request $request)
    {
        $tartib = $request->input('tartib');
        $order_id = $request->input('order_id');
        $izoh = $request->input('izoh');
        $one = Order::query();
        $one = $one->where(['order_id' => $order_id, 'order_filial_id' => Auth::user()->filial_id]);
        if (!$one->first()) {
            return response([
                'message' => 'Not Found order'
            ], 404);
        }
        $one->update([
            'tartib_raqam' => $tartib,
            'izoh' => !empty($izoh) ? $izoh : ' ',
        ]);
        return response([
            'message' => 'tartiblandi',
        ], 200);
    }

    public function orderCount()
    {
        $transport = User::query();
        $order = Order::query();
        $transport = $transport->where(['role' => 'tayorlov', 'filial_id' => Auth::user()->filial_id])->get();
        $all = [];
        foreach ($transport as $item) {
            $all[] = ['data' => $item->fullname . ' ' . $order->where(['order_driver' => $item->id])->count() . ' ta', 'value' => $item->id];
        }
        return $all;
    }

    public function transportOrder(Request $request)
    {
        $id = $request->get('transport_id');
        $orders = Order::query();
        $orders = $orders->where(['order_filial_id' => Auth::user()->filial_id, 'order_driver' => $id])
            ->with('cleans')
            ->get();
        if (empty($orders)) {
            return response(['message' => 'Not found'], 404);
        }
        return response($orders, 200);
    }

    public function oneOrder($order_id)
    {
        $orders = Clean::where(['clean_filial_id' => Auth::user()->filial_id, 'order_id' => $order_id])->get();
        if (!$orders) {
            return response([
                'message' => 'not found'
            ], 404);
        }
        return $orders;
    }

    public function yetkazish(Request $request)
    {
        $limit = $request->get('limit');
        $status = $request->get('status');
        if (!$limit)
            $limit = 50;

        $order = Order::query();

        if ($status == 'keltirish') {
            $order = $order->where(['order_filial_id' => Auth::user()->filial_id, 'order_status' => 'keltirish'])
                ->orderByDesc('order_id')
                ->paginate($limit);
        } else if ($status == 'qabul') {
            $order = $order->where(['order_filial_id' => Auth::user()->filial_id, 'order_status' => 'qabul qilindi'])
                ->orderByDesc('order_id')
                ->paginate($limit);
        } else {
            return response(['message' => 'stats empty']);
        }

        return response($order, 200);
    }

    public function yetkazishTransport(Request $request, $id)
    {
        $order = Order::query();
        $order = $order->where(['order_filial_id' => Auth::user()->filial_id, 'order_driver' => $id])
            ->with('cleans')
            ->get();
        return $order;
    }

    public function yetkazishbekor($order_id)
    {
        return $order_id;
    }

    public function test(Request $request)
    {
        $arr = $request->input('products');
        $all = [];

        $xizmat = Xizmatlar::query();
        $xizmat = $xizmat->where(['filial_id' => Auth::user()->filial_id, 'status' => 'active'])->get();

        $jami = 0;

        foreach ($arr as $ar) {
            $all['xizmat' . $ar['id']] = (int)$ar['count'];
        }

        foreach ($xizmat as $item) {
            if (!array_key_exists('xizmat' . $item->xizmat_id, $all)) {
                break;
            }
            $b_name = $all['xizmat' . $item->xizmat_id];
            if (intval($b_name) > 0) {
                $jami += (int)$b_name;
            }
        }
        return $jami;
    }

    public function qayta(Request $request)
    {
        $limit = $request->get('limit');
        $limit_count = 15;
        if ($limit) {
            $limit_count = $limit;
        }

        $clean = Clean::query();
        $clean = $clean
            ->select('order_id', 'reclean_driver', 'costumer_id')
            // ->where('clean_status', 'qayta keltirish') // qayta yuvish
            ->where('reclean_place', 3)
            ->where('clean_filial_id', Auth::user()->filial_id)
            ->with('driver:id,fullname')
            ->with('order')
            ->groupBy(["order_id", "reclean_driver", 'costumer_id'])
            ->paginate($limit_count);
        return $clean;
    }

    public function qadoqlash($status)
    {
        $order = Order::query();
        if ($status == 'qadoqlash') {
            $order = $order
                // ->with(['cleans' => function ($query) {
                //     $query->where('clean_status', 'qadoqlandi')->orwhere('clean_status', 'qayta qadoqlandi');
                // }])
                // ->where(function ($query) {
                //     $query->where('order_status', 'yuvilmoqda')->orWhere('order_status', 'yuvilmoqda');
                // })
                ->where('order_filial_id', Auth::user()->filial_id)
                ->where(function ($query) {
                    $query->where('order_status', 'quridi')->orWhere('order_status', 'qayta quridi');
                })
                ->with('custumer')
                ->orderBy('order_date', 'desc')
                // ->when(count(Order::with('cleans')->get()->pluck('cleans')->flatten()) > 0, function ($query) {
                //     $query->with(['cleans' => function ($query) {
                //         $query->where('clean_status', 'qadoqlandi')->orWhere('clean_status', 'qayta qadoqlandi');
                //     }]);
                // })
                // ->has('cleans')
                ->paginate(10);
        }
        return response($order);
    }

    public function yakunlash(Request $request, $id)
    {
        $order = Order::where(['order_filial_id' => Auth::user()->filial_id, 'order_id' => $id])
            ->with('cleans')
            ->first();
        $all = [];
        array_push($all, ['maxsulot_soni' => count($order->cleans)]);
        return $order;
    }

    public function yakun(Request $request, $id)
    {
        $last_price = $request->input('order_last_price');
        $tolov_turi = $request->input('tolov_turi');

        $order = Order::query()->where('order_filial_id', Auth::user()->filial_id)->where('order_id', $id)->first();
        $buyurtma = Buyurtma::where('order_id', $id)->first();

        $nasiaya_belgilash = NasiyaBelgilash::where('filial_id',  Auth::user()->filial_id);

        $cleans = Clean::query();
        $cleans = $cleans->whereIn('clean_status', ['qadoqlandi', 'qayta qadoqlandi'])
            ->where('clean_filial_id', Auth::user()->filial_id)
            ->where('order_id',  $id);

        $summa = NasiyaBelgilash::where('filial_id',  Auth::user()->filial_id)->first();

        $qayta_yuv = Clean::query()
            ->where('order_id',  $id)
            ->where(function ($query) {
                $query->where('clean_status', 'qayta yuvish')
                    ->orWhere('clean_status', 'yuvilmoqda');
            })->count();

        $qayta_qad = Clean::where('order_id',  $id)->where(function ($query) {
            $query->where('clean_status', 'qayta quridi')
                ->orWhere('clean_status', 'quridi');
        })->count();
        $total_absolute_cost = $cleans->sum('clean_narx');

        if ($qayta_yuv > 0 || $qayta_qad > 0) {
            if ($qayta_yuv > 0) {
                $status = 'qayta yuvish';
            } elseif ($qayta_qad > 0 && $qayta_yuv == 0) {
                $status = 'qayta qadoqlash';
            }
        } else {
            $status = 'topshirildi';
        }
        $total_with_discount_cost = 0;
        if ($order->own == 0) {
            foreach ($cleans->get() as $item) {
                if ($item->reclean_place < 3) {
                    $total_with_discount_cost += $item->clean_narx;
                }
            }
            $skidka = $order->order_skidka_sum + ($total_with_discount_cost * $order->order_skidka_foiz / 100);
            $yakuniy_min_sum = ($total_with_discount_cost * $summa->topshir_sum_min) / 100;

            if ($skidka <= $total_with_discount_cost)
                $total_with_discount_cost -= $skidka;
            else
                $total_with_discount_cost = 0;
            $discount_for_own = 100;
        } else {
            foreach ($cleans->get() as $item) {
                if ($item->reclean_place < 3)
                    if (!empty($item->xizmat)) {
                        $total_with_discount_cost += $item->xizmat->discount_for_own * $item->clean_hajm;
                    }
            }
            if ($total_with_discount_cost > 0)
                $discount_for_own = $total_with_discount_cost / $total_absolute_cost * 100;
            else
                $discount_for_own = 100;
        }

        $yakuniy = $total_with_discount_cost;
        if (Auth::user()->role == 'saygak')
            $tushib_berish = $yakuniy - $cleans->sum('clean_narx');
        else {
            if ($nasiaya_belgilash) {
                if ($nasiaya_belgilash->first()->foiz == 0) {
                    $tsh_foiz = 1;
                } else {
                    $tsh_foiz = $nasiaya_belgilash->foiz;
                }

                $tsh_sum = $nasiaya_belgilash->first()->summa;
            } else {
                $tsh_foiz = 1;
                $tsh_sum = 0;
            }
            $tushib_berish = $yakuniy - ($total_absolute_cost * $tsh_foiz / 100) - $tsh_sum;
        }

        if ($yakuniy < 0) {
            $chegirma_sum = abs($yakuniy);
            $yakuniy = 0;
        } else {
            $chegirma_sum = 0;
            $yakuniy = floor($yakuniy);
        }
        $tushib_berish = floor($tushib_berish);

        $order->order_price = $order->cleansSum();

        $old_last_price = $order->eski_last_price;

        if ($last_price > ($yakuniy + $yakuniy / 2) || $last_price < ($yakuniy / 2))
            return response(['message' => 'summa notog`ri kiritildi'], 422);

        if ($qayta_yuv > 0 or $qayta_qad > 0) {
            if ($qayta_yuv > 0)
                $order->order_status = 'qayta yuvish';
            else if ($qayta_qad > 0 and $qayta_yuv == 0)
                $order->orderstatus = 'qayta qadoqlash';
        } else
            $order->order_status = 'topshirildi';

        $order_skidka_foiz = 0;
        $order_skidka_sum = 0;

        $maxsulot_detai = null;

        $cleans->update([
            'clean_status' => 'topshirildi',
            'top_sana' => now()->format('Y-m-d H:i:s'),
            'top_user' => Auth::id(),
        ]);

        // foreach ($cleans->get() as $item) {
        //     $item->clean_status = 'topshirildi';
        //     $item->top_sana = now()->format('Y-m-d H:i:s');
        //     $item->top_user = Auth::id();
        //     $item->save();
        // }


        if ($last_price > 0) {
            $user = User::query()->where(['id' => Auth::id()])->first();
            if (Auth::user()->role !== 'saygak') {
                if ($tolov_turi == 'naqd') {
                    $user->balance += $last_price;
                    $user->save();
                } else {
                    if ($tolov_turi == 'Terminal-bank') {
                        $user->plastik += $last_price;
                        $user->save();
                    } elseif ($tolov_turi == 'click') {
                        $user->click += $last_price;
                        $user->save();
                    }
                }
                $kirim = new MijozKirim();
                $kirim->summa = $last_price;
                $kirim->costumer_id = $order->costumer_id;
                $kirim->order_id = $order->order_id;
                $kirim->date = \date('Y-m-d H:i:s');
                $kirim->status = 'olindi';
                $kirim->tolov_turi = $tolov_turi;
                $kirim->user_id = \auth()->id();
                $kirim->kirim_izoh = '';
                $kirim->filial_id = Auth::user()->filial_id;
                $kirim->kassachi_id = Auth::id();
                $kirim->user_fullname = '';
                $kirim->kassachi_fullname = '';
                $kirim->costumer = '';
                //                $kirim->load(Yii::$app->request->post());
                $kirim->save();
            }
            if (Auth::user()->role == 'transport' and Auth::user()->kpi < 100 || Auth::user()->role == 'joyida_yuvish' and Auth::user()->role < 100) {
                $kpi_summa = $last_price * Auth::user()->kpi / 100;
            } else {
                $kpi_summa = 0;
            }


            //kpi hisobiga role saygak bolsa ham tushmaydi chunki summa 0
            $kpi_h = new KpiHisob();
            $kpi_h->user_id = Auth::id();
            $kpi_h->summa = $kpi_summa;
            $kpi_h->filial_id = Auth::user()->filial_id;
            $kpi_h->date = \date('Y-m-d H:i:s');

            //agar kpi summa nol bo'lsa saqlanmaydi
            if ($kpi_h->summa > 0) {
                $kpi_h->save();
            }
            $user->oylik += $kpi_summa;
            $user->save();

            $order->order_filial_id = Auth::user()->filial_id;

            $order->top_sana = \date('Y-m-d H:i:s');
            $order->order_price_status = Auth::user()->role;
            $order->finish_driver = Auth::id();
            $yakuniy_min_sum = ($total_with_discount_cost * $summa->topshir_sum_min) / 100;

            if ($last_price < $tushib_berish) {
                $nasiya = new Nasiya();
                $nasiya->summa = $yakuniy - $last_price;
                $nasiya->nasiya = $yakuniy - $last_price;
                $nasiya->nasiyachi_id = $order->costumer_id;
                $nasiya->order_id = $order->order_id;
                $nasiya->ber_date = now()->format('Y-m-d H:i:s');
                $nasiya->filial_id = Auth::user()->filial_id;
                $nasiya->ber_date = now()->format('Y-m-d H:i:s');
                $nasiya->status = '0';
                //                $last_price += $old_last_price;

                $order->order_price = $last_price + $old_last_price;
                if ($nasiya->save() and $order->save()) {
                    return response([
                        'message' => ' Buyurtma topshirildi!',
                        'nasiya_id' => $nasiya->id,
                    ]);
                }
            } else {
                $order->order_price = $last_price + $old_last_price;
                if ($order->save()) {
                    return response(['message' => ' Buyurtma topshirildi!', 'nasiya_id' => null]);
                }
            }
        } else {
            return response(['message' => 'Ma`lumotlar saqlandi!']);
        }
    }

    public function tt()
    {
        return 'tt';
    }

    public function talking()
    {
        $date = date('Y-m-d', strtotime('-6 months'));
        $calling = Order::query();

        $calling = $calling->select(["costumer_id"])
            ->where(['order_filial_id' => Auth::user()->filial_id, 'last_operator_id' => 0, 'order_status' => 'topshirildi'])
            ->havingRaw("max(DATE(order_date)) > $date")
            ->havingRaw("max(DATE(order_date)) < $date")
            ->groupBy('costumer_id')
            ->get();
        return $calling;
    }

    public function qaytaview($order_id)
    {
        $clean = Clean::where([
            'clean_filial_id' => Auth::user()->filial_id, 'clean_status' => 'qayta keltirish',
            'reclean_place' => 3, 'order_id' => $order_id
        ]);

        if (Auth::user()->role == 'saygak')
            $clean = $clean->where(['reclean_driver' => Auth::id()]);
        else if (Auth::user()->role != 'admin_filial')
            $clean = $clean->whereIn('reclean_driver', [0, Auth::id()]);

        return response($clean->get());
    }

    public function qaytaviewstore($order_id, Request $request)
    {
        $clean = Clean::find($order_id);
        $cleans = Clean::where('clean_filial_id', Auth::user()->filial_id)
            ->where('reclean_place', 3)
            ->where('order_id', $clean->order_id)
            // ->where(function ($query) {
            ->where('clean_status', 'qayta keltirish');
        // ->orWhere('clean_status', 'qadoqlandi')
        // });
        if (empty($cleans)) {
            return response(['message' => 'Bunday clean mavjud emas'], 404);
        }

        if (Auth::user()->role == 'saygak')
            $cleans = $cleans->where(['reclean_driver' => Auth::id()]);
        else if (Auth::user()->role != 'admin_filial')
            $cleans = $cleans->whereIn('reclean_driver', [0, Auth::id()]);
        $status = $request->input('status');

        if ($status == 'check') {
            $clean = Clean::find($order_id);
            if (is_null($clean)) {
                return response(['message' => 'Bunday clean mavjud emas'], 404);
            }
            $clean->update(['clean_status' => 'qayta yuvish']);
            if ($clean) {

                if ($cleans->count() == 0) {
                    $clean->order()->update(['order_status' => 'qayta yuvish']);
                    if ($clean) {
                        return response(['message' => 'Buyurtma qayta yuvishga olindi']);
                    } else {
                        return response(['message' => 'Clean orderi update bo`lmadi']);
                    }
                }
            } else {
                return response(['message' => 'Clean update bo`lmadi']);
            }
            return response(['message' => 'Buyurtma qayta yuvishga olindi']);
        }

        if ($status == 'cancel') {
            $clean = Clean::find($order_id);
            if (is_null($clean)) {
                return response(['message' => 'Bunday clean mavjud emas'], 404);
            }
            $clean->update(['clean_status' => 'topshirildi']);
            if ($cleans->count() == 0) {
                $clean->order()->update(['order_status' => 'topshirildi']);
                return response(['message' => 'Buyurtma qayta yuvishga olindi']);
            }
            return response(['message' => 'Buyurtma qayta yuvishga olindi']);
        }
    }

    public function cancel($order_id, Request $request)
    {
        $order = Order::where(['order_filial_id' => Auth::user()->filial_id, 'order_id' => $order_id]);
        if (!$order->first())
            return response(['message' => 'bunday buyurtma topilmadi'], 404);

        $costumer = Costumers::where(['costumers_filial_id' => Auth::user()->filial_id, 'id' => $order->first()->costumer_id])->first();

        $order->update([
            'izoh' => $request->input('izoh') ? $request->input('izoh') : '',
            'order_status' => 'bekor qilindi'
        ]);
        $costumer->update([
            'costumer_status' => 'kutish',
        ]);
        return response(['message' => 'buyurtma bekor qilindi'], 200);
    }

    public function driver(Request $request, $order_id)
    {
        $driver = $request->input('driver');
        $order = Order::where(['order_id' => $order_id, 'order_filial_id' => Auth::user()->filial_id]);
        if (!$order->first())
            return response(['message' => 'not found order'], 404);

        $order->update([
            'order_driver' => $driver
        ]);
        return response(['message' => 'haydivchi o`zgartirildi'], 200);
    }

    public function foiz($order_id, Request $request)
    {
        $order = Order::where(['order_filial_id' => Auth::user()->filial_id, 'order_id' => $order_id, 'order_status' => 'keltirish']);
        $xizmatlar = Xizmatlar::where(['filial_id' => Auth::user()->filial_id, 'status' => 'active'])->get();
        if (!$order->first())
            return response(['message' => 'not found order'], 404);
        $a = [];
        foreach ($xizmatlar as $x) :
            $ch = Chegirma::where(['order_id' => $order->first()->order_id, 'xizmat_id' => $x->xizmat_id])->first();
            if ($ch)
                $sum = $ch->summa;
            else
                $sum = 0;
            $a[] = [
                'xizmat_id' => $x->xizmat_id,
                'chegirma' => $sum
            ];
        endforeach;

        return $a;
    }

    public function item_cancel($order_id, Request $request)
    {
        $id = $request->input('id');
        $qayta_yuvish = Clean::where(['order_id' => $order_id])->count();

        if (empty($qayta_yuvish)) {
            return response('Bunday order_id li clean topilmadi', 404);
        }
        if ($id) {
            $clean = Clean::where(['id' => $id]);
            if (empty($clean)) {
                return response('Bunday clean topilmadi', 404);
            }
            $clean->update(['clean_status' => "bekor qilindi"]);
            if ($qayta_yuvish == 0) {
                $cl = Clean::where(['id' => $id]);
                $cl->first()->order->update(['order_status' => 'bekor qilindi']);
                return response(["success" => "Buyurtmalar qayta yuvishga olindi"]);
            }
            return response(['message' => 'Buyurtma qayta yuvilishi bekor qilindi']);
        } else {
            return response('Id bo`sh bo`lmasligi kerak', 200);
        }
    }

    public function end($order_id, Request $request)
    {
        $order = Order::query();
        $order = $order->where(['order_filial_id' => Auth::user()->filial_id, 'order_id' => $order_id])->first();
        $cleans = Clean::query();
        $cleans = $cleans->where(['clean_filial_id' => Auth::user()->filial_id, 'order_id' => $order_id]);
        $summa = NasiyaBelgilash::where(['filial_id' => Auth::user()->filial_id])->first();
        $nasiaya_belgilash = NasiyaBelgilash::where(['filial_id' => Auth::user()->filial_id])->first();
        $qayta_yuv = Clean::where(['order_id' => $order_id, 'clean_status' => 'qayta yuvish'])
            ->orWhere(['order_id' => $order_id, 'clean_status' => 'yuvilmoqda']);
        $qayta_qad = Clean::where(['order_id' => $order_id, 'clean_status' => 'qayta quridi'])
            ->orWhere(['order_id' => $order_id, 'clean_status' => 'quridi']);

        $user = User::where(['id' => Auth::id()]);

        $total_absolute_cost = $cleans->sum('clean_narx');
        $total_with_discount_cost = 0;
        if ($order->own == 0) {
            foreach ($cleans->get() as $clean) {
                if ($clean->reclean_place < 3) {
                    $total_with_discount_cost += $clean->clean_narx;
                }
            }
            $skidka = $order->order_skidka_sum + ($total_with_discount_cost * $order->order_skidka_foiz / 100);
            $yakuniy_min_sum = ($total_with_discount_cost * $summa->topshir_sum_min) / 100;
            if ($skidka <= $total_with_discount_cost) {
                $total_with_discount_cost -= $skidka;
            } else {
                $total_with_discount_cost = 0;
            }
            $discount_for_own = 100;
        } else {
            foreach ($cleans->get() as $clean) {
                if ($clean->reclean_place < 3) {
                    $total_with_discount_cost += $clean->xizmatlar->discount_for_own * $clean->clean_hajm;
                }
            }
            if ($total_with_discount_cost > 0) {
                $discount_for_own = $total_with_discount_cost / $total_absolute_cost * 100;
            } else {
                $discount_for_own = 100;
            }
        }


        $yakuniy = $total_with_discount_cost;

        //agar topshirayotgan odam saygak bolsa buyurtmada nasiya belgilash summasi ishlayamaydi
        if (Auth::user()->role == 'saygak') {
            $tushib_berish = $yakuniy - $clean->sum('clean_narx');
        } else {

            if ($nasiaya_belgilash) {
                if ($nasiaya_belgilash->foiz == 0) {
                    $tsh_foiz = 1;
                } else {
                    $tsh_foiz = $nasiaya_belgilash->foiz;
                }

                $tsh_sum = $nasiaya_belgilash->summa;
            } else {
                $tsh_foiz = 1;
                $tsh_sum = 0;
            }

            $tushib_berish = $yakuniy - ($total_absolute_cost * $tsh_foiz / 100) - $tsh_sum;
        }

        //agar yakuniy summa noldan kichik boladigan bolsa bunda yakuniy summa nol boladi va keyingi chegirma hisoblanadi
        if ($yakuniy < 0) {
            $chegirma_sum = abs($yakuniy);
            $yakuniy = 0;
        } else {
            $chegirma_sum = 0;
            $yakuniy = floor($yakuniy);
        }


        //summalarni yaxlitlash
        $tushib_berish = floor($tushib_berish);

        //buyurtmaning jami qiymatini hisoblash
        //       $order->order_price = $order->getClean()->sum('clean_narx');
        $order_price = $cleans->sum('clean_narx');


        if ($order->order_last_price > ($yakuniy + $yakuniy / 2) || $order->order_last_price < ($yakuniy / 2)) {
            return response(['message' => 'summma notog`ri kiritildi']);
        }

        if ($qayta_yuv->count() > 0 || $qayta_qad->count() > 0) {
            // $order->avans = $chegirma_sum;
            if ($qayta_yuv->count() > 0) {
                $order_status = 'qayta yuvish';
            } elseif ($qayta_qad->count() > 0 && $qayta_yuv->count() == 0) {
                $order_status = 'qayta qadoqlash';
            }
        } else {
            $order_status = 'topshirildi';
            // $order->avans = 0;
        }

        if ($order->order_last_price > 0) {


            //agar role saygak boladigan bolsa uning balanciga olgan summasi
            if (Auth::user()->role !== 'saygak') {
                $tolov_turi = $request->input('tolov_turi');
                if ($tolov_turi == 'naqd') {
                    $user->update([
                        'balance' => $user->first()->balance + $order->order_last_pricem
                    ]);
                } else {

                    if ($tolov_turi == 'Terminal-bank') {

                        $user->update([
                            'plastik' => $user->first()->plastik + $order->order_last_pricem
                        ]);
                    } elseif ($tolov_turi == 'click') {

                        $user->update([
                            'click' => $user->first()->click + $order->order_last_pricem
                        ]);
                    }
                }
            }

            MijozKirim::create([
                'summa' => $order->order_last_price,
                'costumer_id' => $order->costumer_id,
                'order_id' => $order->order_id,
                'date' => \date('Y-m-d H:i:s'),
                'status' => 'olindi',
                'tolov_turi' => $request->input('tolov_turi')
            ]);
        }

        if (Auth::user()->role == 'transport' && Auth::user()->kpi < 100 || Auth::user()->role == 'joyida_yuvish' && Auth::user()->kpi < 100) {
            $kpi_summa = $order->order_last_price * Auth::user()->kpi / 100;
        } else {
            $kpi_summa = 0;
        }

        $yakuniy_min_sum = ($total_with_discount_cost * $summa->topshir_sum_min) / 100;

        if ($order->order_last_price < $tushib_berish) {

            $nasiya = Nasiya::create([
                'summa' => $yakuniy - $order->order_last_price,
                'nasiya' => $yakuniy - $order->order_last_price,
                'nasiyachi_id' => $order->costumer_id,
                'order_id' => $order->$order->order_id,
                'ber_date' => \date('Y-m-d H:i:s'),
                'filial_id' => Auth::user()->filial_id,
                'status' => '0',
            ]);
            $ord = $order->update([
                'order_price' => $order_price,
                'order_status' => $order_status,
                'order_skidka_foiz' => 0,
                'order_skidka_sum' => 0,
                'top_sana' => now()->format('Y-m-d H:i:s'),
                'order_price_status' => Auth::user()->role,
                'finish_driver' => Auth::id(),
                'eski_last_price' => $order->eski_last_price
            ]);
            if ($nasiya && $ord) {
                return response(['message' => 'Buyurtma topshirildi']);
            }
        } else {
            $ord = $order->update([
                'order_price' => $order_price,
                'order_status' => $order_status,
                'order_skidka_foiz' => 0,
                'order_skidka_sum' => 0,
                'top_sana' => now()->format('Y-m-d H:i:s'),
                'order_price_status' => Auth::user()->role,
                'finish_driver' => Auth::id(),
                'eski_last_price' => $order->eski_last_price
            ]);
            //           $order->order_last_price += $eski_last_price;
            if ($ord) {
                return response(['message' => 'Buyurtma topshirildi']);
            }
        }
    }

    public function yuvish(Request $request)
    {
        $limit = $request->get('limit');
        $status = $request->get('status');
        $p = $request->get('page');
        if (!$limit)
            $limit = 15;

        $order = Order::query()->where(['order_filial_id' => Auth::user()->filial_id]);
        $cleans = Clean::query();

        if ($status == 'yuvish') {
            $cleans = $cleans
                ->select('order_id')
                ->where(['clean_filial_id' => Auth::user()->filial_id])
                // ->where(['clean_status' => 'yuvilmoqda'])
                ->where(function ($query) {
                    $query->where('clean_status', 'yuvilmoqda');
                })
                ->orWhere(['clean_status' => 'olchov'])
                ->groupBy('order_id')
                ->orderBy('created_at', 'desc')
                ->get();
            $all = [];
            foreach ($cleans as $clean) {
                $order = Order::query()->where(['order_filial_id' => Auth::user()->filial_id])
                    ->where(['order_id' => $clean->order_id])
                    ->with('custumer')
                    ->where(function ($query) {
                        $query->where('order_status', 'yuvilmoqda');
                    })
                    ->orderBy('order_date', 'desc')
                    ->first();
                if ($order)
                    $all[] = $order;
            }

            $pages = !empty($request->input('page')) ? (int)$request->input('page') : 1;
            $total = count($all);
            $limits = 50;
            $totalPages = ceil($total / $limits);
            $pages = max($pages, 1);
            $pages = min($pages, $totalPages);
            $offset = ($pages - 1) * $limits;

            $pg = array_slice($all, $offset, $limits);
            //            $order = $order->paginate($limit);
            return ['data' => $pg, 'totalCount' => $total, 'totalPages' => $totalPages, 'onePage' => $limit];
        }
        if ($status == 'qayta') {
            $cleans = $cleans
                ->select('order_id')
                ->where(['clean_filial_id' => Auth::user()->filial_id])
                ->where(function ($query) {
                    $query->where('clean_status', 'qayta yuvish');
                })
                ->groupBy('order_id')
                ->orderBy('created_at', 'desc')
                ->get();
            $all = [];
            foreach ($cleans as $clean) {
                $order = Order::query()
                    ->where(['order_filial_id' => Auth::user()->filial_id])
                    ->where(['order_id' => $clean->order_id])
                    ->where(function ($query) {
                        $query->where('order_status', 'qayta yuvish');
                    })
                    ->with('custumer')
                    ->orderBy('order_date', 'desc')
                    ->first();
                if ($order) {
                    $all[] = $order;
                }
            }

            $pages = !empty($request->input('page')) ? (int)$request->input('page') : 1;
            $total = count($all);
            $limits = 50;
            $totalPages = ceil($total / $limits);
            $pages = max($pages, 1);
            $pages = min($pages, $totalPages);
            $offset = ($pages - 1) * $limits;

            $pg = array_slice($all, $offset, $limits);
            //            $order = $order->paginate($limit);
            return ['data' => $pg, 'totalCount' => $total, 'totalPages' => $totalPages, 'onePage' => $limit];
        }
    }

    public function yuvildi($clean_id, Request $request)
    {
        $hajm = $request->input('hajm');
        $eni = $request->input('gilam_eni');
        $boyi = $request->input('gilam_boyi');
        $clean_price = $request->input('clean_price');

        if ($clean_price == 0)
            return response(['message' => 'narx 0 dan kotta bolishi kerak']);

        $clean = Clean::query();
        $clean = $clean->where(['clean_filial_id' => Auth::user()->filial_id, 'id' => $clean_id])->first();
        if (!$clean)
            return response(['message' => 'not found clean']);

        $order = Order::where(['order_filial_id' => Auth::user()->filial_id, 'order_id' => $clean->order_id])->first();
        if (!$order)
            return response(['message' => 'not found order'], 404);

        $xizmat = Xizmatlar::where(['xizmat_id' => $clean->clean_product])->first();
        $kpi = Kpi::where(['xizmat_id' => $clean->clean_product, 'status' => 1, 'user_id' => Auth::id()])
            ->orWhere(['xizmat_id' => $clean->clean_product, 'status' => 5, 'user_id' => Auth::id()]);

        if ($kpi->count() > 0)
            $narx = $kpi->first()->summa;
        else
            $narx = 0;

        $clena_old_price = $clean->clean_narx;
        $clean->user_id = \auth()->id();

        $clean->gilam_eni = !empty($eni) ? $eni : 0;
        $clean->gilam_boyi = !empty($boyi) ? $boyi : 0;

        $clean->narx = $clean_price;

        if ($hajm == 0.0)
            $clean->clean_hajm = 0;
        if ($xizmat->olchov == 'dona')
            $clean->clean_hajm = 1;
        else
            $clean->clean_hajm = $hajm;

        $clean->clean_narx = $clean->narx * ($hajm == 0.0 ? 1 : $hajm);
        $order->order_price = $clean->clean_narx - $clena_old_price;
        $kpi_summa = $narx * $clean->clean_hajm;
        $davomat = Davomat::whereDate('sana', '=', \date('Y.m.d'))
            ->where(['filial_id' => Auth::user()->filial_id])
            ->where('status', '!=', 0)
            ->get();
        foreach ($davomat as $item) {
            $kpi_user = User::where(['id' => $item->id])->first();
            if (\auth()->user()->role == 'yuvish') {
                $kpi_user->oylik += $kpi_summa;
                $kpi_user->save();
                $h_kpi = new Kpi();
                $h_kpi->user_id = $item->user_id;
                $h_kpi->summa = $kpi_summa;
                $h_kpi->clean_id = $clean->id;
                $h_kpi->filial_id = Auth::user()->filial_id;
                $h_kpi->date = now()->format('Y-m-d H:i:s');
                if ($h_kpi->summa > 0)
                    $h_kpi->save();
            }
        }

        if ($clean->clean_status == 'qayta yuvish') {
            $clean->clean_status = 'qayta quridi';
        } elseif ($clean->clean_status == 'olchov' || $clean->clean_status == 'yuvilmoqda') {
            $clean->clean_status = 'quridi';
        }

        $clean->clean_date = \date('Y-m-d H:i:s');
        if ($clean->clean_hajm > 0) {

            $modelFind = Clean::where('order_id', $clean->order_id)
                ->where(function ($query) {
                    $query->where('clean_status', 'yuvilmoqda')->orwhere('clean_status', 'qayta yuvish');
                })
                ->count();
            if ($modelFind <= 1) {
                if ($order->order_status == 'qayta yuvish') {
                    $order->order_status = 'qayta quridi';
                } else {
                    $order->order_status = 'quridi';
                }
            }

            if ($clean->save() and $order->save())
                return response(['message' => 'yuvildi ' . $modelFind]);
        } else
            return response(['message' => 'Hajm 0 dan katta bo`lishi kerak!']);
    }

    public function yakunGet($order_id, Request $request)
    {
        if (empty($order_id)) {
            return response('Order id bo`sh bo`lmasilig kerak', 404);
        }

        $order = Order::query();
        $order = $order->where(['order_filial_id' => Auth::user()->filial_id, 'order_id' => $order_id])
            ->with('custumer')
            ->with('operator:id,fullname')
            ->with(['cleans' => function ($query) {
                // $query->where('clean_status', 'qadoqlandi')
                // ->orwhere('clean_status', 'qayta qadoqlandi')
                $query->with('xizmat');
            }])
            ->withSum([
                'chegirma' => fn ($query) => $query->select(DB::raw('COALESCE(SUM(summa),0)')),
            ], 'summa')
            ->first();
        $summa = NasiyaBelgilash::where(['filial_id' => Auth::user()->filial_id])->first();
        $cleans = Clean::where(['clean_filial_id' => Auth::user()->filial_id, 'order_id' => $order->order_id])->where(function ($query) {
            $query->where('clean_status', 'qadoqlandi')->orwhere('clean_status', 'qayta qadoqlandi');
        })->get();
        $total_absolute_cost = Clean::where(['clean_filial_id' => Auth::user()->filial_id, 'order_id' => $order_id])
            ->sum('clean_narx');
        $buyurtma = Buyurtma::query()
            ->where(['filial_id' => Auth::user()->filial_id, 'order_id' => $order->order_id])
            ->get();
        $total_with_discount_cost = 0;
        if (!$order)
            return response(['message' => 'not found order']);

        $discount_for_own = 0;
        if ($order->own == 0) {
            foreach ($cleans as $clean) {
                if ($clean->reclean_place < 3) {
                    $total_with_discount_cost += $clean->clean_narx;
                }
            }
            $skidka = $order->order_skidka_sum + ($total_with_discount_cost * $order->order_skidka_foiz / 100);

            $yakuniy_min_sum = ($total_with_discount_cost * $summa->topshir_sum_min) / 100;

            if ($skidka <= $total_with_discount_cost) {
                $total_with_discount_cost -= $skidka;
            } else {
                $total_with_discount_cost = 0;
            }
            $discount_for_own = 100;
        } else {
            foreach ($cleans->all() as $item) {

                /// if it has not recleaned
                if ($item->reclean_place < 3) {

                    $total_with_discount_cost += $item->xizmat->discount_for_own * $item->clean_hajm;
                }
            }

            if ($total_with_discount_cost > 0) {
                $discount_for_own = $total_with_discount_cost / $total_absolute_cost * 100;
            } else {
                $discount_for_own = 100;
            }
        }
        $yakuniy = $total_with_discount_cost;

        if ($yakuniy < 0) {
            $chegirma_sum = abs($yakuniy);
            $yakuniy = 0;
        } else {
            $chegirma_sum = 0;
            $yakuniy = floor($yakuniy);
        }

        $jami_sum = 0;
        $all = [];
        foreach ($buyurtma as $item) {
            $cleann = Clean::query()
                ->where('clean_filial_id', '=', auth()->user()->filial_id)
                ->where('reclean_place', '!=', 3)
                ->where('order_id', $order->order_id)
                ->where('clean_product', $item->x_id)
                ->where(function ($query) {
                    $query->where('clean_status', 'qadoqlandi')
                        ->orWhere('clean_status', 'qayta qadoqlandi');
                })
                ->sum('clean_narx');
            $jami_sum += $cleann;
        }

        return [
            'data' => $order,
            'jami_summa' => $jami_sum,
            'yakuniy_tolov' => $yakuniy,
            'qayta_yuvish_chegirma' => 0,
            'discount_for_own' => 100 - $discount_for_own,
            'tushish_chegirma' => 0,
            'jami_chegirma' => 0,
        ];
    }

    public function addProduct($order_id, Request $request)
    {
        //        $this->validate($request,[
        //           'product'=>'required',
        //            'date'=>'required',
        //            'izoh'=>'required'
        //        ]);

        $avans = $request->input('avans');
        $avans_type = $request->input('avans_type');
        $date = $request->input('date');
        $izoh = $request->input('izoh');
        $own = $request->input('own');
        $dog = $request->input('dog');
        $brak = $request->input('brak');

        if (!$date)
            return response(['message' => 'date empty'], 422);

        if (!$request->input('products'))
            return response(['message' => 'products empty'], 422);
        $order = Order::where(['order_filial_id' => Auth::user()->filial_id, 'order_id' => $order_id])->first();
        $costumer = Costumers::query();
        $costumer = $costumer->where(['costumers_filial_id' => Auth::user()->filial_id, 'id' => $order->costumer_id])->first();
        $xizmatlar = Xizmatlar::where(['filial_id' => Auth::user()->filial_id])->get();
        if (!$order)
            return response(['message' => 'Not found Order'], 404);


        $order->order_filial_id = Auth::user()->filial_id;
        $order->order_driver = \auth()->id();
        $order->costumer_id = $order->costumer_id;
        $order->avans = $request->input('avans') ? $request->input('avans') : 0;
        $order->avans_type = $request->input('avans_type') ? $request->input('avans_type') : '';

        $order->izoh3 = $izoh ? $izoh : '';
        $order->own = $own;
        $order->dog = $dog ? $dog : '';
        $order->brak = $brak ? $brak : '';
        $order->order_price = 0;
        $order->order_last_price = 0;
        $order->finish_driver = 0;
        $order->order_price_status = "yoq";
        // $order->avans_status = "yoq";
        $order->tartib_raqam = 0;
        $order->order_status = 'qabul qilindi';
        $order->olibk_sana = \date('Y-m-d');
        $order_jami = 0;

        $xizmatlar_array = [];
        $order_jami = 0;

        if (Auth::user()->role == 'saygak') {
            $order->saygak_id = Auth::id();
        }
        if (Auth::user()->role == 'joyida_yuvish') {
            $order->topshir_sana = now()->format('Y-m-d H:i:s');
            $order->joyida = 1;
        }
        if (!$order->geoplugin_longitude) {
            $order->geoplugin_longitude = "bosh";
            $order->geoplugin_latitude = "bosh";
        }


        foreach ($request->input('products') as $item) {
            $xizmatlar_array['xizmat' . $item['id']] = (int)$item['count'];
        }

        foreach ($xizmatlar as $xizmat) {
            if (array_key_exists('xizmat' . $xizmat->xizmat_id, $xizmatlar_array)) {
                //                return response(['message' => 'bunday xizmat topilmadi ' . $xizmat->xizmat_id, 'data' => $xizmatlar_array]);
                $b_name = $xizmatlar_array['xizmat' . $xizmat->xizmat_id];
                //                    return $b_name;
                if (intval($b_name) > 0) {
                    $order_jami += $b_name;
                }
            }
        }

        if ($avans > 0) {

            $kirim = new MijozKirim();
            $kirim->summa = $order->avans;
            $kirim->costumer_id = $order->costumer_id;
            $kirim->order_id = $order->order_id;
            $kirim->date = \date('Y-m-d');
            $kirim->status = 'olindi';
            $kirim->tolov_turi = $order->avans_type;
            $kirim->user_id = Auth::id();
            $kirim->kirim_izoh = "";
            $kirim->filial_id = Auth::user()->filial_id;
            $kirim->user_fullname = '';
            $kirim->kassachi_fullname = '';
            $kirim->costumer = '';
            $kirim->kassachi_id = Auth::id();
            $kirim->save();
        }
        if ($order_jami > 0) {
            if ($order->save()) {
                foreach ($xizmatlar as $xizmat) {
                    if (array_key_exists('xizmat' . $xizmat->xizmat_id, $xizmatlar_array)) {
                        //                            return response(['message'=>'bunday xizmat topilmadi'. $xizmat->xizmat_id]);
                        //                            break;
                        $b_val = $xizmatlar_array['xizmat' . $xizmat->xizmat_id];

                        if ($b_val > 0) {
                            $new_b = new Buyurtma();
                            $new_b->x_id = $xizmat->xizmat_id;
                            $new_b->status = 1;
                            $new_b->value = $b_val;
                            $new_b->filial_id = Auth::user()->filial_id;
                            $new_b->order_id = $order->order_id;
                            $new_b->save();
                        }
                    }
                }
            }
            $costumer->update([
                'costumers_filial_id' => Auth::user()->filial_id,
                'costumer_status' => 'kutish',
            ]);
            return response(['message' => 'maxsulot qo`shildi'], 200);
        } else {
            return response(['message' => 'xech bolmaganda bitta maxsulot kiriting']);
        }
    }

    public function razmer($order_id, Request $request)
    {
        $product = $request->input('product');
        $hajm = $request->input('hajm');
        $eni = $request->input('eni');
        $boyi = $request->input('boyi');
        $narx = $request->input('narx');

        $the_role = Auth::user()->role;
        $order = Order::where(['order_filial_id' => Auth::user()->filial_id, 'order_id' => $order_id])->first();
        $clean = Clean::where(['clean_filial_id' => Auth::user()->filial_id, 'order_id' => $order->order_id, 'id' => $product])->first();
        $xizmat = Xizmatlar::where(['xizmat_id' => $clean->clean_product, 'filial_id' => Auth::user()->filial_id])->first();
        $chegirma = Chegirma::where(['order_id' => $order->order_id])->first();

        $cheg_narx = $xizmat->narx = $narx;

        if (!$order)
            return response(['message' => 'not found order']);

        if ($the_role == "transport" or $the_role == "tayorlov" or $the_role == "yuvish" or $the_role == "saygak") {
            if ($hajm and $hajm > 60) {
                return response(['message' => 'Gilam hajmi 60 kv.metr dan oshmasligi kerak']);
            }
        }

        if (!$chegirma) {
            $chegirma_add = new Chegirma();
            $chegirma_add->order_id = $order->order_id;
            $chegirma_add->xizmat_id = $clean->id;
            $chegirma_add->summa = $cheg_narx;
            $chegirma_add->save();
        } else {
            $chegirma_one = Chegirma::where(['order_id' => $order->order_id])->first();
            $chegirma_one->summa = $cheg_narx;
            $chegirma_one->save();
        }

        $eski_narx = $clean->clean_narx;
        $clean->gilam_eni = $eni ? $eni : 0;
        $clean->gilam_boyi = $boyi ? $boyi : 0;

        if ($xizmat->olchov == 'dona')
            $clean->clean_hajm = 1;
        else
            $clean->clean_hajm = $hajm;

        $clean->clean_narx = $clean->narx * $clean->clean_hajm;


        if ($order->joyida == 1) {

            // Clean countlarini olamiz
            $OrderCount = Clean::query()
                ->where(['clean_filial_id' => Auth::user()->filial_id, 'order_id' => $order->order_id])
                ->where('clean_status', '!=', 'qadoqlandi')
                ->count();
            if ($OrderCount <= 1) {
                $order->order_status = 'qadoqlandi';
            }
            $clean->clean_status = 'qadoqlandi';
            $clean->joyida_user = Auth::user()->filial_id;
            $clean->joyida_date = \date('Y-m-d');
        } else {

            // Clean countlarini olamiz
            $OrderCount = Clean::query()
                ->where(['clean_filial_id' => Auth::user()->filial_id, 'order_id' => $order->order_id])
                ->where('clean_status', '!=', 'yuvilmoqda')
                ->count();
            if ($OrderCount <= 1) {
                $order->order_status = 'yuvilmoqda';
            }
            $clean->clean_status = 'yuvilmoqda';
        }


        $clean->clean_filial_id = Auth::user()->filial_id;

        $order->order_price = $order->order_price - $eski_narx + $clean->clean_narx;


        if ($clean->save() and $order->save()) {
            return response(['message' => 'olchandi ' . $OrderCount], 200);
        }
    }

    public function qabul(Request $request)
    {
        $status = $request->get('status');
        $limit = $request->get('limit');
        if (!$limit)
            $limit = 50;
        $order = Order::query();
        $order = $order->where(['order_filial_id' => Auth::user()->filial_id]);


        $the_role = Auth::user()->role;
        $the_filial_id = Auth::user()->filial_id;


        if ($status == 'yangi') {
            if ($the_role == 'admin_filial') {
                $keltirish = Order::find()->where(['order_status' => 'keltirish', 'order_filial_id' => $the_filial_id]);
            } elseif ($the_role == 'saygak') {
                $keltirish = Order::find()->where(['order_status' => 'keltirish', 'order_filial_id' => $the_filial_id, 'order_driver' => Auth::id()]);
            } elseif ($the_role == 'transport') {
                $keltirish = Order::find()->where(['order_status' => 'keltirish', 'order_filial_id' => $the_filial_id, 'order_driver' => 'hamma'])
                    ->orWhere(['order_status' => 'keltirish', 'order_filial_id' => $the_filial_id, 'order_driver' => Auth::id()]);
            } else {
                $keltirish = Order::find()->where([
                    'order_status' => 'keltirish', 'order_filial_id' => $the_filial_id,
                    'order_driver' => Auth::id()
                ]);
            }
            return $keltirish->with('custumer')->paginate($limit);
        }


        if ($status == 'yakunlanmagan') {
            $qayta_keltirish = Clean::find()->select("order_id, reclean_driver, costumer_id")->where(['and', ['clean_status' => 'qayta keltirish', 'reclean_place' => 3], ["clean_filial_id" => $the_filial_id]]);
            if ($the_role == 'saygak') {
                $qayta_keltirish = $qayta_keltirish->andWhere(['reclean_driver' => Auth::id()]);
            } else if ($the_role !== "admin_filial") {
                $qayta_keltirish = $qayta_keltirish->whereIn('reclean_driver', [0, Auth::id()]);
            }
            $qayta_keltirish = $qayta_keltirish->with("orders")->with("driver")->groupBy("order_id, reclean_driver");
            return $qayta_keltirish->with('orders.custumer')->paginate($limit);
        }
    }

    public function add($costumer_id, Request $request)
    {
        $costumer = Costumers::where(['costumers_filial_id' => Auth::user()->filial_id, 'id' => $costumer_id])->first();
        if (!$costumer)
            return response(['message' => 'not found costumer']);
        $keltirish = Order::where(['order_filial_id' => Auth::user()->filial_id, 'costumer_id' => $costumer->id])
            ->where(function ($query) {
                $query->where(['order_status' => 'keltirish'])
                    ->orWhere(['order_status' => 'qabul qilindi']);
            })->first();

        if ($keltirish)
            return response([
                'message' => 'Ushbu mijozda Yangi buyurtma allaqachon mavjud.',
                'order_id' => -1,
                //                'qabul'=>$keltirish == 'keltirish' ? 1:0,
                //                'tasdiq'=>$keltirish == 'qabul qilindi' ? 1:0,

            ]);
        else {
            $order_last = Order::where(['order_filial_id' => Auth::user()->filial_id])
                ->whereYear('order_date', \date('Y'))
                ->max('nomer');

            if (Auth::user()->role == 'saygak')
                $saygak_id = Auth::id();
            else
                $saygak_id = 0;

            if ($order_last)
                $nomer = $order_last;
            else
                $nomer = 0;

            $order = Order::create([
                'costumer_id' => $costumer->id,
                'mintaqa_id' => Auth::user()->filial->mintaqa_id,
                'order_filial_id' => Auth::user()->filial_id,
                'nomer' => $nomer + 1,
                'avans' => 0,
                'avans_type' => 'bosh',
                'order_skidka_foiz' => 0,
                'order_skidka_sum' => 0,
                'ch_foiz' => 0,
                'ch_sum' => 0,
                'order_date' => date('Y-m-d H:i:s'),
                'olibk_sana' => date('Y-m-d H:i:s'),
                'izoh' => '',
                'izoh2' => '',
                'izoh3' => '',
                'order_price' => 0,
                'order_price_status' => '-',
                'order_last_price' => 0,
                'order_status' => 'keltirish',
                'operator_id' => Auth::id(),
                'order_driver' => 'hamma',
                'finish_driver' => 0,
                'geoplugin_longitude' => '_',
                'geoplugin_latitude' => '_',
                'tartib_raqam' => 0,
                'dog' => 'yo`q',
                'brak' => 'yo`q',
                'saygak_id' => $saygak_id,
                'own' => 0,
                'joyida' => 0,
                'called' => 0,
                'last_operator_id' => 0,
                'last_izoh' => '',
                'talk_type' => '',
                'last_operator_id2' => 0,
                'last_izoh2' => '',
                'talk_type2' => '',
                'talk_date2' => date('Y-m-d'),
                'ombor_user' => 0,
                'topshir_sana' => \date('Y-m-d'),
                'top_sana' => date('Y-m-d'),
                'talk_date' => date('Y-m-d'),
                'haydovchi_satus' => 0,
            ]);

            $costumer->costumer_status = 'keltirish';

            if ($order and $costumer->save()) {
                return response([
                    'message' => 'Yangi buyurtma olindi',
                    'order_id' => $order->order_id
                ]);
            }
        }
    }

    public function confirmation($order_id, Request $request)
    {
        $status = $request->input('status');
        $products = $request->input('products');
        $izoh = $request->input('izoh');

        $order = Order::query();
        $order = $order->where(['order_filial_id' => Auth::user()->filial_id, 'order_id' => $order_id])->first();
        if (!$order)
            return response(['message' => 'not found order'], 404);


        $buyurtmalar = Buyurtma::where(['order_id' => $order->order_id, 'filial_id' => Auth::user()->filial_id])->get();

        $all = [];
        foreach ($products as $product) {
            $all['buyurtma' . $product['id']] = $product['count'];
        }

        $jami = 0;
        foreach ($buyurtmalar as $item) {
            if (array_key_exists('buyurtma' . $item->id, $all)) {
                $b_val = $all['buyurtma' . $item->id];
                $item->value = $b_val;
                $item->status = 2;
                $item->save();
                $jami += $b_val;
            }
        }

        $order->order_driver = Auth::id();
        $cl_num = Clean::where(['clean_filial_id' => Auth::user()->filial_id, 'order_id' => $order])->count();

        if ($cl_num < 1) {
            foreach ($buyurtmalar as $buyurtma) {
                $xizmat = Xizmatlar::where(['filial_id' => Auth::user()->filial_id, 'xizmat_id' => $buyurtma->x_id])->first();
                for ($x = 1; $x <= $buyurtma->value; $x++) {
                    $clean = new Clean();
                    $clean->clean_filial_id = Auth::user()->filial_id;
                    $clean->order_id = $order->order_id;

                    if ($xizmat->olchov == 'dona') {
                        $clean->clean_hajm = 1;
                    } else {
                        $clean->clean_hajm = 0;
                    }

                    $chegirma = Chegirma::where([
                        "xizmat_id" => $buyurtma->x_id,
                        "order_id" => $order->order_id
                    ])->first();

                    if ($chegirma) {
                        $narx_bir = $xizmat->narx - $chegirma->summa;
                    } else {
                        $narx_bir = $xizmat->narx;
                    }

                    $clean->gilam_eni = 0;
                    $clean->gilam_boyi = 0;
                    $clean->joy = ' ';
                    $clean->clean_narx = 0;
                    $clean->narx = $narx_bir;
                    $clean->user_id = 0;
                    $clean->qad_user = 0;
                    $clean->costumer_id = $order->costumer_id;
                    $clean->clean_product = $xizmat->xizmat_id;
                    //status tanlash
                    $clean->sana = Carbon::create(1, 0, 0, 0, 0, 0);
                    $clean->clean_date = Carbon::create(1, 0, 0, 0, 0, 0);
                    $clean->qad_date = Carbon::create(1, 0, 0, 0, 0, 0);
                    $clean->top_sana = Carbon::create(1, 0, 0, 0, 0, 0);

                    $clean->barcode = '';
                    $clean->top_user = 0;
                    $clean->reclean_place = 0;
                    $clean->reclean_driver = 0;
                    $clean->qayta_sana = Carbon::create(1, 0, 0, 0, 0, 0);
                    $clean->joyida_date = Carbon::create(1, 0, 0, 0, 0, 0);
                    $clean->joyida_user = 0;
                    if ($status == 'create')
                        $clean->clean_status = 'yuvilmoqda';
                    else if ($status == 'razmer') {
                        if ($order->joyida == 1) {
                            $clean->clean_status = 'olchov';
                        } else {
                            $clean->clean_status = 'olchov';
                        }
                    }
                    $clean->save();
                }
            }
        }
        if ($status == 'create') {
            $order->order_status = 'yuvilmoqda';
            if ($order->save())
                return response(['message' => 'Buyurtma tasdiqlandi!']);
        }
        if ($status == 'razmer') {
            if ($order->joyida == 1) {
                $order->order_status = 'joyida_yuvish';
            } else {
                $order->order_status = 'olchov';
            }
            if ($order->save())
                return response(['message' => 'Buyurtma tasdiqlandi!']);
        }
    }
    public function new(Request  $request)
    {
        $status = $request->get('status');
        $limit = $request->get('limit');
        $order = Order::query();

        if (!$limit)
            $limit = 50;

        if (empty($status))
            return response(['message' => 'status empry'], 412);

        if ($status == 'new') {
            if (Auth::user()->role == 'admin_filial') {
                $order->where('order_filial_id', Auth::user()->filial_id)->where('order_status', 'keltirish');
            } else if (Auth::user()->role == 'saygak') {
                $order->where('order_filial_id', Auth::user()->filial_id)->where('order_status', 'keltirish')->where('order_driver', Auth::id());
            } else if (Auth::user()->role == 'transport') {
                $order->where('order_filial_id', Auth::user()->filial_id)
                    ->where('order_status', 'keltirish')
                    ->where(function ($query) {
                        $query->where('order_driver', 'hamma')->orWhere('order_driver', Auth::id());
                    });
            } else {
                $order->where('order_filial_id', Auth::user()->filial_id)->where('order_status', 'keltirish')->where('order_driver', Auth::id());
            }
            return $order->with('custumer')->orderBy('nomer', 'desc')->paginate($limit);
        }
        if ($status == 'yakunlanmagan') {
            $order = $order->where('order_filial_id', Auth::user()->filial_id)->orderByDesc('order_id');
            if (Auth::user()->role == 'joyida_yuvish')
                $order = $order->where('order_status', 'qabul qilindi')->orWhere('order_status', 'joyida_yuvish');
            else
                $order = $order->where('order_status', 'qabul qilindi');

            if (Auth::user()->role != 'admin_filial' or Auth::user()->role == 'hisobchi')
                $order = $order->where('order_driver', Auth::id());
            return  $order->with('custumer')->paginate($limit);
        }
    }

    public function orderCancle(Request $request)
    {
        $o_id = $request->get('id');

        if (empty($o_id)) {
            return response('"id" bo`sh bo`lmasligi kerak');
        }

        if (strlen($request->input('izoh')) < 3) {
            return response('Izoh kamida 3 ta harfdan iborat bo`lishi kerak.');
        }

        $order = Order::find($o_id);

        if (empty($order)) {
            return response('Bunday "id" li order topilmadi');
        }
        $order->order_date = now()->format('Y-m-d H:i:s');
        $order->izoh = $request->input('izoh');
        $order->order_status = 'bekor qilindi';
        $order->order_driver = auth()->user()->id;


        if ($order->save()) {
            Costumers::find($order->costumer_id)->update(['costumer_status' => 'kutish']);
            return response('success');
        } else {
            return response('"order" ni update qilishda muamo', 500);
        }
    }

    public function confirmationGET($order_id, Request  $request)
    {
        $order = Order::query();
        $order = $order->where(['order_filial_id' => Auth::user()->filial_id, 'order_id' => $order_id])->with('custumer')->first();
        $xizmat = Xizmatlar::query();
        //        $xizmat = $xizmat->where(['filial_id'=>Auth::user()->filial_id,'status'=>'active'])->get();

        $buyurtma = Buyurtma::query();
        $buyurtma = $buyurtma->where(['filial_id' => Auth::user()->filial_id, 'order_id' => $order_id])->get();


        if (!$order)
            return response(['message' => 'not found order'], 404);


        $all = [];

        $soni = [];
        $all = [];

        foreach ($buyurtma as $item) {
            $xizmat = $xizmat->where(['filial_id' => Auth::user()->filial_id, 'xizmat_id' => $item->x_id])->first();
            if ($item->x_id == $xizmat->xizmat_id) {
                $soni[] = [
                    'id' => $xizmat->xizmat_id,
                    "xizmat_turi" => $xizmat->xizmat_turi,
                    "count" =>    $item->value
                ];
            }
        }
        $all['data'] = $order;
        $all['soni'] = $soni;
        //        array_push($all,['data'=>$order,'son'=>$soni]);
        //        array_push($all,[]);
        return  $all;
    }
    public function volume($order_id, Request  $request)
    {
        $product_id = $request->input('product_id');
        $xizmat_id = $request->input('xizmat_id');
        $hajm = $request->input('hajm');
        $eni = $request->input('eni');
        $boyi = $request->input('boyi');
        $narx = $request->input('narx');

        $order = Order::where(['order_filial_id' => Auth::user()->filial_id, 'order_id' => $order_id])->first();
        if (!$order)
            return response(['message' => 'not found order'], 404);
        $clean = Clean::where(['clean_filial_id' => Auth::user()->filial_id, 'id' => $product_id])->first();
        $xizmat = Xizmatlar::where(['filial_id' => Auth::user()->filial_id, 'xizmat_id' => $clean->clean_product])->first();
        $chegirma = Chegirma::where(['order_id' => $order->order_id])->first();

        $cheg_narx = $xizmat->narx  - $narx;

        if (!$chegirma) {
            $chegirma_add = new Chegirma();
            $chegirma_add->order_id = $order->order_id;
            $chegirma_add->xizmat_id = $clean->id;
            $chegirma_add->summa = $cheg_narx;
            $chegirma_add->save();
        } else {
            $chegirma_one = Chegirma::find()->where(['order_id' => $order->order_id])->one();
            $chegirma_one->summa = $cheg_narx;
            $chegirma_one->save();
        }

        $clean->hajm = $hajm ? $hajm : 0;

        if ($xizmat->olchov == 'dona')
            $clean->hajm = 1;

        $clean->gilam_eni = $eni ? $eni : 0;
        $clean->gilam_boyi = $boyi ? $boyi : 0;
        $clean->narx = $narx;
        $clean->clean_narx = $clean->hajm * $clean->narx;

        $clean_eski_narx = $clean->clean_narx;

        if ($order->joyida == 1) {
            $clean->clean_status = 'qadoqlandi';
            $clean->joyida_user = auth()->user()->id;
            $clean->joyida_date = \date('Y-m-d H:i:s');
        } else {
            $clean->clean_status = 'yuvilmoqda';
        }
        $clean->clean_filial_id = Auth::user()->filial_id;
        $order->order_price = $order->order_price - $clean_eski_narx + $clean->clean_narx;

        if ($clean->save() and  $order->save()) {
            return  response(['message' => 'o`lchandi']);
        }
    }
    public function razmerGet($order_id, Request  $request)
    {
        $order = Order::where(['order_filial_id' => Auth::user()->filial_id, 'order_id' => $order_id])
            ->with('custumer', 'cleans', 'cleans.xizmat')
            ->first();
        $cleans = Clean::where(['clean_filial_id' => Auth::user()->filial_id, 'order_id' => $order->order_id])->get();
        $xizmat = Xizmatlar::query();
        $xizmat = $xizmat->where(['filial_id' => Auth::user()->filial_id, 'status' => 'active'])->get();

        $all = [];
        $soni = [];

        foreach ($xizmat as $item) {
            $cleans = Clean::where(['order_id' => $order->order_id, 'clean_product' => $item->xizmat_id]);
            if ($cleans->count() > 0) {
                array_push($soni, [
                    'id' => $item->xizmat_id,
                    'xizmat_turi' => $item->xizmat_turi,
                    'count' => $cleans->count(),
                ]);
            }
        }
        //            array_push($all,['data'=>$order,'soni'=>$soni]);
        $all['data']  = $order;
        $all['soni']  = $soni;
        return response()->json($all);
    }
    public function addOne($order_id, $x_id, Request  $request)
    {
        $order = Order::where(['order_filial_id' => Auth::user()->filial_id, 'order_id' => $order_id])->first();
        if (!$order)
            return response(['message' => 'not found order']);

        $xizmat = Xizmatlar::where(['xizmat_id' => $x_id])->first();
        if (!$xizmat)
            return response(['message' => 'not found xizmat'], 404);

        $buyurtma = Buyurtma::where(['filial_id' => Auth::user()->filial_id, 'x_id' => $xizmat->xizmat_id, 'order_id' => $order->order_id]);

        if ($buyurtma->count() > 0) {
            $buyurtma = $buyurtma->first();
            $buyurtma->value += 1;
        } else {
            $buyurtma = new Buyurtma();
            $buyurtma->filial_id = Auth::user()->filial_id;
            $buyurtma->x_id = $xizmat->xizmat_id;
            $buyurtma->order_id = $order->order_id;
            $buyurtma->value = 1;
            $buyurtma->status = 2;
        }
        $clean = new Clean();
        $clean->order_id = $order->order_id;
        $clean->clean_filial_id = Auth::user()->filial_id;
        $clean->costumer_id = $order->costumer_id;
        $clean->clean_product = $xizmat->xizmat_id;
        $clean->clean_status = "olchov";
        $clean->gilam_eni = 0;
        $clean->gilam_boyi = 0;
        $clean->joy = '';
        $clean->reclean_place = 0;
        $clean->reclean_driver = 0;
        $clean->joyida_user = 0;
        if ($xizmat->olchov == 'dona') {
            $clean->clean_hajm = 1;
        } else {
            $clean->clean_hajm = 0;
        }
        $clean->qad_user = 0;
        $clean->barcode = '';
        $clean->top_user = 0;
        $clean->sana = Carbon::create(1, 0, 0, 0, 0, 0);
        $clean->clean_date = Carbon::create(1, 0, 0, 0, 0, 0);
        $clean->qad_date = Carbon::create(1, 0, 0, 0, 0, 0);
        $clean->top_sana = Carbon::create(1, 0, 0, 0, 0, 0);
        $clean->qayta_sana = Carbon::create(1, 0, 0, 0, 0, 0);
        $clean->joyida_date = Carbon::create(1, 0, 0, 0, 0, 0);
        $clean->narx = 0;
        $clean->clean_narx = 0;
        $clean->user_id = 0;
        $clean->costumer_id = $order->costumer_id;
        if ($clean->save() and $buyurtma->save())
            return  response(['message' => 'Qoshimcha buyurtma olindi'], 200);
    }
    public function washshing($order_id, Request  $request)
    {
        $order_son = Order::query()
            ->leftJoin(DB::raw('clean'), function (JoinClause $join) {
                $join->on('orders.order_id', '=', 'clean.order_id');
                $join->leftJoin('xizmatlar', function (JoinClause $join) {
                    $join->on('clean.clean_product', '=', 'xizmatlar.xizmat_id');
                });
            })
            ->where(function ($query) {
                $query->where('order_status', 'yuvilmoqda')->orWhere('order_status', 'qayta yuvish')->orWhere('order_status', 'quridi')->orWhere('order_status', 'qayta quridi');
            })
            ->groupBy('clean.clean_product')
            ->select(
                'orders.order_id as order_id',
                'xizmatlar.xizmat_id as xizmat_id',
                'xizmatlar.xizmat_turi as xizmat_turi',
                DB::raw('count(*) as count')
            )
            ->where(['order_filial_id' => Auth::user()->filial_id, 'orders.order_id' => $order_id])
            ->get()
            ->map(function ($query) {
                $query->child = Clean::query()
                    ->where('order_id', $query->order_id)
                    ->where('clean_product', $query->xizmat_id)
                    // ->where(function ($query) {
                    //     $query->where('clean_status', 'yuvilmoqda')->orWhere('clean_status', 'qayta yuvish')->orWhere('clean_status', 'quridi')->orWhere('clean_status', 'qayta quridi');
                    // })
                    ->leftJoin('xizmatlar', function (JoinClause $join) {
                        $join->on('clean.clean_product', '=', 'xizmatlar.xizmat_id');
                    })
                    ->select(
                        'clean.clean_hajm as hajm',
                        'clean.clean_narx as clean_narx',
                        'clean.gilam_eni as gilam_eni',
                        'clean.clean_status as status',
                        'clean.gilam_boyi as gilam_boyi',
                        'xizmatlar.xizmat_turi as xizmat_turi',
                        'xizmatlar.olchov as olchov',
                        'clean.barcode as barcode',
                        'xizmatlar.min_narx as min_narx',
                    )
                    ->get();
                return $query;
            });

        $order = Order::query()
            ->where(['order_filial_id' => Auth::user()->filial_id, 'orders.order_id' => $order_id])
            ->where(function ($query) {
                $query->where('order_status', 'yuvilmoqda')->orWhere('order_status', 'qayta yuvish')->orWhere('order_status', 'quridi')->orWhere('order_status', 'qayta quridi');
            })
            ->with('cleans.xizmat')
            ->with('custumer')
            ->first();

        // Order bormi yoki yoqligini tekshirib olamiz va yo`q bo`lsa quydagicha xabar qaytaramiz
        if (!$order)
            return response(['message' => 'not found order'], 404);
        $all = [
            'data' => $order,
            'soni' => $order_son
        ];
        return response()->json($all);
    }
}
