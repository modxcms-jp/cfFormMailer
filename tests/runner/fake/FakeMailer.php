<?php
/**
 * テスト用のメーラー（MODxMailer / PHPMailer の代わり）。
 *
 * Send() のたびに、その時点の内容を $GLOBALS['__cfm']['mails'] に記録する。
 * 入力の mail_fail が真なら送信に失敗したことにする。
 */
class FakeMailer
{
    public $From = '';
    public $FromName = '';
    public $Sender = '';
    public $Subject = '';
    public $Body = '';
    public $CharSet = '';
    public $Encoding = '';
    public $ErrorInfo = '';

    private $to = [];
    private $cc = [];
    private $bcc = [];
    private $replyTo = [];
    private $attachments = [];
    private $isHtml = null;

    public function AddAddress($address, $name = '')
    {
        $this->to[] = $this->format($address, $name);
        return true;
    }

    public function AddCC($address, $name = '')
    {
        $this->cc[] = $this->format($address, $name);
        return true;
    }

    public function AddBCC($address, $name = '')
    {
        $this->bcc[] = $this->format($address, $name);
        return true;
    }

    public function addReplyTo($address, $name = '')
    {
        $this->replyTo[] = $this->format($address, $name);
        return true;
    }

    public function setFrom($address, $name = '', $auto = true)
    {
        $this->From = $address;
        $this->FromName = $name;
        return true;
    }

    public function AddAttachment($path, $name = '', $encoding = 'base64', $type = '', $disposition = 'attachment')
    {
        $this->attachments[] = ['path' => $path, 'name' => $name];
        return true;
    }

    public function IsHTML($isHtml = true)
    {
        $this->isHtml = (bool) $isHtml;
    }

    public function Send()
    {
        $failed = !empty($GLOBALS['__cfm']['mail_fail']);
        $GLOBALS['__cfm']['mails'][] = [
            'to' => $this->to,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'reply_to' => $this->replyTo,
            'from' => $this->format($this->From, $this->FromName),
            'sender' => $this->Sender,
            'subject' => $this->Subject,
            'charset' => $this->CharSet,
            'encoding' => $this->Encoding,
            'is_html' => $this->isHtml,
            'attachments' => $this->attachments,
            'body' => $this->Body,
            'sent' => !$failed,
        ];
        if ($failed) {
            $this->ErrorInfo = 'fake send failure';
            return false;
        }
        return true;
    }

    private function format($address, $name)
    {
        return $name === '' ? (string) $address : sprintf('%s <%s>', $name, $address);
    }
}
