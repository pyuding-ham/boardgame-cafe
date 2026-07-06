<?php
declare(strict_types = 1);

namespace BoardgameCafe\Controllers;

use BoardgameCafe\Validate\Validate;

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
            $start_num = $total_count - $offset;

            // 글 번호 가공
            foreach ($list as &$article) {
                if ($article['is_pinned'] == 1) {
                    // 상단 고정 글(공지)은 번호 자리를 비워둠
                    $article['board_no'] = null; 
                    // 번호가 뜨지 않도록 마이너스 처리
                    $start_num--; 
                } else {
                    // 상단 고정 글이 아닌 글
                    $article['board_no'] = $start_num;
                    $start_num--; 
                }
            }
            unset($article);
        }

        $total_pages = (int)ceil($total_count / $per_page);

        return [
            'list' => $list,
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_count' => $total_count,
            'keyword' => $keyword,
            'search_type' => $search_type,
            'board_name' => $boardName,
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

        return [
            'article' => $article,
            'board_name' => $boardName,
        ];
    }

    /**
     * 기본형 게시글 쓰기
     */
    public function writeBasic(string $boardName, array $postData, array $fileData, int $userId): array
    {
        $title     = trim($postData['title'] ?? '');
        $content   = trim($postData['content'] ?? '');
        $is_pinned = isset($postData['is_pinned']) ? 1 : 0;
        $errors    = [];

        // 게시글 내용 글자 수 카운트 변수
        $decodedForLength = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $pureTextForLength = strip_tags($decodedForLength);
        $cleanContentForLength = preg_replace('/[\s\x{00a0}\x{200b}]+/u', '', $pureTextForLength);
        // 순수 글자 수
        $realTextLength = mb_strlen($cleanContentForLength, 'UTF-8');
        // HTML 태그를 포함한 용량
        $htmlByteLength = strlen($content);

        // 1. 제목 필수 입력 값 검사
        if (empty($title)) {
            $errors['title'] = '제목을 입력해 주세요.';
        }
        // 2. 제목 글자 수 검사 (최대 100자)
        if (empty($errors['title']) && !Validate::isText($title, 1, 100)) {
            $errors['title'] = '제목은 최대 100자까지 입력할 수 있습니다.';
        }
        
        if (empty($errors['content'])) {
            // 3. 내용 필수 입력 값 검사
            if ($realTextLength === 0 || empty($cleanContentForLength)) {
                $errors['content'] = '내용을 입력해주세요.';
            } 
            // 4. 내용 글자 수 검사
            elseif ($realTextLength > 5000) {
                $errors['content'] = '본문 내용은 최대 5,000자까지 입력 가능합니다. (현재 ' . number_format($realTextLength) . '자)';
            } 
            // 5. 과도한 HTML 태그 서식 입력 방지
            elseif ($htmlByteLength > 50000) {
                $errors['content'] = '과도한 서식(색상, 굵기 등)이 포함되어 저장할 수 없습니다. 서식을 조금 줄여주세요.';
            }
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors'  => $errors,
                'article' => [
                    'title' => $title,
                    'content' => $content,
                    'is_pinned' => $is_pinned,
                ]
            ];
        }

        // 파일 업로드 처리 (최대 3개, 1개의 파일 당 10MB 제한)
        $uploaded_files = [];
        $max_file_count = 3;
        $max_file_size  = 10 * 1024 * 1024; 

        if (!empty($fileData['attached_files']['name'])) {
            $upload_dir = APP_ROOT . '/public/uploads/attachments/';
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            foreach ($fileData['attached_files']['name'] as $key => $name) {
                if (count($uploaded_files) >= $max_file_count) {
                    break;
                }

                if ($fileData['attached_files']['error'][$key] === UPLOAD_ERR_OK) {
                    $tmp_name = $fileData['attached_files']['tmp_name'][$key];
                    $size     = $fileData['attached_files']['size'][$key];

                    if ($size > $max_file_size) {
                        $errors['files'] = '파일 당 최대 용량(10MB)을 초과했습니다.';
                        break;
                    }

                    $ext      = pathinfo($name, PATHINFO_EXTENSION);
                    $new_name = 'notice_' . uniqid('', true) . '.' . $ext; 
                    $file_path = $upload_dir . $new_name;

                    if (move_uploaded_file($tmp_name, $file_path)) {
                        $uploaded_files[] = [
                            'file_path' => 'public/uploads/attachments/' . $new_name,
                            'org_name'  => $name
                        ];
                    }
                }
            }
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors'  => $errors,
                'article' => [
                    'title' => $title,
                    'content' => $content,
                    'is_pinned' => $is_pinned,
                ]
            ];
        }

        // DB 서비스 호출
        $board_service = $this->cms->getBoard();
        $inserted_id = false;

        if ($boardName === 'notice') {
            $inserted_id = $board_service->insertNoticeArticle([
                'user_id'   => $userId,
                'title'     => $title,
                'content'   => $content,
                'is_pinned' => $is_pinned
            ], $uploaded_files);
        }

        if ($inserted_id) {
            return [
                'success' => true,
            ];
        }

        return [
            'success' => false,
            'errors'  => ['system' => '게시글 저장 중 오류가 발생했습니다.'],
            'article' => [
                'title' => $title,
                'content' => $content,
                'is_pinned' => $is_pinned,
            ]
        ];
    }
}