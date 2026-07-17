<?php
// 1. 세션이 시작되지 않았다면 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// 로그인한 상태면 회원 ID 저장
$currentUserId = $_SESSION['id'] ?? null;

// 2. 애플리케이션 루트 경로 정의
define('APP_ROOT', dirname(__FILE__, 2));

// 3. 핵심 파일 및 라이브러리 로드
require APP_ROOT . '/src/functions.php';
require APP_ROOT . '/config/config.php';
require APP_ROOT . '/vendor/autoload.php';

// 4. 에러 및 세션/타임존 기본 설정
if (DEV === false) {
    if (!function_exists('handle_exception')) {
        function handle_exception($exception) {

            error_log(
                $exception->getMessage() .
                " in " .
                $exception->getFile() .
                ":" .
                $exception->getLine()
            );

            http_response_code(500);

            echo "<h1>서비스 이용에 불편을 드려 죄송합니다.</h1>";
            echo "<p>일시적인 오류가 발생했습니다. 잠시 후 다시 시도해 주세요.</p>";

            exit;
        }
    }

    if (!function_exists('handle_error')) {
        function handle_error($error_level, $error_message, $error_file, $error_line) {

            if (!(error_reporting() & $error_level)) {
                return false;
            }

            throw new ErrorException(
                $error_message,
                0,
                $error_level,
                $error_file,
                $error_line
            );
        }
    }

    if (!function_exists('handle_shutdown')) {
        function handle_shutdown() {

            $error = error_get_last();

            if (
                $error !== null &&
                in_array($error['type'], [
                    E_ERROR,
                    E_CORE_ERROR,
                    E_COMPILE_ERROR,
                    E_PARSE
                ])
            ) {

                error_log(
                    $error['message'] .
                    " in " .
                    $error['file'] .
                    ":" .
                    $error['line']
                );

                http_response_code(500);

                echo "<h1>서비스 이용에 불편을 드려 죄송합니다.</h1>";
                echo "<p>일시적인 오류가 발생했습니다. 잠시 후 다시 시도해 주세요.</p>";
            }
        }
    }

    set_exception_handler('handle_exception');
    set_error_handler('handle_error');
    register_shutdown_function('handle_shutdown');
}

date_default_timezone_set('Asia/Seoul');
mb_internal_encoding('UTF-8');

// 5. 데이터베이스 객체 생성
$cms = new \BoardgameCafe\CMS\CMS($dsn, $username, $password);
unset($dsn, $username, $password);

// 6. Twig 템플릿 엔진 설정
$twig_options['cache'] = (DEV === true) ? false : APP_ROOT . '/var/cache';
$twig_options['debug'] = DEV;

$loader = new Twig\Loader\FilesystemLoader(APP_ROOT . '/templates');
$twig = new Twig\Environment($loader, $twig_options);

// Twig 전역 변수 등록
$twig->addGlobal('doc_root', DOC_ROOT);
$twig->addGlobal('session', $cms->getSession());
$twig->addGlobal('is_logged_in', isset($_SESSION['id']));
$twig->addGlobal('user_role', $_SESSION['role'] ?? 'public');

if (DEV === true) {
    $twig->addExtension(new \Twig\Extension\DebugExtension());
}

// 7. HTMLPurifier 보안 객체 생성
$purifierConfig = \HTMLPurifier_Config::createDefault();
$purifierConfig->set('Core.Encoding', 'UTF-8');
$purifierConfig->set('HTML.Allowed', 'p,b,i,strong,em,span,img[src|alt|width|height],br,ul,ol,li');
$purifier = new \HTMLPurifier($purifierConfig);