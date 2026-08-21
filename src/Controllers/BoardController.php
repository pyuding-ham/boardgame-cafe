<?php
declare(strict_types = 1);

namespace BoardgameCafe\Controllers;

use Exception;
use BoardgameCafe\Validate\Validate;
use BoardgameCafe\Exceptions\PostNotFoundException;
use BoardgameCafe\Exceptions\AuthorizationException;
use BoardgameCafe\Exceptions\ErrorCode;

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
    public function index(int $page = 1, ?string $boardName = 'notice', ?string $boardId = '3'): array {
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

        // 4. 총 게시글 개수
        // DB 서비스 호출
        $board_service = $this->cms->getBoard();
        $total_count = $board_service->getBoardTotalCount($boardName, $filters);

        // 5. 게시판 이름에 따라 다른 서비스 메서드 호출
        // 게임소개
        if ($boardName === 'boardgame') {
            $per_page = 9;

            $category = $board_service->getCategory($boardId);
            $level = $board_service->getBoardgameLevel();

            $list = $board_service->getBoardList($boardName, $per_page, $offset, $filters);

            foreach ($list as $key => $post) {
                // 해시태그
                if (isset($post['hashtag']) && !empty($post['hashtag'])) {
                    $tags_array = explode(',', $post['hashtag']);

                    $formatted_array = array_map(function($tag) {
                        return '#' . trim($tag);
                    }, $tags_array);

                    $list[$key]['hashtag'] = implode(' ', array_filter($formatted_array));
                }
            }
        }
        // 지점소개
        elseif ($boardName === 'branch') {
            $per_page = 9;

            $list = $board_service->getBoardList($boardName, $per_page, $offset, $filters);
        }
        // 공지사항
        elseif ($boardName === 'notice') {
            $list = $board_service->getBoardList('notice', $per_page, $offset, $filters);
            $start_num = $total_count - $offset;

            // 글 번호 가공
            foreach ($list as &$post) {
                if ($post['is_pinned'] == 1) {
                    // 상단 고정 글(공지)은 번호 자리를 비워둠
                    $post['board_no'] = null; 
                    // 번호가 뜨지 않도록 마이너스 처리
                    $start_num--; 
                } else {
                    // 상단 고정 글이 아닌 글
                    $post['board_no'] = $start_num;
                    $start_num--; 
                }
            }
            unset($post);
        }
        // 기본 게시판
        else {
            $list = $board_service->getBoardList($boardName, $per_page, $offset, $filters);
            $start_num = $total_count - $offset;

            // 글 번호 가공
            foreach ($list as &$post) {
                $post['board_no'] = $start_num;
                $start_num--;
            }
            unset($post);
        }

        $total_pages = (int)ceil($total_count / $per_page);

        $data = [
            'list' => $list,
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_count' => $total_count,
            'keyword' => $keyword,
            'search_type' => $search_type,
            'session' => $_SESSION,
        ];

        // 게임소개
        if ($boardName === 'boardgame') {
            $data['category'] = $category;
            $data['level']    = $level;
        }

        return $data;
    }

    /**
     * 게시글 상세
     * 
     * @param string|int $identifier 숫자 ID 또는 영문 슬러그
     * @param string $boardName 게시판 식별자 이름
     * @return array|false 게시글 데이터 배열 또는 실패 시 false
     */
    public function view(string|int $identifier, ?string $boardName, ?int $userId = null): array|false {
        $board_service = $this->cms->getBoard();

        // 게시판 이름에 따라 다른 상세 보기 데이터 호출
        // 게임소개
        if ($boardName === 'boardgame') {
            $post = $board_service->getBoardPostBySlug($boardName, $identifier, $userId);

            // 해시태그
            if (isset($post['hashtag']) && !empty($post['hashtag'])) {
                $tags_array = explode(',', $post['hashtag']);

                $formatted_array = array_map(function($tag) {
                    return '#' . trim($tag);
                }, $tags_array);

                $post['hashtag'] = implode(' ', array_filter($formatted_array));
            }
        }
        // 그 외의 게시판
        else {
            $post = $board_service->getBoardPost($boardName, $identifier);
        }

        // 게시글이 존재하지 않거나 삭제된 경우 예외 처리
        if (!$post) {
            throw new PostNotFoundException(ErrorCode::POST_NOT_FOUND_READ->value);
        }

        return [
            'post' => $post,
        ];
    }

    /**
     * 게시글 작성
     */
    public function insert(string $boardName, array $postData, array $fileData, int $userId): array
    {
        // 게임소개, 지점소개, 공지사항 게시판일 때 관리자 여부 체크
        // DB 서비스 호출
        $user_service = $this->cms->getUser();
        
        if (($boardName === 'boardgame'|| $boardName === 'branch'|| $boardName === 'notice') && !$user_service->isAdmin($userId)) {
            throw new AuthorizationException(ErrorCode::ACCESS_DENIED->value);
        }

        $title     = trim($postData['title'] ?? '');
        $content   = trim($postData['content'] ?? '');
        $address   = trim($postData['address'] ?? '');
        $is_pinned = isset($postData['is_pinned']) ? 1 : 0;
        $errors    = [];

        // 게시글 내용 글자 수 카운트 변수
        $decoded_for_length = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $pure_text_for_length = strip_tags($decoded_for_length);
        $clean_content_for_length = preg_replace('/[\s\x{00a0}\x{200b}]+/u', '', $pure_text_for_length);
        // 순수 글자 수
        $real_text_length = mb_strlen($clean_content_for_length, 'UTF-8');
        // HTML 태그를 포함한 용량
        $html_byte_length = strlen($content);

        // 1. 제목 필수 입력 값 검사
        if (empty($title)) {
            $errors['title'] = '제목을 입력해 주세요.';
        }
        // 2. 제목 글자 수 검사 (최대 100자)
        if (empty($errors['title']) && !Validate::isText($title, 1, 100)) {
            $errors['title'] = '제목은 최대 100자까지 입력할 수 있습니다.';
        }
       
        // 3. 내용 필수 입력 값 검사
        if ($real_text_length === 0 || empty($clean_content_for_length)) {
            $errors['content'] = '내용을 입력해주세요.';
        }

        if (empty($errors['content'])) {
            // 4. 내용 글자 수 검사
            if ($real_text_length > 5000) {
                $errors['content'] = '본문 내용은 최대 5,000자까지 입력 가능합니다. (현재 ' . number_format($real_text_length) . '자)';
            } 
            // 5. 과도한 HTML 태그 서식 입력 방지
            elseif ($html_byte_length > 50000) {
                $errors['content'] = '과도한 서식(색상, 굵기 등)이 포함되어 저장할 수 없습니다. 서식을 조금 줄여주세요.';
            }
        }

        // 지점소개
        if ($boardName === 'branch') {
            // 1. 주소 필수 입력 값 검사
            if (empty($address)) {
                $errors['address'] = '주소를 입력해 주세요.';
            }
            // 2. 주소 글자 수 검사 (최대 100자)
            if (empty($errors['address']) && !Validate::isText($address, 1, 255)) {
                $errors['address'] = '주소는 최대 255자까지 입력할 수 있습니다.';
            }
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors'  => $errors,
                'post' => [
                    'title' => $title,
                    'content' => $content,
                    'address' => $address,
                    'is_pinned' => $is_pinned,
                ]
            ];
        }

        // 대표 이미지 업로드 처리 (파일 당 5MB 제한)
        $thumbnail_file = [];
        $max_img_size  = 5 * 1024 * 1024;

        if (!empty(isset($fileData['thumbnail']) && !empty($fileData['thumbnail']['name']))) {

            // 업로드 이미지 유효성 검사
            $image_errors = $this->validateImageUpload($fileData['thumbnail'], $max_img_size);

            if (!empty($image_errors)) {
                $errors['thumbnail'] = $image_errors[0];

            } else {
                $date_path = date('Y/m/d') . '/';
                $upload_dir = APP_ROOT . '/public/uploads/boards/' . $boardName . '/' . $date_path;

                if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
                    throw new Exception('업로드 폴더를 생성할 수 없습니다.');
                }

                $ext = strtolower(
                    pathinfo($fileData['thumbnail']['name'], PATHINFO_EXTENSION)
                );
                
                $new_name = 'thumb_' . uniqid('', true) . '.' . $ext;
                $file_path = $upload_dir . $new_name;

                if (move_uploaded_file($fileData['thumbnail']['tmp_name'], $file_path)) {
                    $thumbnail_file = [
                        'file_path' => 'public/uploads/boards/' . $boardName . '/' . $date_path . $new_name,
                        'org_name'  => $fileData['thumbnail']['name'],
                    ];
                } else {
                    $errors['thumbnail'] = '대표 이미지 업로드에 실패했습니다.';
                }
            }
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors'  => $errors,
                'post' => [
                    'title' => $title,
                    'content' => $content,
                    'address' => $address,
                    'is_pinned' => $is_pinned,
                ]
            ];
        }

        // 상세 이미지 업로드 처리 (파일 당 5MB 제한)
        $images_files = [];
        $max_file_count = 10;
        $max_file_size  = 5 * 1024 * 1024;

        if (isset($fileData['detailImages']) && !empty($fileData['detailImages']['name'][0])) {

            $date_path = date('Y/m/d') . '/';
            $upload_dir = APP_ROOT . '/public/uploads/boards/' . $boardName . '/' . $date_path;

            if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
                throw new Exception('업로드 폴더를 생성할 수 없습니다.');
            }

            foreach ($fileData['detailImages']['name'] as $key => $name) {
                if (count($images_files) >= $max_file_count) {
                    break;
                }

                // 파일 배열 생성
                $file = [
                    'name'     => $fileData['detailImages']['name'][$key],
                    'type'     => $fileData['detailImages']['type'][$key],
                    'tmp_name' => $fileData['detailImages']['tmp_name'][$key],
                    'error'    => $fileData['detailImages']['error'][$key],
                    'size'     => $fileData['detailImages']['size'][$key],
                ];

                // 업로드 이미지 유효성 검사
                $image_errors = $this->validateImageUpload($file, $max_file_size);

                if (!empty($image_errors)) {
                    $errors['detail_images'] = $image_errors[0];
                    break;
                }

                $ext = strtolower(
                    pathinfo($name, PATHINFO_EXTENSION)
                );

                $new_name = 'detail_' . uniqid('', true) . '.' . $ext;
                $file_path = $upload_dir . $new_name;

                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    $images_files[] = [
                        'file_path'  => 'public/uploads/boards/' . $boardName . '/' . $date_path . $new_name,
                        'org_name'   => $name,
                    ];
                } else {
                    $errors['detail_images'] = '상세 이미지 업로드에 실패했습니다.';
                }
            }
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors'  => $errors,
                'post' => [
                    'title' => $title,
                    'content' => $content,
                    'address' => $address,
                    'is_pinned' => $is_pinned,
                ]
            ];
        }

        // 파일 업로드 처리 (최대 3개, 1개의 파일 당 10MB 제한)
        $uploaded_files = [];
        $max_file_count = 3;
        $max_file_size  = 10 * 1024 * 1024; 

        if (!empty(isset($fileData['attached_files']) && $fileData['attached_files']['name'])) {
            $date_path = date('Y/m/d') . '/';
            $upload_dir = APP_ROOT . '/public/uploads/attachments/' . $date_path;
            
            if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
                throw new Exception('업로드 폴더를 생성할 수 없습니다.');
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
                            'file_path' => 'public/uploads/attachments/' . $date_path . $new_name,
                            'org_name'  => $name,
                        ];
                    }
                }
            }
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors'  => $errors,
                'post' => [
                    'title' => $title,
                    'content' => $content,
                    'address' => $address,
                    'is_pinned' => $is_pinned,
                ]
            ];
        }

        // DB 서비스 호출
        $board_service = $this->cms->getBoard();
        $db = $this->cms->getDb();

        // 게임소개
        if ($boardName === 'boardgame') {
            $db->beginTransaction();

            try {
                $post_id = $board_service->insertBoardPost(
                    'boardgame',
                    $userId,
                    [
                        'title'        => $title,
                        'slug'         => $postData['slug'],
                        'category'     => $postData['category_id'],
                        'level'        => $postData['level_id'] ?? null,
                        'play_time'    => $postData['play_time'] ?? null,
                        'player_count' => $postData['player_count'] ?? null,
                        'hashtag'      => $postData['hashtag'] ?? null,
                        'content'      => $content,
                        'thumbnail'    => $thumbnail_file['file_path'] ?? null,
                    ]
                );

                $board_service->insertBoardImage($post_id, $thumbnail_file, $images_files);

                $db->commit();
            
            } catch (\Throwable $e) {
                $db->rollBack();
                throw $e; 
            }
        }
        // 지점소개
        elseif ($boardName === 'branch') {
            $db->beginTransaction();

            try {
                $post_id = $board_service->insertBoardPost(
                    'branch',
                    $userId,
                    [
                        'title'     => $title,
                        'content'   => $content,
                        'address'   => $address,
                        'thumbnail' => $thumbnail_file['file_path'] ?? null,
                    ]
                );

                $board_service->insertBoardImage($post_id, $thumbnail_file, $images_files);

                $db->commit();
            
            } catch (\Throwable $e) {
                $db->rollBack();
                throw $e; 
            }
        }
        // 공지사항
        elseif ($boardName === 'notice') {
            $db->beginTransaction();

            try {
                $post_id = $board_service->insertBoardPost(
                    'notice',
                    $userId,
                    [
                        'title'     => $title,
                        'content'   => $content,
                        'is_pinned' => $is_pinned,
                    ]
                );

                $board_service->insertBoardFile($post_id, $uploaded_files);

                $db->commit();
            
            } catch (\Throwable $e) {
                $db->rollBack();
                throw $e; 
            }
        }
        // 기본 게시판
        else {
            $board_service->insertBoardPost(
                $boardName,
                $userId,
                [
                    'title'     => $title,
                    'content'   => $content,
                ]
            );
        }

        return [
            'success' => true,
        ];
    }

    /**
     * 게시글 작성 (게임소개)
     */
    public function insertBoardgame(string $boardName, array $postData, array $fileData, int $userId): array
    {
        // 관리자 여부 체크
        // DB 서비스 호출
        $user_service = $this->cms->getUser();

        if (!$user_service->isAdmin($userId)) {
            throw new AuthorizationException(ErrorCode::ACCESS_DENIED->value);
        }

        $title        = trim($postData['title'] ?? '');
        $slug         = trim($postData['slug'] ?? '');
        $category     = trim($postData['category_id'] ?? '');
        $level        = isset($postData['level_id']) ? trim($postData['level_id']) : null;
        $play_time    = trim($postData['play_time'] ?? '');
        $player_count = trim($postData['player_count'] ?? '');
        $content      = trim($postData['content'] ?? '');
        $hashtag      = trim($postData['hashtag'] ?? '');
        $errors       = [];

        // 게시글 내용 글자 수 카운트 변수
        $decoded_for_length = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $pure_text_for_length = strip_tags($decoded_for_length);
        $clean_content_for_length = preg_replace('/[\s\x{00a0}\x{200b}]+/u', '', $pure_text_for_length);
        // 순수 글자 수
        $real_text_length = mb_strlen($clean_content_for_length, 'UTF-8');
        // HTML 태그를 포함한 용량
        $html_byte_length = strlen($content);

        // DB 서비스 호출
        $board_service = $this->cms->getBoard();


        // 입력 값 검증

        // 1. 제목
        // 필수 입력 값 검사
        if (empty($title)) {
            $errors['title'] = '제목을 입력해 주세요.';
        }
        // 글자 수 검사
        elseif (!Validate::isText($title, 1, 100)) {
            $errors['title'] = '제목은 최대 100자까지 입력할 수 있습니다.';
        }

        // 2. 페이지 주소
        // 필수 입력 값 검사
        if (empty($slug)) {
            $errors['slug'] = '페이지 주소를 입력해 주세요.';
        }
        // 글자 수 검사
        elseif (empty($errors['slug']) && !Validate::isText($slug, 1, 255)) {
            $errors['slug'] = '페이지 주소는 최대 255자까지 입력할 수 있습니다.';
        }
        // 슬러그 값 가공
        else {
            $clean_slug = $this->sanitize_slug($slug);

            // 슬러그 빈 값 검사
            if (empty($clean_slug)) {
                $errors['slug'] = "올바른 페이지 주소 형식이 아닙니다. 영문이나 숫자를 포함해 주세요.";
            }
            // 슬러그 중복체크 및 숫자 붙이기
            else {
                $final_slug = $clean_slug;
                $counter = 1;
        
                while (true) {
                    $is_duplicate = $board_service->isSlugExists($final_slug);
                    
                    if (!$is_duplicate) {
                        break;
                    }

                    $final_slug = $clean_slug . '-' . $counter;
                    $counter++;
                }

                $slug = $postData['slug'] = $final_slug;
            }
        }

        // 3. 카테고리
        // 필수 입력 값 검사
        if (empty($category)) {
            $errors['category'] = '카테고리를 선택해 주세요.';
        }
        // 위변조 검증
        elseif (!$board_service->hasCategory($category)) {
            $errors['category'] = '올바르지 않은 카테고리 선택입니다.';
        }

        // 4. 난이도
        // 위변조 검증
        if (!empty($level) && !$board_service->hasBoardgameLevel($level)) {
            $errors['level'] = '올바르지 않은 난이도 선택입니다.';
        }

        // 5. 플레이 인원 글자 수 검사
        if (!Validate::isText($player_count, 0, 20)) {
            $errors['player_count'] = '플레이 인원은 최대 20자까지 입력할 수 있습니다.';
        }
        
        // 6. 플레이 시간 글자 수 검사
        if (!Validate::isText($play_time, 0, 20)) {
            $errors['play_time'] = '플레이 시간은 최대 20자까지 입력할 수 있습니다.';
        }

        // 7. 내용
        // 필수 입력 값 검사
        if ($real_text_length === 0 || empty($clean_content_for_length)) {
            $errors['content'] = '내용을 입력해주세요.';
        }

        if (empty($errors['content'])) {
            // 글자 수 검사
            if ($real_text_length > 5000) {
                $errors['content'] = '본문 내용은 최대 5,000자까지 입력 가능합니다. (현재 ' . number_format($real_text_length) . '자)';
            } 
            // 과도한 HTML 태그 서식 입력 방지
            elseif ($html_byte_length > 50000) {
                $errors['content'] = '과도한 서식(색상, 굵기 등)이 포함되어 저장할 수 없습니다. 서식을 조금 줄여주세요.';
            }
        }
        
        // 8. 해시태그 글자 수 검사
        if (!Validate::isText($hashtag, 0, 255)) {
            $errors['hashtag'] = '해시태그는 최대 255자까지 입력할 수 있습니다.';
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors'  => $errors,
                'post' => [
                    'title'             => $title,
                    'content'           => $content,
                    'slug'              => $slug,
                    'category_selected' => $category,
                    'level_selected'    => $level,
                    'play_time'         => $play_time,
                    'player_count'      => $player_count,
                    'hashtag'           => $hashtag,
                ]
            ];
        }

        $result = $this->insert($boardName, $postData, $fileData, $userId);

        if (isset($result['success']) && $result['success'] === true) {
            return [
                'success' => true,
            ];
        } else {
            return [
                'success' => false,
                'errors'  => $errors,
                'post' => [
                    'title'             => $title,
                    'content'           => $content,
                    'slug'              => $slug,
                    'category_selected' => $category,
                    'level_selected'    => $level,
                    'play_time'         => $play_time,
                    'player_count'      => $player_count,
                    'hashtag'           => $hashtag,
                ]
            ];
        }
    }

    /**
     * 게시글 수정 페이지 조회
     */
    public function edit(int $identifier, string $boardName, int $userId): array|false
    {
        // 1. DB 서비스 호출
        $board_service = $this->cms->getBoard();

        // 2. 게시글 존재 여부 확인
        $post_owner = $board_service->findPostOwnerById($identifier);

        if (!$post_owner) {
            throw new PostNotFoundException(ErrorCode::POST_NOT_FOUND_UPDATE->value);
        }

        // 3. 수정 권한 체크
        if (!$board_service->canModifyPost($userId, $post_owner, $boardName)) {
           throw new AuthorizationException(ErrorCode::ACCESS_DENIED->value);
        }

        return $this->view($identifier, $boardName);
    }

    /**
     * 게시글 수정 페이지 저장
     */
    public function update(
        string $boardName,
        int $postId,
        array $postData,
        array $fileData,
        int $userId
    ): array
    {
        // 지점소개, 공지사항 게시판일 때 관리자 여부 체크
        $userService = $this->cms->getUser();
        
        if (($boardName === 'branch' || $boardName == 'notice') && !$userService->isAdmin($userId)) {
            throw new AuthorizationException(ErrorCode::ACCESS_DENIED->value);
        }

        $title            = trim($postData['title'] ?? '');
        $content          = trim($postData['content'] ?? '');
        $address          = trim($postData['address'] ?? '');
        $delete_thumbnail = trim($postData['delete_thumbnail'] ?? 'N');
        $is_pinned        = isset($postData['is_pinned']) ? 1 : 0;

        
        // 삭제할 첨부파일 목록 생성 및 정제
        $delete_file_ids = $postData['delete_file_ids'] ?? [];

        // 숫자 이외의 값 제거
        $delete_file_ids = array_filter(
            $delete_file_ids,
            fn($id) => is_numeric($id)
        );

        // 문자열 숫자를 정수로 변환
        $delete_file_ids = array_map(
            'intval',
            $delete_file_ids
        );

        $errors = [];

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

        // 지점소개
        if ($boardName === 'branch') {
            // 1. 주소 필수 입력 값 검사
            if (empty($address)) {
                $errors['address'] = '주소를 입력해 주세요.';
            }
            // 2. 주소 글자 수 검사 (최대 100자)
            if (empty($errors['address']) && !Validate::isText($address, 1, 255)) {
                $errors['address'] = '주소는 최대 255자까지 입력할 수 있습니다.';
            }
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors'  => $errors,
                'post' => [
                    'title' => $title,
                    'content' => $content,
                    'address' => $address,
                    'is_pinned' => $is_pinned,
                ]
            ];
        }

        // 대표 이미지 수정 처리 (파일 당 5MB 제한)
        $thumbnail_file = [];
        $delete_image_ids = json_decode(
            $postData['deleted_images'] ?? '[]',
            true
        );
        $image_orders = json_decode(
            $postData['image_orders'] ?? '[]',
            true
        );
        $max_img_size = 5 * 1024 * 1024;

        if (!empty(isset($fileData['thumbnail']) && !empty($fileData['thumbnail']['name']))) {

            // 업로드 이미지 유효성 검사
            $image_errors = $this->validateImageUpload($fileData['thumbnail'], $max_img_size);

            if (!empty($image_errors)) {
                $errors['thumbnail'] = $image_errors[0];

            } else {
                $date_path = date('Y/m/d') . '/';
                $upload_dir = APP_ROOT . '/public/uploads/boards/' . $boardName . '/' . $date_path;

                if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
                    throw new Exception('업로드 폴더를 생성할 수 없습니다.');
                }

                $ext = strtolower(
                    pathinfo($fileData['thumbnail']['name'], PATHINFO_EXTENSION)
                );

                $new_name = 'thumb_' . uniqid('', true) . '.' . $ext;
                $file_path = $upload_dir . $new_name;

                if (move_uploaded_file($fileData['thumbnail']['tmp_name'], $file_path)) {
                    $thumbnail_file = [
                        'file_path' => 'public/uploads/boards/' . $boardName . '/' . $date_path . $new_name,
                        'org_name'  => $fileData['thumbnail']['name'],
                    ];
                } else {
                    $errors['thumbnail'] = '대표 이미지 업로드에 실패했습니다.';
                }
            }
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors'  => $errors,
                'post' => [
                    'title' => $title,
                    'content' => $content,
                    'address' => $address,
                    'is_pinned' => $is_pinned,
                ]
            ];
        }

        // 상세 이미지 업로드 처리 (파일 당 5MB 제한)
        $images_files = [];
        $max_file_count = 10;
        $max_file_size  = 5 * 1024 * 1024;

        if (isset($fileData['detailImages']) && !empty($fileData['detailImages']['name'][0])) {
            $date_path = date('Y/m/d') . '/';
            $upload_dir = APP_ROOT . '/public/uploads/boards/' . $boardName . '/' . $date_path;

            if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
                throw new Exception('업로드 폴더를 생성할 수 없습니다.');
            }

            foreach ($fileData['detailImages']['name'] as $key => $name) {
                if (count($images_files) >= $max_file_count) {
                    break;
                }

                // 파일 배열 생성
                $file = [
                    'name'     => $fileData['detailImages']['name'][$key],
                    'type'     => $fileData['detailImages']['type'][$key],
                    'tmp_name' => $fileData['detailImages']['tmp_name'][$key],
                    'error'    => $fileData['detailImages']['error'][$key],
                    'size'     => $fileData['detailImages']['size'][$key],
                ];

                // 업로드 이미지 유효성 검사
                $image_errors = $this->validateImageUpload($file, $max_file_size);

                if (!empty($image_errors)) {
                    $errors['detail_images'] = $image_errors[0];
                    break;
                }

                $ext = strtolower(
                    pathinfo($name, PATHINFO_EXTENSION)
                );

                $new_name = 'detail_' . uniqid('', true) . '.' . $ext;
                $file_path = $upload_dir . $new_name;

                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    $images_files[] = [
                        'file_path'  => 'public/uploads/boards/' . $boardName . '/' . $date_path . $new_name,
                        'org_name'   => $name,
                    ];
                } else {
                    $errors['detail_images'] = '상세 이미지 업로드에 실패했습니다.';
                }
            }
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors'  => $errors,
                'post' => [
                    'title' => $title,
                    'content' => $content,
                    'address' => $address,
                    'is_pinned' => $is_pinned,
                ]
            ];
        }

        // 파일 업로드 처리 (최대 3개, 1개의 파일 당 10MB 제한)
        $uploaded_files = [];

        if (!empty($delete_file_ids) || !empty($fileData['attached_files']['name'])) {
            $max_file_count = 3;
            $max_file_size  = 10 * 1024 * 1024; 
    
            $date_path = date('Y/m/d') . '/';
            $upload_dir = APP_ROOT . '/public/uploads/attachments/' . $date_path;
            
            if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
                throw new Exception('업로드 폴더를 생성할 수 없습니다.');
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

                    $ext       = pathinfo($name, PATHINFO_EXTENSION);
                    $new_name  = 'notice_' . uniqid('', true) . '.' . $ext; 
                    $file_path = $upload_dir . $new_name;

                    if (move_uploaded_file($tmp_name, $file_path)) {
                        $uploaded_files[] = [
                            'file_path' => 'public/uploads/attachments/' . $date_path . $new_name,
                            'org_name'  => $name,
                        ];
                    }
                }
            }
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors'  => $errors,
                'post' => [
                    'title' => $title,
                    'content' => $content,
                    'address' => $address,
                    'is_pinned' => $is_pinned,
                ]
            ];
        }

        // DB 서비스 호출
        $board_service = $this->cms->getBoard();
        $db = $this->cms->getDb();

        // 지점소개
        if ($boardName === 'branch') {
            $db->beginTransaction();

            try {
                $board_service->updateBoardPost(
                    'branch',
                    $postId,
                    $userId,
                    [
                        'title'     => $title,
                        'content'   => $content,
                        'address'   => $address,
                        'thumbnail' => $thumbnail_file['file_path'] ?? null,
                    ],
                );

                $board_service->updateBoardImage($postId, $thumbnail_file, $images_files, $delete_image_ids, $image_orders, $delete_thumbnail);

                $db->commit();
            
            } catch (\Throwable $e) {
                $db->rollBack();
                throw $e; 
            }
        }
        // 공지사항
        elseif ($boardName === 'notice') {
            $db->beginTransaction();

            try {
                $board_service->updateBoardPost(
                    'notice',
                    $postId,
                    $userId,
                    [
                        'title'     => $title,
                        'content'   => $content,
                        'is_pinned' => $is_pinned,
                    ],
                    $uploaded_files,
                    $delete_file_ids
                );

                $board_service->updateBoardFile($postId, $uploaded_files, $delete_file_ids);

                $db->commit();

            } catch (\Throwable $e) {
                $db->rollBack();
                throw $e; 
            }
        }
        // 기본 게시판
        else {
            $board_service->updateBoardPost(
                $boardName,
                $postId,
                $userId,
                [
                    'title'           => $title,
                    'content'         => $content,
                    'thumbnail'       => $thumbnail ?? null,
                ]
            );
        }

        return [
            'success' => true,
        ];
    }

    /**
     * 게시글 삭제
     */
    public function delete(int $identifier, string $boardName, int $userId): void
    {
        // 1. DB 서비스 호출
        $board_service = $this->cms->getBoard();

        // 2. 게시글 존재 여부 확인
        $post_owner = $board_service->findPostOwnerById($identifier);

        if (!$post_owner) {
            throw new PostNotFoundException(ErrorCode::POST_NOT_FOUND_DELETE->value);
        }

        // 3. 삭제 권한 체크
        if (!$board_service->canModifyPost($userId, $post_owner, $boardName)) {
           throw new AuthorizationException(ErrorCode::ACCESS_DENIED->value);
        }
        
        $board_service->deleteBoardPost($boardName, $identifier, $userId);
    }

    /**
     * 게시글 존재 여부 확인
     */
    public function isPostExists(string $post_id): bool 
    {
        // 1. DB 서비스 호출
        $board_service = $this->cms->getBoard();

        // 2. 게시글 존재 여부 확인
        $result = $board_service->isPostExists($post_id);

        return $result;
    }

    /**
     * 게시글 작성 가능 여부 확인
     */
    public function canWritePost(int $user_id, string $board_name): bool
    {
        // DB 서비스 호출
        $user_service = $this->cms->getUser();

        // 게임소개, 지점소개, 공지사항은 관리자만 작성 가능
        if ($board_name === 'boardgame' || $board_name === 'branch' || $board_name === 'notice') {
            return $user_service->isAdmin($user_id);
        }

        return true;
    }

    /**
     * 업로드 이미지 유효성 검사
     */
    public function validateImageUpload($file, $max_img_size)
    {
        $errors = [];

        // 업로드 오류 확인
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = '이미지 업로드 중 오류가 발생했습니다.';
            return $errors;
        }

        // 용량 확인 (기본 5MB)
        if ($file['size'] > $max_img_size) {
            $errors[] = '이미지 당 최대 5MB까지 업로드 가능합니다.';
            return $errors;
        }

        // 확장자 검사
        $allowed_ext = [
            'jpg',
            'jpeg',
            'png',
            'gif',
            'webp',
        ];

        $ext = strtolower(
            pathinfo($file['name'], PATHINFO_EXTENSION)
        );

        if (!in_array($ext, $allowed_ext, true)) {
            $errors[] = '지원하지 않는 이미지 확장자입니다.';
            return $errors;
        }

        // MIME 타입 검사
        $allowed_mime = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        ];

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);

        if (!in_array($mime, $allowed_mime, true)) {
            $errors[] = '지원하지 않는 이미지 형식입니다.';
            return $errors;
        }

        // 실제 이미지 확인
        $image_info = @getimagesize($file['tmp_name']);
        if ($image_info === false) {
            $errors[] = '올바른 이미지 파일이 아닙니다.';
            return $errors;
        }

        // 이미지 크기 제한
        if ($image_info[0] > 5000 || $image_info[1] > 5000) {
            $errors[] = '이미지 크기는 최대 5000x5000까지 가능합니다.';
            return $errors;
        }

        return [];
    }

    /**
     * 슬러그 값 가공
     */
    public function sanitize_slug(string $slug): string
    {
        // 1. 소문자로 변환
        $slug = strtolower($slug);
        
        // 2. 영문 소문자, 숫자, 하이픈(-) 외의 모든 문자(한글, 특수문자 등)는 제거
        $slug = preg_replace('/[^a-z0-9-]/', '', $slug);
        
        // 3. 연속으로 들어온 하이픈(--)은 하나의 하이픈(-)으로 축소
        $slug = preg_replace('/-+/', '-', $slug);
        
        // 4. 양끝에 남아있는 하이픈 제거
        $slug = trim($slug, '-');
        
        return $slug;
    }

    /**
     * 게시글 등록 화면 출력
     */
    public function getWriteForm(string $site_menu_id): array
    {
        // DB 서비스 호출
        $board_service = $this->cms->getBoard();

        $result['category'] = $board_service->getCategory($site_menu_id);

        // 게임소개
        if ($site_menu_id === '1') {
            $result['level'] = $board_service->getBoardgameLevel();
        }

        return $result;
    }
}
