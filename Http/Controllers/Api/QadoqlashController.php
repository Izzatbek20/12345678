<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Clean;
use App\Models\Davomat;
use App\Models\Kpi;
use App\Models\Order;
use App\Models\User;
use App\Models\Xizmatlar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use mysql_xdevapi\Expression;


class QadoqlashController extends Controller
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
        $qadoqlash = Clean::query();
        $qadoqlash = $qadoqlash
            ->select('order_id')
            ->where('clean_status', 'quridi')
            ->where('clean_filial_id', Auth::user()->filial_id)
            ->groupBy('order_id')
            ->paginate($limit_count);
        $all = [];
        foreach ($qadoqlash as $item) {
            $order = Order::where(['order_filial_id' => Auth::user()->filial_id, 'order_id' => $item->order_id])->first();
            $all[] = $order;
        }
        return $all;
    }
    public function qayta(Request $request)
    {
        $limit = $request->get('limit');
        $limit_count = 50;
        if ($limit) {
            $limit_count = $limit;
        }
        $qadoqlash = Clean::query();
        $qadoqlash = $qadoqlash->where('clean_status', 'qayta quridi')->where('clean_filial_id', Auth::user()->filial_id)->paginate($limit_count);
        return $qadoqlash;
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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $qadoqlash = Clean::query()->where('id', $id)->where('clean_filial_id', Auth::user()->filial_id)->first();
        if (!$qadoqlash) {
            return response([
                'message' => 'Not Found'
            ], 404);
        }
        return $qadoqlash;
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
        $one = Clean::find($id)->where('clean_filial_id', Auth::user()->filial_id)->first();
        if (!$one) {
            return response([
                'message' => 'Not found'
            ], 404);
        }
        return $one;
        //        $update = $one->update([
        //            'clean_status'=>'qadoqlandi'
        //        ]);
        //        if ($update)
        //        {
        //            return response([
        //                'message'=>$id.' id dagi maxsulot qadoqlandi'
        //            ],200);
        //        }
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
    public function qadoqlash($order_id, Request $request)
    {
        $id = $request->input('id');
        $joy = $request->input('shelf');
        $order = Order::where(['order_filial_id' => Auth::user()->filial_id, 'order_id' => $order_id])->first();
        $clean = Clean::query()->where(['clean_filial_id' => \auth()->user()->filial_id, 'id' => $id])->first();
        $xizmat = Xizmatlar::where(['filial_id' => Auth::user()->filial_id, 'xizmat_id' => $clean->clean_product])->first();

        if (!$order)
            return response(['message' => 'not found order'], 404);

        $myself = $clean->user;
        if ($myself and $myself->role == 'saygak' and $clean->clean_status == 'quridi') {
            User::where(['id' => $myself])->update([
                'balance' => $myself->balance + ($xizmat->saygak_narx * $clean->clean_hajm),
            ]);
        }
        $qad_user = Auth::id();

        $kpi = Kpi::query();
        $kpi = $kpi->where(['xizmat_id' => $clean->clean_product, 'status' => 1, 'user_id' => Auth::id()])
            ->orWhere(['xizmat_id' => $clean->clean_product, 'status' => 5, 'user_id' => Auth::id()]);
        if ($kpi->count() > 0)
            $kpi_summa = $kpi->first()->summa * $clean->clean_hajm;
        else
            $kpi_summa = 0;

        $davomat = Davomat::query();
        $davomat = $davomat->where(['sana' => date('Y.m.d'), 'filial_id' => Auth::user()->filial_id])
            ->where('status', '!=', 0)->get();

        if ($clean->clean_status == 'qayta quridi')
            $clean_status = 'qayta qadoqlandi';
        else
            $clean_status = 'qadoqlandi';


        if ($joy)
            $clean_joy = $joy;

        $qad_date = date('Y-m-d H:i:s');

        $update_clean =  $clean->update([
            'clean_status' => $clean_status,
            'joy' => $joy ? $joy : '',
            'qad_date' => $qad_date,
            'qad_user' => $qad_user,
        ]);
        if ($update_clean) {
            // Orderni update qilish uchun cleanlarini sanab olamiz agar clinlari soni 0 ga teng bo`lsa orderni ham update qilib qoyamiz

            $cl_num = Clean::query()->where('clean_filial_id', Auth::user()->filial_id)
                ->where('order_id', $order_id)
                ->where(function ($query) {
                    $query->where('clean_status', 'quridi')->orWhere('clean_status', 'qayta quridi');
                })
                ->count();

            $aa = "";
            if ($cl_num == 0) {
                if ($order->order_status == 'qayta quridi') {
                    $order->order_status = 'qayta qadoqlandi';
                    $aa = 'qayta qadoqlandi';
                } else {
                    $order->order_status = 'qadoqlandi';
                    $aa = 'qadoqlandi';
                    foreach ($davomat as $item) {
                        $kpi_user = User::where(['id' => $item->user_id])->first();
                        if ($kpi_user->role == 'tayorlov') {
                            $kpi_user->oylik += $kpi_summa;
                            $kpi_user->save();
                            $kpi_h = new Kpi();
                            $kpi_h->user_id = $item->user_id;
                            $kpi_h->summa = $kpi_summa;
                            $kpi_h->filial_id = Auth::user()->filial_id;
                            $kpi_h->clean_id = $clean->id;
                            $kpi_h->date = now()->format('Y-m-d H:i:s');
                            if ($kpi_h->summa > 0) {
                                $kpi_h->izoh = $clean->clean_hajm . ' ' . $xizmat->olchov . 'lik ' . $xizmat->xizmat_turi . ' qadoqladi';
                                $kpi_h->save();
                            }
                        }
                    }
                }
            }
            if ($order->save())
                return  response(['message' => 'qadoqlandi ' . $cl_num . ' ' . $aa]);
            else
                return response(['message' => 'xatolik']);
        }
    }
}
