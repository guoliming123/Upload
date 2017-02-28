<?php
/**
 * 项目分类
 * User: zhaibaoming
 * Date: 2017/2/4
 * Time: 11:09
 */

namespace Admin\Controller;

use User\Api\UserApi;

class ProjectController extends AdminController
{
    private $imagePath = '/Uploads/Project/';

    /**
     * 主页面
     *
     * @author: zhaibaoming
     * @Date: 2017/2/4
     */
    public function index()
    {
        $ClassificationModel = D('Classification');
        if (IS_GET) {
            //读取数据
            $count = $ClassificationModel->count();
            $Page = new \Think\Page($count, C('PAGE'));
            $show = $Page->show();
            $this->assign('page', $show);
            $this->list = $ClassificationModel->order('sort desc')->limit($Page->firstRow.','.$Page->listRows)->select();

            $this->display();

            return;
        }

        $data['name'] = I('post.name');
        $data['sort'] = I('post.sort', 0, 'intval');
        $data['create_time'] = time();

        if (!$ClassificationModel->create()) {
            $this->error($ClassificationModel->getError());
        }

        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 31457280;// 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->savePath = $this->imagePath; // 设置附件上传目录
        $info = $upload->uploadOne($_FILES['images']);
        if (!$info) {
            $this->error($upload->getError());
        } else {
            $data['images'] = $info['savepath'] . $info['savename'];
        }

        $result = $ClassificationModel->add($data);

        if (!$result) {
            $this->error('添加失败!');
            return;
        }

        $this->success('添加成功!');
    }

    /**
     * 删除
     *
     * @author: zhaibaoming
     * @Date: 2017/2/4
     */
    public function delete()
    {
        $map['id'] = I('get.id', 0, 'intval');
        if ($map['id'] < 1) {
            $this->error('没有参数!');
        }
        $ClassificationModel = M('Classification');
        $result = $ClassificationModel->where($map)->delete();

        if (!$result) {
            $this->error('删除失败!');
            return;
        }
        $this->success('删除成功!');
    }

    /**
     * 编辑隐藏显示状态
     *
     * @author: zhaibaoming
     * @Date: 2017/2/4
     */
    public function editState()
    {
        $map['id'] = I('get.id', 0, 'intval');
        $state = I('get.state', 0, 'intval');
        if ($map['id'] < 1) {
            $this->error('没有参数!');
        }
        if ($state !== 1 && $state !== 0) {
            $this->error('参数错误!');
        }

        $updateWhere['state'] = $state ? 0 : 1;
        $ClassificationModel = M('Classification');
        $result = $ClassificationModel->where($map)->save($updateWhere);
        if ($result === false) {
            $this->error('编辑失败!');
            return;
        }
        $this->success('编辑成功!');
    }


}