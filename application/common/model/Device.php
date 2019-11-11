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
class  Device extends Model {

    public $page_info;



    /**
     * 新增广告
     * @author csdeshang
     * @param array $data 参数内容
     * @return bool 布尔类型的返回结果
     */
    public function adddevice($data) {
        $result = db('traffic')->insertGetId($data);
        return $result;
    }

    /**
     * 删除一条广告
     * @author csdeshang
     * @param array $device_id 广告id
     * @return bool 布尔类型的返回结果
     */
    public function deldevice($device_id) {
        $condition['id'] = $device_id;
        $device = db('traffic')->where($condition)->find();
        return db('traffic')->where($condition)->delete();
    }

    public function getOnedevice($condition = array()) {
        return db('traffic')->where($condition)->find();
    }

    /**
     * 根据条件查询多条记录
     * @author csdeshang
     * @param array $condition 查询条件
     * @param obj $page 分页页数
     * @param int $limit 数量限制
     * @param str $orderby 排序
     * @return array 二维数组
     */
    public function getdeviceList($condition = array(), $page = '', $limit = '', $orderby = 'id desc') {
        if ($page) {
            $result = db('traffic')->where($condition)->order($orderby)->paginate($page, false, ['query' => request()->param()]);
            $this->page_info = $result;
            return $result->items();
        } else {
            return db('traffic')->where($condition)->order($orderby)->select();
        }
    }




    /**
     * 更新记录
     * @author csdeshang
     * @param array $data 更新内容
     * @return bool
     */
    public function editdevice($data) {
        $device_array = db('traffic')->where('id', $data['device_id'])->find();
        if ($device_array) {
            // drop cache
            $apId = (int) $device_array['ap_id'];
            dkcache("device/{$apId}");
        }
        return db('traffic')->where('id', $data['device_id'])->update($data);
    }





}

?>
