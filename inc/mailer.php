<?php

function smtp_send_mail(string $toEmail, string $subject, string $bodyText): bool
{
    $smtp = config('smtp');

    if (!$smtp['enabled']) {
        error_log('[Mitchie Todo] SMTP disabled. Mail body: ' . $bodyText);
        return is_debug();
    }

    $host = (string) $smtp['host'];
    $port = (int) $smtp['port'];
    $encryption = (string) $smtp['encryption'];
    $timeout = (int) ($smtp['timeout'] ?? 15);
    $transportHost = $encryption === 'ssl' ? 'ssl://' . $host : $host;
    $socket = @stream_socket_client($transportHost . ':' . $port, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT);

    if (!$socket) {
        error_log('[Mitchie Todo] SMTP connect failed: ' . $errno . ' ' . $errstr);
        return false;
    }

    stream_set_timeout($socket, $timeout);

    $read = static function ($socket): string {
        $buffer = '';
        while (($line = fgets($socket, 515)) !== false) {
            $buffer .= $line;
            if (preg_match('/^\d{3} /', $line)) {
                break;
            }
        }
        return $buffer;
    };

    $expect = static function (string $response, array $codes): void {
        $code = (int) substr(trim($response), 0, 3);
        if (!in_array($code, $codes, true)) {
            throw new RuntimeException('SMTP error: ' . trim($response));
        }
    };

    $write = static function ($socket, string $command): void {
        fwrite($socket, $command . "\r\n");
    };

    try {
        $expect($read($socket), [220]);
        $write($socket, 'EHLO localhost');
        $expect($read($socket), [250]);

        if ($encryption === 'tls') {
            $write($socket, 'STARTTLS');
            $expect($read($socket), [220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('TLS handshake failed');
            }
            $write($socket, 'EHLO localhost');
            $expect($read($socket), [250]);
        }

        $username = (string) ($smtp['username'] ?? '');
        $password = (string) ($smtp['password'] ?? '');
        if ($username !== '') {
            $write($socket, 'AUTH LOGIN');
            $expect($read($socket), [334]);
            $write($socket, base64_encode($username));
            $expect($read($socket), [334]);
            $write($socket, base64_encode($password));
            $expect($read($socket), [235]);
        }

        $fromEmail = (string) $smtp['from_email'];
        $fromName = (string) $smtp['from_name'];
        $message = implode("\r\n", [
            'Date: ' . date(DATE_RFC2822),
            'From: =?UTF-8?B?' . base64_encode($fromName) . '?= <' . $fromEmail . '>',
            'To: <' . $toEmail . '>',
            'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
            '',
            chunk_split(base64_encode($bodyText), 76, "\r\n"),
        ]);

        $write($socket, 'MAIL FROM:<' . $fromEmail . '>');
        $expect($read($socket), [250]);
        $write($socket, 'RCPT TO:<' . $toEmail . '>');
        $expect($read($socket), [250, 251]);
        $write($socket, 'DATA');
        $expect($read($socket), [354]);
        fwrite($socket, $message . "\r\n.\r\n");
        $expect($read($socket), [250]);
        $write($socket, 'QUIT');
        fclose($socket);
        return true;
    } catch (Throwable $exception) {
        error_log('[Mitchie Todo] SMTP send failed: ' . $exception->getMessage());
        fclose($socket);
        return false;
    }
}

function send_magic_link_email(string $email, string $link, DateTimeImmutable $expiresAt): bool
{
    $subject = 'ログイン用リンクをお送りします';
    $body = implode("\n", [
        'Mitchie Todo のログイン用リンクです。',
        '',
        '下のURLを開くとログインできます。',
        $link,
        '',
        '有効期限: ' . $expiresAt->format('Y-m-d H:i'),
        '',
        '心当たりがない場合はこのメールを破棄してください。',
    ]);

    return smtp_send_mail($email, $subject, $body);
}
