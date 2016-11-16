<?php
/**
 * Created by PhpStorm.
 * User: guoshaomin
 * Date: 15/12/9
 * Time: 下午2:00
 * IOS前端把苹果支付生成的receipt进行 编码后传给后端 后端去苹果商城进行二次验证 成功后进行平台业务操作
 */
class IosPay{

    /**
     * IAP支付凭证  以及是否使用沙盒进行验证 
     * 
     * 
     * */
   public  static  function  getReceiptData($receipt,$useSandBox = true)
    {

        if($useSandBox){
            $endpoint = 'https://sandbox.itunes.apple.com/verifyReceipt';
        }else{
            $endpoint = 'https://buy.itunes.apple.com/verifyReceipt';
        }        $postData = json_encode(
            array('receipt-data' => $receipt)
        );

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);  //这两行一定要加，不加会报SSL 错误
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $response = curl_exec($ch);
        $errno    = curl_errno($ch);
        $errmsg   = curl_error($ch);
        curl_close($ch);

        if($errno != 0) {
            throw new ServiceException($errno,$errmsg);
        }

        $data = json_decode($response);

        if (!is_object($data)) {
            throw new ServiceException(ErrorCodes::USER_DEFINED_ERROR,'Invalid response data');
        }

        // 返回receipt错误
        if(!isset($data->status) ){
            throw new ServiceException(ErrorCodes::USER_DEFINED_ERROR,'Invalid receipt');
        }
        // 使用 沙盒环境凭证却使用了苹果正式环境返回错误码
        if ( $data->status == 21007 ) {
           $res =  self::getReceiptData($receipt,true);
        }elseif($data->status == 21008 ){ // 使用 正式环境凭证却使用了苹果沙盒环境返回错误码
            $res = self::getReceiptData($receipt,false);
        }elseif($data->status != 0){
            throw new ServiceException(ErrorCodes::USER_DEFINED_ERROR,'Invalid receipt');
        }else{
            $res= array(
                'quantity'       =>  $data->receipt->quantity,
                'product_id'     =>  $data->receipt->product_id,
                'transaction_id' =>  $data->receipt->transaction_id,
                'purchase_date'  =>  $data->receipt->purchase_date,
                'app_item_id'    =>  $data->receipt->item_id,
                'bid'            =>  $data->receipt->bid,
                'original_transaction_id'=>$data->receipt->original_transaction_id,
                'unique_identifier' =>$data->receipt->unique_identifier,
                'unique_vendor_identifier' =>$data->receipt->unique_vendor_identifier,
                'original_purchase_date'=>$data->receipt->original_purchase_date,
                'isSandBox'=> intval($useSandBox),
            );
        }
        return $res;
    }
}
?>