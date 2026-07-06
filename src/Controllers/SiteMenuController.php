<?php
declare(strict_types = 1);

namespace BoardgameCafe\Controllers;

class SiteMenuController {
    private $cms;

    public function __construct($cms) {
        $this->cms = $cms;
    }

    /**
     * 전체 메뉴 목록을 [page_code => menu_title] 형태로 반환
     */
    public function getMenus(): array
    {
        // 1. 세션에 데이터가 있으면 그대로 반환
        if (isset($_SESSION['site_menus']) && is_array($_SESSION['site_menus'])) {
            return $_SESSION['site_menus'];
        }

        // 2. DB에서 필요한 데이터 조회
        $dbMenus = $this->cms->getSiteMenu()->getMenus();
        
        // 3. 데이터를 세션에 저장
        $_SESSION['site_menus'] = $dbMenus ? $dbMenus : [];

        return $_SESSION['site_menus'];
    }

    /**
     * 페이지 코드에 해당하는 단일 메뉴 반환
     */
    public function getMenuTitleByPageCode(string $pageCode): string
    {
        $menus = $this->getMenus();

        foreach ($menus as $menu) {
            if ($menu['page_code'] === $pageCode) {
                return $menu['menu_title'];
            }
        }

        return '';
    }
}