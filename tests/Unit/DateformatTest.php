<?php

namespace CfFormMailer\Tests\Unit {

    use Class_cfFormMailer;
    use PHPUnit\Framework\TestCase;
    use ReflectionClass;

    require_once dirname(__DIR__, 2) . '/src/autoload.php';

    /**
     * テキストフィルタ dateformat（_f_dateformat）
     */
    class DateformatTest extends TestCase
    {
        private $savedModx;
        private $savedTimezone;

        protected function setUp(): void
        {
            $this->savedModx = $GLOBALS['modx'] ?? null;
            $this->savedTimezone = date_default_timezone_get();
            date_default_timezone_set('Asia/Tokyo');
            // evolution-jp の DocumentParser の代わり。mb_strftime() の呼ばれ方を記録する
            $GLOBALS['modx'] = new class {
                public $calls = [];

                public function mb_strftime($format = '%Y/%m/%d', $timestamp = '')
                {
                    $this->calls[] = [$format, $timestamp];
                    return 'mb:' . date('Y-n-j H:i', $timestamp);
                }
            };
        }

        protected function tearDown(): void
        {
            $GLOBALS['modx'] = $this->savedModx;
            date_default_timezone_set($this->savedTimezone);
        }

        private function format($text, $param)
        {
            $class = new ReflectionClass(Class_cfFormMailer::class);
            $mailer = $class->newInstanceWithoutConstructor();
            $method = $class->getMethod('_f_dateformat');
            if (PHP_VERSION_ID < 80100) {
                // PHP 8.1 からは不要（8.5 で非推奨）
                $method->setAccessible(true);
            }
            return $method->invoke($mailer, $text, $param);
        }

        /** @test */
        public function strftime形式はmb_strftimeに任せる(): void
        {
            $this->assertSame('mb:2026-8-4 09:03', $this->format('2026-08-04 09:03:00', '%Y年%-m月%-d日 %H時%M分'));
            $this->assertSame(
                [['%Y年%-m月%-d日 %H時%M分', strtotime('2026-08-04 09:03:00')]],
                $GLOBALS['modx']->calls
            );
        }

        /** @test */
        public function date形式はそのままDateTimeで出す(): void
        {
            $this->assertSame('2026/08/04 9:03', $this->format('2026-08-04 09:03:00', 'Y/m/d G:i'));
            $this->assertSame([], $GLOBALS['modx']->calls);
        }

        /** @test */
        public function 日付として読めない値はそのまま(): void
        {
            $this->assertSame('&nbsp;', $this->format('&nbsp;', '%Y年%-m月'));
            $this->assertSame([], $GLOBALS['modx']->calls);
        }

        /** @test */
        public function 配列は要素ごとに変換する(): void
        {
            $this->assertSame(
                ['mb:2026-8-4 00:00', 'mb:2026-12-25 00:00'],
                $this->format(['2026-08-04', '2026-12-25'], '%-m/%-d')
            );
        }
    }
}

namespace {
    // MODX 本体の evo()（$modx を返す）。他のテストで定義済みなら、それを使う
    if (!function_exists('evo')) {
        function evo()
        {
            global $modx;
            return $modx;
        }
    }
}
