<?php
/**
 * 云端应用商店
 */

namespace Admin\Controller;

use Admin\Builder\AdminListBuilder;
use Admin\Builder\AdminSortBuilder;
use Admin\Builder\AdminConfigBuilder;


class AppcloudController extends AdminController
{

    public function _initialize()
    {
        parent::_initialize();
    }

    public function index(){
        $this->display();
    }

} 