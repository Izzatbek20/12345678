<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Xizmatlar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class XizmatlarController extends Controller
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
            $limit_count = $limit;

        $all = Xizmatlar::where(['filial_id'=>\Auth::user()->filial_id,'status'=>'active'])->paginate($limit_count);
        return $all;
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
           'xizmat_turi'=>'required|string',
            'status'=>'required|string',
            'olchov'=>'required|string',
            'narx'=>'required|integer',
            'min_narx'=>'required|integer',
            'saygak_narx'=>'integer',
            'discount_for_own'=>'integer',
            'operator_kpi_line'=>'integer'
        ]);
        return $request->all();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $one = Xizmatlar::where(['filial_id'=>Auth::user()->filial_id,'xizmat_id'=>$id])->first();
        if (!$one)
        {
            return  response([
                'message'=>'Not found',
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
}
