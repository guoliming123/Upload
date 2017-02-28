<?php
/**
 * Created by PhpStorm.
 * User: zhaibaoming
 * Date: 2017/2/4
 * Time: 17:00
 */

namespace Admin\Controller;


class ServiceItemsController extends AdminController
{

    /**
     * 添加服务子类
     *
     * @author: zhaibaoming
     * @Date: 2017/2/4
     */
    public function addServiceItems()
    {
        $ServiceItems = D('ServiceItems');

        if (IS_GET) {
            //读取分类数据
            $ClassificationModel = M('Classification');
            $this->list = $ClassificationModel->order('sort desc')->select();

            //读取服务项目数据
            $ServiceItemsData = D('ServiceItems');
            //$count = $ServiceItemsData->count();
            // $Page = new \Think\Page($count, C('PAGE'));
            //$show = $Page->show();
            $this->itemList = $ServiceItemsData->dataList();
            //$this->assign('page', $show);

            $this->display();
            return;
        }


        $data['name'] = I('post.name');
        $data['sort'] = I('post.sort', 0, 'intval');
        $data['class_id'] = I('post.class_id', 0, 'intval');
        $data['create_time'] = time();

        if (!$ServiceItems->create()) {
            $this->error($ServiceItems->getError());
        }

        $result = $ServiceItems->add($data);
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
        $ServiceItemsModel = M('ServiceItems');
        $result = $ServiceItemsModel->where($map)->delete();

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
        $ServiceItemsModel = M('ServiceItems');
        $result = $ServiceItemsModel->where($map)->save($updateWhere);
        if ($result === false) {
            $this->error('编辑失败!');
            return;
        }
        $this->success('编辑成功!');
    }
}