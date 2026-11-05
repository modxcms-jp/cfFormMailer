<?php
/**
 * cfFormMailer
 * 
 * @author  Clefarray Factory
 * @link  http://www.clefarray-web.net/
 * @version 1.7.0
 *
 * Documentation: http://www.clefarray-web.net/blog/manual/cfFormMailer_manual.html
 * LICENSE: GNU General Public License (GPL) (http://www.gnu.org/copyleft/gpl.html)
 */

/** @var documentParser $modx */
if ($modx->isBackend()) {
    return '';
}

if (!isset($config)) {
    return '<strong>ERROR!:</strong> 「config」パラメータは必須です';
}

define('CFM_PATH', __DIR__ . '/');

require_once CFM_PATH . 'src/autoload.php';

// 確認画面で、アップロードされたファイルを見せる（[+項目.url+] のリンク先）
if (isset($_GET[\CfFormMailer\Upload\FileView::QUERY_KEY])) {
    \CfFormMailer\Upload\FileView::respond($_GET[\CfFormMailer\Upload\FileView::QUERY_KEY]);
}

$mf = new Class_cfFormMailer($modx);
$mf->parseConfig($config);

if ($mf->hasSystemError()) {
    return '<strong>ERROR!</strong> ' . $mf->getSystemError();
}

if (is_file(CFM_PATH . 'additionalMethods.inc.php')) {
    include_once CFM_PATH . 'additionalMethods.inc.php';
}

if (!postv()) {
    return $mf->renderForm();
}

if (postv('return')) {
    return $mf->renderFormOnBack();
}

if ($mf->alreadySent()) {
    return $mf->raiseError('すでに送信しています');
}

if (postv('_mode') === 'conf') {
    if(!$mf->validate()) {
        return $mf->renderFormWithError();
    }
    return $mf->renderConfirm();
}

if (postv('_mode') === 'send') {
    if (!$mf->isValidToken(postv('_cffm_token'))) {
        return $mf->raiseError('画面遷移が正常に行われませんでした');
    }
    // 確認画面の hidden の値は書き換えられるので、送られてきた値ではなく、
    // 確認画面を出したときに検証を通った値で送信する
    if (!$mf->useConfirmedForm()) {
        return $mf->raiseError('画面遷移が正常に行われませんでした');
    }
    $sent = $mf->sendMail();
    if (!$sent) {
        return $mf->raiseError($mf->getError());
    }
    
    $mf->cleanUploadedFiles();
    $mf->storeDataInSession();
    $mf->storeDB();
    
    return $mf->renderComplete();
}

return $mf->renderForm();
