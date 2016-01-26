<?php
namespace Li;
/**
 * 文件上传
 */
class File
{
    public static $_instance;
    private $_config=array(
        'autoFolder'=>true,     // 自动创建目录
        'savePath'=>'',         // 存储目录
    );

    protected $_file;

    static public function file()
    {
        if(!(self::$_instance instanceof self))
        {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * 上传文件
     * array(1) {
     *   ["file"]=>
     *   array(5) {
     *     ["name"]=>
     *     string(44) "a71ea8d3fd1f413458fdd278261f95cad0c85eea.jpg"
     *     ["type"]=>
     *     string(10) "image/jpeg"
     *     ["tmp_name"]=>
     *     string(24) "F:\xampp\tmp\phpBA0B.tmp"
     *     ["error"]=>
     *     int(0)
     *     ["size"]=>
     *     int(11345)
     *   }
     * }
     */
    public function upload($file)
    {
        $fileName = $this->getFileName($file);
        $filePath = $this->getFilePath($file);

        $this->_file=$file;
        // move_uploaded_file(filename, destination);
    }
    public function uploads($file)
    {
        $fileName = $this->getFileName($file);
        $filePath = $this->getFilePath($file);

        $this->_file=$file;
        // move_uploaded_file(filename, destination);
    }

    public function getFileName($file)
    {
        return md5($file['tmp_name']);
    }

    public function getFilePath($file)
    {
        $path='';

        return $path;
    }

    public function remote($url)
    {
        
    }

    /**
     * 写文件
     */
    public static function write($file,$content,$recursive=false,$mode=0777)
    {
        $dir = dirname($file);

        if(!is_dir($dir))
        {
            mkdir($dir,$mode,$recursive);
        }

        return file_put_contents($file,$content);
    }

    public static function read()
    {

    }


}
