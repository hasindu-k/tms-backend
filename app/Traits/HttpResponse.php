<?php

namespace App\Traits;

trait HttpResponse
{
    protected function success($data, $massage = null, $code = 200)
    {
        return response()->json([
            'status' => 'Request was successful.',
            'massage' => $massage,
            'data' => $data
        ], $code);
    }

    protected function error($data, $code, $massage = null)
    {
        return response()->json([
            'status' => 'Error has occurred.',
            'massage' => $massage,
            'data' => $data
        ], $code);
    }
}
