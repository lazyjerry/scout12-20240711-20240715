<?php

namespace App\Libraries;

// Library for batch process locking
class FileLock
{
    private String $process_name = '';
    private boolean $status = false;
    private String $message = "";
    private boolean $no_lock = false;
    private String $lock_dir = '';
    private String $lock_file = '';

    // Function to initialize our object
    public function init(String $process_name, boolean $no_lock, String $lock_dir = "")
    {
        // $this->process_id = posix_getpid();
        $this->process_name = str_replace(' ', '_', $process_name);

        $this->no_lock = $no_lock;

        if ($lock_dir && is_dir($lock_dir)) {
            $this->lock_dir = $lock_dir;
        } else {
            $this->lock_dir = FCPATH . "writable/locks/";
        }
        $this->lock_file = $this->lock_dir . $this->process_name . '.lock';

        // var_dump($this->lock_file);die();
    }

    // Function to attempt to set a lock.  Returns true on success and false on failure.
    // On failure will populate $this->message with info
    public function setLock($skip_lock = true)
    {
        if (isset($this->no_lock) && !empty($this->no_lock)) {
            $this->message = "no lock";
            return true;
        }
        $this->fp = fopen($this->lock_file, 'w+');
        /*
        — LOCK_SH, 取得共享鎖定, 讀取用
        — LOCK_EX, 取得獨佔鎖定, 寫入用
        — LOCK_UN, 解除鎖定
        — LOCK_NB, 不要讓 flock() 在鎖定時堵塞
         */
        if (flock($this->fp, ($skip_lock) ? (LOCK_EX | LOCK_NB) : LOCK_EX)) {
            $this->status = true;
            $this->message = "lock";
        } else {
            $this->status = false;
            $this->message = "error lock";
        }
        return $this->status;
    }

    // Clear the current lock file
    public function clearLock()
    {
        if ($this->status) {
            flock($this->fp, LOCK_UN);
            fclose($this->fp);
            $this->status = false;
            $this->message = "unlock";
            return true;
        }
        return false;
    }

    // Override the lock dir if desired
    public function setLockDir($lock_dir = false)
    {
        $lock_dir = realpath($lock_dir);
        if ($lock_dir) {
            $this->lock_dir = $lock_dir;
            $this->lock_file = $this->lock_dir . $this->process_name . '.lock';
        }
    }

    public function getLockFile()
    {
        return $this->lock_file;
    }

    public function isLock()
    {
        return $this->status;
    }

    public function getMessage()
    {
        return $this->message;
    }
}
