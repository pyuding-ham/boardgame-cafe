<?php
// 1. 애플리케이션 루트 경로 정의
define('APP_ROOT', dirname(__FILE__), 2);

// 2. 핵심 파일 및 라이브러리 로드
require APP_ROOT . '/src/functions.php';
require APP_ROOT . '/config/config.php';
require APP_ROOT . '/vendor/autoload.php';

// 3. 에러 및 세션/타임존 기본 설정
if (DEV === false) {
    set_exception_handler('handle_exception');
    set_error_handler('handle_error');
    register_shutdown_function('handle_shutdown');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Seoul');
mb_internal_encoding('UTF-8');

// 4. 데이터베이스 객체 생성
/** @var string $dsn */
/** @var string $username */
/** @var string $password */
$db = new \BoardgameCafe\CMS\Database($dsn, $username, $password);
unset($dsn, $username, $password);

// 5. Twig 템플릿 엔진 설정
$twig_options['cache'] = (DEV === true) ? false : APP_ROOT . '/var/cache';
$twig_options['debug'] = DEV;

$loader = new Twig\Loader\FilesystemLoader(APP_ROOT . '/templates');
$twig = new Twig\Environment($loader, $twig_options);

// Twig 전역 변수 등록
$twig->addGlobal('doc_root', DOC_ROOT);
$twig->addGlobal('session', $_SESSION);

if (DEV === true) {
    $twig->addExtension(new \Twig\Extension\DebugExtension());
}

// 6. HTMLPurifier 보안 객체 생성
$purifierConfig = \HTMLPurifier_Config::createDefault();
$purifierConfig->set('Core.Encoding', 'UTF-8');
$purifierConfig->set('HTML.Allowed', 'p,b,i,strong,em,span,img[src|alt|width|height],br,ul,ol,li');
$purifier = new \HTMLPurifier($purifierConfig);