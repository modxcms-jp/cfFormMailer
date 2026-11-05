<?php

namespace CfFormMailer\Tests\Characterization;

use CfFormMailer\Tests\Harness\CharacterizationTestCase;

/**
 * name に [] の無い項目に、配列（email[]=…）が送られたとき。
 *
 * 検証メソッドは文字列を前提にしているので、配列のまま渡すと PHP 8 で TypeError になる。
 * その項目を入力エラーにして、検証を飛ばす。name に [] の付いた項目（チェックボックス）は、
 * これまでどおり配列で受ける。
 */
class ArrayInputTest extends CharacterizationTestCase
{
    private const CONFIG = '@FILE:assets/snippets/cfFormMailer/forms/_t/array_input/config.ini';

    private function confirm(array $post): array
    {
        return $this->runStep([
            'snippet_params' => ['config' => self::CONFIG],
            'post' => array_replace([
                '_mode' => 'conf',
                'email' => 'taro@example.com',
                'tel' => '03-1234-5678',
                'zip' => '1234567',
                'items' => ['A', 'B'],
            ], $post),
        ]);
    }

    /** @test */
    public function 正しい入力なら確認画面へ進む(): void
    {
        $this->assertSnapshot('valid', $this->confirm([]));
    }

    /** @test */
    public function メールアドレスに配列が送られたら入力エラーにする(): void
    {
        $this->assertSnapshot('email_array', $this->confirm(['email' => ['taro@example.com']]));
    }

    /** @test */
    public function 電話番号に配列が送られたら入力エラーにする(): void
    {
        $this->assertSnapshot('tel_array', $this->confirm(['tel' => ['03-1234-5678']]));
    }

    /** @test */
    public function 必須でない項目に配列が送られても入力エラーにする(): void
    {
        $this->assertSnapshot('zip_array', $this->confirm(['zip' => ['1234567']]));
    }
}
