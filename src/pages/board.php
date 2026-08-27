<?php
declare(strict_types = 1);

use BoardgameCafe\Controllers\BoardController;
use BoardgameCafe\Controllers\SiteMenuController;
use BoardgameCafe\Exceptions\NotFoundException;
use BoardgameCafe\Exceptions\PostNotFoundException;
use BoardgameCafe\Exceptions\AuthorizationException;
use BoardgameCafe\Exceptions\AuthenticationException;
use BoardgameCafe\Exceptions\ErrorCode;

// 글쓰기에서 보낸 상태 저장
$status = $_SESSION['_flash_status'] ?? null;
if ($status) {
    unset($_SESSION['_flash_status']);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($postId)) {
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
    'boardgame',
    'branch',
    'notice',
];

if (in_array($boardName, $allowed_boards)) {
    $boardController = new BoardController($cms);
    $siteMenuController = new SiteMenuController($cms);

    $data = [];

    $data = array_merge($data, [
        // 메뉴 아이디
        'board_id' => $siteMenuController->getMenuIdByPageCode($boardName),
        // 메뉴 코드
        'board_name'  => $boardName,
        // 메뉴 이름
        'board_title' => $siteMenuController->getMenuTitleByPageCode($boardName),
    ]);
    
    // 1. 게시글 상세
    if ($boardAction === 'view') {
        // 보드게임 소개 게시판은 슬러그, 그 외는 ID로 조회
        $identifier = ($boardName === 'boardgame') ? $postSlug : $postId;

        if (!$identifier) {
            throw new PostNotFoundException(ErrorCode::POST_NOT_FOUND_READ->value);
        }

        $data = array_merge($data, $boardController->view($identifier, $boardName, (int)$currentUserId));
        
        if (!$data) {
            throw new PostNotFoundException(ErrorCode::POST_NOT_FOUND_READ->value);
        }

        // 템플릿 렌더링
        echo $twig->render($boardName . '-view.html', $data);
    }
    // 2. 게시글 작성
    elseif ($boardAction === 'write') {
        // 로그인 여부 확인
        if (!$currentUserId) {
            throw new AuthenticationException();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 게임소개
            if ($boardName === 'boardgame') {
                $result = $boardController->insertBoardgame($boardName, $_POST, $_FILES, (int)$currentUserId);
            } else {
                $result = $boardController->insert($boardName, $_POST, $_FILES, (int)$currentUserId);
            }
            
            if ($result['success']) {
                redirect("board/{$boardName}", [
                    'status' => 'write_success'
                ]);
                exit;
            } else {
                // 게임소개
                if (($boardName === 'boardgame')) {
                    $result['post'] = array_merge(
                        $result['post'] ?? [],
                        $boardController->getWriteForm($data['board_id'])
                    );
                }
                    
                $data['errors'] = $result['errors'];
                $data['post']   = $result['post'];
            }
        } 
        // 최초 글쓰기 페이지 진입 (GET)
        else {
            // 게임소개, 지점소개, 공지사항 게시판일 때 관리자 여부 체크
            if (($boardName === 'boardgame' || $boardName === 'branch' || $boardName === 'notice') && !$boardController->canWritePost((int)$currentUserId, $boardName)) {
                throw new AuthorizationException(ErrorCode::ACCESS_DENIED->value);
            }

            // 게임소개
            if (($boardName === 'boardgame')) {
                $result = $boardController->getWriteForm($data['board_id']);

                $data['post'] = [
                    'title' => '',
                    'category' => $result['category'] ?? null,
                    'level' => $result['level'] ?? null,
                    'content' => '',
                ];
            }
            // 그 이외 게시판
            else {
                $data['post'] = [
                    'title' => '',
                    'content' => '',
                ];
            }

            $data['errors']  = [];
        }

        // 템플릿 렌더링
        echo $twig->render($boardName . '-write.html', $data);
        exit;
    }
    // 3. 게시글 수정
    elseif ($boardAction === 'edit') {
        // 로그인 여부 확인
        if (!$currentUserId) {
            throw new AuthenticationException();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 게임소개
            if ($boardName === 'boardgame') {
                $result = $boardController->updateBoardgame($boardName, $postId, $_POST, $_FILES, (int)$currentUserId);
            } else {
                $result = $boardController->update($boardName, $postId, $_POST, $_FILES, (int)$currentUserId);
            }
            
            if ($result['success']) {
                redirect("board/{$boardName}", [
                    'status' => 'update_success'
                ]);
                exit;
            } else {
                $data['errors']  = $result['errors'];

                $editData = $boardController->edit($identifier, $boardName, (int)$currentUserId);

                $data['post'] = array_merge(
                    $result['post'] ?? [],
                    [
                        'files' => $editData['post']['files'] ?? [],
                        'images' => $editData['post']['images'] ?? []
                    ]
                );

                // 게임소개
                if (($boardName === 'boardgame')) {
                    $data['post'] = array_merge(
                        $data['post'] ?? [],
                        $boardController->getEditForm($data['board_id'])
                    );
                }
            }
        } 
        // 최초 수정 페이지 진입 (GET)
        else {
            // 게임소개
            if (($boardName === 'boardgame')) {
                $editForm = $boardController->getEditForm($data['board_id']);
                $editResult = $boardController->edit($identifier, $boardName, (int)$currentUserId);

                $data['post'] = [
                    'category' => $editForm['category'] ?? null,
                    'level' => $editForm['level'] ?? null,
                    ...($editResult['post'] ?? []),
                ];

            }
            // 그 이외 게시판
            else {
                $result = $boardController->edit($identifier, $boardName, (int)$currentUserId);
                
                $data['post'] = $result['post'];
            }

            $data['errors'] = [];
        }

        // 템플릿 렌더링
        echo $twig->render($boardName . '-edit.html', $data);
        exit;
    }
    // 4. 게시글 삭제
    elseif ($boardAction === 'delete') {
        // 로그인 여부 확인
        if (!$currentUserId) {
            throw new AuthenticationException();
        }

        $boardController->delete($postId, $boardName, (int)$currentUserId);

        redirect("board/{$boardName}", [
            'status' => 'delete_success',
        ]);
        exit;
    }
    // 5. 게시판 목록
    else {
        $data = array_merge($data, $boardController->index($currentPage, $boardName, $data['board_id']));
        $data['status'] = $status;
        
        // 템플릿 렌더링
        echo $twig->render($boardName . '-list.html', $data);
    }
} else {
    throw new NotFoundException(ErrorCode::BOARD_NOT_FOUND->value);
}
