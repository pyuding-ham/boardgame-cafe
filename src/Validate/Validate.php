<?php
namespace BoardgameCafe\Validate;

class Validate
{
    public static function isNumber($number, $min = 0, $max = 100): bool
    {
        return ($number >= $min and $number <= $max);
    }

    public static function isText(String $string, int $min = 0, int $max = 1000): bool
    {
        $length = mb_strlen($string);
        return ($length >= $min and $length <= $max);
    }

    public static function isUsername(string $username): bool
    {
        // 아이디는 4-20자의 영문 대소문자, 숫자, 언더바만 허용
        return (bool)preg_match('/^[a-zA-Z0-9_]{4,20}$/', $username);
    }

    public static function isEmail(string $email): bool
    {
        return (filter_var($email, FILTER_VALIDATE_EMAIL)) ? true : false;
    }

    public static function isPhone(string $phone): bool
    {
        $digits = preg_replace('/[\s\-()]/', '', $phone) ?? '';

        // '+82'로 시작되는 경우
        if (str_starts_with($digits, '+82')) {
            $digits = '0' . ltrim(substr($digits, 3), '0');
        }

        // 휴대전화 010/011/016/017/018/019 (10~11자리)
        // 지역번호 02, 031 등 (9~11자리)
        return (bool)preg_match('/^01[016789]\d{7,8}$/', $digits)
            || (bool)preg_match('/^0[2-6]\d{7,9}$/', $digits);
    }

    public static function isPassword(string $password)
    {
        if (mb_strlen($password) >= 10
            and preg_match('/[a-z]/i', $password)
            and preg_match('/[0-9]/', $password)
            and preg_match('/^[a-z0-9!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]+$/i', $password) 
        ) {
            return true;
        }
        return false;
    }
}