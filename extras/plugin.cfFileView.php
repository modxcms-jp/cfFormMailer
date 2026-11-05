//<?php
/**
 * cfFileView
 * cfFormMailer用アップロードファイル表示
 *
 * @version 1.1
 * @author Clefarray Factory
 * @internal @events OnPageNotFound
 * @internal @modx_category cfFormMailer
 * 
 */

$viewFileName = 'cfFileView';

if ($modx->event->name!=='OnPageNotFound') return;
if ($modx->documentIdentifier !== $viewFileName) return;

$field = getv('field', '');

if (!is_string($field) || $field === '') exit;

$uploaded_file = sessionv("_cf_uploaded.{$field}");
if (!is_array($uploaded_file) || !is_file($uploaded_file['path'])) exit;

header('P3P: CP="NOI NID ADMa OUR IND UNI COM NAV"');
header('Cache-Control: private, must-revalidate');
header('Content-type: ' . $uploaded_file['mime']);
header('X-Content-Type-Options: nosniff');
// ブラウザでそのまま表示するのは、画像（SVG を除く）と PDF だけにする
// （HTML などをサイトのオリジンで表示させない）
if (!preg_match('@^(image/(jpeg|png|gif|webp|bmp)|application/pdf)$@i', (string) $uploaded_file['mime'])) {
    header('Content-Disposition: attachment');
}
readfile($uploaded_file['path']);
exit;
