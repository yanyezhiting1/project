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
class  Pointvoucher extends BasePointShop
{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        Lang::load(APP_PATH.'home/lang/'.config('default_lang').'/voucher.lang.php');
        if (config('voucher_allow') != 1){
            $this->error(lang('voucher_pointunavailable'),HOME_SITE_URL);
        }
    }

    public function index(){
        $this->pointvoucher();
        return $this->fetch($this->template_dir.'pointvoucher');
    }
    /**
     * 代金券列表
     */
    public function pointvoucher(){
        //查询会员及其附属信息
        parent::pointshopMInfo();

        $voucher_model = model('voucher');

        //代金券模板状态
        $templatestate_arr = $voucher_model->getTemplateState();

        //查询会员信息
        $member_info = model('member')->getMemberInfoByID(session('member_id'));

        //查询代金券列表
        $where = array();
        $where['vouchertemplate_state'] = $templatestate_arr['usable'][0];
        $where['vouchertemplate_enddate'] = array('gt',time());
        if (intval(input('price')) > 0){
            $where['vouchertemplate_price'] = intval(input('price'));
        }
        //查询仅我能兑换和所需积分
        $points_filter = array();
        if (intval(input('isable')) == 1){
            $points_filter['isable'] = $member_info['member_points'];
        }
        if (intval(input('points_min')) > 0){
            $points_filter['min'] = intval(input('points_min'));
        }
        if (intval(input('points_max')) > 0){
            $points_filter['max'] = intval(input('points_max'));
        }
        if (count($points_filter) > 0){
            asort($points_filter);
            if (count($points_filter) > 1){
                $points_filter = array_values($points_filter);
                $where['vouchertemplate_points'] = array('between',array($points_filter[0],$points_filter[1]));
            } else {
                if ($points_filter['min']){
                    $where['vouchertemplate_points'] = array('egt',$points_filter['min']);
                } elseif ($points_filter['max']) {
                    $where['vouchertemplate_points'] = array('elt',$points_filter['max']);
                } elseif ($points_filter['isable']) {
                    $where['vouchertemplate_points'] = array('elt',$points_filter['isable']);
                }
            }
        }
        //排序
        switch (input('orderby')){
            case 'exchangenumdesc':
                $orderby = 'vouchertemplate_giveout desc,';
                break;
            case 'exchangenumasc':
                $orderby = 'vouchertemplate_giveout asc,';
                break;
            case 'pointsdesc':
                $orderby = 'vouchertemplate_points desc,';
                break;
            case 'pointsasc':
                $orderby = 'vouchertemplate_points asc,';
                break;
            default:
                $orderby = '';
        }
        $orderby .= 'vouchertemplate_id desc';
        $voucherlist = $voucher_model->getVouchertemplateList($where, '*', 0, 18, $orderby);
        $this->assign('voucherlist',$voucherlist);
        $this->assign('show_page', $voucher_model->page_info->render());



        //分类导航
        $nav_link = array(
            0=>array('title'=>lang('ds_index'),'link'=>HOME_SITE_URL),
            1=>array('title'=> lang('ds_pointprod'),'link'=>url('Pointshop/index')),
            2=>array('title'=> lang('voucher_list'))
        );
        $this->assign('nav_link_list', $nav_link);
    }
    /**
     * 兑换代金券
     */
    public function voucherexchange(){
        $vid = intval(input('param.vid'));
        $result = true;
        $message = "";
        if ($vid <= 0){
            $result = false;
        }
        if ($result){
            //查询可兑换代金券模板信息
            $template_info = model('voucher')->getCanChangeTemplateInfo($vid,intval(session('member_id')));
            if ($template_info['state'] == false){
                $result = false;
                $message = $template_info['msg'];
            }else {
                //查询会员信息
                $member_info = model('member')->getMemberInfoByID(session('member_id'));
                $this->assign('member_info',$member_info);
                $this->assign('template_info',$template_info['info']);
            }
        }
        $this->assign('message',$message);
        $this->assign('result',$result);
        echo $this->fetch($this->template_dir.'exchange');exit;
    }
    /**
     * 兑换代金券保存信息
     *
     */
    public function voucherexchange_save(){
        if(session('is_login') != '1'){
            ds_json_encode(10001,'异常错误');
        }
        $vid = intval(input('post.vid'));
        if ($vid <= 0){
            ds_json_encode(10001,lang('param_error'));
        }
        $voucher_model = model('voucher');
        //验证是否可以兑换代金券
        $data = $voucher_model->getCanChangeTemplateInfo($vid,intval(session('member_id')));
        if ($data['state'] == false){
            ds_json_encode(10001,$data['msg']);
        }
        //添加代金券信息
        $data = $voucher_model->exchangeVoucher($data['info'],session('member_id'),session('member_name'));
        if ($data['state'] == true){
            ds_json_encode(10000,$data['msg']);
        } else {
            ds_json_encode(10001,$data['msg']);
        }
    }
    
}