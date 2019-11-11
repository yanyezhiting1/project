<?php
namespace app\admin\controller;
use think\Lang;/**
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
class  Identity extends AdminControl {

    public function _initialize() {
        parent::_initialize();
        Lang::load(APP_PATH . 'admin/lang/'.config('default_lang').'/adv.lang.php');
    }


    /**
     *
     * 管理
     */
    public function index() {
        $identity_model = model('identity');


        if (!request()->isPost()) {
            $condition = array();

            $identity_info = $identity_model->getidentityList($condition, 20, '', '');
            $this->assign('identity_info', $identity_info);



            $this->assign('show_page', $identity_model->page_info->render());
            
            $this->assign('filtered', $condition ? 1 : 0); //是否有查询条件
            $this->setAdminCurItem('identity');
            return $this->fetch('identity_index');
        }
    }

    /**
     * 管理员添加广告
     */
    public function identity_add() {
        $identity_model = model('identity');
        if (!request()->isPost()) {

            //获取广告列表
            $this->assign('identity', '');

            return $this->fetch('identity_form');
        } else {

            $insert_array['name'] = trim(input('post.name'));
            $insert_array['is_choose'] = trim(input('post.is_choose'));
//            //上传文件保存路径
//            $upload_file = BASE_UPLOAD_PATH . DS . ATTACH_ADV;
//            if (!empty($_FILES['identity_code']['name'])) {
//                if($_FILES['identity_code']['size']>3*1024*1024){
//                    $this->error('上传文件不能大于3M，请重新选择');
//                }
//                $file = request()->file('identity_code');
//                $info = $file->rule('uniqid')->validate(['ext' => ALLOW_IMG_EXT])->move($upload_file);
//                if ($info) {
//                    $insert_array['identity_code'] = $info->getFilename();
//                } else {
//                    // 上传失败获取错误信息
//                    $this->error($file->getError());
//                }
//            }

            //验证数据  BEGIN

            //验证数据  END
            //广告信息入库
            $result = $identity_model->addidentity($insert_array);

            if ($result) {
                $this->log(lang('identity_add_succ') . '[' . input('post.identity_name') . ']', null);
                dsLayerOpenSuccess('分类添加成功');
//                $this->success(lang('identity_add_succ'), url('identity/identity', ['ap_id' => input('post.ap_id')]));
            } else {
                $this->error('添加失败');
            }
        }
    }

    /**
     *
     * 修改广告
     */
    public function identity_edit() {
        $identity_id = intval(input('param.identity_id'));
        $identity_model = model('identity');
        //获取指定广告
        $condition['identity_id'] = $identity_id;
        $identity = $identity_model->getOneidentity($condition);
        if (!request()->isPost()) {

            //获取广告列表
            $this->assign('identity', $identity);
            $this->assign('ref_url', get_referer());
            return $this->fetch('identity_form');
        } else {
            $param['identity_id'] = $identity_id;
            $param['identity_name'] = input('param.identity_name');
           

            if (!empty($_FILES['identity_code']['name'])) {
                //上传文件保存路径
                if($_FILES['identity_code']['size']>3*1024*1024){
                    $this->error('上传文件不能大于3M，请重新选择');
                }
                $upload_file = BASE_UPLOAD_PATH . DS . ATTACH_ADV;
                $file = request()->file('identity_code');
                $info = $file->rule('uniqid')->validate(['ext' => ALLOW_IMG_EXT])->move($upload_file);
                if ($info) {
                    //还需删除原来图片
                    if (!empty($identity['identity_code'])) {
                        @unlink($upload_file . DS . $identity['identity_code']);
                    }
                    $param['identity_code'] = $info->getFilename();
                } else {
                    // 上传失败获取错误信息
                    $this->error($file->getError());
                }
            }

            //验证数据  BEGIN
            $identity_validate = validate('identity');
            if (!$identity_validate->scene('identity_edit')->check($param)) {
                $this->error($identity_validate->getError());
            }
            //验证数据  END

            $result = $identity_model->editidentity($param);

            if ($result>=0) {
                $this->log(lang('identity_change_succ') . '[' . input('post.ap_name') . ']', null);
                dsLayerOpenSuccess(lang('identity_change_succ'));
//               $this->success(lang('identity_change_succ'), input('post.ref_url'));
            } else {
                $this->error(lang('identity_change_fail'));
            }
        }
    }

    /**
     *
     * 删除广告
     */
    public function identity_del() {
        $identity_model = model('identity');
        /**
         * 删除一个广告
         */
        $identity_id = intval(input('param.identity_id'));
        $result = $identity_model->delidentity($identity_id);

        if (!$result) {
            ds_json_encode('10001', lang('删除失败'));
        } else {
            $this->log(lang('identity_del_succ') . '[' . $identity_id . ']', null);
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
        $identity_model = model('identity');
        switch (input('get.branch')) {
            case 'ap_branch':
                $column = trim(input('param.column'));
                $value = trim(input('param.value'));
                $identity_id = intval(input('param.id'));
                $param['ap_id'] = $identity_id;
                $param[$column] = trim($value);
                $result = $identity_model->editidentityposition($param);
                break;
            //ADV数据表更新
            case 'identity_branch':
                $column = trim(input('param.column'));
                $value = trim(input('param.value'));
                $identity_id = intval(input('param.id'));
                $param[$column] = trim($value);
                $result = $identity_model->editidentity(array_merge($param, array('id' => $identity_id)));
                break;
        }
        if($result>=0){
            echo 'true';
        }else{
            echo false;
        }
    }

    function identity_template() {
        $pages = $this->_get_editable_pages();
        $this->assign('pages', $pages);
        $this->setAdminCurItem('identity_template');
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
//                'url' => url('identity/ap_manage')
//            ),
        );
//        $menu_array[] = array(
//            'name' => 'ap_add',
//            'text' => lang('ap_add'),
//            'url' =>"javascript:dsLayerOpen('".url('identity/ap_add')."','".lang('ap_add')."')"
//        );
        $menu_array[] = array(
            'name' => 'identity',
            'text' => lang('物流分类管理'),
            'url' => url('identity/index')
        );
        $menu_array[] = array(
            'name' => 'identity_add',
            'text' => lang('添加物流分类'),
            'url' => "javascript:dsLayerOpen('".url('identity/identity_add')."','".lang('添加物流分类')."')"
        );


        return $menu_array;
    }

}

?>
