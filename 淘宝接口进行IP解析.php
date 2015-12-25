<?php

/**
 * @file TaobaoIPQuery.php
 * @author guoshaomin(guo_shao_min@163.com) 
 * @date 2015-12-25
 * 
 * 淘宝查询IP接口 PHP
 **/
Class TaobaoIPQuery{
    private static $_requestURL = 'http://ip.taobao.com/service/getIpInfo.php';

    /**
     * 根据IP获取信息
     * 
     * */
    public static function getIPInfo($ip){
        $long = ip2long($ip);
        if($long === 0){
            throw new Exception('IP address error', 5);
        }
        $ip=long2ip($long);
        $IPInfo = self::queryIPInfo($ip);
        return self::parseJSON($IPInfo);
    }
    
    /**
     * 返回的JSON数据格式
    * {
        code: 0,
        data: {
            country: "中国",
            country_id: "CN",
            area: "华东",
            area_id: "300000",
            region: "安徽省",
            region_id: "340000",
            city: "合肥市",
            city_id: "340100",
            county: "",
            county_id: "-1",
            isp: "电信",
            isp_id: "100017",
            ip: "223.240.90.149"
            }
        }
     * */
    private static function queryIPInfo($ip){
        $query = http_build_query(array('ip'=>$ip));

        $ch = curl_init();
        $options = array(
            CURLOPT_URL => sprintf('%s?%s', self::$_requestURL, $query),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_AUTOREFERER => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HEADER => false,
            CURLOPT_TIMEOUT => 3.0,
        );

        curl_setopt_array($ch, $options);
        $content = curl_exec($ch);
        curl_close($ch);
        return $content;
    }
    
    /**
     * 解析JSON字符串
     * * {
        code: 0,
        data: {
            country: "中国",
            country_id: "CN",
            area: "华东",
            area_id: "300000",
            region: "安徽省",
            region_id: "340000",
            city: "合肥市",
            city_id: "340100",
            county: "",
            county_id: "-1",
            isp: "电信",
            isp_id: "100017",
            ip: "223.240.90.149"
            }
        }
        *
     * */
    private static function parseJSON($json){
        $O = json_decode ($json, true);
        if(false === is_null($O)){
            return $O;
        }
        if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
            $errorCode = json_last_error();
            if(isset(self::$_JSONParseError[$errorCode])){
                throw new Exception(self::$_JSONParseError[$errorCode], 5);
            }
        }
        throw new Exception('JSON parse error', 5);
    }

    /**
     * 错误码解析
     *
    **/
    private static $_JSONParseError = array(
        JSON_ERROR_NONE=>'No error has occurred',   
        JSON_ERROR_DEPTH=>'The maximum stack depth has been exceeded',   
        JSON_ERROR_CTRL_CHAR=>'Control character error, possibly incorrectly encoded',   
        JSON_ERROR_STATE_MISMATCH=>'Invalid or malformed JSON',   
        JSON_ERROR_SYNTAX=>'Syntax error',   
        JSON_ERROR_UTF8=>'Malformed UTF-8 characters, possibly incorrectly encoded',
    );
}



//方法调用
/**
$ip = $_SERVER["REMOTE_ADDR"];
$ipquery = new taobaoIPQuery($ip);
$region = $ipquery->get_region();
$country = $ipquery->get_country();
$city = $ipquery->get_city();
**/
