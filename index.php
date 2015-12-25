<?php
include('ipQuery_TaoBao.php');

$ip = '223.240.90.149';
$ipQuery = new TaobaoIPQuery();
$info = $ipQuery->getIPInfo($ip);

var_dump($info);
