<?php
declare(strict_types = 1);

namespace BoardgameCafe\Controllers;

use Exception;
use BoardgameCafe\Validate\Validate;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class UserController 
{
    private $cms;

    public function __construct($cms) 
    {
        $this->cms = $cms;
    }

    /**
     * 회원 정보 및 프로필 이미지 변경
     */
    public function updateProfile(array $postData, array $fileData, array $currentUser): array
    {
        $errors = [];
        $user = $currentUser;

        // 1. 입력값 받기
        $user['nickname'] = trim($postData['nickname'] ?? '');
        $user['email']    = trim($postData['email'] ?? '');

        $isNicknameChanged = ($user['nickname'] !== $currentUser['nickname']);
        $isEmailChanged    = ($user['email'] !== $currentUser['email']);
        $isImageUploaded   = (isset($fileData['profile_image']['tmp_name']) && !empty($fileData['profile_image']['tmp_name']));

        // 2. 필수 입력 값 검사 및 유효성 검사 및 중복 검사
        if (empty($user['nickname'])) {
            $errors['nickname'] = '닉네임을 입력해 주세요.';
        } elseif (!Validate::isText($user['nickname'], 2, 10)) {
            $errors['nickname'] = '닉네임은 2~10자 사이여야 합니다.';
        } elseif ($isNicknameChanged && $this->cms->getUser()->isNicknameExists($user['nickname'], $currentUser['id'])) { 
            $errors['nickname'] = '이미 다른 회원이 사용 중인 닉네임입니다.';
        }

        if (empty($user['email'])) {
            $errors['email'] = '이메일을 입력해 주세요.';
        } elseif (!Validate::isEmail($user['email'])) {
            $errors['email'] = '올바른 이메일 주소를 입력해 주세요.';
        } elseif ($isEmailChanged && $this->cms->getUser()->isEmailExists($user['email'], $currentUser['id'])) { 
            $errors['email'] = '이미 다른 회원이 사용 중인 이메일입니다.';
        }

        // 아무것도 변경되지 않았을 때
        if (!$isNicknameChanged && !$isEmailChanged && !$isImageUploaded) {
            $errors['message'] = "변경된 정보가 없습니다.";
            return ['success' => false, 'errors' => $errors, 'user' => $user];
        }

        // 에러가 있다면 조기 리턴
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'user' => $user];
        }

        // 3. 이미지 업로드 처리 (Intervention Image)
        $maxFileSize = 1 * 1024 * 1024; 
        if ($fileData['profile_image']['size'] > $maxFileSize) {
            $errors['profile_image'] = '프로필 이미지는 최대 1MB까지만 업로드할 수 있습니다.';
            return ['success' => false, 'errors' => $errors, 'user' => $user];
        }

        if ($isImageUploaded) {
            try {
                $manager = new ImageManager(new Driver());
                $image = $manager->read($fileData['profile_image']['tmp_name']);
                // 300x300 정사각형 크롭
                $image->cover(300, 300);

                // 업로드 경로
                $uploadDir = APP_ROOT . '/public/uploads/profiles/';

                // 파일명 생성
                $newFileName = 'profile_' . $currentUser['id'] . '_' . time() . '.webp';

                // 최종 저장
                $image->toWebp(80)->save($uploadDir . $newFileName);

                // 기존 이미지가 임시 기본 이미지가 아닐 때만 서버에서 삭제
                $oldImage = $currentUser['profile_image'] ?? '';
                if (!empty($oldImage) && $oldImage !== 'user-blank.png' && file_exists($uploadDir . $oldImage)) {
                    unlink($uploadDir . $oldImage);
                }

                // 새로운 파일명을 유저 데이터에 반영
                $user['profile_image'] = $newFileName;

            } catch (Exception $e) {
                $errors['system'] = '이미지 처리 중 오류 발생: ' . $e->getMessage();
                return ['success' => false, 'errors' => $errors, 'user' => $user];
            }
        }

        // 4. DB 저장 단계
        try {
            $this->cms->getUser()->update($user);
            return ['success' => true, 'user' => $user];

        } catch (Exception $e) {
            if (str_contains($e->getMessage(), '닉네임')) {
                $errors['nickname'] = $e->getMessage();
            } elseif (str_contains($e->getMessage(), '이메일')) {
                $errors['email'] = $e->getMessage();
            } else {
                $errors['system'] = $e->getMessage();
            }
            return ['success' => false, 'errors' => $errors, 'user' => $user];
        }
    }
}
