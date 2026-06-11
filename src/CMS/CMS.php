<?php
namespace BoardgameCafe\CMS;

class CMS
{
    protected $db = null;
    protected $user = null;
    protected $session = null;

    public function __construct($dsn, $username, $password)
    {
        $this->db = new Database($dsn, $username, $password);
    }

    public function getDb() {
        return $this->db;
    }

    public function getUser() {
        if ($this->user === null) {
            $this->user = new user($this->db);
        }
        return $this->user;
    }

    public function getSession() {
        if ($this->session === null) {
            $this->session = new Session();
        }
        return $this->session;
    }
}