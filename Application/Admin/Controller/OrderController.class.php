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
 * 订单管理
 * @author wangliang
 * @date 2017-02-07
 */
class OrderController extends AdminController {
    protected $Morder;
    protected $Orderstatus;
    public function _initialize(){
        parent::_initialize();
        $this->Morder = M('order');
        $this->Orderstatus = array('0'=>'待支付','1'=>'待体验','2'=>'已完成','3'=>'申请退款','4'=>'退款受理中');
    }

    /**
     * 订单列表
     */
    public function index(){
        $where = array();
        $order_id = trim(I('order_id'));
        $mobile = trim(I('mobile'));
        $time_start = trim(I('time_start'));
        $time_end = trim(I('time_end'));
        $today_order = trim(I('today_order')); //查看今日订单
        //查询条件
        if (mb_strlen($order_id, 'utf8') > 0) $where['o.id'] = $order_id;
        if (mb_strlen($mobile, 'utf8') > 0) $where['o.mobile'] = $mobile;
        if ((strtotime($time_start) > 0) && (strtotime($time_end) > 0)) {
            $where['o.create_time'] = array('between', array($time_start, $time_end));
        }
        if ($today_order == 'today_order') $where['o.create_time'] = time();

        //读取数据
        $count = $this->Morder->where($where)->count();
        $Page = new \Think\Page($count, C('PAGE'));
        $show = $Page->show();
        $this->assign('page', $show);

        $order_info = $this->Morder->alias('o')
            ->join('xm_users u ON  u.id=o.users_id','LEFT')
            ->where($where)->field('o.*,u.mobile')->limit($Page->firstRow.','.$Page->listRows)->select();
        foreach ($order_info as $key=>$val){
            $order_info[$key]['status'] = $this->Orderstatus[$val['state']];
        }
        $this->assign('order_info', $order_info);
        $this->assign('order_state', $this->order_state);
        $this->display();
    }

    /**
     * 订单详情
     */
    public function detail(){
        $id = (int)I('id');
        $where = array();
        $where['o.id'] = $id;
        //订单信息->alias('v')
        //->join('xm_classification c ON  c.id=v.class_id','LEFT')
        $order_info = $this->Morder->alias('o')->field('o.*,s.start_time,v.name vname,v.province,v.city,v.area,v.address,si.name siname,c.name')
            ->join('xm_schedule s ON  o.schedule_id=s.id','LEFT')
            ->join('xm_venue v ON  s.venue_id=v.id','LEFT')
            ->join('xm_service_items si ON  s.venue_service_items_id=si.id','LEFT')
            ->join('xm_classification c ON  c.id=si.class_id','LEFT')
            ->where($where)->find();
        $order_info['status'] = $this->Orderstatus[$order_info['state']];
        $province = $this->getaddress($order_info['province']);
        $city = $this->getaddress($order_info['city']);
        $area = $this->getaddress($order_info['area']);
        $order_info['addressdetail'] = $province.$city.$area.$order_info['address'];
        //会员信息
        $member_info = A('Member')->member_info($order_info['users_id']);
        $this->assign('order_info', $order_info);
        $this->assign('member_info', $member_info);
        $this->assign('order_state', $this->order_state);
        $this->display();
    }

    //获取详细地址
    public function getaddress($id){
        $where['id']=$id;
        $name = M('region')->where($where)->getField('name');
        return $name;
    }

}