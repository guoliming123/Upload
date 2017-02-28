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

class VenueController extends AdminController {

    /**
     * 场馆列表
     */
    public function index(){
        if(IS_AJAX){
            $result['status'] = 0;
            $vstatus = I('vstatus');
            if(I('vname')){
                $vname = I('vname');
                $wher['v.name'] = array('LIKE',"%$vname%");
            }
            $wher['v.state'] = $vstatus;
            $list = M('venue')->alias('v')
                ->join('xm_classification c ON  c.id=v.class_id','LEFT')
                ->field('v.*,c.name cname')->where($wher)->order('v.sort asc')->select();
            //场馆下对应的服务
            if($list && !empty($list)){
                foreach ($list as $key=>$value) {
                    $where['vs.venue_id'] = $list[$key]['id'];
                    $result = M('venue_service_items')->alias('vs')
                        ->join('xm_service_items s ON vs.service_items_id=s.id','LEFT')->field('s.name sname')->where($where)->select();
                    if(empty($result) || is_null($result)) $result = 0;
                    $list[$key]['service'] = $result;
                }
            }
            $result['status'] = 1;
            $result['venuelist'] = $list;
            echo json_encode($result);
        }else{
            // 构建列表数据
            //读取数据
            $count = M('venue')->count();
            $Page = new \Think\Page($count, C('PAGE'));
            $show = $Page->show();
            $this->assign('page', $show);
            $list = M('venue')->alias('v')
                ->join('xm_classification c ON  c.id=v.class_id','LEFT')
                ->field('v.*,c.name cname')->order('v.sort asc')->limit($Page->firstRow.','.$Page->listRows)->select();
            //场馆下对应的服务
            if($list && !empty($list)){
                foreach ($list as $key=>$value) {
                    $where['vs.venue_id'] = $list[$key]['id'];
                    $result = M('venue_service_items')->alias('vs')
                        ->join('xm_service_items s ON vs.service_items_id=s.id','LEFT')->field('s.name sname')->where($where)->select();
                    if(empty($result) || is_null($result)) $result = 0;
                    $list[$key]['service'] = $result;
                }
            }
            //城市列表

            $this->assign('list',$list);
            $this->display('index');
        }
    }

    /*
     * 场馆详细信息
     * */
    public function info(){
        $id = I('get.id',0,'intval');
        //查询场馆信息
        $where['v.id'] = $id;
        $data = M('venue')->alias('v')
            ->join('xm_classification c  ON v.class_id=c.id','LEFT')
            ->join('xm_region r   ON v.province=r.id','LEFT')
            ->join('xm_region re  ON v.city=re.id','LEFT')
            ->join('xm_region reg ON v.area=reg.id','LEFT')
            ->Field('v.*,r.name provincename,re.name cityname,reg.name areaname,c.name cname')->where($where)->find();
        if($data['images']) $data['venimages'] = explode(',',rtrim($data['images'],','));

        //查询城市
        $addresslist = $this->getProvince();

        //服务子类
        $clarray = M('venue_service_items')->alias('vs')
            ->join('xm_service_items s  ON s.id=vs.service_items_id','LEFT')
            ->where("vs.venue_id='$id'")->Field('vs.service_items_id,s.name sname')->select();

        //服务人群
        $crarray = M('venue_crowd')->alias('vc')
            ->join('xm_crowd c  ON vc.crowd_id=c.id','LEFT')
            ->where("vc.venue_id='$id'")->Field('vc.crowd_id,c.name crname')->select();

        //查询场馆的排期date(”Y-m-d H:i:s”,time())
        $date['venue_id']=$id;
//        $date['start_time']=array('lt',time());   //过期排期不显示
        foreach ($clarray as $key=>$val){
            $date['venue_service_items_id']=$clarray[$key]['service_items_id'];
            $schedulelist[]['schedule'] = M('schedule')->where($date)->field('id,people_number,price,member_price,start_time')->order('start_time asc')->select();
        }
//        echo '<pre>';
//        var_dump($schedulelist);
        $this->assign('clarray',$clarray);
        $this->assign('crarray',$crarray);
        $this->assign('addresslist',$addresslist);
        $this->assign('schedulelist',$schedulelist);
        $this->assign('venuedetail',$data);
        $this->display('info');
    }

    /**
     * 添加、编辑场馆
     */
    public function editVenue(){
        if(IS_POST){
            $result['status'] = 0;
            //准备数据
            $venueid = I('venueid',0,'intval');   //场馆id
            //如有场馆id 则为修改场馆
            $data['name']=I('venuename');
            $data['province']=I('province');
            $data['city']=I('city');
            $data['area']=I('area');
            $data['state']=I('isopen');
            $data['class_id']=I('topclass');
            $data['telephone']=I('vtelephone');
            $data['sort']=I('vsort');
            $data['address']=I('address');
            $data['single_image']=I('venuepicpic');   //场馆单图片
            $data['images']=I('venuepicpics');   //场馆多张图片
            $data['create_time']=time();
            if($venueid){
                $where['id'] = $venueid;
                $res = M('venue')->where($where)->save($data);  //根据条件更新记录
                $result['status'] = 1;
                $result['message']='编辑成功！';
                if($res){
                    //删除venue_service_items，venue_crowd表原来数据
                    $whe['venue_id'] = $venueid;
                    M('venue_service_items')->where($whe)->delete();
                    M('venue_crowd')->where($whe)->delete();
                }
            }else{
                $res = M('venue')->data($data)->add();
                $venueid = $res;
                $result['status'] = 1;
                $result['message']='添加成功！';
            }

            //更新xm_venue_service_items表
            $cclass = explode(',',I('cclass')); //选中子类
            foreach ($cclass as $key=>$val){
                $date['venue_id'] = $venueid;
                $date['service_items_id'] = $val;
                $date['people_number'] = 20;  //人数可变
                $date['activity_time'] = time();
                M('venue_service_items')->data($date)->add();
            }
            //更新xm_venue_crowd表
            $crowd = explode(',',I('crowd')); //适合人群
            foreach ($crowd as $k=>$v){
                $data['crowd_id'] = $v;
                $data['venue_id'] = $venueid;
                M('venue_crowd')->data($data)->add();
            }
            echo json_encode($result);
        }else{
            $id = I('id',0,'intval');
            if($id){
                $date['v.id'] = $id;
                $venue = M('venue')->alias('v')
                    ->join('xm_region r   ON v.province=r.id','LEFT')
                    ->join('xm_region re  ON v.city=re.id','LEFT')
                    ->join('xm_region reg ON v.area=reg.id','LEFT')
                    ->Field('v.*,r.name provincename,re.name cityname,reg.name areaname')->where($date)->find();
                if($venue['images']) $venue['venimages'] = explode(',',rtrim($venue['images'],','));
                $this->assign('venue',$venue);
            }
            //查询城市
            $provincelist = $this->getProvince();
            //查询大分类
            $classlist = $this->getClass();
            //选中默认子类
            $wher['venue_id'] =$id;
            $clarray = M('venue_service_items')->where($wher)->getField('service_items_id',true);

            if($classlist){
                if($id){
                    //编辑场馆
                    $classid=M('venue')->where("id='$id'")->getField('class_id');  //场馆选中的大分类id
                    $where['class_id'] = $classid?$classid:$classlist[0]['id'];
                }else{
                    $where['class_id'] = $classlist[0]['id'];
                }
                $where['state'] = 1;
                $classchild = M('service_items')->where($where)->select();
                $this->assign('classchild',$classchild);
            }
            //查询适合人群
            $crowlist = $this->getCrow();
            $crarray = M('venue_crowd')->where($wher)->getField('crowd_id',true);

            $this->assign('crarray',$crarray);
            $this->assign('crowlist',$crowlist);
            $this->assign('clarray',$clarray);
            $this->assign('classlist',$classlist);
            $this->assign('provincelist',$provincelist);
            $this->assign('venueid',$id);
            $this->display('venue');
        }
    }
    /*
     * 删除场馆
     * */
    public function delvenue(){
        $result['status'] = 0;
        $venueid = I('venueid');  //场馆id
        $where['id'] = $venueid;
        $date = M('venue')->where($where)->delete();
        if($date){
            $result['status'] = 1;
        }
        echo json_encode($result);
    }

    /*
     * 获取子类
     * */
    public function getClasslist(){
        $result['status'] = 0;
        $classid = I('classid');   //大分类id
        $where['class_id'] =$classid;
        $where['state'] =1;
        $data = M('service_items')->where($where)->order('sort asc')->select();
        if($data){
            $result['cclass'] =$data?$data:0;
        }
        echo json_encode($result);
    }

    /*
     * 获取城市
     * */
    public function getCity(){
        $result['status'] = 0;
        $pid = I('provinceid',0,'intval');
        $where['fid'] = $pid;
        $citylist = M('region')->where($where)->select();
        if($citylist){
            $result['status'] = 1;
            $result['citylist'] = $citylist;
        }
        echo json_encode($result);
    }
    /*
     * 获取地区
     * */
    public function getArea(){
        $result['status'] = 0;
        $cid = I('cityid',0,'intval');
        $where['fid'] = $cid;
        $arealist = M('region')->where($where)->select();
        if($arealist){
            $result['status'] = 1;
            $result['arealist'] = $arealist;
        }
        echo json_encode($result);
    }
    /*
     * 获取子服务项目
     * */
    public function getItems(){
        $result['status'] = 0;
        $venueid = I('venueid',0);
        $itemslist = M('venue')->alias('v')
            ->join('xm_venue_service_items vs ON vs.venue_id=v.id','LEFT')
            ->join('xm_service_items s ON vs.service_items_id=s.id','LEFT')
            ->Field('s.id,s.name sname')->where("v.id='$venueid'")->select();
        if($itemslist){
            $result['status'] = 1;
            $result['itemslist'] = $itemslist;
        }
        echo json_encode($result);
    }
    /*
     * 增加排期
     * */
    public function addSchedule(){
        if(IS_POST){
            $result['status'] = 0;
            //准备数据
            $data['venue_id']=I('venueadd');
            $data['venue_service_items_id']=I('itemsadd');
            $data['price']=I('price');
            $data['member_price']=I('memberprice');
            $data['people_number']=I('peplenum');
            $data['start_time']=strtotime(I('timechose'));
            $data['time_length']=I('timelength');
            $data['create_time']=time();
            $res = M('schedule')->data($data)->add();
            if($res){
                $result['status'] = 1;
            }
            echo M('schedule')->_sql();
        }else{
            $id=I('id',0,'intval');
            $where['state'] = 1;
            $where['id'] = $id;
            $venue = M('venue')->Field('id,name vname')->where($where)->find();
            //场馆列表
            $venuelist = M('venue')->Field('id,name vname')->where('state=1')->order('sort asc')->select();
            //服务项目根据场馆变
            if($id){
                //服务项目列表
                //$itemslist = M('service_items')->Field('id,name sname')->where('state=1')->order('sort asc')->select();
                $itemslist = M('venue')->alias('v')
                    ->join('xm_venue_service_items vs ON vs.venue_id=v.id','LEFT')
                    ->join('xm_service_items s ON vs.service_items_id=s.id','LEFT')
                    ->Field('s.id,s.name sname')->where("v.id='$id'")->select();
            }
            $this->assign('venue',$venue);
            $this->assign('venueid',$id);
            $this->assign('venuelist',$venuelist);
            $this->assign('itemslist',$itemslist);
            $this->display('addschedule');
        }
    }

    /*
     * 删除排期
     * */
    public function delschedule(){
        $result['status'] = 0;
        $id = I('scheid',0); //排期id
        $res = M('schedule')->where("id='$id'")->delete();
        if($res){
            $result['status'] = 1;
        }
        echo json_encode($result);
    }

    //图片上传
    public function uploadify() {
        if (!empty($_FILES)) {
            //图片上传设置
            $config = array(
                'maxSize'    =>    3145728,
                'savePath'   =>    '',
                'saveName'   =>    array('uniqid',''),
                'exts'       =>    array('jpg', 'gif', 'png', 'jpeg'),
                'autoSub'    =>    true,
                'subName'    =>    array('date','Ymd'),
            );
            $upload = new \Think\Upload($config);// 实例化上传类
            $info = $upload->upload();
            if(!$info) {
                //上传错误提示信息
                $this->error($upload->getError());
            } else {
                foreach($info as $file){
                    echo $file['savepath'].$file['savename'];
                }
            }
        }
    }
}