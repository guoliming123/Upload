<?php
/**
 * Created by PhpStorm.
 * User: zhaibaoming
 * Date: 2017/2/6
 * Time: 17:31
 */

namespace Index\Controller;
use Think\Controller;

class SearchController extends Controller
{
    public function index()
    {
        $classification_id = I('param.classification_id', 0, 'intval');
        $search = I('param.search', 0, 'intval');
        if (IS_POST) {
            if ($search === 1) {
                $name = I('post.name');
                $result = $this->searchKeyword($name);
                if ($result['code'] !== 1) {
                    $this->error($result['msg']);
                }
                $this->list = $result['list'];
            } else {
                $classification_id = I('post.classification_id', 0, 'intval');
                $service_items_id = I('post.service_items_id');
                $this->list = $this->postData($classification_id, $service_items_id);
            }
        } elseif (IS_GET) {
            switch ($search) {
                case 1:
                    $classification_id = I('get.id', 0, 'intval');
                    $this->list = $this->postData($classification_id);
                    break;

                case 2:
                    $id = I('get.id', 0, 'intval');
                    if ($id < 1) {
                        $this->error('参数错误');
                    }
                    $this->list = $this->regionVenue($id);
                    break;

                case 3:
                    $id = I('get.id', 0, 'intval');
                    if ($id < 1) {
                        $this->error('参数错误');
                    }
                    $this->list = $this->crowdVenue($id);
                    break;

                case 4:
                    $id = I('get.id', 0, 'intval');
                    if ($id < 1) {
                        $this->error('参数错误');
                    }
                    $this->list = $this->serviceItemsData($id);
                    break;
            }
        }

        if (empty($this->list)) {
            $this->display('no');

            return;
        }

        //项目
        $classificationModel = M('Classification');
        $this->classificationList = $classificationModel->where('state=1')->order('sort DESC')->select();

        //地点
        $regionModel = M('Region');
        $this->regionList = $regionModel->where('level=2')->select();

        //人群
        $crowdModel = M('Crowd');
        $this->crowdList = $crowdModel->where('state=1')->order('sort desc')->select();


        if ($classification_id > 0) {
            //查询当前大类的子类
            $where = [
                'class_id' => $classification_id,
                'state' => 1,
            ];
            $ServiceItemsModel = M('ServiceItems');
            $this->serviceItemsList = $ServiceItemsModel->where($where)->order('sort DESC')->select();
        }

        $this->display();
    }


    /**
     * 适合人群 查场馆
     *
     * @param $id
     * @author: zhaibaoming
     * @Date: 2017-02-06
     * @return mixed
     */
    private function crowdVenue($id)
    {
        $VenueCrowdModel = M('VenueCrowd vc');
        $join = C('DB_PREFIX') . 'venue v';
        $where = [
            ['vc.venue_id=v.id'],
            'crowd_id' => $id,
            'state' => 1,
        ];
        $order = 'sort DESC';
        $data = $VenueCrowdModel->join($join)->where($where)->order($order)->select();

        return $data;
    }

    /**
     * 通过城市 查场馆
     *
     * @param $id
     * @author: zhaibaoming
     * @Date: 2017-02-06
     * @return mixed
     */
    private function regionVenue($id)
    {
        $VenueModel = M('Venue');
        return $VenueModel->where('city=' . $id . ' AND state = 1 ')->order('sort DESC')->select();
    }

    /**
     * 搜索关键字
     *
     * @param $param
     * @author: zhaibaoming
     * @Date: 2017-02-06
     * @return array
     */
    private function searchKeyword($param)
    {
        $data = [
            'code' => 0,
            'msg' => '',
        ];
        if (!check_special_string($param)) {
            $data['msg'] = '关键字错误';

            return $data;
        }
        $data['code'] = 1;
        $venueModel = M('Venue');
        $where = [
            'name' => ['like', '%' . $param . '%'],
            'state' => 1,
        ];
        $data['list'] = $venueModel->where($where)->order('sort desc')->select();
        if ($data['list']) {

            return $data;
        }

        $ServiceItemsModel = M('ServiceItems s');
        $where = [
            ['s.id =vsi.venue_id'],
            ['vsi.venue_id =v.id'],
            's.name' => ['like', '%' . $param . '%'],
            'v.state' => 1,
        ];
        $field = 'v.id,v.name';
        $join1 = C('DB_PREFIX') . 'venue_service_items vsi';
        $join2 = C('DB_PREFIX') . 'venue v';
        $data['list'] = $ServiceItemsModel->field($field)->join($join1)->join($join2)->where($where)->order('v.sort desc')->select();

        return $data;
    }

    /**
     * 通过 服务子类 查询 场馆
     *
     * @param $id
     * @author: zhaibaoming
     * @Date: 2017-02-07
     * @return mixed
     */
    private function serviceItemsData($id)
    {
        $ServiceItemsModel = M('ServiceItems s');
        $where = [
            ['s.id =vsi.venue_id'],
            ['vsi.venue_id =v.id'],
            's.id' => $id,
            'v.state' => 1,
        ];
        $field = 'v.id,v.name';
        $join1 = C('DB_PREFIX') . 'venue_service_items vsi';
        $join2 = C('DB_PREFIX') . 'venue v';
        $data = $ServiceItemsModel->field($field)->join($join1)->join($join2)->where($where)->order('v.sort desc')->select();

        return $data;
    }

    /**
     * 返回 post 请求
     *
     * @author: zhaibaoming
     * @Date: 2017-02-06
     * @return mixed
     */
    private function postData($classification_id, $service_items_id = false)
    {
        $flg = false;

        if (empty($classification_id)) {
            $this->error('参数错误-1');
        }

        if (is_array($service_items_id)) {
            foreach ($service_items_id as $v) {
                if ($v < 0 || is_numeric($service_items_id)) {
                    $this->error('参数错误-3');
                }
            }
            $service_items_string = implode(',', $service_items_id);
            $flg = true;
        }
        $VenueModel = M('Venue v');
        if ($flg) {
            $join = C('DB_PREFIX') . 'venue_service_items vsi';
            $where = 'v.id = vsi.venue_id AND v.class_id =' . $classification_id . ' AND vsi.service_items_id in (' . $service_items_string . ')';
            $order['v.sort'] = 'desc';
            $field = 'v.id,v.name';
            $list = $VenueModel->field($field)->join($join)->where($where)->order($order)->select();
        } else {
            $where['class_id'] = $classification_id;
            $order['sort'] = 'desc';
            $fidle = 'id,name';
            $list = $VenueModel->field($fidle)->where($where)->order($order)->select();
        }

        return $list;
    }
}