<?php
$user = isset($_SESSION['id']) ? $_SESSION : null;

$data = [
    'user' => $user,
];

echo $twig->render('index.html', $data);