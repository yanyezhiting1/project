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
class  Inform extends AdminControl
{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        Lang::load(APP_PATH . 'admin/lang/'.config('default_lang').'/inform.lang.php');
    }

    /*
        * 未处理的举报列表
        */
    public function inform_list()
    {

        $this->get_inform_list(1,  'inform_list');
        return $this->fetch();
    }

    /*
     * 已处理的举报列表
     */
    public function inform_handled_list()
    {

        $this->get_inform_list(2, 'inform_handled_list');
        return $this->fetch();
    }


    /*
     * 获取举报列表
     */
    private function get_inform_list($type, $action)
    {

        //获得举报列表
        $inform_model = model('inform');
        //搜索条件
        $condition = array();
        if ((input('param.input_inform_goods_name'))) {
            $condition['inform.inform_goods_name'] = array('like', "%" . input('param.input_inform_goods_name') . "%");
        }
        if ((input('param.input_inform_member_name'))) {
            $condition['inform.inform_member_name'] = array('like', "%" . input('param.input_inform_member_name') . "%");
        }
        if ((input('param.input_inform_type'))) {
            $condition['inform_subject.informsubject_type_name'] = array('like', "%" . input('param.input_inform_type') . "%");
        }
        if ((input('param.input_inform_subject'))) {
            $condition['inform_subject.informsubject_content'] = array('like', "%" . input('param.input_inform_subject') . "%");
        }
        $stime = input('param.input_inform_datetime_start')?strtotime(input('param.input_inform_datetime_start')):0;
        $etime = input('param.input_inform_datetime_end')?strtotime(input('param.input_inform_datetime_end')):0;
        if ($stime > 0 && $etime>0){
            $condition['inform.inform_datetime'] = array('between',array($stime,$etime));
        }
        if ($type === 1) {
            $order = 'inform_id asc';
        } else {
            $order = 'inform_id desc';
        }
        $condition['inform.inform_state'] = $type;
        $inform_list = $inform_model->getInformList($condition, 10,$order);

        $this->setAdminCurItem($action);
        $this->assign('inform_list', $inform_list);
        $this->assign('show_page', $inform_model->page_info->render());
    }


    /*
     * 举报类型列表
     */
    public function inform_subject_type_list()
    {

        //获得有效举报类型列表
        $informsubjecttype_model = model('informsubjecttype');
        $informsubjecttype_list = $informsubjecttype_model->getActiveInformsubjecttypeList(10);

        $this->setAdminCurItem('inform_subject_type_list');
        $this->assign('informsubjecttype_list', $informsubjecttype_list);
        $this->assign('show_page', $informsubjecttype_model->page_info->render());
        return $this->fetch();
    }


    /*
     * 举报主题列表
     */
    public function inform_subject_list()
    {

        //获得举报主题列表
        $informsubject_model = model('informsubject');

        //搜索条件
        $condition = array();
        $condition['informsubject_type_id'] = trim(input('param.informsubject_type_id'));
        $condition['informsubject_state'] = 1;
        $informsubject_list = $informsubject_model->getInformsubjectList($condition, 10);

        //获取有效举报类型
        $informsubjecttype_model = model('informsubjecttype');
        $type_list = $informsubjecttype_model->getActiveInformsubjecttypeList();

        $this->setAdminCurItem('inform_subject_list');
        $this->assign('informsubject_list', $informsubject_list);
        $this->assign('type_list', $type_list);
        $this->assign('show_page', $informsubject_model->page_info->render());
        return $this->fetch();
    }

    /*
     * 添加举报类型页面
     */
    public function inform_subject_type_add()
    {
        return $this->fetch();
    }

    /*
     * 保存添加的举报类型
     */
    public function inform_subject_type_save()
    {

        //获取提交的内容
        $input['informtype_name'] = trim(input('post.informtype_name'));
        $input['informtype_desc'] = trim(input('post.informtype_desc'));

        //验证提交的内容
        $data=[
           'informtype_name'=> $input['informtype_name'],
           'informtype_desc'=>$input['informtype_desc'],
        ];
        $inform_validate = validate('inform');
        if (!$inform_validate->scene('inform_subject_type_save')->check($data)) {
            $this->error($inform_validate->getError());
        } else {
            //验证成功保存
            $input['informtype_state'] = 1;
            $informsubjecttype_model = model('informsubjecttype');
            $informsubjecttype_model->addInformsubjecttype($input);
            $this->log(lang('ds_add').lang('inform_type') . '[' . input('post.informtype_name') . ']', 1);
            dsLayerOpenSuccess(lang('ds_common_save_succ'));
        }
    }

    /*
     * 删除举报类型,伪删除只是修改标记
     */
    public function inform_subject_type_drop()
    {

        $informtype_id = trim(input('param.informtype_id'));
        $informrtype_id_array = ds_delete_param($informtype_id);
        if ($informrtype_id_array == FALSE) {
            ds_json_encode(10001, lang('param_error'));
        }

        //删除分类
        $informsubjecttype_model = model('informsubjecttype');
        $update_array = array();
        $update_array['informtype_state'] = 2;
        $where_array = array();
        $where_array['informtype_id'] = array('in',$informrtype_id_array);
        $informsubjecttype_model->editInformsubjecttype($update_array, $where_array);

        //删除分类下边的主题
        $informsubject_model = model('informsubject');
        $update_subject_array = array();
        $update_subject_array['informsubject_state'] = 2;
        $where_subject_array = array();
        $where_subject_array['informsubject_type_id'] = array('in',$informrtype_id_array);
        $informsubject_model->editInformsubject($update_subject_array, $where_subject_array);
        $this->log(lang('ds_del').lang('inform_type') . '[ID:' . input('post.informtype_id') . ']', 1);
        ds_json_encode(10000, lang('ds_common_del_succ'));
    }

    /*
     * 添加举报主题页面
     */
    public function inform_subject_add()
    {

        //获得可用举报类型列表
        $informsubjecttype_model = model('informsubjecttype');
        $informsubjecttype_list = $informsubjecttype_model->getActiveInformsubjecttypeList();

        if (empty($informsubjecttype_list)) {
            $this->error(lang('inform_type_error'));
        }
        $this->assign('informsubjecttype_list', $informsubjecttype_list);
        return $this->fetch('');

    }

    /*
     * 保存添加的举报主题
     */
    public function inform_subject_save()
    {
        //获取提交的内容
        list($input['informsubject_type_id'], $input['informsubject_type_name']) = explode(',', trim(input('post.inform_subject_type')));
        $input['informsubject_content'] = trim(input('post.informsubject_content'));

        //验证提交的内容
        $data=[
            'informsubject_type_name'=>$input['informsubject_type_name'],
            'informsubject_content'=>$input['informsubject_content'],
            'informsubject_type_id'=>$input['informsubject_type_id']
        ];

        $inform_validate = validate('inform');
        if (!$inform_validate->scene('inform_subject_save')->check($data)) {
            $this->error($inform_validate->getError());
        } else {
            //验证成功保存
            $input['informsubject_state'] = 1;
            $informsubject_model = model('informsubject');
            $informsubject_model->addInformsubject($input);
            $this->log('添加'.lang('inform_subject') . '[' . $input['informsubject_type_name'] . ']', 1);
            dsLayerOpenSuccess(lang('ds_common_save_succ'));
        }
    }

    /*
     * 删除举报主题,伪删除只是修改标记
     */
    public function inform_subject_drop()
    {
        $informsubject_id = trim(input('param.informsubject_id'));
        
        $informsubject_id_array = ds_delete_param($informsubject_id);
        if ($informsubject_id_array == FALSE) {
            ds_json_encode('10001', lang('param_error'));
        }
        $informsubject_model = model('informsubject');
        $update_array = array();
        $update_array['informsubject_state'] = 2;
        $where_array = array();
        $where_array['informsubject_id'] = array('in',$informsubject_id_array);
        $informsubject_model->editInformsubject($update_array, $where_array);
        $this->log(lang('ds_del').lang('inform_subject') . '[' . input('post.informsubject_id') . ']', 1);
        ds_json_encode(10000, lang('ds_common_del_succ'));
    }    


    /*
     * 显示处理举报
     */
    public function show_handle_page()
    {
        $inform_id = intval(input('param.inform_id'));
        $inform_goods_name = urldecode(input('param.inform_goods_name'));

        $this->assign('inform_id', $inform_id);
        $this->assign('inform_goods_name', $inform_goods_name);
        return $this->fetch('inform_handle');
    }

    /*
     * 处理举报
     */
    public function inform_handle()
    {

        $inform_id = intval(input('post.inform_id'));
        $inform_handle_type = intval(input('post.inform_handle_type'));
        $inform_handle_message = trim(input('post.inform_handle_message'));

        if (empty($inform_id) || empty($inform_handle_type)) {
            $this->error(lang('param_error'));
        }

        //验证输入的数据
        $data= [
            "inform_handle_message" => $inform_handle_message,
        ];

        $inform_validate = validate('inform');
        if (!$inform_validate->scene('inform_handle')->check($data)) {
            $this->error($inform_validate->getError());
        }

        $inform_model = model('inform');
        $inform_info = $inform_model->getOneInform(array('inform_id'=>$inform_id));
        if (empty($inform_info) || intval($inform_info['inform_state']) === 2) {
            $this->error(lang('param_error'));
        }

        $update_array = array();
        $where_array = array();

        //根据选择处理
        switch ($inform_handle_type) {

            case 1:
                $where_array['inform_id'] = $inform_id;
                break;
            case 2:
                //恶意举报，清理所有该用户的举报，设置该用户禁止举报
                $where_array['inform_member_id'] = $inform_info['inform_member_id'];
                $this->denyMemberInform($inform_info['inform_member_id']);
                break;
            default:
                $this->error(lang('param_error'));

        }

        $update_array['inform_state'] = 2;
        $update_array['inform_handle_type'] = $inform_handle_type;
        $update_array['inform_handle_message'] = $inform_handle_message;
        $update_array['inform_handle_datetime'] = time();
        $admin_info = $this->getAdminInfo();
        $update_array['inform_handle_member_id'] = $admin_info['admin_id'];
        $where_array['inform_state'] = 1;

        if ($inform_model->editInform($update_array, $where_array)) {
            $this->log(lang('inform_text_handle').lang('inform') . '[ID:' . $inform_id . ']', 1);
            dsLayerOpenSuccess(lang('ds_common_op_succ'));
        } else {
            $this->error(lang('ds_common_op_fail'));
        }
    }

    /*
     * 禁止该用户举报
     */
    private function denyMemberInform($member_id)
    {

        $member_model = model('member');
        $param = array();
        $param['inform_allow'] = 2;
        return $member_model->editMember(array('member_id' => $member_id), $param);
    }


    /**
     * 获取卖家栏目列表,针对控制器下的栏目
     */
    protected function getAdminItemList()
    {
        $menu_array = array(
            array(
                'name' => 'inform_list',
                'text' => lang('inform_state_unhandle'),
                'url' => url('Inform/inform_list')
            ),
            array(
                'name' => 'inform_handled_list',
                'text' => lang('inform_state_handled'),
                'url' => url('Inform/inform_handled_list')
            ),
            array(
                'name' => 'inform_subject_type_list',
                'text' => lang('inform_type'),
                'url' => url('Inform/inform_subject_type_list')
            ),
            array(
                'name' => 'inform_subject_type_add',
                'text' => lang('inform_type_add'),
                'url' =>"javascript:dsLayerOpen('".url('Inform/inform_subject_type_add')."','".lang('inform_type_add')."')"
            ),
            array(
                'name' => 'inform_subject_list',
                'text' => lang('inform_subject'),
                'url' => url('Inform/inform_subject_list')
            ),
            array(
                'name' => 'inform_subject_add',
                'text' => lang('inform_subject_add'),
                'url' =>"javascript:dsLayerOpen('".url('Inform/inform_subject_add')."','".lang('inform_subject_add')."')"
            ),
        );
       return $menu_array;
    }
}