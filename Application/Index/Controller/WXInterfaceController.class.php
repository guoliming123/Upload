<?php
/**
 * Created by PhpStorm.
 * User: zhaibaoming
 * Date: 2017/2/4
 * Time: 20:44
 */

namespace Index\Controller;

use Think\Controller;

class WXInterfaceController extends Controller
{
    public function index()
    {
        $userInfo = $this->webAuthorization();
        if ($userInfo['code'] == 0) {
            $this->error($userInfo['msg']);
        }

        $where = ['openid' => $userInfo['openid']];
        $UsersModel = M('Users');
        $list = $UsersModel->where($where)->find();
        if (empty($list['id'])) {
            $list['id'] = $this->addUserInfo($userInfo);
            if (empty($list['id'])) {
                $this->error('用户添加失败!');
            }
        }

        $this->display();
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
            $url = 'http://' . C('DOMAIN_NAME') . U('WXInterface/index');
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

}