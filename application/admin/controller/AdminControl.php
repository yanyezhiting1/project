<?php

namespace app\admin\controller;

use think\Controller;
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
class  AdminControl extends Controller {

    /**
     * 管理员资料 name id group
     */
    protected $admin_info;

    protected $permission;
    public function _initialize() {
        $this->admin_info = $this->systemLogin();
        $config_list = rkcache('config', true);
        config($config_list);
        //引用语言包的类型 针对于前端模板 中文\英文
//        if (in_array(cookie('ds_admin_lang'), array('zh-cn', 'en-us'))) {
//            config('default_lang', cookie('ds_admin_lang'));
//        }
        
        //引用语言包的类型 针对于数据库读写类型 中文\英文
        if (in_array(cookie('ds_admin_sql_lang'), array('zh-cn', 'en-us'))) {
            config('default_sql_lang', cookie('ds_admin_sql_lang'));
        }else{
            config('default_sql_lang', 'zh-cn');
        }
        
        if ($this->admin_info['admin_id'] != 1) {
            // 验证权限
            $this->checkPermission();
        }
        $this->setMenuList();
    }

    /**
     * 取得当前管理员信息
     *
     * @param
     * @return 数组类型的返回结果
     */
    protected final function getAdminInfo() {
        return $this->admin_info;
    }

    /**
     * 系统后台登录验证
     *
     * @param
     * @return array 数组类型的返回结果
     */
    protected final function systemLogin() {
        $admin_info = array(
            'admin_id' => session('admin_id'),
            'admin_name' => session('admin_name'),
            'admin_gid' => session('admin_gid'),
            'admin_is_super' => session('admin_is_super'),
        );
        if (empty($admin_info['admin_id']) || empty($admin_info['admin_name']) || !isset($admin_info['admin_gid']) || !isset($admin_info['admin_is_super'])) {
            session(null);
            $this->redirect('Admin/Login/index');
        }

        return $admin_info;
    }

    public function setMenuList() {
        $menu_list = $this->menuList();

        $menu_list=$this->parseMenu($menu_list);
        $this->assign('menu_list', $menu_list);
    }

    /**
     * 验证当前管理员权限是否可以进行操作
     *
     * @param string $link_nav
     * @return
     */
    protected final function checkPermission($link_nav = null){
        if ($this->admin_info['admin_is_super'] == 1) return true;

        $controller = request()->controller();
        $action = request()->action();
        if (empty($this->permission)){
            
            $admin_model=model('admin');
            $gadmin = $admin_model->getOneGadmin(array('gid'=>$this->admin_info['admin_gid']));
            $permission = ds_decrypt($gadmin['glimits'],MD5_KEY.md5($gadmin['gname']));
            $this->permission = $permission = explode('|',$permission);
        }else{
            $permission = $this->permission;
        }
        //显示隐藏小导航，成功与否都直接返回
        if (is_array($link_nav)){
            if (!in_array("{$link_nav['controller']}.{$link_nav['action']}",$permission) && !in_array($link_nav['controller'],$permission)){
                return false;
            }else{
                return true;
            }
        }
        //以下几项不需要验证
        $tmp = array('Index','Dashboard','Login');
        if (in_array($controller,$tmp)){
            return true;
        }
        if (in_array($controller,$permission) || in_array("$controller.$action",$permission)){
            return true;
        }else{
            $extlimit = array('ajax','export_step1');
            if (in_array($action,$extlimit) && (in_array($controller,$permission) || strpos(serialize($permission),'"'.$controller.'.'))){
                return true;
            }
            //带前缀的都通过
            foreach ($permission as $v) {
                if (!empty($v) && strpos("$controller.$action",$v.'_') !== false) {
                    return true;break;
                }
            }
        }
        $this->error(lang('ds_assign_right'),'Dashboard/welcome');
    }

    /**
     * 过滤掉无权查看的菜单
     *
     * @param array $menu
     * @return array
     */
    private final function parseMenu($menu = array()) {
        if ($this->admin_info['admin_is_super'] == 1) {
            return $menu;
        }
        foreach ($menu as $k => $v) {
            foreach ($v['children'] as $ck => $cv) {
                $tmp = explode(',', $cv['args']);
                //以下几项不需要验证
                $except = array('Index', 'Dashboard', 'Login');
                if (in_array($tmp[1], $except))
                    continue;
                if (!in_array($tmp[1], array_values($this->permission))) {
                    unset($menu[$k]['children'][$ck]);
                }
            }
            if (empty($menu[$k]['children'])) {
                unset($menu[$k]);
                unset($menu[$k]['children']);
            }
        }
        return $menu;
    }

    /**
     * 记录系统日志
     *
     * @param $lang 日志语言包
     * @param $state 1成功0失败null不出现成功失败提示
     * @param $admin_name
     * @param $admin_id
     */
    protected final function log($lang = '', $state = 1, $admin_name = '', $admin_id = 0) {
        if ($admin_name == '') {
            $admin_name = session('admin_name');
            $admin_id = session('admin_id');
        }
        $data = array();
        if (is_null($state)) {
            $state = null;
        } else {
            $state = $state ? '' : lang('ds_fail');
        }
        $data['adminlog_content'] = $lang . $state;
        $data['adminlog_time'] = TIMESTAMP;
        $data['admin_name'] = $admin_name;
        $data['admin_id'] = $admin_id;
        $data['adminlog_ip'] = request()->ip();
        $data['adminlog_url'] = request()->controller() . '&' . request()->action();
        
        $adminlog_model=model('adminlog');
        return $adminlog_model->addAdminlog($data);
    }

    /**
     * 添加到任务队列
     *
     * @param array $goods_array
     * @param boolean $ifdel 是否删除以原记录
     */
    protected function addcron($data = array(), $ifdel = false) {
        $cron_model = model('cron');
        if (isset($data[0])) { // 批量插入
            $where = array();
            foreach ($data as $k => $v) {
                if (isset($v['content'])) {
                    $data[$k]['content'] = serialize($v['content']);
                }
                // 删除原纪录条件
                if ($ifdel) {
                    $where[] = '(type = ' . $data['type'] . ' and exeid = ' . $data['exeid'] . ')';
                }
            }
            // 删除原纪录
            if ($ifdel) {
                $cron_model->delCron(implode(',', $where));
            }
            $cron_model->addCronAll($data);
        } else { // 单条插入
            if (isset($data['content'])) {
                $data['content'] = serialize($data['content']);
            }
            // 删除原纪录
            if ($ifdel) {
                $cron_model->delCron(array('type' => $data['type'], 'exeid' => $data['exeid']));
            }
            $cron_model->addCron($data);
        }
    }

    /**
     * 当前选中的栏目
     */
    protected function setAdminCurItem($curitem = '') {
        $this->assign('admin_item', $this->getAdminItemList());
        $this->assign('curitem', $curitem);
    }

    /**
     * 获取卖家栏目列表,针对控制器下的栏目
     */
    protected function getAdminItemList() {
        return array();
    }

    /*
     * 侧边栏列表
     */

    function menuList() {
        return array(
            'dashboard' => array(
                'name' => 'dashboard',
                'text' => lang('ds_dashboard'),

                'children' => array(
                    'welcome' => array(
                        'ico'=>"&#xe70b;",
                        'text' => lang('ds_welcome'),
                        'args' => 'welcome,Dashboard,dashboard',
                    ),
                    /*
                    'aboutus' => array(
                        'text' => lang('ds_aboutus'),
                        'args' => 'aboutus,dashboard,dashboard',
                    ),
                     */
                    'config' => array(
                        'ico'=>'&#xe6e0;',
                        'text' => lang('ds_base'),
                        'args' => 'base,Config,dashboard',
                    ),
//                    'member' => array(
//                        'ico'=>'&#xe667;',
//                        'text' => lang('ds_member_manage'),
//                        'args' => 'member,Member,dashboard',
//                    ),
                    
                ),
            ),
            'setting' => array(
                'name' => 'setting',
                'text' => lang('ds_set'),
                'children' => array(
                    'config' => array(
                        'ico'=>'&#xe6e0;',
                        'text' => lang('ds_base'),
                        'args' => 'base,Config,setting',
                    ),
//                  'account' => array(
//                      'ico'=>'&#xe678;',
//                      'text' => lang('ds_account'),
//                      'args' => 'qq,Account,setting',
//                  ),
//                  'upload_set' => array(
//                      'ico'=>'&#xe72a;',
//                      'text' => lang('ds_upload_set'),
//                      'args' => 'default_thumb,Upload,setting',
//                  ),
//                  'seo' => array(
//                      'ico'=>'&#xe6e0;',
//                      'text' => lang('ds_seo_set'),
//                      'args' => 'index,Seo,setting',
//                  ),
//                  'message' => array(
//                      'ico'=>'&#xe71b;',
//                      'text' => lang('ds_message'),
//                      'args' => 'email,Message,setting',
//                  ),
//                  'payment' => array(
//                      'ico'=>'&#xe74d;',
//                      'text' => lang('ds_payment'),
//                      'args' => 'index,Payment,setting',
//                  ),
                    'admin' => array(
                        'ico'=>'&#xe67b;',
                        'text' => lang('ds_admin'),
                        'args' => 'admin,Admin,setting',
                    ),
//                  'express' => array(
//                      'ico'=>'&#xe69e;',
//                      'text' => lang('ds_express'),
//                      'args' => 'index,Express,setting',
//                  ),
//                  'waybill' => array(
//                      'ico'=>'&#xe71f;',
//                      'text' => lang('ds_waybill'),
//                      'args' => 'index,Waybill,setting',
//                  ),
//                  'Region' => array(
//                      'ico'=>'&#xe69e;',
//                      'text' => lang('ds_region'),
//                      'args' => 'index,Region,setting',
//                  ),
//                  'db' => array(
//                      'ico'=>'&#xe6f5;',
//                      'text' => lang('ds_db'),
//                      'args' => 'db,Db,setting',
//                  ),
                    'admin_log' => array(
                        'ico'=>'&#xe71f;',
                        'text' => lang('ds_adminlog'),
                        'args' => 'loglist,Adminlog,setting',
                    ),
                ),
            ),
            'member' => array(
                'name' => 'member',
                'text' =>'用户',
                'children' => array(
                    'member' => array(
                        'ico'=>'&#xe667;',
                        'text' => '用户管理',
                        'args' => 'member,Member,member',
                    ),
//                  'membergrade' => array(
//                      'ico'=>'&#xe6a3;',
//                      'text' => lang('ds_membergrade'),
//                      'args' => 'index,Membergrade,member',
//                  ),
//                  'exppoints' => array(
//                      'ico'=>'&#xe727;',
//                      'text' => lang('ds_exppoints'),
//                      'args' => 'index,Exppoints,member',
//                  ),
                    'notice' => array(
                        'ico'=>'&#xe71b;',
                        'text' => '用户极光通知',
                        'args' => 'index,Notice,member',
                    ),
                    'auth' => array(
                        'ico'=>'&#xe727;',
                        'text' => lang('采购商认证'),
                        'args' => 'index,Auth,member',
                    ),

                    'membersupply' => array(
                        'ico'=>'&#xe6f3;',
                        'text' => lang('供应商认证'),
                        'args' => 'index,Membersupply,member',
                    ),
                    'supply' => array(
                        'ico'=>'&#xe6f5;',
                        'text' => lang('供应求购管理'),
                        'args' => 'index,Supply,member',
                    ),
                    'device' => array(
                        'ico'=>'&#xe6f2;',
                        'text' => lang('物流管理'),
                        'args' => 'index,Device,member',
                    ),

                    'rongyun' => array(
                        'ico'=>'&#xe6f4;',
                        'text' => lang('用户消息记录'),
                        'args' => 'index,Rongyun,member',
                    ),
                    'indentity' => array(
                        'ico'=>'&#xe6f4;',
                        'text' => lang('物流分类管理'),
                        'args' => 'index,Identity,member',
                    ),

                ),
            ),
            'goods' => array(
                'name' => 'goods',
                'text' => lang('ds_goods'),
                'children' => array(
                    'goodsclass' => array(
                        'ico'=>'&#xe652;',
                        'text' => '供求分类',
                        'args' => 'goods_class,Goodsclass,goods',
                    ),
                    'Goods' => array(
                        'ico'=>'&#xe732;',
                        'text' => lang('ds_goods_manage'),
                        'args' => 'index,Goods,goods',
                    ),
                ),
            ),
//            'trade' => array(
//                'name' => 'trade',
//                'text' => lang('ds_trade'),
//                'children' => array(
//                    'deliver' => array(
//                        'ico'=>'&#xe69e;',
//                        'text' => lang('ds_deliver'),
//                        'args' => 'index,Deliver,trade',
//                    ),
//                    'order' => array(
//                        'ico'=>'&#xe69c;',
//                        'text' => lang('ds_order'),
//                        'args' => 'index,Order,trade',
//                    ),
//                  'vrorder' => array(
//                      'ico'=>'&#xe71f;',
//                      'text' => lang('ds_vrorder'),
//                      'args' => 'index,Vrorder,trade',
//                  ),
//                  'refund' => array(
//                      'ico'=>'&#xe6f3;',
//                      'text' => lang('ds_refund'),
//                      'args' => 'refund_manage,Refund,trade',
//                  ),
//                  'return' => array(
//                      'ico'=>'&#xe6f3;',
//                      'text' => lang('ds_return'),
//                      'args' => 'return_manage,Returnmanage,trade',
//                  ),
//                  'vrrefund' => array(
//                      'ico'=>'&#xe6f3;',
//                      'text' => lang('ds_vrrefund'),
//                      'args' => 'refund_manage,Vrrefund,trade',
//                  ),
//                  'consulting' => array(
//                      'ico'=>'&#xe71c;',
//                      'text' => lang('ds_consulting'),
//                      'args' => 'Consulting,Consulting,trade',
//                  ),
//                  'inform' => array(
//                      'ico'=>'&#xe64a;',
//                      'text' => lang('ds_inform'),
//                      'args' => 'inform_list,Inform,trade',
//                  ),
//                  'evaluate' => array(
//                      'ico'=>'&#xe6f2;',
//                      'text' => lang('ds_evaluate'),
//                      'args' => 'evalgoods_list,Evaluate,trade',
//                  ),
//                    'deliverset' => array(
//                        'ico'=>'&#xe69e;',
//                        'text' => '发货设置',
//                        'args' => 'index,Deliverset,trade',
//                    ),
//                    'transport' => array(
//                        'ico'=>'&#xe655;',
//                        'text' => '售卖区域',
//                        'args' => 'index,Transport,trade',
//                    ),
//                ),
//            ),
            'website' => array(
                'name' => 'website',
                'text' => lang('ds_website'),
                'children' => array(
//                    'Articleclass' => array(
//                        'ico'=>'&#xe652;',
//                        'text' => lang('ds_articleclass'),
//                        'args' => 'index,Articleclass,website',
//                    ),
                    'Article' => array(
                        'ico'=>'&#xe72a;',
                        'text' => lang('ds_article'),
                        'args' => 'index,Article,website',
                    ),
                    'Adv' => array(
                        'text' => lang('ds_adv'),
                        'args' => 'ap_manage,Adv,website',
                    ),
                    'Mallconsult' => array(
                        'ico'=>'&#xe750;',
                        'text' => '意见反馈',
                        'args' => 'index,Mallconsult,website',
                    ),
                ),
            ),


        );
    }

    /*
     * 权限选择列表
     */

    function limitList() {
        $_limit = array(
            array('name' => lang('ds_set'), 'child' => array(
                    array('name' => lang('ds_base'), 'action' => null, 'controller' => 'Config'),
                    array('name' => '权限设置', 'action' => null, 'controller' => 'Admin'),
                    array('name' => lang('ds_adminlog'), 'action' => null, 'controller' => 'Adminlog'),
                )),
            array('name' => lang('ds_goods'), 'child' => array(
                    array('name' => lang('ds_goods_manage'), 'action' => null, 'controller' => 'Goods'),
                    array('name' => lang('ds_goodsclass'), 'action' => null, 'controller' => 'Goodsclass'),
                )),
            array('name' => lang('ds_member'), 'child' => array(
                    array('name' => '用户管理', 'action' => null, 'controller' => 'Member'),
                    array('name' => '用户通知', 'action' => null, 'controller' => 'Notice'),
                    array('name' => '采购商认证', 'action' => null, 'controller' => 'Auth'),
                    array('name' => '供应商认证', 'action' => null, 'controller' => 'Membersupply'),
                    array('name' => '供求管理', 'action' => null, 'controller' => 'Supply'),
                    array('name' => '物流管理', 'action' => null, 'controller' => 'Traffic'),
                    array('name' => '物流分类管理', 'action' => null, 'controller' => 'Identity'),
                    array('name' => '消息记录管理', 'action' => null, 'controller' => 'Rongyun'),


            )),
//            array('name' => lang('ds_trade'), 'child' => array(
//                    array('name' => lang('ds_order'), 'action' => null, 'controller' => 'Order'),
//                    array('name' => lang('ds_refund'), 'action' => null, 'controller' => 'Refund'),
//                    array('name' => lang('ds_return'), 'action' => null, 'controller' => 'Returnmanage'),
//                    array('name' => lang('ds_evaluate'), 'action' => null, 'controller' => 'Evaluate'),
//                    array('name' => '发货设置', 'action' => null, 'controller' => 'Deliverset'),
//                )),
            array('name' => lang('ds_website'), 'child' => array(
                    array('name' => lang('ds_article'), 'action' => null, 'controller' => 'Article'),
                    array('name' => lang('ds_adv'), 'action' => null, 'controller' => 'Adv'),
                    array('name' => '意见反馈', 'action' => null, 'controller' => 'Mallconsult'),
                )),
//            array('name' => lang('ds_operation'), 'child' => array(
//                array('name' => lang('ds_operation_set'), 'action' => null, 'controller' => 'Operation'),
//                array('name' => lang('ds_voucher_price_manage'), 'action' => null, 'controller' => 'Voucher'),
//                array('name' => lang('ds_activity_manage'), 'action' => null, 'controller' => 'Vrbill'),
//                array('name' => lang('ds_shop_consult'), 'action' => null, 'controller' => 'Mallconsult'),
//                array('name' => lang('ds_rechargecard'), 'action' => null, 'controller' => 'Rechargecard'),
//            )),
        );

        return $_limit;
    }

}

?>
