<?php
    header("Content-type: text/html; charset=utf-8");
    //入口文件
    @require "pay.php";
    include("mysql.class.php");//引入数据库相关
    $packet = new Packet();
    //获取用户信息
    $get = $_GET['param'];
    $code = $_GET['code'];
    $act_name = '北京信息网红包一期';//活动名称
    $total_amount = 100;//红包付款金额，单位分
    $ran = mt_rand(5, 6);//	//随机中奖
    $qdcard = 10;//10天签到卡
    $time = time();//现在时间记录
    $aopenid = trim($_GET['aopenid']);
    if ($aopenid != "" && $code != "" && $get != "") {// 拆红包
        $userinfo['openid'] = $aopenid;
    //检查红包是否发放完
        $checkhdSql = "SELECT COUNT(act_name) as hd_cou,SUM(total_amount) as hd_sum FROM  `wx_hongbao` WHERE act_name='{$act_name}' ";
        $reshd = mysqlJi::mysqlQu($checkhdSql);//sql语句
        $hdfe = mysqlJi::mysqlFeAs($reshd);
        //echo '一共发'.$hdfe[0]['hd_cou'].'红包,金额一共'.$hdfe[0]['hd_sum'].'分';
        if ($hdfe[0]['hd_cou'] > 1000 || $hdfe[0]['hd_sum'] > 100000) {
            exit('<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=0"><link rel="stylesheet" href="css/hongbao.css"><title>拆红包</title></head><body><div class="all"><div class="bg"><img src="img/18.png"></div><div class="bg_0"><img src="img/19.png"></div><div class="jx"><img src="img/20.png"><p id="p">奖品发完了</p></div></div><script>var jx=document.getElementsByClassName("jx")[0],t=50;window.onload=function(){setInterval(function(){t--,18>=t||(jx.style.marginTop=t+"%")},50)};</script></body></html>');
    //		exit("<script>alert('红包发放完了,下次早点哦--请关闭本页');</script><h1>请关闭本页</h1>");

        }
    //检查用户是否参与过这个活动
        $getBiaoshi = mysqlJi::mysqlQu("SELECT act_name FROM  `wx_hongbao` WHERE act_name='{$act_name}' AND  re_openid ='{$userinfo['openid']}';");
        $rows2 = mysqlJi::mysqlRows();//受影响的行数
        if ($rows2 == 0) {
            //随机中奖
            if ($ran != 6) {
                //入数据库,抽奖一次不能再抽奖
                $sql21 = "INSERT INTO `wx_hongbao` (`act_name`, `re_openid`, `total_amount`, `atime` , `beizhu`) VALUES ('{$act_name}', '{$userinfo['openid']}', '0','{$time}', '')";
                $res = mysqlJi::mysqlQu($sql21);//sql语句
    //	       		赠送签到卡,
    //				echo "<script>alert('没得到红包,赠送签到--请关闭本页');</script>";
                //检查用户是否入过库
                $getBiaoshi = mysqlJi::mysqlQu("SELECT COUNT(*) as biaoshi FROM  `wx_sign` WHERE `openid` ='{$userinfo['openid']}';");
                $acount = mysqlJi::mysqlFeAs($getBiaoshi);//查找数据库是否已经存在该数据
                if ($acount[0]['biaoshi'] == 0) {//如果用户不存在
                    $userin = json_encode($userinfo);//序列化
                    //用户入库
                    $insertsql = "INSERT INTO `wx_user` (`openid`, `nickname`, `headimgurl`, `userinfo`) VALUES ('" . $userinfo['openid'] . "', '" . $userinfo['nickname'] . "', '" . $userinfo['headimgurl'] . "', '" . $userin . "')";
                    $res1 = mysqlJi::mysqlQu($insertsql);//sql语句
                    $rows = mysqlJi::mysqlRows();//受影响的行数
                    if ($rows == 1) {
                        //签到入库
                        $resnewsign = mysqlJi::mysqlQu("INSERT INTO `wx_sign` (`openid`, `dateline`, `signdays`) VALUES ('{$userinfo['openid']}', '{$time}', " . $qdcard . ")");//sql语句
                        echo "<script>alert('新用户赠送" . $qdcard . "天签到成功,连续签到有红包哦,--请关闭本页');</script>";

                    } else {
                        echo "网络连接失败";
                    }
                } else {//用户存在,检查今天是否签到
                    //检查今天是否签到
                    $todayBegin = strtotime(date('Y-m-d') . " 00:00:00");
                    $todayEnd = strtotime(date('Y-m-d') . " 23:59:59");
                    $checkSignSql = "SELECT * FROM `wx_sign` WHERE `openid` = '{$userinfo['openid']}' AND `dateline` < {$todayEnd} AND `dateline` > {$todayBegin} ";
                    $res2 = mysqlJi::mysqlQu($checkSignSql);//sql语句
                    $rows2 = mysqlJi::mysqlRows();//受影响的行数
                    if ($rows2 == 1) {
                        //签到入库
                        $resarign = mysqlJi::mysqlQu("UPDATE `wx_sign` SET `dateline`='{$time}', `signdays` = `signdays` + " . $qdcard . " WHERE (`openid`='{$userinfo['openid']}')");//sql语句
                        echo "<script>alert('今天已签到过,增加赠送" . $qdcard . "天签到成功,连续签到有红包哦,--请关闭本页');</script>";
                    } else {
                        //检查是否漏签
                        //用户存在 ,检查是连续的吗?重置登录signdays为1 ! ! ! ! !
                        $yesterdayBegin = strtotime(date("Y-m-d", strtotime("-1 day")) . " 00:00:00");
                        $yesterdayEnd = strtotime(date("Y-m-d", strtotime("-1 day")) . " 23:59:59");
                        $checkContinuSql = "SELECT * FROM `wx_sign` WHERE  `openid` = '{$userinfo['openid']}' AND `dateline` < {$yesterdayEnd} AND `dateline` > {$yesterdayBegin}";
                        $res3 = mysqlJi::mysqlQu($checkContinuSql);//sql语句
                        $rows3 = mysqlJi::mysqlRows();//受影响的行数
                        if ($rows3 == 1) {//是连续的
                            $qdcard2 = $qdcard + 1;
                            $updateSignSql = "UPDATE `wx_sign` SET `dateline`='{$time}', `signdays` = `signdays` + " . $qdcard2 . " WHERE (`openid`='{$userinfo['openid']}')";
                            $res4 = mysqlJi::mysqlQu($updateSignSql);//sql语句
                            $rows4 = mysqlJi::mysqlRows();//受影响的行数
                            if ($rows4 == 1) {
                                echo "<script>alert('今天未签到过,算现在签到,赠送" . $qdcard2 . "天签到成功,连续签到有红包哦,--请关闭本页');</script>";
                            } else {
                                echo "网络连接失败2";
                            }

                        } else {//重置signdays设置为1
                            $reSignSql = "UPDATE `wx_sign` SET `dateline`='{$time}', `signdays` = " . $qdcard . " WHERE (`openid`='{$userinfo['openid']}')";
                            $res5 = mysqlJi::mysqlQu($reSignSql);//sql语句
                            $rows5 = mysqlJi::mysqlRows();//受影响的行数
                            if ($rows5 == 1) {
                                echo "<script>alert('昨天漏签,签到天数重置后赠送" . $qdcard . "天签到成功,连续签到有红包哦,--请关闭本页');</script>";

                            } else {
                                echo "网络连接失败3";
                            }
                        }
                    }
                }

                exit('<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=0"><link rel="stylesheet" href="css/hongbao.css"><title>拆红包</title></head><body><div class="all"><div class="bg"><img src="img/18.png"></div><div class="bg_0"><img src="img/19.png"></div><div class="jx"><img src="img/20.png"><p id="p">10天签到</p></div></div><script>var jx=document.getElementsByClassName("jx")[0],t=50;window.onload=function(){setInterval(function(){t--,18>=t||(jx.style.marginTop=t+"%")},50)};</script></body></html>');

            }
            //$userin = json_encode($userinfo);//序列化
            //$sql1="INSERT INTO `wx_hongbao` (`re_openid`, `user_info`) VALUES ('".$userinfo['openid']."', '".$userin."')";
            //$res1=mysqlJi::mysqlQu($sql1);//sql语句
            //$rows=mysqlJi::mysqlRows();//受影响的行数
            //if($rows==1){
            //调取支付方法
            $packet->_route('wxpacket', array('openid' => $userinfo['openid']), $total_amount, $act_name);
            //}else{
            //	exit("网络连接失败");
            //}
            mysqlJi::mysqlCl();//关闭数据库连接
        } else {
            exit('<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=0"><link rel="stylesheet" href="css/hongbao.css"><title>拆红包</title></head><body><div class="all"><div class="bg"><img src="img/18.png"></div><div class="bg_0"><img src="img/19.png"></div><div class="jx"><img src="img/20.png"><p id="p">参与过活动了</p></div></div><script>var jx=document.getElementsByClassName("jx")[0],t=50;window.onload=function(){setInterval(function(){t--,18>=t||(jx.style.marginTop=t+"%")},50)};</script></body></html>');

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
            exit("NOAUTH");
        }
    //	var_dump($userinfo);die;
        exit('<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=0"><link rel="stylesheet" href="css/hongbao.css"><title>北京信息网拆红包</title></head><body><div class="all"><div class="bg_0"><img src="img/16.png"></div><div class="jx"><img src="img/15.png"></div><div class="cai"><img src="img/cai.png"></div></div><script>function RemoveRed(){cai.onclick=function(){this.style.transform="rotateY(720deg)";var e=this;setTimeout(function(){e.style.display="none",window.location.href="567f387adc9c893d99367d492cfde8c6.php?aopenid=' . $userinfo['openid'] . '&param=' . $get . '&code=' . $code . '"},3e3)}}var cai=document.getElementsByClassName("cai")[0],dg=document.getElementsByClassName("bg_0")[0];RemoveRed();</script></body></html>');

    } else {
        $packet->_route('userinfo');
    }
