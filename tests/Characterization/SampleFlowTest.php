<?php

namespace CfFormMailer\Tests\Characterization;

use CfFormMailer\Tests\Harness\Browser;
use CfFormMailer\Tests\Harness\CharacterizationTestCase;

/**
 * サンプルのフォーム（forms/sample）の流れ：
 * 入力 → エラーでの再表示 → 確認 → 送信 → 完了、と戻る・トークン・二重送信
 */
class SampleFlowTest extends CharacterizationTestCase
{
    /**
     * 確認画面へ進める入力。サンプルの設定は reply_to = email なので、
     * フォームに無い email も送る（送らないと自動返信先のエラーになる）。
     */
    private function validPost(): array
    {
        return [
            '_mode' => 'conf',
            'name_f' => '山田',
            'name_l' => '太郎',
            'company' => '',
            'email1' => 'taro',
            'email2' => '@docomo.ne.jp',
            'email' => 'taro@example.com',
            'gender' => '男性',
            'zip1' => '123',
            'zip2' => '4567',
            'pref' => '東京都',
            'addr1' => '千代田区',
            'addr2' => '1-1',
            'addr3' => '',
            'tel' => '03-1234-5678',
            'tel_confirm' => '03-1234-5678',
            'summary' => 'その他',
            'message' => "1行目\n2行目 <b>&</b> \"引用\"",
            'way' => 'メール',
            'own' => ['ライトサーベル', 'ですの音'],
            'impression' => ['なし'],
            'veri' => 'abcd',
        ];
    }

    private function confirm(): array
    {
        return $this->runStep(['post' => $this->validPost()]);
    }

    public function testConfirmWithEmptyPostShowsErrors(): void
    {
        $this->assertSnapshot('conf_empty', $this->runStep(['post' => ['_mode' => 'conf']]));
    }

    public function testConfirmWithInvalidValuesShowsErrorsAndRestoresInput(): void
    {
        $post = array_replace($this->validPost(), [
            'name_l' => '',
            'zip1' => '12a',
            'zip2' => '45678',
            'tel_confirm' => '03-0000-0000',
            'way' => '',
            'own' => ['電動スノーボード'],
        ]);
        $this->assertSnapshot('conf_invalid', $this->runStep(['post' => $post]));
    }

    public function testConfirmWithValidPostShowsConfirmPage(): void
    {
        $this->assertSnapshot('conf_valid', $this->confirm());
    }

    public function testSendFromConfirmPage(): void
    {
        $confirm = $this->confirm();
        $this->assertSnapshot('send_ok', $this->runStep([
            'post' => Browser::submit($confirm['return'], 0),
            'session' => $confirm['session'],
        ]));
    }

    public function testSendWithWrongTokenIsRejected(): void
    {
        $confirm = $this->confirm();
        $post = Browser::submit($confirm['return'], 0);
        $post['_cffm_token'] = 'wrong-token';
        $this->assertSnapshot('send_bad_token', $this->runStep([
            'post' => $post,
            'session' => $confirm['session'],
        ]));
    }

    public function testSendingSameDataAgainIsRejected(): void
    {
        $confirm = $this->confirm();
        $post = Browser::submit($confirm['return'], 0);
        $sent = $this->runStep(['post' => $post, 'session' => $confirm['session']]);
        $this->assertSnapshot('send_again', $this->runStep([
            'post' => $post,
            'session' => $sent['session'],
        ]));
    }

    public function testBackFromConfirmPageRestoresInput(): void
    {
        $confirm = $this->confirm();
        $this->assertSnapshot('back', $this->runStep([
            'post' => Browser::submit($confirm['return'], 1),
            'session' => $confirm['session'],
        ]));
    }
}
