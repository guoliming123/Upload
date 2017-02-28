<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Controller;
use User\Api\UserApi;

/**
 * 会员管理
 * @author wangliang
 * @date 2017-02-06
 */
class MemberController extends AdminController {
    protected $Mmember;
    protected $Orderstatus;
    public function _initialize(){
        parent::_initialize();
        $this->Mmember = M('users');
        $this->Orderstatus = array('0'=>'待支付','1'=>'待体验','2'=>'已完成','3'=>'申请退款','4'=>'退款受理中');
    }
    /**
     * 会员列表
     */
    public function index(){
        $where = array();
        $mobile = trim(I('mobile'));
        $nickname = trim(I('nickname'));
        $vip = (int)I('vip');
        $time_start = trim(I('time_start'));
        $time_end = trim(I('time_end'));
        //查询条件
        if (mb_strlen($mobile, 'utf8') > 0) $where['mobile'] = $mobile;
        if (mb_strlen($nickname, 'utf8') > 0) $where['nickname'] = array('like', '%'.$nickname.'%');
        if (in_array($vip, array(1, 2))) $where['vip'] = $vip;
        if ((strtotime($time_start) > 0) && (strtotime($time_end) > 0)) {
            $where['create_time'] = array('between', array($time_start, $time_end));
        }
        //执行查询
        $where['status'] = array('egt', 0);
        $userinfo = $this->Mmember->where($where)->select();
        //用户的消费总金额
        if($userinfo){
            foreach ($userinfo as $key=>$val){
                $userid = $userinfo[$key]['id'];
                $userinfo[$key]['total'] = $this->totalmoney($userid);
            }
        }
        $this->assign('userinfo', $userinfo);
        $this->display();
    }

    /*
     * 用户消费总金额
     */
    protected function totalmoney($uid){
        $whe['users_id'] = $uid;
        $whe['state'] = array('in','1,2,3');
        $totalmoney = M('order')->where($whe)->field('price')->Sum('price');
        return $totalmoney;
    }

    /**
     * 会员详情
     */
    public function detail(){
        $uid = (int)I('id');
        //会员信息
        $memberinfo = $this->member_info($uid);
        $memberinfo['totalmoney'] = $this->totalmoney($uid);
        //用户消费记录
        $whe['users_id'] = $uid;
        $orderlsit = M('order')->where($whe)->select();
        foreach ($orderlsit as $key=>$val){
            $orderlsit[$key]['status'] = $this->Orderstatus[$val['state']];
        }
        $memberinfo['costrecord'] = $orderlsit;
        $this->assign('memberinfo', $memberinfo);
        $this->display();
    }

    /**
     * 冻结账户
     */
    public function freeze(){
        $id = (int)I('id');
        $where['id'] = $id;
        $data['status'] = 0;
        $this->Mmember->where($where)->save($data);
        echo $this->Mmember->_sql();
    }
    
    /**
     * 用户信息
     * @author wangliang
     * @date 2017-02-07
     */
    public function member_info($users_id){
        $where = array();
        $where['uid'] = (int)$users_id;
        $userinfo = $this->Mmember->where($where)->find();
        return $userinfo;
    }

    /**
     * 是否是手机号
     * @param string $mobile 手机号
     */
    public function is_mobile($mobile){
        $reslut = preg_match('/^1[3|4|5|7|8|9][0-9]{9}$/', $mobile);
        return ($reslut > 0) ? true : false;
    }


}