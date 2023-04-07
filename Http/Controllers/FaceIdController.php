<?php

namespace App\Http\Controllers;

use App\Models\Davomat2;
use App\Models\Fingerprint;
use App\Models\User;
use Carbon\Exceptions\Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage as FacadesStorage;
use Illuminate\Support\Str;

class FaceIdController extends Controller
{

    public function index(Request $request)
    {
        $event_log = $request->all();

        $hasUser = Schema::hasColumns('user', ['to_time', 'from_time']);

        if ($event_log) {
            // FacadesStorage::put('111111111111.txt' . Str::uuid(), $event_log);
            FacadesStorage::put('33333.txt' . Str::uuid(), $event_log);
            try {
                $ac_event = $event_log['AccessControllerEvent'];
                if ($ac_event) {
                    if ($ac_event['employeeNoString']) {

                        $employee = User::find($ac_event['employeeNoString']);

                        if ($employee) {

                            $fing = Fingerprint::query()->where('token', $ac_event['deviceName'])->first();

                            if ($fing) {

                                $u_id = $employee->id;
                                $type = 'any';
                                $fng_id = $fing->id;
                                $f_id = $fing->filial_id;

                                $false = 0;

                                $last_davomat = Davomat2::query()->where([
                                    'user_id' => $u_id,
                                    'filial_id' => $f_id
                                ])->orderBy('id',  'desc')->first();

                                $user = User::find($u_id);

                                $maosh = 0;
                                $dav_type = 'entry';

                                if ($type == 'any') {

                                    if ($last_davomat) {
                                        if ($last_davomat->type == 'entry') {

                                            $dav_type = 'exit';

                                            if ($user->maosh > 0) {

                                                if ($hasUser) {
                                                    $daily_hours = (strtotime($user->to_time) - strtotime($user->from_time)) / 3600;
                                                } else {
                                                    $daily_hours = 1;
                                                }

                                                $hourly_salary = $user->maosh / (date('t') - 2) / $daily_hours;

                                                $ishladiSoat = (strtotime(date('Y-m-d H:i:s')) - strtotime($last_davomat->sanavaqt)) / 3600;
                                                $maosh = round($hourly_salary * $ishladiSoat);

                                                if ($maosh > 0) {
                                                    $user->oylik += $maosh;
                                                    $user->save();
                                                }
                                            }
                                        }
                                    }
                                } elseif ($type == 'only_entry') {

                                    $last_davomat = Davomat2::query()->where([
                                        'user_id' => $u_id,
                                        'filial_id' => $f_id
                                    ])->orderBy('id',  'desc')->first();

                                    if ($last_davomat && $last_davomat->type == 'entry') {
                                        $false++;
                                    }
                                } elseif ($type == 'only_exit') {

                                    $last_davomat = Davomat2::query()->where([
                                        'user_id' => $u_id,
                                        'filial_id' => $f_id
                                    ])->orderBy('id',  'desc')->first();


                                    if ($last_davomat) {
                                        if ($last_davomat->type !== 'entry') {
                                            $false++;
                                        } else {

                                            $dav_type = 'exit';

                                            if ($user->maosh > 0) {

                                                if ($hasUser) {
                                                    $daily_hours = (strtotime($user->to_time) - strtotime($user->from_time)) / 3600;
                                                } else {
                                                    $daily_hours = 1;
                                                }
                                                $hourly_salary = $user->maosh / (date('t') - 2) /  $daily_hours;

                                                $ishladiSoat = (strtotime(date('Y-m-d H:i:s')) - strtotime($last_davomat->sanavaqt)) / 3600;
                                                $maosh = round($hourly_salary * $ishladiSoat);

                                                if ($maosh > 0) {
                                                    $user->oylik += $maosh;
                                                    $user->save();
                                                }
                                            }
                                        }
                                    } else {
                                        $false++;
                                    }
                                }

                                if ($false == 0) {
                                    $new_dav = new Davomat2();
                                    $new_dav->user_id = $u_id;
                                    $new_dav->type = $dav_type;
                                    $new_dav->filial_id = $f_id;
                                    $new_dav->maosh = $maosh;
                                    $new_dav->sanavaqt = $event_log['dateTime'];
                                    $new_dav->fingerprint_id = $fng_id;

                                    if ($new_dav->save()) {

                                        // foreach ($new_dav->user->telechat as $tele_chat) {

                                        //     if ($new_dav->type == 'entry') {
                                        //         $text = '🟢 Kirgan vaqt:' . date("H:i");
                                        //     } else {
                                        //         $text = '🔴 Chiqqan vaqt:' . date("H:i") . "  " . 'Hisoblandi: ' . number_format($maosh) . ' so`m';
                                        //     }

                                        //     Yii::$app->telegram->sendMessage([
                                        //         'chat_id' => $tele_chat->chat_id,
                                        //         "text" => $text
                                        //     ]);
                                        // }

                                        if ($new_dav) {
                                            if ($new_dav->type == 'entry') {
                                                $text = '🟢 Kirgan vaqt:' . date("H:i");
                                            } else {
                                                $text = '🔴 Chiqqan vaqt:' . date("H:i") . "  " . 'Hisoblandi: ' . number_format($maosh) . ' so`m';
                                            }
                                            return response([
                                                'message' => $text
                                            ]);
                                        }
                                    }
                                }
                            }
                            return response([
                                'message' => 'Ma`lumotlar bazasida bunday qurilma topilmadi.'
                            ], 422);
                        } else {
                            return response([
                                'message' => 'User yioq.'
                            ], 422);
                        }
                    }
                } else {
                    return response([
                        'message' => 'Succwervaelkbsbkonsbtjosbjosnejobgnjonjoswess'
                    ]);
                }
            } catch (Exception $e) {
                return response([
                    'message' => 'Success'
                ]);
            }
        } else {
            dd(';ererv');
        }
    }
}







        // move_uploaded_file($_FILES['Picture']["tmp_name"], Yii::$app->security->generateRandomString(6).'.jpeg');

        // [
        //     'event_log' => [
        //         "ipAddress" =>    "192.168.1.8",
        //         "portNo" =>    443,
        //         "protocol" =>    "HTTPS",
        //         "macAddress" =>    "ac:b9:2f:fe:06:69",
        //         "channelID" =>    1,
        //         "dateTime" =>    "2022-12-06T23:20:10+05:00",
        //         "activePostCount" =>    1,
        //         "eventType" =>    "AccessControllerEvent",
        //         "eventState" =>    "active",
        //         "eventDescription" =>    "Access Controller Event",
        //         "deviceID" =>    "hik2004",
        //         "AccessControllerEvent" =>    [
        //             "deviceName" =>    "Access Controller",
        //             "majorEventType" =>    5,
        //             "subEventType" =>    75,
        //             "name" =>    "Filial Admin",
        //             "cardReaderKind" =>    1,
        //             "cardReaderNo" =>    1,
        //             "verifyNo" =>    198,
        //             "employeeNoString" =>    "2",
        //             "serialNo" =>    1358,
        //             "userType" =>    "normal",
        //             "currentVerifyMode" =>    "cardOrFaceOrFp",
        //             "frontSerialNo" =>    1357,
        //             "attendanceStatus" =>    "undefined",
        //             "label" =>    "",
        //             "statusValue" =>    0,
        //             "mask" =>    "no",
        //             "picturesNumber" =>    1,
        //             "purePwdVerifyEnable" =>    true
        //         ]
        //     ]
        // ];
