<?php
declare(strict_types = 1);

use BoardgameCafe\Validate\Validate;

$password = [];
$errors = [];
$id = null;
$has_token = false;

$token = $_GET['token'] ?? '';

if ($token) {
    // 토큰이 있는 경우 (이메일로 접근)
    $id = $cms->getToken()->getUserId($token, 'password_reset');
    if(!$id) {
        $errors['invalid_token'] = true;
    } else {
        $has_token = true;
        $cms->getSession()->delete();
    }
} else {
    // 토큰이 없는 경우 (마이페이지에서 접근)
    $id = $_SESSION['id'] ?? null;

    if (!$id) {
        redirect('login/');
        exit;
    }
}

if (empty($errors['invalid_token']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password['current'] = trim($_POST['current_password'] ?? '');
    $password['new'] = trim($_POST['password'] ?? '');
    $password['confirm']  = trim($_POST['confirm'] ?? '');

    // 현재 비밀번호 필수 입력 값 검사 (마이페이지)
    if (!$has_token && empty($password['current'])) {
        $errors['current_password'] = '현재 비밀번호를 입력해 주세요.';
    }

    // 변경할 비밀번호 유효성 검사
    if (empty($password['new'])) {
        $errors['password'] = '변경 비밀번호를 입력해 주세요.';
    } elseif (!Validate::isPassword($password['new'])) {
        $errors['password'] = '변경 비밀번호는 최소 10자 이상이어야 하며 영문과 숫자를 모두 포함해야 합니다.';
    }

    // 변경할 비밀번호 확인 유효성 검사 및 비밀번호 확인 일치 여부 체크
    if (empty($password['confirm'])) {
        $errors['confirm'] = '변경 비밀번호 확인을 입력해 주세요.';
    } elseif ($password['new'] !== $password['confirm']) {
        $errors['confirm'] = '변경 비밀번호와 변경 비밀번호 확인이 일치하지 않습니다.';
    }

    if (empty($errors)) {
        $user_password = $cms->getUser()->getPassword($id);

        if ($user_password) {
            // 입력한 현재 비밀번호가 맞는지 확인 (마이페이지)
            if (!$has_token && !password_verify($password['current'], $user_password)) {
                $errors['current_password'] = '현재 비밀번호가 일치하지 않습니다.';
            }
            
            // 변경할 비밀번호가 현재 비밀번호와 동일한지 확인
            if (password_verify($password['new'], $user_password)) {
                $errors['password'] = '현재 사용 중인 비밀번호와 동일한 비밀번호로는 변경할 수 없습니다.';
            }
        } else {
            $errors['message'] = '사용자 정보를 불러올 수 없습니다.';
        }
    }

    if (empty($errors)) {
        $isUpdated = $cms->getUser()->passwordUpdate($id, $password['new']);

        if ($isUpdated) {
            // 비밀번호 변경 성공 데이터베이스 로그 기록
            $changeMethod = $has_token ? 'email' : 'mypage';
            $cms->getUser()->writePasswordChangeLog($id, $changeMethod);

            if ($user && !empty($user['email'])) {
                $subject = '[보드트립] 비밀번호가 성공적으로 변경되었습니다.';
                $body = '안녕하세요. 보드게임카페 보드트립입니다.<br><br>' .
                        '회원님의 비밀번호가 ' . date('Y-m-d H:i:s') . '에 정상적으로 변경되었습니다.<br>' .
                        '만약 본인이 비밀번호를 변경하지 않았다면, 즉시 관리자 메일(' . $email_config['admin_email'] . ')로 문의해 주시기 바랍니다.';
                
                try {
                    $email = new \BoardgameCafe\Email\Email($email_config);
                    $email->sendEmail($user['email'], $subject, $body, $email_config['admin_email']);

                } catch (\Exception $e) {
                    error_log('[비밀번호 변경 안내메일 발송 실패] 대상: ' . $user['email'] . ' / 에러: ' . $e->getMessage());
                }
            }

            if ($has_token) {
                $cms->getToken()->delete($token);
            }

            $cms->getSession()->delete();
            redirect('login/', ['status' => 'reset_success']);
            exit;

        } else {
            $errors['message'] = '비밀번호 변경 중 데이터베이스 오류가 발생했습니다. 잠시 후 다시 시도해 주세요.';
        }
    }
}

$data['password'] = $password;
$data['errors'] = $errors;
$data['has_token'] = $has_token;

echo $twig->render('password-reset.html', $data);