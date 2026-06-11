<?php
declare(strict_types = 1);

$errors = [];
$email = '';
$found_username = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $errors['find'] = '이메일을 입력해 주세요.';
    }

    if (empty($errors)) {
        $username = $cms->getUser()->getUsernameByEmail($email);

        if ($username) {
            $found_username = $username;
        } else {
            $errors['find'] = '해당 이메일로 가입된 회원 정보가 없습니다.';
        }
    }
}

echo $twig->render('find-id.html', [
    'errors' => $errors,
    'email' => $email,
    'found_username' => $found_username,
]);