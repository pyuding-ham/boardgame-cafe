<?php

namespace BoardgameCafe\Enums;

enum GameCategory: int {
    case STRATEGY = 1;
    case PARTY = 2;
    case THEME_ADVENTURE = 3;
    case KIDS = 4;

    public static function getName(int $value): string {
        return match(self::tryFrom($value)) {
            self::STRATEGY => '전략',
            self::PARTY => '파티',
            self::THEME_ADVENTURE => '테마/모험',
            self::KIDS => '어린이',
            default => '기타',
        };
    }
}