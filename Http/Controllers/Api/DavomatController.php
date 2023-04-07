<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Darvoza;
use App\Models\Davomat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DavomatController extends Controller
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

        return Davomat::where(['filial_id'=>Auth::user()->filial_id])->paginate($limit_count);
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
        $davomat =  Davomat::find($id)->where('filial_id',Auth::user()->filial_id)->first();
        if (!$davomat)
        {
            return response(['message'=>"$id dagi malumot topilmadi"],404);
        }
        return $davomat;
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
        $one = Davomat::find($id)->where('filial_id',Auth::user()->filial_id)->first();
        if ($one)
        {
            $destroy = Davomat::destroy($id);
            if ($destroy)
            {
                return response(['message'=>'Success deleted']);
            }
            else
            {
                return response(['message'=>'Not deleted']);
            }
        }
        else
        {
            return response(['message'=>'Not found'],404);
        }
    }
    public function keldi($id)
    {
        $xodim = User::where('filial_id',Auth::user()->filial_id)->where('id',$id)->first();
        if (!$xodim)
        {
            return response([
                'message'=>'Bunday xodim topilmadi'
            ],404);
        }
        $davomat = Davomat::create([
            'user_id'=>$id,
            'sana'=>date('Y-m-d'),
            'status'=>1,
            'filial_id'=>Auth::user()->filial_id,
            'keldi'=>1,
            'ketdi'=>0,
            'keldi_time'=>date('H:i:s'),
            'ketdi_time'=>0,
            'maosh'=>0,
            'type'=>0,
            'ketdi_darvoza_id'=>0,
            'keldi_darvoza_id'=>0,
        ]);
        return response([
            'message'=>$xodim->fullname.' keldi'
        ],200);
    }
    public function ketdi($id)
    {
       $davomat = Davomat::where(['user_id'=>$id,'filial_id'=>Auth::user()->filial_id])->latest()->first();
        if ($davomat and $davomat->keldi == 1)
        {
            $update =  $davomat->update([
                'user_id'=>$id,
                'sana'=>date('Y-m-d'),
                'status'=>0,
                'filial_id'=>Auth::user()->filial_id,
                'keldi'=>1,
                'ketdi'=>1,
                'keldi_time'=>$davomat->keldi_time,
                'ketdi_time'=>date('H:i:s'),
                'ketdi_darvoza_id'=>0,
                'keldi_darvoza_id'=>0,
                'maosh'=>0,
                'type'=>0,
            ]);
            if ($update)
            {
                return response(['message'=>'xodim ketdi'],200);
            }
        }
        else
        {
            return response(['message'=>'bu xodim ishga kelmagan']);
        }
    }
    public function fingerKeldi($user_id,Request $request)
    {
        $token = $request->get('token');
        if (!$token)
        {
            return response([
                'message'=>'Token empty',
            ]);
        }
        $user = User::where(['filial_id'=>Auth::user()->filial_id,'id'=>$user_id])->first();
        if (!$user)
        {
            return response([
                'message'=>'Not found user',
            ],404);
        }
        $darvoza = Darvoza::where(['filial_id'=>Auth::user()->filial_id,'token'=>$token])->first();
        if (!$darvoza)
        {
            return  response([
                'message'=>'Darvoza topilmadi',
            ],404);
        }
        $davomat = Davomat::create([
            'user_id'=>$user_id,
            'sana'=>date('Y-m-d'),
            'status'=>1,
            'filial_id'=>Auth::user()->filial_id,
            'keldi'=>1,
            'ketdi'=>0,
            'keldi_time'=>date('H:i:s'),
            'ketdi_time'=>0,
            'maosh'=>0,
            'type'=>0,
            'ketdi_darvoza_id'=>$darvoza->id,
            'keldi_darvoza_id'=>0,
        ]);
        if ($darvoza)
        {
            return response([
                'message'=> $user->fullname. ' ' . $darvoza->nomi . ' darvozadan ishga keldi'
            ]);
        }
    }
    public function fingerKetdi($user_id,Request $request)
    {

        $token = $request->get('token');
        if (!$token)
        {
            return response([
                'message'=>'Token empty',
            ]);
        }
        $user = User::where(['filial_id'=>Auth::user()->filial_id,'id'=>$user_id])->first();
        if (!$user)
        {
            return response([
                'message'=>'Not found user',
            ],404);
        }
        $darvoza = Darvoza::where(['filial_id'=>Auth::user()->filial_id,'token'=>$token])->first();
        if (!$darvoza)
        {
            return  response([
                'message'=>'Darvoza topilmadi',
            ],404);
        }
        $davomat = Davomat::query();
        $davomat = $davomat->where(['filial_id'=>Auth::user()->filial_id,'user_id'=>$user_id])->latest()->first();
        if ($davomat)
        {
            $davomat->update([
                'status'=>0,
                'ketdi'=>1,
                'ketdi_time'=>date('H:i:s'),
                'keldi_darvoza_id'=>$darvoza->id,
            ]);
            return response([
                'message'=>$user->fullname. ' '. $darvoza->nomi . ' darvozadan ishdan ketdi'
            ]);
        }
    }
}
