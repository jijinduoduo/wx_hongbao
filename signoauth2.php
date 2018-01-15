<?php
class Wxapi {
    private $app_id = '';
    private $app_secret = '';
    private $app_mchid = '';
    function __construct(){
    //do sth here....
    }
    /**
     * 微信支付
     * 
     * @param string $openid 用户openid
     */
    public function pay($re_openid,$total_amount,$act_name,$db=null)
    {
        include_once('WxHongBaoHelper.php');
        $commonUtil = new CommonUtil();
        $wxHongBaoHelper = new WxHongBaoHelper();

        $wxHongBaoHelper->setParameter("nonce_str", $this->great_rand());//随机字符串，丌长于 32 位
        $wxHongBaoHelper->setParameter("mch_billno", $this->app_mchid.date('YmdHis').rand(1000, 9999));//订单号
        $wxHongBaoHelper->setParameter("mch_id", $this->app_mchid);//商户号
        $wxHongBaoHelper->setParameter("wxappid", $this->app_id);
        $wxHongBaoHelper->setParameter("nick_name", '北京信息网');//提供方名称
        $wxHongBaoHelper->setParameter("send_name", '北京信息网');//红包发送者名称
        $wxHongBaoHelper->setParameter("re_openid", $re_openid);//相对的openid
        $wxHongBaoHelper->setParameter("total_amount", $total_amount);//付款金额，单位分
        $wxHongBaoHelper->setParameter("min_value", 100);//最小红包金额，单位分
        $wxHongBaoHelper->setParameter("max_value", 100);//最大红包金额，单位分
        $wxHongBaoHelper->setParameter("total_num", 1);//红包収放总人数
        $wxHongBaoHelper->setParameter("wishing", '北京信息网祝您笑口常开');//红包祝福诧
        $wxHongBaoHelper->setParameter("client_ip", '210.51.190.166');//调用接口的机器 Ip 地址
        $wxHongBaoHelper->setParameter("act_name", $act_name);//活劢名称
        $wxHongBaoHelper->setParameter("remark", '快来抢！');//备注信息
        $postXml = $wxHongBaoHelper->create_hongbao_xml();

        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack';
        $responseXml = $wxHongBaoHelper->curl_post_ssl($url, $postXml);
		$responseObj = simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $news = json_encode($responseObj);//序列化
		if($responseObj->result_code == "SUCCESS"){
			print "<script>alert('红包发放成功--请关闭本页领取红包');</script>";
			echo '红包发放成功--请关闭本页领取红包';
			$sql2="UPDATE `wx_hongbao` SET `act_name`='".$wxHongBaoHelper->getParameter("act_name")."', `total_amount`='".$responseObj->total_amount."', `beizhu`='".$news."' WHERE (`re_openid`='".$responseObj->re_openid."')";
	        $res=mysqlJi::mysqlQu($sql2);//sql语句
//			$rows=mysqlJi::mysqlRows();//受影响的行数
//			if($rows==1){
//				echo "----成功<br>";
//			}else{
//				echo "----失败<br>";
//			}	
mysqlJi::mysqlCl();//关闭数据库连接
		}else{
			echo "<script>alert('您参与过活动了,---请关闭本页');</script>";
			return false;
		}
       
		return $responseObj->return_code;

		return;
   }
    /**
     * 获取微信授权链接
     * 
     * @param string $redirect_uri 跳转地址
     * @param mixed $state 参数
     */
    public function get_authorize_url($redirect_uri = '', $state = '')
    {
        $redirect_uri = urlencode($redirect_uri);
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$this->app_id}&redirect_uri={$redirect_uri}&response_type=code&scope=snsapi_userinfo&state={$state}#wechat_redirect";  
        echo "<script language='javascript' type='text/javascript'>";  
        echo "window.location.href='$url'";  
        echo "</script>";       
    }       
    
    /**
     * 获取授权token
     * 
     * @param string $code 通过get_authorize_url获取到的code
     */
    public function get_access_token($code = '')
    {
        $token_url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$this->app_id}&secret={$this->app_secret}&code={$code}&grant_type=authorization_code";
        $token_data = $this->http($token_url);
        if(!empty($token_data[0]))
        {
            return json_decode($token_data[0], TRUE);
        }
        
        return FALSE;
    }   

    /**
     * 获取授权后的微信用户信息
     * 
     * @param string $access_token
     * @param string $open_id
     */
    public function get_user_info($access_token = '', $open_id = '')
    {
        if($access_token && $open_id)
        {
			$access_url = "https://api.weixin.qq.com/sns/auth?access_token={$access_token}&openid={$open_id}";
			$access_data = $this->http($access_url);
			$access_info = json_decode($access_data[0], TRUE);
			if($access_info['errmsg']!='ok'){
				exit('页面过期');
			}
            $info_url = "https://api.weixin.qq.com/sns/userinfo?access_token={$access_token}&openid={$open_id}&lang=zh_CN";
            $info_data = $this->http($info_url);  		
            if(!empty($info_data[0]))
            {
                return json_decode($info_data[0], TRUE);
            }
        }
        
        return FALSE;
    }   	
    /**
     * Http方法
     * 
     */ 
    public function http($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $output = curl_exec($ch);//输出内容
        curl_close($ch);
        return array($output);
    }   

    /**
     * 生成随机数
     * 
     */     
    public function great_rand(){
        $str = '1234567890abcdefghijklmnopqrstuvwxyz';
        for($i=0;$i<30;$i++){
            $j=rand(0,35);
            $t1 .= $str[$j];
        }
        return $t1;    
    }
}
?>