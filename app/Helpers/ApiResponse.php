<?php

namespace App\Helpers;



class ApiResponse
{
    public static function success($data = null, $message = 'OK')
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], 200);
    }

    public static function created($data = null, $message = 'Created')
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], 201);
    }

    public static function deleted($message = 'Deleted')
    {
        return response()->json([
            'success' => true,
            'message' => $message
        ], 200);
    }

    public static function error($message = 'Error', $code = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], $code);
    }
}