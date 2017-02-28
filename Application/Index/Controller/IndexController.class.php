<?php
namespace Index\Controller;

use Think\Controller;

class IndexController extends Controller
{

    public function _initialize()
    {
        $this->assign('imagePath', 'http://' . C('DOMAIN_NAME') . C('IMAGE_PATH'));//图片地址
    }

    public function index()
    {
        //$this->userInfoData();

        $userInfo['id'] = 7;
        $userInfo['openid'] = 'oouCit29zCSxfW5osR8LqxwC3qwU';
        $userInfo['nickname'] = '5号将军';
        $userInfo['headimgurl'] = 0;
        $userInfo['mobile'] = 0;
        $userInfo['is_member'] = 0;

        session('userInfo', $userInfo);

        //读取项目
        $map['state'] = 1;
        $order['sort'] = 'desc';
        $classificationModel = M('Classification');
        $this->classList = $classificationModel->where($map)->order($order)->select();

        //读取场馆
        $where['state'] = 1;
        $sort['sort'] = 'desc';
        $VenueModel = M('Venue');
        $this->venueList = $VenueModel->where($where)->order($sort)->select();

        $this->assign('ajaxUrl', U('Index/ajaxServiceItemsData'));

        $this->display();
    }

    /**
     * 用户授权后  入库 存session
     *
     * @author: zhaibaoming
     * @Date: 2017-02-08
     */
    public function userInfoData()
    {
        $userInfo = $this->webAuthorization();
        if ($userInfo['code'] == 0) {
            die($userInfo['msg']);
        }

        $where = ['openid' => $userInfo['openid']];
        $UsersModel = M('Users');
        $list = $UsersModel->where($where)->find();
        $userInfo['mobile'] = $list['mobile'];
        $userInfo['is_member'] = $list['is_member'];
        if (empty($list['id'])) {
            $list['id'] = $this->addUserInfo($userInfo);
            if (empty($list['id'])) {
                $this->error('用户添加失败!');
            }
            $userInfo['mobile'] = 0;
            $userInfo['is_member'] = 0;
        }
        $userInfo['id'] = $list['id'];
        session('userInfo', $userInfo);
    }

    public function ajaxServiceItemsData()
    {
        if (!IS_AJAX) {
            return;
        }
        $id = I('post.id', 0, 'intval');
        if (empty($id)) {
            return;
        }

        $where['class_id'] = $id;
        $order['sort'] = 'desc';
        $ServiceItemsModel = M('ServiceItems');
        $data = $ServiceItemsModel->where($where)->order($order)->select();
        $this->ajaxReturn($data);
    }

    /**
     * 添加用户信息
     *
     * @param $userInfo
     * @author: zhaibaoming
     * @Date: 2017-02-06
     * @return bool|mixed
     */
    private function addUserInfo($userInfo)
    {
        $UsersModel = M('Users');
        $data = [
            'openid' => $userInfo['openid'],
            'nickname' => $userInfo['nickname'],
            'headimgurl' => $userInfo['headimgurl'],
            'create_time' => time(),
        ];
        $id = $UsersModel->add($data);
        if (empty($id)) {

            return false;
        }

        return $id;
    }

    /**
     * 获取用户信息
     *
     * @author: zhaibaoming
     * @Date: 2017-02-06
     */
    private function webAuthorization()
    {
        if (!$_GET['code']) {
            $url = 'http://' . C('DOMAIN_NAME') . U('Index/index');
            web_authorization($url);
            return;
        }

        $data['code'] = 0;
        $data['msg'] = '';
        $result = get_userinfo();
        $data['headimgurl'] = $result->headimgurl;
        if (empty($result->openid)) {
            $data['msg'] = 'openid错误';
            return $data;
        }
        if (empty($result->nickname)) {
            $data['msg'] = 'nickname错误';
            return $data;
        }
        if (empty($result->headimgurl)) {
            $data['headimgurl'] = 0;
            return $data;
        }
        $data['code'] = 1;
        $data['openid'] = $result->openid;
        $data['nickname'] = $result->nickname;

        return $data;
    }


    public function test()
    {
        $JSSDK = new \Vendor\Jssdk(C('appid'),C('appsecret'));
        $this->jssdkData = $JSSDK->getSignPackage();

        $this->display();
    }
}