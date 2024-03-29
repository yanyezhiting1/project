<?php

namespace app\admin\controller;

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
class  Seo extends AdminControl {

    public function _initialize() {
        parent::_initialize();
        Lang::load(APP_PATH . 'admin/lang/'.config('default_lang').'/seo.lang.php');
    }

    function index() {
        $seo_model = model('seo');
        if (!request()->isPost()) {
            //读取SEO信息
            $list = $seo_model->select();
            $seo = array();
            foreach ((array) $list as $value) {
                $seo[$value['seo_type']] = $value;
            }
            $this->assign('seo', $seo);
            return $this->fetch('index');
        } else {
            $seo = input('post.SEO/a');#获取数组
            if (is_array($seo)) {
                foreach ($seo as $key => $value) {
                    $seo_model->where(array('seo_type' => $key))->update($value);
                }
                dkcache('seo');
                ds_json_encode(10000, lang('ds_common_save_succ'));
            }
        }
    }

}

?>
