<?php
declare(strict_types = 1);

$currentUserId = $_SESSION['id'] ?? null; 
if ($currentUserId) {
    header('Location: ' . DOC_ROOT . 'login');
    exit;
}

echo $twig->render('withdraw-complete.html');
