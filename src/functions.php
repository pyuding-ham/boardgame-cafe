<?php
function redirect(string $location, array $parameters = [], int $response_code = 302): void
{
    // 매개변수(메시지 등)가 있다면 세션 플래시 데이터로 저장
    if (!empty($parameters)) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        foreach ($parameters as $key => $value) {
            $_SESSION['_flash_' . $key] = $value;
        }
    }

    // 외부 URL (http:// 또는 https://)이 아닌 경우 DOT_ROOT 추가
    if (!preg_match('/^https?:\/\//i', $location)) {
        $location = DOC_ROOT . ltrim($location, '/');
    }

    header('Location: ' . $location, true, $response_code);
    exit;
}