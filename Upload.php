<?php
namespace Li;

interface IUpload
{
    // 保存
    public function save();
    // 获得上传文件名
    public function getSaveName();
    // 获得上传路径
    public function getSavePath();
}


/**
 * 文件上传
 */
class Upload implements IUpload
{
    public $savePath='uploads';
    public $allowType='jpg,png,gif';
    public $maxSize=1024;


    protected $_mimeType=array(
        'ai'      => 'application/postscript',
        'aif'     => 'audio/x-aiff',
        'aifc'    => 'audio/x-aiff',
        'aiff'    => 'audio/x-aiff',
        'asc'     => 'text/plain',
        'asf'     => 'video/x-ms-asf',
        'asx'     => 'video/x-ms-asf',
        'au'      => 'audio/basic',
        'avi'     => 'video/x-msvideo',
        'bcpio'   => 'application/x-bcpio',
        'bin'     => 'application/octet-stream',
        'bmp'     => 'image/bmp',
        'bz2'     => 'application/x-bzip2',
        'cdf'     => 'application/x-netcdf',
        'chrt'    => 'application/x-kchart',
        'class'   => 'application/octet-stream',
        'cpio'    => 'application/x-cpio',
        'cpt'     => 'application/mac-compactpro',
        'csh'     => 'application/x-csh',
        'css'     => 'text/css',
        'dcr'     => 'application/x-director',
        'dir'     => 'application/x-director',
        'djv'     => 'image/vnd.djvu',
        'djvu'    => 'image/vnd.djvu',
        'dll'     => 'application/octet-stream',
        'dms'     => 'application/octet-stream',
        'doc'     => 'application/msword',
        'dvi'     => 'application/x-dvi',
        'dxr'     => 'application/x-director',
        'eps'     => 'application/postscript',
        'etx'     => 'text/x-setext',
        'exe'     => 'application/octet-stream',
        'ez'      => 'application/andrew-inset',
        'flv'     => 'video/x-flv',
        'gif'     => 'image/gif',
        'gtar'    => 'application/x-gtar',
        'gz'      => 'application/x-gzip',
        'hdf'     => 'application/x-hdf',
        'hqx'     => 'application/mac-binhex40',
        'htm'     => 'text/html',
        'html'    => 'text/html',
        'ice'     => 'x-conference/x-cooltalk',
        'ief'     => 'image/ief',
        'iges'    => 'model/iges',
        'igs'     => 'model/iges',
        'img'     => 'application/octet-stream',
        'iso'     => 'application/octet-stream',
        'jad'     => 'text/vnd.sun.j2me.app-descriptor',
        'jar'     => 'application/x-java-archive',
        'jnlp'    => 'application/x-java-jnlp-file',
        'jpe'     => 'image/jpeg',
        'jpeg'    => 'image/jpeg',
        'jpg'     => 'image/jpeg',
        'js'      => 'application/x-javascript',
        'kar'     => 'audio/midi',
        'kil'     => 'application/x-killustrator',
        'kpr'     => 'application/x-kpresenter',
        'kpt'     => 'application/x-kpresenter',
        'ksp'     => 'application/x-kspread',
        'kwd'     => 'application/x-kword',
        'kwt'     => 'application/x-kword',
        'latex'   => 'application/x-latex',
        'lha'     => 'application/octet-stream',
        'lzh'     => 'application/octet-stream',
        'm3u'     => 'audio/x-mpegurl',
        'man'     => 'application/x-troff-man',
        'me'      => 'application/x-troff-me',
        'mesh'    => 'model/mesh',
        'mid'     => 'audio/midi',
        'midi'    => 'audio/midi',
        'mif'     => 'application/vnd.mif',
        'mov'     => 'video/quicktime',
        'movie'   => 'video/x-sgi-movie',
        'mp2'     => 'audio/mpeg',
        'mp3'     => 'audio/mpeg',
        'mpe'     => 'video/mpeg',
        'mpeg'    => 'video/mpeg',
        'mpg'     => 'video/mpeg',
        'mpga'    => 'audio/mpeg',
        'ms'      => 'application/x-troff-ms',
        'msh'     => 'model/mesh',
        'mxu'     => 'video/vnd.mpegurl',
        'nc'      => 'application/x-netcdf',
        'odb'     => 'application/vnd.oasis.opendocument.database',
        'odc'     => 'application/vnd.oasis.opendocument.chart',
        'odf'     => 'application/vnd.oasis.opendocument.formula',
        'odg'     => 'application/vnd.oasis.opendocument.graphics',
        'odi'     => 'application/vnd.oasis.opendocument.image',
        'odm'     => 'application/vnd.oasis.opendocument.text-master',
        'odp'     => 'application/vnd.oasis.opendocument.presentation',
        'ods'     => 'application/vnd.oasis.opendocument.spreadsheet',
        'odt'     => 'application/vnd.oasis.opendocument.text',
        'ogg'     => 'application/ogg',
        'otg'     => 'application/vnd.oasis.opendocument.graphics-template',
        'oth'     => 'application/vnd.oasis.opendocument.text-web',
        'otp'     => 'application/vnd.oasis.opendocument.presentation-template',
        'ots'     => 'application/vnd.oasis.opendocument.spreadsheet-template',
        'ott'     => 'application/vnd.oasis.opendocument.text-template',
        'pbm'     => 'image/x-portable-bitmap',
        'pdb'     => 'chemical/x-pdb',
        'pdf'     => 'application/pdf',
        'pgm'     => 'image/x-portable-graymap',
        'pgn'     => 'application/x-chess-pgn',
        'png'     => 'image/png',
        'pnm'     => 'image/x-portable-anymap',
        'ppm'     => 'image/x-portable-pixmap',
        'ppt'     => 'application/vnd.ms-powerpoint',
        'ps'      => 'application/postscript',
        'qt'      => 'video/quicktime',
        'ra'      => 'audio/x-realaudio',
        'ram'     => 'audio/x-pn-realaudio',
        'ras'     => 'image/x-cmu-raster',
        'rgb'     => 'image/x-rgb',
        'rm'      => 'audio/x-pn-realaudio',
        'roff'    => 'application/x-troff',
        'rpm'     => 'application/x-rpm',
        'rtf'     => 'text/rtf',
        'rtx'     => 'text/richtext',
        'sgm'     => 'text/sgml',
        'sgml'    => 'text/sgml',
        'sh'      => 'application/x-sh',
        'shar'    => 'application/x-shar',
        'silo'    => 'model/mesh',
        'sis'     => 'application/vnd.symbian.install',
        'sit'     => 'application/x-stuffit',
        'skd'     => 'application/x-koan',
        'skm'     => 'application/x-koan',
        'skp'     => 'application/x-koan',
        'skt'     => 'application/x-koan',
        'smi'     => 'application/smil',
        'smil'    => 'application/smil',
        'snd'     => 'audio/basic',
        'so'      => 'application/octet-stream',
        'spl'     => 'application/x-futuresplash',
        'src'     => 'application/x-wais-source',
        'stc'     => 'application/vnd.sun.xml.calc.template',
        'std'     => 'application/vnd.sun.xml.draw.template',
        'sti'     => 'application/vnd.sun.xml.impress.template',
        'stw'     => 'application/vnd.sun.xml.writer.template',
        'sv4cpio' => 'application/x-sv4cpio',
        'sv4crc'  => 'application/x-sv4crc',
        'swf'     => 'application/x-shockwave-flash',
        'sxc'     => 'application/vnd.sun.xml.calc',
        'sxd'     => 'application/vnd.sun.xml.draw',
        'sxg'     => 'application/vnd.sun.xml.writer.global',
        'sxi'     => 'application/vnd.sun.xml.impress',
        'sxm'     => 'application/vnd.sun.xml.math',
        'sxw'     => 'application/vnd.sun.xml.writer',
        't'       => 'application/x-troff',
        'tar'     => 'application/x-tar',
        'tcl'     => 'application/x-tcl',
        'tex'     => 'application/x-tex',
        'texi'    => 'application/x-texinfo',
        'texinfo' => 'application/x-texinfo',
        'tgz'     => 'application/x-gzip',
        'tif'     => 'image/tiff',
        'tiff'    => 'image/tiff',
        'torrent' => 'application/x-bittorrent',
        'tr'      => 'application/x-troff',
        'tsv'     => 'text/tab-separated-values',
        'txt'     => 'text/plain',
        'ustar'   => 'application/x-ustar',
        'vcd'     => 'application/x-cdlink',
        'vrml'    => 'model/vrml',
        'wav'     => 'audio/x-wav',
        'wax'     => 'audio/x-ms-wax',
        'wbmp'    => 'image/vnd.wap.wbmp',
        'wbxml'   => 'application/vnd.wap.wbxml',
        'wm'      => 'video/x-ms-wm',
        'wma'     => 'audio/x-ms-wma',
        'wml'     => 'text/vnd.wap.wml',
        'wmlc'    => 'application/vnd.wap.wmlc',
        'wmls'    => 'text/vnd.wap.wmlscript',
        'wmlsc'   => 'application/vnd.wap.wmlscriptc',
        'wmv'     => 'video/x-ms-wmv',
        'wmx'     => 'video/x-ms-wmx',
        'wrl'     => 'model/vrml',
        'wvx'     => 'video/x-ms-wvx',
        'xbm'     => 'image/x-xbitmap',
        'xht'     => 'application/xhtml+xml',
        'xhtml'   => 'application/xhtml+xml',
        'xls'     => 'application/vnd.ms-excel',
        'xml'     => 'text/xml',
        'xpm'     => 'image/x-xpixmap',
        'xsl'     => 'text/xml',
        'xwd'     => 'image/x-xwindowdump',
        'xyz'     => 'chemical/x-xyz',
        'zip'     => 'application/zip'
    );
        
    protected $_error;

    protected function _checkType($file)
    {
        $mime = $this->_getMimeType($file['name']);

        // 检查文件类型
        $type = explode(',',$this->allowType);

        foreach($type as $value)
        {
            if(!empty($value) && $this->_mimeType[$value] == $mime)
            {
                return $value;
            }
        }

        return false;
    }

    protected function _checkSize($file)
    {
        if($file['size'] > $this->maxSize*1024)
        {
            return false;
        }

        return true;
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
    public function save($file=null)
    {
        if(empty($file))
        {
            $this->_error='文件不存在';
            return false;
        }

        if ($file['error']) {
            $this->error($file['error']);
            return false;
        }
        $fileType = $this->_checkType($file);
        if(false === $fileType)
        {
            $this->_error='不允许该类型文件';
            return false;
        }

        if(false == $this->_checkSize($file))
        {
            $this->_error='文件太大';
            return false;
        }

        $saveName = $this->getSaveName($file);
        $savePath = $this->getSavePath($file);

        $saveFile = $savePath.DIRECTORY_SEPARATOR.$saveName.'.'.$fileType;

        if(false==move_uploaded_file($file['tmp_name'], APP_PATH.$saveFile))
        {
            $this->_error='上传失败';
            return false;
        }

        return '/'.str_replace('\\', '/', $saveFile);
    }

    public function saveRemoteImage($url)
    {

        //获取请求头并检测死链
        $heads = get_headers($url, 1);
        if (!(stristr($heads[0], "200") && stristr($heads[0], "OK"))) {
            $this->error = '文件不存在';
            return;
        }

        if($heads['Content-Type'] == 'image/png')
        {
            $fileType = '.png';
        }
        else if($heads['Content-Type'] == 'image/jpeg')
        {
            $fileType = '.jpeg';
        }
        else if($heads['Content-Type'] == 'image/gif')
        {
            $fileType = '.gif';
        }
        else if($heads['Content-Type'] == 'image/x-jpg')
        {
            $fileType = '.jpg';
        }
        else
        {
            $this->_error='远程不是图片';
            return false;
        }


        $fileName = $this->getSaveName();
        $fileName = $this->getSavePath();

// debug($heads);
// die;

        //打开输出缓冲区并获取远程图片
        ob_start();
        $context = stream_context_create(
            array('http' => array(
                'follow_location' => false // don't follow redirects
            ))
        );

        if(strpos($imgUrl, 'wx_fmt=') !== false
            || strpos($imgUrl, 'wxfrom=') !== false
        )
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $imgUrl);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/43.0.2357.132 Safari/537.36');
            $img = curl_exec($ch);
            $m="";
            // $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $this->fileType = $fileType;
        }
        else
        {
            readfile($imgUrl, false, $context);
            $img = ob_get_contents();
            ob_end_clean();
            preg_match("/[\/]([^\/]*)[\.]?[^\.\/]*$/", $imgUrl, $m);
        }
        
        $this->oriName = $m ? $m[1]:"";
        $this->fileSize = strlen($img);
        $this->fileType = empty($this->fileType) ? $this->getFileExt() : $this->fileType;
        $this->fullName = $this->getFullName();
        $this->filePath = $this->getFilePath();
        $this->fileName = $this->getFileName();
        $dirname = dirname($this->filePath);


        //检查文件大小是否超出限制
        if (!$this->checkSize()) {
            $this->stateInfo = $this->getStateInfo("ERROR_SIZE_EXCEED");
            return;
        }

        //创建目录失败
        if (!file_exists($dirname) && !mkdir($dirname, 0777, true)) {
            $this->stateInfo = $this->getStateInfo("ERROR_CREATE_DIR");
            return;
        } else if (!is_writeable($dirname)) {
            $this->stateInfo = $this->getStateInfo("ERROR_DIR_NOT_WRITEABLE");
            return;
        }
        //移动文件
        if (!(file_put_contents($this->filePath, $img) && file_exists($this->filePath))) { //移动失败
            $this->stateInfo = $this->getStateInfo("ERROR_WRITE_CONTENT");
        } else { //移动成功
            $this->stateInfo = $this->stateMap[0];
        }

    }


    public function getSaveName($file=null)
    {
        if(empty($file))
        {
            return md5(NOW.mt_rand(111,999));
        }
        else
        {
            return md5($file['tmp_name']);
        }
    }

    public function getSavePath($recursive=false,$mode=0777)
    {
        $date = date('Ym', NOW);

        $savePath = 'public'.DIRECTORY_SEPARATOR.$this->savePath.DIRECTORY_SEPARATOR.$date;

        if(!is_dir(APP_PATH.$savePath))
        {
            mkdir(APP_PATH.$savePath,$mode,$recursive);
        }

        return $savePath;
    }

    protected function _getMimeType($filename, $debug = true ) {
        if ( function_exists( 'finfo_open' ) && function_exists( 'finfo_file' ) && function_exists( 'finfo_close' ) ) {

            $fileinfo = finfo_open( FILEINFO_MIME );
            $mime_type = finfo_file( $fileinfo, $filename );
            finfo_close( $fileinfo );
            
            if ( ! empty( $mime_type ) ) {
                if ( true === $debug )
                    return array( 'mime_type' => $mime_type, 'method' => 'fileinfo' );
                return $mime_type;
            }
        }
        if ( function_exists( 'mime_content_type' ) ) {
            $mime_type = mime_content_type( $filename );
            
            if ( ! empty( $mime_type ) ) {
                if ( true === $debug )
                    return array( 'mime_type' => $mime_type, 'method' => 'mime_content_type' );
                return $mime_type;
            }
        }
        $tmpVar = explode( '.', $filename );
        $ext = strtolower( array_pop( $tmpVar ) );
        
        if ( ! empty( $this->_mimeType[$ext] ) ) {
            if ( true === $debug )
            return $this->_mimeType[$ext];
        }
        
        if ( true === $debug )
            return array( 'mime_type' => 'application/octet-stream', 'method' => 'last_resort' );
        return 'application/octet-stream';
    }

    public function getError()
    {
        return $this->_error;
    }

}
