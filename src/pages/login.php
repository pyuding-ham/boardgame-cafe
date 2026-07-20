<?php
$errors = [];
$username = '';

// 로그인 사용자 차단
if ($currentUserId) {
    header("Location: " . DOC_ROOT);
    exit;
}

// 비밀번호 재설정, index(장시간 미사용 로그아웃)에서 보낸 상태 저장
$status = $_POST['status'] ?? $_SESSION['_flash_status'] ?? null;

// 장시간 미사용 시 로그아웃
if ($status === 'login_required') {
    $cms->getSession()->delete();
    $data['is_logged_in'] = false;
}

if ($status) {
    unset($_SESSION['_flash_status']);
}

// 메시지는 새로고침 시 사라짐
unset($_SESSION['_flash_success'], $_SESSION['_flash_warning']);

if($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['status'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $errors['login'] = '아이디와 비밀번호를 모두 입력해 주세요.';
    }

    if (empty($errors)) {
        $user_id = $cms->getUser()->getIdByUsername($username); 

        if ($user_id) {
            // 비밀번호를 10분 내 5회 이상 틀렸는지 검사
            $fail_count = $cms->getUser()->checkLoginAttempts($user_id);
            if ($fail_count >= 5) {
                $errors['login'] = '5회 연속 로그인 실패로 인해 10분간 접속이 차단되었습니다.';
            }
        }

        if (empty($errors)) {
            $user = $cms->getUser()->login($username, $password);

            if ($user) {
                $cms->getSession()->create($user);

                // 로그인 성공 로그 기록
                $cms->getUser()->writeLoginLog($user['id'], 'success');
    
                header('Location: ' . DOC_ROOT);
                exit;
            } else {
                $errors['login'] = '아이디 또는 비밀번호가 일치하지 않습니다.';

                // 로그인 실패 로그 기록
                $target_id = ($user_id > 0) ? $user_id : null;
                $fail_reason = ($user_id > 0) ? 'invalid password' : 'user not found';
                $cms->getUser()->writeLoginLog($target_id, 'fail', $fail_reason);
            }
        }
    }
}

$data['errors'] = $errors;
$data['username'] = $username;
$data['status'] = $status;

echo $twig->render('login.html', $data);