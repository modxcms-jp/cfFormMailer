<?php
/**
 * テスト用の $modx。cfFormMailer が呼ぶメソッドとプロパティだけを持つ。
 *
 * 呼ばれた内容は $GLOBALS['__cfm'] に記録し、run-step.php が結果として返す。
 * 振る舞いは evolution-jp（commit 8c703cb）の DocumentParser に合わせている。
 */
class FakeDocumentParser
{
    public $config = [];
    public $event;
    public $mail;
    public $filter;
    public $db;
    public $documentIdentifier = 1;

    private $chunks;

    public function __construct(array $input)
    {
        $this->config = $input['modx_config'];
        $this->event = new stdClass();
        $this->event->params = $input['event_params'];
        $this->db = new FakeDb($input);
        $this->chunks = $input['chunks'];
    }

    public function isBackend()
    {
        return false;
    }

    public function getChunk($chunk_name)
    {
        return isset($this->chunks[$chunk_name]) ? $this->chunks[$chunk_name] : null;
    }

    /**
     * MODX のタグは解釈せず、そのまま返す（呼ばれたことだけを記録する）
     */
    public function parseDocumentSource($source)
    {
        $GLOBALS['__cfm']['parse_document_source']++;
        return $source;
    }

    /**
     * 本物と同じく、値のある [+key+] だけを置き換える。配列の値は置き換えない。
     * 修飾子（[+key:mod+]）は FakePhx と同じ書式で、呼ばれたことがわかる文字列にする。
     */
    public function parseText($tpl = '', $ph = [], $left = '[+', $right = '+]', $execModifier = true)
    {
        if (!$ph || !$tpl) {
            return $tpl;
        }
        $pattern = '/' . preg_quote($left, '/') . '(.+?)' . preg_quote($right, '/') . '/s';
        if (!preg_match_all($pattern, $tpl, $matches)) {
            return $tpl;
        }
        foreach ($matches[1] as $i => $key) {
            $modifiers = false;
            if (strpos($key, ':') !== false && $execModifier) {
                list($key, $modifiers) = explode(':', $key, 2);
            }
            if (!isset($ph[$key])) {
                continue;
            }
            $value = $ph[$key];
            if ($modifiers !== false) {
                $value = (new FakePhx())->phxFilter($key, $value, $modifiers);
            }
            if (is_array($value)) {
                continue;
            }
            $tpl = str_replace($matches[0][$i], (string) $value, $tpl);
        }
        return $tpl;
    }

    public function htmlspecialchars($str, $flags = ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, $encode = null, $double_encode = true)
    {
        if ($str === null) {
            return '';
        }
        if (is_object($str)) {
            return $str;
        }
        if (is_array($str)) {
            foreach ($str as $k => $v) {
                $str[$k] = $this->htmlspecialchars($v, $flags, $encode, $double_encode);
            }
            return $str;
        }
        if ($encode === null) {
            $encode = isset($this->config['modx_charset']) ? $this->config['modx_charset'] : 'utf-8';
        }
        if (!is_string($str)) {
            $str = is_bool($str) ? ($str ? '1' : '') : (string) $str;
        }
        return htmlspecialchars($str, $flags, $encode, $double_encode);
    }

    public function loadExtension($extname)
    {
        switch (strtolower($extname)) {
            case 'modifiers':
            case 'phx':
            case 'filter':
                $this->filter = new FakePhx();
                return true;
            case 'modxmailer':
                // 本物も呼ぶたびに new MODxMailer で作り直す
                $this->mail = new FakeMailer();
                return true;
            default:
                return false;
        }
    }

    public function move_uploaded_file($tmp_path, $target_path)
    {
        $GLOBALS['__cfm']['moved_files'][] = [$tmp_path, $target_path];
        return copy($tmp_path, $target_path);
    }

    public function sendRedirect($url, $count_attempts = 0, $type = 'REDIRECT_HEADER', $responseCode = '')
    {
        $GLOBALS['__cfm']['redirects'][] = $url;
    }

    public function makeUrl($id = '', $alias = '', $args = '', $scheme = 'full', $ignoreReference = false)
    {
        return 'url:' . $id;
    }

    public function logEvent($evtid, $type, $msg, $source = 'Parser')
    {
        $GLOBALS['__cfm']['logs'][] = [
            'evtid' => $evtid,
            'type' => $type,
            'source' => $source,
            'msg' => $msg,
        ];
    }
}
