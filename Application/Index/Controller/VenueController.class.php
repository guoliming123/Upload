<?php
/**
 * 场馆
 * User: zhaibaoming
 * Date: 2017/2/7
 * Time: 9:42
 */

namespace Index\Controller;

use Think\Controller;

class VenueController extends Controller
{
    public function index()
    {
        $id = I('get.id', 0, 'intval');
        if ($id < 1) {
            $this->error('参数错误');
        }

        //场馆信息
        $VenueModel = M('Venue v');
        $this->venueList = $VenueModel->where('id= ' . $id . ' AND state = 1')->find();
        if (!$this->venueList) {

            $this->display();
            return;

        }
        //子类信息
        $VenueServiceItemsModel = M('VenueServiceItems vsi');
        $join = C('DB_PREFIX') . 'service_items si ON vsi.service_items_id = si.id';
        $where = 'si.state = 1 AND vsi.venue_id=' . $id;
        $order = 'si.sort desc';
        $venueServiceItemsList = $VenueServiceItemsModel->join($join)->where($where)->order($order)->select();
        $data = [];
        foreach ($venueServiceItemsList as $k => $v) {
            $data[$k] = $v;
            $data[$k]['minute'] = floor(intval($v['activity_time']) / 60);
        }
        $this->venueServiceItemsList = $data;

        //排期信息
        $scheduleList = $this->lookupSchedule($id, 0);

        //订单
        $queryOrderList = $this->queryOrder($scheduleList);

        $scheduleData = [];
        if ($queryOrderList) {
            foreach ($queryOrderList as $val) {
                foreach ($scheduleList as $k => $v) {
                    if ($val['schedule_id'] == $v['id']) {
                        $scheduleData[$k] = $v;
                        $scheduleData[$k]['remainingNumber'] = $v['member_price'] - $val['nums'];
                    }
                }
            }

            $this->scheduleList = $scheduleData;
        }else{
            $this->scheduleList = $scheduleList;
        }



        $this->assign('venue_id', $id);
        $this->assign('ajaxQueryDay', U('Venue/queryDay'));

        $this->display();
    }

    /**
     * ajax  查询 排期
     *
     * @author: zhaibaoming
     * @Date: 2017-02-08
     */
    public function queryDay()
    {
        if (!IS_AJAX) {

            return;
        }
        $id = I('post.venue_id', 0, 'intval');
        $type_id = I('post.type_id', 0, 'intval');
        if ($id < 0 || $type_id < 0) {

            return;
        }
        $data = $this->lookupSchedule($id, $type_id);

        $this->ajaxReturn($data);
    }

    /**
     * 编辑时间
     *
     * @param $type int 0-今天,1明天,2后天 .....
     *
     * @author: zhaibaoming
     * @Date: 2017-02-07
     */
    private function editTime($type)
    {
        $data = [
            'startTime' => 0,
            'endTime' => 0,
        ];
        switch ($type) {
            case 0:
                $data['startTime'] = time();
                $data['endTime'] = strtotime(date('Y-m-d', strtotime('+1 day')));
                break;
            case 1:
                $data['startTime'] = strtotime(date('Y-m-d', strtotime('+1 day'))) - 1;
                $data['endTime'] = strtotime(date('Y-m-d', strtotime('+2 day')));
                break;
            case 2:
                $data['startTime'] = strtotime(date('Y-m-d', strtotime('+2 day'))) - 1;
                $data['endTime'] = strtotime(date('Y-m-d', strtotime('+3 day')));
                break;
        }

        return $data;
    }

    /**
     * 计算 排期
     *
     * @param $venueId
     * @param $timeType
     * @author: zhaibaoming
     * @Date: 2017-02-07
     * @return mixed
     */
    private function lookupSchedule($venueId, $timeType)
    {

        $timeData = $this->editTime($timeType);
        $where = [
            'venue_id' => $venueId,
            'start_time' => ['between', $timeData['startTime'] . ',' . $timeData['endTime']],
        ];
        $order = ' start_time asc';
        $field = ' * ';
        $ScheduleModel = M('Schedule s');
        $scheduleList = $ScheduleModel->field($field)->where($where)->order($order)->select();

        $data = [];
        foreach ($scheduleList as $k => $v) {
            if (empty($v['id'])) {
                continue;
            }
            $data[$k] = $v;
            $data[$k]['start_time'] = date('H:i', $v['start_time']);
            $data[$k]['endTime'] = date('H:i', $v['start_time'] + $v['time_length'] * 60);
        }

        return $data;
    }

    /**
     * 查询订单
     *
     * @param $scheduleList
     * @author: zhaibaoming
     * @Date: 2017-02-09
     * @return mixed
     */
    private function queryOrder($scheduleList)
    {
        $data = [];
        foreach ($scheduleList as $v) {
            $data[] = $v['id'];
        };
        $idString = implode(',', $data);
        $where = [
            'schedule_id' => ['in', $idString],
            'state' => ['in', '0,1']
        ];
        $field = 'schedule_id ,count(*) AS nums';
        $OrderModel = M('Order');
        $res = $OrderModel->field($field)->where($where)->select();
        //echo $OrderModel->getLastSql();
        return $res;
    }
}