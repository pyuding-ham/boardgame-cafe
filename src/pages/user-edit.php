<?php
declare(strict_types = 1);

use BoardgameCafe\Validate\Validate;

$currentUserId = $_SESSION['id'] ?? null; 
if (!$currentUserId) {
    redirect('login/');
    exit;
}

$user = $currentUser = $cms->getUser()->get($currentUserId); 
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. 입력값 받기
    $user['nickname'] = trim($_POST['nickname'] ?? '');
    $user['email']    = trim($_POST['email'] ?? '');
    $user['id']       = $currentUserId;

    $isNicknameChanged = ($user['nickname'] !== $currentUser['nickname']);
    $isEmailChanged    = ($user['email'] !== $currentUser['email']);

    // 2. 필수 입력 값 검사 및 유효성 검사 및 중복 검사
    if (empty($user['nickname'])) {
        $errors['nickname'] = '닉네임을 입력해 주세요.';
    } elseif (!Validate::isText($user['nickname'], 2, 10)) {
        $errors['nickname'] = '닉네임은 2~10자 사이여야 합니다.';
    }  elseif ($isNicknameChanged && $cms->getUser()->isNicknameExists($user['nickname'], $currentUserId)) { 
        $errors['nickname'] = '이미 다른 회원이 사용 중인 닉네임입니다.';
    }

    if (empty($user['email'])) {
        $errors['email'] = '이메일을 입력해 주세요.';
    } elseif (!Validate::isEmail($user['email'])) {
        $errors['email'] = '올바른 이메일 주소를 입력해 주세요.';
    } elseif ($isEmailChanged && $cms->getUser()->isEmailExists($user['email'], $currentUserId)) { 
        $errors['email'] = '이미 다른 회원이 사용 중인 이메일입니다.';
    }

    if (!$isNicknameChanged && !$isEmailChanged) {
        $errors['message'] = "변경된 정보가 없습니다.";
    }

    $invalid = implode($errors);

    // 3. 에러가 없다면 DB 저장
    if (!$invalid) {
        try {
            $cms->getUser()->update($user);

            redirect('mypage/' . $user['id'], [
                'status' => 'update_success'
            ]);
            exit;

        } catch (\Exception $e) {
            // 닉네임 중복
            if (str_contains($e->getMessage(), '닉네임')) {
                $errors['nickname'] = $e->getMessage();
            // 이메일 중복
            } elseif (str_contains($e->getMessage(), '이메일')) {
                $errors['email'] = $e->getMessage();
            // 시스템 오류
            } else {
                $errors['system'] = $e->getMessage();
            }
        }
    }
}

$data['user']   = $user;
$data['errors'] = $errors;

// 템플릿 렌더링
echo $twig->render('user-edit.html', $data);
