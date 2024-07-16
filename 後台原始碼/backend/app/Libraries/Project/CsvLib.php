<?php

namespace App\Libraries\Project;

class CsvLib
{

    // 檢查資料是否存在且不為(空 或 0)
    private static function isValidData(&$data)
    {
        return (isset($data) && !empty($data));
    }

    private static function deleteOldFile($filePath, $minAgo)
    {

        if (!is_dir($fullpath)) {
            $filedate = filemtime($fullpath);
            $minutes = round((time() - $filedate) / 60);
            if ($minutes > $minAgo) {
                unlink($fullpath);
            }
        }
    }

    public static function deleteOldCsvFiles($dir, $minAgo)
    {
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (false !== ($file = readdir($dh))) {
                    if ($file != "." || $file != "..") {
                        continue;
                    }
                    $fullpath = $dir . "/" . $file;
                    if (str_ends_with($fullpath, ".csv")) {
                        self::deleteOldFile($fullpath, $minAgo);
                    }
                }
            }
            closedir($dh);
        }
    }

    public static function exportData(string $filename, string $filePath, array $header, array $data): int
    {
        // file creation
        $file = fopen($filePath, 'w');
        // 中文
        fwrite($file, "\xEF\xBB\xBF");

        fputcsv($file, $header);
        $count = 0;
        foreach ($data as $key => $line) {
            fputcsv($file, $line);
            $count++;
        }
        fclose($file);

        return file_exists($filePath) ? $count : -1;
    }

}
