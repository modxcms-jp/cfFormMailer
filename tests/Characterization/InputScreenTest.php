<?php

namespace CfFormMailer\Tests\Characterization;

use CfFormMailer\Tests\Harness\CharacterizationTestCase;

/**
 * 入力画面（POST が無いときの最初の表示）
 */
class InputScreenTest extends CharacterizationTestCase
{
    /** @test */
    public function サンプルのフォームの最初の表示(): void
    {
        $this->assertSnapshot('sample_initial', $this->runStep());
    }
}
