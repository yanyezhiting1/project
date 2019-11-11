<?php

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
class  Activity extends BaseMall {

    public function _initialize() {
        parent::_initialize();
        Lang::load(APP_PATH . 'home/lang/'.config('default_lang').'/activity.lang.php');
    }
    
    /*
     * 显示所有活动列表
     */
    function index()
    {
        $condition = array();
        $activity_model = model('activity');
        $condition['activity_type'] = 1;
        $condition['activity_startdate'] = array('elt',TIMESTAMP);
        $condition['activity_enddate'] = array('egt',TIMESTAMP);
        $condition['activity_state'] = 1;
        
        $activity_list = $activity_model->getActivityList($condition, 10);
        $this->assign('activity_list', $activity_list);
        $this->assign('show_page', $activity_model->page_info->render());
        $this->assign('html_title', config('site_name') . ' - '.lang('activity_list'));
        return $this->fetch($this->template_dir.'activity_index');
    }
    

    /**
     * 单个活动信息页
     */
    public function detail() {
        //得到导航ID
        $nav_id = intval(input('param.nav_id'));
        $this->assign('index_sign', $nav_id);
        //查询活动信息
        $activity_id = intval(input('param.activity_id'));
        if ($activity_id <= 0) {
            $this->error(lang('param_error')); //'缺少参数:活动编号'
        }
        $activity = model('activity')->getOneActivityById($activity_id);
        if (empty($activity) || $activity['activity_type'] != '1' || $activity['activity_state'] != 1 || $activity['activity_startdate'] > time() || $activity['activity_enddate'] < time()) {
            $this->error(lang('activity_index_activity_not_exists')); //'指定活动并不存在'
        }
        $this->assign('activity', $activity);
        //查询活动内容信息
        $condition = array();
        $condition['activitydetail.activity_id'] = $activity_id;
        $activitydetail_list = model('activitydetail')->getActivitydetailAndGoodsList($condition);
        $this->assign('activitydetail_list', $activitydetail_list);
        $this->assign('html_title', config('site_name') . ' - ' . $activity['activity_title']);
        return $this->fetch($this->template_dir.'activity_show');
    }

}

?>
