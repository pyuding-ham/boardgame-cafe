<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$errors = [];
$username = '';

// 비밀번호 재설정에서 보낸 상태 저장
$status = $_SESSION['_flash_status'] ?? null;
if ($status) {
    unset($_SESSION['_flash_status']);
}

// 메시지는 새로고침 시 사라짐
unset($_SESSION['_flash_success'], $_SESSION['_flash_warning']);

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $errors['login'] = '아이디와 비밀번호를 모두 입력해 주세요.';
    }

    if (empty($errors)) {
        $user = $cms->getUser()->login($username, $password);

        if ($user) {
            $cms->getSession()->create($user);

            header('Location: ' . DOC_ROOT);
            exit;
        } else {
            $errors['login'] = '아이디 또는 비밀번호가 일치하지 않습니다.';
        }
    }
}

$data['errors'] = $errors;
$data['username'] = $username;
$data['status'] = $status;

echo $twig->render('login.html', $data);