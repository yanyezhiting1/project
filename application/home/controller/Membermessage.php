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
class  Membermessage extends BaseMember
{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        Lang::load(APP_PATH . 'home/lang/' . config('default_lang') . '/membermessage.lang.php');
    }

    /**
     * 收到(普通)站内信列表
     *
     * @param
     * @return
     */
    public function message()
    {
        $message_model = model('message');

        $message_array = $message_model->getMessageList(array('message_type' => '2', 'to_member_id_common' => session('member_id'), 'no_message_state' => '2'), 10);
        $this->assign('show_page', $message_model->page_info->render());
        $this->assign('message_array', $message_array);

        // 新消息数量
        $this->showReceivedNewNum();

        $this->assign('drop_type', 'msg_list');
        $this->setMemberCurItem('message');
        $this->setMemberCurMenu('member_message');

        return $this->fetch($this->template_dir . 'message');
    }

    /**
     * 收到(私信)站内信列表
     *
     * @param
     * @return
     */
    public function personalmsg()
    {
        $message_model = model('message');
        $message_array = $message_model->getMessageList(array('message_type' => '0', 'to_member_id_common' => session('member_id'), 'no_message_state' => '2'), 10);
        $this->assign('show_page', $message_model->page_info->render());
        $this->assign('message_array', $message_array);

        // 新消息数量
        $this->showReceivedNewNum();

        $this->assign('drop_type', 'msg_list');
        $this->setMemberCurItem('close');
        $this->setMemberCurMenu('member_message');
        return $this->fetch($this->template_dir . 'message');
    }

    /**
     * 查询会员是否允许发送站内信
     *
     * @return bool
     */
    private function allowSendMessage($member_id)
    {
        $member_info = model('member')->getMemberInfoByID($member_id);
        if ($member_info['is_allowtalk'] == '1') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 私人站内信列表
     *
     * @param
     * @return
     */
    public function privatemsg()
    {
        $message_model = model('message');
        $message_array = $message_model->getMessageList(array('message_type_in' => '0,2', 'from_member_id' => session('member_id'), 'no_message_state' => '1'), 10);
        $this->assign('show_page', $message_model->page_info->render());
        $this->assign('message_array', $message_array);

        // 新消息数量
        $this->showReceivedNewNum();

        $this->assign('drop_type', 'msg_private');
        $this->setMemberCurItem('private');
        $this->setMemberCurMenu('member_message');
        return $this->fetch($this->template_dir . 'sendlist');
    }

    /**
     * 系统站内信列表
     *
     * @param
     * @return
     */
    public function systemmsg()
    {
        $message_model = model('message');

        $message_array = $message_model->getMessageList(array('from_member_id' => '0', 'message_type' => '1', 'to_member_id' => session('member_id'), 'no_del_member_id' => session('member_id')), 10);
        if (!empty($message_array) && is_array($message_array)) {
            foreach ($message_array as $k => $v) {
                $v['message_open'] = '0';
                if (!empty($v['read_member_id'])) {
                    $tmp_readid_arr = explode(',', $v['read_member_id']);
                    if (in_array(session('member_id'), $tmp_readid_arr)) {
                        $v['message_open'] = '1';
                    }
                }
                $v['from_member_name'] = lang('home_message_system_message');
                $message_array[$k] = $v;
            }
        }
        $this->assign('show_page', $message_model->page_info->render());
        $this->assign('message_array', $message_array);

        // 新消息数量
        $this->showReceivedNewNum();

        $this->assign('drop_type', 'msg_system');
        $this->setMemberCurItem('system');
        $this->setMemberCurMenu('member_message');
        return $this->fetch($this->template_dir . 'message');
    }

    /**
     * 发送站内信页面
     *
     * @param
     * @return
     */
    public function sendmsg()
    {
        //查询会员是否允许发送站内信
        $isallowsend = $this->allowSendMessage(session('member_id'));
        if (!$isallowsend) {
            $this->error(lang('home_message_noallowsend'));
        }
        $member_model = model('member');
        $member_id = intval(input('param.member_id'));
        if ($member_id > 0) {
            //连接发放站内信页面
            $member_info = $member_model->getMemberInfoByID($member_id);
            if (empty($member_info)) {
                $this->error(lang('param_error'));
            }
            $member_name_string = $member_info['member_name'];
            $this->assign('member_name', $member_name_string);
        }
        //批量给好友发放站内信页面
        $friend_model = model('snsfriend');
        $friend_list = $friend_model->getSnsfriendList(array('friend_frommid' => session('member_id')));
        $this->assign('friend_list', $friend_list);

        // 新消息数量
        $this->showReceivedNewNum();
        $this->setMemberCurItem('sendmsg');
        $this->setMemberCurMenu('member_message');
        return $this->fetch($this->template_dir . 'send');
    }

    /**
     * 站内信保存操作
     *
     * @param
     * @return
     */
    public function savemsg()
    {
        //查询会员是否允许发送站内信
        $isallowsend = $this->allowSendMessage(session('member_id'));
        if (!$isallowsend) {
            ds_json_encode(10001,lang('home_message_noallowsend'));
        }
        $data = [
            'to_member_name' => input('post.to_member_name'),
            'msg_content' => input('post.msg_content')
        ];
        $message_validate = validate('message');
        if (!$message_validate->scene('savemsg')->check($data)) {
            ds_json_encode(10001,$message_validate->getError());
        }
        $msg_content = trim(input('post.msg_content'));
        $membername_arr = explode(',', input('post.to_member_name'));

        if (in_array(session('member_name'), $membername_arr)) {
            unset($membername_arr[array_search(session('member_name'), $membername_arr)]);
        }
        //查询有效会员
        $member_model = model('member');
        $member_list = $member_model->getMemberList(array('member_name' => array('in', $membername_arr)));

        if (!empty($member_list)) {
            $message_model = model('message');
            foreach ($member_list as $k => $v) {
                $insert_arr = array();
                $insert_arr['from_member_id'] = session('member_id');
                $insert_arr['from_member_name'] = session('member_name');
                $insert_arr['member_id'] = $v['member_id'];
                $insert_arr['to_member_name'] = $v['member_name'];
                $insert_arr['msg_content'] = $msg_content;
                $insert_arr['message_type'] = intval(input('post.msg_type'));
                $message_model->addMessage($insert_arr);
            }
        } else {
            ds_json_encode(10001,lang('home_message_receiver_error'));
        }
        ds_json_encode(10000,lang('home_message_send_success'));
    }

    /**
     * 普通站内信查看操作
     *
     * @param
     * @return
     */
    public function showmsgcommon()
    {
        $message_model = model('message');
        $message_id = intval(input('param.message_id'));
        $drop_type = trim(input('param.type'));
        if (!in_array($drop_type, array('msg_list')) || $message_id <= 0) {
            $this->error(lang('param_error'));
        }
        //查询站内信
        $param = array();
        $param['message_id'] = "$message_id";
        $param['to_member_id_common'] = session('member_id');
        $param['no_message_state'] = "2";
        $message_info = $message_model->getOneMessage($param);
        if (empty($message_info)) {
            $this->error(lang('home_message_no_record'));
        }
        unset($param);
        if ($message_info['message_parent_id'] > 0) {
            //查询该站内信的父站内信
            $parent_array = $message_model->getOneMessage(array('message_id' => "{$message_info['message_parent_id']}", 'message_type' => '0', 'no_message_state' => '2'));
            //查询该站内信的回复站内信
            $reply_array = $message_model->getMessageList(array('message_parent_id' => "{$message_info['message_parent_id']}", 'message_type' => '0', 'no_message_state' => '2'));
        } else {//此信息为父站内信
            $parent_array = $message_info;
            //查询回复站内信
            $reply_array = $message_model->getMessageList(array('message_parent_id' => "$message_id", 'message_type' => '0', 'no_message_state' => '2'));
        }
        //处理获取站内信数组
        $message_list = array();
        if (!empty($reply_array)) {
            foreach ($reply_array as $k => $v) {
                $message_list[$v['message_id']] = $v;
            }
        }
        if (!empty($parent_array)) {
            $message_list[$parent_array['message_id']] = $parent_array;
        }
        unset($parent_array);
        unset($reply_array);
        //更新已读状态
        $messageid_arr = array_keys($message_list);
        if (!empty($messageid_arr)) {
            $messageid_str = "'" . implode("','", $messageid_arr) . "'";
            $message_model->editCommonMessage(array('message_open' => '1'), array('message_id_in' => "$messageid_str"));
        }
        //更新未读站内信数量cookie值
        $cookie_name = 'msgnewnum' . session('member_id');
        $countnum = $message_model->getNewMessageCount(session('member_id'));
        Cookie($cookie_name, $countnum, 2 * 3600);//保存2小时
        $this->assign('message_num', $countnum);
        $this->assign('message_id', $message_id);//点击的该条站内信编号
        $this->assign('message_list', $message_list);//站内信列表

        // 新消息数量
        $this->showReceivedNewNum();

        $this->assign('drop_type', $drop_type);
        $this->setMemberCurMenu('member_message');
        $this->setMemberCurItem('showmsg');
        return $this->fetch($this->template_dir . 'view');
    }

    /**
     * 系统站内信查看操作
     *
     * @param
     * @return
     */
    public function showmsgbatch()
    {
        $message_model = model('message');
        $message_id = intval(input('param.message_id'));
        $drop_type = trim(input('param.type'));

        if (!in_array($drop_type, array('msg_system')) || $message_id <= 0) {
            $this->error(lang('param_error'));
        }
        //查询站内信
        $param = array();
        $param['message_id'] = $message_id;
        $param['to_member_id'] = session('member_id');
        $param['no_del_member_id'] = session('member_id');
        $message_info = $message_model->getOneMessage($param);
        if (empty($message_info)) {
            $this->error(lang('home_message_no_record'));
        }
        if ($drop_type == 'msg_system') {
            $message_info['from_member_name'] = lang('home_message_system_message');
        }
        $message_list[0] = $message_info;
        $this->assign('message_list', $message_list);//站内信列表
        //更新为已读信息
        $tmp_readid_str = '';
        if (!empty($message_info['read_member_id'])) {
            $tmp_readid_arr = explode(',', $message_info['read_member_id']);
            if (!in_array(session('member_id'), $tmp_readid_arr)) {
                $tmp_readid_arr[] = session('member_id');
            }
            foreach ($tmp_readid_arr as $readid_k => $readid_v) {
                if ($readid_v == '') {
                    unset($tmp_readid_arr[$readid_k]);
                }
            }
            $tmp_readid_arr = array_unique($tmp_readid_arr);//去除相同
            sort($tmp_readid_arr);//排序
            $tmp_readid_str = "," . implode(',', $tmp_readid_arr) . ",";
        } else {
            $tmp_readid_str = "," . session('member_id') . ",";
        }
        $message_model->editCommonMessage(array('read_member_id' => $tmp_readid_str), array('message_id' => "{$message_id}"));
        //更新未读站内信数量cookie值
        $cookie_name = 'msgnewnum' . session('member_id');
        $countnum = $message_model->getNewMessageCount(session('member_id'));
        Cookie($cookie_name, $countnum, 2 * 3600);//保存2小时
        $this->assign('message_num', $countnum);

        // 新消息数量
        $this->showReceivedNewNum();

        $this->assign('drop_type', $drop_type);
        $this->setMemberCurMenu('member_message');
        $this->setMemberCurItem('system');
        return $this->fetch($this->template_dir . 'view');
    }

    /**
     * 短消息回复保存
     *
     * @param
     * @return
     */
    public function savereply()
    {
        //查询会员是否允许发送站内信
        $isallowsend = $this->allowSendMessage(session('member_id'));
        if (!$isallowsend) {
            ds_json_encode(10001, lang('home_message_noallowsend'));
        }
        if (request()->isPost()) {
            $message_id = intval(input('post.message_id'));
            if ($message_id <= 0) {
                ds_json_encode(10001,lang('param_error'));
            }

            if (empty(input('post.msg_content'))) {
                ds_json_encode(10001,lang('home_message_reply_content_null'));
            }
            $message_model = model('message');
            //查询站内信
            $param = array();
            $param['message_id'] = "$message_id";
            $param['no_message_state'] = "2";//未删除
            $message_info = $message_model->getOneMessage($param);
            if (empty($message_info)) {
                ds_json_encode(10001,lang('home_message_no_record'));
            }
            //不能回复自己的站内信
            if ($message_info['from_member_id'] == session('member_id')) {
                ds_json_encode(10001,lang('home_message_no_record'));
            }
            $insert_arr = array();
            if ($message_info['message_parent_id'] > 0) {
                $insert_arr['message_parent_id'] = $message_info['message_parent_id'];
            } else {
                $insert_arr['message_parent_id'] = $message_info['message_id'];
            }
            $insert_arr['from_member_id'] = session('member_id');
            $insert_arr['from_member_name'] = session('member_name');
            $insert_arr['member_id'] = $message_info['from_member_id'];
            $insert_arr['to_member_name'] = $message_info['from_member_name'];
            $insert_arr['msg_content'] = input('post.msg_content');
            $insert_state = $message_model->addMessage($insert_arr);
            if ($insert_state) {
                //更新父类站内信更新时间
                $update_arr = array();
                $update_arr['message_update_time'] = time();
                $update_arr['message_open'] = 1;
                $message_model->editCommonMessage($update_arr, array('message_id' => "{$insert_arr['message_parent_id']}"));
            }
            ds_json_encode(10000,lang('home_message_send_success'));
        } else {
            ds_json_encode(10001,lang('home_message_reply_command_wrong'));
        }
    }

    /**
     * 删除普通信
     */
    public function dropcommonmsg()
    {
        $message_id = trim(input('param.message_id'));
        $drop_type = trim(input('param.drop_type'));
        if (!in_array($drop_type, array('msg_private', 'msg_list', 'sns_msg')) || empty($message_id)) {
            ds_json_encode(10001, lang('param_error'));
        }
        $messageid_arr = explode(',', $message_id);
        $messageid_str = '';
        if (!empty($messageid_arr)) {
            $messageid_str = "'" . implode("','", $messageid_arr) . "'";
        }
        $message_model = model('message');
        $param = array('message_id_in' => $messageid_str);
        if ($drop_type == 'msg_private') {
            $param['from_member_id'] = session('member_id');
        } elseif ($drop_type == 'msg_list') {
            $param['to_member_id_common'] = session('member_id');
        } elseif ($drop_type == 'sns_msg') {
            $param['from_to_member_id'] = session('member_id');
        }
        $drop_state = $message_model->delCommonMessage($param, $drop_type);
        if ($drop_state) {
            //更新未读站内信数量cookie值
            $cookie_name = 'msgnewnum' . session('member_id');
            $countnum = $message_model->getNewMessageCount(session('member_id'));
            cookie($cookie_name, $countnum, 2 * 3600);//保存2小时
            ds_json_encode(10000, lang('home_message_delete_success'));
        } else {
            ds_json_encode(10001, lang('home_message_delete_fail'));
        }
    }

    /**
     * 删除批量站内信
     */
    public function dropbatchmsg()
    {
        $message_id = trim(input('param.message_id'));
        $drop_type = trim(input('param.drop_type'));
        if (!in_array($drop_type, array('msg_system')) || empty($message_id)) {
            ds_json_encode(10001, lang('home_message_delete_request_wrong'));
        }
        $messageid_arr = explode(',', $message_id);
        $messageid_str = '';
        if (!empty($messageid_arr)) {
            $messageid_str = "'" . implode("','", $messageid_arr) . "'";
        }
        $message_model = model('message');
        $param = array('message_id_in' => $messageid_str);
        if ($drop_type == 'msg_system') {
            $param['message_type'] = '1';
            $param['from_member_id'] = '0';
        }
        $drop_state = $message_model->delBatchMessage($param, session('member_id'));
        if ($drop_state) {
            //更新未读站内信数量cookie值
            $cookie_name = 'msgnewnum' . session('member_id');
            $countnum = $message_model->getNewMessageCount(session('member_id'));
            Cookie($cookie_name, $countnum, 2 * 3600);//保存2小时
            ds_json_encode(10000, lang('home_message_delete_success'));
        } else {
            ds_json_encode(10001, lang('home_message_delete_fail'));
        }
    }

    /**
     * 消息接收设置
     *
     * 注意：由于用户消息模板不是循环输出，所以每增加一种消息模板，
     *     都需要在模板（member_message_setting）中需要手工添加该消息模板的选项卡，
     *     在control部分也要添加相关的验证，否则默认开启无法关闭。
     */
    public function setting()
    {
        $membermsgsetting_model = model('membermsgsetting');
        $insert = array(
            // 付款成功提醒
            array(
                'membermt_code' => 'order_payment_success', 'member_id' => session('member_id'),
                'membermt_isreceive' => intval(input('post.order_payment_success', '0'))
            ), // 商品出库提醒
            array(
                'membermt_code' => 'order_deliver_success', 'member_id' => session('member_id'),
                'membermt_isreceive' => intval(input('post.order_deliver_success', '0'))
            ), // 余额变动提醒
            array(
                'membermt_code' => 'predeposit_change', 'member_id' => session('member_id'),
                'membermt_isreceive' => intval(input('post.predeposit_change', '0'))
            ), // 充值卡余额变动提醒
            array(
                'membermt_code' => 'recharge_card_balance_change', 'member_id' => session('member_id'),
                'membermt_isreceive' => intval(input('post.recharge_card_balance_change', '0'))
            ), // 代金券使用提醒
            array(
                'membermt_code' => 'voucher_use', 'member_id' => session('member_id'),
                'membermt_isreceive' => intval(input('post.voucher_use', '0'))
            ), // 退款退货提醒
            array(
                'membermt_code' => 'refund_return_notice', 'member_id' => session('member_id'),
                'membermt_isreceive' => intval(input('post.refund_return_notice', '0'))
            ), // 到货通知提醒
            array(
                'membermt_code' => 'arrival_notice', 'member_id' => session('member_id'),
                'membermt_isreceive' => intval(input('post.arrival_notice', '0'))
            ), // 商品咨询回复提醒
            array(
                'membermt_code' => 'consult_goods_reply', 'member_id' => session('member_id'),
                'membermt_isreceive' => intval(input('post.consult_goods_reply', '0'))
            ), // 平台客服回复提醒
            array(
                'membermt_code' => 'consult_mall_reply', 'member_id' => session('member_id'),
                'membermt_isreceive' => intval(input('post.consult_mall_reply', '0'))
            ), // 代金券即将到期
            array(
                'membermt_code' => 'voucher_will_expire', 'member_id' => session('member_id'),
                'membermt_isreceive' => intval(input('post.voucher_will_expire', '0'))
            ), // 兑换码即将到期提醒
            array(
                'membermt_code' => 'vr_code_will_expire', 'member_id' => session('member_id'),
                'membermt_isreceive' => intval(input('post.vr_code_will_expire', '0'))
            ),
        );
        if (request()->isPost()) {
            db('membermsgsetting')->where(array('member_id' => session('member_id')))->delete();
            $result = $membermsgsetting_model->addMembermsgsettingAll($insert);
            if ($result) {
                ds_json_encode(10000, lang('ds_common_save_succ'));
            } else {
                ds_json_encode(10001, lang('ds_common_save_fail'));
            }
        }
        // 新消息数量
        $this->showReceivedNewNum();

        $setting_list = $membermsgsetting_model->getMembermsgsettingList(array('member_id' => session('member_id')));
        if (empty($setting_list)) {
            $setting_list = $insert;
        }
        $setting_array = array();
        if (!empty($setting_list)) {
            foreach ($setting_list as $val) {
                $setting_array[$val['membermt_code']] = intval($val['membermt_isreceive']);
            }
        }
        $this->assign('setting_array', $setting_array);

        $this->setMemberCurItem('setting');
        $this->setMemberCurMenu('member_message');
        return $this->fetch($this->template_dir . 'setting');
    }

    /**
     * 统计未读消息
     */
    private function showReceivedNewNum()
    {
        //查询新接收到普通的消息
        $newcommon = $this->receivedCommonNewNum();
        $this->assign('newcommon', $newcommon);
        //查询新接收到系统的消息
        $newsystem = $this->receivedSystemNewNum();
        $this->assign('newsystem', $newsystem);
        //查询新接收到卖家的消息
        $newpersonal = $this->receivedPersonalNewNum();
        $this->assign('newpersonal', $newpersonal);
        //查询会员是否允许发送站内信
        $isallowsend = $this->allowSendMessage(session('member_id'));
        $this->assign('isallowsend', $isallowsend);
    }

    /**
     * 统计收到站内信未读条数
     *
     * @return int
     */
    private function receivedCommonNewNum()
    {
        $message_model = model('message');
        $countnum = $message_model->getMessageCount(array('message_type' => '2', 'to_member_id_common' => session('member_id'), 'no_message_state' => '2', 'message_open_common' => '0'));
        return $countnum;
    }

    /**
     * 统计系统站内信未读条数
     *
     * @return int
     */
    private function receivedSystemNewNum()
    {
        $message_model = model('message');
        $condition_arr = array();
        $condition_arr['message_type'] = '1';//系统消息
        $condition_arr['to_member_id'] = session('member_id');
        $condition_arr['no_del_member_id'] = session('member_id');
        $condition_arr['no_read_member_id'] = session('member_id');
        $countnum = $message_model->getMessageCount($condition_arr);
        return $countnum;
    }

    /**
     * 统计私信未读条数
     *
     * @return int
     */
    private function receivedPersonalNewNum()
    {
        $message_model = model('message');
        $countnum = $message_model->getMessageCount(array('message_type' => '0', 'to_member_id_common' => session('member_id'), 'no_message_state' => '2', 'message_open_common' => '0'));
        return $countnum;
    }

    /**
     * 用户中心右边，小导航
     *
     * @param string $menu_key 当前导航的menu_key
     * @return
     */
    protected function getMemberItemList()
    {
        $menu_array = array(
//            1 => array(
//                'name' => 'message', 'text' => lang('home_message_received_message'),
//                'url' => url('Membermessage/message')
//            ), 2 => array(
//                'name' => 'private', 'text' => lang('home_message_private_message'),
//                'url' => url('Membermessage/privatemsg')
//            ),
            3 => array(
                'name' => 'system', 'text' => lang('home_message_system_message'),
                'url' => url('Membermessage/systemmsg')
            ),
//            4 => array(
//                'name' => 'close', 'text' => lang('home_message_close'),
//                'url' => url('Membermessage/personalmsg')
//            ),
            5 => array(
                'name' => 'setting', 'text' => lang('receiving_set'), 'url' => url('Membermessage/setting')
            )
        );
        if (request()->action() == 'sendmsg') {
            $menu_array[] = array(
                'name' => 'sendmsg', 'text' => lang('home_message_send_message'),
                'url' => url('Membermessage/sendmsg')
            );
        } elseif (request()->action() == 'showmsg') {
            $menu_array[] = array(
                'name' => 'showmsg', 'text' => lang('home_message_view_message'), 'url' => '#'
            );
        }
        return $menu_array;
    }
}