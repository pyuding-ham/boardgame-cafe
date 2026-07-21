<?php
declare(strict_types = 1);

namespace BoardgameCafe\CMS;

class Board
{
    protected $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * 게시판 목록 조회
     */
    public function getBoardList(string $page_code, int $limit, int $offset, array $filters = []): array
    {
        $params = ['page_code' => $page_code];
        
        // 1. 게시판별 분기 처리
        switch ($page_code) {
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
                $sql = "SELECT p.id, p.title, p.writer_nickname AS nickname, p.created_at,
                            0 AS is_pinned
                        FROM post p
                        INNER JOIN site_menu m ON p.site_menu_id = m.id";
                break;
        }

        // 2. 공통 조건 (삭제되지 않은 요청한 page_code에 해당하는 게시글만)
        $sql .= " WHERE m.page_code = :page_code AND p.is_deleted = 0";

        // 3. 공통 검색 필터 처리
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

        // 4. 공통 정렬 및 페이징
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
    public function getBoardArticle(string $page_code, int $id): array|bool
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
        $article = $stmt ? $stmt->fetch() : false;

        // 게시글이 존재하지 않거나 삭제된 경우 false 반환
        if (!$article) {
            return false;
        }

        // 2. 첨부파일 목록 조회
        $file_sql = "SELECT id, file_path, org_name
                    FROM post_file
                    WHERE post_id = :post_id;";

        $file_stmt = $this->db->runSql($file_sql, ['post_id' => $id]);
        
        $article['files'] = $file_stmt ? $file_stmt->fetchAll() : [];

        return $article;
    }

    /**
     * 게시글 쓰기
     */
    public function insertBoardArticle(string $page_code, array $data, array $files = []): int|string|bool
    {
        // 1. 로그인 여부 체크
        if (empty($data['user_id'])) {
            return false;
        }

        // 2. 공지사항 게시판일 때 관리자 여부 체크
        if ($page_code === 'notice') {
            $user_id = $data['user_id'];
            $role_sql = "SELECT role FROM user WHERE id = :id AND is_deleted = 0;";
            $role_stmt = $this->db->runSql($role_sql, ['id' => $user_id]);
            $user = $role_stmt ? $role_stmt->fetch() : false;

            if (!$user || $user['role'] !== 'ADMIN') {
                return false;
            }
        }

        // 3. page_code 기반으로 site_menu_id 조회
        $menu_sql = "SELECT id FROM site_menu WHERE page_code = :page_code;";
        $menu_stmt = $this->db->runSql($menu_sql, ['page_code' => $page_code]);
        $menu = $menu_stmt ? $menu_stmt->fetch() : false;
        
        if (!$menu) {
            return false; 
        }
        $site_menu_id = $menu['id'];

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
                'user_id'         => $data['user_id'],
                'writer_nickname' => $data['writer_nickname'],
                'title'           => $data['title'],
                'content'         => $data['content'],
                'thumbnail'       => $data['thumbnail'] ?? null,
            ]);

            $post_id = $this->db->lastInsertId();

            // 게시판별 등록 분기
            if ($page_code === 'notice') {
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
            return $post_id;
            
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e; 
        }
        
    }
}
