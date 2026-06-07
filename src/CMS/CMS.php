<?php
namespace BoardgameCafe\CMS;

class CMS
{
    protected $db = null;
    protected $member = null;
    protected $session = null;

    public function __construct($dsn, $username, $password)
    {
        $this->db = new Database($dsn, $username, $password);
    }

    public function getDb() {
        return $this->db;
    }

    public function getMember() {
        if ($this->member === null) {
            $this->member = new Member($this->db);
        }
        return $this->member;
    }

    public function getSession() {
        if ($this->session === null) {
            $this->session = new Session();
        }
        return $this->session;
    }
}