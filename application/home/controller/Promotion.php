<?php

/*
 * 前台促销列表
 */

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
class  Promotion extends BaseMall {

    const PAGESIZE = 16;
    
    public function _initialize() {
        parent::_initialize();
        Lang::load(APP_PATH . 'home/lang/'.config('default_lang').'/promotion.lang.php');
    }

    public function index() {
        $xianshigoods_model = model('pxianshigoods');
        $goods_model = model('goods');

        $condition = array();
        $condition['xianshigoods_state'] = 1;
        $condition['xianshigoods_starttime'] = array('elt', TIMESTAMP);
        $condition['xianshigoods_end_time'] = array('gt', TIMESTAMP);
        $gc_id = intval(input('param.gc_id'));
        if ($gc_id) {
            $condition['gc_id_1'] = intval($gc_id);
        }
        
        $goods_list = $xianshigoods_model->getXianshigoodsExtendList($condition, self::PAGESIZE, 'xianshigoods_id desc');
        
        
        $xs_goods_list = array();
        foreach ($goods_list as $k => $goods_info) {
            $xs_goods_list[$goods_info['goods_id']] = $goods_info;
            $xs_goods_list[$goods_info['goods_id']]['image_url_240'] = goods_cthumb($goods_info['goods_image'], 240);
            $xs_goods_list[$goods_info['goods_id']]['down_price'] = $goods_info['goods_price'] - $goods_info['xianshigoods_price'];
        }
        $condition = array('goods_id' => array('in', array_keys($xs_goods_list)));
        $goods_list = $goods_model->getGoodsOnlineList($condition, 'goods_id,gc_id_1,evaluation_good_star', 0, '', self::PAGESIZE, null, false);
        foreach ($goods_list as $k => $goods_info) {
            $xs_goods_list[$goods_info['goods_id']]['evaluation_good_star'] = $goods_info['evaluation_good_star'];
            if ($xs_goods_list[$goods_info['goods_id']]['gc_id_1'] != $goods_info['gc_id_1']) {
                //兼容以前版本，如果限时商品表没有保存一级分类ID，则马上保存
                $xianshigoods_model->editXianshigoods(array('gc_id_1' => $goods_info['gc_id_1']), array('xianshigoods_id' => $xs_goods_list[$goods_info['goods_id']]['xianshigoods_id']));
            }
        }

        //查询商品评分信息
        $goodsevallist = model('evaluategoods')->getEvaluategoodsList(array('geval_goodsid' => array('in', array_keys($xs_goods_list))));
        $eval_list = array();
        if (!empty($goodsevallist)) {
            foreach ($goodsevallist as $v) {
                if ($v['geval_content'] == '' || (isset($eval_list[$v['geval_goodsid']]) && count($eval_list[$v['geval_goodsid']]) >= 2))
                    continue;
                $eval_list[$v['geval_goodsid']][] = $v;
            }
        }
        $this->assign('goodsevallist', $eval_list);

        $this->assign('goods_list', $xs_goods_list);
        if (!empty(input('get.curpage'))) {
            return $this->fetch($this->template_dir . 'item');
        } else {

            //导航
            $nav_link = array(
                0 => array(
                    'title' => lang('ds_index'),
                    'link' => HOME_SITE_URL,
                ),
                1 => array(
                    'title' => lang('ds_xianshi')
                )
            );
            $this->assign('nav_link_list', $nav_link);

            //查询商品分类
            $goods_class = model('goodsclass')->getGoodsclassListByParentId(0);
            $this->assign('goods_class', $goods_class);

            $this->assign('total_page', $xianshigoods_model->page_info->render());
            return $this->fetch($this->template_dir . 'index');
        }
    }

}

?>
