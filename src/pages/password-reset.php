<?php
declare(strict_types = 1);

use BoardgameCafe\Validate\Validate;

$errors = [];

$token = $_GET['token'] ?? '';
if (!$token) {
    redirect('login/');
    exit;
}

$id = $cms->getToken()->getUserId($token, 'password_reset');

if(!$id) {
    redirect('login/', [
        'warning' => '만료되거나 올바르지 않은 링크입니다. 비밀번호 찾기를 다시 시도해 주세요.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm'] ?? '');

    // 비밀번호 유효성 검사
    if (!Validate::isPassword($password)) {
        $errors['password'] = '비밀번호는 최소 10자 이상이어야 하며 영문과 숫자를 모두 포함해야 합니다.';
    }
    
    // 비밀번호와 비밀번호 확인 일치 여부 체크
    if ($password !== $confirm) {
        $errors['confirm'] = '비밀번호와 비밀번호 확인이 일치하지 않습니다.';
    }

    if (!empty($errors)) {
        $errors['message'] = '입력하신 비밀번호 정보를 다시 확인해 주세요.';
        
    } else {
        $isUpdated = $cms->getUser()->passwordUpdate($id, $password);

        if ($isUpdated) {
            $user = $cms->getUser()->get($id);
            
            if ($user && !empty($user['email'])) {
                $subject = '[보드게임카페] 비밀번호가 성공적으로 변경되었습니다.';
                $body = '안녕하세요. 보드게임카페입니다.<br><br>' .
                        '회원님의 비밀번호가 ' . date('Y-m-d H:i:s') . '에 정상적으로 변경되었습니다.<br>' .
                        '만약 본인이 비밀번호를 변경하지 않았다면, 즉시 관리자 메일(' . $email_config['admin_email'] . ')로 문의해 주시기 바랍니다.';
                
                try {
                    $email = new \BoardgameCafe\Email\Email($email_config);
                    $email->sendEmail($user['email'], $subject, $body, $email_config['admin_email']);

                } catch (\Exception $e) {
                    error_log('[비밀번호 변경 안내메일 발송 실패] 대상: ' . $user['email'] . ' / 에러: ' . $e->getMessage());
                }
            }

            $cms->getToken()->delete($token);

            redirect('login/', ['success' => '비밀번호가 성공적으로 변경되었습니다. 새로운 비밀번호로 로그인해 주세요.']);
        } else {
            $errors['message'] = '비밀번호 변경 중 데이터베이스 오류가 발생했습니다. 잠시 후 다시 시도해 주세요.';
        }
    }
}