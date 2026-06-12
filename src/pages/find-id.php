<?php
declare(strict_types = 1);

use BoardgameCafe\Validate\Validate;

$errors = [];
$email = '';
$found_username = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $errors['find'] = '이메일을 입력해 주세요.';
        
    } elseif (!Validate::isEmail($email)) {
        $errors['find'] = '올바른 이메일 주소를 입력해 주세요.';
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

$data = [
    'errors' => $errors,
    'email' => $email,
    'found_username' => $found_username,
];

echo $twig->render('find-id.html', $data);