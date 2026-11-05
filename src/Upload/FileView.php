<?php

namespace CfFormMailer\Upload;

/**
 * 確認画面で、アップロードされたファイルを見せる。
 *
 * アップロードされたファイルは、一時フォルダ（公開されていない場所、または推測しにくい名前）にあり、
 * どこにあるかは本人のセッション（_cf_uploaded）にしか無い。そのため、URL で直接は開けない。
 * セッションを見て、本人にだけファイルを返す。
 *
 * - スニペット：フォームのページに ?cfm_file=項目名 を付けて呼ぶ（確認画面の [+項目.url+]）
 * - プラグイン cfFileView：cfFileView?field=項目名（以前の書き方。互換のために残す）
 */
class FileView
{
    /** スニペットで、ファイルを返すときの GET の名前 */
    const QUERY_KEY = 'cfm_file';

    /** ブラウザでそのまま表示させる種類（画像（SVG を除く）と PDF）。ほかはダウンロードさせる */
    const INLINE_TYPES = '@^(image/(jpeg|png|gif|webp|bmp)|application/pdf)$@i';

    /**
     * 確認画面から、その項目のファイルを開く URL
     *
     * フォームのページの URL に cfm_file=項目名 を付ける。ページの URL にすでに ? があれば
     * （フレンドリー URL が無効で index.php?id=… のとき）& でつなぐ。HTML には書き出す側でエスケープする。
     *
     * @param string $pageUrl フォームのページの URL
     * @param string $field   項目名
     * @return string
     */
    public static function url($pageUrl, $field)
    {
        $separator = strpos($pageUrl, '?') === false ? '?' : '&';
        return $pageUrl . $separator . self::QUERY_KEY . '=' . rawurlencode($field);
    }

    /**
     * セッションにある、その項目のアップロードされたファイル
     *
     * @param mixed $field 項目名（GET の値をそのまま渡してよい）
     * @return array|null array('path' => ..., 'mime' => ...)。無ければ null
     */
    public static function find($field)
    {
        if (!is_string($field) || $field === '') {
            return null;
        }
        $uploaded = $_SESSION['_cf_uploaded'][$field] ?? null;
        if (!is_array($uploaded) || !is_string($uploaded['path'] ?? null) || !is_file($uploaded['path'])) {
            return null;
        }
        return $uploaded;
    }

    /**
     * ファイルを返すときのヘッダ
     *
     * @param mixed $mime ファイルの MIME タイプ
     * @return array
     */
    public static function headers($mime)
    {
        $mime = is_string($mime) && $mime !== '' ? $mime : 'application/octet-stream';
        $headers = array(
            'Cache-Control: private, must-revalidate',
            'Content-Type: ' . $mime,
            'X-Content-Type-Options: nosniff',
        );
        // HTML などを、サイトのオリジンでそのまま表示させない
        if (!preg_match(self::INLINE_TYPES, $mime)) {
            $headers[] = 'Content-Disposition: attachment';
        }
        return $headers;
    }

    /**
     * その項目のファイルを返して、処理を終える。ファイルが無ければ 404 を返す。
     *
     * それまでに出力をためていれば捨てる（ページの HTML がファイルに混ざらないように）。
     *
     * @param mixed $field 項目名（GET の値をそのまま渡してよい）
     */
    public static function respond($field)
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $file = self::find($field);
        if ($file === null) {
            http_response_code(404);
            exit;
        }
        foreach (self::headers($file['mime'] ?? null) as $header) {
            header($header);
        }
        readfile($file['path']);
        exit;
    }
}
