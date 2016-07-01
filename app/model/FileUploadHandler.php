<?php

namespace App\Model;

use Nette\Http\FileUpload;
use Nette\Object;

class FileUploadHandler extends Object
{
    private static function getUploadDir()
    {
        return WWW_DIR . '/../upload/';
    }

    public static function upload(FileUpload $file)
    {
        $filename = $file->getSanitizedName();
        $folder = strtolower(date('F'));

        $pathinfo = pathinfo($filename);
        $filename = $pathinfo['filename'] . '_' . microtime() . '.' . $pathinfo['extension'];

        $file->move(self::getUploadDir() . $folder . '/' . $filename);

        return $folder . '/' . $filename;
    }

    public static function delete($filename)
    {
        $path = self::getUploadDir() . $filename;
        
        if (file_exists($path) && is_file($path)) {
            unlink($path);
        }
    }

    public static function replace($old_filename, FileUpload $new_file)
    {
        $new = self::upload($new_file);
        
        self::delete($old_filename);
        
        return $new;
    }

}