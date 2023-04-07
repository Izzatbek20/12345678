<?php

namespace App\Http\Controllers;

use App\Models\Fingerprint;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FingerprintController extends Controller
{
    public function index(Request $request)
    {
        return response(Fingerprint::all());
    }
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string'
        ], [
            'name.required' => '"name" to`ldirilishi shart',
            'name.string' => '"name" string bo`lishi kerak',
        ]);

        $model = Fingerprint::create([
            'name' => $request->name,
            'token' => Str::uuid(),
            'filial_id' => auth()->user()->filial_id,
            'date' => now()->format('Y-m-d H:i:s'),
        ]);
        if ($model) {
            return response([
                'message' => 'Ma`lumotlar qo`shildi.'
            ]);
        } else {
            return response([
                'message' => 'Ma`lumotlarni qo`shishda xatolik yuz berdi.'
            ], 422);
        }
    }
}
