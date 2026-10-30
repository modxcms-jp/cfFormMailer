<?php

namespace CfFormMailer\Tests\Harness;

use PHPUnit\Framework\TestCase;

/**
 * 今の振る舞いをスナップショットで固定するテストの基底クラス。
 *
 * スナップショットは tests/Characterization/__snapshots__/<テストクラス>/<名前>.txt。
 * PHP の版で出力が違うときだけ <名前>.php74.txt のような版ごとのファイルを置く（あればそちらを使う）。
 *
 * 更新するとき:
 *   UPDATE_SNAPSHOTS=1        共通のスナップショットを書き直す
 *   UPDATE_SNAPSHOTS=version  いまの PHP の版のスナップショットを書く
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

        $expectedFile = is_file($versioned) ? $versioned : $base;
        if (!is_file($expectedFile)) {
            $this->fail(sprintf('スナップショットがありません: %s（UPDATE_SNAPSHOTS=1 で作れます）', $base));
        }
        $this->assertSame(file_get_contents($expectedFile), $actual, 'スナップショット: ' . $expectedFile);
    }
}
