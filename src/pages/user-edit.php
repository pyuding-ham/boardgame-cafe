<?php
declare(strict_types = 1);

use BoardgameCafe\Controllers\UserController;

$currentUserId = $_SESSION['id'] ?? null; 
if (!$currentUserId) {
    redirect('login/');
    exit;
}

$currentUser = $cms->getUser()->get($currentUserId); 
$user = $currentUser;
$errors = [];

$userController = new UserController($cms);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $userController->updateProfile($_POST, $_FILES, $currentUser);
    
    if ($result['success']) {
        redirect('mypage/', [
            'status' => 'update_success'
        ]);
        exit;
    } else {
        $errors = $result['errors'];
        $user = $result['user'];
    }
}

$data['user']   = $user;
$data['errors'] = $errors;

// 템플릿 렌더링
echo $twig->render('user-edit.html', $data);