<?php

namespace CfFormMailer\Tests\Characterization;

use CfFormMailer\Tests\Harness\Browser;
use CfFormMailer\Tests\Harness\CharacterizationTestCase;

/**
 * 送信先の切り替え（dynamic_send_to_field）。
 *
 * 選択肢のうち、value と sendto の両方がそろったものだけを送信先の対応表に使う。
 * <option value="">選択してください</option> のような選択肢があっても、警告を出さない
 * （MODX 1.3 系では、スニペットの中の警告でページが 500 になる）。
 */
class DynamicSendToTest extends CharacterizationTestCase
{
    private const CONFIG_SELECT = '@FILE:assets/snippets/cfFormMailer/forms/_t/dynamic_send_to/config.ini';
    private const CONFIG_RADIO = '@FILE:assets/snippets/cfFormMailer/forms/_t/dynamic_send_to/config_radio.ini';

    private function confirmAndSend(string $config, string $dept): array
    {
        $confirm = $this->runStep([
            'snippet_params' => ['config' => $config],
            'post' => ['_mode' => 'conf', 'name' => '山田', 'dept' => $dept],
        ]);
        $send = $this->runStep([
            'snippet_params' => ['config' => $config],
            'post' => Browser::submit($confirm['return'], 0),
            'session' => $confirm['session'],
        ]);
        return [$confirm, $send];
    }

    /** @test */
    public function セレクトで送信先のある選択肢を選ぶと、その送信先に送る(): void
    {
        [$confirm, $send] = $this->confirmAndSend(self::CONFIG_SELECT, '営業');
        $this->assertSnapshot('select_conf', $confirm);
        $this->assertSnapshot('select_send_sales', $send);
    }

    /** @test */
    public function セレクトで属性の順が逆の選択肢も使う(): void
    {
        [, $send] = $this->confirmAndSend(self::CONFIG_SELECT, 'サポート');
        $this->assertSnapshot('select_send_support', $send);
    }

    /** @test */
    public function セレクトで送信先の無い選択肢を選ぶと、管理者宛に送る(): void
    {
        [, $send] = $this->confirmAndSend(self::CONFIG_SELECT, 'その他');
        $this->assertSnapshot('select_send_other', $send);
    }

    /** @test */
    public function ラジオボタンで送信先のある選択肢を選ぶと、その送信先に送る(): void
    {
        [$confirm, $send] = $this->confirmAndSend(self::CONFIG_RADIO, '営業');
        $this->assertSnapshot('radio_conf', $confirm);
        $this->assertSnapshot('radio_send_sales', $send);
    }
}
