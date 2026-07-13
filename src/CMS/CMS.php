<?php
namespace BoardgameCafe\CMS;

class CMS
{
    protected $db = null;
    protected $user = null;
    protected $session = null;
    protected $token = null;
    protected $board = null;
    protected $siteMenu = null;

    public function __construct($dsn, $username, $password)
    {
        $this->db = new Database($dsn, $username, $password);
    }

    public function getDb() {
        return $this->db;
    }

    public function getUser() {
        if ($this->user === null) {
            $this->user = new User($this->db);
        }
        return $this->user;
    }

    public function getSession() {
        if ($this->session === null) {
            $this->session = new Session();
        }
        return $this->session;
    }

    public function getToken() {
        if ($this->token === null) {
            $this->token = new Token($this->db);
        }
        return $this->token;
    }

    public function getBoard() {
        if ($this->board === null) {
            $this->board = new Board($this->db);
        }
        return $this->board;
    }

    public function getSiteMenu() {
        if ($this->siteMenu === null) {
            $this->siteMenu = new SiteMenu($this->db);
        }
        return $this->siteMenu;
    }
}