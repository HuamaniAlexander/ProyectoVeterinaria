<?php
class Response {
    public static function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    public static function success($data = [], $message = 'Operación exitosa') {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }
    
    public static function error($message = 'Error en la operación', $status = 400) {
        self::json([
            'success' => false,
            'message' => $message
        ], $status);
    }
}