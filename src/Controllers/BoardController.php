<?php
declare(strict_types = 1);

namespace BoardgameCafe\Controllers;

class BoardController {
    private $cms;

    public function __construct($cms) {
        $this->cms = $cms;
    }

    /**
     * 게시판 목록
     * 
     * @param int $page 현재 페이지 번호
     * @param string $boardName 게시판 식별자 이름
     * @return array 템플릿 렌더링용 연관 배열
     */
    public function index(int $page = 1, string $boardName = 'notice'): array {
        // 1. PRG 패턴 적용 (검색 처리)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 게시판 이름 별로 세션 키를 분리
            $_SESSION[$boardName . '_search_kw'] = trim($_POST['keyword'] ?? '');
            $_SESSION[$boardName . '_search_type'] = trim($_POST['search_type'] ?? 'all');
            
            // 리다이렉트
            header('Location: ' . DOC_ROOT . 'board/' . $boardName);
            exit;
        }
        
        // 2. 세션에서 해당 게시판의 검색어 가져오기
        $keyword = $_SESSION[$boardName . '_search_kw'] ?? '';
        $search_type = $_SESSION[$boardName . '_search_type'] ?? 'all';
        
        $filters = [
            'keyword' => $keyword,
            'type' => $search_type,
        ];

        // 3. 페이징 설정
        $per_page = 10; 
        $offset = ($page - 1) * $per_page;

        $board_service = $this->cms->getBoard();
        
        // 4. 게시판 이름에 따라 다른 서비스 메서드 호출
        if ($boardName === 'boardgame') {
            $list = $board_service->getBoardgameList($per_page, $offset, $filters);
            $total_count = $board_service->getBoardgameTotalCount($filters);
        } else {
            // 기본값은 공지사항 게시판
            $list = $board_service->getNoticeList($per_page, $offset, $filters);
            $total_count = $board_service->getNoticeTotalCount($filters);
        }

        $total_pages = (int)ceil($total_count / $per_page);

        // 한글 타이틀 매핑
        $boardTitles = [
            'notice' => '공지사항',
            'boardgame' => '보드게임 소개',
        ];

        return [
            'list' => $list,
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_count' => $total_count,
            'keyword' => $keyword,
            'search_type' => $search_type,
            'board_name' => $boardName,
            'board_title' => $boardTitles[$boardName] ?? '게시판',
            'session' => $_SESSION,
        ];
    }

    /**
     * 게시글 상세
     * 
     * @param string|int $identifier 숫자 ID 또는 영문 슬러그
     * @param string $boardName 게시판 식별자 이름
     * @return array|false 게시글 데이터 배열 또는 실패 시 false
     */
    public function view(string|int $identifier, string $boardName = 'notice'): array|false {
        $board_service = $this->cms->getBoard();

        // 게시판 이름에 따라 다른 상세 보기 데이터 호출
        if ($boardName === 'boardgame') {
            $article = $board_service->getBoardgameArticleBySlug((string)$identifier);
        } else {
            // 기본값은 공지사항 게시판
            $article = $board_service->getNoticeArticle((int)$identifier);
        }

        // 게시글이 존재하지 않으면 false 반환
        if (!$article) {
            return false;
        }

        // 한글 타이틀 매핑
        $boardTitles = [
            'notice' => '공지사항',
            'boardgame' => '보드게임 소개',
        ];

        return [
            'article' => $article,
            'board_name' => $boardName,
            'board_title' => $boardTitles[$boardName] ?? '게시판',
        ];
    }
}
