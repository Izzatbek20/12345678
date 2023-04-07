<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Clean;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KvitansiyaController extends Controller
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
    public function index()
    {
        //
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

    public function qaytakel($order_id, Request $request)
    {
        $user = auth()->user();
        $request = request();

        if (!in_array($user->role, ["transport", "admin_filial", "hisobchi", "operator"])) {
            return $this->goHome();
        }

        $qayta_keltirish = Clean::where(['clean_status' => 'qayta keltirish', 'reclean_place' => 3, 'order_id' => $order_id]);
        if ($user->role == 'saygak') {
            $qayta_keltirish = $qayta_keltirish->where('reclean_driver', $user->id);
        } else if ($user->role !== "admin_filial") {
            $qayta_keltirish = $qayta_keltirish->whereIn('reclean_driver', [0, $user->id]);
        }

        if ($qayta_keltirish->count() == 0) {
            return response([
                'message' => 'Orqaga redirect qilib yuborish'
            ]);
        }

        $cid = $request->input("clean_id");

        if ($cid) {
            $clean = Clean::findOne($cid);
            $clean->clean_status = "topshirildi";
            $clean->save();

            if ($qayta_keltirish->count() == 0) {
                $clean->orders->order_status = "topshirildi";
                $clean->orders->save();

                return response([
                    'message' => 'Buyurtmalar qayta yuvishga olindi'
                ]);
            }
            return response([
                'message' => 'Buyurtma qayta yuvilishi bekor qilindi'
            ]);
        }
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $order_id)
    {
        $order = Order::where(['order_filial_id' => \Auth::user()->filial_id, 'order_id' => $order_id])->first();
        if (!$order) {
            return response([
                'message' => 'Not Found'
            ], 404);
        }
        return ($request);
        //        for ($i=1;$i<=$request->gilam;$i++)
        //        {
        //            return $i;
        //        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
        //
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
    public function kvitansiya(Request $request)
    {
        $limit = $request->get('limit');
        $staus = $request->get('status');
        $id = $request->get('id');
        $limit_count = 50;
        $order = Order::query();

        if ($limit) {
            $limit_count = $limit;
        }
        if (empty($staus)) {
            return response([
                'message' => 'Status empty'
            ]);
        }
        if ($staus == 'new') {
            $order = $order->where(['order_filial_id' => Auth::user()->filial_id])->orderByDesc('order_id')->paginate($limit_count);
        } elseif ($staus == 'not_complited') {
            if (Auth::user()->role == 'joyida_yuvish') {
                $order = $order
                    ->where(['order_filial_id' => Auth::user()->filial_id])
                    ->where(['order_status' => 'qabul qilindi'])
                    ->orWhere(['order_status' => 'joyida_yuvish'])
                    ->orderByDesc('order_id')
                    ->paginate($limit_count);
            } else {
                $order = $order->where(['order_filial_id' => Auth::user()->filial_id])
                    ->where(['order_status' => 'qabul qilindi'])
                    ->orderByDesc('order_id')
                    ->paginate($limit_count);
            }
        } else if ($staus == 'called_ready' && $id) {
            $order = $order::findOne($id);
            $order->called = 1;
            if ($order->save()) {
                return response([
                    'message' => 'Mijoz bilan gaplashildi!'
                ], 200);
            }
        }
        return response($order, 200);
    }
}
