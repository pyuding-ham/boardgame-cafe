<?php
declare(strict_types = 1);

use BoardgameCafe\Validate\Validate;

$error = '';
$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = '이메일을 입력해 주세요.';

    } elseif (!Validate::isEmail($email)) {
        $error = '올바른 이메일 형식이 아닙니다.';
    }
    
    if ($error === '') {
        $id = $cms->getUser()->getIdByEmail($email);
        
        if ($id) {
            // 회원 정보가 있을 때만 토큰 생성 및 메일 발송
            $token   = $cms->getToken()->create($id, 'password_reset');
            $link    = DOMAIN . DOC_ROOT . 'password-reset.php?token=' . $token;
            
            // 한국어 이메일 설정
            $subject = '[보드트립] 비밀번호 재설정 링크 안내';
            $body    = '안녕하세요. 보드게임카페 보드트립입니다.<br><br>' .
                       '아래 링크를 클릭하시면 비밀번호 재설정 페이지로 이동합니다.<br>' .
                       '<a href="' . $link . '" target="_blank">' . $link . '</a><br><br>' .
                       '본인이 요청하지 않은 경우 이 메일을 무시해 주세요.';
            
            try {
                $mail = new \BoardgameCafe\Email\Email($email_config);
                $mail->sendEmail($email_config['admin_email'], $email, $subject, $body);
                $sent = true;
            } catch (\Exception $e) {
                error_log('[메일 발송 실패] 대상: ' . $email . ' / 에러 내용: ' . $e->getMessage());
                $error = '메일 발송 중 오류가 발생했습니다. 잠시 후 다시 시도해 주세요.';
            }
        } else {
            $error = '해당 이메일로 가입된 회원 정보가 없습니다.';
        }
    }
}

$data['error'] = $error;
$data['sent']  = $sent;

echo $twig->render('password-lost.html', $data);