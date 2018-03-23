<?php
namespace Li;

class Http
{
    public static $_instance;
    protected $_timeout = 1000;
    protected $_client;
    protected $_proxy = [];
    protected $_ua = [];
    public $connect_timeout = 1;
    public $read_timeout = 1;

    public $curProxy;

    public function setTimeout($time) {
        $this->_timeout = $time;
    }

    public static function http($proxy=[])
    {
        if(!(self::$_instance instanceof self))
        {
            self::$_instance = new self($proxy);
        }

        return self::$_instance;
    }

    public function __construct($proxy=[])
    {
        $this->_client = new \GuzzleHttp\Client();
        $this->_proxy = $proxy;
    }

    public function setProxy($proxy)
    {
        $this->_proxy = $proxy;
    }
    public function setUA($ua)
    {
        $this->_ua = $ua;
    }

    public function proxy()
    {
        return $this->_proxy;
    }

    public function option()
    {
        $option = [
            'connect_timeout'   => 2,
            'read_timeout'      => 2,
        ];

        if(!empty($this->_proxy)) {
            $option['proxy'] = $this->_proxy[array_rand($this->_proxy)];
        }

        if(!empty($this->_ua)) {
            $option['User-Agent'] = $this->_ua[array_rand($this->_ua)];
        }
        return $option;
    }

    public function get($url)
    {
        $option = $this->option();

        if(!empty($option)) {
            return $this->_client->request('GET', $url, $option);
        } else {
            return $this->_client->request('GET', $url);
        }
    }

    public function post($url)
    {
        return $this->_client->request('POST', $url, $this->option());
    }


/*
    public function get($url, $ua=[], $proxy=[]) {
        $ua = [
            "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; AcooBrowser; .NET CLR 1.1.4322; .NET CLR 2.0.50727)",
            "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; Acoo Browser; SLCC1; .NET CLR 2.0.50727; Media Center PC 5.0; .NET CLR 3.0.04506)",
            "Mozilla/4.0 (compatible; MSIE 7.0; AOL 9.5; AOLBuild 4337.35; Windows NT 5.1; .NET CLR 1.1.4322; .NET CLR 2.0.50727)",
            "Mozilla/5.0 (Windows; U; MSIE 9.0; Windows NT 9.0; en-US)",
            "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Win64; x64; Trident/5.0; .NET CLR 3.5.30729; .NET CLR 3.0.30729; .NET CLR 2.0.50727; Media Center PC 6.0)",
            "Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; .NET CLR 1.0.3705; .NET CLR 1.1.4322)",
            "Mozilla/4.0 (compatible; MSIE 7.0b; Windows NT 5.2; .NET CLR 1.1.4322; .NET CLR 2.0.50727; InfoPath.2; .NET CLR 3.0.04506.30)",
            "Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN) AppleWebKit/523.15 (KHTML, like Gecko, Safari/419.3) Arora/0.3 (Change: 287 c9dfb30)",
            "Mozilla/5.0 (X11; U; Linux; en-US) AppleWebKit/527+ (KHTML, like Gecko, Safari/419.3) Arora/0.6",
            "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.2pre) Gecko/20070215 K-Ninja/2.1.1",
            "Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9) Gecko/20080705 Firefox/3.0 Kapiko/3.0",
            "Mozilla/5.0 (X11; Linux i686; U;) Gecko/20070322 Kazehakase/0.4.5",
            "Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.8) Gecko Fedora/1.9.0.8-1.fc10 Kazehakase/0.5.6",
            "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.56 Safari/535.11",
            "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_3) AppleWebKit/535.20 (KHTML, like Gecko) Chrome/19.0.1036.7 Safari/535.20",
            "Opera/9.80 (Macintosh; Intel Mac OS X 10.6.8; U; fr) Presto/2.9.168 Version/11.52",
        ];


        $ua = $ua[mt_rand(0, count($ua))];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        // curl_setopt($ch, CURLOPT_PROXY, $proxy);
        // curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
        // curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyauth);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->_timeout);
        curl_setopt($ch, CURLOPT_USERAGENT, $ua);
        // curl_setopt($ch, CURLOPT_COOKIEJAR, "cookies.txt"); 
        // curl_setopt($ch, CURLOPT_COOKIEFILE, "cookies.txt");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        $curl_scraped_page = curl_exec($ch);
        curl_close($ch);

        return $curl_scraped_page;
    }
    */
}
