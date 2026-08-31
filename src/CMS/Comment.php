<?php
declare(strict_types = 1);

namespace BoardgameCafe\CMS;

use BoardgameCafe\Exceptions\PostNotFoundException;
use BoardgameCafe\Exceptions\ErrorCode;

class Comment
{
    protected $db;
    private Board $board;
    
    public function __construct(
        Database $db,
        Board $board
    ) {
        $this->db = $db;
        $this->board = $board;
    }

    /**
     * 댓글 상세 조회
     */
    public function getPostComment(int $post_id, ?int $user_id = null): array|false
    {
        $post = $this->board->isPostExists($post_id);
        
        // 게시글이 존재하지 않거나 삭제된 경우 예외 처리
        if (!$post) {
            throw new PostNotFoundException(ErrorCode::POST_NOT_FOUND_READ->value);
        }

        $sql = "SELECT
                  c.id,
                  c.post_id,
                  c.user_id,
                  c.writer_nickname AS nickname,
                  c.content,
                  c.created_at,
                  u.profile_image,
                  IF(c.user_id = :user_id, 1, 0) AS is_inserted
                FROM post_comment c
                INNER JOIN post p
                  ON c.post_id = p.id
                LEFT JOIN user u
                  ON c.user_id = u.id
                WHERE c.post_id = :post_id
                  AND c.is_deleted = 0
                ORDER BY c.created_at;";

        $params = [
            'post_id' => $post_id,
            'user_id' => $user_id,
        ];

        return $this->db->runSql($sql, $params)->fetchAll();
    }
}