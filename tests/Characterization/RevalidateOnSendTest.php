<?php

namespace CfFormMailer\Tests\Characterization;

use CfFormMailer\Tests\Harness\Browser;
use CfFormMailer\Tests\Harness\CharacterizationTestCase;
use CfFormMailer\Tests\Harness\SiteFixture;

/**
 * 送信（send）のときにも入力を検証し直す。
 *
 * 確認画面の hidden の値は書き換えられる。正しく確認画面を通ってトークンを得たあとで
 * 値を書き換えて送られても、検証を通らない値ではメールを送らない。
 * 正しい流れでは、必須のファイル（確認画面で保存したもの）と画像認証も、検証し直しで通る。
 */
class RevalidateOnSendTest extends CharacterizationTestCase
{
    private const CONFIG = '@FILE:assets/snippets/cfFormMailer/forms/_t/revalidate/config.ini';

    private function confirm(): array
    {
        $path = SiteFixture::snippetPath() . '/forms/_t/paths/upload.txt';
        return $this->runStep([
            'snippet_params' => ['config' => self::CONFIG],
            'post' => [
                '_mode' => 'conf',
                'name' => '山田',
                'email' => 'taro@example.com',
                'veri' => 'abcd',
            ],
            'files' => ['attach' => [
                'name' => 'upload.txt',
                'type' => 'text/plain',
                'tmp_name' => $path,
                'error' => 0,
                'size' => filesize($path),
            ]],
            'session' => ['veriword' => 'abcd'],
        ]);
    }

    private function send(array $confirm, array $override): array
    {
        return $this->runStep([
            'snippet_params' => ['config' => self::CONFIG],
            'post' => array_replace(Browser::submit($confirm['return'], 0), $override),
            'session' => $confirm['session'],
        ]);
    }

    /** @test */
    public function 正しい流れでは送信する(): void
    {
        $confirm = $this->confirm();
        $this->assertSnapshot('conf', $confirm);
        $this->assertSnapshot('send_ok', $this->send($confirm, []));
    }

    /** @test */
    public function 書き換えたメールアドレスでは送信しない(): void
    {
        $this->assertSnapshot('send_bad_email', $this->send($this->confirm(), ['email' => 'not-an-address']));
    }

    /** @test */
    public function 必須の項目を消して送られたら送信しない(): void
    {
        $this->assertSnapshot('send_empty_required', $this->send($this->confirm(), ['name' => '']));
    }
}
