<?php
declare(strict_types = 1);

use BoardgameCafe\Validate\Validate;

$user = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 2. 입력값 받기 및 XSS 방지
    $user['username'] = trim($_POST['username'] ?? '');
    $user['nickname'] = $purifier->purify(trim($_POST['nickname'] ?? ''));
    $user['email'] = trim($_POST['email'] ?? '');
    $user['password'] = $_POST['password'];
    $confirm = $_POST['confirm'] ?? '';

    // 2. Validate 클래스를 이용한 유효값 검사
    $errors['username'] = Validate::isUsername($user['username'])
        ? '' : '아이디는 4~20자의 영문, 숫자, 언더바(_)만 가능합니다';

    $errors['nickname'] = Validate::isText($user['nickname'], 2, 10)
        ? '' : '닉네임은 2~10자 사이여야 합니다.';

    $errors['email'] = Validate::isEmail($user['email'])
        ? '' : '올바른 이메일 주소를 입력해 주세요.';

    $errors['password'] = Validate::isPassword($user['password'])
        ? '' : '비밀번호는 최소 10자 이상이어야 하며 영문과 숫자를 모두 포함해야 합니다.';

    $errors['confirm'] = ($user['password'] === $confirm)
        ? '' : '비밀번호와 비밀번호 확인이 일치하지 않습니다.';

    $invalid = implode($errors);

    // 3. 에러가 없다면 DB 저장
    if (!$invalid) {
        try {
            $result = $cms->getUser()->register(
                $user['username'],
                $user['password'],
                $user['nickname'],
                $user['email'],
            );

            if (is_array($result)) {
                if ($result['username'] === true) {
                    $errors['username'] = '이미 사용 중인 아이디입니다.';
                }
                if ($result['email'] === true) {
                    $errors['email'] = '이미 사용 중인 이메일입니다.';
                }
            } else {
                // 회원가입 성공 시 페이지 이동
                redirect('register-success/', [
                    'status' => 'register_success',
                    'nickname' => $user['nickname'],
                ]);
                exit;
            }

        } catch (Exception $e) {
            $errors['system'] = $e->getMessage();
        }
        
    }
}

$data['user']   = $user;
$data['errors'] = $errors;

echo $twig->render('register.html', $data);