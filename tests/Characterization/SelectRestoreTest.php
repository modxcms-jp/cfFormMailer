<?php

namespace CfFormMailer\Tests\Characterization;

use CfFormMailer\Tests\Harness\CharacterizationTestCase;

/**
 * 入力エラーで出し直したとき、選択欄の選択を送られた値に合わせる。
 *
 * - テンプレートで selected にしてある選択肢と同じ値が送られても、選択を外さない。
 * - 外すのは属性の selected だけで、value="unselected" のような値は変えない。
 * - 属性の値（引用符の中）にある > や selected は、タグの終わりや属性として扱わない。
 */
class SelectRestoreTest extends CharacterizationTestCase
{
    private const CONFIG = '@FILE:assets/snippets/cfFormMailer/forms/_t/select_restore/config.ini';

    /** @test */
    public function テンプレートでselectedにした値が送られても選択を外さない(): void
    {
        $this->assertSnapshot('same_as_template', $this->runStep([
            'snippet_params' => ['config' => self::CONFIG],
            'post' => ['_mode' => 'conf', 'name' => '', 'pref' => 'tokyo', 'state' => 'selected', 'order' => 'c'],
        ]));
    }

    /** @test */
    public function テンプレートと違う値が送られたらその値を選ぶ(): void
    {
        $this->assertSnapshot('other_than_template', $this->runStep([
            'snippet_params' => ['config' => self::CONFIG],
            'post' => ['_mode' => 'conf', 'name' => '', 'pref' => 'osaka', 'state' => 'unselected', 'order' => 'b'],
        ]));
    }
}
