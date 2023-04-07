<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\Costumers;
use Illuminate\Support\Facades\Auth;

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
            return $costumers->where('costumers_filial_id', Auth::user()->filial_id)->paginate($limit_count);
        }
        else
        {
            return response(['message'=>'login'],401);
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
    public function store(Request $request)
    {
         $request->validate([
            "costumer_name"=> "string",
            "costumer_phone_1"=> "string",
            "costumer_addres"=> "string",
            "costumer_source"=> "string",
            "costumer_turi"=> "string",
             "millat_id"=>"integer",
        ]);
         $created = Costumers::create([
             "costumers_filial_id"=>Auth::user()->filial_id,
            "costumer_name"=> $request->costumer_name,
            "costumer_phone_1"=> $request->costumer_phone_1,
            "costumer_phone_2"=> $request->costumer_phone_2 ? $request->costumer_phone_2 : '',
             "costumer_phone_3"=> '',
             'costumer_date'=>date('Y-m-d H:i:s'),
             "costumer_addres"=> $request->costumer_addres,
            "costumer_source"=> $request->costumer_source,
            "costumer_turi"=> $request->costumer_turi,
             "millat_id"=>$request->millat_id,
             "orienter"=>"",
             "izoh"=>$request->izoh ? $request->izoh : '',
             "costumer_status"=>"kutish",
             "saygak_id"=>0,
             "mintaqa_id"=>1,
             "manba"=>"rest api",
             "token"=>0,
             "parol"=>0,
             "user_id"=>Auth::id(),
             "call_count"=>0,
             "calling"=>0,
         ]);
        if ($created)
        {
            return response([
                'message'=>'Success created',
                'costumed_id'=>$created
            ],201);
        }
        else
        {
            return response([
                'message'=>'created error',
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id,Request $request)
    {
        $costumer = Costumers::where(['costumers_filial_id'=>Auth::user()->filial_id,'id'=>$id])->first();
        if (!$costumer)
        {
            return response([
                'message'=>'Not found',
            ],404);
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
        $costumer = Costumers::where(['costumers_filial_id'=>Auth::user()->filial_id,'id'=>$id])->first();
        if (!$costumer)
        {
            return response(['message'=>'Not found costumer'],404);
        }
        $update = $costumer->update([
            "costumer_name"=> $request->costumer_name,
            "costumer_phone_1"=> $request->costumer_phone_1,
            "costumer_phone_2"=> $request->costumer_phone_2,
            "costumer_phone_3"=> '',
            'costumer_date'=>date('Y-m-d H:i:s'),
            "costumer_addres"=> $request->costumer_addres,
            "costumer_source"=> $request->costumer_source,
            "costumer_turi"=> $request->costumer_turi,
            "millat_id"=>$request->millat_id,
            "orienter"=>"",
            "izoh"=>$request->izoh,
            "costumer_status"=>"kutish",
            "saygak_id"=>0,
            "mintaqa_id"=>1,
            "manba"=>"rest api",
            "token"=>0,
            "parol"=>0,
            "user_id"=>Auth::id(),
            "call_count"=>0,
            "calling"=>0,
        ]);
        if ($update)
        {
            return response(['message'=>'Succes update']);
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
        $cosumer_one = Costumers::where(['costumers_filial_id'=>Auth::user()->filial_id,'id'=>$id])->first();
        if ($cosumer_one)
        {
            $cosumer = Costumers::destroy($id);
            if ($cosumer)
            {
                return response(['message'=>'success deleted'],200);
            }
        }
        else
        {
            return  response(['message'=>'bunday foydalanuvchi topilmadi'],404);
        }
    }
    public function orders($costumer_id,Request $request)
    {
        $status = $request->get('status');

        $one = Costumers::query();
        $costumer = $one->where(['id'=>$costumer_id,'costumers_filial_id'=>Auth::user()->filial_id])->first();
        if (!$costumer)
        {
            return response([
                'message'=>'Not found costumer'
            ],404);
        }
        $one = $one->where(['id'=>$costumer_id,'costumers_filial_id'=>Auth::user()->filial_id]);

        return response($one->first()->orders);
    }
    public function nasiya($costumer_id)
    {
        $one = Costumers::query();
        $costumer = $one->where(['id'=>$costumer_id,'costumers_filial_id'=>Auth::user()->filial_id])->first();
        if (!$costumer)
        {
            return response([
                'message'=>'Not found costumer'
            ],404);
        }
        return response($costumer->nasiya);
    }
    public function pullar($costumer_id)
    {
        $costumer = Costumers::query();
        $costumer = $costumer->where(['costumers_filial_id'=>Auth::user()->filial_id,'id'=>$costumer_id])->first();
        if (!$costumer)
        {
            return response([
                'message'=>'Not found costumer',
            ],404);
        }
        return response($costumer->pullar);
    }
}
