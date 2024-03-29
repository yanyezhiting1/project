<?php

/**
 * 系统文章
 */

namespace app\home\controller;

use think\Lang;
/**
 * ============================================================================
 * DSMall多用户商城
 * ============================================================================
 * 版权所有 2014-2028 长沙德尚网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.csdeshang.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * 控制器
 */
class  Document extends BaseMall {

    public function _initialize() {
        parent::_initialize();
        Lang::load(APP_PATH . 'home/lang/'.config('default_lang').'/index.lang.php');
    }

    public function index() {
        $code = input('param.code');
        
        if ($code == '') {
            $this->error(lang('param_error'));//'缺少参数:文章标识'
        }
        $document_model = model('document');
        $doc = $document_model->getOneDocumentByCode($code);
        $this->assign('doc', $doc);
        /**
         * 分类导航
         */
        $nav_link = array(
            array(
                'title' => lang('ds_index'),
                'link' => HOME_SITE_URL
            ),
            array(
                'title' => $doc['document_title']
            )
        );
        $this->assign('nav_link_list', $nav_link);
        return $this->fetch($this->template_dir . 'index');
    }

}

?>
