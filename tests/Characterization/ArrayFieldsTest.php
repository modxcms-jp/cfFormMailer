<?php

namespace CfFormMailer\Tests\Characterization;

use CfFormMailer\Tests\Harness\Browser;
use CfFormMailer\Tests\Harness\CharacterizationTestCase;

/**
 * name に [] の付いた項目と、入れ子の配列。
 *
 * - 文字列を前提にした標準の検証（tel・num・len など）は、配列の要素ごとに当てる。
 * - name="xxx[]" の 1 段の配列までを受け、それより深い配列（xxx[a][]=…）は捨てる。
 *   フォームの項目でも、フォームに無いキーでも同じ。
 */
class ArrayFieldsTest extends CharacterizationTestCase
{
    private const CONFIG = '@FILE:assets/snippets/cfFormMailer/forms/_t/array_fields/config.ini';

    private function confirm(array $post): array
    {
        return $this->runStep([
            'snippet_params' => ['config' => self::CONFIG],
            'post' => array_replace([
                '_mode' => 'conf',
                'tel' => ['０３−１２３４−５６７８', ''],
                'n' => ['１２', '3'],
                'items' => ['A'],
            ], $post),
        ]);
    }

    /** @test */
    public function テキストボックスを並べた項目は要素ごとに検証し変換する(): void
    {
        $confirm = $this->confirm([]);
        $this->assertSnapshot('valid_conf', $confirm);
        $this->assertSnapshot('valid_send', $this->runStep([
            'snippet_params' => ['config' => self::CONFIG],
            'post' => Browser::submit($confirm['return'], 0),
            'session' => $confirm['session'],
        ]));
    }

    /** @test */
    public function 要素のどれかが誤っていれば入力エラーにする(): void
    {
        $this->assertSnapshot('invalid_element', $this->confirm([
            'tel' => ['03-1234-5678', 'abc'],
            'n' => ['1', '12345'],
        ]));
    }

    /** @test */
    public function 項目に入れ子の配列が送られたら深いところを捨てる(): void
    {
        $this->assertSnapshot('nested_field', $this->confirm(['items' => ['a' => ['A']]]));
    }

    /** @test */
    public function フォームに無いキーに入れ子の配列が送られたら深いところを捨てる(): void
    {
        $this->assertSnapshot('nested_extra_key', $this->confirm(['zzz' => ['a' => ['x'], 'b' => 'y']]));
    }
}
