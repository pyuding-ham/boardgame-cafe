<?php
// $user = $cms->getSession()->create('member') ?? null;

$data = [
    'title' => '보드게임 카페 홈',
    // 'user' => $user,
];

echo $twig->render('index.html', $data);
