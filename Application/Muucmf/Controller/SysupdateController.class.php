<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Muucmf\Controller;

use Think\Controller\RestController;

class SysupdateController extends RestController
{
    protected $sysupdateModel;

    function _initialize()
    {
        $this->sysupdateModel = D('Muucmf/sysupdate');
    }
    //系统升级云端首页
    public function index()
    {
        $version = I('enable_version','','op_t');
        /* 获取当前分类下列表 */
        $info = $this->sysupdateModel->getVersionData($version);
        if($info){
            $result['status'] = 1;
            $result['info'] = '返回成功';
            $result['data'] = $info;
        }else{
            $result['status'] = 0;
            $result['info'] = '返回错误';
        }
        $this->response($result,'json');
    }
    /*
    *最新版本返回
    */
    public function newVersion()
    {
        /* 获取当前分类下列表 */
        $info = $this->sysupdateModel->field('id,version')->limit(1)->select();
        if($info){
            $result['status'] = 1;
            $result['info'] = '返回成功';
            $result['data'] = $info;
        }else{
            $result['status'] = 0;
            $result['info'] = '返回错误';
        }

        $info = array_column($info, 'version');
        //dump($info);
        echo $info[0];
    }

    /*
    *下载并记录下载数量
    */
    public function downSysUpdate()
    {
        $aid=I('id',0,'intval');

        $result = $this->sysupdateModel->getData($aid);
        $file_id = $result['download_file'];
        $downFile= M('file')->find($file_id);
        $file_url = $_SERVER['DOCUMENT_ROOT'].$downFile['savepath'].$downFile['savename'];
        //if($result){
        //    redirect($file_url);
        //}
        //dump($file_url);exit;
        if ($file_url) {
                $this->_download($file_url, $downFile['savename']);
                return;
            } else {
                $this->error('发生错误！');
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
        ob_end_clean();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename='.'update'.$file_name);
        header('Content-Length:'.filesize($get_url));

        ob_clean();  
        flush(); 

        readfile($get_url);
        exit;
    }

}