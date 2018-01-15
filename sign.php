<?php
header("Content-type: text/html; charset=utf-8");

//入口文件
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
            $this->wxapi->get_authorize_url('http://b.bjxxw.com/hongbao/sign.php?param=access_token', 'STATE');
        }
    }

    /**
     * 微信红包
     *
     */
    private function wxpacket($param, $total_amount, $act_name)
    {
        return $this->wxapi->pay($param['openid'], $total_amount, $act_name);
    }

}

include("mysql.class.php");//引入数据库相关
$packet = new Packet();
//获取用户信息
$get = $_GET['param'];
$code = $_GET['code'];
$aopenid = trim($_GET['aopenid']);
$time = time();//现在时间记录
if ($aopenid != "" && $code != "" && $get != "") {//签到金额兑换
    $usersignsql = mysqlJi::mysqlQu("SELECT signdays FROM  `wx_sign` WHERE `openid` ='{$aopenid}';");
    $usersign = mysqlJi::mysqlFeAs($usersignsql);
    $days = $usersign[0]['signdays'];//连续签到天数
    $money = 0;//应得钱数
    if ($days >= 18 && $days < 30) $money = 1;
    elseif ($days >= 30 && $days < 50) $money = 2;
    elseif ($days >= 50 && $days < 100) $money = 5;
    elseif ($days >= 100 && $days < 365) $money = 12;
    elseif ($days >= 365) $money = 100;
    else $money = 0;
    if ($money >= 1 && $days >= 18) {
        $reSignSql = "UPDATE `wx_sign` SET `dateline`='{$time}', `signdays` = `signdays` - " . $days . " WHERE (`openid`='{$aopenid}')";
        $res5 = mysqlJi::mysqlQu($reSignSql);//sql语句
        $rows5 = mysqlJi::mysqlRows();//受影响的行数
        if ($rows5 == 1) {
            //调取支付方法
            echo "<h1>连续签到" . $usersign[0]['signdays'] . "天,给您发了" . $money . '元红包,天数已经清0重新计算.</h1><hr>';
            $money2 = $money * 100;
            $packet->_route('wxpacket', array('openid' => $aopenid), $money2, '签到红包');
        } else {
            echo '<script>alert("网络连接失败,--请关闭本页");</script><script type="text/javascript">var cpro_id="u3166030";</script><script type="text/javascript"src="https://cpro.baidustatic.com/cpro/ui/cm.js"></script>';
        }

    } else {

        echo '<script>alert("签到' . $days . '天不够兑换红包,--请关闭本页");</script><script type="text/javascript">var cpro_id="u3166030";</script><script type="text/javascript"src="https://cpro.baidustatic.com/cpro/ui/cm.js"></script>';
        //document.addEventListener('WeixinJSBridgeReady', function(){ WeixinJSBridge.call('closeWindow'); }, false);
    }
    exit;

}
//判断code是否存在

if ($get == 'access_token' && !empty($code)) {
    $param['param'] = 'access_token';
    $param['code'] = $code;
    //获取用户openid信息
    $userinfo = $packet->_route('userinfo', $param);
    if (empty($userinfo['openid'])) {
        exit('<html><head><meta name="viewport" content="user-scalable=no" ><meta charset="utf-8"/><title>北京信息网签到红包活动</title></head><body><img src="img/logo.jpg" ><h1>请在北京信息网公众号里签到哦!</h1></body><script type="text/javascript">var cpro_id="u3166030";</script><script type="text/javascript"src="https://cpro.baidustatic.com/cpro/ui/cm.js"></script></html>');
    }


    //检查用户是否入过库
    $getBiaoshi = mysqlJi::mysqlQu("SELECT COUNT(openid) as biaoshi FROM  `wx_sign` WHERE `openid` ='{$userinfo['openid']}';");
    $acount = mysqlJi::mysqlFeAs($getBiaoshi);//查找数据库是否已经存在该数据
    if ($acount[0]['biaoshi'] == 0) {//如果用户不存在
        $userin = json_encode($userinfo);//序列化
        //用户入库
        $insertsql = "INSERT INTO `wx_user` (`openid`, `nickname`, `headimgurl`, `userinfo`) VALUES ('" . $userinfo['openid'] . "', '" . $userinfo['nickname'] . "', '" . $userinfo['headimgurl'] . "', '" . $userin . "')";
        $res1 = mysqlJi::mysqlQu($insertsql);//sql语句
        $rows = mysqlJi::mysqlRows();//受影响的行数
        if ($rows == 1) {
            //签到入库
            $resnewsign = mysqlJi::mysqlQu("INSERT INTO `wx_sign` (`openid`, `dateline`, `signdays`) VALUES ('{$userinfo['openid']}', '{$time}', 1)");//sql语句
            echo "<script>alert('新用户您签成功');</script>";

        } else {
            echo "网络连接失败";
        }
    } else {//用户存在,检查今天是否签到

        //检查今天是否签到
        $todayBegin = strtotime(date('Y-m-d') . " 00:00:00");
        $todayEnd = strtotime(date('Y-m-d') . " 23:59:59");
        $checkSignSql = "SELECT openid FROM `wx_sign` WHERE `openid` = '{$userinfo['openid']}' AND `dateline` < {$todayEnd} AND `dateline` > {$todayBegin} ";
        $res2 = mysqlJi::mysqlQu($checkSignSql);//sql语句
        $rows2 = mysqlJi::mysqlRows();//受影响的行数
        if ($rows2 == 1) {
            echo "<script>alert('今天已经签到过了');</script>";
        } else {
            //检查是否漏签
            //用户存在 ,检查是连续的吗?重置登录signdays为1 ! ! ! ! !
            $yesterdayBegin = strtotime(date("Y-m-d", strtotime("-1 day")) . " 00:00:00");
            $yesterdayEnd = strtotime(date("Y-m-d", strtotime("-1 day")) . " 23:59:59");
            $checkContinuSql = "SELECT * FROM `wx_sign` WHERE  `openid` = '{$userinfo['openid']}' AND `dateline` < {$yesterdayEnd} AND `dateline` > {$yesterdayBegin}";
            $res3 = mysqlJi::mysqlQu($checkContinuSql);//sql语句
            $rows3 = mysqlJi::mysqlRows();//受影响的行数
            if ($rows3 == 1) {//是连续的
                $updateSignSql = "UPDATE `wx_sign` SET `dateline`='{$time}', `signdays` = `signdays` + 1 WHERE (`openid`='{$userinfo['openid']}')";
                $res4 = mysqlJi::mysqlQu($updateSignSql);//sql语句
                $rows4 = mysqlJi::mysqlRows();//受影响的行数
                if ($rows4 == 1) {
                    echo "<script>alert('签到增加');</script>";
                } else {
                    echo "网络连接失败2";
                }

            } else {//重置signdays设置为1
                $reSignSql = "UPDATE `wx_sign` SET `dateline`='{$time}', `signdays` = 1 WHERE (`openid`='{$userinfo['openid']}')";
                $res5 = mysqlJi::mysqlQu($reSignSql);//sql语句
                $rows5 = mysqlJi::mysqlRows();//受影响的行数
                if ($rows5 == 1) {
                    echo "<script>alert('漏签,签到重置');</script>";
                } else {
                    echo "网络连接失败3";
                }
            }
        }
    }
    //调取支付方法
    //$packet->_route('wxpacket',array('openid'=>$userinfo['openid']),120,'签到红包');
    $usersignsql = mysqlJi::mysqlQu("SELECT signdays FROM  `wx_sign` WHERE `openid` ='{$userinfo['openid']}';");
    $usersign = mysqlJi::mysqlFeAs($usersignsql);
    mysqlJi::mysqlCl();//关闭数据库连接
    exit('<!DOCTYPE html><html><head><meta name="viewport" content="user-scalable=no" ><meta charset="utf-8"/><title>北京信息网签到红包活动</title><link rel="stylesheet"href="css/index.css"/></head><body><center class="center"><p class="qiandao">已签到<br>' . $usersign[0]['signdays'] . '天</p><div class="touxiang"><img src="' . $userinfo['headimgurl'] . '"/></div><p class="name">' . $userinfo['nickname'] . '</p><div class="btn"><a href="sign.php?aopenid=' . $userinfo['openid'] . '&param=' . $get . '&code=' . $code . '">领取红包</a></div></center><p style="position: relative;bottom:0px;left:0px;"><script type="text/javascript">var cpro_id="u3166030";</script><script type="text/javascript"src="https://cpro.baidustatic.com/cpro/ui/cm.js"></script></p></body></html>');
} else {
    $packet->_route('userinfo');
}
