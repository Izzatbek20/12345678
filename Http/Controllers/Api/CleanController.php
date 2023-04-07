<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Buyurtma;
use App\Models\Chegirma;
use App\Models\Costumers;
use App\Models\Davomat;
use App\Models\Kpi;
use App\Models\Order;
use App\Models\Xizmatlar;
use Illuminate\Http\Request;
use App\Models\Clean;
use Illuminate\Support\Facades\Auth;
use mysql_xdevapi\Expression;
use Symfony\Component\Console\Input\Input;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CleanController extends Controller
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
        //        $this->authorize('filial_admin');
        $limit = $request->get('limit');
        $filial_id = $request->header('filialid');
        $limit_count = 50;
        if ($limit) {
            $limit_count = $limit;
        }
        return Clean::where(['clean_filial_id' => Auth::user()->filial_id])->paginate($limit_count);
        //        return $filial_id;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //        return Auth::user();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */


    public function store(Request $request, $order_id)
    {
        $order = Order::query()->where(['order_filial_id' => Auth::user()->filial_id, 'order_id' => $order_id, 'order_status' => 'keltirish'])->first();
        $costumer = Costumers::where(['costumers_filial_id' => Auth::user()->filial_id, 'id' => $order->costumer_id])->first();
        $mahsulot = $request->post('mahsulot');
        if (!$order or !$order_id) {
            return response([
                'message' => 'Bunday order topilmadi yoki mavjud emas'
            ], 404);
        }

        $order_jami = 0;
        $xizmatlar = Xizmatlar::query()->where('filial_id', Auth::user()->filial_id)->get();

        foreach ($xizmatlar as $xizmat) {
            $b_name = 'xizmat' . $xizmat->xizmat_id;
            $b_val = $request->post($b_name);
            if ($b_val > 0)
                $order_jami += $b_val;
        }
        $upd_order = $order->update([
            'order_filial_id' => Auth::user()->filial_id,
            'order_driver' => Auth::id(),
            'costumer_id' => $order->costumer_id,
            'order_price' => 0,
            'order_last_price' => 0,
            'finish_driver' => 0,
            'order_price_status' => 'yoq',
            'tartib_raqam' => 0,
            'order_status' => 'qabul qilindi',
            'olibk_sana' => now()->format('Y-m-d H:i:s'),
        ]);
        if ($order_jami > 0) {
            if ($upd_order) {
                foreach ($xizmatlar as $xizmat) {
                    $b_name = 'xizmat' . $xizmat->xizmat_id;
                    $b_val = $request->post($b_name);

                    if ($b_val > 0) {
                        Buyurtma::create([
                            'x_id' => $xizmat->xizmat_id,
                            'status' => 1,
                            'value' => $b_val,
                            'filial_id' => Auth::user()->filial_id,
                            'order_id' => $order->order_id,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        return  response('salom', 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $clean  = Clean::query();
        $clean = $clean
            ->where(['clean_filial_id' => Auth::user()->filial_id, 'order_id' => $id])
            ->first();
        if (!$clean)
            return  response(['message' => 'bunday maxsulot topilmadi'], 404);

        if ($clean) {
            $clean->update([
                'gilam_eni' => $request->input('gilam_eni'),
                'gilam_boyi' => $request->input('gilam_boyi'),
                'clean_hajm' => $request->input('clean_hajm'),
                'narx' => $request->input('narx')
            ]);
            return  response(['message' => 'succees'], 200);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function tayyor(Request $request)
    {
        //        $status1 = null;
        //        $status2 = null;
        //        if (Auth::user()->role == 'tayorlov')
        //        {
        //            $status1 = 'qadoqlandi';
        //            $status2 = 'qayta qadoqlandi';
        //        }
        //        elseif (Auth::user()->role == 'yuvish')
        //        {
        //
        //        }

        $status1 = 'qadoqlandi';
        $status2 = 'qayta qadoqlandi';

        $tayyor  = Order::query()
            // ->select(
            // 'order_id',
            // 'nomer',
            // 'costumer_id',
            // 'operator_id',
            // 'tartib_raqam',
            // 'tartib_raqam',
            // 'own',
            // // 'qayta',
            // 'geoplugin_longitude',
            // 'geoplugin_latitude',
            // 'izoh',
            // 'topshir_sana',
            // 'order_date'
            // )
            ->where('order_filial_id', Auth::user()->filial_id)
            ->where(function ($query) use ($status1, $status2) {
                $query->where('order_status', $status1);
                $query->orwhere('order_status', $status2);
            })
            ->when(!empty(request('driver')) && !empty(request('driver')) != -2, function ($query) {
                $query->where(['order_driver' => request('driver')]);
            })
            ->when(request('type') == 'arranged', function ($query) {
                $query->where('tartib_raqam', '>', 0);
                $query->orderBy('tartib_raqam', 'asc');
            })
            ->when(request('type') == 'notarranged', function ($query) {
                $query->where('tartib_raqam', '<=', 0);
            })
            ->with('operator:id,fullname,phone')
            ->with('custumer:id,costumer_name,costumer_phone_1,costumer_phone_2,costumer_addres')
            ->withCount('cleans')
            ->paginate(!empty(request('limit')) ? request('limit') : 15);

        return response($tayyor, 200);
    }
    public function cleans($costumer_id, $order_id)
    {
        $order = Order::where(['order_filial_id' => Auth::user()->filial_id, 'order_id' => $order_id])->first();
        if (!$order) {
            return response([
                'message' => 'not found Order'
            ]);
        }
        return response($order->cleans);
    }
    public function joriy(Request $request)
    {
        $limit = $request->get('limit');
        $limit_count = 15;
        if ($limit) {
            $limit_count = $limit;
        }
        if (empty(request('kv_id')) && empty(request('telefon')) && empty(request('status')) && empty(request('type')) && empty(request('muddat'))) {

            $orders = Order::query();
            $orders = $orders->where('order_filial_id', '=', Auth::user()->filial_id)
                ->where(function ($query) {
                    $query->where('order_status', '!=', 'topshirildi')
                        ->where('order_status', '!=', 'keltirish')
                        ->where('order_status', '!=', 'kutish')
                        ->where('order_status', '!=', 'bekor qilindi');
                })
                ->withCount('cleans')
                ->with('custumer')
                ->paginate($limit_count);
        } else {
            $orders = Order::query()
                ->when(!empty(request('kv_id')), function (Builder $query) {
                    $query->where('nomer', 'like', '%' . request('kv_id') . '%');
                })
                ->when(!empty(request('telefon')), function (Builder $query) {
                    $query->join('costumers', 'orders.costumer_id', '=', 'costumers.id');
                    $query->where('costumers.costumer_phone_1', 'like', '%' . request('telefon') . '%');
                })
                ->when(!empty(request('status')), function (Builder $query) {
                    $query->where("order_status", request('status'));
                })
                ->when(!empty(request('type')), function (Builder $query) {
                    $query->when(request('type') == 'saygak', fn (Builder $query) => $query->where('saygak_id', '>', 0));
                    $query->when(request('type') == 'olibketish', fn (Builder $query) => $query->where('own', 1));
                })
                ->when(!empty(request('muddat')), function (Builder $query) {
                    $query->when(request('muddat') == 'qizil', fn (Builder $query) => $query->where('topshir_sana', '<', date("Y-m-d")));
                    $query->when(request('muddat') == 'yashil', fn (Builder $query) => $query->where('topshir_sana', '>', date("Y-m-d")));
                    $query->when(request('muddat') == 'sariq', fn (Builder $query) => $query->where('topshir_sana', date("Y-m-d")));
                })
                ->where('order_filial_id', '=', Auth::user()->filial_id)
                ->where('order_status', '!=', 'topshirildi')
                ->where('order_status', '!=', 'keltirish')
                ->where('order_status', '!=', 'kutish')
                ->where('order_status', '!=', 'bekor qilindi')
                ->withCount('cleans')
                ->paginate($limit);
        }

        return response($orders);
    }
    public function joriyFilter(Request $request)
    {
        $limit = $request->get('limit');

        // $limit bo`sh bo`lsa unga 15 beramiz
        if (empty($limit)) {
            $limit = 15;
        }

        $data = [
            'status' => [
                'yuvilmoqda',
                'qadoqlandi',
                'quridi',
                'ombo',
                'qayta yuvish',
                'qayta qadoqlandi',
                'qayta quridi',
            ],

            'type' => [
                'saygak',
                'olib ketish',
            ],
            'muddat' => [
                'qizil',
                'sariq',
                'yashil',
            ],
        ];

        return response($data);
    }
    public function joriyOne($order_id)
    {
        $orders = Order::query()
            ->where('order_filial_id', '=', Auth::user()->filial_id)
            ->where('order_status', '!=', 'topshirildi')
            ->where('order_status', '!=', 'keltirish')
            ->where('order_status', '!=', 'kutish')
            ->where('order_status', '!=', 'bekor qilindi')
            ->where('order_id', '=', $order_id)
            ->with('cleans')
            ->with('cleans.xizmat')
            ->with('cleans.tokcha')
            ->with('cleans.qadoqladi:id,fullname')
            ->with('cleans.yuvdi:id,fullname')
            ->withSum('cleans', 'clean_narx')
            ->first();
        if (empty($orders)) {
            return response('Bunday "id" li order mavjud emas');
        }
        if ($orders->order_skidka_foiz == 0) {
            $skidka = 0;
        } else {
            $skidka =  ($orders->cleans_sum_clean_narx / 100) * $orders->order_skidka_foiz;
        }
        return response($orders, 200);
    }
    public function cleanCount($order_id)
    {
        $clean = Clean::query();
        $clean = $clean->where(['clean_filial_id' => Auth::user()->filial_id, 'order_id' => $order_id])->count();
        //        if (!$clean)
        //        {
        //            return response([
        //                'message'=>'Not found'
        //            ],404);
        //        }
        return  response($clean, 200);
    }
    public function yuvildi($clean_id, Request $request)
    {
        $clean = Clean::find($clean_id, 'id');
        $xizmat = Xizmatlar::where(['id' => $clean->clean_product])->first();
        $kpi  = Kpi::query();
        $kpi = $kpi
            ->where(['xizmat_id' => $clean->clean_product, 'status' => 1, 'user_id' => Auth::id()])
            ->orWhere(['xizmat_id' => $clean->clean_product, 'status' => 5, 'user_id' => Auth::id()]);
        if ($kpi->count() > 0)
            $kpi_narx = $kpi->first()->summa;
        else
            $kpi_narx = 0;

        if ($clean->clean_hajm == null)
            $cl_xajm = 0;
        if ($clean->clean_hajm == 'dona')
            $cl_xajm = 1;

        $cl_narx = $clean->clean_narx * $clean->clean_hajm;
        $kpi_sum = $kpi_narx * $cl_xajm;

        $davomat = Davomat::query();
        $davomat = $davomat
            ->where(['sana' => date('Y.m.d'), 'filial_id' => Auth::user()->filial_id])
            ->orWhere('status', '!=', 0)
            ->get();
        if ($clean->status == 'yuvilmoqda' || $clean->status == 'olchov') {
            foreach ($davomat as $dav) {
                if ($kpi_sum > 0) {
                    $kpi->create(array(
                        'user_id' => $dav->user_id,
                        'summa' => $kpi_sum,
                        'clean_id' => $clean->id,
                        'filial_i' => Auth::user()->filial_id,
                        'date' => new Expression('NOW()'),
                    ));
                }
            }
        }
        if ($clean->clean_status == 'qayta yuvish')
            $status = 'qayta quridi';
        else if ($clean->clean_status == 'olchov' || $clean->clean_status == 'yuvilmoqda')
            $status = 'quridi';
        $clean->update([
            'clean_status' => $status,
            'clean_date' => new Expression('NOW()'),
        ]);
        return 'success';
    }
    public function transport()
    {
        $transport = User::query();
        $transport = $transport->where(['filial_id' => Auth::user()->filial_id, 'role' => 'transport'])->get();
        $clean = Order::query();
        $clean = $clean->where(['order_filial_id' => Auth::user()->filial_id]);
        $all = [];
        // dd($transport);
        array_push($all, ['id' => 0, "fullname" => 'Barchasi', "count" => $clean->count()]);
        array_push($all, ['id' => -1, "fullname" => 'o`zi olib ketadigon', "count" => $clean->where('own', '=', 1)->count()]);


        foreach ($transport as $tr) {
            array_push($all, [
                'id' => $tr->id,
                'fullname' => $tr->fullname,
                'count' => $clean->where(['order_driver' => $tr->id])->count()
            ]);
        }
        return $all;
    }
    public function transportone($id)
    {
        $order = Order::where([
            'order_driver' => $id, 'order_filial_id' => Auth::user()->filial_id,
            'order_status' => 'quridi'
        ])
            ->get();
        return $order;
    }
    public function cleanPrice($order_id, Request $request)
    {
        $summa  = $request->input('discount');
        $x_id = $request->input('xizmat_id');
        $order = Order::where(['order_filial_id' => Auth::user()->filial_id, 'order_id' => $order_id])->first();
        $xizmat = Xizmatlar::where(['filial_id' => Auth::user()->filial_id, 'xizmat_id' => $x_id])->first();
        //        return $order;
        //        exit();
        $chegirma = Chegirma::where(['order_id' => $order->order_id, 'xizmat_id' => $xizmat->xizmat_id]);
        //        return  $chegirma->first() ? $chegirma->first() : 'x';
        //        exit();
        if (!$chegirma->first()) {
            Chegirma::create([
                'xizmat_id' => $xizmat->xizmat_id,
                'order_id' => $order->order_id,
                'summa' => $summa
            ]);
            return response(['message' => 'O`zgartirildi']);
        } else {
            $cheg = $chegirma->update([
                'summa' => $summa
            ]);
            if ($cheg)
                return response(['message' => 'O`zgartirildi']);
        }
    }
}
