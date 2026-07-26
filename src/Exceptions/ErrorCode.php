<?php

namespace BoardgameCafe\Exceptions;

enum ErrorCode: string
{
    case POST_NOT_FOUND_READ   = 'POST_NOT_FOUND_READ';
    case POST_NOT_FOUND_UPDATE = 'POST_NOT_FOUND_UPDATE';
    case POST_NOT_FOUND_DELETE = 'POST_NOT_FOUND_DELETE';
    case PAGE_NOT_FOUND        = 'PAGE_NOT_FOUND';
}
