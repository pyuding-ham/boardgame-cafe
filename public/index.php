<?php
declare(strict_types = 1);

include '../src/bootstrap.php';

$raw_uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$path = mb_strtolower($raw_uri, 'UTF-8');
$path = substr($path, strlen(DOC_ROOT));
$path = trim($path, '/'); 
$parts = explode('/', $path);

if ($parts[0] != 'admin') {
    $page = $parts[0] ? str_replace('.php', '', $parts[0]) : 'index';
    $urlParam = $parts[1] ?? null;

    if ($page === 'board') {
        // 기본 게시판 설정
        $boardName = $urlParam ?? 'notice';
        
        // 기본값은 1페이지
        $currentPage = 1;
        // 일반 게시판용 숫자 ID
        $articleId = null;
        // 보드게임용 영문 ID (슬러그)
        $articleSlug = null;
        // 기본값은 목록 보기
        $boardAction = 'list';

        // page/view/write
        $actionKeyword = $parts[2] ?? null;

        // 1. 값이 없으면 게시판 목록
        if ($actionKeyword === null) {
            $boardAction = 'list';
        // 2. 게시판 목록 페이징 주소
        } elseif ($actionKeyword === 'page') {
            $boardAction = 'list';
            $currentPage = isset($parts[3]) ? (int)$parts[3] : 1;
        // 3. 게시글 상세
        } elseif ($actionKeyword === 'view') {
            $boardAction = 'view';
            $identifier = $parts[3] ?? null;
            
            // 숫자가 들어오면 ID, 문자가 들어오면 영문 슬러그로 저장
            if (is_numeric($identifier)) {
                $articleId = (int)$identifier;
            } else {
                $articleSlug = $identifier; 
            }
        // 4. 게시글 작성
        } elseif ($actionKeyword === 'write') {
            $boardAction = 'write';
        }
    }
} else {
    $admin_page = isset($parts[1]) ? str_replace('.php', '', $parts[1]) : '';
    $page = 'admin/' . $admin_page;
}

$php_page = APP_ROOT . '/src/pages/' . $page . '.php';

if (!file_exists($php_page)) {
    $php_page = APP_ROOT . '/src/pages/page-not-found.php';
}

include $php_page;