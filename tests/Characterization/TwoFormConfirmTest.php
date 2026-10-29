<?php

namespace CfFormMailer\Tests\Characterization;

use CfFormMailer\Tests\Harness\Browser;
use CfFormMailer\Tests\Harness\CharacterizationTestCase;
use CfFormMailer\Tests\Harness\SampleForm;

/**
 * 確認画面に「送信する」と「戻る」の 2 つのフォームを置いたテンプレート（以前のサンプルの形）。
 *
 * cfFormMailer は確認画面のすべての <form> に _mode=send を、すべての </form> の前に
 * hidden とトークンを差し込む。既存のサイトにはこの形のテンプレートが残っているので、
 * サンプルを 1 つのフォームに直したあとも、この振る舞いを固定しておく。
 */
class TwoFormConfirmTest extends CharacterizationTestCase
{
    private const CONFIG = '@FILE:assets/snippets/cfFormMailer/forms/_t/two_form_confirm/config.ini';

    private function confirm(): array
    {
        return $this->runStep([
            'snippet_params' => ['config' => self::CONFIG],
            'post' => SampleForm::validPost(),
        ]);
    }

    public function testConfirmPageGetsHiddenFieldsInBothForms(): void
    {
        $this->assertSnapshot('conf_valid', $this->confirm());
    }

    public function testSendFromFirstForm(): void
    {
        $confirm = $this->confirm();
        $this->assertSnapshot('send_ok', $this->runStep([
            'snippet_params' => ['config' => self::CONFIG],
            'post' => Browser::submit($confirm['return'], 0),
            'session' => $confirm['session'],
        ]));
    }

    public function testBackFromSecondForm(): void
    {
        $confirm = $this->confirm();
        $this->assertSnapshot('back', $this->runStep([
            'snippet_params' => ['config' => self::CONFIG],
            'post' => Browser::submit($confirm['return'], 1),
            'session' => $confirm['session'],
        ]));
    }
}
