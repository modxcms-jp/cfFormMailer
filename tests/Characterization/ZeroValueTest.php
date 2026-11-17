<?php

namespace CfFormMailer\Tests\Characterization;

use CfFormMailer\Tests\Harness\CharacterizationTestCase;

/**
 * 値が 0 の項目（ラジオボタン・テキスト・チェックボックス）を、確認画面に空欄でなく 0 と出す。
 * 入力の無い項目は、これまでどおり空欄（&nbsp;）にする。
 */
class ZeroValueTest extends CharacterizationTestCase
{
    private const CONFIG = '@FILE:assets/snippets/cfFormMailer/forms/_t/zero_value/config.ini';

    /** @test */
    public function 値が0の項目を確認画面に出す(): void
    {
        $this->assertSnapshot('zero_value_confirm', $this->runStep([
            'snippet_params' => ['config' => self::CONFIG],
            'post' => ['_mode' => 'conf', 'count' => '0', 'amount' => '0', 'items' => ['0', '1'], 'note' => ''],
        ]));
    }
}
