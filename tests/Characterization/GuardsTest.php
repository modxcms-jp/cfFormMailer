<?php

namespace CfFormMailer\Tests\Characterization;

use CfFormMailer\Tests\Harness\Browser;
use CfFormMailer\Tests\Harness\CharacterizationTestCase;
use CfFormMailer\Tests\Harness\SiteFixture;

/**
 * 設定・テンプレート・入力の書き方で、警告を出して落ちないこと。
 *
 * - 管理者のメールアドレスが 1 つも正しくなければ、エラーにしてメールを送らない。
 * - フォームに無いファイルの項目は、保存も添付もしない（検証を通っていないため）。
 * - ラベルの id に正規表現の記号があっても、項目名を取れる。
 * - name="xxx[]" の項目にも、エラーの class を付ける。
 */
class GuardsTest extends CharacterizationTestCase
{
    private const CONFIG = '@FILE:assets/snippets/cfFormMailer/forms/_t/guards/config.ini';
    private const BAD_ADMIN = '@FILE:assets/snippets/cfFormMailer/forms/_t/guards/config_bad_admin.ini';

    private function file(string $name): array
    {
        $path = SiteFixture::snippetPath() . '/forms/_t/paths/upload.txt';
        return [
            'name' => $name,
            'type' => 'text/plain',
            'tmp_name' => $path,
            'error' => 0,
            'size' => filesize($path),
        ];
    }

    /** @test */
    public function 管理者のメールアドレスが正しくなければ送信しない(): void
    {
        $confirm = $this->runStep([
            'snippet_params' => ['config' => self::BAD_ADMIN],
            'post' => ['_mode' => 'conf', 'name' => '山田', 'items' => ['A']],
        ]);
        $this->assertSnapshot('bad_admin_send', $this->runStep([
            'snippet_params' => ['config' => self::BAD_ADMIN],
            'post' => Browser::submit($confirm['return'], 0),
            'session' => $confirm['session'],
        ]));
    }

    /** @test */
    public function フォームに無いファイルの項目は受けない(): void
    {
        $this->assertSnapshot('unlisted_file', $this->runStep([
            'snippet_params' => ['config' => self::CONFIG],
            'post' => ['_mode' => 'conf', 'name' => '山田', 'items' => ['A']],
            'files' => ['attach' => $this->file('ok.txt'), 'evil' => $this->file('evil.txt')],
        ]));
    }

    /** @test */
    public function 記号のあるidのラベルと配列の項目のエラーを表示する(): void
    {
        $this->assertSnapshot('errors', $this->runStep([
            'snippet_params' => ['config' => self::CONFIG],
            'post' => ['_mode' => 'conf', 'name' => ''],
        ]));
    }
}
