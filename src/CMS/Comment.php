<?php
declare(strict_types = 1);

namespace BoardgameCafe\CMS;

use BoardgameCafe\Exceptions\PostNotFoundException;
use BoardgameCafe\Exceptions\AuthenticationException;
use BoardgameCafe\Exceptions\ErrorCode;

class Comment
{
    protected $db;
    private Board $board;
    private User $user;
    
    public function __construct(
        Database $db,
        Board $board,
        User $user
    ) {
        $this->db = $db;
        $this->board = $board;
        $this->user = $user;
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

    /**
     * 댓글 작성
     */
    public function insertPostComment(string $post_id, string $comment, int $user_id): int
    {
        // 1. 회원 존재 여부 확인
        $user = $this->user->get($user_id);
        
        if (!$user) {
            throw new AuthenticationException();
        }

        // 닉네임 저장
        $writer_nickname = $user['nickname'];

        // 2. 댓글 삽입
        $post_sql = "INSERT INTO post_comment (
                       post_id,
                       user_id,
                       writer_nickname,
                       content,
                       is_deleted,
                       created_at,
                       updated_at
                    )
                    VALUES (
                      :post_id,
                      :user_id,
                      :writer_nickname,
                      :content,
                      0,
                      NOW(),
                      NOW()
                    );";

        $this->db->runSql($post_sql, [
            'post_id'         => $post_id,
            'user_id'         => $user_id,
            'writer_nickname' => $writer_nickname,
            'content'         => $comment,
        ]);

        $comment_id = $this->db->lastInsertId();

        return (int)$comment_id;
    }
}
