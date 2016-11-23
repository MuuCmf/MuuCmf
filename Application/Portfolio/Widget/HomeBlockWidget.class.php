<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * @author 大蒙<59262424@qq.com>
 */

namespace Portfolio\Widget;


use Think\Controller;

class HomeBlockWidget extends Controller{
    public function render()
    {
        $this->assignPortfoilo();
        $this->display(T('Application://Portfolio@Widget/homeblock'));
    }

    private function assignPortfoilo()
    {
        $num = modC('PORTFOLIO_SHOW_COUNT', 4, 'Portfolio');
        $type= modC('PORTFOLIO_SHOW_TYPE', 0, 'Portfolio');
        $field = modC('PORTFOLIO_SHOW_ORDER_FIELD', 'view', 'Portfolio');
        $order = modC('PORTFOLIO_SHOW_ORDER_TYPE', 'desc', 'Portfolio');
        $cache = modC('PORTFOLIO_SHOW_CACHE_TIME', 600, 'Portfolio');
        $list = S('portfolio_home_data');
        if (!$list) {
            if($type){
                /**
                 * 获取推荐位数据列表
                 * @param  number  $pos      推荐位 1-系统首页，2-推荐阅读，4-本类推荐
                 * @param  number  $category 分类ID
                 * @param  number  $limit    列表行数
                 * @param  boolean $filed    查询字段
                 * @param order 排序
                 * @return array             数据列表
                 */
                $list=D('Portfolio/Portfolio')->position(1,null,$num,true,$field . ' ' . $order);
            }else{
                $map = array('status' => 1);
                $list = D('Portfolio/Portfolio')->getList($map,$field . ' ' . $order,$num);
            }
            foreach ($list as &$v) {
                $val['user']=query_user(array('space_url','nickname'),$v['uid']);
            }
            unset($v);
            if(!$list){
                $list=1;
            }
            S('portfolio_home_data', $list, $cache);
        }
        unset($v);
        if($list==1){
            $list=null;
        }
        $this->assign('portfolio_lists', $list);
    }
} 