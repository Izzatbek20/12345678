<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Darvoza;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DarvozaController extends Controller
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
        $darvoza = Darvoza::where(['filial_id'=>Auth::user()->filial_id])->get();
        return $darvoza;
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
            'nomi'=>'required|string'
        ]);
        $darvoza = Darvoza::create([
            'filial_id'=>Auth::user()->filial_id,
            'nomi'=>$request->input('nomi'),
            'token'=>Str::random(32),
        ]);
        if ($darvoza)
        {
            return response([
                'message'=>'Success created',
                'darvoza_id'=>$darvoza->id
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
        $one = Darvoza::where(['filial_id'=>Auth::user()->filial_id,'id'=>$id])->first();
        if (!$one)
        {
            return response([
                'message'=>'Not Found'
            ],404);
        }
        return response($one,200);
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
        $one = Darvoza::where(['filial_id'=>Auth::user()->filial_id,'id'=>$id])->first();
        if (!$one)
        {
            return response([
                'message'=>'Not Found'
            ],404);
        }
        $request->validate([
            'nomi'=>'required|string',
        ]);
        $update = Darvoza::where(['filial_id'=>Auth::user()->filial_id,'id'=>$id])->update([
            'nomi'=>$request->input('nomi'),
        ]);
        if ($update)
        {
            return response(['success updated'],200);
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
        $one = Darvoza::where(['filial_id'=>Auth::user()->filial_id,'id'=>$id])->first();
        if (!$one)
        {
            return response([
                'message'=>'Not Found'
            ],404);
        }
        Darvoza::destroy($id);
        return response([
            'message'=>'Success deleted'
        ]);
    }
}
