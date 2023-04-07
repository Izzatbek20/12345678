<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chiqim;
use App\Models\Clean;
use App\Models\Costumers;
use App\Models\Davomat;
use App\Models\Kirim;
use App\Models\KpiHisob;
use App\Models\MijozKirim;
use App\Models\Nasiya;
use App\Models\Order;
use App\Models\Recall;
use App\Models\User;
use App\Models\Xizmatlar;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations\Get;

use function PHPUnit\Framework\returnCallback;

class KorsatkichController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }
    public function index(Request $request)
    {
        $from = $request->get('from');
        $to = $request->get('to');

        if (!$from) {
            $from = date('Y-m-d', strtotime(date('Y-m-d') . ' -1 day'));
        }
        if (!$to) {
            $to = date('Y-m-d');
        }

        // Yuvish sonini va summasi aniqlash oalmiz
        $clean = Clean::query()
            ->select(DB::raw('count(*) as clean_count, SUM(clean_hajm) as clean_summ'))
            ->where(['clean_filial_id' => Auth::user()->filial_id])
            ->where(function ($query) {
                $query->where('clean_status', 'quridi')
                    ->orWhere('clean_status', 'qayta quridi');
            })
            ->whereBetween('clean_date', [$from, $to])
            ->first();

        // User ma'lumotlari olamiz
        $current_user = Auth::user()->getXizmatlar;

        // Clean juda ko`p joyda qayta qayta kelganligi sabab uni bita o`zgaruvchiga biriktirib olamiz
        $cleans_qury = Clean::query();

        // Ko`p qaytarilganligi uchun o`zgaruvchiga tenglab olamiz
        $cleans_where = $cleans_qury
            ->where(['clean_product' => 0])
            ->when(!empty($current_user) && $current_user->where('olchov', 'metr')->count() > 0, function ($query) use ($current_user) {
                $query->orWhere(['clean_product' => $current_user->where('olchov', 'metr')->first()->xizmat_id]);
            });

        // Jami yuvulganlar hajm summasi
        $cleans_get = $cleans_where
            ->select(DB::raw('SUM(clean_hajm) as hajm_summ'))
            ->where(['clean_filial_id' => Auth::user()->filial_id])
            ->whereBetween('clean_date', [$from, $to])
            ->first();

        // Qadoqlanganlar soni va hajm summasi
        $qadoqlandi = $cleans_where
            ->where(function ($query) {
                $query->where('clean_status', 'qadoqlandi')
                    ->orWhere('clean_status', 'qayta qadoqlandi');
            })
            ->whereBetween('qad_date', [$from, $to])
            ->select(DB::raw('count(*) as qadoqlandi_count, SUM(clean_hajm) as qadoqlandi_summ'))
            ->first();

        // Joyida yuvilganlar hajm summasi
        $joyida_yuv_summ = $cleans_where
            ->where(['clean_filial_id' => Auth::user()->filial_id])
            ->whereBetween('clean_date', [$from, $to])
            ->sum('clean_hajm');

        // Topshriligan 
        $top_kvm = $cleans_where
            ->where('clean_status', 'topshirildi')
            ->when(Auth::user()->role == 'yuvish' || Auth::user()->role == 'transport' || Auth::user()->role == 'tayorlov' || Auth::user()->role == 'saygak', function ($query) {
                $query->where(['top_user' => Auth::id()]);
            })
            ->select(DB::raw('SUM(clean_hajm) as summa_clean'))
            ->where('clean_filial_id', Auth::user()->filial_id)
            ->whereBetween('top_sana', [$from, $to])
            ->first();

        $qayta_y = clone $cleans_qury;
        $qayta_kv = clone $cleans_qury;
        $topshirildi = clone $cleans_qury;
        $olindi = clone $cleans_qury;

        $order_query = Order::query();

        $ozi_olib_ketadi = clone $order_query;
        $recall6 = clone $order_query;
        $olbk_num = clone $order_query;
        $kechagi_recall = clone $order_query;
        $order_qabul  = clone $order_query;
        $order_cancel = clone $order_query;

        $maosh_history = Chiqim::query();

        $rejadagi_call = Recall::query();
        $mijozlar = Costumers::query();
        $davomat = Davomat::query();


        $olibk_num = $olbk_num
            ->where('order_filial_id', '=', Auth::user()->filial_id)
            ->where('order_status', '!=', 'bekor qilindi')
            ->where('order_status', '!=', 'keltirish')
            ->whereDate('olibk_sana', '>=', $from)
            ->whereDate('olibk_sana', '<=', $to)
            ->count();

        $olindi = $olindi->where(['clean_filial_id' => Auth::user()->filial_id])
            ->whereDate('sana', '>=', $from)
            ->whereDate('sana', '<=', $to)->count();

        $ozi_olib_ketadi = $ozi_olib_ketadi->where(['order_filial_id' => Auth::user()->filial_od, 'own' => 1])
            ->whereDate('order_date', '>=', $from)
            ->count();
        $rejadagi_call = $rejadagi_call
            ->where(['recall_filial_id' => Auth::user()->filial_id])
            ->whereDate('recall_date', '>=', $from)
            ->whereDate('recall_date', '<=', $to)
            ->count();
        $recall6 = $recall6
            ->where(['order_filial_id' => Auth::user()->filial_id])
            ->whereDate('talk_date2', '>=', $from)
            ->whereDate('talk_date2', '<=', $to)
            ->count();
        $kechagi_recall = $kechagi_recall
            ->where(['order_filial_id' => Auth::user()->filial_id])
            ->whereDate('talk_date2', '>=', $from)
            ->whereDate('talk_date2', '<=', $to)
            ->count();
        $qayta_y = $qayta_y
            ->where(['clean_filial_id' => Auth::user()->filial_id])
            ->whereDate('qayta_sana', '>=', $from)
            ->whereDate('qayta_sana', '<=', $to)
            ->count();
        $qayta_kv = $qayta_kv
            ->where(['clean_filial_id' => Auth::user()->filial_id])
            ->whereDate('qayta_sana', '>=', $from)
            ->whereDate('qayta_sana', '<=', $to)
            ->sum('clean_hajm');

        $mijozlar = $mijozlar->where(['costumers_filial_id' => Auth::user()->filial_id])
            ->whereDate('costumer_date', '>=', $from)
            ->whereDate('costumer_date', '<=', $to)
            ->count();
        $order_qabul = $order_qabul->where(['order_filial_id' => Auth::user()->filial_id])
            ->where('order_driver', '>', 0)
            ->whereDate('order_date', '>=', $from)
            ->whereDate('order_date', '<=', $to)
            ->count();
        $order_cancel = $order_cancel->where(['order_filial_id' => Auth::user()->filial_id, 'order_status' => 'bekor qilindi'])
            //            ->where('order_status','=','bekor')
            //            ->where('order_driver','=',0)
            ->whereDate('order_date', '>=', $from)
            ->whereDate('order_date', '<=', $to)
            ->count();
        $davomat = $davomat
            ->select(['keldi_time', 'ketdi_time', 'keldi', 'ketdi'])
            ->where(['filial_id' => Auth::user()->filial_id])
            ->where('user_id', '=', Auth::id())
            ->whereDate('sana', date('Y-m-d'))
            ->first();

        $maosh_history_c = 0;
        if (in_array(Auth::user()->role, array("yuvish", 'hisobchi', 'transport', 'hisobchi', 'operator', 'transport', 'yuvish', 'tayorlov'))) {
            $maosh_history_c = $maosh_history
                ->where(['doimiy_izoh' => 'maosh', 'chiqim_shaxsiy' => Auth::id()])
                ->whereDate('chiqim_date', '>=', $from)
                ->whereDate('chiqim_date', '<=', $to)
                ->sum('chiqim_summ');
        }

        $kpi = KpiHisob::query()->where(['filial_id' => Auth::user()->filial_id])
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->sum('summa');
        $maosh = Auth::user()->filial->getDavomat()->whereDate('sana', '>=', $from)->whereDate('sana', '<=', $to);
        $kun_maosh = clone $maosh;
        $maosh = $maosh->sum('maosh');
        $kun_maosh = $kun_maosh->where('maosh', '>', 0)->sum('maosh');
        $kechim = Auth::user()->filial->getMijozkirim()->whereDate('date', '>=', $from)->whereDate('date', '<=', $to);
        $nasiya = Auth::user()->filial->getNasiya()->whereDate('date', '>=', $from)->whereDate('date', '<=', $to);
        $topshirildi = $topshirildi->where('clean_status', 'topshirildi')->where(['clean_filial_id' => Auth::user()->filial_id])
            ->whereDate('top_sana', '>=', $from)
            ->whereDate('top_sana', '<=', $to);


        if (Auth::user()->role == 'yuvish' || Auth::user()->role == 'transport' || Auth::user()->role == 'tayorlov' || Auth::user()->role == 'saygak') {
            $topshirildi = $topshirildi->where(['top_user' => Auth::id()]);
        }
        if (Auth::user()->role !== 'operator') {
            $kechim =  $kechim->where(['user_id' => Auth::id()]);
            $nasiya = $nasiya->where(['user_id' => Auth::id()]);
        }
        $topshirildi = $topshirildi->count();
        $nasiya = $nasiya->sum('nasiya');
        $kechim = $kechim->sum('summa');

        $repons = [
            'yuvildi' => [
                'dona' => $clean->clean_count,
                'hajm' => $clean->clean_summ ?? 0 // Yuqorida SUM qilgani 0 bo`lsa null qaytaradi shuning uchun null ga tekshiramiz
            ],
            'hajm' => round($cleans_get->hajm_summ ?? 0, 2),
            'joyida_yuvildi' => round($joyida_yuv_summ, 2),
            'ozi_olib_ket' => $ozi_olib_ketadi,
            'rejadagi_call' => $rejadagi_call,
            'recall6' => $recall6,
            'kechagi_call' => $kechagi_recall,
            'qayta' => [
                'dona' => $qayta_y,
                'hajm' => $qayta_kv
            ],
            'maosh_history' => $maosh_history_c,
            'kpi' => $kpi,
            'maosh' => $maosh,
            'kechim' => $kechim,
            'nasiya' => $nasiya,
            'topshirildi' => [
                'dona' => $topshirildi,
                'hajm' => $top_kvm->summa_clean ?? 0
            ],
            'mijozlar' => $mijozlar,
            'buyurtma_qabul_qilindi' => $order_qabul,
            'buyurtma_bekor_qilindi' => $order_cancel,
            'davomat' => $davomat,
            'qadoqlandi' => [
                'dona' => $qadoqlandi->qadoqlandi_count,
                'hajm' => $qadoqlandi->qadoqlandi_summ ?? 0 // Yuqorida SUM qilgani 0 bo`lsa null qaytaradi shuning uchun null ga tekshiramiz
            ],
            'olib_ketildi' => $olibk_num,
            'olindi' => $olindi,
            'kun_maosh' => $kun_maosh
        ];
        return response($repons, 200);
    }
    public function kassaga(Request $request)
    {
        $from = $request->get('from');
        $to = $request->get('to');
        $kirm =  Kirim::where(['kirim_user_id' => Auth::user()->id, 'kirim_filial_id' => Auth::user()->filial_id])->sum('kirim_summ');
        return response(['summa' => $kirm]);
    }

    public function cleans(Request $request)
    {
        $from = $request->get('from');
        $to = $request->get('to');
        if (!$from) {
            $from = date('Y-m-d', strtotime(date('Y-m-d') . ' -1 day'));
        }
        if (!$to) {
            $to = date('Y-m-d');
        }

        $clean = Clean::query();
        $clean = $clean->where(['clean_filial_id' => Auth::user()->filial_id])
            ->whereDate('sana', '>=', $from)
            ->whereDate('sana', '<=', $to)
            ->get();
        return response($clean);
    }
    public function recall(Request $request)
    {
        $limit = $request->get('limit');
        $limit_count = 50;
        if ($limit) {
            $limit_count = $limit;
        }
        $recall = Recall::query();
        $user_query = "SELECT fullname FROM user WHERE id = recall.user_id LIMIT 1";
        $customer_query = "SELECT costumer_name FROM costumers WHERE costumer_phone_1 = recall.recall_costumer_phone LIMIT 1";

        $recall = $recall
            ->select("
                izoh, recall_time, recall_date,
                (SELECT fullname FROM user WHERE id = recall.user_id LIMIT 1) AS recall_status,
                (SELECT costumer_name FROM costumers WHERE costumer_phone_1 = recall.recall_costumer_phone LIMIT 1) AS recall_costumer_phone,
            ")
            ->where(['recall_status' => 'off', 'recall_filial_id' => Auth::user()->filial_id])
            ->paginate($limit_count);
        return response($recall);
    }
    public function recall6(Request $request)
    {
        $type = $request->get('type');
        $limit = $request->get('limit');
        $limit_count = 50;

        if ($limit) {
            $limit_count = $limit;
        }

        $order = Order::query();

        if ($type == 2) {
            $order = $order->select("
             nomer, talk_date, talk_date2, last_izoh2 as last_izoh, talk_type2 as talk_type,
                    (SELECT fullname FROM user WHERE `id` = `orders`.`last_operator_id2`) AS dog,
                    (SELECT costumer_name FROM costumers WHERE `id` = `orders`.`costumer_id`) AS brak,")
                ->where('last_operator_id2', '>', 0)->where('order_filial_id', Auth::user()->filial_id);
        } else {
            $order = $order->select("
             nomer, talk_date, talk_date2, last_izoh, talk_type,
                    (SELECT fullname FROM user WHERE id = orders.last_operator_id) AS dog,
                    (SELECT costumer_name FROM costumers WHERE id = orders.costumer_id) AS brak,")
                ->where('last_operator_id', '>', 0)->where('order_filial_id', Auth::user()->filial_id);
        }
        $order = $order->paginate($limit_count);
        return response($order, 200);
    }
    public function qayta(Request $request)
    {
        $limit = $request->get('limit');
        $limit_count = 50;
        $from = $request->get('from');
        $to = $request->get('to');

        if ($limit) {
            $limit_count = $limit;
        }
        if (!$from)
            $from = date('Y-m-d', strtotime(date('Y-m-d') . ' -1 day'));
        if (!$to)
            $to = date('Y-m-d');

        $xizmat = Xizmatlar::query();
        $xizmat = $xizmat->where(['filial_id' => Auth::user()->filial_id, 'status' => 'active'])->get();


        $orders = Order::query();
        $orders = $orders->where(['order_filial_id' => Auth::user()->filial_id, 'order_status' => 'qayta yuvish'])
            ->whereDate('order_date', '>=', $from)
            ->whereDate('order_date', '<=', $to)
            ->with('custumer:costumer_name')
            ->with('cleans')
            ->with('cleans.xizmat')
            ->withSum('naqd', 'summa')
            ->withSum('terminal', 'summa')
            ->withSum('click', 'summa')
            ->withSum('kechildi', 'summa')
            ->withSum('nasiya', 'summa');
        if (Auth::user()->role == 'operator') {
            return  $orders->paginate($limit_count);
        } else {

            $p = $request->get('p');
            $orders = $orders->paginate($limit_count);
            $soni = [];
            $all = [];

            foreach ($orders as $order) :
                foreach ($xizmat as $item) {
                    $cleans = Clean::where(['order_id' => $order->order_id, 'clean_product' => $item->xizmat_id]);
                    if ($cleans->count() > 0) {
                        $soni[] = [
                            'id' => $item->xizmat_id,
                            'xizmat_turi' => $item->xizmat_turi,
                            'count' => $cleans->count(),
                            'olchov' => $item->olchov,
                            'hajmi' => $cleans->sum('clean_hajm')
                        ];
                    }
                }
                $order['soni'] = $soni;
                $all[] = $order;
            endforeach;
            $pages = !empty($request->input('page')) ? (int) $request->input('page') : 1;
            $total = count($all);
            $limits = 50;
            $totalPages = ceil($total / $limits);
            $pages = max($pages, 1);
            $pages = min($pages, $totalPages);
            $offset = ($pages - 1) * $limits;

            $pg = array_slice($all, $offset, $limits);

            return response($pg);
        }
    }
    public function kechilgan(Request $request)
    {
        $limit = $request->get('limit');
        $limit_count = 50;
        $from = $request->get('from');
        $to = $request->get('to');

        if ($limit) {
            $limit_count = $limit;
        }
        if (!$from)
            $from = date('Y-m-d', strtotime(date('Y-m-d') . ' -1 day'));
        if (!$to)
            $to = date('Y-m-d');

        $mijoz_kirim = MijozKirim::query();
        $mijoz_kirim = $mijoz_kirim->where(['filial_id' => Auth::user()->filial_id, 'user_id' => Auth::id()])
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->with('user:id,fullname')
            ->with('order.custumer')
            //            ->with('order')
            ->paginate($limit_count);
        return $mijoz_kirim;
    }
    public function topshirildi(Request $request)
    {

        $limit = $request->get('limit');
        $from = $request->get('from');
        $to = $request->get('to');
        $p = $request->get('p');

        if (empty($limit)) {
            $limit = 8;
        }
        if (!$from)
            $from = date('Y-m-d', strtotime(date('Y-m-d') . ' -1 day'));
        if (!$to)
            $to = date('Y-m-d');

        if (Auth::user()->role == 'tayorlov' or Auth::user()->role == 'yuvish') {


            // Requestlarni olamiz
            $limit = $request->get('limit');
            $from = $request->get('from');
            $to = $request->get('to');
            $p = $request->get('p');

            $limit_count = 20;
            if ($limit) {
                $limit_count = $limit;
            }

            if (!$from)
                $from = date('Y-m-d', strtotime(date('Y-m-d') . ' -1 day'));
            if (!$to)
                $to = date('Y-m-d');


            $data = Clean::query()
                ->where('clean_filial_id', Auth::user()->filial_id)
                ->where('clean_status', 'topshirildi')
                ->whereDate('clean_date', '>=', $from)
                ->whereDate('clean_date', '<=', $to)
                ->join('user', 'user.id', '=', 'clean.user_id')
                ->join('orders', 'orders.order_id', '=', 'clean.order_id')
                ->join('xizmatlar', 'xizmatlar.xizmat_id', '=', 'clean.clean_product')
                ->with('custumer')
                ->select(
                    'user.id as id',
                    'orders.nomer as kv_id',
                    'user.fullname as fullname',
                    // 'xizmatlar.xizmat_turi as xizmat',
                )
                ->groupBy('user_id')
                ->get();

            $pages = !empty($p) ? (int) $p : 1;
            $total = count($data);
            $limits = $limit_count;
            $totalPages = ceil($total / $limits);
            $pages = max($pages, 1);
            $pages = min($pages, $totalPages);
            $offset = ($pages - 1) * $limits;

            $pg = array_slice($data->toArray(), $offset, $limits);

            return response($pg);

            // $order_son = Order::query()
            //     ->leftJoin(DB::raw('clean'), function (JoinClause $join) {
            //         $join->on('orders.order_id', '=', 'clean.order_id');
            //         $join->leftJoin('xizmatlar', function (JoinClause $join) {
            //             $join->on('clean.clean_product', '=', 'xizmatlar.xizmat_id');
            //         });
            //     })
            //     ->groupBy('clean.clean_product')
            //     ->select(
            //         'orders.order_id as order_id',
            //         'xizmatlar.xizmat_id as xizmat_id',
            //         'xizmatlar.xizmat_turi as xizmat_turi',
            //         DB::raw('count(*) as count')
            //     )
            //     ->where(['order_filial_id' => Auth::user()->filial_id, 'orders.order_id' => $order_id])
            //     ->get()
            //     ->map(function ($query) {
            //         $query->child = Clean::query()
            //             ->where('order_id', $query->order_id)
            //             ->where('clean_product', $query->xizmat_id)
            //             ->leftJoin('xizmatlar', function (JoinClause $join) {
            //                 $join->on('clean.clean_product', '=', 'xizmatlar.xizmat_id');
            //             })
            //             ->select(
            //                 'clean.clean_hajm as hajm',
            //                 'clean.clean_narx as clean_narx',
            //                 'clean.gilam_eni as gilam_eni',
            //                 'clean.clean_status as status',
            //                 'clean.gilam_boyi as gilam_boyi',
            //                 'xizmatlar.xizmat_turi as xizmat_turi',
            //                 'xizmatlar.olchov as olchov',
            //                 'clean.barcode as barcode',
            //                 'xizmatlar.min_narx as min_narx',
            //             )
            //             ->get();
            //         return $query;
            //     });

            // $order = Order::query()
            //     ->where('order_filial_id', Auth::user()->filial_id)
            //     ->leftJoin(DB::raw('clean'), function (JoinClause $join) use ($from, $to) {
            //         $join->on('orders.order_id', '=', 'clean.order_id')
            //             ->whereDate('clean.top_sana', '>=', $from)
            //             ->whereDate('clean.top_sana', '<=', $to);
            //         // $join->leftJoin('xizmatlar', function (JoinClause $join) {
            //         //     $join->on('clean.clean_product', '=', 'xizmatlar.xizmat_id');
            //         // });
            //     })
            //     // ->whereHas('cleans', function ($query) use ($from, $to) {
            //     //     $query->whereDate('top_sana', '>=', $from)
            //     //         ->whereDate('top_sana', '<=', $to);
            //     // })
            //     ->with('cleans')
            //     ->with('cleans.xizmat')
            //     ->with('custumer')
            //     ->paginate(10);

            // return response($order);
        } else {
            $order = Order::query();
            $order = $order->where(['order_filial_id' => Auth::user()->filial_id, 'order_status' => 'topshirildi'])
                ->whereDate('order_date', '>=', $from)
                ->whereDate('order_date', '<=', $to)
                ->with('cleans')
                ->with('cleans.xizmat')
                ->withSum('naqd', 'summa')
                ->withSum('terminal', 'summa')
                ->withSum('click', 'summa')
                ->withSum('kechildi', 'summa')
                ->withSum('nasiya', 'summa')
                ->with('custumer')
                ->with('operator');

            $order = $order->paginate($limit);
            return $order;
        }
    }
    public function topshirildiHodim($id, Request $request)
    {
        // Requestlarni olamiz
        $limit = $request->get('limit');
        $from = $request->get('from');
        $to = $request->get('to');
        $p = $request->get('p');

        $limit_count = 20;
        if ($limit) {
            $limit_count = $limit;
        }

        if (!$from)
            $from = date('Y-m-d', strtotime(date('Y-m-d') . ' -1 day'));
        if (!$to)
            $to = date('Y-m-d');

        $data = Clean::query()
            ->where('clean_filial_id', Auth::user()->filial_id)
            ->where('clean_status', 'topshirildi')
            ->where('user_id', $id)
            ->whereDate('clean_date', '>=', $from)
            ->whereDate('clean_date', '<=', $to)
            ->join('xizmatlar', 'xizmatlar.xizmat_id', '=', 'clean.clean_product')
            // ->select(
            //     'xizmatlar.xizmat_turi as xizmat',
            //     'clean.clean_hajm as hajm',
            //     'clean.clean_date as sana',
            //     'xizmatlar.olchov as olchov',
            // )
            ->with('order:order_id,nomer')
            ->with('custumer:id,costumer_name')
            ->get();

        $pages = !empty($p) ? (int) $p : 1;
        $total = count($data);
        $limits = $limit_count;
        $totalPages = ceil($total / $limits);
        $pages = max($pages, 1);
        $pages = min($pages, $totalPages);
        $offset = ($pages - 1) * $limits;

        $pg = array_slice($data->toArray(), $offset, $limits);

        return response($pg);
    }
    public function topshirildiTransport(Request  $request)
    {
        $limit = $request->get('limit');
        $limit_count = 50;
        $from = $request->get('from');
        $to = $request->get('to');
        $p = $request->get('p');

        if ($limit) {
            $limit_count = $limit;
        }
        if (!$from)
            $from = date('Y-m-d', strtotime(date('Y-m-d') . ' -1 day'));
        if (!$to)
            $to = date('Y-m-d');
        $xizmat = Xizmatlar::query();
        $xizmat = $xizmat->where(['filial_id' => Auth::user()->filial_id, 'status' => 'active'])->get();

        //        $order_sub = Order::query()
        //            ->whereColumn('order_id','clean.order_id')
        //            ->get();
        $cleans = Clean::query();
        $cleans = $cleans
            ->select('order_id')
            ->where(['clean_filial_id' => Auth::user()->filial_id])
            ->whereDate('top_sana', '>=', $from)
            ->whereDate('top_sana', '<=', $to)
            ->groupBy('order_id')
            ->paginate($limit_count);
        $all = [];
        $soni  = [];
        $ord = [];

        foreach ($cleans as $clean) {
            $orders = Order::query()->where(['order_filial_id' => Auth::user()->filial_id, 'order_id' => $clean->order_id])
                ->with('cleans.topshiruvchi:id,fullname')
                ->with('custumer')
                ->first();

            foreach ($xizmat as $item) {
                $cleans = Clean::where(['order_id' => $orders->order_id, 'clean_product' => $item->xizmat_id]);
                $soni[] = [
                    'xizmat_turi' => $item->xizmat_turi,
                    'count' => $cleans->count(),
                    'id' => $item->xizmat_id,
                    'olchov' => $item->olchov,
                    'hajm' => $cleans->sum('clean_hajm')
                ];
            }
            $orders = $orders->toArray();
            $ord[] = array_merge($orders, ['soni' => $soni]);
        }

        $pages = !empty($request->input('page')) ? (int) $request->input('page') : 1;
        $total = count($ord);
        $limits = 50;
        $totalPages = ceil($total / $limits);
        $pages = max($pages, 1);
        $pages = min($pages, $totalPages);
        $offset = ($pages - 1) * $limits;

        $pg = array_slice($ord, $offset, $limits);

        return response($pg);
    }
    public function qadoqlandi(Request $request)
    {
        // Requestlarni olamiz
        $limit = $request->get('limit');
        $from = $request->get('from');
        $to = $request->get('to');
        $p = $request->get('p');

        $limit_count = 20;
        if ($limit) {
            $limit_count = $limit;
        }

        if (!$from)
            $from = date('Y-m-d', strtotime(date('Y-m-d') . ' -1 day'));
        if (!$to)
            $to = date('Y-m-d');


        $data = Clean::query()
            ->where('clean_filial_id', Auth::user()->filial_id)
            ->where(function ($query) {
                $query->where('clean_status', 'qadoqlandi')
                    ->orWhere('clean_status', 'qayta qadoqlandi');
            })
            ->whereDate('clean_date', '>=', $from)
            ->whereDate('clean_date', '<=', $to)
            ->join('user', 'user.id', '=', 'clean.user_id')
            ->join('orders', 'orders.order_id', '=', 'clean.order_id')
            ->join('xizmatlar', 'xizmatlar.xizmat_id', '=', 'clean.clean_product')
            ->select(
                'user.id as id',
                'orders.nomer as kv_id',
                'user.fullname as fullname',
                // 'xizmatlar.xizmat_turi as xizmat',
            )
            ->groupBy('user_id')
            ->get();

        $pages = !empty($p) ? (int) $p : 1;
        $total = count($data);
        $limits = $limit_count;
        $totalPages = ceil($total / $limits);
        $pages = max($pages, 1);
        $pages = min($pages, $totalPages);
        $offset = ($pages - 1) * $limits;

        $pg = array_slice($data->toArray(), $offset, $limits);

        return response($pg);
    }
    // Zakazlarni qadoqlandi hodimlarni zakazlar kesimida ko`rib chiqish
    public function hodimQadoqlandi($id, Request $request)
    {
        // Requestlarni olamiz
        $limit = $request->get('limit');
        $from = $request->get('from');
        $to = $request->get('to');
        $p = $request->get('p');

        $limit_count = 20;
        if ($limit) {
            $limit_count = $limit;
        }

        if (!$from)
            $from = date('Y-m-d', strtotime(date('Y-m-d') . ' -1 day'));
        if (!$to)
            $to = date('Y-m-d');

        $data = Clean::query()
            ->where('clean_filial_id', Auth::user()->filial_id)
            ->where(function ($query) {
                $query->where('clean_status', 'qadoqlandi')
                    ->orWhere('clean_status', 'qayta qadoqlandi');
            })
            ->where('user_id', $id)
            ->whereDate('clean_date', '>=', $from)
            ->whereDate('clean_date', '<=', $to)
            ->join('xizmatlar', 'xizmatlar.xizmat_id', '=', 'clean.clean_product')
            // ->select(
            //     'xizmatlar.xizmat_turi as xizmat',
            //     'clean.clean_hajm as hajm',
            //     'clean.clean_date as sana',
            //     'xizmatlar.olchov as olchov',
            // )
            ->with('order:order_id,nomer')
            ->with('custumer:id,costumer_name')
            ->get();

        $pages = !empty($p) ? (int) $p : 1;
        $total = count($data);
        $limits = $limit_count;
        $totalPages = ceil($total / $limits);
        $pages = max($pages, 1);
        $pages = min($pages, $totalPages);
        $offset = ($pages - 1) * $limits;

        $pg = array_slice($data->toArray(), $offset, $limits);

        return response($pg);
    }
    public function joyida(Request $request)
    {
        $limit = $request->get('limit');
        $limit_count = 50;
        $from = $request->get('from');
        $to = $request->get('to');

        if ($limit) {
            $limit_count = $limit;
        }
        if (!$from)
            $from = date('Y-m-d', strtotime(date('Y-m-d') . ' -1 day'));
        if (!$to)
            $to = date('Y-m-d');
        $cleans = Clean::query();

        $cleans = $cleans->select('order_id')
            ->where('clean_filial_id', Auth::user()->filial_id)
            ->whereDate('joyida_date', '>=', $from)
            ->whereDate('joyida_date', '<=', $to)
            ->groupBy('order_id')
            ->paginate($limit_count);

        return $cleans;
    }
    public function yuvildi(Request $request)
    {
        // Requestlarni olamiz
        $limit = $request->get('limit');
        $from = $request->get('from');
        $to = $request->get('to');
        $p = $request->get('p');

        $limit_count = 20;
        if ($limit) {
            $limit_count = $limit;
        }

        if (empty($from))
            $from = date('Y-m-d', strtotime(date('Y-m-d') . ' -1 day'));
        if (empty($to))
            $to = date('Y-m-d');


        $data = Clean::query()
            ->where('clean_filial_id', Auth::user()->filial_id)
            ->where(function ($query) {
                $query->where('clean_status', 'quridi')
                    ->orWhere('clean_status', 'qayta quridi');
            })
            ->whereDate('clean_date', '>=', $from)
            ->whereDate('clean_date', '<=', $to)
            ->join('user', 'user.id', '=', 'clean.user_id')
            ->join('orders', 'orders.order_id', '=', 'clean.order_id')
            ->join('xizmatlar', 'xizmatlar.xizmat_id', '=', 'clean.clean_product')
            ->select(
                'user.id as id',
                'orders.nomer as kv_id',
                'user.fullname as fullname',
                // 'xizmatlar.xizmat_turi as xizmat',
            )
            ->groupBy('user_id')
            ->get();


        $pages = !empty($p) ? (int) $p : 1;
        $total = count($data);
        $limits = $limit_count;
        $totalPages = ceil($total / $limits);
        $pages = max($pages, 1);
        $pages = min($pages, $totalPages);
        $offset = ($pages - 1) * $limits;

        $pg = array_slice($data->toArray(), $offset, $limits);

        return response($pg);
    }

    // Zakazlarni yuvgan hodimlarni zakazlar kesimida ko`rib chiqish
    public function hodim($id, Request $request)
    {
        // Requestlarni olamiz
        $limit = $request->get('limit');
        $from = $request->get('from');
        $to = $request->get('to');
        $p = $request->get('p');

        $limit_count = 20;
        if ($limit) {
            $limit_count = $limit;
        }

        if (empty($from))
            $from = date('Y-m-d', strtotime(date('Y-m-d') . ' -1 day'));
        if (empty($to))
            $to = date('Y-m-d');

        $data = Clean::query()
            ->where('clean_filial_id', Auth::user()->filial_id)
            ->where(function ($query) {
                $query->where('clean_status', 'quridi')
                    ->orWhere('clean_status', 'qayta quridi');
            })
            ->where('user_id', $id)
            ->whereDate('clean_date', '>=', $from)
            ->whereDate('clean_date', '<=', $to)
            ->join('xizmatlar', 'xizmatlar.xizmat_id', '=', 'clean.clean_product')
            // ->select(
            //     'xizmatlar.xizmat_turi as xizmat',
            //     'clean.clean_hajm as hajm',
            //     'clean.clean_date as sana',
            //     'xizmatlar.olchov as olchov',
            // )
            ->with('order:order_id,nomer')
            ->with('custumer:id,costumer_name')
            ->get();

        $pages = !empty($p) ? (int) $p : 1;
        $total = count($data);
        $limits = $limit_count;
        $totalPages = ceil($total / $limits);
        $pages = max($pages, 1);
        $pages = min($pages, $totalPages);
        $offset = ($pages - 1) * $limits;

        $pg = array_slice($data->toArray(), $offset, $limits);

        return response($pg);
    }
    // public function yuvildi(Request $request)
    // {
    //     // Requestlarni olamiz
    //     $limit = $request->get('limit');
    //     $from = $request->get('from');
    //     $to = $request->get('to');
    //     $p = $request->get('p');

    //     $limit_count = 5;
    //     if ($limit) {
    //         $limit_count = $limit;
    //     }

    //     if (!$from)
    //         $from = date('Y-m-d', , strtotime(date('Y-m-d') . ' -1 day'));
    //     if (!$to)
    //         $to = date('Y-m-d');


    //     $data = Order::query()
    //         ->where('order_filial_id', Auth::user()->filial_id)
    //         ->join('clean', function ($query) use ($from, $to) {
    //             $query->on('clean.order_id', '=', 'orders.order_id')
    //                 ->where('clean_status', 'quridi')
    //                 ->whereDate('clean_date', '>=', $from)
    //                 ->whereDate('clean_date', '<=', $to);
    //         })
    //         ->with('custumer:costumer_name')
    //         ->with(['cleans' => function ($query) use ($from, $to) {
    //             $query->where('clean_status', 'quridi')
    //                 ->whereDate('clean_date', '>=', $from)
    //                 ->whereDate('clean_date', '<=', $to);
    //             $query->with([
    //                 'xizmat:xizmat_id,xizmat_turi,olchov',
    //                 'yuvdi:id,fullname',
    //             ]);
    //         }])
    //         ->select(
    //             'orders.order_id',
    //             'orders.nomer',
    //             DB::raw('count(clean.id) as cleans_count'),
    //             DB::raw('sum(clean.clean_narx) as cleans_sum_clean_narx')
    //         )
    //         ->groupBy('orders.nomer')
    //         ->get()
    //         ->map(function ($query) use ($from, $to) {
    //             $data = [];
    //             $findModel = Xizmatlar::query()->where(['filial_id' => Auth::user()->filial_id, 'status' => 'active'])->get();
    //             if (!empty($findModel)) {
    //                 foreach ($findModel as $value) {
    //                     $count = Clean::query()
    //                         ->where('clean_status', 'quridi')
    //                         ->where('clean_product', $value->xizmat_id)
    //                         ->whereDate('clean_date', '>=', $from)
    //                         ->count();
    //                     if (!empty($count)) {
    //                         $data[$value->xizmat_turi] = $count;
    //                     }
    //                 }
    //             }
    //             $query->soni = $data;
    //             return $query;
    //         });


    //     $pages = !empty($p) ? (int) $p : 1;
    //     $total = count($data);
    //     $limits = $limit_count;
    //     $totalPages = ceil($total / $limits);
    //     $pages = max($pages, 1);
    //     $pages = min($pages, $totalPages);
    //     $offset = ($pages - 1) * $limits;

    //     $pg = array_slice($data->toArray(), $offset, $limits);

    //     return response($pg);
    // }
    public function mahsulot(Request $request)
    {
        $limit = $request->get('limit');
        $limit_count = 50;
        $from = $request->get('from');
        $to = $request->get('to');

        if ($limit) {
            $limit_count = $limit;
        }
        if (!$from)
            $from = date('Y-m-d');
        if (!$to)
            $to = date('Y-m-d');

        $transport = User::query()->select('id', 'fullname')->where(['filial_id' => Auth::user()->filial_id, 'role' => 'transport'])->get();
        $all = [];
        foreach ($transport as $item) {
            $order = Order::query();
            $order = $order->where(['order_filial_id' => Auth::user()->filial_id])
                ->whereDate('order_date', '>=', $from)
                ->whereDate('order_date', '<=', $to);
            $all[] = [
                'id' => $item->id,
                'fullname' => $item->fullname,
                'count' => $order->count(),
            ];
        }
        return $all;
    }

    public function mahsulot_by_transport(Request  $request)
    {
        $limit = $request->get('limit');
        $transport_id = $request->input('transport_id');
        $limit_count = 50;
        $from = $request->get('from');
        $to = $request->get('to');

        if (!$limit)
            $limit = 50;
        if (!$from)
            $from = date('Y-m-d');
        if (!$to)
            $to = date('Y-m-d');

        $orders = Order::query()
            ->select('order_id', 'costumer_id')
            ->where(['order_filial_id' => Auth::user()->filial_id, 'order_driver' => $transport_id])
            ->whereDate('order_date', '>=', $from)
            ->whereDate('order_date', '<=', $to)
            ->with('custumer:id,costumer_name')
            ->paginate($limit);
        $all = [];
        foreach ($orders as $order) {
            $alc =  Clean::query()
                ->select('order_id', 'clean_product')
                ->where(['order_id' => $order->order_id])
                ->with('xizmat:xizmat_id,xizmat_turi')->get();
            foreach ($alc as $item) {
                $all[] = [
                    'costumer' => $order->custumer,
                    'product' => $item
                ];
            }
        }
        return $all;
    }

    public function bekor(Request $request)
    {
        $limit = $request->get('limit');
        $limit_count = 50;
        $from = $request->get('from');
        $to = $request->get('to');

        if ($limit) {
            $limit_count = $limit;
        }
        if (!$from)
            $from = date('Y-m-d');
        if (!$to)
            $to = date('Y-m-d');

        $order = Order::query();

        $order = $order
            ->where(['order_filial_id' => Auth::user()->filial_id, 'order_status' => 'bekor qilindi'])
            ->whereDate('order_date', '>=', $from)
            ->whereDate('order_date', '<=', $to)
            ->with('transport:id,fullname')
            ->with('custumer')
            ->paginate($limit_count);

        return $order;
    }
    public function olibkelindi(Request $request)
    {
        $limit = $request->get('limit');
        $limit_count = 50;
        $from = $request->get('from');
        $to = $request->get('to');

        if ($limit) {
            $limit_count = $limit;
        }
        if (!$from)
            $from = date('Y-m-d');
        if (!$to)
            $to = date('Y-m-d');

        if (Auth::user()->role == 'yuvish' || Auth::user()->role == 'transport' || Auth::user()->role == 'tayorlov' || Auth::user()->role == 'saygak' | Auth::user()->role == 'operator ') {
            $users = User::where(['filial_id' => Auth::user()->filial_id, 'id' => Auth::id()])->first();
            $orders = Order::query();
            $orders = $orders->where(['order_filial_id' => Auth::user()->filial_id])
                ->where('order_status', '!=', 'keltirish')
                ->where('order_status', '!=', 'bekor qilindi')
                ->whereDate('olibk_sana', '>=', $from)
                ->whereDate('olibk_sana', '<=', $to)
                ->with('custumer')
                ->paginate($limit_count);
            $all = [];
            foreach ($orders as $order) {
                $all[] = $order;
            }
            return ['user' => $users->fullname, 'orders' => $all];
        } else
            $users = User::where(['filial_id' => Auth::user()->filial_id]);
    }
    public function qabul($id, Request $request)
    {
        $limit = $request->get('limit');
        $limit_count = 50;
        $from = $request->get('from');
        $to = $request->get('to');

        if (!$limit) {
            $limit_count = $limit;
        }
        if (!$from)
            $from = date('Y-m-d');
        if (!$to)
            $to = date('Y-m-d');

        $orders = Order::query();
        $orders = $orders->where(['order_filial_id' => Auth::user()->filial_id])
            ->where('order_status', '!=', 'bekor qilindi')
            ->where('operator_id', '=', Auth::id())
            ->whereDate('order_date', '>=', $from)
            ->whereDate('order_date', '<=', $to)
            ->paginate($limit_count);

        return $orders;
    }

    public function mijoz(Request $request)
    {
        $from = $request->get('from');
        $to = $request->get('to');
        $limit = $request->get('limit');
        $limit_count = 50;

        if (!$limit)
            $limit_count = $limit;

        if (!$from)
            $from = date('Y-m-d');
        if (!$to)
            $to = date('Y-m-d');

        $costumers = Costumers::query();
        $costumers = $costumers->where(['costumers_filial_id' => Auth::user()->filial_id])
            ->whereDate('costumer_date', '>=', $from)
            ->whereDate('costumer_date', '<=', $to)
            ->paginate($limit_count);
        return $costumers;
    }
    public function buyurtma(Request $request)
    {
        $from = $request->get('from');
        $to = $request->get('to');
        $limit = $request->get('limit');
        $limit_count = 50;
        if (!$limit)
            $limit_count = $limit;
        if (!$from)
            $from = date('Y-m-d');
        if (!$to)
            $to =  date('Y-m-d');

        $order = Order::query();
        $order = $order->where(['order_filia_id' => Auth::user()->filial_id])
            ->where('order_status', '!=', 'bekor qilindi')
            ->whereDate('order_date', '>=', $from)
            ->whereDate('order_date', '<=', $to)
            ->paginate($limit_count);

        return $order;
    }
    public function nasiya(Request $request)
    {
        $from = $request->get('from');
        $to = $request->get('to');
        $limit = $request->get('limit');
        $limit_count = 50;
        if (!$limit)
            $limit_count = $limit;

        if (!$from)
            $from = date('Y-m-d');
        if (!$to)
            $to = date('Y-m-d');

        $nasiya = Nasiya::query();
        $nasiya = $nasiya->where(['filial_id' => Auth::user()->filial_id, 'user_id' => Auth::id()])
            ->whereDate('date', '>=', $from)
            ->where('date', '<=', $to)
            ->paginate($limit_count);
    }
    public function kpi(Request $request)
    {
        $limit = $request->get('limit');
        $from = $request->get('from');
        $to = $request->get('to');

        if (!$limit)
            $limit = 50;
        if (!$to)
            $to = date('Y-m-d');
        if (!$from)
            $from = date('Y-m-d');

        $kpi = KpiHisob::query();

        $kpi = $kpi->where(['filial_id' => Auth::user()->filial_id])
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to);
        $all = ['data' => []];

        if (Auth::user()->role == 'yuvish' || Auth::user()->role == 'transport' || Auth::user()->role == 'operator' || Auth::user()->role == 'tayorlov') {
            $kpi = $kpi
                ->where(['user_id' => Auth::id()])
                ->with('user');
            $sum = clone  $kpi;
            $sum = $sum->sum('summa');
        }

        foreach ($kpi->paginate($limit) as $item) {
            $all['data'][] = $item;
        }
        $all['jami'] = (int)$sum;

        return $all;
    }
    public  function maosh(Request  $request)
    {
        $limit = $request->get('limit');
        $from = $request->get('from');
        $to = $request->get('to');

        if (!$limit)
            $limit = 50;
        if (!$to)
            $to = date('Y-m-d');
        if (!$from)
            $from = date('Y-m-d');

        $the_user = Auth::user();

        $davomat = Davomat::query()
            ->where(['filial_id' => Auth::user()->filial_id])
            ->whereDate('sana', '>=', $from)
            ->whereDate('sana', '<=', $to)->with('user:id,fullname');
        if ($the_user->role == 'yuvish' || $the_user->role == 'transport' || $the_user->role == 'operator' || $the_user->role == 'tayorlov') {
            $davomat = $davomat->where(['user_id' => Auth::id()]);
        }
        $sum = clone $davomat;
        $data =  $davomat->paginate($limit)->toArray();
        $data['jami'] = (int)$sum->sum('maosh');
        return $data;
    }
}
