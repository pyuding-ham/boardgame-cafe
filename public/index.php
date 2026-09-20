<?php
declare(strict_types = 1);

use BoardgameCafe\Controllers\BoardController;
use BoardgameCafe\Exceptions\NotFoundException;
use BoardgameCafe\Exceptions\ErrorCode;

if (basename($_SERVER['SCRIPT_NAME']) === 'keep_alive.php') {
    return; 
}

include '../src/bootstrap.php';

$raw_uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$path = mb_strtolower($raw_uri, 'UTF-8');
$path = substr($path, strlen(DOC_ROOT));
$path = trim($path, '/'); 
$parts = explode('/', $path);

$boardController = new BoardController($cms);

// 상단 메뉴바 전역 변수 설정
$twig->addGlobal('menus', (new \BoardgameCafe\Controllers\SiteMenuController($cms))->getMenus());

if (str_contains($raw_uri, 'password-reset')) {
    $page = 'password-reset';
} elseif (str_contains($raw_uri, 'comment-list')) {
    $postId = $parts[1] ?? null;
    $page = 'comment-list';
} else {
    if ($parts[0] != 'admin') {
        $page = $parts[0] ? str_replace('.php', '', $parts[0]) : 'index';
        $urlParam = $parts[1] ?? null;
    
        if ($page === 'board') {
            // 기본 게시판 설정
            $boardName = $urlParam ?? 'notice';
            
            // 기본값은 1페이지
            $currentPage = 1;
            // 일반 게시판용 숫자 ID
            $postId = null;
            // 보드게임용 영문 ID (슬러그)
            $postSlug = null;
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
                
                // 게시글 식별자가 숫자인 경우
                if (is_numeric($identifier)) {
                    // 실제 게시글 존재 여부 확인
                    $isRealPostId = $boardController->isPostExists($identifier);

                    // 게시글 있는 경우
                    if ($isRealPostId) {
                        // 슬러그가 아닌 ID로 저장
                        $postId = (int)$identifier;
                    }
                    // 게시글 없는 경우
                    else {
                        // 영문 슬러그로 저장
                        $postSlug = (string)$identifier;
                    }
                }
                // 게시글 식별자가 문자인 경우
                else {
                    // 영문 슬러그로 저장
                    $postSlug = (string)$identifier;
                }
            // 4. 게시글 작성
            } elseif ($actionKeyword === 'write') {
                $boardAction = 'write';
            // 5. 게시글 수정
            } elseif ($actionKeyword === 'edit') {
                $boardAction = 'edit';

                $identifier = $parts[3] ?? null;

                // 게시글 식별자가 숫자인 경우
                if (is_numeric($identifier)) {
                    // 실제 게시글 존재 여부 확인
                    $isRealPostId = $boardController->isPostExists($identifier);

                    // 게시글 있는 경우
                    if ($isRealPostId) {
                        // 슬러그가 아닌 ID로 저장
                        $postId = (int)$identifier;
                    }
                    // 게시글 없는 경우
                    else {
                        if ($boardName === 'boardgame') {
                            // 영문 슬러그로 저장
                            $postSlug = (string)$identifier;
                            // 게시글 아이디 조회 후 저장
                            $postId = $boardController->getPostId($boardName, $identifier, 'edit');
                        }
                    }
                }
                // 게시글 식별자가 문자인 경우
                else {
                    if ($boardName === 'boardgame') {
                        // 영문 슬러그로 저장
                        $postSlug = (string)$identifier;
                        // 게시글 아이디 조회 후 저장
                        $postId = $boardController->getPostId($boardName, $identifier, 'edit');
                    }
                }
            // 6. 게시글 삭제
            } elseif ($actionKeyword === 'delete') {
                $boardAction = 'delete';

                $identifier = $parts[3] ?? null;

                // 게시글 식별자가 숫자인 경우
                if (is_numeric($identifier)) {
                    // 실제 게시글 존재 여부 확인
                    $isRealPostId = $boardController->isPostExists($identifier);

                    // 게시글 있는 경우
                    if ($isRealPostId) {
                        // 슬러그가 아닌 ID로 저장
                        $postId = (int)$identifier;
                    }
                    // 게시글 없는 경우
                    else {
                        if ($boardName === 'boardgame') {
                            // 영문 슬러그로 저장
                            $postSlug = (string)$identifier;
                            // 게시글 아이디 조회 후 저장
                            $postId = $boardController->getPostId($boardName, $identifier, 'delete');
                        }
                    }
                }
                // 게시글 식별자가 문자인 경우
                else {
                    if ($boardName === 'boardgame') {
                        // 영문 슬러그로 저장
                        $postSlug = (string)$identifier;
                        // 게시글 아이디 조회 후 저장
                        $postId = $boardController->getPostId($boardName, $identifier, 'delete');
                    }
                }
            }
        }
    } else {
        $admin_page = isset($parts[1]) ? str_replace('.php', '', $parts[1]) : '';
        $page = 'admin/' . $admin_page;
    }
}

$php_page = APP_ROOT . '/src/pages/' . $page . '.php';

if (!file_exists($php_page)) {
    throw new NotFoundException(ErrorCode::PAGE_NOT_FOUND->value);
}

include $php_page;

// 장시간 미사용 시 자동 로그아웃
if (isset($_SESSION['id']) && file_exists($php_page)) {
    $login_target_url = rtrim(DOC_ROOT, '/') . '/login';
    $keep_alive_url = rtrim(DOC_ROOT, '/') . '/keep_alive.php';
    ?>
    <script>
    (function() {
        const SESSION_TIMEOUT = <?php echo (int) SESSION_LIFETIME * 1000; ?>;
        const EXTEND_INTERVAL = 5 * 60 * 1000;

        let lastActivityTime = Date.now();
        let lastKeepAliveTime = Date.now();
        let lastMouseX = null;
        let lastMouseY = null;
        let isLoggingOut = false;

        // 자동 로그아웃
        function logoutByTimeout() {
            if (isLoggingOut) {
                return;
            }
            isLoggingOut = true;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?php echo $login_target_url; ?>';

            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'status';
            hiddenInput.value = 'session_expired';

            form.appendChild(hiddenInput);
            document.body.appendChild(form);
            form.submit();
        }

        // 로그인 상태 연장
        function markActivity() {
            // 마지막 활동 시간 저장
            lastActivityTime = Date.now();

            // 이벤트 발생 때마다가 아닌 정해진 시간마다 쿠키 수명을 늘려주는 파일 실행
            if (lastActivityTime - lastKeepAliveTime > EXTEND_INTERVAL) {
                // 마지막으로 언제 실행했는지 시간 저장
                lastKeepAliveTime = lastActivityTime;
                fetch('<?php echo $keep_alive_url; ?>').catch(function() {});
            }
        }

        window.addEventListener('mousemove', function(e) {
            // 실제 사람이 발생시킨 이벤트인지 확인
            if (!e.isTrusted) {
                return;
            }
            // 첫 마우스 이벤트인지 확인
            if (lastMouseX === null) {
                lastMouseX = e.clientX;
                lastMouseY = e.clientY;
                return;
            }
            // 가로/세로 이동량을 합쳐서 10px이 넘는지 확인
            if (Math.abs(e.clientX - lastMouseX) + Math.abs(e.clientY - lastMouseY) < 10) {
                return;
            }
            lastMouseX = e.clientX;
            lastMouseY = e.clientY;
            markActivity();
        });

        document.addEventListener('keydown', function(e) {
            // 실제 사람이 발생시킨 이벤트인지 확인
            if (!e.isTrusted) {
                return;
            }
            markActivity();
        }, true);

        // 자동 로그아웃 여부 1초마다 확인
        setInterval(function() {
            if (Date.now() - lastActivityTime >= SESSION_TIMEOUT) {
                logoutByTimeout();
            }
        }, 1000);
    })();
    </script>
    <?php
}
