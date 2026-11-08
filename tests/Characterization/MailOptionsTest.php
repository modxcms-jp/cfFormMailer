<?php

namespace CfFormMailer\Tests\Characterization;

use CfFormMailer\Tests\Harness\Browser;
use CfFormMailer\Tests\Harness\CharacterizationTestCase;
use CfFormMailer\Tests\Harness\SampleForm;

/**
 * 設定の読み方：言語からの文字コード、reply_to の組み立て、HTML メールのエスケープ。
 */
class MailOptionsTest extends CharacterizationTestCase
{
    private const ADMIN_HTML = '@FILE:assets/snippets/cfFormMailer/forms/_t/mail_options/config_admin_html.ini';
    private const REPLY_HTML = '@FILE:assets/snippets/cfFormMailer/forms/_t/mail_options/config_reply_html.ini';
    private const NO_SEND = '@FILE:assets/snippets/cfFormMailer/forms/_t/mail_options/config_no_send.ini';

    private function confirm(string $config, array $post): array
    {
        return $this->runStep([
            'snippet_params' => ['config' => $config],
            'post' => array_replace([
                '_mode' => 'conf',
                'm1' => 'taro',
                'm2' => 'example.com',
                'memo' => '<b>太字</b>',
            ], $post),
        ]);
    }

    private function confirmAndSend(string $config): array
    {
        $confirm = $this->confirm($config, []);
        return $this->runStep([
            'snippet_params' => ['config' => $config],
            'post' => Browser::submit($confirm['return'], 0),
            'session' => $confirm['session'],
        ]);
    }

    /** @test */
    public function 管理画面の言語がutfでもeucでもなければutf8として扱う(): void
    {
        $this->assertSnapshot('language_english', $this->runStep([
            'post' => SampleForm::validPost(),
            'modx_config' => ['manager_language' => 'english'],
        ]));
    }

    /** @test */
    public function 組み立てるreply_toの欄が空なら入力エラーにする(): void
    {
        $this->assertSnapshot('reply_to_part_empty', $this->confirm(self::ADMIN_HTML, ['m1' => '']));
    }

    /** @test */
    public function 組み立てるreply_toの欄に配列が送られたら入力エラーにする(): void
    {
        $this->assertSnapshot('reply_to_part_array', $this->confirm(self::ADMIN_HTML, ['m1' => ['taro']]));
    }

    /** @test */
    public function 管理者宛だけHTMLメールなら管理者宛だけエスケープする(): void
    {
        $this->assertSnapshot('admin_html', $this->confirmAndSend(self::ADMIN_HTML));
    }

    /** @test */
    public function 自動返信だけHTMLメールなら自動返信だけエスケープする(): void
    {
        $this->assertSnapshot('reply_html', $this->confirmAndSend(self::REPLY_HTML));
    }

    /** @test */
    public function send_mailが0ならメールを送らずに完了の画面を出す(): void
    {
        $this->assertSnapshot('no_send', $this->confirmAndSend(self::NO_SEND));
    }
}
