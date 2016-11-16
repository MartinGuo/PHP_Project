<?php
/**
* Api接口合计 
* @author 郭绍民
*/
class ApiColletion {
    /**
     * User: guoshaomin
     * Date: 15/11/27
     * Time: 下午4:25
     * 获取身份证信息
     * */
    public static function getCardInfo($cardNo) {
        $ch = curl_init();
        $url = 'http://apis.baidu.com/apistore/idservice/id?id=' . $cardNo;
        $header = array('apikey:f013a534b35e46b9ea1d819967ef1e9c',);
        // 添加apikey到header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 执行HTTP请求
        curl_setopt($ch, CURLOPT_URL, $url);
        $res = curl_exec($ch);
        //var_dump(json_decode($res));
        return json_decode($res,true);
    }


    /**
     * User: guoshaomin
     * Date: 15/11/27
     * Time: 下午4:25
     * 获取身份证信息
     **/
    public static function getBrower() {
        if (strpos($_SERVER ['HTTP_USER_AGENT'], 'Maxthon')) {
            $browser = 'Maxthon';
        } elseif (strpos($_SERVER ['HTTP_USER_AGENT'], 'MSIE 12.0')) {
            $browser = 'IE12.0';
        } elseif (strpos($_SERVER ['HTTP_USER_AGENT'], 'MSIE 11.0')) {
            $browser = 'IE11.0';
        } elseif (strpos($_SERVER ['HTTP_USER_AGENT'], 'MSIE 10.0')) {
            $browser = 'IE10.0';
        } elseif (strpos($_SERVER ['HTTP_USER_AGENT'], 'MSIE 9.0')) {
            $browser = 'IE9.0';
        } elseif (strpos($_SERVER ['HTTP_USER_AGENT'], 'MSIE 8.0')) {
            $browser = 'IE8.0';
        } elseif (strpos($_SERVER ['HTTP_USER_AGENT'], 'MSIE 7.0')) {
            $browser = 'IE7.0';
        } elseif (strpos($_SERVER ['HTTP_USER_AGENT'], 'MSIE 6.0')) {
            $browser = 'IE6.0';
        } elseif (strpos($_SERVER ['HTTP_USER_AGENT'], 'NetCaptor')) {
            $browser = 'NetCaptor';
        } elseif (strpos($_SERVER ['HTTP_USER_AGENT'], 'Netscape')) {
            $browser = 'Netscape';
        } elseif (strpos($_SERVER ['HTTP_USER_AGENT'], 'Lynx')) {
            $browser = 'Lynx';
        } elseif (strpos($_SERVER ['HTTP_USER_AGENT'], 'Opera')) {
            $browser = 'Opera';
        } elseif (strpos($_SERVER ['HTTP_USER_AGENT'], 'Chrome')) {
            $browser = 'Google';
        } elseif (strpos($_SERVER ['HTTP_USER_AGENT'], 'Firefox')) {
            $browser = 'Firefox';
        } elseif (strpos($_SERVER ['HTTP_USER_AGENT'], 'Safari')) {
            $browser = 'Safari';
        } elseif (strpos($_SERVER ['HTTP_USER_AGENT'], 'iphone') || strpos($_SERVER ['HTTP_USER_AGENT'], 'ipod')) {
            $browser = 'iphone';
        } elseif (strpos($_SERVER ['HTTP_USER_AGENT'], 'ipad')) {
            $browser = 'iphone';
        } elseif (strpos($_SERVER ['HTTP_USER_AGENT'], 'android')) {
            $browser = 'android';
        } else {
            $browser = 'other';
        }
        return addslashes($browser);
    }


}

echo date('yyyy-mm-D H:i:s');
echo date('Y-m-d H:i:s',time() - 1*24*60*60);
$a = '370124199207177534';
$res= ApiColletion::getCardInfo($a);
var_dump($res);die;


