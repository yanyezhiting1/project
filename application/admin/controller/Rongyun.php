<?php
namespace app\admin\controller;
use think\Lang;
use RongCloud\RongCloud;
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
class  Rongyun extends AdminControl {

    public function _initialize() {
        parent::_initialize();
        Lang::load(APP_PATH . 'admin/lang/'.config('default_lang').'/adv.lang.php');
    }


    /**
     *
     * 广告管理
     */
    public function index($date='') {

        $RongSDK = new RongCloud('82hegw5u8x4ix', 'XGETxjmV1Og');
        $message = [
            'date' => $date,//日期
        ];
        $Chartromm = $RongSDK->getMessage()->History()->get($message);
        $url = $Chartromm['url'];
        $path = "uploads/home/zip/";
        $arr=parse_url($url);
        $fileName=basename($arr['path']);

        $file=file_get_contents($url);
        file_put_contents($path.$fileName,$file);
        $zip = new \ZipArchive();
        $openRes = $zip->open($path.$fileName);
        if ($openRes === TRUE) {
            $zip->extractTo($path);
            unlink($path.$fileName);
            $zip->close();
        }
        $year = substr($date,0,4);
        $month = substr($date,4,2);
        $day = substr($date,-4,2);
        $hour = substr($date,-2);
        $file_date = $year."-".$month."-".$day."-".$hour;
        rename($path.$file_date,$path.$file_date.".txt");
        $newtxt = $path.$file_date.".txt";
        $read_file = fopen($newtxt,"r");
        $str = fread($read_file,filesize($newtxt));
        fclose($newtxt);
        unlink($newtxt);
        $result=array();
        preg_match_all("/(?:\{)(.*)(?:\})/i",$str, $result);
        $material2_info = $result[0];
        $message=[];
        foreach($material2_info as $k=>$v){
           $obj = json_decode($v);
           if($obj->classname=="RC:TxtMsg"){
               $message[$k]['content'] = $obj->content->content;
               $message[$k]['sendtime'] = $obj->dateTime;
               $send_userid =  $obj->fromUserId;
               $message[$k]['senduser'] = db('member')->where('member_id',$send_userid)->value('member_name');
               $accept_userid = $obj->targetId;
               $message[$k]['acceptuser'] = db('member')->where('member_id',$accept_userid)->value('member_name');

           }

        }
        $this->assign('material2_info', $message);
        $this->setAdminCurItem('material2');
        return $this->fetch('material2_index');

    }

    /**
     * 管理员添加广告
     */
    public function material2_add() {
        $material2_model = model('material2');
        if (!request()->isPost()) {

            //获取广告列表
            $this->assign('material2', '');

            return $this->fetch('material2_form');
        } else {

            $insert_array['material2_name'] = trim(input('post.material2_name'));


//            //上传文件保存路径
//            $upload_file = BASE_UPLOAD_PATH . DS . ATTACH_ADV;
//            if (!empty($_FILES['material2_code']['name'])) {
//                if($_FILES['material2_code']['size']>3*1024*1024){
//                    $this->error('上传文件不能大于3M，请重新选择');
//                }
//                $file = request()->file('material2_code');
//                $info = $file->rule('uniqid')->validate(['ext' => ALLOW_IMG_EXT])->move($upload_file);
//                if ($info) {
//                    $insert_array['material2_code'] = $info->getFilename();
//                } else {
//                    // 上传失败获取错误信息
//                    $this->error($file->getError());
//                }
//            }

            //验证数据  BEGIN

            //验证数据  END
            //广告信息入库
            $result = $material2_model->addmaterial2($insert_array);

            if ($result) {
                $this->log(lang('material2_add_succ') . '[' . input('post.material2_name') . ']', null);
                dsLayerOpenSuccess('材质添加成功');
//                $this->success(lang('material2_add_succ'), url('material2/material2', ['ap_id' => input('post.ap_id')]));
            } else {
                $this->error('添加失败');
            }
        }
    }

    /**
     *
     * 修改广告
     */
    public function material2_edit() {
        $material2_id = intval(input('param.material2_id'));
        $material2_model = model('material2');
        //获取指定广告
        $condition['material2_id'] = $material2_id;
        $material2 = $material2_model->getOnematerial2($condition);
        if (!request()->isPost()) {

            //获取广告列表
            $this->assign('material2', $material2);
            $this->assign('ref_url', get_referer());
            return $this->fetch('material2_form');
        } else {
            $param['material2_id'] = $material2_id;
            $param['material2_name'] = input('param.material2_name');
           

            if (!empty($_FILES['material2_code']['name'])) {
                //上传文件保存路径
                if($_FILES['material2_code']['size']>3*1024*1024){
                    $this->error('上传文件不能大于3M，请重新选择');
                }
                $upload_file = BASE_UPLOAD_PATH . DS . ATTACH_ADV;
                $file = request()->file('material2_code');
                $info = $file->rule('uniqid')->validate(['ext' => ALLOW_IMG_EXT])->move($upload_file);
                if ($info) {
                    //还需删除原来图片
                    if (!empty($material2['material2_code'])) {
                        @unlink($upload_file . DS . $material2['material2_code']);
                    }
                    $param['material2_code'] = $info->getFilename();
                } else {
                    // 上传失败获取错误信息
                    $this->error($file->getError());
                }
            }

            //验证数据  BEGIN
            $material2_validate = validate('material2');
            if (!$material2_validate->scene('material2_edit')->check($param)) {
                $this->error($material2_validate->getError());
            }
            //验证数据  END

            $result = $material2_model->editmaterial2($param);

            if ($result>=0) {
                $this->log(lang('material2_change_succ') . '[' . input('post.ap_name') . ']', null);
                dsLayerOpenSuccess(lang('material2_change_succ'));
//               $this->success(lang('material2_change_succ'), input('post.ref_url'));
            } else {
                $this->error(lang('material2_change_fail'));
            }
        }
    }

    /**
     *
     * 删除广告
     */
    public function material2_del() {
        $material2_model = model('material2');
        /**
         * 删除一个广告
         */
        $material2_id = intval(input('param.material2_id'));
        $result = $material2_model->delmaterial2($material2_id);

        if (!$result) {
            ds_json_encode('10001', '删除失败');
        } else {
            $this->log(lang('删除材质成功') . '[' . $material2_id . ']', null);
            ds_json_encode('10000', '删除成功');
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
        $material2_model = model('material2');
        switch (input('get.branch')) {
            case 'ap_branch':
                $column = trim(input('param.column'));
                $value = trim(input('param.value'));
                $material2_id = intval(input('param.id'));
                $param['ap_id'] = $material2_id;
                $param[$column] = trim($value);
                $result = $material2_model->editmaterial2position($param);
                break;
            //ADV数据表更新
            case 'material2_branch':
                $column = trim(input('param.column'));
                $value = trim(input('param.value'));
                $material2_id = intval(input('param.id'));
                $param[$column] = trim($value);
                $result = $material2_model->editmaterial2(array_merge($param, array('material2_id' => $material2_id)));
                break;
        }
        if($result>=0){
            echo 'true';
        }else{
            echo false;
        }
    }

    function material2_template() {
        $pages = $this->_get_editable_pages();
        $this->assign('pages', $pages);
        $this->setAdminCurItem('material2_template');
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
        $menu_array = array();

        $menu_array[] = array(
            'name' => 'material2',
            'text' => lang('消息管理'),
            'url' => url('rongyun/index')
        );


        return $menu_array;
    }

}

?>
