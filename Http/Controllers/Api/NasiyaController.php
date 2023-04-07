<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Costumers;
use App\Models\MijozKirim;
use App\Models\User;
use Illuminate\Http\Request;

use App\Models\Nasiya;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use mysql_xdevapi\Expression;

class NasiyaController extends Controller
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
        $muddat = $request->get('muddat');
        $status = $request->get('status');
        $limit_count = 20;
        if ($limit) {
            $limit_count = $limit;
        }
        $nasiya = Nasiya::query()
            ->where('filial_id', Auth::user()->filial_id)
            ->where('summa', '!=', 0)
            ->where('status', '!=', '4');
        if ($status == 'unmarked') {
            $nasiya = $nasiya->where(['filial_id' => Auth::user()->filial_id, 'status' => 0]);
        } else if ($status == 'marked') {
            $nasiya = $nasiya->where(['filial_id' => Auth::user()->filial_id])->where('status', '=', 1);
        }

        $nasiya =  $nasiya->orderBy('ber_date');
        return $nasiya->with('nasiyachi:id,costumer_name,costumer_phone_1,costumer_phone_2')->with('user:id,fullname')->paginate($limit_count);
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
        $nasiya = Nasiya::where(['id' => $id, 'filial_id' => Auth::user()->filial_id])
            ->with('nasiyachi')
            ->first();
        if (!$nasiya) {
            return response(['message' => 'Not Found'], 404);
        }
        return $nasiya;
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

    public function search(Request $request)
    {
        $limit = $request->get('limit');
        $limit_count = 15;
        if ($limit) {
            $limit_count = $limit;
        }

        $nasiya = Nasiya::query()
            ->where('filial_id', Auth::user()->filial_id)
            ->where('summa', '!=', 0)
            ->where('status', '!=', '4')
            ->when(!empty(request('status')), function ($query) {
                if (request('status') == 'unmarked') {
                    $query->where('status', '=', 0);
                }
                if (request('status') == 'marked') {
                    $query->where('status', '=', 1);
                }
            })
            ->rightJoin('costumers', function (JoinClause $join) {
                $join->on('nasiya.nasiyachi_id', '=', 'costumers.id')
                    ->when(!empty(request('q')), function ($query) {
                        $query->where('costumer_name', 'LIKE', '%' . request('q') . '%')
                            ->orWhere('costumer_phone_1', 'LIKE', '%' . request('q') . '%')
                            ->orWhere('costumer_phone_2', 'LIKE', '%' . request('q') . '%');
                    });
            })
            ->select('nasiya.*')
            ->orderBy('ber_date')
            ->with('nasiyachi:id,costumer_name,costumer_phone_1,costumer_phone_2')
            ->with('user:id,fullname')
            ->paginate($limit_count);

        return response($nasiya);
    }

    public function filter(Request $request)
    {
        $nasiya = Nasiya::query();
        $nasiya  = $nasiya->where(['filial_id' => Auth::user()->filial_id])
            ->where('status', '!=', '4')
            ->count();

        $users = User::query();
        $users = $users
            ->select('id', 'fullname')
            ->where(['filial_id' => Auth::user()->filial_id])
            ->where('role', '!=', 'admin_filial')
            ->withCount('nasiya as count')
            ->get()->toArray();
        array_push($users, ['id' => 0, 'fullname' => 'Barchasi', 'count' => $nasiya]);
        return $users;
    }
    public function nasiyaol($nasiya_id, Request $request)
    {
        $summa = $request->input('summa');
        $tolov_turi = $request->input('tolov_turi');
        $izoh = $request->input('izoh');

        $nasiya = Nasiya::where(['id' => $nasiya_id])->first();

        if (!$nasiya) {
            return response('Bunday nasiya topilmadi', 404);
        }


        $ol_summa = $nasiya->summa - $summa;

        if ($ol_summa >= 0) {
            $mijozKirim = MijozKirim::create([
                'summa' => $ol_summa,
                'tolov_turi' => $tolov_turi,
                'order_id' => $nasiya->order_id,
                'costumer_id' => $nasiya->nasiyachi_id,
                'date' => date('Y-m-d H:i:s'),
                'status' => 'olindi',
                'user_id' => Auth::id(),
                'kirim_izoh' => $izoh ?? "",
                'filial_id' => Auth::user()->filial_id,
                'kassachi_id' => 0,
                'user_fullname' => '',
                'kassachi_fullname' => '',
                'costumer' => ''
            ]);

            if (!$mijozKirim) {
                return response('MijozKirim yaratishda xatolik', 500);
            }

            $nasiya->update([
                'summa' => $ol_summa
            ]);

            if ($tolov_turi == 'naqd') {
                $sum = $summa + Auth::user()->balance;
                User::query()->where(['id' => Auth::id()])->first()->update([
                    'balance' => $sum,
                ]);
            } else {
                if ($tolov_turi == 'Terminal-bank') {
                    $sum = $summa + Auth::user()->plastik;
                    User::query()->where(['id' => Auth::id()])->first()->update([
                        'plastik' => $sum,
                    ]);
                } elseif ($tolov_turi == 'click') {
                    $sum = $summa + Auth::user()->click;
                    User::query()->where(['id' => Auth::id()])->first()->update([
                        'click' => $sum,
                    ]);
                }
            }
            if ($ol_summa == 0) {
                $nasiya->update(['status' => 3, 'summa' => 0]);
            }
            return response(['message' => 'Tolov qabul qilindi']);
        }
    }
    public function kechish($nasiya_id, Request $request)
    {
        $nasiya = Nasiya::where(['id' => $nasiya_id]);
        if ($nasiya->first()->summa > 0) {
            $mijoz = MijozKirim::create([
                'summa' => $nasiya->first()->summa,
                'tolov_turi' => '',
                'order_id' => $nasiya->first()->order_id,
                'costumer_id' => $nasiya->first()->nasiyachi_id,
                'date' => date('Y-m-d H:i:s'),
                'status' => 'kechildi',
                'user_id' => Auth::id(),
                'kirim_izoh' => $request->input('izoh') ? $request->input('izoh') : '',
                'filial_id' => Auth::user()->filial_id,
                'kassachi_id' => 0,
                'user_fullname' => '',
                'kassachi_fullname' => '',
                'costumer' => '',
            ]);
            $nasiya->update([
                'status' => 4
            ]);
            return response([
                'message' => 'Nasiya kechildi'
            ]);
        } else {
            return response('Qarzi yo`q');
        }
    }
    public function end(Request $request, $nasiya_id)
    {
        $date = $request->input('date');
        $nasiya = Nasiya::where(['filial_id' => Auth::user()->filial_id, 'id' => $nasiya_id]);

        if (!$nasiya->first())
            return response(['message' => 'not found nasiyya'], 404);

        $nasiya->update([
            'status' => 1,
            'ber_date' => $date
        ]);
        return  response(['message' => 'qabul qilindi']);
    }
}
