<?php

namespace CfFormMailer\Tests\Unit;

use CfFormMailer\Upload\FileView;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/src/autoload.php';

/**
 * アップロードされたファイルを返すときのヘッダ
 */
class FileViewTest extends TestCase
{
    /** @test */
    public function 画像はそのまま表示させる(): void
    {
        $this->assertSame([
            'Cache-Control: private, must-revalidate',
            'Content-Type: image/png',
            'X-Content-Type-Options: nosniff',
        ], FileView::headers('image/png'));
    }

    /** @test */
    public function PDFはそのまま表示させる(): void
    {
        $this->assertNotContains('Content-Disposition: attachment', FileView::headers('application/pdf'));
    }

    /** @test */
    public function HTMLとSVGはダウンロードさせる(): void
    {
        $this->assertContains('Content-Disposition: attachment', FileView::headers('text/html'));
        $this->assertContains('Content-Disposition: attachment', FileView::headers('image/svg+xml'));
    }

    /** @test */
    public function 種類がわからなければoctet_streamでダウンロードさせる(): void
    {
        $headers = FileView::headers(false);
        $this->assertContains('Content-Type: application/octet-stream', $headers);
        $this->assertContains('Content-Disposition: attachment', $headers);
    }

    /** @test */
    public function URLはフォームのページにcfm_fileを付ける(): void
    {
        $this->assertSame('[~[*id*]~]?cfm_file=my%20photo', FileView::url('my photo'));
    }
}
