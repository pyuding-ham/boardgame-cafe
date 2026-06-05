<?php
namespace BoardgameCafe\CMS;

/**
 * 데이터베이스 연결 및 쿼리 실행 클래스
 * PHP 내장 PDO 클래스를 상속받음
 */

class Database extends \PDO
{
    /**
     * 데이터베이스 연결을 설정하고 기본 옵션을 적용
     * 
     * @param string $dsn 데이터베이스 소스 네임 (예: mysql:host=localhost;dbname=boardgame_db)
     * @param string $username 데이터베이스 접속 계정명
     * @param string $password 데이터베이스 접속 비밀번호
     * @param array $options 추가적인 PDO 설정 옵션
     */
    public function __construct(string $dsn, string $username, string $password, array $options = [])
    {
        $default_options[\PDO::ATTR_DEFAULT_FETCH_MODE] = \PDO::FETCH_ASSOC;
        $default_options[\PDO::ATTR_EMULATE_PREPARES] = false;
        $default_options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;
        $options = array_replace($default_options, $options);
        parent::__construct($dsn, $username, $password, $options);
    }

    /**
     * SQL 쿼리 실행
     * 
     * @param string $sql 실행할 SQL 문장
     * @param array|null $arguments SQL 문에 바인딩할 데이터 배열
     * @return \PDOStatement|false 실행 결과 객체 또는 실패 시 false 반환
     */
    public function runSql(string $sql, ?array $arguments = null) : \PDOStatement|false
    {
        // 파라미터가 없는 경우
        if (!$arguments) {
            return $this->query($sql);
        }
        
        // 파라미터가 있는 경우
        $statement = $this->prepare($sql);
        $status = $statement->execute($arguments);

        return ($status == false) ? false : $statement;
    }
}