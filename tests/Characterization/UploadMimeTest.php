<?php

namespace CfFormMailer\Tests\Characterization;

use CfFormMailer\Tests\Harness\CharacterizationTestCase;
use CfFormMailer\Tests\Harness\SiteFixture;

/**
 * アップロードされたファイルの MIME タイプの判定。
 *
 * 確認画面の [+attach.filetype+] と、セッションの _cf_uploaded の mime で、判定結果を見る。
 * （allowtype はテストの環境では is_uploaded_file() が偽になるので通せない）
 */
class UploadMimeTest extends CharacterizationTestCase
{
    private const CONFIG = '@FILE:assets/snippets/cfFormMailer/forms/_t/paths/config.ini';

    /**
     * @param string $file          forms/_t/paths/ の下のファイル名
     * @param string $declaredType  ブラウザが送ってきた種類（$_FILES[...]['type']）
     */
    private function confirmWithFile(string $file, string $declaredType): array
    {
        $path = SiteFixture::snippetPath() . '/forms/_t/paths/' . $file;
        return $this->runStep([
            'snippet_params' => ['config' => self::CONFIG],
            'post' => ['_mode' => 'conf', 'name' => '山田'],
            'files' => ['attach' => [
                'name' => $file,
                'type' => $declaredType,
                'tmp_name' => $path,
                'error' => 0,
                'size' => filesize($path),
            ]],
        ]);
    }

    /** @test */
    public function 本物のPDFをブラウザがoctet_streamと申告したとき(): void
    {
        $this->assertSnapshot('real_pdf_declared_octet_stream', $this->confirmWithFile('document.pdf', 'application/octet-stream'));
    }

    /** @test */
    public function 中身がテキストのPDFをブラウザがPDFと申告したとき(): void
    {
        $this->assertSnapshot('text_declared_pdf', $this->confirmWithFile('fake.pdf', 'application/pdf'));
    }

    /** @test */
    public function テキストのファイルをブラウザがテキストと申告したとき(): void
    {
        $this->assertSnapshot('text_declared_text', $this->confirmWithFile('upload.txt', 'text/plain'));
    }
}
