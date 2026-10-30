<?php

namespace CfFormMailer\Tests\Harness;

/**
 * スナップショットの中で、実行のたびに変わる値を伏せる。
 */
class Masker
{
    public static function mask(string $text, array $result): string
    {
        $text = str_replace(SiteFixture::root(), '<SITE>', $text);

        $token = isset($result['session']['_cffm_token']) ? $result['session']['_cffm_token'] : null;
        if (is_string($token) && $token !== '') {
            $text = str_replace($token, '<TOKEN>', $text);
        }

        return preg_replace(
            [
                '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/',
                '/\d{4}年\d{2}月\d{2}日 \d{2}時\d{2}分/u',
                '/tmp=\d+/',
            ],
            ['<DATE>', '<DATE>', 'tmp=<RND>'],
            $text
        );
    }

    /**
     * ログの先頭の [file:line] は呼び出し元が変わるたびに変わるので伏せる。
     * 送信エラーのログに付く var_export のダンプも伏せる。
     */
    public static function logMessage(string $msg, string $source): string
    {
        $msg = preg_replace('/^\[[^\]\s]+:[0-9?]+\] /', '[<SRC>] ', $msg);
        if (preg_match('/Error$/', $source) && ($pos = strpos($msg, '<br>')) !== false) {
            $msg = substr($msg, 0, $pos) . '<br><DUMP>';
        }
        return $msg;
    }
}
