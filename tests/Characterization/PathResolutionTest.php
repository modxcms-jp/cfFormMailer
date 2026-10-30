<?php

namespace CfFormMailer\Tests\Characterization;

use CfFormMailer\Tests\Harness\CharacterizationTestCase;
use CfFormMailer\Tests\Harness\SiteFixture;

/**
 * 設定・テンプレート・追加のファイルを、どこから読むか。
 *
 * ディレクトリ構成を変えると、ここのスナップショットだけが変わる想定。
 * - @FILE: は CFM_PATH（bootstrap.php のあるフォルダ）→ MODX のルートの順に探す
 * - additionalMethods.inc.php は CFM_PATH から、class.upload.php はクラスのファイルと同じフォルダから読む
 * - アップロードの一時ファイルは CFM_PATH/tmp に置く
 */
class PathResolutionTest extends CharacterizationTestCase
{
    private const FORM_DIR = 'forms/_t/paths';
    private const CONFIG_FROM_MODX_ROOT = '@FILE:assets/snippets/cfFormMailer/forms/_t/paths/config.ini';

    /** 名前の項目に付けた t3mark の検証。読まれたら必ずエラーになる */
    private const USER_VALIDATOR = "<?php\nfunction _validate_t3mark(\$value, \$param)\n{\n    return 'additionalMethods.inc.php の検証が呼ばれた';\n}\n";

    /** class.upload.php の代わり。読まれたら MIME タイプがこの値になる */
    private const FAKE_UPLOAD_CLASS = "<?php\nclass upload\n{\n    public \$uploaded = true;\n    public \$file_src_mime = 'text/x-from-class-upload';\n    public function __construct(\$file)\n    {\n    }\n}\n";

    private function confirmWithUpload(array $input = []): array
    {
        $file = SiteFixture::snippetPath() . '/' . self::FORM_DIR . '/upload.txt';
        return $this->runStep(array_replace([
            'snippet_params' => ['config' => self::CONFIG_FROM_MODX_ROOT],
            'post' => ['_mode' => 'conf', 'name' => '山田'],
            'files' => ['attach' => [
                'name' => 'upload.txt',
                'type' => 'text/plain',
                'tmp_name' => $file,
                'error' => 0,
                'size' => filesize($file),
            ]],
        ], $input));
    }

    /** @test */
    public function MODXのルートからの相対パスで設定とテンプレートを読む(): void
    {
        $this->assertSnapshot('modx_root_relative', $this->runStep([
            'snippet_params' => ['config' => self::CONFIG_FROM_MODX_ROOT],
        ]));
    }

    /** @test */
    public function 設置フォルダからの相対パスで設定を読む(): void
    {
        $this->assertSnapshot('install_relative_config', $this->runStep([
            'snippet_params' => ['config' => '@FILE:' . self::FORM_DIR . '/config.ini'],
        ]));
    }

    /** @test */
    public function 設置フォルダからの相対パスでテンプレートを読む(): void
    {
        $this->assertSnapshot('install_relative_template', $this->runStep([
            'snippet_params' => ['config' => '@FILE:assets/snippets/cfFormMailer/' . self::FORM_DIR . '/config_install_relative.ini'],
        ]));
    }

    /** @test */
    public function includesフォルダからの相対パスで設定を読む(): void
    {
        $this->assertSnapshot('includes_relative_config', $this->runStep([
            'snippet_params' => ['config' => '@FILE:../' . self::FORM_DIR . '/config.ini'],
        ]));
    }

    /** @test */
    public function チャンクの設定とテンプレートを読み角括弧と感嘆符の呼び出しを書き換える(): void
    {
        $this->assertSnapshot('chunk', $this->runStep([
            'snippet_params' => ['config' => 'paths_config'],
            'chunks' => [
                'paths_config' => "admin_mail = admin@example.com\ntmpl_input = paths_form\ntmpl_conf = paths_form\ntmpl_comp = paths_form\ntmpl_mail_admin = paths_form\n",
                'paths_form' => "<p>チャンクの入力画面 [!t3snippet!]</p>\n<form method=\"post\"><input type=\"text\" name=\"name\" /></form>\n",
            ],
        ]));
    }

    /** @test */
    public function リソースIDのテンプレートを読む(): void
    {
        $this->assertSnapshot('resource', $this->runStep([
            'snippet_params' => ['config' => 'paths_config'],
            'chunks' => [
                'paths_config' => "admin_mail = admin@example.com\ntmpl_input = 7\ntmpl_conf = 7\ntmpl_comp = 7\ntmpl_mail_admin = 7\n",
            ],
            'documents' => [
                7 => "<p>リソース 7 の入力画面</p>\n<form method=\"post\"><input type=\"text\" name=\"name\" /></form>\n",
            ],
        ]));
    }

    /** @test */
    public function 設定が読めないとき(): void
    {
        $this->assertSnapshot('config_missing', $this->runStep([
            'snippet_params' => ['config' => '@FILE:' . self::FORM_DIR . '/no-such-config.ini'],
        ]));
    }

    /** @test */
    public function テンプレートが読めないとき(): void
    {
        $this->assertSnapshot('template_missing', $this->runStep([
            'snippet_params' => ['config' => 'paths_config'],
            'chunks' => [
                'paths_config' => "admin_mail = admin@example.com\ntmpl_input = @FILE:no-such-form.html\ntmpl_conf = x\ntmpl_comp = x\ntmpl_mail_admin = x\n",
            ],
        ]));
    }

    /** @test */
    public function 設置フォルダの直下のadditionalMethodsを読むか(): void
    {
        $this->assertSnapshot('additional_methods_in_root', $this->runStep([
            'snippet_params' => ['config' => self::CONFIG_FROM_MODX_ROOT],
            'post' => ['_mode' => 'conf', 'name' => '山田'],
            'extra_files' => ['additionalMethods.inc.php' => self::USER_VALIDATOR],
        ]));
    }

    /** @test */
    public function includesフォルダのadditionalMethodsを読むか(): void
    {
        $this->assertSnapshot('additional_methods_in_includes', $this->runStep([
            'snippet_params' => ['config' => self::CONFIG_FROM_MODX_ROOT],
            'post' => ['_mode' => 'conf', 'name' => '山田'],
            'extra_files' => ['includes/additionalMethods.inc.php' => self::USER_VALIDATOR],
        ]));
    }

    /** @test */
    public function アップロードの一時ファイルを置く場所(): void
    {
        $this->assertSnapshot('upload_tmp', $this->confirmWithUpload());
    }

    /** @test */
    public function 設置フォルダの直下のclass_uploadを読むか(): void
    {
        $this->assertSnapshot('class_upload_in_root', $this->confirmWithUpload([
            'extra_files' => ['class.upload.php' => self::FAKE_UPLOAD_CLASS],
        ]));
    }

    /** @test */
    public function includesフォルダのclass_uploadを読むか(): void
    {
        $this->assertSnapshot('class_upload_in_includes', $this->confirmWithUpload([
            'extra_files' => ['includes/class.upload.php' => self::FAKE_UPLOAD_CLASS],
        ]));
    }
}
