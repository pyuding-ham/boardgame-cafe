<?php
declare(strict_types = 1);

use BoardgameCafe\Validate\Validate;

$user = [];
$errors = [];

// 로그인 사용자 차단
if ($currentUserId) {
    header("Location: " . DOC_ROOT);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. 입력값 받기
    $user['username'] = trim($_POST['username'] ?? '');
    $user['email'] = trim($_POST['email'] ?? '');
    $user['password'] = $_POST['password'] ?? '';
    $user['confirm'] = $_POST['confirm'] ?? '';

    // 2. 필수 입력 값 검사 및 유효성 검사 및 중복 검사
    if (empty($user['username'])) {
        $errors['username'] = '아이디를 입력해 주세요.';
    } elseif (!Validate::isUsername($user['username'])) {
        $errors['username'] = '아이디는 4~20자의 영문, 숫자, 언더바(_)만 가능합니다.';
    }  elseif ($cms->getUser()->isUsernameExists($user['username'])) { 
        $errors['username'] = '이미 다른 회원이 사용 중인 아이디입니다.';
    }

    if (empty($user['email'])) {
        $errors['email'] = '이메일을 입력해 주세요.';
    } elseif (!Validate::isEmail($user['email'])) {
        $errors['email'] = '올바른 이메일 주소를 입력해 주세요.';
    }  elseif ($cms->getUser()->isEmailExists($user['email'])) { 
        $errors['email'] = '이미 다른 회원이 사용 중인 이메일입니다.';
    }

    if (empty($user['password'])) {
        $errors['password'] = '비밀번호를 입력해 주세요.';
    } elseif (!Validate::isPassword($user['password'])) {
        $errors['password'] = '비밀번호는 최소 10자 이상이어야 하며 영문과 숫자를 모두 포함해야 합니다.';
    }

    if (empty($user['confirm'])) {
        $errors['confirm'] = '비밀번호 확인을 입력해 주세요.';
    } elseif ($user['password'] !== $user['confirm']) {
        $errors['confirm'] = '비밀번호와 비밀번호 확인이 일치하지 않습니다.';
    }

    $invalid = implode($errors);

    // 3. 에러가 없다면 DB 저장
    if (!$invalid) {
        // 아이디 중복 검사
        if ($cms->getUser()->isUsernameExists($user['username'])) {
            $errors['username'] = '이미 사용 중인 아이디입니다.';
        }
        // 이메일 중복 검사
        if ($cms->getUser()->isEmailExists($user['email'])) {
            $errors['email'] = '이미 사용 중인 이메일입니다.';
        }

        if (empty($errors)) {
            $nickname = $cms->getUser()->register(
                $user['username'],
                $user['password'],
                $user['email']
            );
            
            redirect('register-success/', [
                'status' => 'register_success',
                'nickname' => $nickname,
            ]);
            exit;
        }
    }
}

$data['user']   = $user;
$data['errors'] = $errors;

echo $twig->render('register.html', $data);