<?php

namespace BoardgameCafe\Exceptions;

enum ErrorCode: string
{
    case POST_NOT_FOUND_READ   = 'POST_NOT_FOUND_READ';
    case POST_NOT_FOUND_UPDATE = 'POST_NOT_FOUND_UPDATE';
    case POST_NOT_FOUND_DELETE = 'POST_NOT_FOUND_DELETE';
    case PAGE_NOT_FOUND        = 'PAGE_NOT_FOUND';
    case BOARD_NOT_FOUND       = 'BOARD_NOT_FOUND';
    case ACCESS_DENIED         = 'ACCESS_DENIED';
}
