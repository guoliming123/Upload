<?php
/**
 * Created by PhpStorm.
 * User: zhaibaoming
 * Date: 2017/2/4
 * Time: 17:33
 */

namespace Admin\Model;

use Think\Model;

class ClassificationModel extends Model
{
    private $nameLength = 10; //name长度
    private $sortLength = 99999; //sort长度

    protected $_validate = array(
        ['name', 'require', '分类名称不能为空'],
        ['sort', 'require', '排序不能为空'],
        ['name', 'check_special_string', '分类名称不能包含特殊字符', 1, 'function'],
        ['sort', 'check_int', '排序必须是数字！', 1, 'function'],
        ['name', 'checkNameLen', '分类名称长度不能大于10个字！', 3, 'callback'],
        ['sort', 'checkSortLen', '排序不能大于99999！', 3, 'callback'],

    );

    /**
     * 验证name长度
     *
     * @param $param
     * @author: zhaibaoming
     * @Date: ${DATE}
     * @return bool
     */
    public function checkNameLen($param)
    {
        $len = mb_strlen($param, 'utf-8');

        return $len <= $this->nameLength;
    }

    /**
     * 验证 sort 长度
     *
     * @param $param
     * @author: zhaibaoming
     * @Date: ${DATE}
     * @return bool
     */
    public function checkSortLen($param)
    {

        return $param <= $this->sortLength;
    }
}