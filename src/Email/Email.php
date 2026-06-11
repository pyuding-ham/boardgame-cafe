<?php
namespace BoardgameCafe\Email;

use PHPMailer\PHPMailer\Exception;

class Email {

    protected $phpmailer;
    protected $admin_email;

    public function __construct($email_config)
    {
        $this->phpmailer = new \PHPMailer\PHPMailer\PHPMailer(true); 
        $this->phpmailer->isSMTP();
        $this->phpmailer->SMTPAuth   = true;
        $this->phpmailer->Host       = $email_config['server'];
        $this->phpmailer->SMTPSecure = $email_config['security'];
        $this->phpmailer->Port       = $email_config['port'];
        $this->phpmailer->Username   = $email_config['username'];
        $this->phpmailer->Password   = $email_config['password'];
        $this->phpmailer->SMTPDebug  = $email_config['debug'];
        $this->phpmailer->CharSet    = 'UTF-8';
        $this->phpmailer->isHTML(true);

        $this->admin_email = $email_config['admin_email'] ?? '';
    }

    public function sendEmail($to, $subject, $message, $from = null): bool
    {
        try {
            // 1. 다중 발송 시 이전 수신자 기록이 남는 것을 방지하기 위해 매번 초기화
            $this->phpmailer->clearAddresses();
            $this->phpmailer->clearCustomHeaders();

            // 2. 발신자 주소 설정 ($from이 비어있으면 기본 관리자 메일 사용)
            $fromAddress = $from ?? $this->admin_email;
            $this->phpmailer->setFrom($fromAddress, '보드트립'); 

            // 3. 수신자 설정
            $this->phpmailer->addAddress($to);
            
            // 4. 제목 및 본문 설정 (HTML 엔티티 변환 방지 및 줄바꿈 유지)
            $this->phpmailer->Subject = $subject;
            $this->phpmailer->Body    = '<!DOCTYPE html><html lang="ko"><head><meta charset="UTF-8"></head><body>'
                . nl2br($message) .'</body></html>';
            $this->phpmailer->AltBody = strip_tags($message);

            // 5. 발송 및 결과 반환 (성공 시 true 반환)
            return $this->phpmailer->send();

        } catch (Exception $e) {
            error_log("Mail send failed. Error: {$this->phpmailer->ErrorInfo}");
            return false;
        }
    }
}
