<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * @author 大蒙<59262424@qq.com>
 */

namespace Portfolio\Widget;

use Portfolio\Model\PortfolioModel;
use Think\Action;

class UcenterBlockWidget extends Action
{
    public function render($uid = 0, $page = 1, $tab = null, $count = 10)
    {
        !$uid && $uid = is_login();
        //查询条件
        $map['uid']=$uid;

        $PortfolioModel = new PortfolioModel();
        /* 获取当前分类下资讯列表 */
        if($uid!=is_login()){
            $map['status']=1;
        }
        list($list,$totalCount) = $PortfolioModel->getListByPage($map,$page,'update_time desc','*',$count);
        foreach($list as &$val){
            if($val['status']==1){
                $val['audit_status']='<span style="color: green;">审核通过</span>';
            }elseif($val['status']==2){
                $val['audit_status']='<span style="color:#4D9EFF;">待审核</span>';
            }elseif($val['status']==-1){
                $val['audit_status']='<span style="color: #b5b5b5;">审核失败</span>';
            }
        }
        unset($val);
        /* 模板赋值并渲染模板 */
        $this->assign('portfolio_list', $list);
        $this->assign('totalCount',$totalCount);

        $this->display(T('Portfolio@Widget/ucenterblock'));
    }
} 