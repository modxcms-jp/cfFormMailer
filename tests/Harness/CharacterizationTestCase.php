<?php

namespace CfFormMailer\Tests\Harness;

use PHPUnit\Framework\TestCase;

/**
 * 今の振る舞いをスナップショットで固定するテストの基底クラス。
 *
 * スナップショットは tests/Characterization/__snapshots__/<テストクラス>/<名前>.txt。
 * PHP の版で出力が違うときだけ、版ごとのファイルを置く。使うファイルは次の順に探す。
 *   1. <名前>.php74.txt   その版ちょうど（PHP 7.4 だけ）
 *   2. <名前>.lt81.txt    その版より前（PHP 8.1 より前。複数あれば、境目の版がいちばん小さいもの）
 *   3. <名前>.txt         共通
 * 例：PHP 8.1 から出る Deprecated は、共通のファイルに入れ、lt81 のファイルには入れない。
 *
 * 更新するとき:
 *   UPDATE_SNAPSHOTS=1        共通のスナップショットを書き直す
 *   UPDATE_SNAPSHOTS=version  いまの PHP の版ちょうどのスナップショットを書く
 */
abstract class CharacterizationTestCase extends TestCase
{
    protected function runStep(array $input = []): array
    {
        return StepRunner::run($input);
    }

    protected function assertSnapshot(string $name, array $result): void
    {
        $this->assertSnapshotText($name, SnapshotRenderer::render($result));
    }

    protected function assertSnapshotText(string $name, string $actual): void
    {
        $dir = dirname(__DIR__) . '/Characterization/__snapshots__/' . (new \ReflectionClass($this))->getShortName();
        $base = sprintf('%s/%s.txt', $dir, $name);
        $versioned = sprintf('%s/%s.php%d%d.txt', $dir, $name, PHP_MAJOR_VERSION, PHP_MINOR_VERSION);

        $update = getenv('UPDATE_SNAPSHOTS');
        if ($update === '1' || $update === 'version') {
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($update === 'version' ? $versioned : $base, $actual);
            $this->addToAssertionCount(1);
            return;
        }

        $expectedFile = $this->findSnapshot($dir, $name, $versioned, $base);
        if ($expectedFile === null) {
            $this->fail(sprintf('スナップショットがありません: %s（UPDATE_SNAPSHOTS=1 で作れます）', $base));
        }
        $this->assertSame(file_get_contents($expectedFile), $actual, 'スナップショット: ' . $expectedFile);
    }

    private function findSnapshot(string $dir, string $name, string $versioned, string $base): ?string
    {
        if (is_file($versioned)) {
            return $versioned;
        }
        $current = PHP_MAJOR_VERSION * 10 + PHP_MINOR_VERSION;
        $before = null;
        foreach (glob(sprintf('%s/%s.lt*.txt', $dir, $name)) ?: [] as $file) {
            if (!preg_match('/\.lt(\d)(\d)\.txt$/', $file, $m)) {
                continue;
            }
            $limit = (int) $m[1] * 10 + (int) $m[2];
            if ($current < $limit && ($before === null || $limit < $before[0])) {
                $before = [$limit, $file];
            }
        }
        if ($before !== null) {
            return $before[1];
        }
        return is_file($base) ? $base : null;
    }
}
