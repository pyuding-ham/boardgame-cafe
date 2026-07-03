<?php
// 로그인 세션이 있을 때만 쿠키 수명 늘려줌
if (isset($_SESSION['id']) && !empty($_SESSION['id'])) {
    setcookie(session_name(), session_id(), time() + 1440, "/");
}
exit;