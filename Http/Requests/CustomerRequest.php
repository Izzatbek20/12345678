<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class CustomerRequest extends FormRequest
{
    public function rules()
    {
        return [
            "costumer_name" => "string",
            "costumer_phone_1" => "required|string",
            "costumer_phone_2" => "string",
            "costumer_addres" => "string",
            "costumer_source" => "string",
            "costumer_turi" => "string",
            "millat_id" => "integer",
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([

            'success'   => false,
            'message'   => 'Tasdiqlash xatolari',
            'data'      => $validator->errors()

        ]));
    }

    public function messages()
    {
        return [
            'costumer_phone_1.required' => 'Telefon to`ldirilishi kerak',
        ];
    }
}
