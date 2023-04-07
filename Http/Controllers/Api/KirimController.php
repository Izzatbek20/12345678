<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Kirim;
use Illuminate\Support\Facades\Auth;

class KirimController extends Controller
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
        $kirim = Kirim::where(['kirim_filial_id'=>Auth::user()->filial_id])->paginate($limit_count);
        if (!$kirim)
        {
            return response([
                'message'=>'Not found'
            ]);
        }
        return $kirim;
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
        $kirim = Kirim::where(['kirim_filial_id'=>Auth::user()->filial_id,'kirim_id'=>$id])->first();
        if (!$kirim)
        {
            return response([
                'message'=>'Not found'
            ],404);
        }
        return response($kirim,200);
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
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $kirim = Kirim::where(['kirim_id'=>$id,'kirim_filial_id'=>Auth::user()->filial_id])->first();
        if ($kirim)
        {
            $destroy = Kirim::where('kirim_id',$id)->delete();
            if ($destroy)
            {
                return response(['message'=>'success deleted']);
            }
            return response(['message'=>'not deleted']);
        }
        else
        {
            return response(['message'=>'Not found'],404);
        }
    }
}
