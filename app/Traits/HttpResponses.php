<?php

namespace App\Traits;

trait HttpResponses {

    protected function success($data, $message = null, $code = 200)
    {
        return response()->son([
            'status' => 'Request was succesful',
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function error($data, $message = null, $code)
    {
        return response()->son([
            'status' => 'Error has occured...',
            'message' => $message,
            'data' => $data,
        ], $code);
    }
}