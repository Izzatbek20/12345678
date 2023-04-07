<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use App\Models\Order;
use App\Models\Recall;
use Illuminate\Http\Request;
use App\Models\Costumers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CostumerController extends Controller
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
        if (Auth::check()) {
            $filial_id = $request->header('filialid');
            $limit = $request->get('limit');
            $limit_count = 50;
            if ($limit) {
                $limit_count = $limit;
            }
            //        return Costumers::paginate($limit_count)->where('costumers_filial_id',$filial_id)->get();
            $costumers = Costumers::query();
            return $costumers->where('costumers_filial_id', Auth::user()->filial_id)
                ->with('millat:id,name')
                ->paginate($limit_count);
        } else {
            return response(['message' => 'login'], 401);
        }
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
    public function store(CustomerRequest $request)
    {
        $created = Costumers::create([
            "costumers_filial_id" => Auth::user()->filial_id,
            "costumer_name" => $request->costumer_name,
            "costumer_phone_1" => $request->costumer_phone_1,
            "costumer_phone_2" => $request->costumer_phone_2 ?? '',
            "costumer_phone_3" => '',
            'costumer_date' => date('Y-m-d H:i:s'),
            "costumer_addres" => $request->costumer_addres,
            "costumer_source" => $request->costumer_source,
            "costumer_turi" => $request->costumer_turi,
            "millat_id" => $request->millat_id,
            "orienter" => "",
            "izoh" => !empty($request->izoh) ? $request->izoh : "",
            "costumer_status" => "kutish",
            "saygak_id" => 0,
            "mintaqa_id" => 1,
            "manba" => "rest api",
            "token" => 0,
            "parol" => 0,
            "user_id" => Auth::id(),
            "call_count" => 0,
            "calling" => 0,
        ]);
        //         $recall = Recall::create([
        //            'recall_filial_id'=>Auth::user()->filial_id,
        //             'recall_costumer_phone'=>$request->costumer_phone_1,
        //             'recall_date'=>$request->recall_date,
        //             'recall_time'=>$request->recall_time,
        //             'izoh'=>$request->recall_izoh,
        //         ]);
        if ($created) {
            return response($created, 201);
        } else {
            return response([
                'message' => 'created error',
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $request)
    {
        $costumer = Costumers::where(['costumers_filial_id' => Auth::user()->filial_id, 'id' => $id])->first();
        if (!$costumer) {
            return response([
                'message' => 'Not found',
            ], 404);
        }
        return $costumer;
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
        $costumer = Costumers::where(['costumers_filial_id' => Auth::user()->filial_id, 'id' => $id])->first();
        if (!$costumer) {
            return response(['message' => 'Not found costumer'], 404);
        }
        $update = $costumer->update([
            "costumer_name" => $request->costumer_name,
            "costumer_phone_1" => $request->costumer_phone_1,
            "costumer_phone_2" => $request->costumer_phone_2,
            "costumer_phone_3" => '',
            'costumer_date' => date('Y-m-d H:i:s'),
            "costumer_addres" => $request->costumer_addres,
            "costumer_source" => $request->costumer_source,
            "costumer_turi" => $request->costumer_turi,
            "millat_id" => $request->millat_id,
            "orienter" => "",
            "izoh" => !empty($request->izoh) ? $request->izoh : "",
            "costumer_status" => "kutish",
            "saygak_id" => 0,
            "mintaqa_id" => 1,
            "manba" => '',
            "token" => 0,
            "parol" => 0,
            "user_id" => Auth::id(),
            "call_count" => 0,
            "calling" => 0,
        ]);
        if ($update) {
            return $costumer;
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
        $cosumer_one = Costumers::where(['costumers_filial_id' => Auth::user()->filial_id, 'id' => $id])->first();
        if ($cosumer_one) {
            $cosumer = Costumers::destroy($id);
            if ($cosumer) {
                return response(['message' => 'success deleted'], 200);
            }
        } else {
            return  response(['message' => 'bunday foydalanuvchi topilmadi'], 404);
        }
    }
    public function orders($costumer_id, Request $request, Order $orders)
    {
        $status = $request->get('status');

        $one = Costumers::query();
        $costumer = $one->where(['id' => $costumer_id, 'costumers_filial_id' => Auth::user()->filial_id])->first();
        if (!$costumer) {
            return response([
                'message' => 'Not found costumer'
            ], 404);
        }
        $one = $one->where(['id' => $costumer_id, 'costumers_filial_id' => Auth::user()->filial_id]);
        $order = Order::query();
        $order = $order->where(['costumer_id' => $costumer_id, 'order_filial_id' => Auth::user()->filial_id]);
        return response($order
            ->with(['operator:id,fullname'])
            ->with('transport:id,fullname')
            ->with('finishdriver:id,fullname')
            ->get());
    }
    public function nasiya($costumer_id)
    {
        $one = Costumers::query();
        $costumer = $one->where(['id' => $costumer_id, 'costumers_filial_id' => Auth::user()->filial_id])->first();
        if (!$costumer) {
            return response([
                'message' => 'Not found costumer'
            ], 404);
        }
        return response($costumer->nasiya);
    }
    public function pullar($costumer_id)
    {
        $costumer = Costumers::query();
        $costumer = $costumer->where(['costumers_filial_id' => Auth::user()->filial_id, 'id' => $costumer_id])->first();
        if (!$costumer) {
            return response([
                'message' => 'Not found costumer',
            ], 404);
        }
        return response($costumer->pullar);
    }
    public function calls(Request $request, $costumer_id)
    {
        $limit = $request->get('limit');
        $limit_count = 50;
        if ($limit) {
            $limit_count = $limit;
        }
        $costumer = Costumers::query();
        $costumer = $costumer->where(['costumers_filial_id' => Auth::user()->filial_id, 'id' => $costumer_id])->first();
        if (!$costumer) {
            return response([
                'message' => 'Not found costumer',
            ], 404);
        }
        return response($costumer->calls()->paginate($limit_count), 200);
    }
    public function add(Request  $request)
    {
        $this->validate($request, [
            "costumer_name" => "string",
            "costumer_phone_1" => "string",
            "costumer_addres" => "string",
            "costumer_source" => "string",
            "costumer_turi" => "string",
            "millat_id" => "integer",
        ]);

        $created = Costumers::create([
            "costumers_filial_id" => Auth::user()->filial_id,
            "costumer_name" => $request->costumer_name,
            "costumer_phone_1" => $request->costumer_phone_1,
            "costumer_phone_2" => $request->costumer_phone_2 ? $request->costumer_phone_2 : '',
            "costumer_phone_3" => '',
            'costumer_date' => date('Y-m-d H:i:s'),
            "costumer_addres" => $request->costumer_addres,
            "costumer_source" => $request->costumer_source ? $request->costumer_source : '',
            "costumer_turi" => $request->costumer_turi,
            "millat_id" => $request->millat_id,
            "orienter" => "",
            "izoh" => !empty($request->izoh) ? $request->izoh : '',
            "costumer_status" => "kutish",
            "saygak_id" => 0,
            "mintaqa_id" => 1,
            "manba" => "rest api",
            "token" => 0,
            "parol" => 0,
            "user_id" => Auth::id(),
            "call_count" => 0,
            "calling" => 0,
        ]);
        return response([
            'message' => 'costumer added',
            'costumer_id' => $created->id
        ], 201);
    }
    public function called(Request  $request, $costumer_id)
    {
        $order = Order::query()->where('order_filial_id', Auth::user()->filial_id)->where('costumer_id', $costumer_id);
        if (!$order->first())
            return response(['message' => 'not found costumer'], 404);

        $order->update([
            'called' => 1
        ]);
        return  response(['message' => 'called']);
    }
    /**
     * Programmaga telefon kelgan ular telefon raqamni yuboradi va telefon egasi qaytariladi
     * @return Costumers
     */
    public function filterPhone($phone)
    {
        $modelFind = Costumers::query()
            ->where(function ($query) use ($phone) {
                $query->where('costumer_phone_1', $phone);
                $query->orwhere('costumer_phone_2', $phone);
                $query->orwhere('costumer_phone_3', $phone);
            })
            ->where('costumers_filial_id', auth()->user()->filial_id)
            ->first();

        if (empty($modelFind)) {
            return response('Mijoz topilmadi', 404);
        } else {
            return response($modelFind, 200);
        }
    }
    public function recall($type)
    {
        $user = auth()->user();

        $limit = request()->get('limit');
        $filter = request()->get('filter');

        // Agar limit bo`lmasa default 15 ta bo`ladi
        if (empty($limit)) {
            $limit = 15;
        }

        if ($user->role == 'admin_operator' || $user->role == 'admin') {
            $the_filial_id = 0;
        } else {
            $the_filial_id = $user->filial_id;
        }

        // Agar $type=3 bo`lsa unda boshqacha chaqiriladi
        if (in_array($type, [1, 2])) {

            $data = Order::query()
                ->where('order_filial_id', $the_filial_id)
                ->leftJoin('costumers', 'costumers.id', '=', 'orders.costumer_id')
                ->when($type == 1, function ($query) use ($the_filial_id) {
                    $query->where('last_operator_id', '>', 0);
                    $query->leftJoin('user', 'user.id', '=', 'orders.last_operator_id');
                    $query->select(
                        'nomer',
                        'talk_date as sana_1',
                        'talk_date2 as sana_2',
                        'last_izoh as izoh',
                        'talk_type as natija',
                        'user.fullname as operator',
                        'costumers.costumer_name as mijoz',
                    );
                })
                ->when($type == 2, function ($query) use ($the_filial_id) {
                    $query->where('last_operator_id2', '>', 0);
                    $query->leftJoin('user', 'user.id', '=', 'orders.last_operator_id2');
                    $query->select(
                        'nomer',
                        'talk_date as sana_1',
                        'talk_date2 as sana_2',
                        'last_izoh2 as izoh',
                        'talk_type2 as natija',
                        'user.fullname as operator',
                        'costumers.costumer_name as mijoz',
                    );
                })
                ->paginate($limit);
        } else if ($type == 3) {

            $data = Recall::query()
                ->where('recall_filial_id', $the_filial_id)
                ->where('recall_status', 'off')
                ->leftJoin('costumers', 'costumers.costumer_phone_1', '=', 'recall.recall_costumer_phone')
                ->leftJoin('user', 'user.id', '=', 'recall.user_id')
                ->select(
                    'recall.izoh as izoh',
                    'recall.recall_time as vaqt',
                    'recall_date as sana',
                    'user.fullname AS operator',
                    'costumers.costumer_name AS mijoz'
                )
                ->paginate($limit);
        } else {
            return response("'type' ga bunday qiymat berish mumkun emas.", 404);
        }

        return response($data);
    }

    public function costumerView($id, Request $request)
    {
        $model = Costumers::where('id', $id)->with('orders')->with('pullar')->with('nasiya')->first();

        if ($model) {
            return response($model);
        } else {
            return response('Bunday idli mijoz topilmadi');
        }
    }
}
