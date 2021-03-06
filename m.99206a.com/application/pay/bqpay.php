<?php
// +----------------------------------------------------------------------
// | FileName: shunpay.php
// +----------------------------------------------------------------------
// | CreateDate: 2017年11月19日
// +----------------------------------------------------------------------
// | Author: xiaoluo
// +----------------------------------------------------------------------
namespace app\pay;
//佰钱支付

use app\pay\bqpay\Util;
use think\Db;

class bqpay extends basepay {
    public function pay(){
        $arr = $this->params;
        $isApp  = request()->isMobile()?'app':'';

        $DataContentParms =array();
        $DataContentParms["X1_Amount"] = $arr['order_money'];; //订单金额
        $DataContentParms["X2_BillNo"] =$arr['order_id'];//订单号
        $DataContentParms["X3_MerNo"] = $arr['pid'];//商户号
        $DataContentParms["X4_ReturnURL"] = $arr['callbackurl'];
        $DataContentParms["X6_MD5info"] = Util::GetMd5str($DataContentParms,$arr['pkey']);

        $DataContentParms["X5_NotifyURL"] = $arr['hrefbackurl'];
        $DataContentParms["X7_PaymentType"] = $arr['tcode'];
        $DataContentParms["X8_MerRemark"] = "虚拟商品";
        $DataContentParms["isApp"] = $isApp; //固定值： 值为"app",表示app接入； 值为空，表示web接入
        
        switch ($arr['tcode']){
            case 'ICBC':
            case 'KJZF':
            case 'CCB':
            case 'ECITIC':
            case 'ABC':
            case 'CMBCHINA':
            case 'CEB':
            case 'POST':
            case 'BSM':
                $isBank=1;
                break;
        }
        if($isBank == 1){
            echo $this->form($DataContentParms, $arr['purl'],'post');
            exit();
        }
        /*
        if($arr['tcode'] == 'KJZF'){
            echo $this->form($DataContentParms, $arr['purl'],'post');
            exit();
        }
        */
        $ret = $this->post($arr['purl'],$DataContentParms);
        $row = json_decode($ret, true); #将返回json数据转换为数组
        $return = array();
        if (!isset($row['status'])){
            exit('错误描述：' . $row['Result']);
        }
        if($row['status'] != '88'){
            exit('系统错误,错误号：' . $row['status'] . '错误描述：' . $row['msg']);
        }else{
                $qrcodeUrl = $row['imgUrl'];
                if(request()->isMobile()){
                    header("location:".$qrcodeUrl);
                }
                $return['info'] = $qrcodeUrl;
                $return['orderid'] = $DataContentParms["X2_BillNo"];
                $return['money'] = $arr['order_money'];
        }
        
        return $this->form($return, "/index/qr_code");
    }

    public function create_sign()
    {
        // TODO: Implement create_sign() method.
    }

    public function check_sign(){


        $arr=$this->params;
        $form = "<Form action=".$arr['callbackurl']." method='get'>";
        foreach ($_REQUEST as $k=>$v){
            $form .= "<input name='$k' value='$v' />";
        }
        $form .='<input type="submit"></form>';
        file_put_contents(dirname(dirname(dirname(__FILE__))).'/public/callbackLog/bqlog.html', $form."\r\n",FILE_APPEND);


        $signature         =     $_REQUEST["MD5info"];
        $DataContentParms =array();
        $DataContentParms["MerNo"] = $_REQUEST['MerNo'];
        $DataContentParms["BillNo"] = $_REQUEST["BillNo"];
        $DataContentParms["Amount"] =  $_REQUEST["Amount"];
        $DataContentParms["Succeed"] =  $_REQUEST["Succeed"];

        ksort($DataContentParms);
        $prestr = http_build_query($DataContentParms);     	//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $sign = strtoupper(md5($prestr."&". strtoupper(md5($arr['pkey']))));

         return  $signature == $sign;
//        $DataContentParms =array();
//        $DataContentParms["MerNo"] = $_REQUEST['MerNo'];
//        $DataContentParms["BillNo"] = $_REQUEST["BillNo"];
//        $DataContentParms["Amount"] =  $_REQUEST["Amount"];
//        $DataContentParms["Succeed"] =  $_REQUEST["Succeed"];
//
//        $info = db('vc_order')->where(['order_id'=>$_REQUEST["BillNo"]])->find();
//        $thirdpay = db('vc_thirdpay')->where(['name'=>$info['pay_api']])->find();
//        $apikey = $thirdpay['pkey'];
//        $md5sign = Util::GetMd5str($DataContentParms,$apikey);
//        return $MD5info == $md5sign;
    }
    
    //SUCCESS 交易成功,FAILED 交易失败
    public function pay_ok(){
        $order = Db::table('vc_order')->where(['order_id'=>$this->orderid()])->where('order_state','neq',1)->find();
        return "88" == $_REQUEST['Succeed'] && $order['order_money'] == input('Amount');
    }
    
    public function transid(){
        return $ordernumber =trim(input('BillNo'));//系统订单号
    }
    
    public function orderid(){
        return $ordernumber =trim(input('BillNo'));
    }




}