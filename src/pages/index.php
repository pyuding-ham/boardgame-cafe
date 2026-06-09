<?php
$user = isset($_SESSION['role']) ? $_SESSION : null;
$is_logged_in = isset($user['role']);

$data = [
    'user' => $user,
    'is_logged_in' => $is_logged_in,
];

echo $twig->render('index.html', $data);