<?php
namespace BoardgameCafe\CMS;

class Token
{
    protected $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * 토큰 생성
     */
    public function create(int $id, string $purpose): string
    {
        $arguments['token'] = bin2hex(random_bytes(64));
        $arguments['user_id'] = $id;
        $arguments['expires'] = date("Y-m-d H:i:s", strtotime('+30 minutes'));
        $arguments['purpose'] = $purpose;

        $sql = "INSERT INTO token (token, user_id, expires, purpose)
                VALUES (:token, :user_id, :expires, :purpose);";

        $this->db->runSql($sql, $arguments);

        return $arguments['token'];
    }

    /**
     * 토큰 삭제
     */
    public function delete(string $token): bool
    {
        $sql = "DELETE FROM token
                WHERE token = :token;";

        $stmt = $this->db->runSql($sql, [
            'token' => $token,
        ]);

        if ($stmt && $stmt->rowCount() > 0) {
            return true;
        }

        return false;
    }

    /**
     * 토큰 유효성 체크
     */
    public function getUserId(string $token, string $purpose): ?int
    {
        $sql = "SELECT user_id
                  FROM token
                WHERE token = :token AND purpose = :purpose
                  AND expires > NOW();";

        return $this->db->runSql($sql, [
            'token' => $token,
            'purpose' => $purpose,
        ])->fetchColumn();
    }
}
