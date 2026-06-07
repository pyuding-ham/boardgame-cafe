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

    public static function isPassword(string $password)
    {
        if (mb_strlen($password) >= 8
            and preg_match('/[A-Z]/', $password)
            and preg_match('/[a-z]/', $password)
            and preg_match('/[0-9]/', $password)
        ) {
            return true;
        }
        return false;
    }
}