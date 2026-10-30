<?php

namespace CfFormMailer\Tests\Harness;

/**
 * StepRunner の結果を、差分の読みやすいテキストにする。
 * 空のセクションは出さない（新しい警告やメールが出たら、セクションごと差分に現れる）。
 */
class SnapshotRenderer
{
    public static function render(array $result): string
    {
        $sections = [];

        $sections['return'] = is_string($result['return'])
            ? $result['return']
            : var_export($result['return'], true);
        if ($result['echo'] !== '') {
            $sections['echo'] = $result['echo'];
        }
        if ($result['exited']) {
            $sections['exited'] = 'yes';
        }
        if ($result['fatal'] !== null) {
            $sections['fatal'] = $result['fatal'];
        }
        if ($result['php_errors']) {
            $sections['php_errors'] = implode("\n", $result['php_errors']);
        }
        $sections['session'] = self::export(self::sortRecursive($result['session']));
        if ($result['mails']) {
            $sections['mails'] = self::mails($result['mails']);
        }
        if ($result['logs']) {
            $sections['logs'] = self::logs($result['logs']);
        }
        if ($result['redirects']) {
            $sections['redirects'] = implode("\n", $result['redirects']);
        }
        if ($result['db_inserts']) {
            $sections['db_inserts'] = self::export($result['db_inserts']);
        }
        if ($result['moved_files']) {
            $sections['moved_files'] = self::export($result['moved_files']);
        }

        $text = '';
        foreach ($sections as $name => $body) {
            $text .= sprintf("== %s ==\n%s\n\n", $name, rtrim($body, "\n"));
        }
        return Masker::mask(rtrim($text, "\n") . "\n", $result);
    }

    private static function mails(array $mails): string
    {
        $out = [];
        foreach ($mails as $i => $mail) {
            $body = $mail['body'];
            $charset = strtolower((string) $mail['charset']);
            if ($charset !== '' && $charset !== 'utf-8') {
                // 読めるように UTF-8 に戻して載せる（元の文字コードは charset の行でわかる）
                $body = mb_convert_encoding($body, 'UTF-8', $mail['charset']);
            }
            $lines = [sprintf('--- mail #%d%s', $i + 1, $mail['sent'] ? '' : ' (send failed)')];
            foreach (['to', 'cc', 'bcc', 'reply_to'] as $key) {
                if ($mail[$key]) {
                    $lines[] = sprintf('%s: %s', $key, implode(', ', $mail[$key]));
                }
            }
            $lines[] = 'from: ' . $mail['from'];
            $lines[] = 'sender: ' . $mail['sender'];
            $lines[] = 'subject: ' . $mail['subject'];
            $lines[] = sprintf('charset: %s / encoding: %s', $mail['charset'], $mail['encoding']);
            if ($mail['is_html'] !== null) {
                $lines[] = 'is_html: ' . var_export($mail['is_html'], true);
            }
            foreach ($mail['attachments'] as $attachment) {
                $lines[] = sprintf('attachment: %s (%s)', $attachment['path'], $attachment['name']);
            }
            $lines[] = 'body:';
            $lines[] = $body;
            $out[] = implode("\n", $lines);
        }
        return implode("\n", $out);
    }

    private static function logs(array $logs): string
    {
        $out = [];
        foreach ($logs as $log) {
            $out[] = sprintf(
                '[%s/%s] %s: %s',
                $log['evtid'],
                $log['type'],
                $log['source'],
                Masker::logMessage($log['msg'], $log['source'])
            );
        }
        return implode("\n", $out);
    }

    private static function sortRecursive($value)
    {
        if (!is_array($value)) {
            return $value;
        }
        ksort($value);
        foreach ($value as $k => $v) {
            $value[$k] = self::sortRecursive($v);
        }
        return $value;
    }

    private static function export($value): string
    {
        return var_export($value, true);
    }
}
