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
class  Activity extends AdminControl {

    public function _initialize() {
        parent::_initialize(); // TODO: Change the autogenerated stub
        Lang::load(APP_PATH . 'admin/lang/' . config('default_lang') . '/activity.lang.php');
    }

    /**
     * 活动列表/删除活动
     */
    public function index() {
        $activity_model = model('activity');
        //条件
        $condition = array();
        $condition['activity_type'] = '1'; //只显示商品活动
        //状态
        if ((input('param.searchstate'))) {
            $state = intval(input('param.searchstate')) - 1;
            $condition['activity_state'] = "$state";
        }
        //标题
        if ((input('param.searchtitle'))) {
            $condition['activity_title'] = array('like', "%" . input('param.searchtitle') . "%");
        }
        //有效期范围
        if ((input('param.searchstartdate')) && (input('param.searchenddate'))) {
            $startdate = strtotime(input('param.searchstartdate'));
            $enddate = strtotime(input('param.searchenddate'));
            if ($enddate > 0) {
                $enddate += 86400;
            }
            $condition['activity_enddate'] = array('egt', $startdate);
            $condition['activity_startdate'] = array('elt', $enddate);
        }
        //活动列表
        $activity_list = $activity_model->getActivityList($condition, 10, 'activity_sort asc');
        //输出
        $this->assign('show_page', $activity_model->page_info->render());
        $this->assign('activity_list', $activity_list);
        $this->assign('filtered', $condition ? 1 : 0); //是否有查询条件
        $this->setAdminCurItem('index');
        return $this->fetch();
    }

    /**
     * 新建活动/保存新建活动
     */
    public function add() {
        if (request()->isPost()) {
            //提交表单
            $data = [
                'activity_title' => input('post.activity_title'),
                'activity_startdate' => strtotime(input('post.activity_startdate')),
                'activity_enddate' => strtotime(input('post.activity_enddate')),
                'activity_style' => input('post.activity_style'),
                'activity_type' => input('post.activity_type'),
                'activity_banner' => $_FILES['activity_banner']['name'],
                'activity_sort' => intval(input('post.activity_sort'))
            ];
            $activity_validate = validate('activity');
            if (!$activity_validate->scene('add')->check($data)) {
                $this->error($activity_validate->getError());
            }

            $file_name = '';
            if (!empty($_FILES['activity_banner']['name'])) {
                $upload_file = BASE_UPLOAD_PATH . DS . ATTACH_ACTIVITY;
                $file = request()->file('activity_banner');
                $info = $file->rule('uniqid')->validate(['ext' => ALLOW_IMG_EXT])->move($upload_file);
                if ($info) {
                    $file_name = $info->getFilename();
                } else {
                    $this->error($file->getError());
                }
            }

            $data['activity_banner'] = $file_name;
            $data['activity_desc'] = trim(input('post.activity_desc'));
            $data['activity_state'] = intval(input('post.activity_state'));
            $activity_model = model('activity');
            $result = $activity_model->addActivity($data);
            if ($result) {
                $this->log(lang('ds_add') . lang('activity_index') . '[' . input('post.activity_title') . ']', null);
                dsLayerOpenSuccess(lang('ds_common_op_succ'));
            } else {
                //添加失败则删除刚刚上传的图片,节省空间资源
                @unlink($upload_file . DS . $file_name);
                $this->error(lang('ds_common_op_fail'));
            }
        } else {
            $activity = array(
                'activity_type' => '1',
                'activity_startdate' => TIMESTAMP,
                'activity_enddate' => TIMESTAMP,
                'activity_banner' => '',
                'activity_style' => '',
                'activity_desc' => '',
                'activity_state' => '1',
            );
            $this->assign('activity', $activity);
            return $this->fetch('form');
        }
    }

    /**
     * 异步修改
     */
    public function ajax() {
        if (in_array(input('param.branch'), array('activity_title', 'activity_sort'))) {
            $activity_model = model('activity');
            $update_array = array();
            switch (input('param.branch')) {
                /**
                 * 活动主题
                 */
                case 'activity_title':
                    if (trim(input('param.value')) == '')
                        exit;
                    break;
                /**
                 * 排序
                 */
                case 'activity_sort':
                    if (preg_match('/^\d+$/', trim(input('param.value'))) <= 0 or intval(trim(input('param.value'))) < 0 or intval(trim(input('param.value'))) > 255)
                        exit;
                    break;
                default:
                    exit;
            }
            $update_array[input('param.column')] = trim(input('param.value'));
            if ($activity_model->editActivity($update_array, intval(input('param.id'))))
                echo 'true';
        }elseif (in_array(input('param.branch'), array('activitydetail_sort'))) {
            $activitydetail_model = model('activitydetail');
            $update_array = array();
            switch (input('param.branch')) {
                /**
                 * 排序
                 */
                case 'activitydetail_sort':
                    if (preg_match('/^\d+$/', trim(input('param.value'))) <= 0 or intval(trim(input('param.value'))) < 0 or intval(trim(input('param.value'))) > 255)
                        exit;
                    break;
                default:
                    exit;
            }
            $update_array[input('param.column')] = trim(input('param.value'));
            if ($activitydetail_model->editActivitydetail($update_array, array('activitydetail_id' => intval(input('param.id')))))
                echo 'true';
        }
    }

    /**
     * 删除活动
     */
    public function del() {
        $id = intval(input('param.activity_id'));
        if ($id <= 0) {
            ds_json_encode(10001, lang('param_error'));
        }

        $activity_model = model('activity');
        $activitydetail_model = model('activitydetail');
        //获取可以删除的数据
        $condition_arr = array();
        $condition_arr['activity_state'] = '0'; //已关闭
        $condition_arr['activity_enddate'] = array('lt', TIMESTAMP); //过期
        $condition_arr['activity_id'] = $id;
        $activity_list = $activity_model->getActivityList($condition_arr);
        if (empty($activity_list)) {//没有符合条件的活动信息直接返回成功信息
            ds_json_encode(10001, lang('activity_index_help3'));
        }
        $id_arr = array();
        foreach ($activity_list as $v) {
            $id_arr[] = $v['activity_id'];
        }
        //只有关闭或者过期的活动，能删除
        if ($activitydetail_model->getActivitydetailList(array('activity_id', array('in', $id_arr)))) {
            if (!$activitydetail_model->delActivitydetail(array('activity_id', array('in', $id_arr)))) {
                ds_json_encode(10001, lang('activity_del_fail'));
            }
        }
        try {
            //删除数据先删除横幅图片，节省空间资源
            foreach ($id_arr as $v) {
                $this->delBanner(intval($v));
            }
        } catch (Exception $e) {
            ds_json_encode(10001, $e->getMessage());
        }
        if ($activity_model->delActivity(array('activity_id', array('in', $id_arr)))) {
            $this->log(lang('ds_del') . lang('activity_index') . '[ID:' . $id . ']', null);
            ds_json_encode(10000, lang('ds_common_del_succ'));
        }
        ds_json_encode(10001, lang('activity_del_fail'));
    }

    /**
     * 编辑活动/保存编辑活动
     */
    public function edit() {
        $activity_id = intval(input('param.activity_id'));
        if ($activity_id <= 0) {
            $this->error(lang('miss_argument'));
        }
        $activity_model = model('activity');
        $activity = $activity_model->getOneActivityById($activity_id);
        if (!request()->isPost()) {
            $this->assign('activity', $activity);
            return $this->fetch('form');
        } else {
            //提交表单
            $data = [
                'activity_title' => input('post.activity_title'),
                'activity_startdate' => strtotime(input('post.activity_startdate')),
                'activity_enddate' => strtotime(input('post.activity_enddate')),
                'activity_style' => input('post.activity_style'),
                'activity_type' => input('post.activity_type'),
                'activity_sort' => intval(input('post.activity_sort'))
            ];
            $activity_validate = validate('activity');
            if (!$activity_validate->scene('edit')->check($data)) {
                $this->error($activity_validate->getError());
            }
            //构造更新内容
            if ($_FILES['activity_banner']['name'] != '') {
                $upload_file = BASE_UPLOAD_PATH . DS . ATTACH_ACTIVITY;
                $file = request()->file('activity_banner');
                $info = $file->rule('uniqid')->validate(['ext' => ALLOW_IMG_EXT])->move($upload_file);
                if ($info) {
                    $file_name = $info->getFilename();
                    $data['activity_banner'] = $file_name;
                }
            }
            $data['activity_desc'] = trim(input('post.activity_desc'));
            $data['activity_state'] = intval(input('post.activity_state'));

            $activity_model = model('activity');
            $result = $activity_model->editActivity($data, $activity_id);
            if ($result) {
                @unlink($upload_file . DS .$activity['activity_banner']);
                $this->log(lang('ds_edit') . lang('activity_index') . '[ID:' . $activity_id . ']', null);
                dsLayerOpenSuccess(lang('ds_common_save_succ'));
            } else {
                if ($_FILES['activity_banner']['name'] != '') {
                    @unlink($upload_file . DS . $file_name);
                }
                $this->error(lang('ds_common_save_fail'));
            }
        }
    }

    /**
     * 删除活动内容
     */
    public function del_detail() {
        $activitydetail_id = input('param.activitydetail_id');
        $activitydetail_id_array = ds_delete_param($activitydetail_id);
        if ($activitydetail_id_array == FALSE) {
            ds_json_encode('10001', lang('param_error'));
        }

        $activitydetail_model = model('activitydetail');
        //条件
        $condition_arr = array();
        $condition_arr['activitydetail_id'] = array('in', $activitydetail_id_array);
        if ($activitydetail_model->delActivitydetail($condition_arr)) {
            $this->log(lang('ds_del') . lang('activity_index_content') . '[ID:' . implode(',', $activitydetail_id_array) . ']', null);
            ds_json_encode(10000, lang('ds_common_del_succ'));
        } else {
            ds_json_encode(10001, lang('ds_common_del_fail'));
        }
    }

    /**
     * 根据活动编号删除横幅图片
     *
     * @param int $id
     */
    private function delBanner($id) {
        $activity_model = model('activity');
        $row = $activity_model->getOneActivityById($id);
        //删除图片文件
        @unlink(BASE_UPLOAD_PATH . DS . ATTACH_ACTIVITY . DS . $row['activity_banner']);
    }

    /**
     * 参与活动
     */
    public function activity_apply() {

        if (!request()->isPost()) {

            //根据活动编号查询活动信息
            $activity_id = intval(input('param.activity_id'));
            if ($activity_id <= 0) {
                $this->error(lang('param_error'), 'Activity/index');
            }
            $activity_model = model('activity');
            $activity_info = $activity_model->getOneActivityById($activity_id);
            //活动类型必须是商品并且活动没有关闭并且活动进行中
            if (empty($activity_info) || $activity_info['activity_type'] != '1' || $activity_info['activity_state'] != 1) {
                $this->error(lang('activity_not_exists'), 'Activity/index');
            }
            if ($activity_info['activity_startdate'] > time() || $activity_info['activity_enddate'] < time()){
                $this->error(lang('activity_time_end'), 'Activity/index');
            }
            $this->assign('activity', $activity_info);
            //查询品牌列表
            $brand = model('brand');
            $brand_list = $brand->getBrandList(array());
            $this->assign('brand_list', $brand_list);
            //查询活动细节信息
            $activitydetail_model = model('activitydetail');

            $condition = array();
            $condition['activitydetail.activity_id'] = $activity_id;
            $activitydetail_list = $activitydetail_model->getGoodsJoinList($condition);
            //构造通过与审核中商品的编号数组,以便在下方待选列表中,不显示这些内容
            $item_ids = array();
            if (is_array($activitydetail_list) and ! empty($activitydetail_list)) {
                foreach ($activitydetail_list as $k => $v) {
                    $item_ids[] = $v['item_id'];
                }
            }

            $this->assign('activitydetail_list', $activitydetail_list);

            //根据查询条件查询商品列表
            $condition = array();
            if (input('param.gc_id') != '') {
                $condition['gc_id'] = intval(input('param.gc_id'));
            }
            if (input('param.brand_id') != '') {
                $condition['brand_id'] = intval(input('param.brand_id'));
            }
            if (trim(input('param.name')) != '') {
                $condition['goods_name'] = array('like', '%' . trim(input('param.name')) . '%');
            }
            if (!empty($item_ids)) {
                $condition['goods_id'] = array('not in', $item_ids);
            }
            $goods_model = model('goods');
            $goods_list = $goods_model->getGoodsOnlineList($condition, '*', 10);
            $this->assign('goods_list', $goods_list);
            $this->assign('show_page', $goods_model->page_info->render());
            $this->assign('search', input('param.get'));
            /**
             * 页面输出
             */
            $this->setAdminCurItem('activity_apply');
            return $this->fetch('activity_apply');
        } else {
            //判断页面参数
            $item_id_array = input('post.item_id/a');#获取POST数组
            if (empty($item_id_array)) {
                $this->error(lang('activity_choose_goods'), 'Activity/index');
            }
            $activity_id = intval(input('post.activity_id'));
            if ($activity_id <= 0) {
                ds_json_encode(10001,lang('param_error'));
            }
            //根据页面参数查询活动内容信息，如果不存在则添加，存在则根据状态进行修改
            $activity_model = model('activity');
            $activity = $activity_model->getOneActivityById($activity_id);
            //活动类型必须是商品并且活动没有关闭并且活动进行中
            if (empty($activity) || $activity['activity_type'] != '1' || $activity['activity_state'] != '1' || $activity['activity_startdate'] > time() || $activity['activity_enddate'] < time()) {
                ds_json_encode(10001,lang('activity_not_exists'));
            }
            $activitydetail_model = model('activitydetail');
            $list = $activitydetail_model->getActivitydetailList(array('activity_id' => "$activity_id"));
            $ids = array(); //已经存在的活动内容编号
            $ids_state2 = array(); //已经存在的被拒绝的活动编号
            if (is_array($list) and ! empty($list)) {
                foreach ($list as $ad) {
                    $ids[] = $ad['item_id'];
                    $ids_state2[] = $ad['item_id'];
                }
            }
            //根据查询条件查询商品列表
            foreach ($item_id_array as $item_id) {
                $item_id = intval($item_id);
                if (!in_array($item_id, $ids)) {
                    $input = array();
                    $input['activity_id'] = $activity_id;
                    $goods = model('goods');
                    $item = $goods->getGoodsOnlineInfoByID($item_id);
                    if (empty($item)) {
                        continue;
                    }
                    $input['item_name'] = $item['goods_name'];
                    $input['item_id'] = $item_id;
                    $activitydetail_model->addActivitydetail($input);
                } elseif (in_array($item_id, $ids_state2)) {
                    $input = array();
                    $activitydetail_model->editActivitydetail($input, array('item_id' => $item_id));
                }
            }
            ds_json_encode(10000,lang('ds_common_save_succ'));
        }
    }

    /**
     * 获取卖家栏目列表,针对控制器下的栏目
     */
    protected function getAdminItemList() {
        $menu_array = array(
            array(
                'name' => 'index', 'text' => lang('ds_manage'), 'url' => url('Activity/index')
            ), 
            array(
                'name' => 'add',
                'text' => lang('ds_new'),
                'url' => "javascript:dsLayerOpen('" . url('Activity/add') . "','新增')"
            ),
        );
        if (request()->action() == 'activity_apply') {
            $menu_array[] = array(
                'name' => 'activity_apply', 'text' => lang('ds_activity_manage'), 'url' => 'javascript:void(0)'
            );
        }
        return $menu_array;
    }

}