<?php

namespace CfFormMailer\Tests\Harness;

/**
 * サンプルのフォーム（forms/sample/web_form.tpl.html）に送る入力
 */
class SampleForm
{
    /**
     * 確認画面へ進める入力。サンプルの設定は reply_to = email なので、
     * フォームに無い email も送る（送らないと自動返信先のエラーになる）。
     */
    public static function validPost(): array
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
}
