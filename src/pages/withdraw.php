<?php
declare(strict_types = 1);

use BoardgameCafe\Controllers\UserController;

$currentUserId = $_SESSION['id'] ?? null; 
if (!$currentUserId) {
    header('Location: ' . DOC_ROOT . 'login');
    exit;
}

$user = $cms->getUser()->getNicknameById($currentUserId); 
$errors = [];

$userController = new UserController($cms);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $userController->withdraw($currentUserId);
    
    if ($result['success']) {
        redirect('withdraw-complete/', [
            'status' => 'update_success'
        ]);
        exit;
    } else {
        $errors = $result['errors'];
    }
}

$data['user']   = $user;
$data['errors'] = $errors;

echo $twig->render('withdraw.html', $data);
