<?php

namespace CfFormMailer\Tests\Characterization;

use CfFormMailer\Tests\Harness\Browser;
use CfFormMailer\Tests\Harness\CharacterizationTestCase;
use CfFormMailer\Tests\Harness\SiteFixture;

/**
 * 送信（send）では、確認画面を出したときに検証を通った値を使う。
 *
 * 確認画面の hidden の値は書き換えられる。正しく確認画面を通ってトークンを得たあとで
 * 値を書き換えて送られても、送られてきた値は使わず、検証を通った値で送信する。
 * 送信で検証し直すと、変換のある検証（zip でハイフンを足す）と len() の組み合わせで、
 * 変換したあとの値がはじかれるので、検証し直しはしない。
 * 正しい流れでは、必須のファイル（確認画面で保存したもの）と画像認証もそのまま通る。
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
                'zip' => '1234567',
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

    private function send(array $confirm, array $override, ?array $session = null): array
    {
        return $this->runStep([
            'snippet_params' => ['config' => self::CONFIG],
            'post' => array_replace(Browser::submit($confirm['return'], 0), $override),
            'session' => $session ?? $confirm['session'],
        ]);
    }

    /** @test */
    public function 正しい流れでは送信する(): void
    {
        $confirm = $this->confirm();
        $this->assertSnapshot('conf', $confirm);
        // zip,len(7) で、確認画面では 123-4567 に変換される。送信ではじかれない
        $this->assertSnapshot('send_ok', $this->send($confirm, []));
    }

    /** @test */
    public function 書き換えたメールアドレスは使わず確認画面の値で送信する(): void
    {
        $this->assertSnapshot('send_bad_email', $this->send($this->confirm(), ['email' => 'not-an-address']));
    }

    /** @test */
    public function 必須の項目を消して送られても確認画面の値で送信する(): void
    {
        $this->assertSnapshot('send_empty_required', $this->send($this->confirm(), ['name' => '']));
    }

    /** @test */
    public function 配列を送られても確認画面の値で送信する(): void
    {
        $this->assertSnapshot('send_array', $this->send($this->confirm(), ['email' => ['x@example.com']]));
    }

    /** @test */
    public function 確認画面を通っていなければ送信しない(): void
    {
        $confirm = $this->confirm();
        $session = $confirm['session'];
        unset($session['_cffm_confirmed']);
        $this->assertSnapshot('send_without_confirm', $this->send($confirm, [], $session));
    }
}
