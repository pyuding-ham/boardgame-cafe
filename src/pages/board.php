<?php
declare(strict_types = 1);

use BoardgameCafe\Controllers\BoardController;
use BoardgameCafe\Controllers\SiteMenuController;

// 글쓰기에서 보낸 상태 저장
$status = $_SESSION['_flash_status'] ?? null;
if ($status) {
    unset($_SESSION['_flash_status']);
}

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
    $siteMenuController = new SiteMenuController($cms);

    $board_title = [
        'board_title' => $siteMenuController->getMenuTitleByPageCode($boardName)
    ];

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

        $data = array_merge($board_title, $data);

        // 템플릿 렌더링
        echo $twig->render($boardName . '-view.html', $data);
    }
    // 2. 게시글 작성
    elseif ($boardAction === 'write') {
        // 공지사항인데 관리자가 아닌 경우 접근 차단 (GET, POST 공통)
        if ($boardName === 'notice' && ($_SESSION['role'] ?? '') !== 'ADMIN') {
            // 로그인으로 리다이렉트
            redirect("login/", [
                'status' => 'access_denied'
            ]);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$currentUserId) {
                // 로그인으로 리다이렉트
                redirect("login/", [
                    'status' => 'login_required'
                ]);
                exit;
            }

            $result = $boardController->write($boardName, $_POST, $_FILES, (int)$currentUserId);
            
            if ($result['success']) {
                redirect("board/{$boardName}", [
                    'status' => 'write_success'
                ]);
                exit;
            } else {
                $data['errors']  = $result['errors'];
                $data['article'] = $result['article'];
            }
        } 
        // 최초 글쓰기 페이지 진입 (GET)
        else {
            $data['errors']  = [];
            $data['article'] = [
                'title' => '',
                'content' => '',
                'is_pinned' => 0
            ];
        }

        $data = array_merge($board_title, $data);

        // 템플릿 렌더링
        echo $twig->render($boardName . '-write.html', $data);
        exit;
    }
    // 3. 게시판 목록
    else {
        $data = $boardController->index($currentPage, $boardName);
        $data['status'] = $status;
        $data = array_merge($board_title, $data);
        
        // 템플릿 렌더링
        echo $twig->render($boardName . '-list.html', $data);
    }
} else {
    header('Location: ' . DOC_ROOT . 'page-not-found');
    exit;
}