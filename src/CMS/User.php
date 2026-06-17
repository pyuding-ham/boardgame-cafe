<?php
namespace BoardgameCafe\CMS;

use Exception;

class User
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
     * 이메일로 회원 번호 조회
     */
    public function getIdByEmail(string $email)
    {
        $sql = "SELECT id
                  FROM user
                WHERE email = :email;";

        return $this->db->runSql($sql, ['email' => $email])->fetchColumn();
    }

    /**
     * 이메일로 아이디 찾기
     */
    public function getUsernameByEmail(string $email)
    {
        $sql = "SELECT username FROM user WHERE email = :email;";
        
        $stmt = $this->db->runSql($sql, ['email' => $email]);

        $user = $stmt ? $stmt->fetch() : false;

        return $user ? $user['username'] : false;
    }

    /**
     * 로그인 처리를 위해 username으로 회원 정보 조회
     */
    public function login(string $username, string $password)
    {
        $sql = "SELECT id, username, password, nickname, email, profile_image, role, created_at
                  FROM user
                WHERE username = :username;";

        $stmt = $this->db->runSql($sql, ['username' => $username]);

        $user = $stmt ? $stmt->fetch() : false;

        // 회원이 존재하고 비밀번호가 일치하는지 확인
        if ($user && password_verify($password, $user['password'])) {
            // 인증 성공 시 회원 데이터 반환
            return $user;
        }

        // 인증 실패 시 false 반환
        return false;
    }

    /**
     * 회원가입 시 데이터 저장
     */
    public function register(string $username, string $password, string $email): array|string|bool
    {
        $isDuplicated = $this->checkDuplicate($username, $email);

        if ($isDuplicated['username'] === true || $isDuplicated['email'] === true) {
            return $isDuplicated;
        }
        
        // 비밀번호 암호화 및 등록
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $adjectives = ['달리는', '행복한', '귀여운', '노래하는', '반짝이는', '말랑한'];
        $animals = ['햄스터', '토끼', '고양이', '쿼카', '고래', '댕댕이'];
        $baseNickname = $adjectives[array_rand($adjectives)] . ' ' . $animals[array_rand($animals)];
        $randomNickname = $baseNickname . '_' . rand(1, 999);

        $sql = "INSERT INTO user (username, password, nickname, email)
                VALUES (:username, :password, :nickname, :email);";

        $arguments = [
            'username' => $username,
            'password' => $hashed_password,
            'nickname' => $randomNickname,
            'email'    => $email,
        ];

        $result = $this->db->runSql($sql, $arguments);

        if ($result === false) {
            throw new Exception('회원가입 처리 중 데이터베이스 오류가 발생');
        }

        return $randomNickname;
    }

    /**
     * 회원가입 시 아이디와 이메일 중복 체크
     */
    public function checkDuplicate($username, $email): array
    {
        $result = [
            'username' => false,
            'email' => false,
        ];

        $sqlCheck = "SELECT username, email FROM user WHERE username = :username OR email = :email;";
        $stmtCheck = $this->db->runSql($sqlCheck, [
            'username' => $username,
            'email' => $email,
        ]);

        if ($stmtCheck) {
            while ($row = $stmtCheck->fetch()) {
                if ($row['username'] === $username) {
                    // 아이디 중복 발생
                    $result['username'] = true;
                }
                if ($row['email'] === $email) {
                    // 이메일 중복 발생
                    $result['email'] = true;
                }
            }
        }
        
        return $result;
    }

    public function passwordUpdate(int $id, string $password): bool
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "UPDATE user
                SET password = :password
                WHERE id = :id;";

        try {
            $stmt = $this->db->runSql($sql, [
                'id' => $id,
                'password' => $hash,
            ]);

            if ($stmt && $stmt->rowCount() > 0) {
                return true;
            }
            
            return false;

        } catch (\Exception $e) {
            error_log('[비밀번호 변경 실패] 회원 ID: ' . $id . ' / 에러 내용: ' . $e->getMessage());
            return false;
        }
       
    }
}