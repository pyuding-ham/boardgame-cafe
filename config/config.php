<?php
// .env 파일을 읽어서 환경변수에 등록하는 함수
function loadEnv($envPath) {
    if (!file_exists($envPath) || !is_readable($envPath)) {
        die(".env 환경설정 파일이 존재하지 않습니다.");
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) {
            continue;
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        $value = trim($value, '"\'');

        putenv(sprintf('%s=%s', $name, $value));
        $_ENV[$name] = $value;
    }
}

loadEnv(__DIR__ . '/../.env');


// 1. 개발 모드 및 도메인 정의
$isDev = filter_var($_ENV['DEV'] ?? false, FILTER_VALIDATE_BOOLEAN);
define('DEV', $isDev);
define('DOMAIN', $_ENV['DOMAIN'] ?? 'http://localhost');

// 2. 디렉토리 경로 최적화
define('ROOT_PATH', dirname(__DIR__, 1)); // 최상위 루트 폴더 경로
define('ROOT_FOLDER', 'public');
define('DOC_ROOT', '/');

if (DEV) {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// 3. 데이터베이스 설정
$type     = $_ENV['DB_TYPE'] ?? 'mysql';
$server   = $_ENV['DB_SERVER'] ?? 'localhost';
$db       = $_ENV['DB_NAME'] ?? '';
$port     = $_ENV['DB_PORT'] ?? '3306';
$charset  = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
$username = $_ENV['DB_USERNAME'] ?? '';
$password = $_ENV['DB_PASSWORD'] ?? '';

$dsn = "$type:host=$server;dbname=$db;port=$port;charset=$charset";

// 4. SMTP 서버 설정
$email_config = [
    'server'      => $_ENV['SMTP_SERVER'] ?? 'smtp.naver.com',
    'port'        => $_ENV['SMTP_PORT'] ?? '587',
    'username'    => $_ENV['SMTP_USERNAME'] ?? '',
    'password'    => $_ENV['SMTP_PASSWORD'] ?? '',
    'security'    => 'tls',
    'admin_email' => $_ENV['SMTP_USERNAME'] ?? '',
    'debug'       => 0,
];

// 5. 파일 업로드 설정
define('MEDIA_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('FILE_EXTENSIONS', ['jpeg', 'jpg', 'png', 'gif', 'webp']);
define('MAX_SIZE', 2097152);

// 업로드 절대경로 (public/uploads/ 폴더로 고정)
define('UPLOADS_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . ROOT_FOLDER . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);

// 6. 외부 API 및 다국어 설정
// define('KAKAO_MAP_API_KEY', '');
define('DEFAULT_LANG', 'ko');
