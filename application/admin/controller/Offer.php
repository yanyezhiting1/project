<?php
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
class  Offer extends AdminControl {

    public function _initialize() {
        parent::_initialize();
        Lang::load(APP_PATH . 'admin/lang/'.config('default_lang').'/adv.lang.php');
    }


    /**
     *
     * 广告管理
     */
    public function index() {
        $offer_model = model('offer');

        $state = intval(input('param.state'));
        $type = intval(input('param.type'));

        if (!request()->isPost()) {
            $condition = array();
            if (is_numeric($state) === true) {
                $condition['state'] = $state;

            }


            $offer_info = $offer_model->getofferList($condition, 20, '', 'id DESC');


            foreach($offer_info as $k=>$v){


                $class_name = db('goodsclass')->where('gc_id',$v['class_id'])->value('gc_name');


                $offer_info[$k]['class_name'] = $class_name;


            }

            $this->assign('offer_info', $offer_info);

            $this->assign('show_page', $offer_model->page_info->render());

            $this->assign('filtered', $condition ? 1 : 0); //是否有查询条件
            $this->setAdminCurItem('adv');
            return $this->fetch('offer_index');
        }
    }

    /**
     * 管理员添加广告
     */
    public function offer_add() {
        $offer_model = model('offer');
        if (!request()->isPost()) {


            $adv = array(
                'ap_id' => 0,
                'adv_enabled' => '1',
                'adv_startdate' => time(),
                'adv_enddate' => time() + 24 * 3600 * 365,
            );
            $this->assign('adv', $adv);
            $class_list = db('goodsclass')->where('gc_parent_id',0)->select();
            $this->assign('class', $class_list);
            return $this->fetch('adv_form');
        } else {
            $insert_array['article_time'] = time();
            $insert_array['type'] = 1;
            $insert_array['state'] = 1;
            $insert_array['class_id'] = intval(input('post.class_id'));
            $insert_array['offer_title'] = trim(input('post.offer_title'));
            $insert_array['offer_content'] = input('post.offer_content');
            //上传文件保存路径
            $date = date("Y-m-d");
            $savepath = 'uploads/image/' . $date . '/';
            $upload_file = BASE_UPLOAD_PATH . DS .'image/' . $date . '/';

            if (!empty($_FILES['image1']['name'])){
                if($_FILES['image1']['size']>3*1024*1024){
                    $this->error('上传文件不能大于3M，请重新选择');
                }
                $file = request()->file('image1');
                $info = $file->rule('uniqid')->validate(['ext' => ALLOW_IMG_EXT])->move($upload_file);

                if ($info) {
                    $insert_array['image1'] ='http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $info->getFilename();
                } else {
                    // 上传失败获取错误信息
                    $this->error($file->getError());
                }
            }
            if (!empty($_FILES['image2']['name'])){
                if($_FILES['image2']['size']>3*1024*1024){
                    $this->error('上传文件不能大于3M，请重新选择');
                }
                $file = request()->file('image2');
                $info = $file->rule('uniqid')->validate(['ext' => ALLOW_IMG_EXT])->move($upload_file);
                if ($info) {
                    $insert_array['image2'] = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $info->getFilename();
                } else {
                    // 上传失败获取错误信息
                    $this->error($file->getError());
                }
            }
            if (!empty($_FILES['image3']['name'])){
                if($_FILES['image3']['size']>3*1024*1024){
                    $this->error('上传文件不能大于3M，请重新选择');
                }
                $file = request()->file('image3');
                $info = $file->rule('uniqid')->validate(['ext' => ALLOW_IMG_EXT])->move($upload_file);
                if ($info) {
                    $insert_array['image3'] = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $info->getFilename();
                } else {
                    // 上传失败获取错误信息
                    $this->error($file->getError());
                }
            }

            $result = $offer_model->addOffer($insert_array);

            if ($result) {
//                $this->log(lang('adv_add_succ') . '[' . input('post.adv_name') . ']', null);
                dsLayerOpenSuccess('添加成功');
//                $this->success(lang('adv_add_succ'), url('Adv/adv', ['ap_id' => input('post.ap_id')]));
            } else {
                $this->error('添加失败');
            }
        }
    }

    /**
     *
     * 修改广告
     */
    public function adv_edit() {
        $adv_id = intval(input('param.adv_id'));
        $adv_model = model('adv');
        //获取指定广告
        $condition['adv_id'] = $adv_id;
        $adv = $adv_model->getOneAdv($condition);
        if (!request()->isPost()) {

            //获取广告列表
            $ap_list = $adv_model->getAdvpositionList();
            $this->assign('ap_list', $ap_list);
            $this->assign('adv', $adv);
            $this->assign('ref_url', get_referer());
            return $this->fetch('adv_form');
        } else {
            $param['adv_id'] = $adv_id;
            $param['ap_id'] = intval(input('post.ap_id'));
            $param['adv_goodsid'] = input('post.adv_goodsid');
            $param['adv_title'] = trim(input('post.adv_name'));
            $param['adv_link'] = input('post.adv_link');
            $param['adv_sort'] = input('post.adv_sort');
            $param['adv_enabled'] = input('post.adv_enabled');
            $param['adv_bgcolor'] = input('post.adv_bgcolor');
            $param['adv_startdate'] = $this->getunixtime(trim(input('post.adv_startdate')));
            $param['adv_enddate'] = $this->getunixtime(trim(input('post.adv_enddate')));


            if (!empty($_FILES['adv_code']['name'])) {
                //上传文件保存路径
                if($_FILES['adv_code']['size']>3*1024*1024){
                    $this->error('上传文件不能大于3M，请重新选择');
                }
                $upload_file = BASE_UPLOAD_PATH . DS . ATTACH_ADV;
                $file = request()->file('adv_code');
                $info = $file->rule('uniqid')->validate(['ext' => ALLOW_IMG_EXT])->move($upload_file);
                if ($info) {
                    //还需删除原来图片
                    if (!empty($adv['adv_code'])) {
                        @unlink($upload_file . DS . $adv['adv_code']);
                    }
                    $param['adv_code'] = $info->getFilename();
                } else {
                    // 上传失败获取错误信息
                    $this->error($file->getError());
                }
            }

            //验证数据  BEGIN
            $adv_validate = validate('adv');
            if (!$adv_validate->scene('adv_edit')->check($param)) {
                $this->error($adv_validate->getError());
            }
            //验证数据  END

            $result = $adv_model->editAdv($param);

            if ($result>=0) {
                $this->log(lang('adv_change_succ') . '[' . input('post.ap_name') . ']', null);
                dsLayerOpenSuccess(lang('adv_change_succ'));
//               $this->success(lang('adv_change_succ'), input('post.ref_url'));
            } else {
                $this->error(lang('adv_change_fail'));
            }
        }
    }

    /**
     *
     * 用户审核
     */
    public function offer_check() {

        /**
         * 删除一个广告
         */
        $id = intval(input('param.id'));
        $state = intval(input('param.state'));

        $result = db('offer')->where('id',$id)->update(['state'=>$state]);
        if (!$result) {
            ds_json_encode('10001', lang('操作失败'));
        } else {
            $this->log(lang('认证审核成功') . '[' . $id . ']', null);
            ds_json_encode('10000', lang('操作成功'));
        }
    }

    /**
     *
     * 获取UNIX时间戳
     */
    public function getunixtime($time) {
        $array = explode("-", $time);
        $unix_time = mktime(0, 0, 0, $array[1], $array[2], $array[0]);
        return $unix_time;
    }

    public function ajax() {
        $adv_model = model('adv');
        switch (input('get.branch')) {
            case 'ap_branch':
                $column = trim(input('param.column'));
                $value = trim(input('param.value'));
                $adv_id = intval(input('param.id'));
                $param['ap_id'] = $adv_id;
                $param[$column] = trim($value);
                $result = $adv_model->editAdvposition($param);
                break;
            //ADV数据表更新
            case 'adv_branch':
                $column = trim(input('param.column'));
                $value = trim(input('param.value'));
                $adv_id = intval(input('param.id'));
                $param[$column] = trim($value);
                $result = $adv_model->editAdv(array_merge($param, array('adv_id' => $adv_id)));
                break;
        }
        if($result>=0){
            echo 'true';
        }else{
            echo false;
        }
    }

    function adv_template() {
        $pages = $this->_get_editable_pages();
        $this->assign('pages', $pages);
        $this->setAdminCurItem('adv_template');
        return $this->fetch();
    }

    /**
     *    获取可以编辑的页面列表
     */
    function _get_editable_pages() {
        return array(
            '首页' => url('Home/Index/index',['edit_ad'=>1]),
        );
    }

    /**
     * 获取卖家栏目列表,针对控制器下的栏目
     */
    protected function getAdminItemList() {
        $menu_array = array(
//            array(
//                'name' => 'ap_manage',
//                'text' => lang('ap_manage'),
//                'url' => url('Adv/ap_manage')
//            ),
        );
//        $menu_array[] = array(
//            'name' => 'ap_add',
//            'text' => lang('ap_add'),
//            'url' =>"javascript:dsLayerOpen('".url('Adv/ap_add')."','".lang('ap_add')."')"
//        );
        $menu_array[] = array(
            'name' => 'adv',
            'text' => lang('报价管理'),
            'url' => url('offer/index')
        );
        $menu_array[] = array(
            'name' => 'offer_add',
            'text' => '发布报价',
            'url' => "javascript:dsLayerOpen('".url('Offer/offer_add', ['id' => input('param.id')])."','".'发布报价'."')"
        );
//        $menu_array[] = array(
//            'name' => 'adv_template',
//            'text' => lang('adv_template'),
//            'url' => url('Adv/adv_template')
//        );

        return $menu_array;
    }

}

?>
