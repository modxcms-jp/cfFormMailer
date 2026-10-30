<?php
/**
 * 1 リクエストぶん cfFormMailer を動かす（子プロセスで実行する）。
 *
 * usage: php run-step.php <input.ser> <output.ser>
 *
 * cfFormMailer は exit・定数の定義・static のキャッシュを使うので、
 * 1 リクエストごとに新しいプロセスで動かす。入力と出力は serialize した配列。
 * 入力の形は tests/Harness/StepRunner.php を参照。
 */

$input = unserialize(file_get_contents($argv[1]));

$GLOBALS['__cfm'] = [
    'output_file' => $argv[2],
    'mail_fail' => $input['mail_fail'],
    'returned' => false,
    'return' => null,
    'php_errors' => [],
    'mails' => [],
    'logs' => [],
    'redirects' => [],
    'db_inserts' => [],
    'moved_files' => [],
    'parse_document_source' => 0,
];

error_reporting(E_ALL);
ini_set('display_errors', '0');
set_error_handler(function ($errno, $errstr) {
    // @ で抑止されたものは記録しない
    if (!(error_reporting() & $errno)) {
        return true;
    }
    $types = [
        E_WARNING => 'Warning',
        E_NOTICE => 'Notice',
        E_DEPRECATED => 'Deprecated',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_USER_DEPRECATED => 'User Deprecated',
    ];
    $GLOBALS['__cfm']['php_errors'][] = (isset($types[$errno]) ? $types[$errno] : 'Error ' . $errno) . ': ' . $errstr;
    return true;
});

register_shutdown_function(function () {
    $result = $GLOBALS['__cfm'];
    $echo = '';
    while (ob_get_level() > 0) {
        $echo = ob_get_clean() . $echo;
    }
    $result['echo'] = $echo;
    $result['exited'] = !$result['returned'];
    $last = error_get_last();
    $fatalTypes = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR;
    $result['fatal'] = ($last && ($last['type'] & $fatalTypes)) ? $last['message'] : null;
    $result['session'] = isset($_SESSION) ? $_SESSION : null;
    unset($result['output_file'], $result['mail_fail']);
    file_put_contents($GLOBALS['__cfm']['output_file'], serialize($result));
});

ob_start();

date_default_timezone_set('Asia/Tokyo');
mt_srand($input['mt_seed']);

define('MODX_BASE_PATH', $input['site_root'] . '/');
define('MODX_BASE_URL', '/');
define('MODX_SITE_URL', 'http://localhost/');
define('MODX_MANAGER_PATH', MODX_BASE_PATH . 'manager/');

require __DIR__ . '/fake/helpers.php';
require __DIR__ . '/fake/FakeDocumentParser.php';
require __DIR__ . '/fake/FakeMailer.php';
require __DIR__ . '/fake/FakePhx.php';
require __DIR__ . '/fake/FakeDb.php';

if ($input['user_functions'] !== null) {
    require $input['user_functions'];
}

$_GET = $input['get'];
$_POST = $input['post'];
$_FILES = $input['files'];
$_SESSION = $input['session'];
$_SERVER = array_merge(
    ['REQUEST_TIME' => $input['request_time'], 'REQUEST_METHOD' => $input['post'] ? 'POST' : 'GET'],
    $input['server']
);

$modx = new FakeDocumentParser($input);

// MODX がスニペットを評価するときと同じく、スニペットの引数を変数として展開してから実行する
$GLOBALS['__cfm']['return'] = (function ($modx, $__params) {
    extract($__params);
    return include MODX_BASE_PATH . 'assets/snippets/cfFormMailer/install-code.php';
})($modx, $input['snippet_params']);

$GLOBALS['__cfm']['returned'] = true;
