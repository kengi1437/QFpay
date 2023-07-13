<?php
namespace QFPay\PaymentGateway\Helper;

class Utils{

    public static function isMobile()
    {
        return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);

    }

    public static function isWexin() {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            return true;
        } return false;
    }

    /**
     * @param $merchantId
     * @param $data
     * @return string
     */
    public static function getSign($appscrect, $data)
    {
//	    对数组进行a-z排序
        ksort($data);
//        排序后的字符串
        $str='';
        foreach ($data as $k => $v){
            $str.=$k.'='.$v."&";
        }
        $str = substr($str, 0, -1);
        $str = $str.$appscrect;
//        return $str.'---'.strtoupper(md5($str));
        return strtoupper(md5($str));
    }

    public static function verifySign($appscrect, $data)
    {
        $content=json_encode(json_decode($data, true)).$appscrect; 
        $str1 = str_replace('","', '", "', $content); #reformatting
        $str2 = str_replace('":"', '": "', $str1); #reformatting
        return strtoupper(md5($str2));
    }
}
