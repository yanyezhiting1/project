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
class  Material3 extends Model {

    public $page_info;



    /**
     * 新增广告
     * @author csdeshang
     * @param array $data 参数内容
     * @return bool 布尔类型的返回结果
     */
    public function addmaterial3($data) {
        $result = db('material3')->insertGetId($data);

        return $result;
    }

    /**
     * 删除一条广告
     * @author csdeshang
     * @param array $material3_id 广告id
     * @return bool 布尔类型的返回结果
     */
    public function delmaterial3($material3_id) {
        $condition['material3_id'] = $material3_id;
        return db('material3')->where($condition)->delete();
    }



    public function getOnematerial3($condition = array()) {
        return db('material3')->where($condition)->find();
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
    public function getmaterial3List($condition = array(), $page = '', $limit = '', $orderby = 'material3_id desc') {
        if ($page) {
            $result = db('material3')->where($condition)->order($orderby)->paginate($page, false, ['query' => request()->param()]);
            $this->page_info = $result;
            return $result->items();
        } else {
            return db('material3')->where($condition)->order($orderby)->select();
        }
    }

    /**
     * 手机端广告位获取
     * @author csdeshang
     * @param array $condition 条件
     * @param str $orderby 排序
     * @return array
     */
    public function mbmaterial3list($condition,$orderby='material3_sort desc'){
         return db('material3')->alias('a')->join('__ADVPOSITION__ n','a.ap_id=n.ap_id')->where($condition)->order($orderby)->select();
    }


    /**
     * 更新记录
     * @author csdeshang
     * @param array $data 更新内容
     * @return bool
     */
    public function editmaterial3($data) {
        $material3_array = db('material3')->where('material3_id', $data['material3_id'])->find();

        return db('material3')->where('material3_id', $data['material3_id'])->update($data);
    }





}

?>
