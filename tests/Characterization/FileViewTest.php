<?php

namespace CfFormMailer\Tests\Characterization;

use CfFormMailer\Tests\Harness\CharacterizationTestCase;
use CfFormMailer\Tests\Harness\SiteFixture;

/**
 * 確認画面で、アップロードされたファイルを見せる（プラグイン cfFileView を使わない方法）。
 *
 * 確認画面の [+項目.url+] は、フォームのページに ?cfm_file=項目名 を付けた URL になる。
 * その URL で呼ばれたスニペットは、セッションにある本人のファイルを返して終える。
 * （ヘッダは CLI では見えないので、tests/Unit/FileViewTest で確かめる）
 */
class FileViewTest extends CharacterizationTestCase
{
    private const CONFIG = '@FILE:assets/snippets/cfFormMailer/forms/_t/file_view/config.ini';

    private function uploadedPath(): string
    {
        return SiteFixture::snippetPath() . '/forms/_t/paths/upload.txt';
    }

    private function view($field, array $session): array
    {
        return $this->runStep([
            'snippet_params' => ['config' => self::CONFIG],
            'get' => ['cfm_file' => $field],
            'session' => $session,
        ]);
    }

    /** @test */
    public function 確認画面にファイルを開くURLを出す(): void
    {
        $path = $this->uploadedPath();
        $this->assertSnapshot('conf', $this->runStep([
            'snippet_params' => ['config' => self::CONFIG],
            'post' => ['_mode' => 'conf', 'name' => '山田'],
            'files' => ['attach' => [
                'name' => 'upload.txt',
                'type' => 'text/plain',
                'tmp_name' => $path,
                'error' => 0,
                'size' => filesize($path),
            ]],
        ]));
    }

    /** @test */
    public function セッションにあるファイルを返して終える(): void
    {
        $result = $this->view('attach', [
            '_cf_uploaded' => ['attach' => ['path' => $this->uploadedPath(), 'mime' => 'text/plain']],
        ]);
        $this->assertTrue($result['exited']);
        $this->assertSame(file_get_contents($this->uploadedPath()), $result['stdout']);
        $this->assertSame([], $result['php_errors']);
    }

    /** @test */
    public function セッションに無い項目なら何も返さずに終える(): void
    {
        $result = $this->view('other', [
            '_cf_uploaded' => ['attach' => ['path' => $this->uploadedPath(), 'mime' => 'text/plain']],
        ]);
        $this->assertTrue($result['exited']);
        $this->assertSame('', $result['stdout']);
        $this->assertSame([], $result['php_errors']);
    }

    /** @test */
    public function 項目名が配列なら何も返さずに終える(): void
    {
        $result = $this->view(['attach'], [
            '_cf_uploaded' => ['attach' => ['path' => $this->uploadedPath(), 'mime' => 'text/plain']],
        ]);
        $this->assertTrue($result['exited']);
        $this->assertSame('', $result['stdout']);
        $this->assertSame([], $result['php_errors']);
    }
}
