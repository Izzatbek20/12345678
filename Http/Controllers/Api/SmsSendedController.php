<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmsSended;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class SmsSendedController extends Controller
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
        if ($limit)
        {
            $limit_count = $limit;
        }
        $sms = SmsSended::query();
        $sms = $sms->where(['filial_id'=> Auth::user()->filial_id])->paginate($limit_count);
        return response($sms,200);
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
        $request->validate([
            'costumer_id'=>'required|integer',
            'phone'=>'required',
            'text'=>'required'
        ]);
        $create = SmsSended::create([
            'filial_id'=>Auth::user()->filial_id,
            'costumer_id'=>$request->input('costumer_id'),
            'phone'=>$request->input('phone'),
            'text'=>$request->input('text'),
            'date'=>date('Y-m-d H:i:s'),
            'status'=>0
        ]);
        if ($create)
        {
            return response([
                'message'=>'sms sended',
                'sms_id'=>$create->id
            ]);
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
        $one = SmsSended::where(['filial_id'=>Auth::user()->filial_id,'id'=>$id])->first();
        if (!$one)
        {
            return response([
                'message'=>'Bunday xabar topilmadi',
            ],404);
        }
        return $one;
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
        $one = SmsSended::where(['filial_id'=>Auth::user()->filial_id,'id'=>$id])->first();
        if (!$one)
        {
            return response([
                'message'=>'Bunday xabar topilmadi',
            ],404);
        }
        $update = SmsSended::where(['filial_id'=>Auth::user()->filial_id,'id'=>$id])->update([
          'status'=>1,
        ]);
        return response([
            'success updated'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $one = SmsSended::where(['filial_id'=>Auth::user()->filial_id,'id'=>$id])->first();
        if (!$one)
        {
            return response([
                'message'=>'Bunday xabar topilmadi',
            ],404);
        }
        $delete = SmsSended::destroy($id);
        if ($delete)
        {
            return response([
                'message'=>'Sms success deleted'
            ]);
        }
    }
}
