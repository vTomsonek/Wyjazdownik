<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * MailerService - dwa drivery: log (dev) i smtp (prod).
 *
 * Driver "log":
 *   - dopisuje pełną treść maila do /storage/sent_emails.log
 *   - zapamiętuje URL z magic linka w sesji ($_SESSION['_last_magic_link']),
 *     żeby AdminAuthController mógł go pokazać w dev (skip skrzynki).
 *
 * Driver "smtp":
 *   - wymaga zainstalowanego phpmailer/phpmailer (composer install)
 *   - jeśli biblioteki nie ma, rzuca RuntimeException z czytelnym komunikatem
 */
final class MailerService
{
    public function __construct(
        private readonly string $driver = 'log',
        private readonly string $host = 'localhost',
        private readonly int $port = 1025,
        private readonly string $username = '',
        private readonly string $password = '',
        private readonly string $fromAddress = 'noreply@wyjazdownik.pl',
        private readonly string $fromName = 'Wyjazdownik',
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(
            driver:      (string) config('mail.driver', 'log'),
            host:        (string) config('mail.host', 'localhost'),
            port:        (int)    config('mail.port', 1025),
            username:    (string) config('mail.username', ''),
            password:    (string) config('mail.password', ''),
            fromAddress: (string) config('mail.from_address', 'noreply@wyjazdownik.pl'),
            fromName:    (string) config('mail.from_name', 'Wyjazdownik'),
        );
    }

    /**
     * Wyśle mail. Zwraca true jeśli sukces.
     *
     * @param array<string,string|null> $context Opcjonalne dane do logu (np. magicLink) - przydatne w dev.
     */
    public function send(string $to, string $subject, string $htmlBody, string $plainBody = '', array $context = []): bool
    {
        return match ($this->driver) {
            'log'  => $this->sendLog($to, $subject, $htmlBody, $plainBody, $context),
            'smtp' => $this->sendSmtp($to, $subject, $htmlBody, $plainBody),
            default => throw new RuntimeException("Nieznany MAIL_DRIVER: {$this->driver}"),
        };
    }

    /**
     * @param array<string,string|null> $context
     */
    private function sendLog(string $to, string $subject, string $htmlBody, string $plainBody, array $context): bool
    {
        $logFile = BASE_PATH . '/storage/sent_emails.log';
        $entry  = str_repeat('=', 80) . "\n";
        $entry .= 'Date:    ' . date('c') . "\n";
        $entry .= 'From:    ' . $this->fromName . ' <' . $this->fromAddress . ">\n";
        $entry .= 'To:      ' . $to . "\n";
        $entry .= 'Subject: ' . $subject . "\n";
        foreach ($context as $key => $value) {
            if ($value === null || $value === '') continue;
            $entry .= ucfirst($key) . ': ' . $value . "\n";
        }
        $entry .= str_repeat('-', 80) . "\n";
        $entry .= ($plainBody !== '' ? $plainBody : strip_tags($htmlBody)) . "\n\n";

        @file_put_contents($logFile, $entry, FILE_APPEND);

        // Dla wygody w dev: zachowaj ostatni magic link w sesji
        if (isset($context['magic_link']) && session_status() !== PHP_SESSION_NONE) {
            $_SESSION['_last_magic_link'] = (string) $context['magic_link'];
        }

        return true;
    }

    private function sendSmtp(string $to, string $subject, string $htmlBody, string $plainBody): bool
    {
        if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
            throw new RuntimeException(
                'PHPMailer nie jest zainstalowany. Uruchom: composer install'
            );
        }

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $this->host;
        $mail->Port       = $this->port;
        $mail->SMTPAuth   = $this->username !== '';
        $mail->Username   = $this->username;
        $mail->Password   = $this->password;
        $mail->CharSet    = 'UTF-8';
        $mail->SMTPSecure = $this->port === 465 ? 'ssl' : 'tls';

        $mail->setFrom($this->fromAddress, $this->fromName);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body    = $htmlBody;
        $mail->AltBody = $plainBody !== '' ? $plainBody : strip_tags($htmlBody);

        return $mail->send();
    }
}
