<?php
namespace Li;
//1 (1)required:true 必输字段
// (2)remote:"check.php" 使用ajax方法调用check.php验证输入值
//1 (3)email:true 必须输入正确格式的电子邮件
//1 (4)url:true 必须输入正确格式的网址
//1 (5)date:true 必须输入正确格式的日期(默认格式Y-m-d)
// 不支持(6)dateISO:true 必须输入正确格式的日期(ISO)，例如：2009-06-23，1998/01/22 只验证格式，不验证有效性
//1 (7)number:true 必须输入合法的数字(负数，小数)
//1 (8)digits:true 必须输入整数
// 不支持(9)creditcard: 必须输入合法的信用卡号
//1 (10)equalTo:"#field" 输入值必须和#field相同
// 不支持(11)accept: 输入拥有合法后缀名的字符串（上传文件的后缀）
//1 (12)maxlength:5 输入长度最多是5的字符串(汉字算一个字符)
//1 (13)minlength:10 输入长度最小是10的字符串(汉字算一个字符)
//1 (14)rangelength:[5,10] 输入长度必须介于 5 和 10 之间的字符串")(汉字算一个字符)
//1 (15)range:[5,10] 输入值必须介于 5 和 10 之间
//1 (16)max:5 输入值不能大于5
//1 (17)min:10 输入值不能小于10 
class Validate {
    public $rule;
    public $dataWrap;
    public $error;

    public function __construct($rule,$dataWrap='data')
    {
        $this->rule = $rule;
        $this->dataWrap = $dataWrap;
        $this->error = array();
    }

    /**
     * 验证
     */
    public function validate($data)
    {
        foreach($this->rule as $key=>$rule)
        {
            foreach($rule as $r)
            {
                if($r[0] == 'required')
                {
                    if(!isset($data[$key]) || $data[$key] ==='')
                    {
                        $this->error[$key]=$r[1];
                        break;
                    }
                }
                else if($r[0] == 'email')
                {
                    if(!isset($data[$key]) || !$this->checkEmail($data[$key]) )
                    {
                        $this->error[$key]=$r[1];
                        break;
                    }
                }
                else if($r[0] == 'number')
                {
                    if(!isset($data[$key]) || !is_numeric($data[$key]) )
                    {
                        $this->error[$key]=$r[1];
                        break;
                    }
                }

                else if($r[0] == 'digits')
                {
                    if(!isset($data[$key]) && !is_numeric($data[$key]) && !is_int($data[$key]+0))
                    {
                        $this->error[$key]=$r[1];
                        break;
                    }
                }
                else if($r[0] == 'date')
                {
                    debug($data[$key]);

                    if(!isset($data[$key]) || !$this->checkDate(($data[$key]),$r[2]))
                    {
                        $this->error[$key]=$r[1];
                        break;
                    }
                }
                else if($r[0] == 'url')
                {
                    if(!isset($data[$key]) || !$this->checkUrl($data[$key]) )
                    {
                        $this->error[$key]=$r[1];
                        break;
                    }
                }
                else if($r[0] == 'minlength')
                {
                    if(!isset($data[$key]) || strlen($data[$key]) < $r[2])
                    {
                        $this->error[$key]=$r[1];
                        break;
                    }
                }
                else if($r[0] == 'maxlength')
                {
                    if(!isset($data[$key]) || strlen($data[$key]) > $r[2])
                    {
                        $this->error[$key]=$r[1];
                        break;
                    }
                }
                else if($r[0] == 'equalTo')
                {
                    if(!isset($data[$key]) || $data[$key] != $r[2])
                    {
                        $this->error[$key]=$r[1];
                        break;
                    }
                }
                else if($r[0] == 'rangelength')
                {
                    $len = strlen($data[$key]);
                    if(!isset($data[$key]) || ($len < $r[2] || $len > $r[3]))
                    {
                        $this->error[$key]=$r[1];
                        break;
                    }
                }
                else if($r[0] == 'range')
                {
                    if(!isset($data[$key]) || ($data[$key] < $r[2] || $data[$key] > $r[3]))
                    {
                        $this->error[$key]=$r[1];
                        break;
                    }
                }
                else if($r[0] == 'max')
                {
                    if(!isset($data[$key]) || $data[$key] > $r[2])
                    {
                        $this->error[$key]=$r[1];
                        break;
                    }
                }
                else if($r[0] == 'min')
                {
                    if(!isset($data[$key]) || $data[$key] < $r[2])
                    {
                        $this->error[$key]=$r[1];
                        break;
                    }
                }
            }
        }

        return $this->error;
    }

    public function checkEmail($email)
    {
        $pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";
        
        if ( preg_match( $pattern, $email ) )
        {
            return true;
        }

        return false;
    }
    public function checkUrl($url)
    {
        $pattern = "|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i";
        
        if ( preg_match( $pattern, $url ) )
        {
            return true;
        }

        return false;
    }

    public function checkDate($date,$format)
    {
        $format = str_replace('YYYY', 'Y', $format);
        $format = str_replace('YY', 'y', $format);

        $format = str_replace('MM', 'm', $format);
        
        $format = str_replace('DD', 'd', $format);

        $format = str_replace('HH', 'H', $format);
        $format = str_replace('mm', 'i', $format);
        $format = str_replace('ss', 's', $format);

        if(empty($format))
        {
            $format = 'Y-m-d';
        }
        $dt = \DateTime::createFromFormat($format, $date);
        return $dt !== false && !array_sum($dt->getLastErrors());
    }

    public function getValidate()
    {
        $rule_array=array();
        $countA = count($this->rule);
        $i = 0;
        $ruleJs='';
        $messageJs='';

        foreach($this->rule as $key =>$rule)
        {
            $ruleJs.='"'.$this->dataWrap.'['.$key.']":{';
            $messageJs.='"'.$this->dataWrap.'['.$key.']":{';


            $countR = count($rule);
            foreach($rule as $keyR => $vRule)
            {
                $messageJs.=$vRule[0].':"'.$vRule[1].'"';

                if($vRule[0] == 'required'
                    || $vRule[0] == 'email'
                    || $vRule[0] == 'url'
                    || $vRule[0] == 'digits'
                    || $vRule[0] == 'number'
                )
                {
                    $ruleJs.=$vRule[0].':true';
                    
                }
                elseif($vRule[0]=='minlength'
                    || $vRule[0]=='maxlength'
                    || $vRule[0]=='max'
                    || $vRule[0]=='min'
                )
                {
                    $ruleJs.=$vRule[0].':'.$vRule[2];
                }
                elseif($vRule[0]=='rangelength'
                    || $vRule[0]=='range'
                )
                {
                    $ruleJs.=$vRule[0].':['.$vRule[2].','.$vRule[3].']';
                }
                elseif($vRule[0]=='minlength')
                    $ruleJs.=$vRule[0].':'.$vRule[2];
                elseif($vRule[0]=='date')
                    $ruleJs.=$vRule[0].':"'.$vRule[2].'"';
                else
                    $ruleJs.=$vRule[0].':"'.$vRule[2].'"';

                if($keyR < $countR-1)
                {
                    $ruleJs.=',';
                    $messageJs.=',';
                }

            }
            $ruleJs.='}';
            $messageJs.='}';
            if($i < $countA-1)
            {
                $ruleJs.=',';
                $messageJs.=',';
            }
            $i++;
        }

$js = <<< EOF
    var {$this->dataWrap}_validate = $("form").validate({
        errorElement:'span',
        errorClass: "has-error",
        rules: {
            {$ruleJs}
        },
        messages: {
            {$messageJs}
        }
    });
EOF;
        echo $js;
    }

    public function getError()
    {
        $error = '';
        $errorCount = count($this->error);
        $i = 0;
        foreach($this->error as $key => $value)
        {
            $error.='"'.$this->dataWrap.'['.$key.']":"'.$value.'"';

            if($i < $errorCount -1)
            {
                $error.=',';
            }
            $i++;
        }

$js = <<< EOF
    {$this->dataWrap}_validate.showErrors({
        {$error}
    });
EOF;
        echo $js;
    }
}
