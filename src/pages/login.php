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
            $cms->getSession()->create($member);

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