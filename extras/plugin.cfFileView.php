//<?php
/**
 * cfFileView
 * cfFormMailer用アップロードファイル表示（以前の書き方。使わない）
 *
 * 確認画面では [+項目名.url+] を使う。このプラグインは要らない。
 * 対応している MODX（evolution-jp 1.2 以降）では、見つからないページの documentIdentifier が 0 になるため、
 * 下の判定が成り立たず、このプラグインは働かない（cfFileView?field= は 404 になる）。
 * テンプレートの cfFileView?field=項目名 は、[+項目名.url+] に書き換える。
 *
 * @version 1.2
 * @author Clefarray Factory
 * @internal @events OnPageNotFound
 * @internal @modx_category cfFormMailer
 *
 */

$viewFileName = 'cfFileView';

if ($modx->event->name!=='OnPageNotFound') return;
if ($modx->documentIdentifier !== $viewFileName) return;

// スニペットのファイルが古い（FileView が無い）ときは何もしない
$cfm_autoload = MODX_BASE_PATH . 'assets/snippets/cfFormMailer/src/autoload.php';
if (!is_file($cfm_autoload)) return;
require_once $cfm_autoload;
if (!class_exists('CfFormMailer\Upload\FileView')) return;

\CfFormMailer\Upload\FileView::respond(getv('field'));
