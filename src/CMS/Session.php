<?php
namespace BoardgameCafe\CMS;

class Session
{
    public $id;
    public $username;
    public $nickname;
    public $role;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->id = $_SESSION['id'] ?? 0;
        $this->username = $_SESSION['username'] ?? '';
        $this->nickname = $_SESSION['nickname'] ?? '';
        $this->role = $_SESSION['role'] ?? 'public';
    }

    /**
     * 로그인 성공 시 새로운 세션 생성
     */
    public function create($user)
    {
        // 세션 하이재킹 방지
        session_regenerate_id(true);

        $_SESSION['id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nickname'] = $user['nickname'];
        $_SESSION['role'] = $user['role'];
    }

    /**
     * 회원정보 변경 시 세션 갱신
     */
    public function update($user)
    {
        $this->create($user);
    }

    /**
     * 로그아웃 시 세션 및 쿠키 파기
     */
    public function delete()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            $param = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 2400,
                $param['path'],
                $param['domain'],
                $param['secure'],
                $param['httponly']
            );
            session_destroy();
        }
    }
}