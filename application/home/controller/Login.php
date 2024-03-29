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
class  Login extends BaseMall {
    public function _initialize() {
        parent::_initialize();
        Lang::load(APP_PATH . 'home/lang/'.config('default_lang').'/login.lang.php');
    }
    /**
     * 用户登录
     * @return
     */
    public function login() {
        $member_model = model('member');
        $inajax = input('param.inajax');
        //检查登录状态
        $member_model->checkloginMember();
        if (!request()->isPost()) {
            if ($inajax==1) {
                return $this->fetch($this->template_dir . 'login_inajax');
            } else {
                return $this->fetch($this->template_dir . 'login');
            }
        } else {
            if (config('captcha_status_login') == 1 && !captcha_check(input('post.captcha_normal'))) {
                ds_json_encode(10001,lang('image_verification_code_error'));
            }
            $data = array(
                'member_name' => input('post.member_name'),
                'member_password' => input('post.member_password'),
            );
            //验证数据  BEGIN
            $login_validate = validate('member');
            if (!$login_validate->scene('login')->check($data)) {
                ds_json_encode(10001,$login_validate->getError());
            }
            //验证数据  END
            $map = array(
                'member_name' => $data['member_name'],
                'member_password' => md5($data['member_password']),
            );
            $member_info = $member_model->getMemberInfo($map);
            if (empty($member_info) && preg_match('/^0?(13|15|17|18|14)[0-9]{9}$/i', $data['member_name'])) {
                //根据会员名没找到时查手机号
                $map = array();
                $map['member_mobile'] = $data['member_name'];
                $map['member_password'] = md5($data['member_password']);
                $member_info = db('member')->where($map)->find();
            }
            if (empty($member_info) && (strpos($data['member_name'], '@') > 0)) {
                //按邮箱和密码查询会员
                $map = array();
                $map['member_email'] = $data['member_name'];
                $map['member_password'] = md5($data['member_password']);
                $member_info = db('member')->where($map)->find();
            }
            if ($member_info) {
                if (!$member_info['member_state']) {
                    ds_json_encode(10001, lang('login_index_account_stop'));
                }
                //执行登录,赋值操作
                $member_model->createSession($member_info);
                ds_json_encode(10000,lang('login_index_login_success'));
            } else {
                ds_json_encode(10001,lang('login_index_login_fail'));
            }
        }
    }

    public function logout() {
        session(null);
        $this->redirect('Index/index');
    }

    /**
     * 会员注册页面
     *
     * @param
     * @return
     */
    public function register() {
        if (!request()->isPost()) {
            $member_model = model('member');
            $member_model->checkloginMember();
            $inviter_id = intval(input('param.inviter_id'));
            $member = db('member')->where('member_id', $inviter_id)->field('member_id,member_name')->find();
            $this->assign('member', $member);
            return $this->fetch($this->template_dir . 'register');
        } else {
            if (config('captcha_status_register') == 1 && !captcha_check(input('post.captcha_normal'))) {
                $this->error(lang('image_verification_code_error'));
            }
            $member_model = model('member');
            $member_model->checkloginMember();
            if (input('post.member_password') != input('post.member_password_confirm')) {
                $this->error(lang('login_passwords_not_match'));
            }

            $data = array(
                'member_name' => input('post.member_name'),
                'member_password' => input('post.member_password'),
                'member_password_confirm' => input('post.member_password_confirm'),
            );
            //是否开启验证码
            if (config('sms_register')) {
                $sms_mobile = trim(input('sms_mobile'));
                $sms_captcha = trim(input('sms_captcha'));
                if (strlen($sms_mobile) != 11 || strlen($sms_captcha) != 6) {
                    $this->error(lang('verify_that_length_incorrect'));
                }
                //判断验证码是否正确
                if ($sms_captcha != session('sms_captcha')) {
                    $this->error(lang('login_index_wrong_checkcode'));
                }
                if ($sms_mobile != session('sms_mobile')) {
                    $this->error(lang('receive_number_inconsistent'));
                }
                //检测手机号是否被注册
                $check_member_mobile = $member_model->getMemberInfo(array('member_mobile' => $sms_mobile));
                if (!empty($check_member_mobile)) {
                    $this->error(lang('change_another_number'));
                }
                $sms_condition = array(
                    'smslog_phone' => $sms_mobile,
                    'smslog_captcha' => $sms_captcha,
                    'smslog_type' => '1',
                );
                $smslog_model = model('smslog');
                $sms_log = $smslog_model->getSmsInfo($sms_condition);
                if (empty($sms_log) || ($sms_log['smslog_smstime'] < TIMESTAMP - 1800)) {//半小时内进行验证为有效
                    $this->error(lang('dynamic_code_expired'));
                }


                $data['member_mobile'] = $sms_mobile;
                $data['member_mobilebind'] = 1;
            }
            //验证数据  BEGIN
            $login_validate = validate('member');
            if (!$login_validate->scene('register')->check($data)) {
                $this->error($login_validate->getError());
            }
            //验证数据  END
            $inviter_id = intval(input('param.inviter_id'));
            $data['inviter_id'] = $inviter_id;

            $member_info = $member_model->register($data);
            if (!isset($member_info['error'])) {
                $member_model->createSession($member_info, true);
                $ref_url = url('Member/index');
                if (strstr(input('post.ref_url'), 'logout') === false && !empty(input('post.ref_url'))) {
                    $ref_url = input('post.ref_url');
                }
                $this->redirect($ref_url);
            } else {
                $this->error($member_info['error']);
            }
        }
    }


    /**
     * 会员名称检测
     *
     * @param
     * @return
     */
    public function check_member() {
        $member_name = input('param.member_name');
        $member_model = model('member');
        if(empty($member_name)){
            echo 'false';exit;
        }
        $check_member_name = $member_model->getMemberInfo(array('member_name' => $member_name));
        if (is_array($check_member_name) && count($check_member_name) > 0) {
            echo 'false';exit;
        } else {
            echo 'true';exit;
        }
    }

    /**
     * 电子邮箱检测
     *
     * @param
     * @return
     */
    public function check_email() {
        $member_model = model('member');
        $check_member_email = $member_model->getMemberInfo(array('member_email' => input('param.email')));
        if (is_array($check_member_email) && count($check_member_email) > 0) {
            echo 'false';exit;
        } else {
            echo 'true';exit;
        }
    }

    /**
     * 忘记密码页面
     */
    public function forget_password() {
        $this->assign('html_title', config('site_name') . ' - ' . lang('quick_login_forget'));
        return $this->fetch($this->template_dir . 'find_password');
    }

    /**
     * 邮箱绑定验证
     */
    public function bind_email() {

        $member_model = model('member');
        $uid = @base64_decode(input('param.uid'));
        $uid = ds_decrypt($uid, '');
        list($member_id, $member_email) = explode(' ', $uid);
        if (!is_numeric($member_id)) {
            $this->error(lang('validation_fails'), HOME_SITE_URL);
        }

        $member_info = $member_model->getMemberInfo(array('member_id' => $member_id), 'member_email');
        if ($member_info['member_email'] != $member_email) {
            $this->error(lang('validation_fails'), HOME_SITE_URL);
        }

        $hash = array_keys($_GET);
        $verify_code_model = model('verify_code');
        $verify_code_info = $verify_code_model->getVerifyCodeInfo(array('verify_code_type' => 5, 'verify_code_user_type' => 1, 'verify_code_user_id' => $member_id, 'verify_code_add_time' => array('>', TIMESTAMP - VERIFY_CODE_INVALIDE_MINUTE * 60)));
        if (!$verify_code_info || md5($verify_code_info['verify_code']) != $_GET[$hash['1']]) {
            $this->error(lang('validation_fails'), HOME_SITE_URL);
        }


        $update = $member_model->editMember(array('member_id' => $member_id), array('member_emailbind' => 1));
        if (!$update) {
            $this->error(lang('system_error'), HOME_SITE_URL);
        }

        $this->success(lang('successful_email_setting'), url('Membersecurity/index'));
    }

}

?>
