<?php
$user = isset($_SESSION['user_role']) ? $_SESSION : null;
$is_logged_in = isset($user['user_role']);

$data = [
    'user' => $user,
    'is_logged_in' => $is_logged_in,
];

echo $twig->render('index.html', $data);