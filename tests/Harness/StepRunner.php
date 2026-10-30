<?php

namespace CfFormMailer\Tests\Harness;

/**
 * 1 リクエストぶんを子プロセス（tests/runner/run-step.php）で動かす。
 *
 * 入力のキー（省略したものは既定値）:
 * - snippet_params  スニペットの引数（既定: サンプルの設定）
 * - get / post / files / session / server
 * - modx_config     evo()->config（既定値に上書きでマージする）
 * - event_params    evo()->event->params
 * - chunks          チャンク名 => 内容
 * - documents       リソース ID => 内容
 * - db_tables       cfFormDB のテーブルの数（SHOW TABLES の件数）
 * - mail_fail       真ならメールの送信に失敗させる
 * - request_time / mt_seed
 * - user_functions  _validate_* などを定義した PHP ファイル（任意）
 * - extra_files     設置フォルダからの相対パス => 内容。このリクエストの間だけ置く
 *                   （additionalMethods.inc.php などの置き場所を試すときに使う）
 *
 * 結果は run-step.php が返す配列（return / echo / exited / session / mails / logs など）。
 */
class StepRunner
{
    public const SAMPLE_CONFIG = '@FILE:assets/snippets/cfFormMailer/forms/sample/config.ini';

    public static function defaults(): array
    {
        return [
            'site_root' => SiteFixture::root(),
            'snippet_params' => ['config' => self::SAMPLE_CONFIG],
            'get' => [],
            'post' => [],
            'files' => [],
            'session' => [],
            'server' => [],
            'modx_config' => [
                'emailsender' => 'admin@example.com',
                'site_name' => 'テストサイト',
                'manager_language' => 'japanese-utf8',
                'modx_charset' => 'UTF-8',
            ],
            'event_params' => [],
            'chunks' => [],
            'documents' => [],
            'db_tables' => 0,
            'mail_fail' => false,
            'request_time' => 1793232000,
            'mt_seed' => 12345,
            'user_functions' => null,
            'extra_files' => [],
        ];
    }

    public static function run(array $input = []): array
    {
        $defaults = self::defaults();
        if (isset($input['modx_config'])) {
            $input['modx_config'] = array_replace($defaults['modx_config'], $input['modx_config']);
        }
        $input = array_replace($defaults, $input);

        $placed = [];
        foreach ($input['extra_files'] as $path => $content) {
            $file = SiteFixture::snippetPath() . '/' . $path;
            if (file_exists($file)) {
                throw new \RuntimeException('extra_files が既にあるファイルを上書きしようとしました: ' . $path);
            }
            if (!is_dir(dirname($file))) {
                mkdir(dirname($file), 0777, true);
            }
            file_put_contents($file, $content);
            $placed[] = $file;
        }
        try {
            return self::runProcess($input);
        } finally {
            foreach ($placed as $file) {
                unlink($file);
            }
        }
    }

    private static function runProcess(array $input): array
    {
        $inFile = tempnam(sys_get_temp_dir(), 'cfm-in-');
        $outFile = tempnam(sys_get_temp_dir(), 'cfm-out-');
        unlink($outFile);
        file_put_contents($inFile, serialize($input));

        $command = [PHP_BINARY, dirname(__DIR__) . '/runner/run-step.php', $inFile, $outFile];
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
        unlink($inFile);

        if (!is_file($outFile)) {
            throw new \RuntimeException(sprintf(
                "run-step.php が結果を書きませんでした（exit code %d）\nstdout:\n%s\nstderr:\n%s",
                $exitCode,
                $stdout,
                $stderr
            ));
        }
        $result = unserialize(file_get_contents($outFile));
        unlink($outFile);
        $result['stdout'] = $stdout;
        return $result;
    }
}
