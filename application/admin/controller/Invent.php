<?php
namespace app\admin\controller;
use think\Controller;
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
class  Invent  extends Controller{



    /**
     *
     * 管理广告位
     */
    public function invent($memberid='') {

        /**
         * 多选删除广告位
         */
        if (!request()->isPost()) {

            $code = db('member')->where('member_id',$memberid)->value('invent_code');

            $this->assign('code',$code);
            return $this->fetch('invent');
        }else{

            return $this->fetch('invent');
        }

    }

    public function agreement() {

        /**
         * 多选删除广告位
         */
        $content = db('article')->where('article_id',40)->value('article_content');
        $content =htmlspecialchars_decode($content);

        $this->assign('content', $content);
        return $this->fetch();


    }

    /**
     *
     * 修改广告位
     */
    public function ap_edit() {
        $ap_id = intval(input('param.ap_id'));
        $adv_model = model('adv');
        if (!request()->isPost()) {
            $condition['ap_id'] = $ap_id;
            $ap = $adv_model->getOneAdvposition($condition);
            $this->assign('ref_url', get_referer());
            $this->assign('ap', $ap);
            return $this->fetch('ap_form');
        } else {
            $param['ap_id'] = $ap_id;
//          $param['ap_goodsid'] = trim(input('post.ap_goodsid'));
            $param['ap_name'] = trim(input('post.ap_name'));
            $param['ap_intro'] = trim(input('post.ap_intro'));
            $param['ap_width'] = intval(trim(input('post.ap_width')));
            $param['ap_height'] = intval(trim(input('post.ap_height')));
            if (input('post.ap_isuse') != '') {
                $param['ap_isuse'] = intval(input('post.ap_isuse'));
            }

            //验证数据  BEGIN
            $adv_validate = validate('adv');
            if (!$adv_validate->scene('ap_edit')->check($param)) {
                $this->error($adv_validate->getError());
            }
            //验证数据  END
            $result = $adv_model->editAdvposition($param);

            if ($result>=0) {
                $this->log(lang('ap_change_succ') . '[' . input('post.ap_name') . ']', null);
                dsLayerOpenSuccess(lang('ap_change_succ'));
//                $this->success(lang('ap_change_succ'), input('post.ref_url'));
            } else {
                $this->error(lang('ap_change_fail'));
            }
        }
    }

    /**
     *
     * 新增广告位
     */
    public function ap_add() {
        if (!request()->isPost()) {
            $ap['ap_isuse'] = 1;
            $this->assign('ap', $ap);
            return $this->fetch('ap_form');
        } else {
            $adv_model = model('adv');

            $insert_array['ap_name'] = trim(input('post.ap_name'));
            $insert_array['ap_intro'] = trim(input('post.ap_intro'));
            $insert_array['ap_isuse'] = intval(input('post.ap_isuse'));
            $insert_array['ap_width'] = intval(input('post.ap_width'));
            $insert_array['ap_height'] = intval(input('post.ap_height'));

            //验证数据  BEGIN
            $adv_validate = validate('adv');
            if (!$adv_validate->scene('ap_add')->check($insert_array)) {
                $this->error($adv_validate->getError());
            }
            //验证数据  END

            $result = $adv_model->addAdvposition($insert_array);

            if ($result) {
                $this->log(lang('ap_add_succ') . '[' . input('post.ap_name') . ']', null);
                dsLayerOpenSuccess(lang('ap_add_succ'));
//               $this->success(lang('ap_add_succ'), url('Adv/ap_manage'));
           } else {
                $this->error(lang('ap_add_fail'));
            }
        }
    }

    /**
     *
     * 删除广告位
     */
    public function ap_del() {
        $adv_model = model('adv');
        /**
         * 删除一个广告
         */
        $ap_id = intval(input('param.ap_id'));
        $result = $adv_model->delAdvposition($ap_id);

        if (!$result) {
            ds_json_encode('10001', lang('ap_del_fail'));
        } else {
            $this->log(lang('ap_del_succ') . '[' . $ap_id . ']', null);
            ds_json_encode('10000', lang('ap_del_succ'));
        }
    }

    /**
     *
     * 广告管理
     */
    public function adv() {
        $adv_model = model('adv');

        $ap_id = intval(input('param.ap_id'));
        if (!request()->isPost()) {
            $condition = array();
            if ($ap_id) {
                $condition['ap_id'] = $ap_id;
            }
            $adv_info = $adv_model->getAdvList($condition, 20, '', '');
            $this->assign('adv_info', $adv_info);
            $ap_list = $adv_model->getAdvpositionList();
            $this->assign('ap_list', $ap_list);
            if ($ap_id) {
                $ap_condition=array();
                $ap_condition['ap_id'] = $ap_id;
                $ap = $adv_model->getOneAdvposition($ap_condition);
                $this->assign('ap_name', $ap['ap_name']);
            } else {
                $this->assign('ap_name', '');
            }

            $this->assign('show_page', $adv_model->page_info->render());
            
            $this->assign('filtered', $condition ? 1 : 0); //是否有查询条件
            $this->setAdminCurItem('adv');
            return $this->fetch('adv_index');
        }
    }

    /**
     * 管理员添加广告
     */
    public function adv_add() {
        $adv_model = model('adv');
        if (!request()->isPost()) {

            $ap_list = $adv_model->getAdvpositionList();
            $this->assign('ap_list', $ap_list);
            $adv = array(
                'ap_id' => 0,
                'adv_enabled' => '1',
                'adv_startdate' => time(),
                'adv_enddate' => time() + 24 * 3600 * 365,
            );
            $this->assign('adv', $adv);
            return $this->fetch('adv_form');
        } else {
            $insert_array['ap_id'] = intval(input('post.ap_id'));
            $insert_array['adv_goodsid'] = intval(input('post.adv_goodsid'));
            $insert_array['adv_title'] = trim(input('post.adv_name'));
            $insert_array['adv_link'] = input('post.adv_link');
            $insert_array['adv_sort'] = input('post.adv_sort');
            $insert_array['adv_enabled'] = input('post.adv_enabled');
            $insert_array['adv_startdate'] = $this->getunixtime(input('post.adv_startdate'));
            $insert_array['adv_enddate'] = $this->getunixtime(input('post.adv_enddate'));

            //上传文件保存路径
            $upload_file = BASE_UPLOAD_PATH . DS . ATTACH_ADV;
            if (!empty($_FILES['adv_code']['name'])) {
                if($_FILES['adv_code']['size']>3*1024*1024){
                    $this->error('上传文件不能大于3M，请重新选择');
                }
                $file = request()->file('adv_code');
                $info = $file->rule('uniqid')->validate(['ext' => ALLOW_IMG_EXT])->move($upload_file);
                if ($info) {
                    $insert_array['adv_code'] = $info->getFilename();
                } else {
                    // 上传失败获取错误信息
                    $this->error($file->getError());
                }
            }

            //验证数据  BEGIN
            $adv_validate = validate('adv');
            if (!$adv_validate->scene('adv_add')->check($insert_array)) {
                $this->error($adv_validate->getError());
            }
            //验证数据  END
            //广告信息入库
            $result = $adv_model->addAdv($insert_array);

            if ($result) {
                $this->log(lang('adv_add_succ') . '[' . input('post.adv_name') . ']', null);
                dsLayerOpenSuccess(lang('adv_add_succ'));
//                $this->success(lang('adv_add_succ'), url('Adv/adv', ['ap_id' => input('post.ap_id')]));
            } else {
                $this->error(lang('adv_add_fail'));
            }
        }
    }

    /**
     *
     * 修改广告
     */
    public function adv_edit() {
        $adv_id = intval(input('param.adv_id'));
        $adv_model = model('adv');
        //获取指定广告
        $condition['adv_id'] = $adv_id;
        $adv = $adv_model->getOneAdv($condition);
        if (!request()->isPost()) {

            //获取广告列表
            $ap_list = $adv_model->getAdvpositionList();
            $this->assign('ap_list', $ap_list);
            $this->assign('adv', $adv);
            $this->assign('ref_url', get_referer());
            return $this->fetch('adv_form');
        } else {
            $param['adv_id'] = $adv_id;
            $param['ap_id'] = intval(input('post.ap_id'));
            $param['adv_goodsid'] = input('post.adv_goodsid');
            $param['adv_title'] = trim(input('post.adv_name'));
            $param['adv_link'] = input('post.adv_link');
            $param['adv_sort'] = input('post.adv_sort');
            $param['adv_enabled'] = input('post.adv_enabled');
            $param['adv_bgcolor'] = input('post.adv_bgcolor');
            $param['adv_startdate'] = $this->getunixtime(trim(input('post.adv_startdate')));
            $param['adv_enddate'] = $this->getunixtime(trim(input('post.adv_enddate')));
           

            if (!empty($_FILES['adv_code']['name'])) {
                //上传文件保存路径
                if($_FILES['adv_code']['size']>3*1024*1024){
                    $this->error('上传文件不能大于3M，请重新选择');
                }
                $upload_file = BASE_UPLOAD_PATH . DS . ATTACH_ADV;
                $file = request()->file('adv_code');
                $info = $file->rule('uniqid')->validate(['ext' => ALLOW_IMG_EXT])->move($upload_file);
                if ($info) {
                    //还需删除原来图片
                    if (!empty($adv['adv_code'])) {
                        @unlink($upload_file . DS . $adv['adv_code']);
                    }
                    $param['adv_code'] = $info->getFilename();
                } else {
                    // 上传失败获取错误信息
                    $this->error($file->getError());
                }
            }

            //验证数据  BEGIN
            $adv_validate = validate('adv');
            if (!$adv_validate->scene('adv_edit')->check($param)) {
                $this->error($adv_validate->getError());
            }
            //验证数据  END

            $result = $adv_model->editAdv($param);

            if ($result>=0) {
                $this->log(lang('adv_change_succ') . '[' . input('post.ap_name') . ']', null);
                dsLayerOpenSuccess(lang('adv_change_succ'));
//               $this->success(lang('adv_change_succ'), input('post.ref_url'));
            } else {
                $this->error(lang('adv_change_fail'));
            }
        }
    }

    /**
     *
     * 删除广告
     */
    public function adv_del() {
        $adv_model = model('adv');
        /**
         * 删除一个广告
         */
        $adv_id = intval(input('param.adv_id'));
        $result = $adv_model->delAdv($adv_id);

        if (!$result) {
            ds_json_encode('10001', lang('adv_del_fail'));
        } else {
            $this->log(lang('adv_del_succ') . '[' . $adv_id . ']', null);
            ds_json_encode('10000', lang('adv_del_succ'));
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
            array(
                'name' => 'ap_manage',
                'text' => lang('ap_manage'),
                'url' => url('Adv/ap_manage')
            ),
        );
//        $menu_array[] = array(
//            'name' => 'ap_add',
//            'text' => lang('ap_add'),
//            'url' =>"javascript:dsLayerOpen('".url('Adv/ap_add')."','".lang('ap_add')."')"
//        );
        $menu_array[] = array(
            'name' => 'adv',
            'text' => lang('adv_manage'),
            'url' => url('Adv/adv')
        );
        $menu_array[] = array(
            'name' => 'adv_add',
            'text' => lang('adv_add'),
            'url' => "javascript:dsLayerOpen('".url('Adv/adv_add', ['ap_id' => input('param.ap_id')])."','".lang('adv_add')."')"
        );
//        $menu_array[] = array(
//            'name' => 'adv_template',
//            'text' => lang('adv_template'),
//            'url' => url('Adv/adv_template')
//        );

        return $menu_array;
    }

}

?>
