<?php
/**
 * 所属项目 OpenSNS
 * 开发者: 陈一枭
 * 创建日期: 2014-12-01
 * 创建时间: 15:55
 */

namespace Forum\Model;


use Think\Model;

class ForumTypeModel extends Model
{
    protected $tableName = 'forum_type';

    /**获得分类树
     * @param int  $id
     * @param bool $field
     * @return array
     * @auth 陈一枭
     */
    public function getTree($id = 0, $field = true)
    {
        /* 获取当前分类信息 */
        if ($id) {
            $info = $this->info($id);
            $id = $info['id'];
        }

        /* 获取所有分类 */
        $map = array('status' => array('gt', -1));
        $list = $this->field($field)->where($map)->order('sort asc')->select();
        $list = list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_', $root = $id);


        /* 获取返回数据 */
        if (isset($info)) { //指定分类则返回当前分类极其子分类
            $info['_'] = $list;
        } else { //否则返回所有分类
            $info = $list;
        }

        return $info;
    }

    /**
     * 获取分类详细信息
     * @param  milit   $id 分类ID或标识
     * @param  boolean $field 查询字段
     * @return array     分类信息
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function info($id, $field = true)
    {
        /* 获取分类信息 */
        $map = array();
        $map['id'] = $id;

        return $this->field($field)->where($map)->find();
    }

} 