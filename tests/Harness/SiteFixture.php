<?php

namespace CfFormMailer\Tests\Harness;

/**
 * テスト用の MODX のサイトを一時フォルダに作る（PHPUnit の実行ごとに 1 回）。
 *
 * <site>/assets/snippets/cfFormMailer/ にリポジトリを写すので、
 * install-code.php や @FILE: のパスが本物の設置と同じように解決される。
 * - tests/fixtures/forms/<name>/      → <snippet>/forms/_t/<name>/
 * - tests/fixtures/modx-root/<file>   → <site>/<file>
 */
class SiteFixture
{
    /** リポジトリの直下で、設置フォルダに写さないもの */
    private const EXCLUDE = [
        '.git', '.github', '.claude', '.idea', '.vscode', 'vendor', 'tests', 'docs', 'node_modules',
        'composer.json', 'composer.lock', 'phpunit.xml.dist', '.phpunit.result.cache',
    ];

    private static $root;

    public static function root(): string
    {
        if (self::$root === null) {
            self::$root = self::build();
            register_shutdown_function(function () {
                self::remove(self::$root);
            });
        }
        return self::$root;
    }

    public static function snippetPath(): string
    {
        return self::root() . '/assets/snippets/cfFormMailer';
    }

    public static function repoRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    private static function build(): string
    {
        $root = sys_get_temp_dir() . '/cfm-site-' . getmypid() . '-' . bin2hex(random_bytes(4));
        $snippet = $root . '/assets/snippets/cfFormMailer';
        mkdir($snippet, 0777, true);

        foreach (new \DirectoryIterator(self::repoRoot()) as $item) {
            if ($item->isDot() || in_array($item->getFilename(), self::EXCLUDE, true)) {
                continue;
            }
            self::copy($item->getPathname(), $snippet . '/' . $item->getFilename());
        }

        $fixtures = self::repoRoot() . '/tests/fixtures';
        if (is_dir($fixtures . '/forms')) {
            self::copy($fixtures . '/forms', $snippet . '/forms/_t');
        }
        if (is_dir($fixtures . '/modx-root')) {
            foreach (new \DirectoryIterator($fixtures . '/modx-root') as $item) {
                if (!$item->isDot()) {
                    self::copy($item->getPathname(), $root . '/' . $item->getFilename());
                }
            }
        }
        return $root;
    }

    private static function copy(string $from, string $to): void
    {
        if (is_file($from)) {
            copy($from, $to);
            return;
        }
        if (!is_dir($to)) {
            mkdir($to, 0777, true);
        }
        foreach (new \DirectoryIterator($from) as $item) {
            if (!$item->isDot()) {
                self::copy($item->getPathname(), $to . '/' . $item->getFilename());
            }
        }
    }

    private static function remove(string $path): void
    {
        if (is_file($path) || is_link($path)) {
            unlink($path);
            return;
        }
        if (!is_dir($path)) {
            return;
        }
        foreach (new \DirectoryIterator($path) as $item) {
            if (!$item->isDot()) {
                self::remove($item->getPathname());
            }
        }
        rmdir($path);
    }
}
