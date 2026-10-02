<?php

namespace App\Traits;

trait ApiResponse
{
    // رسالة النجاح
    public function successResponse($data = null, $message = null, $code = 200)
    {
        return response()->json([
            'status' => 'success',
            'message' => $message ?? __('messages.success'),
            'data' => $data
        ], $code);
    }
    public function successMessage($message = null, $code = 200)
    {
        return response()->json([
            'status' => 'success',
            'message' => $message ?? __('messages.success'),
        ], $code);
    }
    // رسالة الفشل / الخطأ
    public function errorResponse($message = null, $code = 400, $errors = null)
    {
        $response = [
            'status' => 'error',
            'message' => $message ?? __('messages.error'),
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }


    public function codeSentResponse($message = null)
    {
        return response()->json([
            'status' => 'success',
            'message' => $message ?? __('messages.otp_sent_successfully'),
        ], 200);
    }
}
