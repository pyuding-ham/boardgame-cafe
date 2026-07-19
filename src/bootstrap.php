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

// 4. 타임존 및 인코딩 기본 설정
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

// 4. 에러 설정
if (!function_exists('handle_exception')) {
    function handle_exception($exception)
    {
        global $twig;

        error_log(
            $exception->getMessage() .
            " in " .
            $exception->getFile() .
            ":" .
            $exception->getLine()
        );

        http_response_code(500);

        // 개발 환경에 따른 분기
        if (defined('DEV') && DEV === true) {
            echo "<div style='padding: 20px; background: #fff0f0; border: 1px solid #ffcccc; color: #a00; font-family: monospace;'>";
            echo "<h2>[Development Mode] 예외 발생</h2>";
            echo "<p><b>Message:</b> " . htmlspecialchars($exception->getMessage()) . "</p>";
            echo "<p><b>File:</b> " . $exception->getFile() . ":" . $exception->getLine() . "</p>";
            echo "<h3>Stack Trace:</h3>";
            echo "<pre style='background: #fafafa; padding: 10px; border: 1px solid #ddd; overflow: auto;'>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
            echo "</div>";
        } else {
            try {
                echo $twig->render('errors/500.html');
            } catch (\Throwable $e) {
                echo "<h1>서비스 이용에 불편을 드려 죄송합니다.</h1>";
                echo "<p>잠시 후 다시 시도해 주세요.</p>";
            }
        }
        exit(1);
    }
}

if (!function_exists('handle_error')) {
    function handle_error($error_level, $error_message, $error_file, $error_line)
    {
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
    function handle_shutdown()
    {
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
            // 기존 출력 버퍼를 비움
            if (ob_get_length()) ob_end_clean();

            handle_exception(new ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            ));
        }
    }
}

set_exception_handler('handle_exception');
set_error_handler('handle_error');
register_shutdown_function('handle_shutdown');

// 7. HTMLPurifier 보안 객체 생성
$purifierConfig = \HTMLPurifier_Config::createDefault();
$purifierConfig->set('Core.Encoding', 'UTF-8');
$purifierConfig->set('HTML.Allowed', 'p,b,i,strong,em,span,img[src|alt|width|height],br,ul,ol,li');
$purifier = new \HTMLPurifier($purifierConfig);
