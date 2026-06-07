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
                  FROM user
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
                  FROM user
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

    /**
     * 회원가입 시 데이터 저장
     */
    public function register(string $username, string $password, string $nickname, string $email): bool
    {
        // 아이디 중복 체크
        $sqlCheck = "SELECT id FROM user WHERE username = :username;";
        $stmtCheck = $this->db->runSql($sqlCheck, ['username' => $username]);
        if ($stmtCheck && $stmtCheck->fetch()) {
            return false; // 이미 존재하는 아이디
        }
        
        // 비밀번호 암호화 및 등록
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO user (username, password, nickname, email)
                VALUES (:username, :password, :nickname, :email;";

        $argument = [
            'username' => $username,
            'password' => $hashed_password,
            'nickname' => $nickname,
            'email'    => $email
        ];

        $result = $this->db->runSql($sql, $argument);
        return $result !== false;
    }
}