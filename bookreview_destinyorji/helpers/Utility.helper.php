<?php
class UtilityHelper
{
    public function validateFields($input, $required)
    {
        $data = [];
        $missing = [];

        foreach ($required as $field) {
            if (!isset($input[$field]) || trim($input[$field]) === '') {
                $missing[] = $field;
            } else {
                $data[$field] = trim($input[$field]);
            }
        }

        if (count($missing) > 0) {
            return [
                'error' => true,
                'error_msg' => 'Missing: ' . implode(', ', $missing)
            ];
        }

        return [
            'error' => false,
            'data' => $data
        ];
    }

    public function jsonResponse($status, $message, $data = [])
    {
        http_response_code($status);
        return [
            'statuscode' => $status,
            'status' => $message,
            'data' => $data
        ];
    }
}
