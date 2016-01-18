<?php
/* 
 * Created by NetBeans 7.4
 * Author: Charles Zhu
 * Date: 2015-12-25
 * Time: 22:17:35
 * Email: ritaswc@139.com
 * Copyrights: 任何人可以随便传播、修改、使用，请勿删除作者署名
 * Website: http://blog.yinghualuo.cn/
 * Github: https://github.com/ritaswc/wxRedPack
 */
/*  数据库建立SQL语句参考 utf8mb4支持emoji表情存储
CREATE TABLE IF NOT EXISTS `act3` (
  `_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '数据表id',
  `openid` varchar(50) NOT NULL DEFAULT '' COMMENT '用户的OPENID',
  `amount` int(10) unsigned NOT NULL COMMENT '用户收到的红包金额，单位分',
  `act_name` varchar(32) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '红包活动名字',
  `mch_name` varchar(32) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '红包设置的商户名字',
  `wishing` varchar(128) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '红包的祝福语',
  `remark` varchar(256) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '红包的备注信息',
  `return_code` varchar(20) NOT NULL DEFAULT '' COMMENT '发送红包后的结果',
  `return_msg` varchar(100) NOT NULL DEFAULT '' COMMENT '发送红包后的结果详情',
  `result_code` varchar(20) NOT NULL DEFAULT '' COMMENT '发送红包后的结果代码',
  `err_code` varchar(50) NOT NULL DEFAULT '' COMMENT '返回错误代码',
  `err_code_des` varchar(100) NOT NULL DEFAULT '' COMMENT '返回错误详情',
  `send_listid` varchar(50) NOT NULL DEFAULT '' COMMENT '发送红包后返回的listid',
  `send_time` varchar(20) NOT NULL DEFAULT '' COMMENT '发送红包后返回的sendtime',
  `mch_billno` varchar(30) NOT NULL DEFAULT '' COMMENT '红包对应的订单id',
  `nonce_str` varchar(50) NOT NULL DEFAULT '' COMMENT '产生的随机字符',
  `sign` varchar(50) NOT NULL DEFAULT '' COMMENT '产生的签名信息',
  `create_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建记录的时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '更新记录的时间',
  `status` tinyint(2) unsigned NOT NULL DEFAULT '1' COMMENT '状态，默认1',
  PRIMARY KEY (`_id`),
  KEY `_id` (`_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='发红包记录' AUTO_INCREMENT=1 ;
 */
//include_once 'Config.php';
define('URL', 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack');
// 调试开关，生产环境请设置成false
define('DEBUG', false);
if(DEBUG){
    ini_set("display_errors", "On");
    error_reporting(E_ALL | E_STRICT);
}
class wxRedPack{
    private $actName = '';
    // 这个字段会在红包显示为：  XXXX的红包
    private $mchName = '';
    // 这个字段会直接显示在红包上
    private $wishing = '';
    private $remark = '';
    private $amount = '';
    private $openid = '';
    private $ip = '';
    // 商家ID 类似 1300000000
    private $mchId = '';
    // 类似 wxf3fd427300000000
    private $wxAppId = '';
    // 长度为32的包含数字，大写字母，小写字母的字符串
    // 例如：1234567890abcdefghijABCDEFGHIJ12
    private $wxApiKey = '';
    // 获取配置文件信息
    private $config;
    private $returnCode = '';
    private $returnMsg = '';
    private $resultCode = '';
    private $errCode = '';
    private $errCodeDes = '';
    private $sendListId = '';
    private $sendTime = '';
    private $mchBillNo = '';
    private $nonceStr = '';
    private $sign = '';
    private $resultArr;
    /**
     * 创建一个红包实例
     * @param string $actName 活动主题
     * @param string $mchName 商户名字
     * @param string $wishing 祝福语
     * @param string $remark  备注
     * @param int    $amount  金额，单位1分钱
     * @param string $openid  接收红包关注者的OPENID
     */
    public function __construct($actName, $mchName, $wishing, $remark, $amount, $openid){
        $this->actName  = $actName;
        $this->mchName  = $mchName;
        $this->wishing  = $wishing;
        $this->remark   = $remark;
        $this->amount   = $amount;
        $this->openid   = $openid;
        $this->ip       = $this->getIp();
        if(!($this->config instanceof Config)){
            $this->config = new Config();
        }
        $this->wxAppId  = $this->config->getWxAppId();
        $this->mchId    = $this->config->getMchId();
        $this->wxApiKey = $this->config->getWxApiKey();
        
    }
    /**
     * 通过IP138网站获取服务器公网IP
     * @return string
     */
    private function getIp(){
        $str = file_get_contents("http://1212.ip138.com/ic.asp");
        $str = strip_tags($str);
        $str = explode('[', $str);
        $str = explode(']', $str[1]);
        return $str[0];
    }
    /**
     * POST请求
     * @param string $url 网址
     * @param string $vars 参数，这里是XML字符串 
     * @param int $timeout 请求超时时间
     * @param array $headerArr HTTP头
     * @return boolean | htmlString
     */
    private function curl_post_ssl($url, $vars, $timeout = 30, $headerArr = []){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
        // 三个证书要设置一下 根据网友经验，尽量写绝对路径
        curl_setopt($ch, CURLOPT_SSLCERT, '/home/bae/app/utils/apiclient_cert.pem');
        curl_setopt($ch, CURLOPT_SSLKEY, '/home/bae/app/utils/apiclient_key.pem');
        curl_setopt($ch, CURLOPT_CAINFO, '/home/bae/app/utils/rootca.pem');
        if(count($headerArr) > 0){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArr);
        }
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
        $data = curl_exec($ch);
        if($data){
            curl_close($ch);
            return $data;
        }else{
            if(DEBUG){
                $error = curl_errno($ch);
                echo "Post faild,error:{$error}\n";
            }
            curl_close($ch);
            return false;
        }
    }
    /**
     * 提交发送红包请求
     * @return type
     */
    public function sendRedPack(){
        // 订单号生成算法是，商户ID+年月日时分秒+毫秒
        $this->mchBillNo = $this->mchId.date('YmdHis').substr(microtime(), 2, 4);
        // 随机字符串简单用两次MD5的订单号
        $this->nonceStr = md5(md5($this->mchBillNo));
        $sign = "act_name={$this->actName}&client_ip=".$this->ip."&mch_billno={$this->mchBillNo}"
            . "&mch_id={$this->mchId}&nonce_str={$this->nonceStr}&re_openid={$this->openid}&remark={$this->remark}"
            . "&send_name={$this->mchName}&total_amount={$this->amount}&total_num=1"
            . "&wishing={$this->wishing}&wxappid={$this->config->getWxAppId()}&key={$this->config->getWxApiKey()}";
        $this->sign = strtoupper(md5($sign));
        $vars = "<xml>"
            . "<act_name><![CDATA[{$this->actName}]]></act_name>"
            . "<client_ip><![CDATA[{$this->ip}]]></client_ip>"
            . "<mch_billno><![CDATA[{$this->mchBillNo}]]></mch_billno>"
            . "<mch_id><![CDATA[{$this->mchId}]]></mch_id>"
            . "<nonce_str><![CDATA[{$this->nonceStr}]]></nonce_str>"
            . "<re_openid><![CDATA[{$this->openid}]]></re_openid>"
            . "<remark><![CDATA[{$this->remark}]]></remark>"
            . "<send_name><![CDATA[{$this->mchName}]]></send_name>"
            . "<total_amount><![CDATA[{$this->amount}]]></total_amount>"
            . "<total_num><![CDATA[1]]></total_num>"
            . "<wishing><![CDATA[{$this->wishing}]]></wishing>"
            . "<wxappid><![CDATA[{$this->wxAppId}]]></wxappid>"
            . "<sign><![CDATA[{$this->sign}]]></sign>"
        . "</xml>";
        $rs = $this->curl_post_ssl(URL, $vars);
        $this->resultArr = (array)simplexml_load_string($rs, 'SimpleXMLElement', LIBXML_NOCDATA);
        // 处理结果，不想考虑那么多情况，直接偷懒地写
        if(!empty($this->resultArr['return_code'])){
            $this->returnCode = $this->resultArr['return_code'];
        }
        if(!empty($this->resultArr['return_msg'])){
            $this->returnMsg = $this->resultArr['return_msg'];
        }
        if(!empty($this->resultArr['result_code'])){
            $this->resultCode = $this->resultArr['result_code'];
        }
        if(!empty($this->resultArr['result_code'])){
            $this->resultCode = $this->resultArr['result_code'];
        }
        if(!empty($this->resultArr['err_code'])){
            $this->errCode = $this->resultArr['err_code'];
        }
        if(!empty($this->resultArr['err_code_des'])){
            $this->errCodeDes = $this->resultArr['err_code_des'];
        }
        if(!empty($this->resultArr['send_listid'])){
            $this->sendListId = $this->resultArr['send_listid'];
        }
        if(!empty($this->resultArr['send_time'])){
            $this->sendTime = $this->resultArr['send_time'];
        }
        // 保存记录到数据库，如果用不到数据库可以注释掉下一行
        $insertResult = $this->saveToDataBase();
        if(DEBUG){
            echo 'insertResult:';
            var_dump($insertResult);
        }
        return $this->resultArr;
    }
    /**
     * 返回
     * @return boolean
     */
    private function saveToDataBase(){
        $sql = "INSERT INTO `{$this->config->getDbName()}`.`act3` (`openid`, `amount`, `act_name`, `mch_name`, "
        . "`wishing`, `remark`, `return_code`, `return_msg`, `result_code`, `err_code`, `err_code_des`, `send_listid`, "
                . "`send_time`, `mch_billno`, `nonce_str`, `sign`, `create_time`, `update_time`) VALUES ("
        . "'{$this->openid}', '{$this->amount}', '{$this->actName}', '{$this->mchName}', '{$this->wishing}', "
        . "'{$this->remark}', '{$this->returnCode}', '{$this->returnMsg}', '{$this->resultCode}', '{$this->errCode}', "
        . "'{$this->errCodeDes}', '{$this->sendListId}', '{$this->sendTime}', '{$this->mchBillNo}', "
        . "'{$this->nonceStr}', '{$this->sign}', NOW(), NOW());";
        $rs = mysql_query($sql, $this->config->getDbLink());
        return $rs;
    }
}

 // 调用方法：
  $wxRedPackSdk = new wxRedPack($actName, $mchName, $wishing, $remark, $amount, $openid);
  $wxRedPackSdk->sendRedPack();
 