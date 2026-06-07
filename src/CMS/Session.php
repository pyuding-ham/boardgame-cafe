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
    public function create($member)
    {
        // 세션 하이재킹 방지
        session_regenerate_id(true);

        $_SESSION['id'] = $member['id'];
        $_SESSION['username'] = $member['username'];
        $_SESSION['nickname'] = $member['nickname'];
        $_SESSION['role'] = $member['role'];
    }
}