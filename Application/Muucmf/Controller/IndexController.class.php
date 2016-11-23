<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Muucmf\Controller;

use Think\Controller;


/**
 * 前台首页控制器
 * 主要获取首页聚合数据
 */
class IndexController extends Controller
{
    protected $muucmfLogModel;
    protected $muucmfDownModel;
    protected function _initialize()
    {
        /*读取站点配置*/
        //$config = api('Config/lists');
        //C($config); //添加配置
        if (!C('WEB_SITE_CLOSE')) {
            $this->error(L('_ERROR_WEBSITE_CLOSED_'));
        }
        $this->muucmfLogModel = D('Muucmf/MuucmfLog'); //系统更新日志模型
        $this->muucmfDownModel = D('Muucmf/MuucmfDown'); //系统更新日志模型
    }
    //系统首页
    public function index()
    {
        hook('muucmfIndex');
        $show_blocks = get_kanban_config('BLOCK', 'enable', array(), 'muucmf');
        //dump($show_blocks);
        $this->assign('showBlocks', $show_blocks);

        $enter = modC('ENTER_URL', '', 'Muucmf');
        $this->assign('enter', get_nav_url($enter));

        $this->display();
    }
    public function downList()
    {
        list($list,$totalCount)=$this->muucmfDownModel->getListByPage($map,$page,$aOrder,'*',$r);

    }

    /**
     * 系统下载
     */
    public function downSys()
    {
        $aid=I('id',0,'intval');
        $result = $this->muucmfDownModel->getData($aid);
        if($result['file']){
            $this->muucmfDownModel->setInc('down_num');//下载数量增加
            $file_id = $result['file'];
            $downFile= M('file')->find($file_id);
            $file_url = $_SERVER['DOCUMENT_ROOT'].$downFile['savepath'].$downFile['savename'];
            if (file_exists($file_url)) {
                $this->_download($file_url, $downFile['savename']);
                return;
            } else {
                $this->error('下载文件不存在，请稍后再试...');
                return;
            }
        }else{
            $this->error('发生错误...');
            return;
        }
    }



    /**
     * 下载
     * @param $get_url
     * @param $file_name
     */
    private function _download($get_url, $file_name)
    {
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename='.'update'.$file_name);
        header('Content-Length:'.filesize($get_url));

        readfile($get_url);
        exit;
    }

}