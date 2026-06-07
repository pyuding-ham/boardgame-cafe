<?php
// 1. 개발 모드 및 도메인 정의
define('DEV', true);
define('DOMAIN', 'http://localhost:8888');

// 2. 디렉토리 경로 최적화
define('ROOT_PATH', dirname(__DIR__, 1)); // 최상위 루트 폴더 경로
define('ROOT_FOLDER', 'public');
define('DOC_ROOT', '/' . ROOT_FOLDER . '/');

if (DEV) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// 3. 데이터베이스 설정
$type = 'mysql';
$server = 'localhost';
$db = 'boardgame_cafe';
$port = '3306';
$charset = 'utf8mb4';
$username = 'web_user';
$password = '12345';

$dsn = "$type:host=$server;dbname=$db;port=$port;charset=$charset";

// 4. 파일 업로드 설정
define('MEDIA_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('FILE_EXTENSIONS', ['jpeg', 'jpg', 'png', 'gif', 'webp']);
define('MAX_SIZE', 2097152);

// 업로드 절대경로 (public/uploads/ 폴더로 고정)
define('UPLOADS_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . ROOT_FOLDER . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);

// 5. 외부 API 및 다국어 설정
// define('KAKAO_MAP_API_KEY', '');
define('DEFAULT_LANG', 'ko');