<?php

$errors = [];
$username = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $errors['login'] = '아이디와 비밀번호를 모두 입력해 주세요.';
    }

    if (empty($errors)) {
        $member = $cms->getMember()->login($username, $password);

        if ($member) {
            // 세션 하이재킹 방지를 위한 세션 ID 재발급
            session_regenerate_id(true);

            $_SESSION['user_id'] = $member['id'];
            $_SESSION['username'] = $member['username'];
            $_SESSION['nickname'] = $member['nickname'];
            $_SESSION['role'] = $member['role'];

            header('Location: ' . DOC_ROOT);
            exit;
        } else {
            $errors['login'] = '아이디 또는 비밀번호가 일치하지 않습니다.';
        }
    }
}

echo $twig->render('login.html', [
    'errors' => $errors,
    'username' => $username
]);