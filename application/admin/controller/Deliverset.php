<?php
/**
 * 发货设置
 */
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
class  Deliverset extends AdminControl
{

    public function _initialize()
    {
        parent::_initialize();
        Lang::load(APP_PATH . 'admin/lang/' . config('default_lang') . '/deliver.lang.php');
    }

    /**
     * 发货地址列表
     */
    public function index()
    {
        $daddress_model = model('daddress');
        $condition = array();
        $address_list = $daddress_model->getAddressList($condition, '*', '', 20);
        $this->assign('address_list', $address_list);
        $this->setAdminCurItem('list');
        return $this->fetch('index');
    }

    /**
     * 新增/编辑发货地址
     */
    public function daddress_add() {
        $address_id = intval(input('param.address_id'));
        if ($address_id > 0) {
            $daddress_mod = model('daddress');
            //编辑
            if (!request()->isPost()) {
                $address_info = $daddress_mod->getAddressInfo(array('daddress_id' => $address_id));
                $this->assign('address_info', $address_info);
                return $this->fetch( 'add');
            } else {
                $data = array(
                    'seller_name' => input('post.seller_name'),
                    'area_id' => input('post.area_id'),
                    'city_id' => input('post.city_id'),
                    'area_info' => input('post.region'),
                    'daddress_detail' => input('post.daddress_detail'),
                    'daddress_telphone' => input('post.daddress_telphone'),
                    'daddress_company' => input('post.daddress_company'),
                );
                //验证数据  BEGIN
                $deliverset_validate = validate('deliverset');
                if (!$deliverset_validate->scene('daddress_add')->check($data)) {
                    $this->error($deliverset_validate->getError());
                }
                //验证数据  END
                $result = $daddress_mod->editDaddress($data, array('daddress_id' => $address_id));
                if ($result) {
                    dsLayerOpenSuccess(lang('ds_common_save_succ'));
                } else {
                    $this->error(lang('ds_common_save_fail'));
                }
            }
        } else {
            //新增
            if (!request()->isPost()) {
                $address_info = array(
                    'daddress_id' => '', 'city_id' => '1', 'area_id' => '1', 'seller_name' => '',
                    'area_info' => '', 'daddress_detail' => '', 'daddress_telphone' => '', 'daddress_company' => '',
                );
                $this->assign('address_info', $address_info);
                return $this->fetch('add');
            } else {
                $data = array(
                    'seller_name' => input('post.seller_name'),
                    'area_id' => intval(input('post.area_id')),
                    'city_id' => intval(input('post.city_id')),
                    'area_info' => input('post.region'),
                    'daddress_detail' => input('post.daddress_detail'),
                    'daddress_telphone' => input('post.daddress_telphone'),
                    'daddress_company' => input('post.daddress_company'),
                );
                //验证数据  BEGIN
                $deliverset_validate = validate('deliverset');
                if (!$deliverset_validate->scene('daddress_add')->check($data)) {
                    $this->error($deliverset_validate->getError());
                }
                //验证数据  END
                $result = db('daddress')->insertGetId($data);
                if ($result) {
                    dsLayerOpenSuccess(lang('ds_common_save_succ'));
                } else {
                    $this->error(lang('ds_common_save_fail'));
                }
            }
        }
    }

    /**
     * 删除发货地址
     */
    public function daddress_del() {
        $address_id = intval(input('param.address_id'));
        if ($address_id <= 0) {
            ds_json_encode(10001, lang('daddress_del_fail'));
        }
        $condition = array();
        $condition['daddress_id'] = $address_id;
        $delete = model('daddress')->delDaddress($condition);
        if ($delete) {
            ds_json_encode(10000, lang('删除发货地址成功'));
        } else {
            ds_json_encode(10001, lang('删除发货地址失败'));
        }
    }

    /**
     * 设置默认发货地址
     */
    public function daddress_default_set() {
        $address_id = intval(input('get.address_id'));
        if ($address_id <= 0)
            return false;
        $condition = array();
        model('daddress')->editDaddress(array('daddress_isdefault' => 0), $condition);
        $condition['daddress_id'] = $address_id;
        $update = model('daddress')->editDaddress(array('daddress_isdefault' => 1), $condition);
        return $update;
    }

    /**
    * 免运费额度设置
    */
    public function free_freight() {
        if (!request()->isPost()) {
            $this->assign('free_price', config('free_price'));
            $this->assign('free_time', config('free_time'));

            $this->setAdminCurItem('free_freight');
            return $this->fetch('free_freight');
        } else {
            $config_model = model('config');
            $update_array['free_price'] = floatval(abs(input('post.free_price')));
            $update_array['free_time'] = input('post.free_time');
            $result = $config_model->editConfig($update_array);
            if ($result) {
                ds_json_encode(10000,lang('ds_common_save_succ'));
            } else {
                ds_json_encode(10001,lang('ds_common_save_fail'));
            }
        }
    }

    /**
     * 默认配送区域设置
     */
    public function deliver_region() {
        if (!request()->isPost()) {
            $deliver_region = array(
                '', ''
            );
            if (strpos(config('deliver_region'), '|')) {
                $deliver_region = explode('|', config('deliver_region'));
            }
            $this->assign('deliver_region', $deliver_region);
            $this->setAdminCurItem('deliver_region');
            return $this->fetch('deliver_region');
        } else {
            $config_model = model('config');
            $update_array['deliver_region'] = input('post.area_ids') . '|' . input('post.region');
            $result = $config_model->editConfig($update_array);
            if ($result) {
                ds_json_encode(10000,lang('ds_common_save_succ'));
            } else {
                ds_json_encode(10001,lang('ds_common_save_fail'));
            }
        }
    }

    /**
     * 发货单打印设置
     */
    public function print_set() {

        if (!request()->isPost()) {
            $this->assign('seal_printexplain', config('seal_printexplain'));
            $this->assign('seal_img', config('seal_img'));
            $this->setAdminCurItem('print_set');
            return $this->fetch('print_set');
        } else {
            $data = array(
                'seal_printexplain' => input('seal_printexplain')
            );
            $deliverset_validate = validate('deliverset');
            if (!$deliverset_validate->scene('print_set')->check($data)) {
                $this->error($deliverset_validate->getError());
            }
            $update_arr = array();
            //上传认证文件
            if ($_FILES['seal_img']['name'] != '') {
                $default_dir = BASE_UPLOAD_PATH . DS . DIR_ADMIN;
                $file_name = date('YmdHis') . rand(10000, 99999);
                $upload = request()->file('seal_img');
                $result = $upload->validate(['ext' => ALLOW_IMG_EXT])->move($default_dir, $file_name);
                if ($result) {
                    $update_arr['seal_img'] = $result->getFilename();
                    //删除旧认证图片
                    if (!empty(config('seal_img'))) {
                        @unlink(BASE_UPLOAD_PATH . DS . DIR_ADMIN . DS . config('seal_img'));
                    }
                }
            }

            $config_model = model('config');
            $update_arr['seal_printexplain'] = input('post.seal_printexplain');
            $result = $config_model->editConfig($update_arr);
            if ($result) {
                $this->success(lang('ds_common_save_succ'));
            } else {
                $this->error(lang('ds_common_save_fail'));
            }
        }
    }

    protected function getAdminItemList()
    {
        $menu_array = array(
            array(
                'name' => 'list',
                'text' => '地址库',
                'url' => url('Deliverset/index')
            ),
//          array(
//              'name' => 'free_freight',
//              'text' => '免运费额度',
//              'url' => url('Deliverset/free_freight')
//          ),
            array(
                'name' => 'deliver_region',
                'text' => '默认配送地区',
                'url' => url('Deliverset/deliver_region')
            ),
//          array(
//              'name' => 'print_set',
//              'text' => '发货单打印设置',
//              'url' => url('Deliverset/print_set')
//          ),
            array(
                'name' => 'add',
                'text' => '新增地址',
                'url' => "javascript:dsLayerOpen('".url('Deliverset/daddress_add')."','新增地址')"
            ),
        );
        return $menu_array;
    }
}