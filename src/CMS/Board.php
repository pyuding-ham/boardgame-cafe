<?php
declare(strict_types = 1);

namespace BoardgameCafe\CMS;

use Exception;
use BoardgameCafe\Exceptions\NotFoundException;
use BoardgameCafe\Exceptions\PostNotFoundException;
use BoardgameCafe\Exceptions\AuthorizationException;
use BoardgameCafe\Exceptions\AuthenticationException;
use BoardgameCafe\Exceptions\ErrorCode;

class Board
{
    protected $db;
    private User $user;
    
    public function __construct(
        Database $db,
        User $user
    ) {
        $this->db = $db;
        $this->user = $user;
    }

    /**
     * 게시판 목록 조회
     */
    public function getBoardList(string $board_name, int $limit, int $offset, array $filters = []): array
    {
        // 1. page_code 기반으로 site_menu_id 조회
        $menu_sql = "SELECT id FROM site_menu WHERE page_code = :page_code;";
        $menu_stmt = $this->db->runSql($menu_sql, ['page_code' => $board_name]);
        $menu = $menu_stmt ? $menu_stmt->fetch() : false;
        
        if (!$menu) {
            throw new NotFoundException(ErrorCode::BOARD_NOT_FOUND->value);
        }
        
        $params = ['page_code' => $board_name];
        
        // 2. 게시판별 분기 처리
        switch ($board_name) {
            // 공지사항
            case 'notice':
                $sql = "SELECT p.id, p.title, '관리자' AS nickname, p.created_at,
                            COALESCE(nd.is_pinned, 0) AS is_pinned
                        FROM post p
                        INNER JOIN site_menu m ON p.site_menu_id = m.id
                        INNER JOIN notice_detail nd ON p.id = nd.post_id";
                break;

            // 기본 게시판
            default:
                $sql = "SELECT p.id, p.thumbnail, p.title, p.writer_nickname AS nickname, p.created_at,
                            0 AS is_pinned
                        FROM post p
                        INNER JOIN site_menu m ON p.site_menu_id = m.id";
                break;
        }

        // 3. 공통 조건 (삭제되지 않은 요청한 page_code에 해당하는 게시글만)
        $sql .= " WHERE m.page_code = :page_code AND p.is_deleted = 0";

        // 4. 공통 검색 필터 처리
        if (isset($filters['keyword']) && trim($filters['keyword']) !== '') {
            $keyword_value = '%' . trim($filters['keyword']) . '%';

            if ($filters['type'] === 'title') {
                // 제목으로 검색
                $sql .= " AND p.title LIKE :keyword_title";
                $params['keyword_title'] = $keyword_value;
            } elseif ($filters['type'] === 'content') {
                // 내용으로 검색
                $sql .= " AND p.content LIKE :keyword_content";
                $params['keyword_content'] = $keyword_value;
            } else {
                // 전체 검색
                $sql .= " AND (p.title LIKE :keyword_title OR p.content LIKE :keyword_content)";
                $params['keyword_title'] = $keyword_value;
                $params['keyword_content'] = $keyword_value;
            }
        }

        // 5. 공통 정렬 및 페이징
        $sql .= " ORDER BY is_pinned DESC, p.id DESC";
        $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset . ";";

        return $this->db->runSql($sql, $params)->fetchAll();
    }

    /**
     * 총 게시글 개수 조회
     */
    public function getBoardTotalCount(string $page_code, array $filters = []): int
    {
        // 1. site_menu와 post 테이블을 조인하여 해당 게시판의 삭제되지 않은 글 개수 조회
        $sql = "SELECT COUNT(p.id)
                FROM post p
                INNER JOIN site_menu m ON p.site_menu_id = m.id
                WHERE m.page_code = :page_code AND p.is_deleted = 0";
                
        $params = ['page_code' => $page_code];
        
        // 2. 공통 검색 필터 처리
        if (isset($filters['keyword']) && trim($filters['keyword']) !== '') {
            $keyword_value = '%' . trim($filters['keyword']) . '%';

            if ($filters['type'] === 'title') {
                // 제목으로 검색
                $sql .= " AND p.title LIKE :keyword_title";
                $params['keyword_title'] = $keyword_value;
            } elseif ($filters['type'] === 'content') {
                // 내용으로 검색
                $sql .= " AND p.content LIKE :keyword_content";
                $params['keyword_content'] = $keyword_value;
            } else {
                // 전체 검색
                $sql .= " AND (p.title LIKE :keyword_title OR p.content LIKE :keyword_content)";
                $params['keyword_title'] = $keyword_value;
                $params['keyword_content'] = $keyword_value;
            }
        }

        $stmt = $this->db->runSql($sql, $params);
        return $stmt ? (int)$stmt->fetchColumn() : 0;
    }

    /**
     * 단일 게시글 상세 조회
     */
    public function getBoardPost(string $page_code, int $id): array|bool
    {
        // 1. 게시판별 게시글 조회 분기 처리
        switch ($page_code) {
            // 공지사항
            case 'notice':
                $sql = "SELECT p.id, '관리자' AS nickname, p.title, p.content, p.created_at,
                            COALESCE(nd.is_pinned, 0) AS is_pinned
                        FROM post p
                        LEFT JOIN notice_detail nd ON p.id = nd.post_id
                        WHERE p.id = :id AND p.is_deleted = 0;";
                break;

            // 기본 게시판
            default:
                $sql = "SELECT p.id, p.writer_nickname AS nickname, p.title, p.content, p.created_at,
                            0 AS is_pinned
                        FROM post p
                        WHERE p.id = :id AND p.is_deleted = 0;";
                break;
        }

        $stmt = $this->db->runSql($sql, ['id' => $id]);
        $post = $stmt ? $stmt->fetch() : false;

        // 게시글이 존재하지 않거나 삭제된 경우 예외 처리
        if (!$post) {
            throw new PostNotFoundException(ErrorCode::POST_NOT_FOUND_READ->value);
        }

        // 2. 첨부파일 목록 조회
        $file_sql = "SELECT id, file_path, org_name
                    FROM post_file
                    WHERE post_id = :post_id;";

        $file_stmt = $this->db->runSql($file_sql, ['post_id' => $id]);
        
        $post['files'] = $file_stmt ? $file_stmt->fetchAll() : [];

        return $post;
    }

    /**
     * 게시글 작성
     */
    public function insertBoardPost(string $board_name, int $user_id, array $data, array $files = []): void
    {
        // 1. 회원 존재 여부 확인
        $user = $this->user->get($user_id);
        
        if (!$user) {
            throw new AuthenticationException();
        }

        // 닉네임 저장
        $writer_nickname = $user['nickname'];

        // 2. page_code 기반으로 site_menu_id 조회
        $menu_sql = "SELECT id FROM site_menu WHERE page_code = :page_code;";
        $menu_stmt = $this->db->runSql($menu_sql, ['page_code' => $board_name]);
        $menu = $menu_stmt ? $menu_stmt->fetch() : false;
        
        if (!$menu) {
            throw new NotFoundException(ErrorCode::BOARD_NOT_FOUND->value);
        }
        $site_menu_id = $menu['id'];
        
        // 3. 작성 권한 확인
        if (!$this->canWritePost($user_id, $board_name)) {
            throw new AuthorizationException(ErrorCode::ACCESS_DENIED->value);
        }

        // 4. 데이터베이스 트랜잭션 시작
        $this->db->beginTransaction();

        try {
            $post_sql = "INSERT INTO post (
                            site_menu_id, user_id, writer_nickname, title, content, 
                            thumbnail, is_deleted, created_at, updated_at
                        ) VALUES (
                            :site_menu_id, :user_id, :writer_nickname, :title, :content, 
                            :thumbnail, 0, NOW(), NOW()
                        );";

            $this->db->runSql($post_sql, [
                'site_menu_id'    => $site_menu_id,
                'user_id'         => $user_id,
                'writer_nickname' => $writer_nickname,
                'title'           => $data['title'],
                'content'         => $data['content'],
                'thumbnail'       => $data['thumbnail'] ?? null,
            ]);

            $post_id = $this->db->lastInsertId();

            // 게시판별 등록 분기
            if ($board_name === 'notice') {
                $notice_sql = "INSERT INTO notice_detail (post_id, is_pinned) 
                            VALUES (:post_id, :is_pinned);";
                
                $this->db->runSql($notice_sql, [
                    'post_id'   => $post_id,
                    'is_pinned' => $data['is_pinned'] ?? 0,
                ]);
            }

            // 첨부파일 등록
            if (!empty($files)) {
                $file_sql = "INSERT INTO post_file (post_id, file_path, org_name, created_at) 
                            VALUES (:post_id, :file_path, :org_name, NOW());";
                
                foreach ($files as $file) {
                    $this->db->runSql($file_sql, [
                        'post_id'   => $post_id,
                        'file_path' => $file['file_path'],
                        'org_name'  => $file['org_name'],
                    ]);
                }
            }

            $this->db->commit();
            
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e; 
        }
        
    }

    /**
     * 게시글 수정
     */
    public function updateBoardPost(
        string $board_name,
        int $post_id,
        int $user_id,
        array $data,
        array $files = [],
        array $delete_file_ids = []
    ): void
    {
        // 1. 회원 존재 여부 확인
        $user = $this->user->get($user_id);
        
        if (!$user) {
            throw new AuthenticationException();
        }

        // 2. 게시글 존재 여부 확인
        $post_owner = $this->findPostOwnerById($post_id);

        if (!$post_owner) {
            throw new PostNotFoundException(ErrorCode::POST_NOT_FOUND_UPDATE->value);
        }

        // 3. 수정 권한 체크
        if (!$this->canModifyPost($user_id, $post_owner, $board_name)) {
            throw new AuthorizationException(ErrorCode::ACCESS_DENIED->value);
        }

        // 4. 첨부파일 개수 확인
        $file_check_sql = "SELECT COUNT(*) 
                            FROM post_file
                           WHERE post_id = :post_id;";

        $current_file_count = $this->db->runSql($file_check_sql, [
            'post_id' => $post_id,
        ])->fetchColumn();

        $total_file_count = 
            $current_file_count
            - count($delete_file_ids)
            + count($files);

        if ($total_file_count > 3) {
            throw new Exception("첨부파일은 최대 3개까지 등록 가능합니다.");
        }

        // 5. 데이터베이스 트랜잭션 시작
        $this->db->beginTransaction();

        try {
            $post_sql = "UPDATE post
                         SET title = :title, content = :content, updated_at = NOW()
                         WHERE id = :id
                          AND is_deleted = 0;";
                        
            $this->db->runSql($post_sql, [
                'id'      => $post_id,
                'title'   => $data['title'],
                'content' => $data['content'],
            ]);
    
            // 게시판별 수정 분기
            if ($board_name === 'notice') {
                $notice_sql = "UPDATE notice_detail 
                               SET is_pinned = :is_pinned 
                                WHERE post_id = :post_id;";
                            
                $this->db->runSql($notice_sql, [
                    'post_id'   => $post_id,
                    'is_pinned' => $data['is_pinned'] ?? 0,
                ]);
            }
    
            // 첨부파일 삭제 처리
            if (!empty($delete_file_ids)) {
                // 삭제 대상 파일 조회
                $placeholders = [];
                $params = [
                    'post_id' => $post_id,
                ];

                foreach ($delete_file_ids as $index => $file_id) {
                    $key = 'file_id_' . $index;
                    // 예) :file_id_0, :file_id_1, ...
                    $placeholders[] = ':' . $key;
                    // 예) $params = [
                    //     'post_id' => 1,
                    //     'file_id_0' => 21,
                    //     'file_id_1' => 22
                    // ];
                    $params[$key] = $file_id;
                }

                $select_file_sql = "SELECT id, file_path
                                    FROM post_file
                                    WHERE post_id = :post_id
                                     AND id IN (" . implode(',', $placeholders) . ");";

                $file_stmt = $this->db->runSql($select_file_sql, $params);
                $delete_files = $file_stmt ? $file_stmt->fetchAll() : [];

                // 실제 파일 삭제
                foreach ($delete_files as $file) {
                    $full_path = APP_ROOT . '/' . $file['file_path'];

                    if (is_file($full_path)) {
                        unlink($full_path);
                    }
                }

                // DB 파일 정보 삭제
                $delete_sql = "DELETE FROM post_file
                               WHERE post_id = :post_id
                                AND id IN (" . implode(',', $placeholders) . ");";

                $this->db->runSql($delete_sql, $params);
            }

            // 새 첨부파일 추가
            if (!empty($files)) {
                $insert_file_sql = "INSERT INTO post_file (post_id, file_path, org_name, created_at)
                                    VALUES (:post_id, :file_path, :org_name, NOW());";

                foreach ($files as $file) {
                    $this->db->runSql($insert_file_sql, [
                        'post_id'   => $post_id,
                        'file_path' => $file['file_path'],
                        'org_name'  => $file['org_name'],
                    ]);
                }
            }
    
            $this->db->commit();

        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e; 
        }
    }

    /**
     * 게시글 삭제
     */
    public function deleteBoardPost(int $post_id, int $user_id): void
    {
        // 1. 회원 존재 여부 확인
        $user = $this->user->get($user_id);
        
        if (!$user) {
            throw new AuthenticationException();
        }

        // 2. 게시글 존재 여부 확인
        $post = $this->getPostBoardInfo($post_id);

        if (!$post) {
            throw new PostNotFoundException(ErrorCode::POST_NOT_FOUND_DELETE->value);
        }

        // 3. 삭제 권한 체크
        // 공지사항은 관리자만 삭제 가능
        if ($post['page_code'] === 'notice') {
            if (!$this->user->isAdmin($user_id)) {
                throw new AuthorizationException(ErrorCode::ACCESS_DENIED->value);
            }
        }
        // 이외의 게시판은 작성자 본인만 삭제 가능
        elseif ($post['user_id'] !== $user_id) {
            throw new AuthorizationException(ErrorCode::ACCESS_DENIED->value);
        }

        // 4. 게시글 상태 값 변경
        $sql = "UPDATE post
                SET is_deleted = 1,
                    deleted_at = NOW()
                WHERE id = :id
                 AND is_deleted = 0;";
        
        $this->db->runSql($sql, ['id' => $post_id]);
    }

    /**
     * 게시글 작성 가능 여부 확인
     */
    public function canWritePost(int $user_id, string $board_name): bool
    {
        // 공지사항은 관리자만 작성 가능
        if ($board_name === 'notice') {
            return $this->user->isAdmin($user_id);
        }

        return true;
    }

    /**
     * 게시글 수정/삭제 가능 여부 확인
     */
    public function canModifyPost(int $user_id, array $post_owner, string $board_name): bool
    {
        // 공지사항은 관리자만 수정 가능
        if ($board_name === 'notice') {
            return $this->user->isAdmin($user_id);
        }

        // 이외의 게시판은 작성자 본인만 수정 가능
        return (int)$post_owner['user_id'] === $user_id;
    }

    /**
     * 게시글의 소유자 정보 조회
     */
    public function findPostOwnerById(int $id): array|false
    {
        $sql = "SELECT id, user_id
                 FROM post
                WHERE id = :id
                 AND is_deleted = 0;";

        $stmt = $this->db->runSql($sql, [
            'id' => $id,
        ]);

        return $stmt ? $stmt->fetch() : false;
    }

    /**
     * 게시글의 사용자/메뉴 정보 조회
     */
    public function getPostBoardInfo(int $post_id): array|false
    {
        $sql = "SELECT p.user_id,
                       sm.menu_title,
                       sm.page_code
                 FROM post p
                JOIN site_menu sm
                 ON p.site_menu_id = sm.id
                WHERE p.id = :id
                 AND p.is_deleted = 0;";

        $stmt = $this->db->runSql($sql, [
            'id' => $post_id,
        ]);

        return $stmt ? $stmt->fetch() : false;
    }
}
