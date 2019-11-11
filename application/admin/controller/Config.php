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
class  Config extends AdminControl {

    public function _initialize() {
        parent::_initialize();
        Lang::load(APP_PATH . 'admin/lang/'.config('default_lang').'/config.lang.php');
    }

    public function base() {
        $config_model = model('config');
        if (!request()->isPost()) {
            $list_config = rkcache('config', true);
            $this->assign('list_config', $list_config);
            /* 设置卖家当前栏目 */
            $this->setAdminCurItem('base');
            return $this->fetch();
        } else {
            //上传文件保存路径
            $upload_file = BASE_UPLOAD_PATH . DS . ATTACH_COMMON;
            if (!empty($_FILES['site_logo']['name'])) {
                $file = request()->file('site_logo');
                $info = $file->validate(['ext'=>ALLOW_IMG_EXT])->move($upload_file, 'site_logo');
                if ($info) {
                    $upload['site_logo'] = $info->getFilename();
                } else {
                    // 上传失败获取错误信息
                    $this->error($file->getError());
                }
            }
            if (!empty($_FILES['image1']['name'])) {
                $file = request()->file('image1');

                $info = $file->validate(['ext'=>ALLOW_IMG_EXT])->move($upload_file, 'image1');
                if ($info) {
                    $upload['image1'] = $info->getFilename();
                } else {
                    // 上传失败获取错误信息
                    $this->error($file->getError());
                }
            }
            if (!empty($_FILES['image2']['name'])) {
                $file = request()->file('image2');
                $info = $file->validate(['ext'=>ALLOW_IMG_EXT])->move($upload_file, 'image2');
                if ($info) {
                    $upload['image2'] = $info->getFilename();
                } else {
                    // 上传失败获取错误信息
                    $this->error($file->getError());
                }
            }
            if (!empty($_FILES['image3']['name'])) {
                $file = request()->file('image3');
                $info = $file->validate(['ext'=>ALLOW_IMG_EXT])->move($upload_file, 'image3');
                if ($info) {
                    $upload['image3'] = $info->getFilename();
                } else {
                    // 上传失败获取错误信息
                    $this->error($file->getError());
                }
            }
            if (!empty($_FILES['image4']['name'])) {
                $file = request()->file('image4');
                $info = $file->validate(['ext'=>ALLOW_IMG_EXT])->move($upload_file, 'image4');
                if ($info) {
                    $upload['image4'] = $info->getFilename();
                } else {
                    // 上传失败获取错误信息
                    $this->error($file->getError());
                }
            }
            if (!empty($_FILES['image5']['name'])) {
                $file = request()->file('image5');
                $info = $file->validate(['ext'=>ALLOW_IMG_EXT])->move($upload_file, 'image5');
                if ($info) {
                    $upload['image5'] = $info->getFilename();
                } else {
                    // 上传失败获取错误信息
                    $this->error($file->getError());
                }
            }
            if (!empty($upload['site_logo'])) {
                $update_array['site_logo'] = $upload['site_logo'];
            }
            if (!empty($_FILES['member_logo']['name'])) {
                $file = request()->file('member_logo');
                $info = $file->validate(['ext'=>ALLOW_IMG_EXT])->move($upload_file, 'member_logo');
                if ($info) {
                    $upload['member_logo'] = $info->getFilename();
                } else {
                    // 上传失败获取错误信息
                    $this->error($file->getError());
                }
            }
            if (!empty($upload['member_logo'])) {
                $update_array['member_logo'] = $upload['member_logo'];
            }
            if (!empty($_FILES['site_mobile_logo']['name'])) {
                $file = request()->file('site_mobile_logo');
                $info = $file->validate(['ext'=>ALLOW_IMG_EXT])->move($upload_file, 'site_mobile_logo.png');
                if ($info) {
                    $upload['site_mobile_logo'] = $info->getFilename();
                } else {
                    // 上传失败获取错误信息
                    $this->error($file->getError());
                }
            }
            if (!empty($upload['site_mobile_logo'])) {
                $update_array['site_mobile_logo'] = $upload['site_mobile_logo'];
            }
            if (!empty($_FILES['site_logowx']['name'])) {
                $file = request()->file('site_logowx');
                $info = $file->validate(['ext'=>ALLOW_IMG_EXT])->move($upload_file, 'site_logowx');
                if ($info) {
                    $upload['site_logowx'] = $info->getFilename();
                } else {
                    // 上传失败获取错误信息
                    $this->error($file->getError());
                }
            }
            if (!empty($upload['image1'])) {
                $update_array['image1'] = $upload['image1'];
            }
            if (!empty($upload['image2'])) {
                $update_array['image2'] = $upload['image2'];
            }
            if (!empty($upload['image3'])) {
                $update_array['image3'] = $upload['image3'];
            }
            if (!empty($upload['image4'])) {
                $update_array['image4'] = $upload['image4'];
            }
            if (!empty($upload['image5'])) {
                $update_array['image5'] = $upload['image5'];
            }
            if (!empty($_FILES['business_licence']['name'])) {
                $file = request()->file('business_licence');
                $info = $file->validate(['ext'=>ALLOW_IMG_EXT])->move($upload_file, 'business_licence');
                if ($info) {
                    $upload['business_licence'] = $info->getFilename();
                } else {
                    // 上传失败获取错误信息
                    $this->error($file->getError());
                }
            }
            if (!empty($upload['business_licence'])) {
                $update_array['business_licence'] = $upload['business_licence'];
            }
            $update_array['name1'] = input('post.name1');
            $update_array['name2'] = input('post.name2');
            $update_array['name3'] = input('post.name3');
            $update_array['name4'] = input('post.name4');
            $update_array['name5'] = input('post.name5');
            $update_array['name6'] = input('post.name6');
            $update_array['baidu_ak'] = input('post.baidu_ak');
            $update_array['site_name'] = input('post.site_name');
            $update_array['icp_number'] = input('post.icp_number');
            $update_array['site_phone'] = input('post.site_phone');
            $update_array['site_tel400'] = input('post.site_tel400');
            $update_array['site_email'] = input('post.site_email');
            $update_array['flow_static_code'] = input('post.flow_static_code');
            $update_array['site_state'] = intval(input('post.site_state'));
            $update_array['cache_open'] = intval(input('post.cache_open'));
            $update_array['closed_reason'] = input('post.closed_reason');
            $update_array['hot_search'] = input('post.hot_search');
            $update_array['ios_code'] = input('post.ios_code');
            $update_array['android_code'] = input('post.android_code');
            $result = $config_model->editConfig($update_array);
            if ($result) {
                $this->log(lang('ds_edit').lang('web_set'),1);
                $this->success(lang('ds_common_save_succ'), 'Config/base');
            }else{
                $this->log(lang('ds_edit').lang('web_set'),0);
            }
        }
    }


    public function base2() {
        $config_model = model('config');
        if (!request()->isPost()) {
            $list_config = rkcache('config', true);
            $this->assign('list_config', $list_config);
            /* 设置卖家当前栏目 */
            $this->setAdminCurItem('base2');
            return $this->fetch();
        } else {
            //上传文件保存路径



            $update_array['path1_money'] = input('post.path1_money');
            $update_array['path2_money'] = input('post.path1_money');
            $update_array['path3_money'] = input('post.path1_money');
            $result = $config_model->editConfig($update_array);
            if ($result) {
                $this->log(lang('ds_edit').lang('web_set'),1);
                $this->success(lang('ds_common_save_succ'), 'Config/base2');
            }else{
                $this->log(lang('ds_edit').lang('web_set'),0);
            }
        }
    }

    public function base3() {
        $config_model = model('config');
        if (!request()->isPost()) {
            $list_config = rkcache('config', true);
            $this->assign('list_config', $list_config);
            /* 设置卖家当前栏目 */
            $this->setAdminCurItem('base3');
            return $this->fetch();
        } else {
            //上传文件保存路径
            $update_array['hot_search'] = input('post.hot_search');
            $result = $config_model->editConfig($update_array);
            if ($result) {
                $this->log(lang('ds_edit').lang('web_set'),1);
                $this->success(lang('ds_common_save_succ'), 'Config/base3');
            }else{
                $this->log(lang('ds_edit').lang('web_set'),0);
            }
        }
    }

    /**
     * 防灌水设置
     */
    public function dump(){
        $config_model = model('config');
        if (!request()->isPost()) {
            $list_config = rkcache('config', true);
            $this->assign('list_config', $list_config);
            /* 设置卖家当前栏目 */
            $this->setAdminCurItem('dump');
            return $this->fetch();
        } else {
            $update_array = array();
            $update_array['guest_comment'] = intval(input('post.guest_comment'));
            $update_array['captcha_status_login'] = intval(input('post.captcha_status_login'));
            $update_array['captcha_status_register'] = intval(input('post.captcha_status_register'));
            $update_array['captcha_status_goodsqa'] = intval(input('post.captcha_status_goodsqa'));
            $result = $config_model->editConfig($update_array);
            if ($result === true) {
                $this->log(lang('ds_edit').lang('dis_dump'), 1);
                $this->success(lang('ds_common_save_succ'));
            } else {
                $this->log(lang('ds_edit').lang('dis_dump'), 0);
                $this->error(lang('ds_common_save_fail'));
            }
        }
    }


    /*
     * 设置自动收货时间
     */
    public function auto(){
        $config_model = model('config');
        if (!request()->isPost()) {
            $list_config = rkcache('config', true);
            $this->assign('list_config', $list_config);
            /* 设置卖家当前栏目 */
            $this->setAdminCurItem('auto');
            return $this->fetch();
        } else {
            $order_auto_receive_day = intval(input('post.order_auto_receive_day'));
            $order_auto_cancel_day = intval(input('post.order_auto_cancel_day'));
            $code_invalid_refund = intval(input('post.code_invalid_refund'));
            if($order_auto_receive_day < 1 || $order_auto_receive_day>100){
                $this->error(lang('automatic_confirmation_receipt').'1-100'.lang('numerical'));
            }
            if($order_auto_cancel_day < 1 || $order_auto_cancel_day>50){
                $this->error(lang('automatic_confirmation_receipt').'1-50'.lang('numerical'));
            }
            if($code_invalid_refund < 1 || $code_invalid_refund>100){
                $this->error(lang('exchange_code_refunded_automatically').'1-100'.lang('numerical'));
            }
            $update_array['order_auto_receive_day'] = $order_auto_receive_day;
            $update_array['order_auto_cancel_day'] = $order_auto_cancel_day;
            $update_array['code_invalid_refund'] = $code_invalid_refund;
            $result = $config_model->editConfig($update_array);
            if ($result) {
                $this->log(lang('ds_edit').lang('auto_set'),1);
                $this->success(lang('ds_common_save_succ'), 'Config/auto');
            }else{
                $this->log(lang('ds_edit').lang('auto_set'),0);
                $this->error(lang('ds_common_save_fail'));
            }
        }
    }

    /**
     * 获取卖家栏目列表,针对控制器下的栏目
     */
    protected function getAdminItemList() {
        $menu_array = array(
            array(
                'name' => 'base',
                'text' => lang('ds_base'),
                'url' => url('Config/base')
            ),

            array(
                'name' => 'base2',
                'text' => '分销设置',
                'url' => url('Config/base2')
            ),
            array(
                'name' => 'base3',
                'text' => '热搜设置',
                'url' => url('Config/base3')
            ),
//            array(
//                'name' => 'dump',
//                'text' => lang('dis_dump'),
//                'url' => url('Config/dump')
//            ),
//            array(
//                'name' => 'auto',
//                'text' => lang('automatic_execution_time_setting'),
//                'url' => url('Config/auto')
//            ),
        );
        return $menu_array;
    }

}
