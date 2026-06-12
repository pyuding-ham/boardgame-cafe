<?php
function redirect(string $location, array $parameters = [], int $response_code = 302): void
{
    // 매개변수가 있으면 쿼리 스트링 생성
    $qs = $parameters ? '?' . http_build_query($parameters) : '';
    $location = $location . $qs;

    // 외부 URL (http:// 또는 https://)이 아닌 경우에만 앞에 DOT_ROOT 추가
    if (!preg_match('/^https?:\/\//i', $location)) {
        $location = DOC_ROOT . ltrim($location, '/');
    }

    header('Location: ' . $location, true, $response_code);
    exit;
}