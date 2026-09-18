<?php
declare(strict_types = 1);

use BoardgameCafe\Validate\Validate;

$errors = [];
$form = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'content' => '',
    'privacy_agree' => false,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['name'] = trim($_POST['name'] ?? '');
    $form['email'] = trim($_POST['email'] ?? '');
    $form['phone'] = trim($_POST['phone'] ?? '');
    $form['content'] = trim($_POST['content'] ?? '');
    $form['privacy_agree'] = isset($_POST['privacy_agree']);

    if (empty($form['name'])) {
        $errors['name'] = '이름을 입력해 주세요.';
    } elseif (!Validate::isText($form['name'], 1, 20)) {
        $errors['name'] = '이름은 최대 20자까지 입력할 수 있습니다.';
    }

    if (empty($form['email'])) {
        $errors['email'] = '이메일을 입력해 주세요.';
    } elseif (!Validate::isEmail($form['email'])) {
        $errors['email'] = '올바른 이메일 주소를 입력해 주세요.';
    }

    if (!empty($form['phone']) && !Validate::isPhone($form['phone'])) {
        $errors['phone'] = '올바른 연락처를 입력해 주세요.';
    }

    if (empty($form['content'])) {
        $errors['content'] = '문의내용을 입력해 주세요.';
    } elseif (!Validate::isText($form['content'], 1, 1000)) {
        $errors['content'] = '문의내용은 최대 1000자까지 입력할 수 있습니다.';
    }

    if (!$form['privacy_agree']) {
        $errors['privacy_agree'] = '개인정보 수집 및 이용에 동의해 주세요.';
    }

    if (empty($errors)) {
        $phoneText = !empty($form['phone']) ? $form['phone'] : '없음';
        $subject = '[보드트립] ' . $form['name'] . '님의 메일문의';
        $body = '- 이름: ' . htmlspecialchars($form['name'], ENT_QUOTES, 'UTF-8') . "\n"
            . '- 이메일: ' . htmlspecialchars($form['email'], ENT_QUOTES, 'UTF-8') . "\n"
            . '- 연락처: ' . htmlspecialchars($phoneText, ENT_QUOTES, 'UTF-8') . "\n"
            . '- 문의내용: ' . htmlspecialchars($form['content'], ENT_QUOTES, 'UTF-8');

        $mail = new \BoardgameCafe\Email\Email($email_config);

        if ($mail->sendEmail($email_config['admin_email'], $subject, $body, $email_config['admin_email'])) {
            redirect('/', [
                'status' => 'contact_us_success',
            ]);
        }

        $errors['system'] = '메일 발송 중 오류가 발생했습니다. 잠시 후 다시 시도해 주세요.';
    }
}

$data['errors'] = $errors;
$data['form'] = $form;

echo $twig->render('contact-us.html', $data);
