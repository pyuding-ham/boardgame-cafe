<?php
namespace BoardgameCafe\CMS;

class Member
{
    protected $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * ID로 단일 회원 정보 조회
     */
    public function get(int $id)
    {
        $sql = "SELECT id, username, nickname, email, profile_image, role, created_at
                  FROM users
                WHERE id = :id;";
        $stmt = $this->db->runSql($sql, ['id' => $id]);
        return $stmt ? $stmt->fetch() : false;
    }

    /**
     * 로그인 처리를 위해 username으로 회원 정보 조회
     */
    public function login(string $username, string $password)
    {
        $sql = "SELECT id, username, nickname, email, profile_image, role, created_at
                  FROM users
                WHERE username = :username;";
        $stmt = $this->db->runSql($sql, ['username' => $username]);
        $member = $stmt ? $stmt->fetch() : false;

        // 회원이 존재하고 비밀번호가 일치하는지 확인
        if ($member && password_verify($password, $member['password'])) {
            // 인증 성공 시 회원 데이터 반환
            return $member;
        }

        // 인증 실패 시 false 반환
        return false;
    }
}