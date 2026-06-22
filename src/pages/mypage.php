<?php
declare(strict_types = 1);

// 비로그인 사용자 차단
if (!$currentUserId) {
    header("Location: " . DOC_ROOT . "login");
    exit;
}

// 기본 정보 조회
$userInfo = $cms->getUser()->get($currentUserId);

if (!$userInfo) {
    echo "<script>alert('존재하지 않는 회원입니다.'); location.href='" . DOC_ROOT . "';</script>";
    exit;
}

// 예약 상황 조회
$bookingList = []; // 더미 데이터 처리

// 내가 쓴 글 조회
$postList = []; // 더미 데이터 처리

$data['user'] = $userInfo;
$data['bookings'] = $bookingList;
$data['posts'] = $postList;

echo $twig->render('mypage.html', $data);