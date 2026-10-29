<?php

namespace CfFormMailer\Tests\Characterization;

use CfFormMailer\Tests\Harness\Browser;
use CfFormMailer\Tests\Harness\CharacterizationTestCase;
use CfFormMailer\Tests\Harness\SampleForm;

/**
 * サンプルのフォーム（forms/sample）の流れ：
 * 入力 → エラーでの再表示 → 確認 → 送信 → 完了、と戻る・トークン・二重送信
 */
class SampleFlowTest extends CharacterizationTestCase
{
    private function validPost(): array
    {
        return SampleForm::validPost();
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
            'post' => Browser::submit($confirm['return'], 0, 'return'),
            'session' => $confirm['session'],
        ]));
    }
}
