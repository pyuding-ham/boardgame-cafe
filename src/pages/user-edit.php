<?php
declare(strict_types = 1);

use BoardgameCafe\Validate\Validate;

$currentUserId = $_SESSION['id'] ?? null; 
if (!$currentUserId) {
    redirect('login/');
    exit;
}

$user = $cms->getUser()->get($currentUserId); 
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. 입력값 받기
    $user['nickname'] = trim($_POST['nickname'] ?? '');
    $user['email']    = trim($_POST['email'] ?? '');
    $user['id']       = $currentUserId;

    // 2. Validate 클래스를 이용한 유효값 검사
    $errors['nickname'] = Validate::isUsername($user['nickname']) 
        ? '' : '닉네임은 4~20자의 영문, 숫자, 언더바(_)만 가능합니다';

    $errors['email'] = Validate::isEmail($user['email'])
        ? '' : '올바른 이메일 주소를 입력해 주세요.';

    $invalid = implode($errors);

    // 3. 에러가 없다면 DB 저장
    if (!$invalid) {
        try {
            $cms->getUser()->update($user);

            redirect('mypage/', [
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
