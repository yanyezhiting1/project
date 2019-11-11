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
class  Membervip extends AdminControl {

    public function _initialize() {
        parent::_initialize();
        Lang::load(APP_PATH . 'admin/lang/'.config('default_lang').'/adv.lang.php');
    }







//    /**
//     *
//     * 删除广告位
//     */
//    public function ap_del() {
//        $adv_model = model('adv');
//        /**
//         * 删除一个广告
//         */
//        $ap_id = intval(input('param.ap_id'));
//        $result = $adv_model->delAdvposition($ap_id);
//
//        if (!$result) {
//            ds_json_encode('10001', lang('ap_del_fail'));
//        } else {
//            $this->log(lang('ap_del_succ') . '[' . $ap_id . ']', null);
//            ds_json_encode('10000', lang('ap_del_succ'));
//        }
//    }

    /**
     *
     * 广告管理
     */
    public function index() {
        $vip_model = model('membervip');

//        $state = intval(input('param.state'));
        if (!request()->isPost()) {
            $condition = array();
//            if (is_numeric($state) === true) {
//                $condition['state'] = $state;
//
//            }
            $auth_info = $vip_model->getVipList($condition, 20, '', '');

//            foreach($auth_info as $k=>$v){
//                $userid = $v['userid'];
//                $member = db('member')->where('member_id',$userid)->value('member_name');
//                $auth_info[$k]['member_name'] = $member;
//            }

            $this->assign('adv_info', $auth_info);



            $this->assign('show_page', $vip_model->page_info->render());

            $this->assign('filtered', $condition ? 1 : 0); //是否有查询条件
            $this->setAdminCurItem('adv');
            return $this->fetch('adv_index');
        }
    }

    /**
     * 管理员添加广告
     */
    public function adv_add() {
        $vip_model = model('membervip');
        if (!request()->isPost()) {
            return $this->fetch('adv_form');
        } else {
            $insert_array['charge_time'] = intval(input('post.charge_time'));
            $insert_array['charge_money'] = intval(input('post.charge_money'));


//            //上传文件保存路径
//            $upload_file = BASE_UPLOAD_PATH . DS . ATTACH_ADV;
//            if (!empty($_FILES['adv_code']['name'])) {
//                if($_FILES['adv_code']['size']>3*1024*1024){
//                    $this->error('上传文件不能大于3M，请重新选择');
//                }
//                $file = request()->file('adv_code');
//                $info = $file->rule('uniqid')->validate(['ext' => ALLOW_IMG_EXT])->move($upload_file);
//                if ($info) {
//                    $insert_array['adv_code'] = $info->getFilename();
//                } else {
//                    // 上传失败获取错误信息
//                    $this->error($file->getError());
//                }
//            }

            //验证数据  BEGIN
//            $adv_validate = validate('adv');
//            if (!$adv_validate->scene('adv_add')->check($insert_array)) {
//                $this->error($adv_validate->getError());
//            }
            //验证数据  END
            //广告信息入库
            $result = $vip_model->addAdv($insert_array);

            if ($result) {
//                $this->log(lang('添加成功') . '[' . input('post.adv_name') . ']', null);
                dsLayerOpenSuccess(lang('规则添加成功'));
//                $this->success(lang('adv_add_succ'), url('Adv/adv', ['ap_id' => input('post.ap_id')]));
            } else {
                $this->error(lang('规则添加失败'));
            }
        }
    }

    /**
     *
     * 修改广告
     */
    public function adv_edit() {
        $id = intval(input('param.id'));

        $vip_model = model('membervip');
        //获取指定广告
        $condition['id'] = $id;
        $vip = $vip_model->getOneVip($condition);

        if (!request()->isPost()) {

            //获取广告列表

            $this->assign('adv', $vip);
            $this->assign('ref_url', get_referer());
            return $this->fetch('adv_form');
        } else {
            $param['id'] = $id;

            $param['charge_time'] = input('post.charge_time');
            $param['charge_money'] = input('post.charge_money');



//            if (!empty($_FILES['adv_code']['name'])) {
//                //上传文件保存路径
//                if($_FILES['adv_code']['size']>3*1024*1024){
//                    $this->error('上传文件不能大于3M，请重新选择');
//                }
//                $upload_file = BASE_UPLOAD_PATH . DS . ATTACH_ADV;
//                $file = request()->file('adv_code');
//                $info = $file->rule('uniqid')->validate(['ext' => ALLOW_IMG_EXT])->move($upload_file);
//                if ($info) {
//                    //还需删除原来图片
//                    if (!empty($adv['adv_code'])) {
//                        @unlink($upload_file . DS . $adv['adv_code']);
//                    }
//                    $param['adv_code'] = $info->getFilename();
//                } else {
//                    // 上传失败获取错误信息
//                    $this->error($file->getError());
//                }
//            }

//            //验证数据  BEGIN
//            $adv_validate = validate('adv');
//            if (!$adv_validate->scene('adv_edit')->check($param)) {
//                $this->error($adv_validate->getError());
//            }
            //验证数据  END

            $result = $vip_model->editVip($param);

            if ($result>=0) {
                $this->log(lang('adv_change_succ') . '[' . input('post.ap_name') . ']', null);
                dsLayerOpenSuccess('会员充值规则修改成功');
//               $this->success(lang('adv_change_succ'), input('post.ref_url'));
            } else {
                $this->error('会员充值规则修改失败');
            }
        }
    }

    /**
     *
     * 删除广告
     */
    public function adv_del() {
        $adv_model = model('membervip');
        /**
         * 删除一个广告
         */
        $id = intval(input('param.id'));
        $result = $adv_model->delAdv($id);

        if (!$result) {
            ds_json_encode('10001', lang('规则删除失败'));
        } else {
//            $this->log(lang('adv_del_succ') . '[' . $adv_id . ']', null);
            ds_json_encode('10000', lang('删除成功'));
        }
    }

    /**
     *
     * 获取UNIX时间戳
     */
    public function getunixtime($time) {
        $array = explode("-", $time);
        $unix_time = mktime(0, 0, 0, $array[1], $array[2], $array[0]);
        return $unix_time;
    }

    public function ajax() {
        $adv_model = model('adv');
        switch (input('get.branch')) {
            case 'ap_branch':
                $column = trim(input('param.column'));
                $value = trim(input('param.value'));
                $adv_id = intval(input('param.id'));
                $param['ap_id'] = $adv_id;
                $param[$column] = trim($value);
                $result = $adv_model->editAdvposition($param);
                break;
            //ADV数据表更新
            case 'adv_branch':
                $column = trim(input('param.column'));
                $value = trim(input('param.value'));
                $adv_id = intval(input('param.id'));
                $param[$column] = trim($value);
                $result = $adv_model->editAdv(array_merge($param, array('adv_id' => $adv_id)));
                break;
        }
        if($result>=0){
            echo 'true';
        }else{
            echo false;
        }
    }

    function adv_template() {
        $pages = $this->_get_editable_pages();
        $this->assign('pages', $pages);
        $this->setAdminCurItem('adv_template');
        return $this->fetch();
    }

    /**
     *    获取可以编辑的页面列表
     */
    function _get_editable_pages() {
        return array(
            '首页' => url('Home/Index/index',['edit_ad'=>1]),
        );
    }

    /**
     * 获取卖家栏目列表,针对控制器下的栏目
     */
    protected function getAdminItemList() {
        $menu_array = array(
//            array(
//                'name' => 'ap_manage',
//                'text' => lang('ap_manage'),
//                'url' => url('Adv/ap_manage')
//            ),
        );
        $menu_array[] = array(
            'name' => 'ap_add',
            'text' => lang('新增充值规则'),
            'url' =>"javascript:dsLayerOpen('".url('membervip/adv_add')."','".lang('添加充值规则')."')"
        );
//        $menu_array[] = array(
//            'name' => 'adv',
//            'text' => lang('adv_manage'),
//            'url' => url('Adv/index')
//        );
//        $menu_array[] = array(
//            'name' => 'adv_add',
//            'text' => lang('adv_add'),
//            'url' => "javascript:dsLayerOpen('".url('Adv/adv_add', ['ap_id' => input('param.ap_id')])."','".lang('adv_add')."')"
//        );
//        $menu_array[] = array(
//            'name' => 'adv_template',
//            'text' => lang('adv_template'),
//            'url' => url('Adv/adv_template')
//        );

        return $menu_array;
    }

}

?>
