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
     * 공지사항 목록 조회
     */
    public function getNoticeList(int $limit, int $offset, array $filters = []): array {
        $sql = "SELECT id, title, is_pinned, created_at, '관리자' AS nickname 
                FROM notice n
                WHERE 1=1";
        
        $params = [];

        // 검색 필터
        if (isset($filters['keyword']) && trim($filters['keyword']) !== '') {
            $keyword_value = '%' . trim($filters['keyword']) . '%';

            if ($filters['type'] === 'title') {
                // 제목으로 검색
                $sql .= " AND n.title LIKE :keyword_title";
                $params['keyword_title'] = $keyword_value;
            } elseif ($filters['type'] === 'content') {
                // 내용으로 검색
                $sql .= " AND n.content LIKE :keyword_content";
                $params['keyword_content'] = $keyword_value;
            } else {
                // 전체 검색
            $sql .= " AND (n.title LIKE :keyword_title OR n.content LIKE :keyword_content)";
                $params['keyword_title'] = $keyword_value;
                $params['keyword_content'] = $keyword_value;
            }
        }

        $sql .= " ORDER BY n.is_pinned DESC, n.id DESC";
        $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset . ";";

        return $this->db->runSql($sql, $params)->fetchAll();
    }

    /**
     * 총 공지사항 개수
     */
    public function getNoticeTotalCount(array $filters = []): int {
        $sql = "SELECT COUNT(id) FROM notice WHERE 1=1";
        $params = [];
        
        if (isset($filters['keyword']) && trim($filters['keyword']) !== '') {
            $keyword_value = '%' . trim($filters['keyword']) . '%';

            if ($filters['type'] === 'title') {
                // 제목으로 검색
                $sql .= " AND title LIKE :keyword_title";
                $params['keyword_title'] = $keyword_value;
            } elseif ($filters['type'] === 'content') {
                // 내용으로 검색
                $sql .= " AND content LIKE :keyword_content";
                $params['keyword_content'] = $keyword_value;
            } else {
                // 전체 검색
                $sql .= " AND (title LIKE :keyword_title OR content LIKE :keyword_content)";
                $params['keyword_title'] = $keyword_value;
                $params['keyword_content'] = $keyword_value;
            }
        }

        $stmt = $this->db->runSql($sql, $params);
        return $stmt ? (int)$stmt->fetchColumn() : 0;
    }

    /**
     * 공지사항 단일 게시글 상세 조회
     */
    public function getNoticeArticle(int $id): array|bool
    {
        $sql = "SELECT n.id, n.title, n.content, n.is_pinned, n.created_at, '관리자' AS nickname
                FROM notice n
                JOIN user u ON n.user_id = u.id
                WHERE n.id = :id;";

        $stmt = $this->db->runSql($sql, ['id' => $id]);
        $article = $stmt ? $stmt->fetch() : false;

        // 게시글이 존재하지 않으면 false 반환
        if (!$article) {
            return false;
        }

        // 첨부파일 목록 조회
        $file_sql = "SELECT id, file_path, org_name
                     FROM notice_file
                     WHERE notice_id = :notice_id;";

        $file_stmt = $this->db->runSql($file_sql, ['notice_id' => $id]);
        
        $article['files'] = $file_stmt ? $file_stmt->fetchAll() : [];

        return $article;
    }
}