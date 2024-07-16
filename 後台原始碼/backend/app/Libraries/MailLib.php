<?php

namespace App\Libraries;

class MailLib
{
    private $email;
    private $debugger;

    /**
     * 初始化資料
     * @param  array  $config 設定資料，參考：https://codeigniter4.github.io/userguide/libraries/email.html?highlight=mail#sending-email
     */
    public function init(array $config = [])
    {
        if (!isset($this->email)) {
            $this->email = \Config\Services::email();
        }
        if (!empty($config)) {
            /*
            一般會用到的
            $smpleConfig = [
            'protocol' => 'smtp',
            'SMTPHost' => '',
            'SMTPUser' => '',
            'SMTPPass' => '',
            'SMTPPort' => '465', // 25, 465 ..
            'SMTPTimeout' => '30',
            'SMTPCrypto' => 'ssl', // tls or ssl
            'mailType' => 'html', // text or html
            ];
             */
            $this->email->initialize($config);
        }
    }

    public function getEmailObject()
    {
        if (!isset($this->email)) {
            $this->init();
        }
        return $this->email;
    }

    public function getLastDebugger()
    {
        return $this->debugger;
    }

    public function doMail(string $to, string $from, string $fromName, string $subject, string $message)
    {
        if (!isset($this->email)) {
            $this->init();
        }

        $this->email->setTo($to);
        $this->email->setFrom($from, $fromName);

        $this->email->setSubject($subject);
        $this->email->setMessage($message);

        $isSuccess = $this->email->send();
        $this->debugger = $this->email->printDebugger(['headers', 'subject', 'body']);
        return $isSuccess;
    }

}
