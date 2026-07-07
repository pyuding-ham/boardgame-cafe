<?php
declare(strict_types = 1);

if (basename($_SERVER['SCRIPT_NAME']) === 'keep_alive.php') {
    return; 
}

include '../src/bootstrap.php';

$raw_uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$path = mb_strtolower($raw_uri, 'UTF-8');
$path = substr($path, strlen(DOC_ROOT));
$path = trim($path, '/'); 
$parts = explode('/', $path);

if ($parts[0] != 'admin') {
    $page = $parts[0] ? str_replace('.php', '', $parts[0]) : 'index';
    $urlParam = $parts[1] ?? null;

    if ($page === 'board') {
        // 기본 게시판 설정
        $boardName = $urlParam ?? 'notice';
        
        // 기본값은 1페이지
        $currentPage = 1;
        // 일반 게시판용 숫자 ID
        $articleId = null;
        // 보드게임용 영문 ID (슬러그)
        $articleSlug = null;
        // 기본값은 목록 보기
        $boardAction = 'list';

        // page/view/write
        $actionKeyword = $parts[2] ?? null;

        // 1. 값이 없으면 게시판 목록
        if ($actionKeyword === null) {
            $boardAction = 'list';
        // 2. 게시판 목록 페이징 주소
        } elseif ($actionKeyword === 'page') {
            $boardAction = 'list';
            $currentPage = isset($parts[3]) ? (int)$parts[3] : 1;
        // 3. 게시글 상세
        } elseif ($actionKeyword === 'view') {
            $boardAction = 'view';
            $identifier = $parts[3] ?? null;
            
            // 숫자가 들어오면 ID, 문자가 들어오면 영문 슬러그로 저장
            if (is_numeric($identifier)) {
                $articleId = (int)$identifier;
            } else {
                $articleSlug = $identifier; 
            }
        // 4. 게시글 작성
        } elseif ($actionKeyword === 'write') {
            $boardAction = 'write';
        }
    }
} else {
    $admin_page = isset($parts[1]) ? str_replace('.php', '', $parts[1]) : '';
    $page = 'admin/' . $admin_page;
}

$php_page = APP_ROOT . '/src/pages/' . $page . '.php';

if (!file_exists($php_page)) {
    $php_page = APP_ROOT . '/src/pages/page-not-found.php';
}

// 상단 메뉴바 전역 변수 설정
$twig->addGlobal('menus', (new \BoardgameCafe\Controllers\SiteMenuController($cms))->getMenus());

include $php_page;

// 장시간 미사용 시 자동 로그아웃
if (isset($_SESSION['id']) && file_exists($php_page) && $php_page !== APP_ROOT . '/src/pages/page-not-found.php') {
    // /board/notice와 같은 경로의 경우 결과 값 1
    // /board/notice/view/1과 같은 경로의 경우 결과 값 3
    $depth = count(array_filter($parts)) - 1;

    // /board/notice와 같은 경로의 경우 "../" 로 자동 변환
    // board/notice/view/1과 같은 경로의 경우 "../../../" 로 자동 변환
    $path_prefix = $depth > 0 ? str_repeat('../', $depth) : '';
    
    // 상대 경로 주소를 생성
    $login_target_url = $path_prefix . 'login';
    $keep_alive_url = $path_prefix . 'keep_alive.php';
    ?>
    <script>
    (function() {
        // 24분
        const SESSION_TIMEOUT = 24 * 60 * 1000;
        // 5분
        const EXTEND_INTERVAL = 5 * 60 * 1000;  

        let lastActivityTime = Date.now();
        let logoutTimer;

        function startLogoutTimer() {
            // 유저의 새로운 활동을 감지할 때마다 기존 타이머 삭제
            clearTimeout(logoutTimer);
            
            logoutTimer = setTimeout(() => {
                // 1. 메모리 상에 가짜 <form> 태그 생성
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?php echo $login_target_url; ?>';

                // 2. 메모리 상에 가짜 <input> 태그 생성
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'status';
                hiddenInput.value = 'login_required';

                // 3. 폼에 인풋을 넣고, <body>에 붙여서 전송
                form.appendChild(hiddenInput);
                document.body.appendChild(form);
                form.submit();

            }, SESSION_TIMEOUT);
        }

        // 유저 활동 감지 시 동작
        function handleUserActivity() {
            const now = Date.now();
            startLogoutTimer();

            // 마지막 활동 시간으로부터 5분이 지나면 실행
            if (now - lastActivityTime > EXTEND_INTERVAL) {
                lastActivityTime = now;
                fetch('<?php echo $keep_alive_url; ?>').catch(err => {});
            }
        }
        window.addEventListener('mousemove', handleUserActivity);
        window.addEventListener('click', handleUserActivity);
        window.addEventListener('keydown', handleUserActivity);
        window.addEventListener('scroll', handleUserActivity);
        window.addEventListener('DOMContentLoaded', startLogoutTimer);
    })();
    </script>
    <?php
}