<?php
namespace Common\Widget;

use Think\Controller;

class SimditorWidget extends Controller
{

    public function editor($id = 'myeditor', $name = 'content',$default='',$width='100%',$height='200px',$config='',$style='',$param='')
    {
        $this->assign('id',$id);
        $this->assign('name',$name);
        $this->assign('default',$default);
        $this->assign('width',$width);
        $this->assign('height',$height);
        $this->assign('style',$style);

        $this->assign('config',$config);
        $this->assign('param',$param);
        //cookie('video_get_info',U('Home/Public/getVideo'));

        $this->display(T('Application://Common@Widget/wangeditor'));
    }

}
