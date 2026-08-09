<?php
declare(strict_types = 1);

namespace BoardgameCafe\CMS;

use Exception;
use BoardgameCafe\Exceptions\NotFoundException;
use BoardgameCafe\Exceptions\PostNotFoundException;
use BoardgameCafe\Exceptions\AuthorizationException;
use BoardgameCafe\Exceptions\AuthenticationException;
use BoardgameCafe\Exceptions\ErrorCode;

class Board
{
    protected $db;
    private User $user;
    
    public function __construct(
        Database $db,
        User $user
    ) {
        $this->db = $db;
        $this->user = $user;
    }

    /**
     * 게시판 목록 조회
     */
    public function getBoardList(string $board_name, int $limit, int $offset, array $filters = []): array
    {
        // 1. page_code 기반으로 site_menu_id 조회
        $menu_sql = "SELECT id FROM site_menu WHERE page_code = :page_code;";
        $menu_stmt = $this->db->runSql($menu_sql, ['page_code' => $board_name]);
        $menu = $menu_stmt ? $menu_stmt->fetch() : false;
        
        if (!$menu) {
            throw new NotFoundException(ErrorCode::BOARD_NOT_FOUND->value);
        }
        
        $params = ['page_code' => $board_name];
        
        // 2. 게시판별 분기 처리
        switch ($board_name) {
            // 공지사항
            case 'notice':
                $sql = "SELECT p.id, p.title, '관리자' AS nickname, p.created_at,
                            COALESCE(nd.is_pinned, 0) AS is_pinned
                        FROM post p
                        INNER JOIN site_menu m ON p.site_menu_id = m.id
                        INNER JOIN notice_detail nd ON p.id = nd.post_id";
                break;

            // 기본 게시판
            default:
                $sql = "SELECT p.id, p.thumbnail, p.title, p.writer_nickname AS nickname, p.created_at,
                            0 AS is_pinned
                        FROM post p
                        INNER JOIN site_menu m ON p.site_menu_id = m.id";
                break;
        }

        // 3. 공통 조건 (삭제되지 않은 요청한 page_code에 해당하는 게시글만)
        $sql .= " WHERE m.page_code = :page_code AND p.is_deleted = 0";

        // 4. 공통 검색 필터 처리
        if (isset($filters['keyword']) && trim($filters['keyword']) !== '') {
            $keyword_value = '%' . trim($filters['keyword']) . '%';

            if ($filters['type'] === 'title') {
                // 제목으로 검색
                $sql .= " AND p.title LIKE :keyword_title";
                $params['keyword_title'] = $keyword_value;
            } elseif ($filters['type'] === 'content') {
                // 내용으로 검색
                $sql .= " AND p.content LIKE :keyword_content";
                $params['keyword_content'] = $keyword_value;
            } else {
                // 전체 검색
                $sql .= " AND (p.title LIKE :keyword_title OR p.content LIKE :keyword_content)";
                $params['keyword_title'] = $keyword_value;
                $params['keyword_content'] = $keyword_value;
            }
        }

        // 5. 공통 정렬 및 페이징
        $sql .= " ORDER BY is_pinned DESC, p.id DESC";
        $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset . ";";

        return $this->db->runSql($sql, $params)->fetchAll();
    }

    /**
     * 총 게시글 개수 조회
     */
    public function getBoardTotalCount(string $page_code, array $filters = []): int
    {
        // 1. site_menu와 post 테이블을 조인하여 해당 게시판의 삭제되지 않은 글 개수 조회
        $sql = "SELECT COUNT(p.id)
                FROM post p
                INNER JOIN site_menu m ON p.site_menu_id = m.id
                WHERE m.page_code = :page_code AND p.is_deleted = 0";
                
        $params = ['page_code' => $page_code];
        
        // 2. 공통 검색 필터 처리
        if (isset($filters['keyword']) && trim($filters['keyword']) !== '') {
            $keyword_value = '%' . trim($filters['keyword']) . '%';

            if ($filters['type'] === 'title') {
                // 제목으로 검색
                $sql .= " AND p.title LIKE :keyword_title";
                $params['keyword_title'] = $keyword_value;
            } elseif ($filters['type'] === 'content') {
                // 내용으로 검색
                $sql .= " AND p.content LIKE :keyword_content";
                $params['keyword_content'] = $keyword_value;
            } else {
                // 전체 검색
                $sql .= " AND (p.title LIKE :keyword_title OR p.content LIKE :keyword_content)";
                $params['keyword_title'] = $keyword_value;
                $params['keyword_content'] = $keyword_value;
            }
        }

        $stmt = $this->db->runSql($sql, $params);
        return $stmt ? (int)$stmt->fetchColumn() : 0;
    }

    /**
     * 단일 게시글 상세 조회
     */
    public function getBoardPost(string $page_code, int $id): array|bool
    {
        // 1. 게시판별 게시글 조회 분기 처리
        switch ($page_code) {
            // 지점소개
            case 'branch':
                $sql = "SELECT p.id, '관리자' AS nickname, p.title, p.content, p.thumbnail,
                               p.created_at, bd.address, bd.latitude, bd.longitude
                        FROM post p
                        LEFT JOIN branch_detail bd
                         ON p.id = bd.post_id
                        WHERE p.id = :id
                         AND p.is_deleted = 0;";
                break;

            // 공지사항
            case 'notice':
                $sql = "SELECT p.id, '관리자' AS nickname, p.title, p.content, p.created_at,
                               COALESCE(nd.is_pinned, 0) AS is_pinned
                        FROM post p
                        LEFT JOIN notice_detail nd
                         ON p.id = nd.post_id
                        WHERE p.id = :id
                         AND p.is_deleted = 0;";
                break;

            // 기본 게시판
            default:
                $sql = "SELECT p.id, p.writer_nickname AS nickname, p.title, p.content, p.thumbnail,
                               p.created_at
                        FROM post p
                        WHERE p.id = :id
                         AND p.is_deleted = 0;";
                break;
        }

        $stmt = $this->db->runSql($sql, ['id' => $id]);
        $post = $stmt ? $stmt->fetch() : false;

        // 게시글이 존재하지 않거나 삭제된 경우 예외 처리
        if (!$post) {
            throw new PostNotFoundException(ErrorCode::POST_NOT_FOUND_READ->value);
        }

        // 2. 첨부파일 목록 조회
        $file_sql = "SELECT id, file_path, org_name
                     FROM post_file
                     WHERE post_id = :post_id;";

        $file_stmt = $this->db->runSql($file_sql, ['post_id' => $id]);
        
        $post['files'] = $file_stmt ? $file_stmt->fetchAll() : [];

        
        // 3. 이미지 목록 조회
        $image_sql = "SELECT id, image_path, org_name, sort_order
                      FROM post_image
                      WHERE post_id = :post_id
                        AND file_type = 'DETAIL'
                      ORDER BY sort_order;";

        $image_stmt = $this->db->runSql($image_sql, ['post_id' => $id]);
        
        $post['images'] = $image_stmt ? $image_stmt->fetchAll() : [];

        return $post;
    }

    /**
     * 게시글 작성
     */
    public function insertBoardPost(string $board_name, int $user_id, array $data): int
    {
        // 1. 회원 존재 여부 확인
        $user = $this->user->get($user_id);
        
        if (!$user) {
            throw new AuthenticationException();
        }

        // 닉네임 저장
        $writer_nickname = $user['nickname'];

        // 2. page_code 기반으로 site_menu_id 조회
        $menu_sql = "SELECT id FROM site_menu WHERE page_code = :page_code;";
        $menu_stmt = $this->db->runSql($menu_sql, ['page_code' => $board_name]);
        $menu = $menu_stmt ? $menu_stmt->fetch() : false;
        
        if (!$menu) {
            throw new NotFoundException(ErrorCode::BOARD_NOT_FOUND->value);
        }
        $site_menu_id = $menu['id'];
        
        // 3. 작성 권한 확인
        if (!$this->canWritePost($user_id, $board_name)) {
            throw new AuthorizationException(ErrorCode::ACCESS_DENIED->value);
        }

        // 4. 게시글 삽입
        $post_sql = "INSERT INTO post (
                        site_menu_id, user_id, writer_nickname, title, content, 
                        thumbnail, is_deleted, created_at, updated_at
                    ) VALUES (
                        :site_menu_id, :user_id, :writer_nickname, :title, :content, 
                        :thumbnail, 0, NOW(), NOW()
                    );";

        $this->db->runSql($post_sql, [
            'site_menu_id'    => $site_menu_id,
            'user_id'         => $user_id,
            'writer_nickname' => $writer_nickname,
            'title'           => $data['title'],
            'content'         => $data['content'],
            'thumbnail'       => $data['thumbnail'] ?? '',
        ]);

        $post_id = $this->db->lastInsertId();

        // 게시판별 등록 분기
        // 지점소개
        if ($board_name === 'branch') {
            $address = $data['address'] ?? '';

            $result = $this->getCoordinate($address);

            if (!empty($result['documents'])) {
                $data['latitude'] = $result['documents'][0]['y'];
                $data['longitude'] = $result['documents'][0]['x'];
            } else {
                $data['latitude'] = null;
                $data['longitude'] = null;
            }

            $branch_sql = "INSERT INTO branch_detail (post_id, address, latitude, longitude) 
                           VALUES (:post_id, :address, :latitude, :longitude);";
            
            $this->db->runSql($branch_sql, [
                'post_id'   => $post_id,
                'address' => $address,
                'latitude' => $data['latitude'] ?? 0,
                'longitude' => $data['longitude'] ?? 0,
            ]);
        }
        // 공지사항
        elseif ($board_name === 'notice') {
            $notice_sql = "INSERT INTO notice_detail (post_id, is_pinned) 
                           VALUES (:post_id, :is_pinned);";
            
            $this->db->runSql($notice_sql, [
                'post_id'   => $post_id,
                'is_pinned' => $data['is_pinned'] ?? 0,
            ]);
        }

        return (int)$post_id;
    }

    /**
     * 이미지 등록
     */
    public function insertBoardImage(int $post_id, ?array $thumbnail = null, array $images_files = []): void {
        $image_sql = "INSERT INTO post_image (
                        post_id,
                        image_path,
                        org_name,
                        sort_order,
                        file_type,
                        created_at
                      ) 
                      VALUES (
                        :post_id,
                        :image_path,
                        :org_name,
                        :sort_order,
                        :file_type,
                        NOW()
                      );";

         $images = [];

        // 대표 이미지
        if (!empty($thumbnail)) {
            $images[] = [
                'file'       => $thumbnail,
                'sort_order' => 0,
                'file_type'  => 'THUMB',
            ];
        }

        // 상세 이미지
        foreach ($images_files as $index => $file) {
            $images[] = [
                'file'       => $file,
                'sort_order' => $index + 1,
                'file_type'  => 'DETAIL',
            ];
        }

        foreach ($images as $image) {
            $this->db->runSql($image_sql, [
                'post_id'    => $post_id,
                'image_path' => $image['file']['file_path'],
                'org_name'   => $image['file']['org_name'],
                'sort_order' => $image['sort_order'],
                'file_type'  => $image['file_type'],
            ]);
        }
    }

    /**
     * 첨부파일 등록
     */
    public function insertBoardFile(int $post_id, array $files): void {
        if (!empty($files)) {
            $file_sql = "INSERT INTO post_file (post_id, file_path, org_name, created_at) 
                         VALUES (:post_id, :file_path, :org_name, NOW());";
            
            foreach ($files as $file) {
                $this->db->runSql($file_sql, [
                    'post_id'   => $post_id,
                    'file_path' => $file['file_path'],
                    'org_name'  => $file['org_name'],
                ]);
            }
        }
    }

    /**
     * 게시글 수정
     */
    public function updateBoardPost(
        string $board_name,
        int $post_id,
        int $user_id,
        array $data
    ): void
    {
        // 1. 회원 존재 여부 확인
        $user = $this->user->get($user_id);
        
        if (!$user) {
            throw new AuthenticationException();
        }

        // 2. 게시글 존재 여부 확인
        $post_owner = $this->findPostOwnerById($post_id);

        if (!$post_owner) {
            throw new PostNotFoundException(ErrorCode::POST_NOT_FOUND_UPDATE->value);
        }

        // 3. 수정 권한 체크
        if (!$this->canModifyPost($user_id, $post_owner, $board_name)) {
            throw new AuthorizationException(ErrorCode::ACCESS_DENIED->value);
        }

        // 4. 게시글 수정
        $post_sql = "UPDATE post
                     SET title = :title, content = :content, updated_at = NOW()
                     WHERE id = :id
                       AND is_deleted = 0;";
                    
        $this->db->runSql($post_sql, [
            'id'      => $post_id,
            'title'   => $data['title'],
            'content' => $data['content'],
        ]);

        // 게시판별 수정 분기
        if ($board_name === 'notice') {
            $notice_sql = "UPDATE notice_detail 
                           SET is_pinned = :is_pinned 
                           WHERE post_id = :post_id;";
                        
            $this->db->runSql($notice_sql, [
                'post_id'   => $post_id,
                'is_pinned' => $data['is_pinned'] ?? 0,
            ]);
        }
    }

    /**
     * 이미지 수정
     */
    public function updateBoardImage(
        int $post_id,
        ?array $thumbnail = null,
        array $images_files = [],
        array $delete_image_ids = [],
        array $image_orders = [],
        string $delete_thumbnail = 'N'
    ): void
    {
        // 1. 삭제 이미지 제거
        $this->deleteBoardImage(
            $post_id,
            $delete_image_ids,
            $delete_thumbnail
        );

        // 2. 썸네일 수정
        if (!empty($thumbnail)) {
            // 기존 썸네일 있는 경우
            if ($this->hasThumbnail($post_id)) {
                $thumbnail_update_sql = "UPDATE post_image
                                         SET image_path = :image_path,
                                             org_name = :org_name
                                         WHERE post_id = :post_id
                                           AND file_type = 'THUMB';";

                $this->db->runSql($thumbnail_update_sql, [
                    'image_path' => $thumbnail['file_path'],
                    'org_name'   => $thumbnail['org_name'],
                    'post_id'    => $post_id,
                ]);
            }
            // 기존 썸네일 없는 경우
            else {
                $thumbnail_insert_sql = "INSERT INTO post_image (
                                           post_id,
                                           image_path,
                                           org_name,
                                           sort_order,
                                           file_type,
                                           created_at
                                        ) 
                                        VALUES (
                                          :post_id,
                                          :image_path,
                                          :org_name,
                                          0,
                                          'THUMB',
                                          NOW()
                                        );";

                $this->db->runSql($thumbnail_insert_sql, [
                    'post_id'    => $post_id,
                    'image_path' => $thumbnail['file_path'],
                    'org_name'   => $thumbnail['org_name'],
                ]);
            }

            $thumbnail_sql = "UPDATE post
                              SET thumbnail = :thumbnail
                              WHERE id = :post_id;";

            $this->db->runSql($thumbnail_sql, [
                'thumbnail' => $thumbnail['file_path'],
                'post_id'   => $post_id,
            ]);
        }

        // 3. 상세 이미지 
        $update_sql = "UPDATE post_image
                       SET sort_order = :sort_order
                       WHERE id = :id
                         AND post_id = :post_id;";

        $insert_sql = "INSERT INTO post_image (
                         post_id,
                         image_path,
                         org_name,
                         sort_order,
                         file_type,
                         created_at
                       )
                       VALUES (
                         :post_id,
                         :image_path,
                         :org_name,
                         :sort_order,
                         'DETAIL',
                         NOW()
                       );";

        foreach ($image_orders as $image) {
            // 기존 이미지 순서 변경
            // $image에 저장된 값 : type, image_id, sort_order
            if ($image['type'] === 'OLD') {
                $this->db->runSql($update_sql, [
                    'sort_order' => $image['sort_order'],
                    'id'         => $image['image_id'],
                    'post_id'    => $post_id,
                ]);
            }
            // 신규 이미지 추가
            elseif ($image['type'] === 'NEW') {
                $file = $images_files[$image['file_index']];

                $this->db->runSql($insert_sql, [
                    'post_id'    => $post_id,
                    'image_path' => $file['file_path'],
                    'org_name'   => $file['org_name'],
                    'sort_order' => $image['sort_order'],
                ]);
            }
        }
    }

    /**
     * 썸네일 존재 여부 확인
     */
    private function hasThumbnail(int $post_id): bool
    {
        $sql = "SELECT thumbnail
                FROM post
                WHERE id = :post_id;";

        $result = $this->db->runSql($sql, [
            'post_id' => $post_id,
        ]);

        return !empty($result->fetchColumn());
    }

    /**
     * 이미지 삭제
     */
    public function deleteBoardImage(
        int $post_id,
        array $delete_image_ids = [],
        string $delete_thumbnail = 'N'
    ): void
    {
        // 썸네일이 삭제된 경우
        if ($delete_thumbnail === 'Y') {
            $image_id_sql = "SELECT id
                             FROM post_image
                             WHERE post_id = :post_id
                               AND image_path = (
                                 SELECT thumbnail
                                 FROM post
                                 WHERE id = :id
                               );";
            
            $stmt = $this->db->runSql($image_id_sql, [
                'post_id' => $post_id,
                'id' => $post_id,
            ]);

            $thumbnail = $stmt->fetch();

            if (!empty($thumbnail)) {
                $delete_image_ids[] = $thumbnail['id'];
            }

            $delete_thumbnail_sql = "UPDATE post
                                     SET thumbnail = ''
                                     WHERE id = :post_id;";

            $this->db->runSql($delete_thumbnail_sql, [
                'post_id' => $post_id,
            ]);
        }

        // 삭제한 이미지가 있는 경우
        if (!empty($delete_image_ids)) {
                $placeholders = implode(
                ',',
                array_fill(0, count($delete_image_ids), '?')
            );

            // 파일 경로 조회
            $select_sql = "SELECT image_path
                           FROM post_image
                           WHERE post_id = ?
                             AND id IN ($placeholders);";

            $params = array_merge([$post_id], $delete_image_ids);
            $images = $this->db->runSql($select_sql, $params);

            // 실제 파일 삭제
            foreach ($images as $image) {
                $filePath = APP_ROOT . '/' . $image['image_path'];

                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            // DB 삭제
            $delete_sql = "DELETE FROM post_image
                           WHERE post_id = ?
                             AND id IN ($placeholders);";

            $this->db->runSql($delete_sql, $params);
        }
    }

    /**
     * 첨부파일 수정
     */
    public function updateBoardFile(int $post_id, array $files = [], array $delete_file_ids = []): void
    {
        // 첨부파일 개수 확인
        $file_check_sql = "SELECT COUNT(*) 
                           FROM post_file
                           WHERE post_id = :post_id;";

        $current_file_count = $this->db->runSql($file_check_sql, [
            'post_id' => $post_id,
        ])->fetchColumn();

        $total_file_count = 
            $current_file_count
            - count($delete_file_ids)
            + count($files);

        if ($total_file_count > 3) {
            throw new Exception("첨부파일은 최대 3개까지 등록 가능합니다.");
        }

        // 첨부파일 삭제 처리
        if (!empty($delete_file_ids)) {
            // 삭제 대상 파일 조회
            $placeholders = [];
            $params = [
                'post_id' => $post_id,
            ];

            foreach ($delete_file_ids as $index => $file_id) {
                // 예) file_id_0
                $key = 'file_id_' . $index;
                // 예) :file_id_0, :file_id_1, ...
                $placeholders[] = ':' . $key;
                // 예) $params = [
                //       'post_id' => 1,
                //       'file_id_0' => 21,
                //       'file_id_1' => 22
                //     ];
                $params[$key] = $file_id;
            }

            $select_file_sql = "SELECT id, file_path
                                FROM post_file
                                WHERE post_id = :post_id
                                  AND id IN (" . implode(',', $placeholders) . ");";

            $file_stmt = $this->db->runSql($select_file_sql, $params);
            $delete_files = $file_stmt ? $file_stmt->fetchAll() : [];

            // 실제 파일 삭제
            foreach ($delete_files as $file) {
                $full_path = APP_ROOT . '/' . $file['file_path'];

                if (is_file($full_path)) {
                    unlink($full_path);
                }
            }

            // DB 파일 정보 삭제
            $delete_sql = "DELETE FROM post_file
                           WHERE post_id = :post_id
                             AND id IN (" . implode(',', $placeholders) . ");";

            $this->db->runSql($delete_sql, $params);
        }

        // 새 첨부파일 추가
        if (!empty($files)) {
            $insert_file_sql = "INSERT INTO post_file (
                                  post_id,
                                  file_path,
                                  org_name,
                                  created_at
                                )
                                VALUES (
                                  :post_id,
                                  :file_path,
                                  :org_name,
                                  NOW()
                                );";

            foreach ($files as $file) {
                $this->db->runSql($insert_file_sql, [
                    'post_id'   => $post_id,
                    'file_path' => $file['file_path'],
                    'org_name'  => $file['org_name'],
                ]);
            }
        }
    }

    /**
     * 게시글 삭제
     */
    public function deleteBoardPost(string $board_name, int $post_id, int $user_id): void
    {
        // 1. 회원 존재 여부 확인
        $user = $this->user->get($user_id);
        
        if (!$user) {
            throw new AuthenticationException();
        }

        // 2. 게시글 존재 여부 확인
        $post_owner = $this->findPostOwnerById($post_id);

        if (!$post_owner) {
            throw new PostNotFoundException(ErrorCode::POST_NOT_FOUND_DELETE->value);
        }

        // 3. 삭제 권한 체크
        if (!$this->canModifyPost($user_id, $post_owner, $board_name)) {
            throw new AuthorizationException(ErrorCode::ACCESS_DENIED->value);
        }

        // 4. 게시글 상태 값 변경
        $sql = "UPDATE post
                SET is_deleted = 1,
                    deleted_at = NOW()
                WHERE id = :id
                  AND is_deleted = 0;";
        
        $this->db->runSql($sql, ['id' => $post_id]);
    }

    /**
     * 게시글 작성 가능 여부 확인
     */
    public function canWritePost(int $user_id, string $board_name): bool
    {
        // 공지사항은 관리자만 작성 가능
        if ($board_name === 'branch' || $board_name == 'notice') {
            return $this->user->isAdmin($user_id);
        }

        return true;
    }

    /**
     * 게시글 수정/삭제 가능 여부 확인
     */
    public function canModifyPost(int $user_id, array $post_owner, string $board_name): bool
    {
        // 지점소개 공지사항은 관리자만 수정/삭제 가능
        if ($board_name === 'branch' || $board_name === 'notice') {
            return $this->user->isAdmin($user_id);
        }

        // 이외의 게시판은 작성자 본인만 수정/삭제 가능
        return (int)$post_owner['user_id'] === $user_id;
    }

    /**
     * 게시글의 소유자 정보 조회
     */
    public function findPostOwnerById(int $id): array|false
    {
        $sql = "SELECT id, user_id
                 FROM post
                WHERE id = :id
                 AND is_deleted = 0;";

        $stmt = $this->db->runSql($sql, [
            'id' => $id,
        ]);

        return $stmt ? $stmt->fetch() : false;
    }

    /**
     * 위도/경도 값 계산
     */
    function getCoordinate($address)
    {
        $url = "https://dapi.kakao.com/v2/local/search/address.json?query=" . urlencode($address);

        $headers = [
            "Authorization: KakaoAK 203ea5d20e475c58254caf2e7c5270be"
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        return json_decode($result, true);
    }
}
