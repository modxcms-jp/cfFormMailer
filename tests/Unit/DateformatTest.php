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
        public function mb_strftimeが扱わない書式は先にstrftimeと同じ値にする(): void
        {
            $this->format('2026-01-01 09:03:05', '%j %U %W %g %R %T %D %F %h|%k|%l|%-H %-I %-M %-S %-y %-j');
            $this->assertSame(
                '001 00 00 26 09:03 09:03:05 01/01/26 2026-01-01 Jan| 9| 9|9 9 3 5 26 1',
                $GLOBALS['modx']->calls[0][0]
            );
        }

        /** @test */
        public function mb_strftimeに任せる書式と百分率記号はそのまま渡す(): void
        {
            $this->format('2026-01-01 09:03:05', '%Y年%-m月%-d日（%曜） %%j %a');
            $this->assertSame('%Y年%-m月%-d日（%曜） %%j %a', $GLOBALS['modx']->calls[0][0]);
        }

        /**
         * evolution-jp の mb_strftime()（テスト用の写し）と合わせた結果が、strftime() と同じになる。
         * strftime() は PHP 8.1 から非推奨なので、それより前の版でだけ確かめる
         *
         * @test
         */
        public function これまでのstrftimeと同じ結果になる(): void
        {
            if (PHP_VERSION_ID >= 80100 || !function_exists('strftime')) {
                $this->markTestSkipped('strftime() と比べるのは PHP 8.1 未満だけ');
            }
            require_once dirname(__DIR__) . '/runner/fake/FakeDocumentParser.php';
            $GLOBALS['modx'] = (new ReflectionClass(\FakeDocumentParser::class))->newInstanceWithoutConstructor();
            setlocale(LC_TIME, 'C');

            // 比べないもの:
            // %e は mb_strftime() では空白で桁をそろえない（これまでの PHP 8.1 以降と同じ）。
            // %s は glibc の strftime() が PHP のタイムゾーン設定を見ずにずれる。
            // %a・%A は mb_strftime() では言語ファイルの曜日名になる
            $format = '%Y %y %m %d %H %I %M %S %p %w %u %V %G %g %z '
                . '%j %U %W %R %T %D %F %h %k %l %%j '
                . '%-m %-d %-H %-I %-M %-S %-y %-j %b %B';
            $dates = ['2026-01-01 09:03:05', '2026-01-04 00:00:00', '2026-12-31 23:59:59', '2024-02-29 12:30:00', '2027-01-03 13:07:09'];
            foreach ($dates as $date) {
                $this->assertSame(strftime($format, strtotime($date)), $this->format($date, $format), $date);
            }
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
