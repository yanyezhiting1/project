<?php

namespace app\common\model;
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
class  Inviter extends Model {

    public $page_info;


    /**
     * 增加帮助
     * @access public
     * @author csdeshang
     * @param type $inviter_array 帮助内容
     * @param type $upload_ids 更新ID
     * @return type
     */
    public function addInviter($inviter_array) {
        $inviter_id = db('inviter')->insertGetId($inviter_array);

        return $inviter_id;
    }


    /**
     * 修改帮助记录
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $data 数据
     * @return boolean
     */
    public function editInviter($condition, $data) {
        if (empty($condition)) {
            return false;
        }
        if (is_array($data)) {
            $result = db('inviter')->where($condition)->update($data);
            return $result;
        } else {
            return false;
        }
    }

    public function getInviterInfo($condition,$fields = 'm.member_name,i.*'){
        if (empty($condition)) {
            return false;
        }
        $result = db('inviter')->alias('i')->join('__MEMBER__ m', 'i.inviter_id=m.member_id')->field($fields)->where($condition)->find();
        return $result;
    }

    /**
     * 帮助记录
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $page 分页
     * @param type $limit 限制
     * @param type $fields 字段
     * @return array
     */
    public function getInviterList($condition = array(), $page = '', $limit = '', $fields = '*') {
        if($page) {
            $res=db('inviter')->alias('i')->join('__MEMBER__ m', 'i.inviter_id=m.member_id')->field($fields)->where($condition)->order('inviter_applytime desc')->paginate($page,false,['query' => request()->param()]);
            $this->page_info=$res;
            $result=$res->items();
        }else{
            $result = db('inviter')->alias('i')->join('__MEMBER__ m', 'i.inviter_id=m.member_id')->field($fields)->where($condition)->limit($limit)->order('inviter_applytime desc')->select();
        }
        return $result;
    }

}
