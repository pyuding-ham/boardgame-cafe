<?php
declare(strict_types = 1);

use BoardgameCafe\Controllers\BoardController;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($articleId)) {
    // 단순 새로고침 시 검색어가 지워지는 것을 방지하기 위한 변수
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    // 다른 페이지에서 해당 게시판으로 새로 들어온 경우에만 검색어 초기화
    if (!str_contains($referer, 'board/notice')) {
        unset($_SESSION['notice_search_kw']);
        unset($_SESSION['notice_search_type']);
    }
}

// 게시판 목록
$allowed_boards = [
    'notice',
];

if (in_array($boardName, $allowed_boards)) {
    $boardController = new BoardController($cms);

    // 1. 게시글 상세
    if ($boardAction === 'view') {
        // 보드게임 소개 게시판은 슬러그, 그 외는 ID로 조회
        $identifier = ($boardName === 'boardgame') ? $articleSlug : $articleId;

        if (!$identifier) {
            header('Location: ' . DOC_ROOT . 'page-not-found');
            exit;
        }

        $data = $boardController->view($identifier, $boardName);
        
        if (!$data) {
            header('Location: ' . DOC_ROOT . 'page-not-found');
            exit;
        }

        // 템플릿 렌더링
        echo $twig->render($boardName . '-view.html', $data);
    } 
    // 2. 게시글 작성
    elseif ($boardAction === 'write') {
        $data = method_exists($boardController, 'write') ? $boardController->write() : [];
        echo $twig->render($boardName . '-write.html', $data);
    } 
    // 3. 게시판 목록
    else {
        $data = $boardController->index($currentPage, $boardName);
        
        // 템플릿 렌더링
        echo $twig->render($boardName . '-list.html', $data);
    }
} else {
    header('Location: ' . DOC_ROOT . 'page-not-found');
    exit;
}