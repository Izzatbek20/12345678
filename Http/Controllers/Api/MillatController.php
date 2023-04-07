<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Millat;
use Illuminate\Support\Facades\Auth;

class MillatController extends Controller
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
        return Millat::where('filial_id',Auth::user()->filial_id)->get();
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
             'name'=>['required','string']
         ]);
         $millat = Millat::create([
            'name'=>$request->name,
            'filial_id'=>Auth::user()->filial_id,
         ]);
         if ($millat)
         {
             return response([
                 'message'=>'Millat created',
                 'id'=>$millat->id,
             ],201);
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
        $one = Millat::where(['filial_id'=>Auth::user()->filial_id,'id'=>$id])->first();
        if (!$one)
        {
            return response([
                'message'=>'Not found'
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
        $one = Millat::where(['filial_id'=>Auth::user()->filial_id,'id'=>$id])->first();
        if (!$one)
        {
            return response([
                'message'=>'Not Found'
            ],404);
        }

        $update =  $one->update([
            'name'=>$request->name
        ]);
        if ($update)
        {
            return response(['message'=>'Success updated'],200);
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
        $one = Millat::where(['filial_id'=>Auth::user()->filial_id,'id'=>$id])->first();
        if (!$one)
        {
            return response([
                'message'=>'Not Found'
            ],404);
        }

        Millat::destroy($id);
        return response(['message'=>'Success deleted'],200);
    }
}
