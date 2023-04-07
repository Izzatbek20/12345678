<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Clean;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OmborController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $limit = $request->get('limit');
        $limit_count = 50;
        if ($limit)
        {
            $limit_count = $limit;
        }
        $order = Order::query();
        $order = $order->where(['order_filial_id'=>auth()->user()->filial_id,'order_status' => 'ombor'])
            ->with('custumer')
            ->with('operator')
            ->with('ombor')
            ->paginate($limit_count);
        return response($order,200);
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
    public function store(Request $request,$id)
    {
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
    public function tartib($id,Request $request)
    {
        $order = Order::where(['order_filial_id'=>\Auth::user()->filial_id,'order_id'=>$id])->first();
        $cleans = Clean::where(['order_id'=>$order->order_id,'clean_status'=>'qayta qdoqlandi'])->count();

        if ($cleans>0)
            $status = 'qayta qadoqlandi';
        else
            $status = 'qadoqlandi';
        $ombor_user = 0;


        $r =  $order->update([
            'order_status'=>$status,
            'ombor_user'=>$ombor_user,
        ]);

        if ($r)
        {
            return response(['message'=>'Buyurtma yetkazishga o`tkazildi']);
        }
    }
    public function to($order_id)
    {
        $order = Order::query();
        $order = $order->where(['order_filial_id'=>Auth::user()->filial_id,'order_id'=>$order_id]);

        if (!$order->first())
            return response(['message'=>'not found order'],404);
        $order->update([
            'order_status'=>'ombor',
            'ombor_user'=>Auth::id()
        ]);
        return  response(['message'=>'omborga olindi']);
    }
}
