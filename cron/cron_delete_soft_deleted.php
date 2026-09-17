<?php
// 1. 외부 웹 브라우저 접근 철저히 차단
if (php_sapi_name() !== 'cli') {
    header('HTTP/1.1 403 Forbidden');
    die('접근 권한이 없습니다.');
}

// 2. 프로젝트의 공통 파일 호출
require_once __DIR__ . '/../src/bootstrap.php';

// 3. $cms 객체로부터 Database.php 인스턴스를 꺼냄
$pdo = $cms->getDb();

/**
 * public/uploads 아래 파일만 삭제
 */
function deleteStoredUpload(?string $storedPath): void
{
    if ($storedPath === null || $storedPath === '') {
        return;
    }

    $relative = str_replace('\\', '/', $storedPath);
    $relative = ltrim($relative, '/');

    if (!str_starts_with($relative, 'public/uploads/')) {
        $relative = 'public/uploads/' . ltrim($relative, '/');
    }

    $base_dir = realpath(APP_ROOT . '/public/uploads');
    $full_path = realpath(APP_ROOT . '/' . $relative);

    if ($base_dir === false || $full_path === false || !is_file($full_path)) {
        return;
    }

    $normalized_full = strtolower(str_replace('\\', '/', $full_path));
    $normalized_base = strtolower(str_replace('\\', '/', $base_dir));

    if (!str_starts_with($normalized_full, $normalized_base . '/')) {
        return;
    }

    unlink($full_path);
}

/**
 * 정수 ID 목록을 IN 절용 문자열로 만듦
 */
function toIdList(array $ids): string
{
    $safe = array_values(array_unique(array_map('intval', $ids)));

    return implode(',', $safe);
}

try {
    $pdo->beginTransaction();

    // 4. 30일 지난 소프트 삭제 댓글 물리 삭제
    $pdo->runSql(
        "DELETE FROM post_comment
         WHERE is_deleted = 1
           AND deleted_at IS NOT NULL
           AND deleted_at < NOW() - INTERVAL 30 DAY"
    );

    // 5. 30일 지난 소프트 삭제 게시글 및 연결 데이터 물리 삭제
    $expiredPosts = $pdo->runSql(
        "SELECT id, thumbnail
         FROM post
         WHERE is_deleted = 1
           AND deleted_at IS NOT NULL
           AND deleted_at < NOW() - INTERVAL 30 DAY"
    )->fetchAll();

    $postIds = array_column($expiredPosts, 'id');
    $postFilePaths = [];

    if ($postIds) {
        $postIdList = toIdList($postIds);

        $images = $pdo->query(
            "SELECT image_path FROM post_image WHERE post_id IN ($postIdList)"
        )->fetchAll();
        $files = $pdo->query(
            "SELECT file_path FROM post_file WHERE post_id IN ($postIdList)"
        )->fetchAll();

        foreach ($images as $image) {
            $postFilePaths[] = $image['image_path'] ?? '';
        }
        foreach ($files as $file) {
            $postFilePaths[] = $file['file_path'] ?? '';
        }
        foreach ($expiredPosts as $post) {
            $postFilePaths[] = $post['thumbnail'] ?? '';
        }

        // 좋아요·댓글·이미지·첨부·상세는 post ON DELETE CASCADE
        $pdo->query("DELETE FROM post WHERE id IN ($postIdList)");
    }

    // 6. 30일 지난 탈퇴 회원 및 연결 데이터 물리 삭제
    $expiredUsers = $pdo->runSql(
        "SELECT id, profile_image
         FROM user
         WHERE is_deleted = 1
           AND deleted_at IS NOT NULL
           AND deleted_at < NOW() - INTERVAL 30 DAY"
    )->fetchAll();

    $userIds = array_column($expiredUsers, 'id');
    $profileFiles = [];

    if ($userIds) {
        $userIdList = toIdList($userIds);

        foreach ($expiredUsers as $user) {
            $profileImage = $user['profile_image'] ?? '';
            if ($profileImage !== '') {
                $profileFiles[] = 'public/uploads/profiles/' . $profileImage;
            }
        }

        // 글·댓글 user_id SET NULL, token·로그·좋아요 CASCADE
        $pdo->query("DELETE FROM user WHERE id IN ($userIdList)");
    }

    $pdo->commit();

    foreach ($postFilePaths as $path) {
        deleteStoredUpload($path);
    }
    foreach ($profileFiles as $path) {
        deleteStoredUpload($path);
    }

    echo "[" . date('Y-m-d H:i:s') . "] 소프트 삭제 데이터 정리가 완료되었습니다.\n";
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo "[" . date('Y-m-d H:i:s') . "] 소프트 삭제 데이터 정리 중 에러 발생: " . $e->getMessage() . "\n";
}
