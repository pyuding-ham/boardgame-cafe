<?php
declare(strict_types = 1);

// 비로그인 사용자 차단
if (!isset($_SESSION['id'])) {
    header("Location: " . DOC_ROOT . "login");
    exit;
}

// 마이페이지 id와 세션 id가 다르면 차단
$target_id = $id ? $id : $_SESSION['id'];

if ($target_id !== $_SESSION['id']) {
    echo "<script>alert('잘못된 접근입니다.'); location.href='" . DOC_ROOT . "';</script>";
    exit;
}

// 기본 정보 조회
$userInfo = $cms->getUser()->get($id);

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