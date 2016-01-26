<?php
namespace Li;
/**
 * 验证码类
 */

class Captcha 
{
    protected $_config=array(
        'type'=>'string',
        'width'=>100,
        'height'=>30,
        'fontSize'=>20,
        'length'=>4,
        'wave'=>true,
        'sessionVar'=> 'captcha',
        'bgColor'=>array(255, 255, 255),
        'fontColor'=>array(
            array(27,78,181), // blue
            array(22,163,35), // green
            array(214,36,7),  // red
        ),
        'lineNumber'=>1,        // 几条干扰线
        'lineWidth'=>1,         // 干扰线宽度
        'angle'=>15,            // 文字偏转角度
        'fonts'=>array(
            'Antykwa'  => array('spacing' => -2, 'font' => 'AntykwaBold.ttf'),
            'Candice'  => array('spacing' =>-1.5,'font' => 'Candice.ttf'),
            'Duality'  => array('spacing' =>-0.5,'font' => 'Duality.ttf'),
            'Jura'     => array('spacing' => -1, 'font' => 'Jura.ttf'),
            'StayPuft' => array('spacing' =>-1,  'font' => 'StayPuft.ttf'),
            'VeraSans' => array('spacing' => -1, 'font' => 'VeraSansBold.ttf'),
        ),

        'math'=>array('+','-','*'),
        'string'=>'0123456789abcdefghijklmnopqrstuvwxyz',
    );

    private $_fontColor;
    private $_bgColor;
    private $_textWidth;

    protected $_im;

    public function __construct($config = array()) {
    }

    public function generate($key=null) {
        if(isset(App::app()->config['captcha'])
            && is_array(App::app()->config['captcha'])
            )
        {
            $this->_config = array_merge($this->_config, App::app()->config['captcha']);
        }
        $this->_create();
        $text = $this->_getText($key);

        // 随机字体
        $font  = $this->_config['fonts'][array_rand($this->_config['fonts'])];
        $this->_text($text, $font);

        
        if($this->_config['type'] !== 'math')
        {
            // 随机干扰
            if ($this->_config['lineNumber'] > 0) {
                $i=0;
                for(;$i<$this->_config['lineNumber'];$i++) 
                {
                    $this->_line();
                }
            }
        }

        header("Content-type: image/png");
        imagepng($this->_im);
        imagedestroy($this->_im);
    }

    protected function _getText($key)
    {
        if($this->_config['type'] === 'math')
        {
            shuffle($this->_config['math']);
            $text = mt_rand(1,9).$this->_config['math'][0].mt_rand(1,9);
            eval("\$value = $text;");
            $text.='=';
        }
        else
        {
            $text = $value = substr(str_shuffle($this->_config['string']), 0, $this->_config['length']);
        }
        if(empty($key))
        {
            $_SESSION[$this->_config['sessionVar']] = $value;
        }
        else
        {
            $_SESSION[$this->_config['sessionVar'].$key] = $value;
        }
        return $text;
    }

    protected function _create() {

        $this->_im = imagecreatetruecolor($this->_config['width'], $this->_config['height']);

        // bg color
        $this->_bgColor = imagecolorallocate($this->_im,
            $this->_config['bgColor'][0],
            $this->_config['bgColor'][1],
            $this->_config['bgColor'][2]
        );
        imagefilledrectangle($this->_im, 0, 0, $this->_config['width'], $this->_config['height'], $this->_bgColor);

        // font color
        $color = $this->_config['fontColor'][mt_rand(0, sizeof($this->_config['fontColor'])-1)];
        $this->_fontColor = imagecolorallocate($this->_im, $color[0], $color[1], $color[2]);
    }

    protected function _line() {
        $x1 = $this->_config['width']*.15;
        $x2 = $this->_textWidth;
        $y1 = mt_rand($this->_config['height']*.10, $this->_config['height']*.85);
        $y2 = mt_rand($this->_config['height']*.10, $this->_config['height']*.85);
        $width = $this->_config['lineWidth']/2;

        for ($i = $width*-1; $i <= $width; $i++) {
            imageline($this->_im, $x1, $y1+$i, $x2, $y2+$i, $this->_fontColor);
        }
    }

    protected function _text($text, $font = array()) {
        $fontFile = LI_PATH.'/fonts/'.$font['font'];

        $x      = 10;
        $y      = $this->_config['height']*0.8;

        $length = strlen($text);
        for ($i=0; $i<$length; $i++) {
            $degree   = mt_rand($this->_config['angle']*-1, $this->_config['angle']);
            $fontsize = mt_rand($this->_config['fontSize']-3, $this->_config['fontSize']+3);

            $letter   = substr($text, $i, 1);

            $coords = imagettftext($this->_im, $fontsize, $degree,
                $x, $y,
                $this->_fontColor, $fontFile, $letter);
            $x += ($coords[2]-$x) + ($font['spacing']);
        }

        $this->_textWidth = $x;
    }
}
