<?php
/**
 * Created by PhpStorm.
 * User: zhaibaoming
 * Date: 2017/2/8
 * Time: 10:55
 */

namespace Index\Controller;

use Think\Controller;

class OrderController extends Controller
{
    public function index()
    {
        $schedule_id = I('get.schedule_id', 0, 'intval');
        $users_id = session('userInfo.id');
        if (empty($users_id)) {
            die('用户数据错误!');
        }
        if ($schedule_id < 1) {
            $this->error('参数错误');
        }
        //检查是否有用户数据
        $this->checkUserInfo($schedule_id);

        //验证排期
        $scheduleList = $this->checkSchedule($schedule_id);
        if ($scheduleList['code'] == 0) {
            $this->error($scheduleList['msg']);
        }

        //场馆信息
        $VenueModel = M('Venue');
        $this->venueData = $VenueModel->where('id='.$scheduleList['list']['venue_id'])->find();

        //添加订单
        $data = [
            'schedule_id' => $schedule_id,
            'users_id' => $users_id,
            'price' => $scheduleList['list']['price'],
            'state' => 0,
            'create_time' => time(),
        ];
        $OrderModel = M('Order');
        $res = $OrderModel->add($data);
        if (!$res) {
            $this->error('订单添加失败,请重新操作');
        }

        $this->display();
    }

    /**
     * 验证排期
     *
     * @param $schedule_id
     * @author: zhaibaoming
     * @Date: 2017-02-09
     * @return array
     */
    private function checkSchedule($schedule_id)
    {
        $data = [
            'code' => 0,
            'msg' => '',
        ];
        $ScheduleModel = M('Schedule');
        $where = [
            'id' => $schedule_id,
            'start_time' => ['GT', time()],
        ];
        $scheduleList = $ScheduleModel->where($where)->find();
        if (!$scheduleList) {
            $data['msg'] = '不存在该排期!';

            return $data;
        }

        $orderModel = M('Order');
        $where = [
            'schedule_id' => $schedule_id,
        ];
        $orderData = $orderModel->where($where)->select();
        if(count($orderData) >= $scheduleList['people_number']){
            $data['msg'] = '人数已满!';

            return $data;
        }

        $data['code'] = 1;
        $data['list'] = $scheduleList;

        return $data;
    }

    /**
     * 检查是否 有用户信息 是否填写手机号
     *
     * @author: zhaibaoming
     * @Date: 2017-02-08
     */
    private function checkUserInfo($schedule_id)
    {
        $userInfo = session('userInfo');
        if (empty($userInfo['id'])) {
            //A('Index')->userInfoData();
        }

        if (empty($userInfo['mobile'])) {
            $url = U('MobileRegistration/index', ['userId' => $userInfo['id'], 'schedule_id' => $schedule_id]);
            header('location:' . $url);
        }
    }
}