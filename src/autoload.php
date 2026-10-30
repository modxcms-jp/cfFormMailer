<?php
/**
 * cfFormMailer のクラスの autoloader（Composer は使わない）。
 *
 * - Class_cfFormMailer       → src/Class_cfFormMailer.php
 * - CfFormMailer\Foo\Bar     → src/Foo/Bar.php
 * それ以外のクラス（class.upload.php の upload など）には触らない。
 */

if (!defined('CFM_AUTOLOAD')) {
    define('CFM_AUTOLOAD', true);

    spl_autoload_register(function ($class) {
        if ($class === 'Class_cfFormMailer') {
            require_once __DIR__ . '/Class_cfFormMailer.php';
            return;
        }
        $prefix = 'CfFormMailer\\';
        if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
            return;
        }
        $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    });
}
