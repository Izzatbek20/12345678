<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\Recall;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use mysql_xdevapi\Expression;

class RecallController extends Controller
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
        $recall = Recall::where(['recall_filial_id' => Auth::user()->filial_id])->paginate($limit_count);
        return  $recall;
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
        $recall = Recall::where(['recall_id' => $id, 'recall_filial_id' => Auth::user()->filial_id])->first();
        if (!$recall) {
            return response([['message' => 'Not found']], 404);
        }
        return $recall;
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
        $recall = Recall::where(['recall_filial_id' => Auth::user()->filial_id, 'recall_id' => $id])->first();
        if ($recall) {
            $destroy =  Recall::where(['recall_filial_id' => Auth::user()->filial_id, 'recall_id' => $id])->delete();
            if ($destroy) {
                return response(['message' => 'success deleted'], 200);
            } else {
                return response(['message' => 'not deleted']);
            }
        } else {
            return response(['message' => 'not found'], 404);
        }
    }

    public function talking(Request $request)
    {
        $limit = $request->get('limit');
        $limit_count = 50;
        if ($limit)
            $limit_count = $limit;

        $calling = Order::query();
        $date = date('Y-m-d', strtotime('-6 months'));



        $calling = $calling->select(["costumer_id", "order_last_price", "top_sana", "order_id", "order_date"])
            ->where(['order_filial_id' => Auth::user()->filial_id, 'last_operator_id' => 0, 'order_status' => 'topshirildi'])
            ->having(DB::raw('MAX(DATE(order_date))'), '>', date('Y-m-d', strtotime('-6 months')))
            ->having(DB::raw('MAX(DATE(order_date))'), '<', date('Y-m-d'))
            ->groupBy(["costumer_id", "order_last_price", "top_sana", "order_id"])
            ->groupBy('order_date')
            ->orderByRaw('MAX(top_sana)')
            ->withSum('mijozkirims', 'summa')
            ->with('custumer')
            ->paginate($limit_count);
        return $calling;
    }
    public function oneUser(Request $request)
    {
        $recall = Recall::query();

        $res =  $recall->create([
            'recall_filial_id' => Auth::user()->filial_id,
            'recall_costumer_phone' => $request->input('phone'),
            'recall_date' => $request->input('date'),
            'recall_time' => $request->input('time'),
            'recall_status' => 'off',
            'izoh' => $request->input('izoh'),
            'user_id' => 0,
            'operator_id' => Auth::id(),
        ]);
        if ($res)
            return response([
                'message' => 'vaqt belgilandi'
            ], 200);
        else {
            return response([
                'message' => 'xatolik'
            ]);
        }
    }
    public function recall(Request $request)
    {
        $limit = $request->get('limit');
        $limit_count = 50;
        if ($limit)
            $limit_count = $limit;

        $recall = Recall::query();
        $recall = $recall->where(['recall_filial_id' => Auth::user()->filial_id, 'recall_status' => 'on'])->paginate($limit_count);
        return $recall;
    }
    public function talking6(Request $request)
    {
        $limit = $request->get('limit');
        $limit_count = 50;
        if ($limit)
            $limit_count = $limit;

        $calling = Order::query();
        $date = date('Y-m-d', strtotime('-6 months'));
        $calling = $calling->select(["costumer_id", "order_last_price", "top_sana", "order_id", "order_date"])
            ->where(['order_filial_id' => Auth::user()->filial_id, 'last_operator_id2' => 0, 'order_status' => 'topshirildi'])
            ->having(DB::raw('MAX(DATE(order_date))'), '>', date('Y-m-d', strtotime('-6 months')))
            ->having(DB::raw('MAX(DATE(order_date))'), '<', date('Y-m-d'))
            ->groupBy(["costumer_id", "order_last_price", "top_sana", "order_id"])
            ->groupBy('order_date')
            ->with('custumer')
            ->orderByRaw('MAX(top_sana)')
            ->paginate($limit_count);
        return $calling;
    }
    public function calling(Request $request)
    {
        $limit = $request->get('limit');
        if (!$limit)
            $limit = 15;
        $order = Order::query();
        $order = $order
            // ->select(
            //     'costumer_id',
            //     'order_id',
            // )
            ->where(['order_filial_id' => Auth::user()->filial_id])
            ->whereYear('top_sana', '=', date('Y'))
            ->groupBy('costumer_id')
            ->withOnly('custumer:id,call_count')
            ->with('custumer')
            ->paginate($limit);
        return $order;
    }
    public function talkinguser($order_id, Request $request)
    {
        $type = $request->input('type');
        $izoh = $request->input('izoh');
        //        $order_id = $request->input('order_id');
        $order = Order::query()->where(['order_filial_id' => Auth::user()->filial_id, 'order_id' => $order_id]);

        if ($type == 'null' || $type == null) {
            return response(['message' => 'mijoz fikrini kiriting']);
        } else if (empty($izoh)) {
            return response(['message' => 'mijoz izohini kiriting kiriting']);
        }

        $order->update([
            'last_operator_id' => Auth::id(),
            'talk_date' => date('Y-m-d H:i:s')
        ]);

        return  response(['message' => 'Qo`ng`iroq amalga oshirildi.']);
    }
    public function talkingg6(Request $request)
    {
        $izoh  = $request->input('izoh');
        $type = $request->input('type');
        $order_id = $request->input('order_id');
        $order = Order::where(['order_filial_id' => Auth::user()->filial_id, 'order_id' => $order_id]);

        if (empty($izoh) or $izoh == null || $izoh == 'null') {
            return response(['message' => 'Mijoz izohini kiritig']);
        }

        if ($type == null || $type == 'null') {
            return  response(['message' => 'Mijoz fikrini tanang']);
        }
        $order->update([
            'last_operator_id2' => Auth::id(),
            'talk_date2' => date('Y-m-d')
        ]);
        return  response(['message' => 'qo`ng`iroq amalga oshirildi']);
    }
}
