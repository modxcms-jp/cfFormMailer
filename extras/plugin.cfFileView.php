//<?php
/**
 * cfFileView
 * cfFormMailer用アップロードファイル表示（以前の書き方の互換用）
 *
 * 確認画面で [+項目名.url+] を使えば、このプラグインは要らない。
 * テンプレートに cfFileView?field=項目名 と書いているサイトのために残している。
 * 処理はスニペット側（src/Upload/FileView.php）と同じものを呼ぶ。
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
