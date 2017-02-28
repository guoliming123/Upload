<?php
/**
 * 手机注册
 * User: zhaibaoming
 * Date: 2017/2/8
 * Time: 13:22
 */

namespace Index\Controller;

use Think\Controller;

class MobileRegistrationController extends Controller
{
    public function index()
    {
        if (IS_GET) {
            $data['userId'] = I('get.userId', 0, 'intval');
            $data['schedule_id'] = I('get.schedule_id', 0, 'intval');

            if ($data['userId'] < 1) {
                $this->error('参数错误-1');
            }
            if ($data['schedule_id'] < 1) {
                $this->error('参数错误-2');
            }

            $this->assign('callbackUrl', $data['callbackUrl']);
            $this->assign('userId', $data['userId']);
            $this->assign('schedule_id', $data['schedule_id']);
            $this->display();
            return;
        } elseif (IS_POST) {
            $data['userId'] = I('post.userId', 0, 'intval');
            $data['mobile'] = I('post.mobile');
            $data['schedule_id'] = I('post.schedule_id', 0, 'intval');

            if ($data['userId'] < 1) {
                $this->error('参数错误-1');
            }
            if ($data['schedule_id'] < 1) {
                $this->error('参数错误-2');
            }

            if (!is_tel($data['mobile'], 'mobile')) {
                $this->error('手机号错误');
            }

            $UsersModel = M('Users');
            $result = $UsersModel->where('id=' . $data['userId'])->setField('mobile', $data['mobile']);
            if ($result === false) {
                $this->error('绑定错误');
            }
            $_SESSION['userInfo']['mobile'] =$data['mobile'];
            $this->success('绑定成功', U('Order/index',['schedule_id'=>$data['schedule_id']]));
        } else {
            return;
        }
    }
}