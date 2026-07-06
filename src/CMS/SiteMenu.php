<?php
namespace BoardgameCafe\CMS;

class SiteMenu
{
    protected $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * 화면 표시용 전체 메뉴 목록 조회 (정렬 순서 적용)
     */
    public function getMenus(): array|false
    {
        $sql = "SELECT page_code, menu_title, page_url
                FROM site_menu
                WHERE is_exposed = 1 
                ORDER BY sort_order ASC;";

        $stmt = $this->db->runSql($sql);

        if ($stmt) {
            return $stmt->fetchAll();
        }

        return false;
    }

    /**
     * 특정 페이지 코드(page_code)로 단일 메뉴 정보 조회
     */
    public function getMenuTitleByPageCode(string $pageCode): array|false
    {
        $sql = "SELECT page_code, menu_title
                FROM site_menu
                WHERE page_code = :page_code
                AND is_exposed = 1;";

        $stmt = $this->db->runSql($sql, ['page_code' => $pageCode]);

        if ($stmt) {
            return $stmt->fetch();
        }

        return false;
    }
}