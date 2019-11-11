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
class  Material3 extends AdminControl {

    public function _initialize() {
        parent::_initialize();
        Lang::load(APP_PATH . 'admin/lang/'.config('default_lang').'/adv.lang.php');
    }


    /**
     *
     * 广告管理
     */
    public function index() {
        $material3_model = model('material3');


        if (!request()->isPost()) {
            $condition = array();

            $material3_info = $material3_model->getmaterial3List($condition, 20, '', '');
            $this->assign('material3_info', $material3_info);



            $this->assign('show_page', $material3_model->page_info->render());
            
            $this->assign('filtered', $condition ? 1 : 0); //是否有查询条件
            $this->setAdminCurItem('material3');
            return $this->fetch('material3_index');
        }
    }

    /**
     * 管理员添加广告
     */
    public function material3_add() {
        $material3_model = model('material3');
        if (!request()->isPost()) {

            //获取广告列表
            $this->assign('material3', '');

            return $this->fetch('material3_form');
        } else {

            $insert_array['material3_name'] = trim(input('post.material3_name'));


//            //上传文件保存路径
//            $upload_file = BASE_UPLOAD_PATH . DS . ATTACH_ADV;
//            if (!empty($_FILES['material3_code']['name'])) {
//                if($_FILES['material3_code']['size']>3*1024*1024){
//                    $this->error('上传文件不能大于3M，请重新选择');
//                }
//                $file = request()->file('material3_code');
//                $info = $file->rule('uniqid')->validate(['ext' => ALLOW_IMG_EXT])->move($upload_file);
//                if ($info) {
//                    $insert_array['material3_code'] = $info->getFilename();
//                } else {
//                    // 上传失败获取错误信息
//                    $this->error($file->getError());
//                }
//            }

            //验证数据  BEGIN

            //验证数据  END
            //广告信息入库
            $result = $material3_model->addmaterial3($insert_array);

            if ($result) {
                $this->log(lang('material3_add_succ') . '[' . input('post.material3_name') . ']', null);
                dsLayerOpenSuccess('属性添加成功');
//                $this->success(lang('material3_add_succ'), url('material3/material3', ['ap_id' => input('post.ap_id')]));
            } else {
                $this->error('添加失败');
            }
        }
    }

    /**
     *
     * 修改广告
     */
    public function material3_edit() {
        $material3_id = intval(input('param.material3_id'));
        $material3_model = model('material3');
        //获取指定广告
        $condition['material3_id'] = $material3_id;
        $material3 = $material3_model->getOnematerial3($condition);
        if (!request()->isPost()) {

            //获取广告列表
            $this->assign('material3', $material3);
            $this->assign('ref_url', get_referer());
            return $this->fetch('material3_form');
        } else {
            $param['material3_id'] = $material3_id;
            $param['material3_name'] = input('param.material3_name');
           

            if (!empty($_FILES['material3_code']['name'])) {
                //上传文件保存路径
                if($_FILES['material3_code']['size']>3*1024*1024){
                    $this->error('上传文件不能大于3M，请重新选择');
                }
                $upload_file = BASE_UPLOAD_PATH . DS . ATTACH_ADV;
                $file = request()->file('material3_code');
                $info = $file->rule('uniqid')->validate(['ext' => ALLOW_IMG_EXT])->move($upload_file);
                if ($info) {
                    //还需删除原来图片
                    if (!empty($material3['material3_code'])) {
                        @unlink($upload_file . DS . $material3['material3_code']);
                    }
                    $param['material3_code'] = $info->getFilename();
                } else {
                    // 上传失败获取错误信息
                    $this->error($file->getError());
                }
            }

            //验证数据  BEGIN
            $material3_validate = validate('material3');
            if (!$material3_validate->scene('material3_edit')->check($param)) {
                $this->error($material3_validate->getError());
            }
            //验证数据  END

            $result = $material3_model->editmaterial3($param);

            if ($result>=0) {
                $this->log(lang('material3_change_succ') . '[' . input('post.ap_name') . ']', null);
                dsLayerOpenSuccess(lang('material3_change_succ'));
//               $this->success(lang('material3_change_succ'), input('post.ref_url'));
            } else {
                $this->error(lang('material3_change_fail'));
            }
        }
    }

    /**
     *
     * 删除广告
     */
    public function material3_del() {
        $material3_model = model('material3');
        /**
         * 删除一个广告
         */
        $material3_id = intval(input('param.material3_id'));
        $result = $material3_model->delmaterial3($material3_id);

        if (!$result) {
            ds_json_encode('10001', '删除失败');
        } else {
            $this->log(lang('删除属性成功') . '[' . $material3_id . ']', null);
            ds_json_encode('10000', '删除成功');
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
        $material3_model = model('material3');
        switch (input('get.branch')) {
            case 'ap_branch':
                $column = trim(input('param.column'));
                $value = trim(input('param.value'));
                $material3_id = intval(input('param.id'));
                $param['ap_id'] = $material3_id;
                $param[$column] = trim($value);
                $result = $material3_model->editmaterial3position($param);
                break;
            //ADV数据表更新
            case 'material3_branch':
                $column = trim(input('param.column'));
                $value = trim(input('param.value'));
                $material3_id = intval(input('param.id'));
                $param[$column] = trim($value);
                $result = $material3_model->editmaterial3(array_merge($param, array('material3_id' => $material3_id)));
                break;
        }
        if($result>=0){
            echo 'true';
        }else{
            echo false;
        }
    }

    function material3_template() {
        $pages = $this->_get_editable_pages();
        $this->assign('pages', $pages);
        $this->setAdminCurItem('material3_template');
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
//                'url' => url('material3/ap_manage')
//            ),
        );
//        $menu_array[] = array(
//            'name' => 'ap_add',
//            'text' => lang('ap_add'),
//            'url' =>"javascript:dsLayerOpen('".url('material3/ap_add')."','".lang('ap_add')."')"
//        );
        $menu_array[] = array(
            'name' => 'material3',
            'text' => lang('属性管理'),
            'url' => url('material3/index')
        );
        $menu_array[] = array(
            'name' => 'material3_add',
            'text' => lang('添加属性'),
            'url' => "javascript:dsLayerOpen('".url('material3/material3_add')."','".lang('添加属性')."')"
        );


        return $menu_array;
    }

}

?>
