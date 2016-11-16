<?php

/* * *************************************************************************
 *
 * Copyright (c) 2014 Baijiahulian.com, Inc. All Rights Reserved
 * $Id$
 *
 * ************************************************************************ */



/**
 * @file QrAliPay.php
 * @author gsm
 * @date 2015-10-19 14:45:34
 * @version $Revision$
 * @brief
 * */
namespace Pay\DataService\ThirdParty\base;

use Pay\Lib;
use Pay\Lib\Log;
use Pay\Constants\LogBusName;

class QrAliPay extends BasicPay{

    /**
     * HTTPS形式消息验证地址
     */
    protected $https_verify_url = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';

    /**
     * HTTP形式消息验证地址
     */
    protected $http_verify_url = 'http://notify.alipay.com/trade/notify_query.do?';

    public function check() {
        if (!$this->conf['seller_id'] || !$this->conf['ali_public_key_path'] || !$this->conf['partner']) {
            throw new Lib\ServiceException(1,'支付宝设置有误！');
        }
        return true;
    }

    public function execute($params) {
        return $this->buildRequestParams($params);
    }

    public function buildRequestParams($params) {

        $param = array(
            'method' => 'alipay.trade.precreate',
            'app_id' => 'wx200385d843c73ab6',
            'charset' => 'utf-8',
            'sign_type' => 'RSA',
            'timestamp' => date('Y-m-d H:i:s',time()),
            'notify_url' => urlencode($this->conf['notify_url']),
            'version' => '1.0',
            biz_content =>json_encode(array(
            'out_trade_no' => $params['purchase_id'],
                'seller_id' =>  $this->conf['seller_id'],
                'total_amount' => $params['money'],
                'subject' => $params['body'],
                //'time_expire' => '',
            )),
        );
        ksort($param);
        reset($param);

        $arg = "";
        while (list ($key, $val) = each($param)) {
            $arg.=$key . "=\"" . $val . "\"&";
        }
        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        $sign = $this->rsaSign($arg, $this->conf['pri'].$this->conf['private_key_path']);
        $params['sign'] =urlencode($sign);
        $url = 'https://openapi.alipay.com/gateway.do?charset=utf-8&';
        $response = $this->getHttpResponsePOST($url,$param);
        $result = json_decode($response,true);
        var_dump($result);die();
        if($result['alipay_trade_precreate_response']['code'] != 10000){
           // return $result['alipay_trade_precreate_response']['msg'];
            throw new Lib\ServiceException(Lib\ErrorCodes::FAIL,$result['alipay_trade_precreate_response']['sub_msg']);
        }
        $veryfy = $this->getSignVeryfy($result['alipay_trade_precreate_response'],$result['sign']);
        if($veryfy){
            return $result['alipay_trade_precreate_response']['qr_code'];
        }
        return $arg;
    }

    public function genQueryParam($trade_no) {
        $pcConf = $this->conf['pcConf'];
        $params = array(
            "_input_charset" => $this->conf['_input_charset'],
            "out_trade_no" => $trade_no,
            "partner" => $this->conf['partner'],
            'service' => 'single_trade_query',
        );
        return $params;
    }

    public function queryOrder($trade_no) {
        $params = $this->genQueryParam($trade_no);
        $queryUrl = $this->conf['alipay_gateway'].http_build_query($params);
        $signType = $this->conf['pcConf']['sign_type'];
        $sign = $this->genSign($params,$signType);
        $payUrl = $queryUrl."&sign=$sign&sign_type=$signType";
        $resp = $this->xmlToArray($this->postXmlCurl($payUrl));
        $res = array(
            'code' => $resp['response']['trade_status']=='TRADE_FINISHED'?1:0,
            'purchase_id' => $resp['response']['trade']['out_trade_no'],
            'total_fee' => $resp['response']['trade']['total_fee'],
            'third' => 2,
        );
        return $res;
    }
    /**
     * 获取返回时的签名验证结果
     * @param $para_temp 通知返回来的参数数组
     * @param $sign 返回的签名结果
     * @return 签名验证结果
     */
    protected function getSignVeryfy($para_temp, $sign) {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = array();
        unset($para_temp['_url']);
        while (list ($key, $val) = each($para_temp)) {
            if ($key == "sign" || $key == "sign_type" || $val == "") {
                continue;
            } else {
                $para_filter[$key] = $para_temp[$key];
            }
        }

        //对待签名参数数组排序
        ksort($para_filter);
        reset($para_filter);

        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串

        $arg = "";
        while (list ($key, $val) = each($para_filter)) {
            $arg.=$key . "=" . $val . "&";
        }
        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);
        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }
        $prestr = $arg;


        $isSgin = false;
        switch (strtoupper(trim($this->conf['sign_type']))) {
            case "RSA" :
                $isSgin = $this->rsaVerify($prestr, trim($this->conf['pri'].$this->conf['ali_public_key_path']), $sign);

                break;
            default :
                $isSgin = false;
        }

        return $isSgin;
    }

    /**
     * RSA验签
     * @param $data 待签名数据
     * @param $ali_public_key_path 支付宝的公钥文件路径
     * @param $sign 要校对的的签名结果
     * return 验证结果
     */
    public function rsaVerify($data, $ali_public_key_path, $sign) {
        $s = $this->rsaSign($data, $ali_public_key_path);
        $pubKey = file_get_contents($ali_public_key_path);
        $res = openssl_get_publickey($pubKey);
        $result = (bool) openssl_verify($data, base64_decode($sign), $res);
        openssl_free_key($res);
        return $result;
    }

    /**
     * 针对notify_url验证消息是否是支付宝发出的合法消息
     * @return 验证结果
     */
    public function verifyNotify($notify) {

        //生成签名结果
        //$isSign = $this->getSignVeryfy($notify, $notify["sign"]);
        $isSign = true;
        //获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
        $responseTxt = 'true';
        if (!empty($notify["notify_id"])) {
            $responseTxt = $this->getResponse($notify["notify_id"]);
        }

        if (preg_match("/true$/i", $responseTxt) && $isSign) {
            if($notify['trade_status'] == 'TRADE_FINISHED' || $notify['trade_status'] == 'TRADE_SUCCESS') {
                return $this->parseTradeSuccParam($notify);
            }else {
                return false;
            }
        } else {
            return false;
        }
    }

    protected function setInfo($notify) {
        $info = array();
        //支付状态
        //支付宝的回调接口里没有看到trade_status
        $info['status'] = false;
        if (isset($notify['trade_status'])) {
            $info['status'] = ($notify['trade_status'] == 'TRADE_FINISHED' || $notify['trade_status'] == 'TRADE_SUCCESS') ? true : false;
        } elseif (isset($notify['success'])) {
            $info['status'] = $notify['success'];
        }
        $info['money'] = $notify['total_fee'];
        $info['out_trade_no'] = $notify['out_trade_no'];
        $info['trade_no'] = $notify['trade_no'];

        return $info;
    }

    /**
     * 获取远程服务器ATN结果,验证返回URL
     * @param $notify_id 通知校验ID
     * @return 服务器ATN结果
     * 验证结果集：
     * invalid命令参数不对 出现这个错误，请检测返回处理中partner和key是否为空
     * true 返回正确信息
     * false 请检查防火墙或者是服务器阻止端口问题以及验证时间是否超过一分钟
     */
    protected function getResponse($notify_id) {
        $transport = strtolower(trim($this->conf['transport']));
        $partner = trim($this->conf['partner']);
        $veryfy_url = '';
        if ($transport == 'https') {
            $veryfy_url = $this->https_verify_url;
        } else {
            $veryfy_url = $this->http_verify_url;
        }
        $veryfy_url = $veryfy_url . "partner=" . $partner . "&notify_id=" . $notify_id;
        $responseTxt = $this->getHttpResponseGET($veryfy_url, $this->conf['pri'].$this->conf['cacert']);
        return $responseTxt;
    }

    /**
     * RSA签名
     * @param $data 待签名数据
     * @param $private_key_path 商户私钥文件路径
     * return 签名结果
     */
    protected function rsaSign($data, $private_key_path) {
        $priKey = file_get_contents($private_key_path);
        $res = openssl_get_privatekey($priKey);
        openssl_sign($data, $sign, $res);
        openssl_free_key($res);
        //base64编码
        $sign = base64_encode($sign);
        return $sign;
    }

    /**
     * 远程获取数据，GET模式
     * 注意：
     * 1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
     * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
     * @param $url 指定URL完整路径地址
     * @param $cacert_url 指定当前工作目录绝对路径
     * return 远程输出的数据
     */
    protected function getHttpResponseGET($url, $cacert_url) {
        Log::wb(LogBusName::THIRD,__METHOD__,array("url"=>$url,"cacert_url"=>$cacert_url));
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, 0); // 过滤HTTP头
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 显示输出结果
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true); //SSL证书认证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); //严格认证
        curl_setopt($curl, CURLOPT_CAINFO, $cacert_url); //证书地址
        $responseText = curl_exec($curl);
        Log::wb(LogBusName::THIRD,__METHOD__,array("return"=>$responseText));
        curl_close($curl);

        return $responseText;
    }

    /**
     * 远程获取数据，POST模式
     * @param $url 指定URL完整路径地址
     * @param $data POST传输数据
     * @param $cacert_url 指定当前工作目录绝对路径
     * @return mixed
     */
    protected function getHttpResponsePOST($url,$data, $cacert_url) {
        Log::wb(LogBusName::THIRD,__METHOD__,array("url"=>$url,"cacert_url"=>$cacert_url));
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, 0); // 过滤HTTP头
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 显示输出结果
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true); //SSL证书认证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); //严格认证
        curl_setopt($curl, CURLOPT_CAINFO, $cacert_url); //证书地址

        $responseText = curl_exec($curl);
        Log::wb(LogBusName::THIRD,__METHOD__,array("return"=>$responseText));
        curl_close($curl);

        return $responseText;
    }


    /**
     * 格式化订单信息
     * @param type $param
     * @return type
     */
    public function parseTradeSuccParam($param) {

        $tradeSuccParam = array(
            "purchaseId" => $param["out_trade_no"], //本站订单ID
            "tradeNo" => $param['trade_no'], //第三方支付系统的交易流水号，如支付宝系统的交易流水
            "tradeStatus" => 1,
            "totalFee" => $param["total_fee"],
            "tradeInfo" => '', //第三方支付系统回调的返回信息，如买家基本信息等
            "realPayType" => $param["buyer_email"], //真实支付类型
            "payType" => 2, //第三方支付
        );

        return $tradeSuccParam;
    }
}
