<?php

namespace app\common\model;

use think\Db;
use think\Model;
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
 * 数据层模型
 */
class  Member extends Model
{

    public $page_info;

    /**
     * 会员详细信息（查库）
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @param string $field 字段
     * @return array
     */
    public function getMemberInfo($condition, $field = '*')
    {
        $res = db('member')->field($field)->where($condition)->find();
        return $res;
    }

    /**
     * 取得会员详细信息（优先查询缓存）
     * 如果未找到，则缓存所有字段
     * @access public
     * @author csdeshang
     * @param int $member_id 会员ID
     * @return array
     */
    public function getMemberInfoByID($member_id)
    {
        $member_info = rcache($member_id, 'member');
        if (empty($member_info)) {
            $member_info = $this->getMemberInfo(array('member_id' => $member_id), '*');
            wcache($member_id, $member_info, 'member');
        }
        return $member_info;
    }

    /**
     * 会员列表
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @param string $field    字段
     * @param number $page     分页
     * @param string $order    排序
     * @return array 
     */
    public function getMemberList($condition = array(), $field = '*', $page = 0, $order = 'member_id desc')
    {
        if ($page) {
            $member_list = db('member')->where($condition)->order($order)->paginate($page,false,['query' => request()->param()]
            );
            $this->page_info = $member_list;
            return $member_list->items();
        }
        else {
            return db('member')->where($condition)->order($order)->select();
        }
    }

    /**
     * 会员数量
     * @access public
     * @author csdeshang
     * @param array $condition 查询条件
     * @return int
     */
    public function getMemberCount($condition)
    {
        return db('member')->where($condition)->count();
    }

    /**
     * 编辑会员
     * @access public
     * @author csdeshang
     * @param array $condition 检索条件
     * @param array $data 数据
     * @return bool 
     */
    public function editMember($condition, $data)
    {
        $update = db('member')->where($condition)->update($data);
        if ($update && $condition['member_id']) {
            dcache($condition['member_id'], 'member');
        }
        return $update;
    }
    

    /**
     * 登录时创建会话SESSION
     * @access public
     * @author csdeshang
     * @param type $member_info 会员信息
     * @param type $reg 规则
     * @return type
     */
    public function createSession($member_info = array(), $reg = false)
    {
        if (empty($member_info) || !is_array($member_info)) {
            return;
        }
        $member_gradeinfo = model('member')->getOneMemberGrade(intval($member_info['member_exppoints']));
        $member_info = array_merge($member_info, $member_gradeinfo);
        session('is_login', '1');
        session('member_id', $member_info['member_id']);
        session('member_name', $member_info['member_name']);
        session('member_email', $member_info['member_email']);
        session('is_buy', isset($member_info['is_buylimit']) ? $member_info['is_buylimit'] : 1);
        session('avatar', $member_info['member_avatar']);
        session('level', isset($member_info['level']) ? $member_info['level'] : '');
        session('level_name', isset($member_info['level_name']) ? $member_info['level_name'] : '');
        session('member_exppoints', $member_info['member_exppoints']);  //经验值
        session('member_points', $member_info['member_points']);        //积分值
        // 头衔COOKIE
        $this->set_avatar_cookie();

        if (trim($member_info['member_qqopenid'])) {
            session('openid', $member_info['member_qqopenid']);
        }
        if (trim($member_info['member_sinaopenid'])) {
            session('slast_key.uid', $member_info['member_sinaopenid']);
        }
        if (trim($member_info['member_wxopenid'])) {
            session('wxopenid', $member_info['member_wxopenid']);
        }
        if (trim($member_info['member_wxunionid'])) {
            session('wxunionid', $member_info['member_wxunionid']);
        }

        if (!$reg) {
            //添加会员积分
            $this->addPoint($member_info);
            //添加会员经验值
            $this->addExppoint($member_info);
        }

        if (!empty($member_info['member_logintime'])) {
            $update_info = array(
                'member_loginnum' => ($member_info['member_loginnum'] + 1),
                'member_logintime' => TIMESTAMP,
                'member_old_logintime' => $member_info['member_logintime'], 
                'member_login_ip' => request()->ip(),
                'member_old_login_ip' => $member_info['member_login_ip']
            );
            $this->editMember(array('member_id' => $member_info['member_id']), $update_info);
        }
        cookie('cart_goods_num', '', -3600);
        // cookie中的cart存入数据库
        model('cart')->mergeCart($member_info);
        // cookie中的浏览记录存入数据库
        model('goodsbrowse')->mergeGoodsbrowse(session('member_id'));

        if (isset($member_info['auto_login']) && ($member_info['auto_login'] == 1)) {
            $this->auto_login();
        }
    }


    /**
     * 7天内自动登录 
     * @access public
     * @author csdeshang
     */
    public function auto_login()
    {
        // 自动登录标记 保存7天
        cookie('auto_login', ds_encrypt(session('member_id'), MD5_KEY), 7 * 24 * 60 * 60);
    }
    
    /**
     * 设置cookie
     * @access public
     * @author csdeshang
     */
    public function set_avatar_cookie()
    {
        cookie('member_avatar', session('avatar'), 365 * 24 * 60 * 60);
    }

    /**
     * 获取会员信息
     * @access public
     * @author csdeshang
     * @param    array $condition 会员条件
     * @param    string $field 显示字段
     * @return    array 数组格式的返回结果
     */
    public function infoMember($condition, $field = '*')
    {
        if (empty($condition))
            return false;
        $member_info = db('member')->where($condition)->field($field)->find();
        return $member_info;
    }

    /**
     * 注册
     * @access public
     * @author csdeshang
     * @param type $register_info
     * @return type
     */
    public function register($register_info)
    {
        // 验证用户名是否重复
        $check_member_name = $this->getMemberInfo(array('member_name' => $register_info['member_name']));
        if (is_array($check_member_name) and count($check_member_name) > 0) {
            return array('error' => '用户名已存在');
        }

        $insert_id = $this->addMember($register_info);
        if ($insert_id) {
            $this->addMemberAfter($insert_id,$register_info);
            $member_info = db('member')->where('member_id', $insert_id)->find();
            return $member_info;
        }
        else {
            return array('error' => '注册失败');
        }
    }
    /**
     * 新增用户后,赠送积分,添加相册等其他操作,主要是针对于 新增用户注册获得积分，等奖励信息的处理
     * @access public
     * @author csdeshang
     * @param type $member_id 会员ID
     * @param type $member_info 会员信息
     * @return type 
     */
    public function addMemberAfter($member_id,$member_info){
        //添加会员积分
        if (config('points_isuse')) {
            model('points')->savePointslog('regist', array('pl_memberid' => $member_id, 'pl_membername' => $member_info['member_name']), false);
            if (isset($member_info['inviter_id'])) {
                //向上查询3级更新分销成员数
                db('inviter')->where('inviter_id='.$member_info['inviter_id'])->setInc('inviter_1_quantity');
                $inviter_2=db('member')->where('member_id='.$member_info['inviter_id'])->value('inviter_id');
                if($inviter_2){
                    db('inviter')->where('inviter_id='.$inviter_2)->setInc('inviter_2_quantity');
                    $inviter_3=db('member')->where('member_id='.$inviter_2)->value('inviter_id');
                    if($inviter_3){
                        db('inviter')->where('inviter_id='.$inviter_3)->setInc('inviter_3_quantity');
                    }
                }
                //添加邀请人(推荐人)会员积分
                $inviter_name = ds_getvalue_byname('member', 'member_id', $member_info['inviter_id'], 'member_name');
                model('points')->savePointslog('inviter', array(
                    'pl_memberid' => $member_info['inviter_id'], 'pl_membername' => $inviter_name,
                    'invited' => $member_info['member_name']
                ));
            }
        }
    }

    /**
     * 注册商城会员
     * @access public
     * @author csdeshang
     * @param  array $data 会员信息
     * @return array 数组格式的返回结果
     */
    public function addMember($data)
    {
        if (empty($data)) {
            return false;
        }
        try {
            $this->startTrans();
            $member_info = array();
            $time = time();
            $rand = rand(10000,99999);
            $token = $time.$rand;
            $member_info['token'] = $token;
            $member_info['invent_code'] = $data['invent_code'];
            $member_info['member_name'] = '用户'.$data['member_name'];
            $member_info['member_mobile'] = $data['member_name'];
            $member_info['member_password'] = md5(md5(trim($data['member_password'])));
            if (isset($data['member_email'])) {
                $member_info['member_email'] = $data['member_email'];
            }
            $member_info['member_addtime'] = TIMESTAMP;
            $member_info['member_logintime'] = TIMESTAMP;
            $member_info['member_old_logintime'] = TIMESTAMP;
//            $member_info['member_login_ip'] = request()->ip();
//            $member_info['member_old_login_ip'] = $member_info['member_login_ip'];
            if (isset($data['member_truename'])) {
                $member_info['member_truename'] = $data['member_truename'];
            }

            //添加邀请人(推荐人)会员积分
            if (isset($data['inviter_id'])) {
                $member_info['inviter_id'] = $data['inviter_id'];
            }

            //  手机注册登录绑定


            $insert_id = db('member')->insertGetId($member_info);
            if (!$insert_id) {
                exception();
            }


            //添加会员积分
            if (config('points_isuse')) {
                model('points')->savePointslog('regist', array(
                    'pl_memberid' => $insert_id, 'pl_membername' => $data['member_name']
                ), false);
            }
            $this->commit();
            return $insert_id;
        } catch (Exception $e) {
            $this->rollback();
            return false;
        }
    }

    /**
     * 会员登录检查
     * @access public
     * @author csdeshang
     * @return bool
     */
    public function checkloginMember()
    {
        if (session('is_login') == '1') {
            @header("Location: " . url('Home/Member/index'));
            exit();
        }
    }

    /**
     * 检查会员是否允许举报商品
     * @access public
     * @author csdeshang
     * @param type $member_id 会员id
     * @return boolean
     */
    public function isMemberAllowInform($member_id)
    {
        $condition = array();
        $condition['member_id'] = $member_id;
        $member_info = $this->getMemberInfo($condition, 'inform_allow');
        if (intval($member_info['inform_allow']) === 1) {
            return true;
        }
        else {
            return false;
        }
    }

    
    /**
     * 添加会员积分
     * @access public
     * @author csdeshang
     * @param type $member_info 会员信息
     * @return type
     */
    public function addPoint($member_info)
    {
        if (!config('points_isuse') || empty($member_info))
            return;

        //一天内只有第一次登录赠送积分
        if (trim(@date('Y-m-d', $member_info['member_logintime'])) == trim(date('Y-m-d')))
            return;

        //加入队列
        $queue_content = array();
        $queue_content['member_id'] = $member_info['member_id'];
        $queue_content['member_name'] = $member_info['member_name'];
        \mall\queue\QueueClient::push('addPoint', $queue_content);
    }

    /**
     * 添加会员经验值
     * @access public
     * @author csdeshang
     * @param unknown $member_info 会员信息
     */
    public function addExppoint($member_info)
    {
        if (empty($member_info))
            return;

        //一天内只有第一次登录赠送经验值
        if (trim(@date('Y-m-d', $member_info['member_logintime'])) == trim(date('Y-m-d')))
            return;

        //加入队列
        $queue_content = array();
        $queue_content['member_id'] = $member_info['member_id'];
        $queue_content['member_name'] = $member_info['member_name'];
        \mall\queue\QueueClient::push('addExppoint', $queue_content);
    }

    /**
     * 取得会员安全级别
     * @access public
     * @author csdeshang
     * @param array $member_info 会员信息
     */
    public function getMemberSecurityLevel($member_info = array())
    {
        $tmp_level = 0;
        if ($member_info['member_emailbind'] == '1') {
            $tmp_level += 1;
        }
        if ($member_info['member_mobilebind'] == '1') {
            $tmp_level += 1;
        }
        if ($member_info['member_paypwd'] != '') {
            $tmp_level += 1;
        }
        return $tmp_level;
    }

    /**
     * 获得会员等级
     * @access public
     * @author csdeshang
     * @param bool $show_progress 是否计算其当前等级进度
     * @param int $exppoints 会员经验值
     * @param array $cur_level 会员当前等级
     * @return type
     */
    public function getMemberGradeArr($show_progress = false, $exppoints = 0, $cur_level = '')
    {
        $member_grade = config('member_grade') ? unserialize(config('member_grade')) : array();
        //处理会员等级进度
        if ($member_grade && $show_progress) {
            $is_max = false;
            if ($cur_level === '') {
                $cur_gradearr = $this->getOneMemberGrade($exppoints, false, $member_grade);
                $cur_level = $cur_gradearr['level'];
            }
            foreach ($member_grade as $k => $v) {
                if ($cur_level == $v['level']) {
                    $v['is_cur'] = true;
                }
                $member_grade[$k] = $v;
            }
        }
        return $member_grade;
    }



    /**
     * 获得某一会员等级
     * @access public
     * @author csdeshang
     * @param int $exppoints 会员经验值
     * @param bool $show_progress 是否计算其当前等级进度
     * @param array $member_grade 会员等级
     * @return type
     */
    public function getOneMemberGrade($exppoints, $show_progress = false, $member_grade = array())
    {
        if (!$member_grade) {
            $member_grade = config('member_grade') ? unserialize(config('member_grade')) : array();
        }
        if (empty($member_grade)) {//如果会员等级设置为空
            $grade_arr['level'] = -1;
            $grade_arr['level_name'] = '暂无等级';
            return $grade_arr;
        }

        $exppoints = intval($exppoints);

        $grade_arr = array();
        if ($member_grade) {
            foreach ($member_grade as $k => $v) {
                if ($exppoints >= $v['exppoints']) {
                    $grade_arr = $v;
                }
            }
        }
        //计算提升进度
        if ($show_progress == true) {
            if (intval($grade_arr['level']) >= (count($member_grade) - 1)) {//如果已达到顶级会员
                $grade_arr['downgrade'] = $grade_arr['level'] - 1; //下一级会员等级
                $grade_arr['downgrade_name'] = $member_grade[$grade_arr['downgrade']]['level_name'];
                $grade_arr['downgrade_exppoints'] = $member_grade[$grade_arr['downgrade']]['exppoints'];
                $grade_arr['upgrade'] = $grade_arr['level']; //上一级会员等级
                $grade_arr['upgrade_name'] = $member_grade[$grade_arr['upgrade']]['level_name'];
                $grade_arr['upgrade_exppoints'] = $member_grade[$grade_arr['upgrade']]['exppoints'];
                $grade_arr['less_exppoints'] = 0;
                $grade_arr['exppoints_rate'] = 100;
            }
            else {
                $grade_arr['downgrade'] = $grade_arr['level']; //下一级会员等级
                $grade_arr['downgrade_name'] = $member_grade[$grade_arr['downgrade']]['level_name'];
                $grade_arr['downgrade_exppoints'] = $member_grade[$grade_arr['downgrade']]['exppoints'];
                $grade_arr['upgrade'] = $member_grade[$grade_arr['level'] + 1]['level']; //上一级会员等级
                $grade_arr['upgrade_name'] = $member_grade[$grade_arr['upgrade']]['level_name'];
                $grade_arr['upgrade_exppoints'] = $member_grade[$grade_arr['upgrade']]['exppoints'];
                $grade_arr['less_exppoints'] = $grade_arr['upgrade_exppoints'] - $exppoints;
                $grade_arr['exppoints_rate'] = round(($exppoints - $member_grade[$grade_arr['level']]['exppoints']) / ($grade_arr['upgrade_exppoints'] - $member_grade[$grade_arr['level']]['exppoints']) * 100, 2);
            }
        }
        return $grade_arr;
    }

    
    /**
     * 登录生成token
     * @access public
     * @author csdeshang
     * @param type $member_id 会员id
     * @param type $member_name 会员名字
     * @param type $client 客户端
     * @return type
     */
    public function getBuyerToken($member_id, $member_name, $client,$openid='') {
        $mbusertoken_model = model('mbusertoken');
        //重新登录后以前的令牌失效
        //暂时停用
        //$condition = array();
        //$condition['member_id'] = $member_id;
        //$condition['member_clienttype'] = $client;
        //$mbusertoken_model->delMbusertoken($condition);
        //生成新的token
        $mb_user_token_info = array();
        $token = md5($member_name . strval(TIMESTAMP) . strval(rand(0, 999999)));
        $mb_user_token_info['member_id'] = $member_id;
        $mb_user_token_info['member_name'] = $member_name;
        $mb_user_token_info['member_token'] = $token;
        $mb_user_token_info['member_logintime'] = TIMESTAMP;
        $mb_user_token_info['member_clienttype'] = $client;
        if(!empty($openid)){
            $mb_user_token_info['member_openid'] = $openid;
        }

        $result = $mbusertoken_model->addMbusertoken($mb_user_token_info);
        if ($result) {
            return $token;
        } else {
            return null;
        }
    }

}
