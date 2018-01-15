<?php

class Packet
{
    private $wxapi;

    function _route($fun, $param = '', $total_amount = '', $act_name = '')
    {
        @require_once "oauth2.php";
        $this->wxapi = new Wxapi();
        switch ($fun) {
            case 'userinfo':
                return $this->userinfo($param);
                break;
            case 'wxpacket':
                return $this->wxpacket($param, $total_amount, $act_name);
                break;
            default:
                exit("Error_fun");
        }
    }

    /**
     * 用户信息
     *
     */
    private function userinfo($param)
    {
        $get = $param['param'];
        $code = $param['code'];
        if ($get == 'access_token' && !empty($code)) {
            $json = $this->wxapi->get_access_token($code);
            if (!empty($json)) {
                $userinfo = $this->wxapi->get_user_info($json['access_token'], $json['openid']);
                return $userinfo;
            }
        } else {
            $this->wxapi->get_authorize_url('http://b.bjxxw.com/hongbao/567f387adc9c893d99367d492cfde8c6.php?param=access_token', 'STATE');
        }
    }

    /**
     * 微信红包
     *
     */
    private function wxpacket($param, $total_amount, $act_name)
    {
//		$getBiaoshi=mysqlJi::mysqlQu("SELECT act_name as biaoshi FROM  `wx_hongbao` WHERE re_openid ='{$param["openid"]}';");
//			$acount=mysqlJi::mysqlFeAs($getBiaoshi);//查找数据库是否已经存在该数据
////			var_dump($acount[0]['biaoshi']);
//			if($acount[0]['biaoshi']==0){
        return $this->wxapi->pay($param['openid'], $total_amount, $act_name);
//				
//			}else{				
//				echo "<script>alert('您参与过活动了,--请关闭本页');</script>";
//				return false;
//			}

    }
}

?>