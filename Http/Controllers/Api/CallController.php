<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Costumers;
use Illuminate\Http\Request;
use App\Models\Calls;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;


class CallController extends Controller
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
        $calls = Calls::where(['filial_id'=>Auth::user()->filial_id])->paginate($limit_count);
        return response($calls,200);
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
//        $request->validate([
//            'costumer_id'=>'required|integer',
//            'audio'=>'required|mimes:mp3,wav,ogg',
//            'izoh'=>'string',
//        ]);
         $costumer = Costumers::where(['costumers_filial_id'=>Auth::user()->filial_id,'costumer_phone_1'=>$request->input('costumer_phone')])
             ->orWhere(['costumers_filial_id'=>Auth::user()->filial_id,'costumer_phone_2'=>$request->input('costumer_phone')])
             ->first();
//         return  $costumer;
//         exit;
         if (!$costumer)
         {
             return response([
                 'message'=>'Costumer not found'
             ],404);
         }

         if ($request->hasFile('audio')) {


             $file = $request->file('audio');
             $filename = Str::random(64) . '.' . $file->getClientOriginalExtension();
             $path = $file->move(public_path('uploads/audios'), $filename);
//        str_replace('/home/','test',$path);
//        return $path;
//        exit;
             $new_call = Calls::create([
                 'costumer_id'=>$costumer->id,
                 'operator_id' => Auth::id(),
                 'filial_id' => Auth::user()->filial_id,
                 'audio' => $path,
                 'audio_name' => $filename,
                 'customer_phone' => $request->input('costumer_phone'),
                 'date' => date('Y-m-d H:i:s'),
             ]);
             if ($new_call) {
                 return response([
                     'message' => 'Call recorded'
                 ], 200);
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
        $one = Calls::where(['filial_id'=>Auth::user()->filial_id,'id'=>$id])->first();
        if (!$one)
        {
            return response([
                'message'=>'Not found'
            ]);
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
        $one = Calls::where(['filial_id'=>Auth::user()->filial_id,'id'=>$id])->first();
        if (!$one)
        {
            return response([
                'message'=>'Not found'
            ]);
        }
        $update = $one->update([
            $request->all()
        ]);
        return response([
            'message'=>'success update',
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
        //
    }
}
