<?php
declare(strict_types = 1);

use BoardgameCafe\Controllers\UserController; // 신설한 컨트롤러 로드

$currentUserId = $_SESSION['id'] ?? null; 
if (!$currentUserId) {
    redirect('login/');
    exit;
}

$currentUser = $cms->getUser()->get($currentUserId); 
$user = $currentUser; // 기본값 세팅
$errors = [];

// 컨트롤러 인스턴스 생성
$userController = new UserController($cms);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 컨트롤러에 POST 데이터, 파일 데이터, 현재 유저 데이터를 통째로 넘겨 처리를 위임합니다.
    $result = $userController->updateProfile($_POST, $_FILES, $currentUser);

    if ($result['success']) {
        // 성공 시 마이페이지로 이동
        redirect('mypage/', [
            'status' => 'update_success'
        ]);
        exit;
    } else {
        // 실패 시 에러와 입력했던 데이터를 받아 화면에 다시 뿌려줌
        $errors = $result['errors'];
        $user = $result['user'];
    }
}

$data['user']   = $user;
$data['errors'] = $errors;

// 템플릿 렌더링
echo $twig->render('user-edit.html', $data);