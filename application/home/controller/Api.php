<?php
namespace app\home\controller;
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use app\common\validate\Admin;
use RongCloud\RongCloud;
use jpush\src\JPush\Client as JPush;

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
class  Api {
    public function register ($account='', $code='', $password='',$invent_code='')
    {
        $data = json_decode('{}');
        if (empty($account) ) {
            $output = array('data' => $data, 'msg' => 'account', 'code' => '400');
            exit(json_encode($output));
        }
        if (empty($password) ) {
            $output = array('data' => $data, 'msg' => 'password', 'code' => '400');
            exit(json_encode($output));
        }

        $password = $this->rsa_decode($password);
        $aa = db('member')->where('member_mobile',$account)->find();

        if($aa){
            $output = array('data' => $data, 'msg' => '该手机号已注册，请勿重复操作', 'code' => '400');
            exit(json_encode($output));
        }
        if($invent_code !=''){
            $invent_info = db('member')->where('invent_code',$invent_code)->find();
            if(empty($invent_info)){
                $output = array('data' => $data, 'msg' => '邀请码无效', 'code' => '400');
                exit(json_encode($output));
            }
        }

        $res = db('verify_code')->where('verify_code_user_name', $account)->where('verify_code_type',2)->order('verify_code_add_time desc')->find();
        if ($code== $res['verify_code']) {
            $list['member_password'] = md5(md5($password));
            $list['member_mobile'] = $account;
            $list['member_truename'] = $account;
            $list['member_addtime'] = time();
            $list['member_logintime'] = time();
            $list['member_name'] = "用户".$account;
            $token = $this->gettoken();
            $list['token'] = $token;
//            for($i=0;$i<100;$i++){
//                $invent_code2 = rand(100000,999999);
//                $temp = db('member')->where('invent_code',$invent_code2)->find();
//                if($temp==''){
//                    break;
//                }
//            }
//            $list['invent_code'] = $invent_code2;
            $id = db('member')->insertGetId($list);
            if ($id){
                $insertid = $id;
                if($insertid){
                    //////注册融云账号
                    $rong_token  = $this->rong_register($token);

                    $data->rong_token = db('member')->where('member_id',$id)->value('rong_token');


                    $voucher_list = db('vouchertemplate')->where('invent',1)->select();
                    foreach($voucher_list as $vv){
                        $temp[] = $vv['vouchertemplate_id'];
                    }
                    $str = implode(',',$temp);
                    $path['uid'] = $insertid;
                    $str.=",";
                    if($invent_code !=''){
//                        //////发送邀请成功消息
//                        $vid = db('member')->where('invent_code',$invent_code)->value('voucher_id');
                        $insertid2 =db('member')->where('invent_code',$invent_code)->value('member_id');
//                        db('member')->where('member_id',$id)->update(['inviter_id',$insertid2]);
//                        $vid .=$str;
//                        db('member')->where('invent_code',$invent_code)->update(['voucher_id'=>$vid]);
//
                        db('member')->where('invent_code',$invent_code)->setInc('invent_num');
                        $add_money = db('config')->where('id',817)->value('value');
                        $change['userid'] =  $insertid2;
                        $change['money'] =  $add_money;
                        $change['type'] =  1;
                        $change['time'] =  time();
                        db('moneychange')->insert($change);
                        db('member')->where('member_id',$insertid2)->setInc('available_predeposit',$add_money);
//                        $message3['to_member_id'] = $insertid2;
//                        $info = db('article')->where('article_id',42)->value('article_content');
//                        $info =strip_tags(htmlspecialchars_decode($info));
//                        $message3['message_body'] = $info;
//                        $message3['message_title'] = '您已经成功邀请';
//                        $message3['message_time'] =time();
//                        $message3['message_type'] =3;
//                        db('message')->insert($message3);
//
//                        ////////增加邀请path
//                        $path['pid'] = $insertid2;
//                        $parent_pathinfo = db('path')->where('uid',$insertid2)->find();
//                        $parent_path = $parent_pathinfo['path'];
//                        $path['path'] = $parent_path.$insertid2.",";
//                        $invent_str = $parent_path.$insertid2;
//                        $invent_arr = explode(",",$invent_str);
//
//                        $invent_arr = array_reverse($invent_arr);
//
//                        array_pop($invent_arr);
//
//                        if(count($invent_arr)==1){
//                            $path['path_1'] =$invent_arr[0];
//                        }
//                        if(count($invent_arr)==2){
//                            $path['path_1'] = $invent_arr[0];
//                            $path['path_2'] = $invent_arr[1];
//                        }
//                        if(count($invent_arr)>=3){
//                            $path['path_1'] = $invent_arr[0];
//                            $path['path_2'] = $invent_arr[1];
//                            $path['path_3'] = $invent_arr[2];
//                        }
//                    }else{
//                        $path['pid'] = 0;
//                        $path['path'] ="0,";
                    }
//                    db('path')->insert($path);
                    $message2['to_member_id'] = $insertid;
                    $info = db('article')->where('article_id',42)->value('article_content');
                    $info =strip_tags(htmlspecialchars_decode($info));
//                    $message2['message_body'] = $info;
//                    $message2['message_title'] = '注册返还优惠券';
//                    $message2['message_time'] =time();
//                    $message2['message_type'] =4;
//                    db('message')->insert($message2);
                    $data->token = $token;
                    $output = array('data' => $data, 'msg' => '注册成功', 'code' => '200');
                    exit(json_encode($output));
                }else{
                    $output = array('data' => $data, 'msg' => '注册失败', 'code' => '400');
                    exit(json_encode($output));
                }
                
            }else{
                $output = array('data' => $data, 'msg' => '注册失败', 'code' => '401');
                exit(json_encode($output));
            }
        } else {
            $output = array('data' => $data, 'msg' => '验证码错误', 'code' => '402');
            exit(json_encode($output));
        }
    }

    /** 登录验证(未完成) **/
    public function login ($account='', $password='',$type='',$mobile='',$code='')
    {
        $data = json_decode('{}');
        ///  type 0 账号密码登录  1 手机号登录  2微信登录

        if($type==0){

            if (empty($account) || empty($password)  ) {

                $output = array('data' => $data, 'msg' => '账号或密码不能为空', 'code' => '400');
                exit(json_encode($output));
            }
            $password = $this->rsa_decode($password);
            $where['member_mobile'] = $account;
            $where['member_password'] = md5(md5($password));
            $res = db('member')->where($where)->find();
            if($res['member_state']==0){
                $output = array('data' => $data, 'msg' => '用户被冻结', 'code' => '400');
                exit(json_encode($output));
            }
            if ($res){
                $time = time();

                db('member')->where($where)->update(['member_logintime'=>$time]);
                if($res['member_logintime'] !=0){
                    db('member')->where($where)->update(['member_isfirst'=>1]);
                }
                $data->mobile = (string)$res['member_mobile'];
                $data->token = (string)$res['token'];
                $data->rong_token = (string)$res['rong_token'];
                $output = array('data' => $data, 'msg' => '登录成功', 'code' => '200');
                exit(json_encode($output));
            }else{
                $output = array('data' => $data, 'msg' => '用户名或密码错误', 'code' => '401');
                exit(json_encode($output));
            }

        }
        else if($type==1){

            if (empty($account) || empty($code) || empty($type)) {
                $output = array('data' => $data, 'msg' => '参数不正确', 'code' => '400');
                exit(json_encode($output));
            }
            $res = db('verify_code')->where('verify_code_user_name',$account)->order('verify_code_add_time DESC')->find();

            if($code == $res['verify_code']){
                $time = time();
                $res = db('member')->where('member_mobile',$account)->find();
                if(empty($res)){
                    $output = array('data' => $data, 'msg' => '请先注册', 'code' => '400');
                    exit(json_encode($output));
                }
                db('member')->where('member_mobile',$account)->update(['member_logintime'=>$time]);
                if($res['member_logintime'] !=0){
                    db('member')->where('member_mobile',$account)->update(['member_isfirst'=>1]);
                }

                $member = db('member')->where('member_mobile',$account)->find();
                $data->rong_token = (string)$member['rong_token'];
                if($member){
                    $data->token = (string)$member['token'];
                    $data->mobile = (string)$member['member_mobile'];
                }else{
                    $list['member_mobile'] = $account;
                    $list['member_addtime'] = time();
                    $token = $this->gettoken();
                    $list['token'] = $token;
                    db('member')->insert($list);
                    $data->token = (string)$token;
                    $data->mobile = (string)$account;
                }
                $output = array('data' => $data, 'msg' => '登录成功', 'code' => '200');
                exit(json_encode($output));

            }else{
                $output = array('data' => $data, 'msg' => '登录失败', 'code' => '401');
                exit(json_encode($output));
            }
        }


    }


    //////个人中心
    public function user_index($token='')
    {
        $data = json_decode('{}');
        if ($token == '') {
            $output = array('data' => $data, 'msg' => '缺少token', 'code' => '400');
            exit(json_encode($output));
        }
        $userid = $this->getuserid($token);
        if ($userid == false) {
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }
        $user_info = db('member')->where('member_id',$userid)->find();
        $data->name = $user_info['member_name'];
        $data->mobile = $user_info['member_mobile'];
        $data->invent_code = $user_info['invent_code'];
        $data->is_supply = $user_info['supply_auth'];
        if($user_info['member_auth']==1){
            $data->user_auth = 1;
        }else if($user_info['shop_auth']==1){
            $data->user_auth = 2;
        }else if($user_info['company_auth']==1){
            $data->user_auth = 3;
        }else{
            $data->user_auth = 0;
        }

        $temp = db('member_auth')->where('userid',$userid)->where('state',0)->find();
        $temp2 = db('member_auth')->where('userid',$userid)->where('state',1)->find();
        $data->check_state=2;
        if(empty($temp2)){
            $data->check_state = 0;
        }

        if($temp){
            $data->check_state = 1;
        }
        $data->avatar = $user_info['member_avatar'];
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));

    }


    ///////团队人数
    public function invent_list($token='',$type){
        $data = json_decode('{}');
        if($token==''){
            $output = array('data' => $data, 'msg' => '缺少token', 'code' => '400');
            exit(json_encode($output));
        }
        $userid = $this->getuserid($token);
        if($userid==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }
        $count=0;
        $where['path_1']  = $userid;
        $where['path_2']  = $userid;
        $where['path_3']  = $userid;
        $count = db('path')->whereOr($where)->count();
        $temp =[];
        $temp2 =[];
        $temp3 =[];
        if($type==1){
            $list = db('path')->where('path_1',$userid)->select();
            foreach($list as $v){
                $arr = [];
                $path1_id = $v['uid'];
                $inventuser = db('member')->where('member_id',$path1_id)->find();
                $arr['name'] = $inventuser['member_name'];
                $addtime = $inventuser['member_addtime'];
                $arr['time'] = date("Y-m-d",$addtime);
                $arr['level'] = 1;
                $temp[] = $arr;
            }
        }

        if($type==2){
            $list = db('path')->where('path_2',$userid)->select();
            foreach($list as $v){
                $arr = [];
                $path1_id = $v['uid'];
                $inventuser = db('member')->where('member_id',$path1_id)->find();
                $arr['name'] = $inventuser['member_name'];
                $addtime = $inventuser['member_addtime'];
                $arr['time'] = date("Y-m-d",$addtime);
                $arr['level'] = 2;
                $temp2[] = $arr;
            }
        }

        if($type==3){
            $list = db('path')->where('path_3',$userid)->select();
            foreach($list as $v){
                $arr = [];
                $path1_id = $v['uid'];
                $inventuser = db('member')->where('member_id',$path1_id)->find();
                $arr['name'] = $inventuser['member_name'];
                $addtime = $inventuser['member_addtime'];
                $arr['time'] = date("Y-m-d",$addtime);
                $arr['level'] = 3;
                $temp3[] = $arr;
            }
        }

        $data->count = $count;
        if($type==1){
            $data->list = $temp;
        }
        if($type==2){
            $data->list = $temp2;
        }
        if($type==3){
            $data->list = $temp3;
        }


        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));


    }



///////红包
    public function redpaper($token=''){
        $data = json_decode('{}');
        if($token==''){
            $output = array('data' => $data, 'msg' => '缺少token', 'code' => '400');
            exit(json_encode($output));
        }
        $userid = $this->getuserid($token);
        if($userid==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }


        $member = db('member')->where('member_id',$userid)->find();
        $is_first = $member['member_isfirst'];
        if($is_first !=0){
            $is_first=1;

        }

        $data->is_firstlogin = $is_first;
        if($is_first==0){
            $list = db('vouchertemplate')->where('redpaper',1)->select();
            $list2 =[];
            $str='';
            foreach($list as $k=>$v){
                $info = db('vouchertemplate')->where('vouchertemplate_id',$v['vouchertemplate_id'])->find();
                $list2[$k]['id'] = $info['vouchertemplate_id'];
                $list2[$k]['image'] = 'http://' . $_SERVER['HTTP_HOST']."/uploads/home/voucher/".$info['vouchertemplate_customimg'];
                $list2[$k]['title'] = $info['vouchertemplate_title'];
                $list2[$k]['money'] = $info['vouchertemplate_price'];
                $list2[$k]['limit'] = $info['vouchertemplate_limit'];
                $list2[$k]['start_time'] = date('Y-m-d',$info['vouchertemplate_startdate']);
                $list2[$k]['end_time'] = date('Y-m-d',$info['vouchertemplate_enddate']);
                $str.=$info['vouchertemplate_id'].",";
            }

            db('member')->where('member_id',$userid)->update(['voucher_id'=>$str]);
            $data->list = $list2;

        }
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }
    ////判断商品是否存在
    public function goods_exists($id){
        $data = json_decode('{}');
        $res1 =  db('goods')->where('goods_id',$id)->find();
        $res2 =  db('goods')->where('goods_id',$id)->find();
        if($res1=='' || $res2==''){
            $output = array('data' => $data, 'msg' => '商品不存在', 'code' => '400');
            exit(json_encode($output));
        }else{
            $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
            exit(json_encode($output));
        }
    }
    ////发送短信
    public function sendsms($phone='',$type='',$token=''){
        $data = json_decode('{}');
        if($phone=='' || $type==''){
            $output = array('data' => $data, 'msg' => '缺少phone', 'code' => '400');
            exit(json_encode($output));
        }
        if($phone=='' || $type==''){
            $output = array('data' => $data, 'msg' => '缺少type', 'code' => '400');
            exit(json_encode($output));
        }
        $time = db('verify_code')->where('verify_code_user_name',$phone)->value('verify_code_add_time');

        if($time !="" && (time()-$time)<60){
            $output = array('data' => $data, 'msg' => '一分钟只能发送一条', 'code' => '400');
            exit(json_encode($output));
        }

        $code = rand(100000,999999);
        AlibabaCloud::accessKeyClient('LTAI4FxSBKGcdgR4VVmubrBF', 'AcrCfbPRjKXMcOkNGNu5p7YJCONrZB')
            ->regionId('cn-hangzhou')
            ->asDefaultClient();
        try {
            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->options([
                    'query' => [
                        'RegionId' => "cn-hangzhou",
                        'PhoneNumbers' => $phone,
                        'SignName' => "聚采购",
                        'TemplateCode' => "SMS_175534054",
                        'TemplateParam' => "{\"code\": \"$code\"}",
                    ],
                ])
                ->request();

            $res = $result->toArray();

            if($res['Message']=='OK'){
                $insert['verify_code_type']=$type;
                $insert['verify_code']=$code;
                if($token !=''){
                    $insert['verify_code_user_id']=$this->getuserid($token);
                }
                $insert['verify_code_user_name']=$phone;
                $insert['verify_code_add_time']=time();

                db('verify_code')->insert($insert);
                $output = array('data' => $data, 'msg' => '发送成功', 'code' => '200');
                exit(json_encode($output));
            }else{
                $output = array('data' => $data, 'msg' => '发送失败', 'code' => '400');
                exit(json_encode($output));
            }
        } catch (ClientException $e) {
            echo $e->getErrorMessage() . PHP_EOL;
        } catch (ServerException $e) {
            echo $e->getErrorMessage() . PHP_EOL;
        }

    }

    /** 找回密码 **/
    public function forgetpassword ($account='', $code='', $password='')
    {
        $data = json_decode('{}');

        if (empty($account) || empty($code) || empty($password)) {
            $output = array('data' => $data, 'msg' => '请输入完整信息', 'code' => '400');
            exit(json_encode($output));
        }


        $password = $this->rsa_decode($password);

        if (db('verify_code')->where('verify_code_user_name',$account)->order('verify_code_add_time DESC')->find()) {

            $res = db('verify_code')->where('verify_code_user_name',$account)->order('verify_code_add_time DESC')->value('verify_code');
            if ($code == $res) {
                $arr = array('member_password' => md5(md5($password)));
                $past = db('member')->where('member_mobile',$account)->value('member_password');

                if($arr['member_password'] ==$past){
                    $output = array('data' => $data, 'msg' => '新密码不能和原密码相同', 'code' => '400');
                    exit(json_encode($output));
                }
                $id = db('member')->where('member_mobile',$account)->update($arr);

                if ($id!=0) {

                    $output = array('data' => $data, 'msg' => '修改成功', 'code' => '200');
                    exit(json_encode($output));
                }
                else {
                    $output = array('data' => $data, 'msg' => '修改失败', 'code' => '403');
                    exit(json_encode($output));
                }
            } else {

                $output = array('data' => $data, 'msg' => '修改失败', 'code' => '403');
                exit(json_encode($output));
            }
        }else{
            $output = array('data' => $data, 'msg' => '账号不存在', 'code' => '402');
            exit(json_encode($output));
        }
    }

    //////修改密码
    public function changepassword ( $old,$token='', $password='')
    {
        $data = json_decode('{}');

        if (empty($token) ) {
            $output = array('data' => $data, 'msg' => '缺少token', 'code' => '400');
            exit(json_encode($output));
        }

        if (empty($password)) {
            $output = array('data' => $data, 'msg' => '缺少password', 'code' => '400');
            exit(json_encode($output));
        }
        $old = $this->rsa_decode($old);
        $password = $this->rsa_decode($password);
        $userid = $this->getuserid($token);
        if($userid==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }

        $arr = array('member_password' => md5(md5($password)));
        $old = md5(md5($old));
        $past = db('member')->where('member_id',$userid)->value('member_password');
        if($old !=$past){
            $output = array('data' => $data, 'msg' => '密码输入错误', 'code' => '400');
            exit(json_encode($output));
        }
        if($arr['member_password'] ==$past ){
            $output = array('data' => $data, 'msg' => '新密码不能和原密码相同', 'code' => '400');
            exit(json_encode($output));
        }
        $id = db('member')->where('member_id',$userid)->update($arr);

        if ($id!=0) {

            $output = array('data' => $data, 'msg' => '修改成功', 'code' => '200');
            exit(json_encode($output));
        }
        else {
            $output = array('data' => $data, 'msg' => '修改失败', 'code' => '403');
            exit(json_encode($output));
        }

    }


    ////首页轮播图
    public function index_list(){
        $data = json_decode('{}');
        $list1 = [];
        $where1['ap_id'] = 1;
        $time = strtotime(date("Y-m-d",time()));
        $adarr = db('adv')->where($where1)->select();
        foreach ($adarr as $key1 => $v1) {
            $arr = json_decode('{}');

            $image = 'http://' . $_SERVER['HTTP_HOST'] . '/uploads/' . ATTACH_ADV  .'/'. $v1['adv_code'];
            $arr->imgUrl = $image;
            $arr->link = $v1['adv_link'];
            $list1[] = $arr;
        }


        $data->adList = $list1;
        $image1 = db('config')->where('id',820)->find();
        $image2 = db('config')->where('id',821)->find();
        $image3 = db('config')->where('id',822)->find();
        $image4 = db('config')->where('id',823)->find();
        $image5 = db('config')->where('id',824)->find();
        $name1 = db('config')->where('id',825)->find();
        $name2 = db('config')->where('id',826)->find();
        $name3 = db('config')->where('id',827)->find();
        $name4 = db('config')->where('id',828)->find();
        $name5 = db('config')->where('id',829)->find();
        $name6 = db('config')->where('id',830)->find();
        $arr2[0]['name'] =$name1['value'];
        $arr2[0]['img'] = 'http://' . $_SERVER['HTTP_HOST'] . '/uploads/home/common/'.$image1['value'];
        $tmp = db('goods')->where('goods_type',2)->where('goods_addtime','gt',$time)->find();
        if($tmp){
            $arr2[0]['is_new'] = 1;
        }else{
            $arr2[0]['is_new'] = 0;
        }
        $arr2[1]['name'] =$name2['value'];
        $arr2[1]['img'] = 'http://' . $_SERVER['HTTP_HOST'] . '/uploads/home/common/'.$image2['value'];
        $tmp2 = db('goods')->where('goods_type',1)->where('goods_addtime','gt',$time)->find();
        if($tmp2){
            $arr2[1]['is_new'] = 1;
        }else{
            $arr2[1]['is_new'] = 0;
        }
        $arr2[2]['name'] =$name3['value'];
        $arr2[2]['img'] = 'http://' . $_SERVER['HTTP_HOST'] . '/uploads/home/common/'.$image3['value'];
        $arr2[3]['name'] = $name4['value'];
        $arr2[3]['img'] = 'http://' . $_SERVER['HTTP_HOST'] . '/uploads/home/common/'.$image4['value'];
        $arr2[4]['name'] =  $name5['value'];
        $arr2[4]['img'] = 'http://' . $_SERVER['HTTP_HOST'] . '/uploads/home/common/'.$image5['value'];
        $data->title_info = $arr2;
        $notice = db('article')->where('article_id','gt',50)->order('article_time desc')->value('article_content');
        $data->notice = strip_tags(htmlspecialchars_decode($notice));
        $adv1 = db('adv')->where('adv_id',20)->find();
        $adv2 = db('adv')->where('adv_id',21)->find();
        $adv3 = db('adv')->where('adv_id',22)->find();
        $adv4 = db('adv')->where('adv_id',23)->find();
        $adv5 = db('adv')->where('adv_id',24)->find();
        $adv6 = db('adv')->where('adv_id',25)->find();
        $adv[0]['img'] = 'http://' . $_SERVER['HTTP_HOST'] . '/uploads/home/adv/'.$adv1['adv_code'];
        $adv[0]['supply_id'] =$adv1['adv_goodsid'];
        $adv[1]['img'] ='http://' . $_SERVER['HTTP_HOST'] . '/uploads/home/adv/'.$adv2['adv_code'];
        $adv[1]['supply_id'] = $adv2['adv_goodsid'];
        $adv[2]['img'] = 'http://' . $_SERVER['HTTP_HOST'] . '/uploads/home/adv/'.$adv3['adv_code'];
        $adv[2]['supply_id'] = $adv3['adv_goodsid'];
        $adv[3]['img'] = 'http://' . $_SERVER['HTTP_HOST'] . '/uploads/home/adv/'.$adv4['adv_code'];
        $adv[3]['supply_id'] = $adv4['adv_goodsid'];
        $adv[4]['img'] = 'http://' . $_SERVER['HTTP_HOST'] . '/uploads/home/adv/'.$adv5['adv_code'];
        $adv[4]['supply_id'] = $adv5['adv_goodsid'];
        $adv[5]['img'] = 'http://' . $_SERVER['HTTP_HOST'] . '/uploads/home/adv/'.$adv6['adv_code'];
        $adv[5]['supply_id'] = $adv6['adv_goodsid'];
        $data->adv = $adv;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }
    //////首页搜索
    public function search($search='',$type=''){
        $data = json_decode('{}');
        if($type==''){
            $temp1  = db('circle')->where('circle_content','like','%'.$search.'%')->field('id,circle_content,article_time')->select();
            $list1 = [];
            foreach($temp1 as $k=>$v){
                $list1[$k]['type'] =1;
                $list1[$k]['title'] =$v['circle_content'];
                $list1[$k]['time'] = $v['article_time'];
                $list1[$k]['id'] = $v['id'];
            }

            $temp2 = db('device')->where('title','like','%'.$search.'%')->select();
            $list2 = [];
            foreach($temp2 as $k=>$v){
                $list2[$k]['type'] =2;
                $list2[$k]['title'] =$v['title'];
                $list2[$k]['time'] = $v['time'];
                $list2[$k]['id'] = $v['device_id'];
            }
            $temp3 = db('supply')->where('title','like','%'.$search.'%')->where('type',1)->select();
            $list3 = [];
            foreach($temp3 as $k=>$v){
                $list3[$k]['type'] =3;
                $list3[$k]['title'] =$v['title'];
                $list3[$k]['time'] = $v['time'];
                $list3[$k]['id'] = $v['id'];
            }
            $temp4 = db('offer')->where('offer_title','like','%'.$search.'%')->select();
            $list4 = [];
            foreach($temp4 as $k=>$v){
                $list4[$k]['type'] =4;
                $list4[$k]['title'] =$v['offer_title'];
                $list4[$k]['time'] = $v['article_time'];
                $list4[$k]['id'] = $v['id'];
            }
            $temp5 = db('supply')->where('title','like','%'.$search.'%')->where('type',2)->select();
            $list5 = [];
            foreach($temp5 as $k=>$v){
                $list5[$k]['type'] =5;
                $list5[$k]['title'] =$v['title'];
                $list5[$k]['time'] = $v['time'];
                $list5[$k]['id'] = $v['id'];
            }
            $new_list = array_merge($list1,$list2,$list3,$list4,$list5);
            $last_names = array_column($new_list,'time');
            array_multisort($last_names,SORT_DESC,$new_list);
            $data->list = $new_list;
            $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
            exit(json_encode($output));
        }
        if($type==1){
            $temp1  = db('circle')->where('circle_content','like','%'.$search.'%')->field('id,circle_content,article_time')->select();
            $list1 = [];
            foreach($temp1 as $k=>$v){
                $list1[$k]['type'] =1;
                $list1[$k]['title'] =$v['circle_content'];
                $list1[$k]['time'] = $v['article_time'];
                $list1[$k]['id'] = $v['id'];
            }
            $data->list = $list1;
            $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
            exit(json_encode($output));
        }
        if($type==2){
            $temp2 = db('device')->where('title','like','%'.$search.'%')->select();
            $list2 = [];
            foreach($temp2 as $k=>$v){
                $list2[$k]['type'] =2;
                $list2[$k]['title'] =$v['title'];
                $list2[$k]['time'] = $v['time'];
                $list2[$k]['id'] = $v['device_id'];
            }
            $data->list = $list2;
            $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
            exit(json_encode($output));
        }
        if($type==3){
            $temp3 = db('supply')->where('title','like','%'.$search.'%')->where('type',1)->select();
            $list3 = [];
            foreach($temp3 as $k=>$v){
                $list3[$k]['type'] =3;
                $list3[$k]['title'] =$v['title'];
                $list3[$k]['time'] = $v['time'];
                $list3[$k]['id'] = $v['id'];
            }
            $data->list = $list3;
            $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
            exit(json_encode($output));
        }
        if($type==4){
            $temp4 = db('offer')->where('offer_title','like','%'.$search.'%')->select();
            $list4 = [];
            foreach($temp4 as $k=>$v){
                $list4[$k]['type'] =4;
                $list4[$k]['title'] =$v['offer_title'];
                $list4[$k]['time'] = $v['article_time'];
                $list4[$k]['id'] = $v['id'];
            }
            $data->list = $list4;
            $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
            exit(json_encode($output));
        }
        if($type==5){
            $temp5 = db('supply')->where('title','like','%'.$search.'%')->where('type',2)->select();
            $list5 = [];
            foreach($temp5 as $k=>$v){
                $list5[$k]['type'] =5;
                $list5[$k]['title'] =$v['title'];
                $list5[$k]['time'] = $v['time'];
                $list5[$k]['id'] = $v['id'];
            }
            $data->list = $list5;
            $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
            exit(json_encode($output));
        }


    }
    /////热门搜索
    public function  hot_search(){
        $str = db('config')->where('id',137)->value('value');
        $list=[];
        if(strpos($str, ",") !==false){
            $list = explode(",",$str);
        }else{
            $list = explode("，",$str);
        }



        $data = json_decode('{}');

        $data->list =$list;


        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }

    ////首页商品分类列表
    public function homeGoodsClass(){
        $data = json_decode('{}');
        $list1 = [];

        $res = db('goodsclass')->where('gc_parent_id',0)->where('is_homepage',1)->where('gc_id','neq',133)->order('gc_id')->limit(8)->select();
        foreach ($res as $k => $v) {

            $arr = json_decode('{}');
            $arr->name = $v['gc_name'];
            $img = $v['pic'];
            $img2 = substr($img,24);
            $arr->imgUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/'.$img2;
            $arr->classId = $v['gc_id'];

            $list1[] = $arr;
        }
        $res2 = db('goodsclass')->where('gc_id',133)->find();


        $arr = json_decode('{}');
        $arr->name = $res2['gc_name'];
        $img = $res2['pic'];
        $img2 = substr($img,28);
        $arr->imgUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/'.$img2;
        $arr->classId = $res2['gc_id'];

        $list2 = $arr;

        $data->classList2 = $list2;
        $data->classList = $list1;

        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }
    ////限时抢购列表
    public function flashsale($page=1){

        $data = json_decode('{}');
        if($page==''){
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $page-= 1;
        $list1 = [];
        $time =time();
        $res = db('groupbuy')->where('groupbuy_endtime>'.$time)->where('groupbuy_starttime<'.$time)->limit($page*20,20)->select();

        $count = db('groupbuy')->count();
        $totalpage = ceil($count/20);
        foreach ($res as $k => $v) {
            $arr = json_decode('{}');
            $arr->goodsId = $v['goods_id'];
            $goods_info = db('goods')->where('goods_id',$v['goods_id'])->find();
            if(empty($goods_info)){
                continue;
            }
            $arr->flashPrice = $v['groupbuy_price'];
            $arr->starttime = date('Y-m-d H:i:s',$v['groupbuy_starttime']);
            $arr->entime = date('Y-m-d H:i:s',$v['groupbuy_endtime']);
            $arr->goodsPrice = $v['goods_price'];
            $arr->name = $v['groupbuy_name'];
            $arr->imgUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/uploads/home/groupbuy/'.$v['groupbuy_image'];
            $intro = $v['groupbuy_intro'];
            $intro2 = strip_tags(htmlspecialchars_decode($intro));
            $arr->intro = $intro2;
            $list1[] = $arr;
        }
        $data->list = $list1;
        $data->totalpage = $totalpage;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));

    }

    ////消息列表
    public function message_list(){



        $data = json_decode('{}');
        $list1 =db('article')->where('article_id','gt','50')->where('ac_id',1)->order('article_time DESC')->select();
        $systemList = [];
        foreach($list1 as $k=>$v){
            $arr = json_decode('{}');
            $arr->title = $v['article_title'];
            $arr->id = $v['article_id'];
            $arr->content = strip_tags(htmlspecialchars_decode($v['article_content']));
            $arr->time = date("Y-m-d",$v['article_time']);
            $systemList[] = $arr;
        }


        $data->list = $systemList;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));


    }

    public function invent(){

        $url = 'http://' . $_SERVER['HTTP_HOST'].'/index.php/Admin/invent/invent';
        $output = array('data' => $url, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }
    public function invent_code($code='',$token=''){
        $data = json_decode('{}');
        $invent_id = db('member')->where('invent_code',$code)->value('member_id');
        if($invent_id == ''){
            $output = array('data' => $data, 'msg' => '邀请码无效', 'code' => '400');
            exit(json_encode($output));
        }
        $userId = $this->getuserid($token);
        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }

        if($invent_id == $userId){
            $output = array('data' => $data, 'msg' => '不能自己邀请自己', 'code' => '400');
            exit(json_encode($output));
        }
        db('member')->where('member_id',$invent_id)->setInc('invent_num');

        db('member')->where('member_id',$userId)->update(['inviter_id'=>$invent_id]);
        $output = array('data' => $data, 'msg' => '操作成功', 'code' => '200');
        exit(json_encode($output));
    }


    ////消息详情
    public function messageDetail($token='',$message_id){
        $time = time();
        $data = json_decode('{}');
        if (empty($token) || $message_id=='' ) {
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $userId = $this->getuserid($token);
        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }

        $res =db('message')->where('message_id',$message_id)->find();

        $arr = json_decode('{}');


        $arr->content = $res['message_body'];

        $arr->time = date("Y-m-d",$res['message_time']);
        $arr->title = $res['message_title'];
        $str = $res['read_member_id'];
        if(strpos($res['read_member_id'],$userId.",") === false){

            $str2 = $str."$userId".",";
            db('message')->where('message_id',$res['message_id'])->update(['read_member_id'=>$str2]);
        }
        $data->list = $arr;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }

    ////获取全部一级分类列表
    public function nextClassList(){

        $data = json_decode('{}');
        $list = [];
        $image='';
        $res = db('goodsclass')->where('gc_parent_id',0)->where('gc_id','neq',133)->order('gc_id')->select();
        foreach($res as $v){
            $arr = json_decode('{}');
            $arr->Id = $v['gc_id'];
            $arr->name = $v['gc_name'];
            $list[] =$arr;
        }
        $data->list = $list;
//          $image = db('adv')->where('ap_id',2)->value('adv_code');
//    	  $data->image = 'http://'.$_SERVER['HTTP_HOST']."/uploads/home/adv/".$image;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }




    /////获取下级列表接口
    public function getthreeClassList($classId='', $deep=''){
        $data = json_decode('{}');
        $list=[];
        $res = db('goodsclass')->where('gc_parent_id',0)->order('gc_sort')->field('gc_id,gc_name')->select();
        foreach($res as $k=>$v){
            $res[$k]['id'] = $v['gc_id'];
            unset($res[$k]['gc_id']);
            $res[$k]['name'] = $v['gc_name'];
            unset($res[$k]['gc_name']);
        }
        foreach($res as $k=>$v){
            $gc_id = $v['id'];
            $list2 =  db('goodsclass')->where('gc_parent_id',$gc_id)->order('gc_sort')->select();
            foreach($list2 as $kk=>$vv){
                $res[$k]['list2'][$kk]['id'] = $vv['gc_id'];
                $res[$k]['list2'][$kk]['name'] = $vv['gc_name'];
                $gc_id_2 = $vv['gc_id'];
                $list3 =  db('goodsclass')->where('gc_parent_id',$gc_id_2)->order('gc_sort')->field('gc_id,gc_name,pic')->select();
                foreach($list3 as $kkk=>$vvv){
                    $res[$k]['list2'][$kk]['list3'][$kkk]['id'] = $vvv['gc_id'];
                    $res[$k]['list2'][$kk]['list3'][$kkk]['name'] = $vvv['gc_name'];
                    $pic = substr($vvv['pic'],24);
                    $pic = 'http://' . $_SERVER['HTTP_HOST'] . '/'.$pic;
                    $res[$k]['list2'][$kk]['list3'][$kkk]['image'] = $pic;

                }
            }

        }
        $data->list = $res;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));

    }

    public function getTree($array, $pid =0, $level = 1){

        //声明静态数组,避免递归调用时,多次声明导致数组覆盖
        static $list = [];
        foreach ($array as $key => $value){
            //第一次遍历,找到父节点为根节点的节点 也就是pid=0的节点

            if ($value['gc_parent_id'] == $pid){
                //父节点为根节点的节点,级别为0，也就是第一级
                $value['level'] = $level;
                //把数组放到list中
                $list[] = $value;
                //把这个节点从数组中移除,减少后续递归消耗
                unset($array[$key]);
                //开始递归,查找父ID为该节点ID的节点,级别则为原级别+1
                $this->getTree($array, $value['gc_id'], $level+1);
            }
        }
        return $list;
    }



    private function _getTreeClassList($deep, $classId,$show_deep=1,$i = 0)
    {
        $class_list = db('goodsclass')->where('gc_parent_id',$classId)->field('gc_name,gc_id,gc_parent_id')->select();
        static $show_class = array(); //树状的平行数组
        if (is_array($class_list) && !empty($class_list)) {
            $size = count($class_list);
            if ($i == 0)
                $show_class = array(); //从0开始时清空数组，防止多次调用后出现重复
            for ($i=0; $i < $size; $i++) {//$i为上次循环到的分类编号，避免重新从第一条开始
                $val = $class_list[$i];
                $gc_id = $val['gc_id'];
                if ($show_deep > $deep)
                    break; //当前分类的父编号大于本次递归的时退出循环
                $show_class[] = $val;
                $nextcount = db('goodsclass')->where('gc_parent_id', $gc_id)->count();

                if ($nextcount > 0) {
                    $this->_getTreeClassList($deep, $gc_id, $show_deep + 1, 1);
                }
            }
        }
        return $show_class;
    }
    ///////物流分类
    public function traffic_class_list(){
        $data = json_decode('{}');
        $list = db('traffic_class')->select();
        $data->list = $list;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }

    ///////省级筛选列表
    public function traffic_city_list(){
        $data = json_decode('{}');
        $list = db('area')->where('area_parent_id',0)->field('area_name,area_id')->select();
        $data->list = $list;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }


    //////物流
    public function traffic_list($type='',$page,$search='',$city_id=''){
        $data = json_decode('{}');
        $page -=1;
        if (  $page===''){

            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        if($search !=''){
            $where['name'] =  array('like', '%' . $search . '%');
        }
        if($type !=''){
            $where['type']=$type;
        }
        if($type==1){
            $where['city_id'] = array('like', '%' . $city_id . '%');
        }

        $list = db('traffic')->where($where)->limit($page*20,20)->select();
        foreach($list as $k=>$v){
            $city_id = $v['city_id'];
            $city_arr = explode(",",$city_id);
            $area=[];
            foreach ($city_arr as $vv){
                $city_name =db('area')->where('area_id',$vv)->value('area_name');
                $area[]=$city_name;
            }
            $list[$k]['area'] = $area;
            unset($list[$k]['city_id']);
            unset($list[$k]['type']);
        }
        $count = count($list);
        $totalpage = ceil($count/20);
        $data->totalpage = $totalpage;
        $data->list = $list;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }



    ////商品
    /// 获取下级商品列表
    /// type 1特价 2库存
    public function goods_list($page='',$search='',$type=''){
        $data = json_decode('{}');
        $page -=1;
        if (  $page===''){

            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $where2['goods_state']= 1;
        $list=[];
        $time = time();
        $where2['goods_type'] = $type;
        if ($search != '') {

            $where2['goods_name'] = array('like', '%' . $search . '%');
        }
        $res2 =  db('goods')->where($where2)->order('goods_id desc')->limit($page*20,20)->select();

        $list=[];
        foreach ($res2 as $v) {
            $arr = json_decode('{}');
            $common_info =  db('goodscommon')->where('goods_commonid',$v['goods_id'] )->find();
            $arr->image = 'http://' . $_SERVER['HTTP_HOST'] . "/uploads/home/store/goods/" . $v['goods_image'];
            $arr->name = $v['goods_name'];
            $arr->id = $v['goods_id'];
            $arr->intro = $common_info['goods_body'];
            $arr->num = $common_info['goods_storage'];
            $arr->time =date("Y-m-d",$v["goods_addtime"]);
            $arr->price = (string)$v['goods_price'];
            $list[] = $arr;
        }
        $count = count($list);
        $totalpage = ceil($count/20);
        $data->totalpage = $totalpage;
        $data->list = $list;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }
    /////商品详情
    public function getgoodsInfo($goodsid='',$token=''){
        $data = json_decode('{}');
        if ($goodsid=='' ){
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }

        $goods_info = db('goods')->where('goods_id',$goodsid)->find();
        $common_info = db('goodscommon')->where('goods_commonid',$goodsid)->find();

        $list=[];
        $list['goods_name'] = $common_info['goods_name'];
        $list['class_name'] = db('goodsclass')->where('gc_id',$goods_info['gc_id_3'])->value('gc_name');
        $goods_image =db('goodsimages')->where('goods_commonid',$goodsid)->where('goodsimage_isdefault',0)->field('goodsimage_url')->select();

        foreach($goods_image as $k=>$v){

            $goods_image[$k] = 'http://' . $_SERVER['HTTP_HOST']."/uploads/home/store/goods/".$v['goodsimage_url'];
        }
        $list['goods_image'] = $goods_image;
        $list['goods_price'] =$common_info['goods_price'];
        $list['intro'] = $common_info['goods_body'];
        $list['userid'] = $goods_info['user_id'];
        $list['num'] = $common_info['goods_storage'];
        $member_id = $common_info['user_id'];
        $member_info = db('member')->where('member_id',$member_id)->find();
        $supply_info = db('membersupply')->where('userid',$member_id)->where('state',1)->find();
        $list['member_name'] = $supply_info['name'];
        $area = db('area')->where('area_id',$supply_info['area_id'])->value('area_name');
        $city = db('area')->where('area_id',$supply_info['city_id'])->value('area_name');
        $district = db('area')->where('area_id',$supply_info['district_id'])->value('area_name');
        $list['area_name'] =$area.$city.$district.$supply_info['address'];
        $list['manage_type'] =$supply_info['manage_type'];
        $list['main'] =$supply_info['main'];
        $list['mobile1'] =$supply_info['mobile1'];
        $list['mobile2'] =$supply_info['mobile2'];
        $list['mobile3'] =$supply_info['mobile3'];
        $list['rongyun'] =$member_info['rong_token'];

        if($token!=''){
            $userId = $this->getuserid($token);
            if($userId==false){
                $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
                exit(json_encode($output));
            }
        }
        $data->list = $list;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));


    }

    public function create_order($token='',$goods_id='',$title='',$num='',$name='',$mobile='',$company_name='',$company_address=''){
        $data = json_decode('{}');
        if ($goods_id=='' ){
            $output = array('data' => $data, 'msg' => '缺少goods_id', 'code' => '400');
            exit(json_encode($output));
        }
        if ( $token==''  ){
            $output = array('data' => $data, 'msg' => 'token无效', 'code' => '400');
            exit(json_encode($output));
        }

        $userId = $this->getuserid($token);
        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }
        $goods_info = db('goodscommon')->where('goods_commonid')->value('user_id');
        $insert['goods_userid'] = $goods_info;
        $insert['goods_id'] = $goods_id;
        $insert['user_id'] = $userId;
        $insert['title'] = $title;
        $insert['num'] = $num;
        $insert['name'] = $name;
        $insert['mobile'] = $mobile;
        $insert['time'] = time();
        $insert['company_name'] = $company_name;
        $insert['company_address'] = $company_address;
        $id = db('showorder')->insert($insert);
        if($id){
            $output = array('data' => json_decode('{}'), 'msg' => '下单成功', 'code' => '200');
            exit(json_encode($output));
        }else{
            $output = array('data' => json_decode('{}'), 'msg' => '下单失败', 'code' => '400');
            exit(json_encode($output));
        }

    }



    /////菜单详情
    public function getmenuInfo($goodsId='',$token=''){
        $data = json_decode('{}');
        if ($goodsId=='' ){
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }

        $goods_info = db('pbundling')->where('bl_id',$goodsId)->find();


        $list=[];
        $list['goods_name'] = $goods_info['bl_name'];
        $goods_image=[];
        if($goods_info['image'] !=''){
            $goods_image[] = $goods_info['image'];
        }
        if($goods_info['image2'] !=''){
            $goods_image[] = $goods_info['image2'];
        }
        if($goods_info['image3'] !=''){
            $goods_image[] = $goods_info['image3'];
        }
        if($goods_info['image4'] !=''){
            $goods_image[] = $goods_info['image4'];
        } if($goods_info['image5'] !=''){
            $goods_image[] = $goods_info['image5'];
        }

        foreach($goods_image as $k=>$v){
            $goods_image[$k] = 'http://' . $_SERVER['HTTP_HOST']."/uploads/home/store/goods/".$v;
        }
        $list['goods_image'] = $goods_image;
        $temp = db('pbundlinggoods')->where('bl_id',$goodsId)->select();
        if(count($temp)==0){
            $output = array('data' => $data, 'msg' => '菜单已下架', 'code' => '400');
            exit(json_encode($output));
        }
        $menu=[];
        foreach($temp as $k=>$v){
            $menu[$k]['name'] = $v['goods_name'];
            $menu[$k]['use'] = $v['use'];
        }
        $list['menu'] =$menu;
        $list['goods_price'] =$goods_info['bl_discount_price'];

        $list['salenum'] = $goods_info['goods_salenum'];

        $list['describe'] = htmlspecialchars_decode($goods_info['bl_content']);
        if($token!=''){
            $userId = $this->getuserid($token);
            if($userId==false){
                $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
                exit(json_encode($output));
            }

            $is_collect = db('collect')->where(['user_id'=>$userId,'goods_id'=>$goodsId,'type'=>2])->find();
            if($is_collect !=''){
                $list['is_collect']='1';
            }else{
                $list['is_collect']='0';
            }
        }else{

            $list['is_collect']='';
        }
        $where['bl_id'] = array('lt',$goodsId);

        $upid =  db('pbundling')->where($where)->order('bl_id DESC')->value('bl_id');
        $downid = db('pbundling')->where('bl_id','gt',$goodsId)->order('bl_id')->value('bl_id');
        if($upid== null){
            $upid = '';
        }
        if($downid==''){
            $downid='';
        }

        $list['upid'] = (string)$upid;
        $list['downid'] = (string)$downid;
        $list['off'] = (string)$goods_info['off'];
        $text = db('article')->where('article_id',1)->value('article_content');
        $list['text'] = htmlspecialchars_decode($text);
        $cart_count=0;
        if($token !=''){


/////购物车列表

            $data2 = json_decode('{}');
            if ( $token==''  ){
                $output = array('data' => $data2, 'msg' => '缺少必要参数', 'code' => '400');
                exit(json_encode($output));
            }

            $userId = $this->getuserid($token);
            if($userId==false){
                $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
                exit(json_encode($output));
            }

            $res = db('cart')->where('buyer_id',$userId)->where('type',1)->order('cart_id DESC')->select();

            foreach($res as $v){
                $goodsinfo = db('goods')->where('goods_id',$v['goods_id'])->find();
                $goods_state = db('goodscommon')->where('goods_commonid',$v['goods_id'])->value('goods_state');
                ////商品未删除未下架

                $standards_info = db('goods_standards')->where('id',$v['standards_id'])->find();
                if($standards_info==''){
                    continue;
                }

                if($goodsinfo !='' && $goods_state ==1 && $standards_info['goods_storage'] >= $v['goods_num']){
                    $cart_count += $v['goods_num'];

                }

            }


            $res2 = db('cart')->where('buyer_id',$userId)->where('type',2)->order('cart_id DESC')->select();

            foreach($res2 as $vv){
                $menuinfo = db('pbundling')->where('bl_id',$vv['goods_id'])->find();


                if($menuinfo !=''){

                    $cart_count += $vv['goods_num'];

                }
            }
        }

        $list['cart_count'] = (string)$cart_count;
        $data->list = $list;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }

    /////添加购物车
    public function addCart($token='',$goodsId='',$count='',$standardsId='',$tag=''){
        $data = json_decode('{}');
        if ($goodsId=='' || $token=='' || $count==''  || $tag==''){
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $userId = $this->getuserid($token);
        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }
        $where['type'] = $tag;
        $where['buyer_id'] = $userId;
        $where['goods_id'] = $goodsId;
        if($tag==1){
            $where['standards_id'] = $standardsId;
        }
        $is_addcart = db('cart')->where('type',$tag)->where($where)->find();
        if($is_addcart !=''){

            db('cart')->where('type',$tag)->where($where)->setInc('goods_num',$count);
            $output = array('data' => $data, 'msg' => '添加成功', 'code' => '200');
            exit(json_encode($output));
        }else{
            if($tag==1){

                $goodsInfo = db('goods')->where('goods_id',$goodsId)->find();
                $insert['goods_name'] = $goodsInfo['goods_name'];
                $insert['goods_image'] ='http://' . $_SERVER['HTTP_HOST']."/uploads/home/store/goods/".$goodsInfo['goods_image'];
                $insert['goods_price'] = $goodsInfo['goods_price'];
                $insert['transfer'] = $goodsInfo['goods_freight'];
                $insert['standards_id'] = $standardsId;
            }
            if($tag==2){
                $menuInfo = db('pbundling')->where('bl_id',$goodsId)->find();
                $insert['goods_name'] = $menuInfo['bl_name'];
                $insert['goods_image'] ='http://' . $_SERVER['HTTP_HOST']."/uploads/home/store/goods/".$menuInfo['image'];
                $insert['goods_price'] = $menuInfo['bl_discount_price'];
                $insert['transfer'] = $menuInfo['bl_freight'];
                $insert['bl_id'] = $menuInfo['bl_id'];
            }

            $insert['buyer_id'] = $userId;
            $insert['goods_id'] = $goodsId;
            $insert['type'] = $tag;
            $insert['goods_num'] = $count;
            $id2 = db('cart')->insert($insert);
        }

        if($id2){
            $output = array('data' => $data, 'msg' => '添加成功', 'code' => '200');
            exit(json_encode($output));
        }else{
            $output = array('data' => $data, 'msg' => '系统繁忙', 'code' => '400');
            exit(json_encode($output));
        }

    }

    ////////获取购物车列表

    /**
     * @param string $token
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cartList($token=''){

        $data = json_decode('{}');
        $list=[];
        $list2=[];
        $list3=[];
        if ( $token==''  ){
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }

        $userId = $this->getuserid($token);
        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }

        $res = db('cart')->where('buyer_id',$userId)->where('type',1)->order('cart_id DESC')->select();

        foreach($res as $v){
            $goodsinfo = db('goods')->where('goods_id',$v['goods_id'])->find();
            $goods_state = db('goodscommon')->where('goods_commonid',$v['goods_id'])->value('goods_state');
            ////商品未删除未下架

            $arr2 = json_decode('{}');
            $arr2->goods_id = $v['goods_id'];
            $arr2->cartId = $v['cart_id'];
            $arr2->goods_image = $v['goods_image'];
            $arr2->goods_name = $v['goods_name'];
            $arr2->goods_price = $v['goods_price'];
            $arr = json_decode('{}');
            $arr->goods_id = $v['goods_id'];
            $arr->cartId = $v['cart_id'];
            $arr->goods_image = $v['goods_image'];
            $arr->goods_name = $v['goods_name'];
            $arr->goods_price = $v['goods_price'];
            $arr->goods_num = $v['goods_num'];
            $arr->transport = $v['transfer'];
            $arr->tag =1;
            $standards_info = db('goods_standards')->where('id',$v['standards_id'])->find();
            if($standards_info==''){
                continue;
            }
            $arr->standards = $standards_info;
            $arr->cartId = $v['cart_id'];

            if($goodsinfo !='' && $goods_state ==1 && $standards_info['goods_storage'] >= $v['goods_num'] && $standards_info['goods_storage']!=0 ){
                $list[] = $arr;

            }else{
                $list3[] = $arr;
            }

        }
        $data->goodslist = $list;

        $res2 = db('cart')->where('buyer_id',$userId)->where('type',2)->order('cart_id DESC')->select();

        foreach($res2 as $v){
            $menuinfo = db('pbundling')->where('bl_id',$v['goods_id'])->find();
            $arr = json_decode('{}');
            $arr->goods_id = $v['goods_id'];
            $arr->goods_image = $v['goods_image'];
            $arr->goods_name = $v['goods_name'];
            $arr->goods_price = $v['goods_price'];
            $arr->goods_num = $v['goods_num'];
            $arr->transport = $v['transfer'];
            $arr->cartId = $v['cart_id'];
            $arr->tag =2;

            if($menuinfo !=''){

                $temp = db('pbundlinggoods')->where('bl_id',$v['goods_id'])->select();
                if(count($temp)==0){
                    $lis3[] = $arr;
                }else{
                    $menu_foods =[];
                    $menu_goods = [];
                    foreach($temp as $kk=>$vv){
                        $menu_foods[$kk]['name'] = $vv['goods_name'];
                        $menu_foods[$kk]['use'] = $vv['use'];
                        $menu_goods[$kk]['goods_image'] = 'http://' . $_SERVER['HTTP_HOST']."/uploads/home/store/goods/".$vv['goods_image'];
                        $menu_goods[$kk]['goods_price'] = $vv['blgoods_price'];
                        $menu_goods[$kk]['make_price'] = $vv['make'];
                        $menu_goods[$kk]['goods_name'] = $vv['goods_name'];
                        $menu_goods[$kk]['goods_id'] = $vv['goods_id'];
                    }
                    $arr->menu_food = $menu_foods;
                    unset($menu_foods);

                    $arr->menu_goods = $menu_goods;
                    unset($menu_goods);
                    $list2[] = $arr;
                }

            }else{
                $list3[] = $arr;
            }
        }
        $data->menulist = $list2;
        $data->outtimelist = $list3;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }

    ////删除购物车
    public function deleteCart($cartId=''){
        $data = json_decode('{}');
        $list=[];
        if ( $cartId=='' ){
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }

        $id = db('cart')->where('cart_id','in',$cartId)->delete();
        if($id){
            $output = array('data' => $data, 'msg' => '删除成功', 'code' => '200');
            exit(json_encode($output));
        }else{
            $output = array('data' => $data, 'msg' => '删除失败', 'code' => '400');
            exit(json_encode($output));
        }
    }


    ////获取地区列表
    public function areaList(){
        $data = json_decode('{}');
        $list=[];
//
//
//        $res = db('area')->where('area_parent_id',0)->field('area_id,area_name')->select();
//        foreach($res as $k=>$v){
//            $area_id = $v['area_id'];
//            $list2 =  db('area')->where('area_parent_id',$area_id)->field('area_id,area_name')->select();
//
//
//            foreach($list2 as $kk=>$vv){
//                $res[$k]['list2'][$kk]['area_id'] = $vv['area_id'];
//                $res[$k]['list2'][$kk]['area_name'] = $vv['area_name'];
//                $area_id_2 = $vv['area_id'];
//                $list3 =  db('area')->where('area_parent_id',$area_id_2)->field('area_id,area_name')->select();
//                foreach($list3 as $kkk=>$vvv){
//                    $res[$k]['list2'][$kk]['list3'][$kkk]['area_id'] = $vvv['area_id'];
//                    $res[$k]['list2'][$kk]['list3'][$kkk]['area_name'] = $vvv['area_name'];
//                }
//            }
//
//        }
//
//        $data->list = $res;
//        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
//        exit(json_encode($output));


//        $province = db('area')->where(array('area_parent_id' => '0'))->field('area_name,area_id')->select();
//        foreach ($province as $k => $v) {
//            $province_temp[$k] = $v['area_id'];
//        }
//        $province_id_collection = implode(',', $province_temp);
//        $city = db('area')->where(array('area_parent_id' => array('in', $province_id_collection)))->select();
//        foreach ($city as $k => $v) {
//            $city_temp[$k] = $v['area_id'];
//        }
//        $city_id_collection = implode(',', $city_temp);
//        $area = db('area')->where(array('area_parent_id' => array('in', $city_id_collection)))->select();
//        foreach ($area as $k => $v) {
//            $_area[$v['area_parent_id']][] = $v;
//        }
//        foreach ($city as $k => $v) {
//            $_city[$k] = $v;
//            if (empty($_area[$v['area_id']])) {
//                $_city[$k]['area_id'] = null;
//            }else{
//                $_city[$k]['area_id'] = $_area[$v['area_id']];
//            }
//        }
//        foreach ($_city as $k => $v) {
//            $_temp_city[$v['area_parent_id']][] = $v;
//        }
//        foreach ($province as $k => $v) {
//            $_province[$k] = $v;
//            if (empty($_temp_city[$v['area_id']])) {
//                $_province[$k]['area_id'] = null;
//
//            }else{
//                $_province[$k]['area_id'] = $_temp_city[$v['area_id']];
//            }
//        }
//        $output = array('data' => $_province, 'msg' => '查询成功', 'code' => '200');
//        exit(json_encode($output));


        $a = db('area')->where('area_parent_id', '0')->field('area_name,area_id')->select();

        foreach ($a as $k => $v) {
            // 定义一个空数组为二级分类的名字
            $a[$k]['list2'] = [];

            // 查询二级分类。条件为父id等于等级分类的id
            $b = db('area')->where('area_parent_id', $v['area_id'])->field('area_name,area_id')->select();

            foreach ($b as $k1 => $v1) {
                // 把查询出来的结果合并到定义的数组中，合并一级二级分类
                array_push($a[$k]['list2'], $v1);

                // 再定义一个数组为三级分类的名字
                $a[$k]['list2'][$k1]['list3'] = [];

                // 获取三级分类的信息
                $c = db('area')->where('area_parent_id', $v1['area_id'])->field('area_name,area_id')->select();
                foreach ($c as $v2) {
                    // 合并一级二级三级分类
                    array_push($a[$k]['list2'][$k1]['list3'], $v2);
                }
            }
        }
        $data->list = $a;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));



    }



    ////获取商品二级联动列表
    public function goods_twoclassList(){
        $data = json_decode('{}');
        $list=[];


        $res = db('goodsclass')->where('gc_parent_id',0)->field('gc_id,gc_name')->select();
        foreach($res as $k=>$v){
            $res[$k]['id'] = $v['gc_id'];
            unset($res[$k]['gc_id']);
            $res[$k]['name'] = $v['gc_name'];
            unset($res[$k]['gc_name']);
        }
        foreach($res as $k=>$v){
            $gc_id = $v['id'];
            $list2 =  db('goodsclass')->where('gc_parent_id',$gc_id)->select();


            foreach($list2 as $kk=>$vv){
                $res[$k]['list2'][$kk]['id'] = $vv['gc_id'];
                $res[$k]['list2'][$kk]['name'] = $vv['gc_name'];
//                $gc_id_2 = $vv['gc_id'];
//                $list3 =  db('goodsclass')->where('gc_parent_id',$gc_id_2)->field('gc_id,gc_name')->select();
//                foreach($list3 as $kkk=>$vvv){
//                    $res[$k]['list2'][$kk]['list3'][$kkk]['id'] = $vvv['gc_id'];
//                    $res[$k]['list2'][$kk]['list3'][$kkk]['name'] = $vvv['gc_name'];
//                }
            }

        }

        $data->list = $res;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }




    /////添加收货地址
    public function addAddress($token,$name='',$mobile='',$areaId='',$cityId='',$districtId='',$detail='',$default='',$addressId=''){
        $data = json_decode('{}');
//  	if ($token=='' || $name=='' || $mobile=='' || $areaId==''|| $cityId=='' || $districtId=='' || $detail=='' || $default==''){
//
//          $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
//          exit(json_encode($output));
//     }
        if ($token=='' ){

            $output = array('data' => $data, 'msg' => '缺少token', 'code' => '400');
            exit(json_encode($output));
        }
        if ($name=='' ){

            $output = array('data' => $data, 'msg' => '姓名不能为空', 'code' => '400');
            exit(json_encode($output));
        }
        if ($mobile=='' ){

            $output = array('data' => $data, 'msg' => '电话不能为空', 'code' => '400');
            exit(json_encode($output));
        }
        if ($areaId=='' ){

            $output = array('data' => $data, 'msg' => '城市不能为空', 'code' => '400');
            exit(json_encode($output));
        }
        if ($cityId=='' ){

            $output = array('data' => $data, 'msg' => '市不能为空', 'code' => '400');
            exit(json_encode($output));
        }
        if ($districtId=='' ){

            $output = array('data' => $data, 'msg' => '区不能为空', 'code' => '400');
            exit(json_encode($output));
        }
        if ($detail=='' ){

            $output = array('data' => $data, 'msg' => '详细不能为空', 'code' => '400');
            exit(json_encode($output));
        }
        if ($default=='' ){

            $output = array('data' => $data, 'msg' => '默认不能为空', 'code' => '400');
            exit(json_encode($output));
        }

        $userId = $this->getuserid($token);
        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }

        if($default==1){
            $where['member_id'] = $userId;
            $where['address_is_default'] =1;
            db('address')->where($where)->update(['address_is_default'=>0]);
        }
        if($addressId !=''){
            $update['member_id'] = $userId;
            $update['address_realname'] = $name;
            $update['address_mob_phone'] = $mobile;
            $update['area_id'] = $areaId;
            $update['city_id'] = $cityId;
            $update['district_id'] = $districtId;
            $update['address_detail'] = $detail;
            $update['address_is_default'] = $default;
            $id = db('address')->where('address_id',$addressId)->update($update);
            if($id){
                $output = array('data' => $data, 'msg' => '修改成功', 'code' => '200');
                exit(json_encode($output));
            }else{
                $output = array('data' => $data, 'msg' => '系统繁忙', 'code' => '400');
                exit(json_encode($output));
            }
        }else{
            $insert['member_id'] = $userId;
            $insert['address_realname'] = $name;
            $insert['address_mob_phone'] = $mobile;
            $insert['area_id'] = $areaId;
            $insert['city_id'] = $cityId;
            $insert['district_id'] = $districtId;
            $insert['address_detail'] = $detail;
            $insert['address_is_default'] = $default;
            $id = db('address')->insert($insert);
            if($id){
                $output = array('data' => $data, 'msg' => '添加成功', 'code' => '200');
                exit(json_encode($output));
            }else{
                $output = array('data' => $data, 'msg' => '系统繁忙', 'code' => '400');
                exit(json_encode($output));
            }
        }


    }

    /////获取我的地址列表
    public function getAddressList($token=''){
        $data = json_decode('{}');
        $list = [];
        if ( $token=='' ){
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $userId = $this->getuserid($token);
        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }

        $res = db('address')->where('member_id',$userId)->select();
        foreach($res as $v){

            $arr = json_decode('{}');
            $arr->addressId = $v['address_id'];
            $arr->name = $v['address_realname'];
            $arr->mobile = $v['address_mob_phone'];
            $area = db('area')->where('area_id',$v['area_id'])->value('area_name');
            $city = db('area')->where('area_id',$v['city_id'])->value('area_name');
            $district = db('area')->where('area_id',$v['district_id'])->value('area_name');
            $arr->area_name = $area;
            $arr->area_id = $v['area_id'];
            $arr->city_name = $city;
            $arr->city_id = $v['city_id'];
            $arr->district_name = $district;
            $arr->district_id = $v['district_id'];
            $arr->is_default = $v['address_is_default'];
            $arr->detail = $v['address_detail'];
            if($v['address_is_default']==1){
                array_unshift($list,$arr);
            }else{
                $list[] = $arr;
            }

        }
        $data->list = $list;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }

    /////获取地址信息
    public function deleteAddress($addressId=''){
        $data = json_decode('{}');
        if ( $addressId=='' ){
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $id = db('address')->where('address_id',$addressId)->delete();
        if($id){
            $output = array('data' => $data, 'msg' => '删除成功', 'code' => '200');
            exit(json_encode($output));
        }else{
            $output = array('data' => $data, 'msg' => '删除失败', 'code' => '400');
            exit(json_encode($output));
        }
    }
    ////意见反馈
    public function advice($token='',$info='',$title=''){
        $data = json_decode('{}');
        if ( $token=='' ){
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $userId = $this->getuserid($token);
        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }

        $insert['member_id'] = $userId;
        $insert['mallconsult_content'] = $info;
        $insert['mallconsult_title'] = $title;
        $insert['mallconsult_addtime'] = time();
        $id = db('mallconsult')->insert($insert);
        if($id){
            $output = array('data' => $data, 'msg' => '添加成功', 'code' => '200');
            exit(json_encode($output));
        }else{
            $output = array('data' => $data, 'msg' => '添加失败', 'code' => '400');
            exit(json_encode($output));
        }

    }

    ////文章接口
    public function article($type=''){
        $data = json_decode('{}');
        if ( $type=='' ){
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }

        if($type==1){
            $info = db('article')->where('article_id',48)->value('article_content');
            $data->info =htmlspecialchars_decode($info);
        }
        if($type==2){
            $info = db('article')->where('article_id',47)->value('article_content');
            $data->info =htmlspecialchars_decode($info);
        }
        if($type==3){
            $info = db('article')->where('article_id',49)->value('article_content');
            $data->info =htmlspecialchars_decode($info);
        }
        if($type==4){
            $info = db('article')->where('article_id',50)->value('article_content');
            $data->info =htmlspecialchars_decode($info);
        }
        if($type==5){
            $info = db('article')->where('article_id',22)->value('article_content');
            $data->info =htmlspecialchars_decode($info);
        }

        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }


    //////会员权益
    public function vip_article(){
        $data = json_decode('{}');

        $text = db('article')->where('article_id',47)->value('article_content');
        $text = strip_tags(htmlspecialchars_decode($text));
        $data->text = $text;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }

    ///////我的支付宝
    public function myalipay_account($token='',$money='')
    {
        $data = json_decode('{}');
        $userId = $this->getuserid($token);
        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }
        $userinfo = db('member')->where('member_id',$userId)->find();
        $account = $userinfo['member_alipay'];
        $data->account = $account;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }

    ///////支付宝提现
    public function take_money($token='',$money=''){
        $data = json_decode('{}');
        $userId = $this->getuserid($token);
        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }
        $userinfo = db('member')->where('member_id',$userId)->find();
        $user_money = $userinfo['available_predeposit'];
        $account = $userinfo['member_alipay'];
        if($money>$user_money){
            $output = array('data' => $data, 'msg' => '余额不足', 'code' => '400');
            exit(json_encode($output));
        }
        $out_sn = $this->getOrderSn();
        vendor('alipay.AopSdk');
        vendor("alipay.aop.request.AlipayFundTransToaccountTransferRequest");
        $aop = new \AopClient ();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = '2019092067632222';
        $aop->rsaPrivateKey = 'MIIEpAIBAAKCAQEAyqB5fmhNgVAxGFDF/fe4ebRk8GEA5DfFfLNRzsA0/0JfHUcb0pulZHjUTBKONyU2GABBSBdOToYiFRwFxvSSjGbXBt5BF7lorupt6RilEVmtCKtROutwP/SPlIaoXT4VrilblT6NqY8uGApPFAGTBzIrGa2Ib2i1ufopwL7YnBIjzH0CJH+bGkffOvETotU1Pk812CgOLugHQGbCrzFwVeFqciX6JeABZKuMdeynNd5tIZ2sdaq1F8Ol5IMH/MKQ0jcS8vLQwS9743YFtZcQZv0wtqmWIwiUajqZdzmTE3I7JpS40kbXnpZL7hUQqYjmc2QYc/szym0jY8DzTY3AtQIDAQABAoIBAGDXVXmeq/wzsWMnp5j7vgUcvGlOUzi/lvlEUsL3hdzBgefiRl/f16ovPXemHqYoeLP72zdzPA+3d66TGAfAeBH2TKqRqpaGHIwMqr8O8kVakKJmDoqUX6+RWNXpjaoStBXq2kR4AwiYz7TZqHWtUvHLfmHlWCG277OU5kOicrAT+Nm87ZfJXIv6EMpcUEFdvlFXSKqgSFBMkhqPwuErTlwHF44RRnH7wI+N7nfoNIBDQbWt6IGG+50SJgJi1aRVGC3XEf2PR6PoquM6y8yQ39ulSEeo/sd93Vavy6F0BFIDX3NRKx/JivdQRwLqKnzPpB50AjHjy+8XOu48H9RBSXkCgYEA+DEoihPV99/mr8P3tzLSqQOjMiBJx+5kKR410ejtaSwRqUx3lBCLur3Pb9FH3JuGYRSowUZgeMlnXtNS4PST74a8GwlGWASaqkUjnXFy44qTExd8Tlfc24E5UnSVRjmR1JNIKs+Cy8kN2KvCdtorkU8Pbl18ffHjLMUrSyspaG8CgYEA0QBaJYr9p7ItS72MbI7B5ozZJY5O4G0JCbyAb/UtnvoqnF/y+9rCN42us5pXC355tFQZXpgwSNwRnXQC20YDqu55zyf97pWC1NK7GOCR/Il2wIWNPwcsRXOUeTt/Sz+3fXzAnDENnWyHwtLEABCRrquHvc1pGhc3tVpYqwZIkxsCgYA13fV+gm+eLOpUm6PYDx/JrxBsgLWCvyreAcCMnpFokjgDFqWdbTnmfevXyQRfzSGNUH6P9EZb8NqOqi8CxBKXmhaZh5nM4LLw4bCpK0ZUPG9PZXmFR2yX96QJUWRUqYoNKSowoHky4aAvtpeuVAvArfgbbA7pBubXgLO0zNlf4QKBgQC+kZCg/NwOxYNRtXLOJVkeDD2PZfP75M/B5fRCoY9Ijxi9Xyuig7RljTXHpCpMW7VDPQ+o1iHovWj+ZaKZJ3z+pdXBktiSbBdQURmyNEpIt1rlbqD84GB4r0upQxvtlBqOPGsvv/aHHUeo2B9JY9JCLztlUF/OH293V/rTbrZMEwKBgQDV33h75lGTuFW+3u+tCOyp4Ir3yjupuuqV59lGriV0h9PBN85UcqdHZ9eSTWs/u2bOpNdDTWaWFS5KjLlq8CsN6+ad5xbGWKS4hOiBqgmW4Q7a9x7bFNO92hhIC93BdIYf+Qeg1TSuMYdHsNwZqQCSs9ufb7uURgyEid+V4kwyFg==';
        $aop->alipayrsaPublicKey='MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAjYrOrIaD0mQHSTbOwf7ORQPq5fjSIz7LnfBBdF7To1CVIQZA1AxZJns4cUR87G0+e27E/gS3yTbpauHCnNGpBoKdih+yWm1W0grVZJbINzLRmENWjf5OnOohFyG17NuHjyf8xP9A6302sae6DdZ4Q8fb3QG13SAMZKwSeFo7RjuL7EGJKUopilNBFCMhLcJo5gWG/6mExjTilVeg68rm4z00/uCKvGWcke5vuSHUXFgOmjIabmKVPEbG1sQ8hXl4FcXJiZP8ghryR71AKwll88SGhbeZQQSpGRHKHKMSkacg3JWx+TZZt9vOLDc3uC/bDzoCaxBejU56i2GeNrw59wIDAQAB';
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='UTF-8';
        $aop->format='json';
        $request = new \AlipayFundTransToaccountTransferRequest ();
        $request->setBizContent("{" .
            "\"out_biz_no\":\"$out_sn\"," .
            "\"payee_type\":\"ALIPAY_LOGONID\"," .
            "\"payee_account\":\"$account\"," .
            "\"amount\":\"$money\"," .
            "  }");
        $result = $aop->execute ( $request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;

        if(!empty($resultCode)&&$resultCode == 10000){
            $output = array('data' => $data, 'msg' => '提现成功', 'code' => '200');
            exit(json_encode($output));
        } else {
            $output = array('data' => $data, 'msg' => '提现失败', 'code' => '400');
            exit(json_encode($output));
        }
    }

    /////绑定支付宝
    public function  bind_alipay($token,$alipay){
        $data = json_decode('{}');
        $userId = $this->getuserid($token);
        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }
        $update['member_alipay'] = $alipay;
        db('member')->where('member_id',$userId)->update($update);
        $output = array('data' => $data, 'msg' => '绑定成功', 'code' => '200');
        exit(json_encode($output));
    }

    /////余额明细
    public function  moneychange($token,$page){
        $data = json_decode('{}');
        $userId = $this->getuserid($token);
        $page-=1;
        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }

        $res= db('moneychange')->where('userid',$userId)->order('id DESC')->limit($page*20,20)->select();
        $list=[];
        foreach($res as $v){
            $arr = json_decode('{}');
            $arr->money = $v['money'];
            $arr->time = date("Y-m-d H:i",$v['time']);
            $list[] = $arr;
        }
        $data->list = $list;
        $data->allmoney=db('member')->where('member_id',$userId)->value('available_predeposit');
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }


    /////编辑个人信息展示
    public function userInfo_show($token=''){
        $data = json_decode('{}');
        $userId = $this->getuserid($token);
        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }

        $member=db('member')->where('member_id',$userId)->find();
        $data->nickname = $member['member_name'];
        $data->avatar = $member['member_avatar'];
        $data->mobile = $member['member_truename'];
        $data->identity = $member['member_identity'];
        $temp1 = explode(",",$member['member_identity']);
        $str1 ='';
        foreach($temp1 as $v){
            $str1 .= db('identity')->where('identity_id',$v)->value('identity_name');
        }
        $data->identity_str = $str1;
        $data->main = $member['main'];
        $temp2 = explode(",",$member['main']);
        $str2 ='';
        foreach($temp2 as $v){
            $str2 .= db('goodsclass')->where('gc_id',$v)->value('gc_name');
        }
        $data->identity_str = $str1;
        $data->main_str = $str2;
        $data->company_name = $member['company_name'];
        $data->company_address = $member['company_address'];
        $data->company_intro = $member['company_intro'];
        $data->area_id = $member['member_areaid'];
        $data->city_id = $member['member_cityid'];
        $data->province_id = $member['member_provinceid'];

        $data->area_name = db('area')->where('area_id',$member['member_areaid'])->value('area_name');
        $data->city_name = db('area')->where('area_id',$member['member_cityid'])->value('area_name');
        $data->province_name = db('area')->where('area_id',$member['member_provinceid'])->value('area_name');

        $output = array('data' => $data, 'msg' => '修改成功', 'code' => '200');
        exit(json_encode($output));

    }

    //////个人信息
    public function userInfo($token=''){
        $data = json_decode('{}');
        $userId = $this->getuserid($token);
        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }
        $data->userid = $userId;
        $info = db('member')->where('member_id',$userId)->find();
        $data->avatar = $info['member_avatar'];
        if($info['member_name']){
            $data->nickname = $info['member_name'];
        }
        $data->type='';
        if($info['member_auth']==1){
            $auth_info = db('member_auth')->where('userid',$userId)->where('type',1)->where('state',1)->find();
            $data->type=1;
            $data->name =  $info['member_name'];
            $area = db('area')->where('area_id',$auth_info['area_id'])->value('area_name');
            $city = db('area')->where('area_id',$auth_info['city_id'])->value('area_name');
            $district = db('area')->where('area_id',$auth_info['district_id'])->value('area_name');
            $data->area = $area.$city.$district;
        }
        if($info['shop_auth']==1){
            $auth_info = db('member_auth')->where('userid',$userId)->where('type',2)->where('state',1)->find();
            $data->type=2;
            $data->name = $auth_info['shop_name'];
            $area = db('area')->where('area_id',$auth_info['area_id'])->value('area_name');
            $city = db('area')->where('area_id',$auth_info['city_id'])->value('area_name');
            $district = db('area')->where('area_id',$auth_info['district_id'])->value('area_name');
            $data->area = $area.$city.$district;
        }
        if($info['company_auth']==1){
            $auth_info = db('member_auth')->where('userid',$userId)->where('type',3)->where('state',1)->find();
            $data->type=3;
            $data->name = $auth_info['company_name'];
            $area = db('area')->where('area_id',$auth_info['area_id'])->value('area_name');
            $city = db('area')->where('area_id',$auth_info['city_id'])->value('area_name');
            $district = db('area')->where('area_id',$auth_info['district_id'])->value('area_name');
            $data->area = $area.$city.$district;
        }
        if($info['supply_auth']==1){
            $auth_info = db('membersupply')->where('userid',$userId)->where('state',1)->find();
            $data->type=4;
            $data->name = $auth_info['company_name'];
            $area = db('area')->where('area_id',$auth_info['area_id'])->value('area_name');
            $city = db('area')->where('area_id',$auth_info['city_id'])->value('area_name');
            $district = db('area')->where('area_id',$auth_info['district_id'])->value('area_name');
            $data->area = $area.$city.$district.$auth_info['address'];
        }


        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }


    /////用户身份
    public function identity($token=''){
        $data = json_decode('{}');
        $member_info = db('member')->where('token',$token)->find();
        if($member_info['member_auth']==1 ||  $member_info['shop_auth']==1 ||$member_info['company_auth']==1){
            $data->is_shop = 1;
        }else{
            $data->is_shop = 0;
        }
        if($member_info['supply_auth']==1){
            $data->is_supply = 1;
        }else{
            $data->is_supply = 0;
        }

        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }

    /////材料列表
    public function material_list(){
        $data = json_decode('{}');
        $list = db('material')->select();
        $arr2=[];
        foreach($list as $k=>$v){
            $arr = json_decode('{}');
            $arr->id = $v['material_id'];
            $arr->name =  $v['material_name'];
            $arr2[] = $arr;
        }


        $data->list = $arr2 ;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }

    /////材质列表
    public function material2_list(){
        $data = json_decode('{}');
        $list = db('material2')->select();
        $arr2=[];
        foreach($list as $k=>$v){
            $arr = json_decode('{}');
            $arr->id = $v['material2_id'];
            $arr->name =  $v['material2_name'];
            $arr2[] = $arr;
        }


        $data->list = $arr2 ;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }

    /////属性列表
    public function material3_list(){
        $data = json_decode('{}');
        $list = db('material3')->select();
        $arr2=[];
        foreach($list as $k=>$v){
            $arr = json_decode('{}');
            $arr->id = $v['material3_id'];
            $arr->name =  $v['material3_name'];
            $arr2[] = $arr;
        }


        $data->list = $arr2 ;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }
    ////修改个人信息
    public function changeuserInfo($token='',$avatar=''){
        $data = json_decode('{}');
        $userId = $this->getuserid($token);
        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }
        if($_FILES){

            $img = $_FILES['avatar'];

            $date = date('Y-m-d');
            $time = time();
            $rand = rand(10000,99999);
            $img_name = $rand . $time . '.jpg';
            $savepath = 'uploads/image/' . $date . '/';
            if (!file_exists($savepath)) {
                mkdir($savepath, 0777, true);
            }
            $road = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name;
            move_uploaded_file($_FILES["avatar"]["tmp_name"],$savepath.$img_name);
            $update['member_avatar'] = $road;
        }

        $id = db('member')->where('member_id',$userId)->update($update);

        $output = array('data' => $data, 'msg' => '修改成功', 'code' => '200');
        exit (json_encode($output));
    }
    //////客服微信电话
    ///
    public function service_info(){
        $phone = db('config')->where('code','site_phone')->value('value');
        $vx_num = db('config')->where('code','site_tel400')->value('value');
        $data = json_decode('{}');
        $data->phone = $phone;
        $data->vx_num = $vx_num;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }

    ///////供应商认证
    public function membersupply_auth($manage_type='',$token='',$image1='',$image2='',$image3='',$image4='',$image5='',$image6='',$image7=''
        ,$image8='',$image9='',$image10='',$image11='',$image12='',$image13='',$image14='',$main='',$name='',$companyname='',
                                      $intro='',$mobile1='',$mobile2='',$mobile3='',$legal='',$money='',$time='',$address,$area_id,$city_id,$district_id){
        $data = json_decode('{}');

        if ($token==''){
            $output = array('data' => $data, 'msg' => '缺少token参数', 'code' => '400');
            exit(json_encode($output));
        }
        $userId = $this->getuserid($token);
        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }
        $update['userid'] = $userId;
        $update['manage_type'] = $manage_type;
        $update['mobile1'] = $mobile1;
        $update['mobile2'] = $mobile2;
        $update['company_name'] = $companyname;
        $update['mobile3'] = $mobile3;
        $update['main'] = $main;
        $update['intro'] = $intro;
        $update['legal'] = $legal;
        $update['money'] = $money;
        $update['time'] = $time;
        $update['name'] = $name;
        $update['address'] = $address;
        $update['area_id'] = $area_id;
        $update['city_id'] = $city_id;
        $update['district_id'] = $district_id;

        if($_FILES){

            $date = date('Y-m-d');
            $time = time();
            $rand1 = rand(10000,99999);
            $rand2 = rand(10000,99999);
            $rand3 = rand(10000,99999);
            $rand4 = rand(10000,99999);
            $rand5 = rand(10000,99999);
            $rand6 = rand(10000,99999);
            $rand7 = rand(10000,99999);
            $rand8 = rand(10000,99999);
            $rand9 = rand(10000,99999);
            $rand10 = rand(10000,99999);
            $rand11 = rand(10000,99999);
            $rand12 = rand(10000,99999);
            $rand13 = rand(10000,99999);
            $rand14 = rand(10000,99999);
            $img_name1 = $rand1 . $time . '.jpg';
            $img_name2 = $rand2 . $time . '.jpg';
            $img_name3 = $rand3 . $time . '.jpg';
            $img_name4 = $rand4 . $time . '.jpg';
            $img_name5 = $rand5 . $time . '.jpg';
            $img_name6 = $rand6 . $time . '.jpg';
            $img_name7 = $rand7 . $time . '.jpg';
            $img_name8 = $rand8 . $time . '.jpg';
            $img_name9 = $rand9 . $time . '.jpg';
            $img_name10 = $rand10 . $time . '.jpg';
            $img_name11 = $rand11 . $time . '.jpg';
            $img_name12 = $rand12 . $time . '.jpg';
            $img_name13 = $rand13 . $time . '.jpg';
            $img_name14 = $rand14 . $time . '.jpg';

            $savepath = 'uploads/image/' . $date . '/';
            if (!file_exists($savepath)){
                mkdir($savepath, 0777, true);
            }

            $road1 = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name1;
            $road2 = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name2;
            $road3 = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name3;
            $road4 = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name4;
            $road5 = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name5;
            $road6 = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name6;
            $road7 = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name7;
            $road8 = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name8;
            $road9 = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name9;
            $road10 = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name10;
            $road11 = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name11;
            $road12 = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name12;
            $road13= 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name13;
            $road14= 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name14;
            if(isset($_FILES["image1"])) {
                move_uploaded_file($_FILES["image1"]["tmp_name"], $savepath . $img_name1);
                $update['image1'] = $road1;
            }
            if(isset($_FILES["image2"])) {
                move_uploaded_file($_FILES["image2"]["tmp_name"], $savepath . $img_name2);
                $update['image2'] = $road2;
            }
            if(isset($_FILES["image3"])) {
                move_uploaded_file($_FILES["image3"]["tmp_name"], $savepath . $img_name3);
                $update['image3'] = $road3;
            }
            if(isset($_FILES["image4"])){
                move_uploaded_file($_FILES["image4"]["tmp_name"],$savepath.$img_name4);
                $update['image4'] = $road4;
            }
            if(isset($_FILES["image5"])){
                move_uploaded_file($_FILES["image5"]["tmp_name"],$savepath.$img_name5);
                $update['image5'] = $road5;
            }
            if(isset($_FILES["image6"])){
                move_uploaded_file($_FILES["image6"]["tmp_name"],$savepath.$img_name6);
                $update['image6'] = $road6;
            }
            if(isset($_FILES["image7"])){
                move_uploaded_file($_FILES["image7"]["tmp_name"],$savepath.$img_name7);
                $update['image7'] = $road7;
            }
            if(isset($_FILES["image8"])){
                move_uploaded_file($_FILES["image8"]["tmp_name"],$savepath.$img_name8);
                $update['image8'] = $road8;
            }
            if(isset($_FILES["image9"])){
                move_uploaded_file($_FILES["image9"]["tmp_name"],$savepath.$img_name9);
                $update['image9'] = $road9;
            }
            if(isset($_FILES["image10"])){
                move_uploaded_file($_FILES["image10"]["tmp_name"],$savepath.$img_name10);
                $update['image10'] = $road10;
            }
            if(isset($_FILES["image11"])){
                move_uploaded_file($_FILES["image11"]["tmp_name"],$savepath.$img_name11);
                $update['image11'] = $road11;
            }
            if(isset($_FILES["image12"])){
                move_uploaded_file($_FILES["image12"]["tmp_name"],$savepath.$img_name12);
                $update['image12'] = $road12;
            }
            if(isset($_FILES["image13"])){
                move_uploaded_file($_FILES["image13"]["tmp_name"],$savepath.$img_name13);
                $update['image13'] = $road13;
            }
            if(isset($_FILES["image14"])){
                move_uploaded_file($_FILES["image14"]["tmp_name"],$savepath.$img_name14);
                $update['image14'] = $road14;
            }


        }



        db('membersupply')->insert($update);
        $output = array('data' => $data, 'msg' => '提交成功', 'code' => '200');
        exit(json_encode($output));

    }

    /////供应商入驻信息
    public function  membersupply_date($token){
        $data = json_decode('{}');
        if ($token==''){
            $output = array('data' => $data, 'msg' => '缺少token参数', 'code' => '400');
            exit(json_encode($output));
        }
        $userId = $this->getuserid($token);
        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }
        $enddate = db('member')->where('member_id',$userId)->value('membervip_endtime');
        $time = time();
        if($time>$enddate){
            $output = array('data' => $data, 'msg' => '供应商已过期', 'code' => '400');
            exit(json_encode($output));
        }
        $date = round(($enddate-$time)/86400);
        $data->date =(string)$date;
        $data->name = db('membersupply')->where('userid',$userId)->where('state',1)->value('company_name');
        $output = array('data' =>$data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }





    //////认证审核
    public function member_auth($type='',$token='',$image1='',$image2='',$image3='',$image4='',$image5='',$image6='',$idcard='',$name='',
                                $shop_name='',$mobile='',$company_name='',$area_id='',$city_id='',$district_id=''){
        $data = json_decode('{}');

        if ($type==''){
            $output = array('data' => $data, 'msg' => '缺少type参数', 'code' => '400');
            exit(json_encode($output));
        }
        if ($token==''){
            $output = array('data' => $data, 'msg' => '缺少token参数', 'code' => '400');
            exit(json_encode($output));
        }
        $userId = $this->getuserid($token);
        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }
        db('member_auth')->where('userid',$userId)->where('type',$type)->delete();
        $update['userid'] = $userId;
        $update['type'] = $type;
        $update['mobile'] = $mobile;
        $update['idcard'] = $idcard;
        $update['name'] = $name;
        $update['area_id'] = $area_id;
        $update['city_id'] = $city_id;
        $update['district_id'] = $district_id;


        if($_FILES){

            $date = date('Y-m-d');
            $time = time();

            $rand1 = rand(10000,99999);
            $rand2 = rand(10000,99999);
            $rand3 = rand(10000,99999);
            $rand4 = rand(10000,99999);
            $rand5 = rand(10000,99999);
            $rand6 = rand(10000,99999);
            $img_name1 = $rand1 . $time . '.jpg';
            $img_name2 = $rand2 . $time . '.jpg';
            $img_name3 = $rand3 . $time . '.jpg';
            $img_name4 = $rand4 . $time . '.jpg';
            $img_name5 = $rand5 . $time . '.jpg';
            $img_name6 = $rand6 . $time . '.jpg';
            $savepath = 'uploads/image/' . $date . '/';
            if (!file_exists($savepath)){
                mkdir($savepath, 0777, true);
            }

            $road1 = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name1;
            $road2 = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name2;
            $road3 = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name3;
            $road4 = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name4;
            $road5 = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name5;
            $road6 = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name6;
            if(isset($_FILES["image1"])) {
                move_uploaded_file($_FILES["image1"]["tmp_name"], $savepath . $img_name1);
                $update['image1'] = $road1;
            }
            if(isset($_FILES["image2"])) {
                move_uploaded_file($_FILES["image2"]["tmp_name"], $savepath . $img_name2);
                $update['image2'] = $road2;
            }
            if(isset($_FILES["image3"])) {
                move_uploaded_file($_FILES["image3"]["tmp_name"], $savepath . $img_name3);
                $update['image3'] = $road3;
            }
            if(isset($_FILES["image4"])){
                move_uploaded_file($_FILES["image4"]["tmp_name"],$savepath.$img_name4);
                $update['image4'] = $road4;
            }
            if(isset($_FILES["image5"])){
                move_uploaded_file($_FILES["image5"]["tmp_name"],$savepath.$img_name5);
                $update['image5'] = $road5;
            }

        }

        $update['shop_name'] = $shop_name;
        $update['company_name'] = $company_name;


        db('member_auth')->insert($update);
        $output = array('data' => $data, 'msg' => '提交成功', 'code' => '200');
        exit(json_encode($output));

    }

    /////////////////我的下单收到列表
    /// type 1下单  2收到
    public function showorder_list($token='',$type=''){
        $data = json_decode('{}');
        if ( $token=='' ){
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        if($type==''){
            $output = array('data' => $data, 'msg' => '缺少type参数', 'code' => '400');
            exit(json_encode($output));
        }
        $userId = $this->getuserid($token);

        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }
        if($type==1){
            $where['user_id'] = $userId;
        }else{
            $where['goods_userid'] = $userId;
        }
        $res = db('showorder')->where($where)->select();
        $list=[];
        foreach($res as $v){
            $arr = json_decode('{}');
            $arr->id = $v['id'];
            $arr->title = $v['title'];
            $arr->time = date("Y-m-d",$v['time']);
            $arr->is_see = $v['is_see'];
            $list[] = $arr;
        }
        $data->list = $list;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }


    ////////下单详情
    ///tag 1 收到订单查看
    public  function showorder_detail($id='' ,$type=''){
        $data = json_decode('{}');
        if ( $id=='' ){
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $info = db('showorder')->where('id',$id)->find();
        if($type ==2){
            db('showorder')->where('id',$id)->update(['is_see'=>1]);
        }
        $data->goods_id = $info['goods_id'];
        $data->title = $info['title'];
        $data->num = $info['num'];
        $data->name = $info['name'];
        $data->mobile = $info['mobile'];
        $data->company_name = $info['company_name'];
        $data->company_address = $info['company_address'];
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));

    }

    //////////////下单评论
    public  function showorder_discuss($goods_id='',$token='',$content=''){
        $data = json_decode('{}');
        if ( $goods_id=='' ){
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        if ( $token=='' ){
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $userId = $this->getuserid($token);

        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }
        $insert['userid'] = $userId;
        $insert['goods_id'] = $goods_id;
        $insert['time'] = time();
        $insert['discuss'] = $content;
        db('order_discuss')->insert($insert);
        $output = array('data' => json_decode('{}'), 'msg' => '评论成功', 'code' => '200');
        exit(json_encode($output));

    }

    ///////下单评论列表
    public function  orderdiscuss_list($token,$goods_id,$page=''){
        $page-=1;
        $data = json_decode('{}');
        if ($token == '' || $goods_id=='' ) {
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $userId = $this->getuserid($token);
        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }

        $where['goods_id'] = $goods_id;
        $res = db('order_discuss')->where($where)->order('time DESC')->limit($page*20,20)->select();
        $count = db('order_discuss')->where($where)->count();
        $data->count = $count;
        $totalpage = ceil($count/20);
        $data->totalpage = $totalpage;
        foreach($res as $k=> $v){
            $userid = $v['userid'];
            $member = db('member')->where('member_id',$userid)->find();
            $res[$k]['avatar'] = $member['member_avatar'];
            $res[$k]['mmeber_name'] = $member['member_name'];

            $time = $v['time'];
            $date = $this->formatTime($time);
            $res[$k]['date'] = $date;
            unset($res[$k]['time']);
        }
        $data->list =$res;

        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));



    }



    /////////我的商品列表
    public function mygoods_list($token=''){
        $data = json_decode('{}');
        if ($token == '') {
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $userId = $this->getuserid($token);
        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }
        $where['goods_state']= 1;
        $where['user_id']= $userId;
        $list=[];
        $time = time();





        $res2 =  db('goods')->where($where)->select();

        foreach ($res2 as $v) {
            $common_info = db('goodscommon')->where('goods_commonid',$v['goods_id'])->find();
            $arr = json_decode('{}');
            $arr->image = 'http://' . $_SERVER['HTTP_HOST'] . "/uploads/home/store/goods/" . $v['goods_image'];
            $arr->name = $v['goods_name'];
            $arr->id = $v['goods_id'];
            $arr->price_unit = $v['price_unit'];
            $arr->num_unit = $v['num_unit'];
            $arr->price = (string)$v['goods_price'];
            $arr->area_id = (string)$v['area_id'];
            $arr->area_name = db('area')->where('area_id',$v['area_id'])->value('area_name');
            $arr->city_name = db('area')->where('area_id',$v['city_id'])->value('area_name');
            $arr->district_name = db('area')->where('area_id',$v['district_id'])->value('area_name');
            $arr->city_id = (string)$v['city_id'];
            $arr->district_id = (string)$v['district_id'];
            $arr->gc_id_1 = (string)$v['gc_id_1'];
            $arr->gc_id_2 = (string)$v['gc_id_2'];
            $arr->gc_id_3 = (string)$v['gc_id_3'];
            $arr->gc_id_1_name = db('goodsclass')->where('gc_id',$v['gc_id_1'])->value('gc_name');
            $arr->gc_id_2_name = db('goodsclass')->where('gc_id',$v['gc_id_2'])->value('gc_name');

            $arr->storage = $common_info['goods_storage'];
            $arr->body = $common_info['goods_body'];
            $arr->factory_name = $common_info['factory_name'];
            $arr->factory_address = $common_info['factory_address'];
            $arr->ermo = $common_info['ermo'];
            $arr->make_level = $common_info['make_level'];
            $arr->level = $common_info['level'];
            $arr->tese_level = $common_info['tese_level'];
            $arr->color = $common_info['color'];
            $arr->use = $common_info['use'];
            $arr->exper_date = $common_info['exper_date'];
            $arr->material_id = $common_info['material_id'];
            $image_arr = db('goodsimages')->where('goods_commonid',$v['goods_id'])->field('goodsimage_url')->select();
            $image2=[];
            foreach($image_arr as $v){
                $image2[] = 'http://' . $_SERVER['HTTP_HOST']."/uploads/home/store/goods/".$v['goodsimage_url'];
            }
            $arr->image = $image2;
            $list[] = $arr;
        }

        $data->list = $list;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }

    public function  yellowpage_list($page='',$identity='',$area='',$city='',$main=''){
        $data = json_decode('{}');
        $page-=1;
        $where='';
        if($identity !=''){
            $where['member_identity'] = array('like',"%".$identity."%");
        }else{
            $where['member_identity'] = array('neq','');
        }
        if($main !=''){
            $where['main'] = array('like',"%".$main."%");
        }else{
            $where['main'] = array('neq','');
        }
        if($city !=''){
            $where['member_cityid'] = $city;
        }else{
            $where['member_cityid'] = array('neq','');
        }

        $where['member_name'] = array('neq','');
        $where['member_mobile'] = array('neq','');
        $where['member_areaid'] = array('neq','');
        $where['member_provinceid'] = array('neq','');
        $where['company_name'] = array('neq','');
        $where['company_intro'] = array('neq','');
        $list2=[];
        $list = db('member')->where($where)->order('member_id DESC')->limit($page*20,20)->select();
        foreach($list as $k=>$v){
            $userId = $v['member_id'];
            $userinfo = db('member')->where('member_id',$userId)->find();
            $arr = json_decode('{}');
            $arr->member_id = $v['member_id'];
            $arr->name = $v['member_name'];
            $arr->avatar = $v['member_avatar'];
            $area_name = db('area')->where('area_id',$v['member_areaid'])->value('area_name');
            $city_name = db('area')->where('area_id',$v['member_cityid'])->value('area_name');
            $arr->area = $area_name.$city_name;
            $identity_arr = explode(",",$v['member_identity']);
            $str = '';

            foreach($identity_arr as $vv){
                $identity_name = db('identity')->where('identity_id',$vv)->value('identity_name');
                $str .= $identity_name.",";

            }
            $str = substr($str,0,-1);
            $arr->identity_str = $str;
            $arr->mobile = $v['member_mobile'];
            $temp = db('supply')->where('userid',$v['member_id'])->order('id DESC')->find();

            if($temp){
                $arr->supply = $temp['type'];
                $gc_id_1_name = db('goodsclass')->where('gc_id',$temp['gc_id_1'])->value('gc_name');
                $gc_id_2_name = db('goodsclass')->where('gc_id',$temp['gc_id_2'])->value('gc_name');
                $arr->gc_id_1_name = $gc_id_1_name;
                $arr->gc_id_2_name = $gc_id_2_name;
            }else{
                $arr->gc_id_1_name = '';
                $arr->gc_id_2_name = '';
                $arr->supply = '';
            }
            $list2[] = $arr;
        }

        $count = count($list);
        $totalpage = ceil($count/20);
        $data->totalpage = $totalpage;


        $data->list = $list2;

        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }
    //////黄页详情
    public function yellowpage_detail($token='',$member_id){
        $data = json_decode('{}');
        $info = db('member')->where('member_id',$member_id)->find();
        $data->name = $info['member_name'];
        $data->mobile =$info['member_mobile'];
        $data->company =$info['company_name'];
        $data->company_address =$info['company_address'];
        $temp1 = db('goodscommon')->where('user_id',$member_id)->select();
        if(count($temp1) !=0){
            $shop =[];
            foreach($temp1 as $v){
                $arr = json_decode('{}');;
                $goodsinfo = db('goods')->where('goods_id',$v['goods_commonid'])->find();
                $arr->image = 'http://' . $_SERVER['HTTP_HOST'] . "/uploads/home/store/goods/" . $v['goods_image'];
                $arr->goods_name = $v['goods_name'];
                $arr->id = $v['goods_commonid'];
                $arr->price = $goodsinfo['goods_price'];
                $arr->price_unit = $goodsinfo['price_unit'];
                $shop[] = $arr;
            }
            $data->shop = $shop;

        }else{
            $data->shop = [];
        }
        $supply1 = db('supply')->where('type',1)->where('userid',$member_id)->order('id DESC')->find();
        $supply2 = db('supply')->where('type',2)->where('userid',$member_id)->order('id DESC')->find();
        if($supply1){
            $supply['supply_id'] = $supply1['id'];
            $supply['image'] = $supply1['image1'];
            $supply['name'] = $supply1['title'];
            $supply['price'] = $supply1['supply_price'];
            $supply['price_unit'] = $supply1['price_unit'];
            $data->supply_arr = $supply;
        }else{
            $data->supply_arr = json_decode('{}');
        }

        if($supply2){
            $buy['buy_id'] = $supply2['id'];
            $buy['name'] = $supply2['title'];
            $area = db('area')->where('area_id',$supply2['area_id'])->value('area_name');
            $city = db('area')->where('area_id',$supply2['city_id'])->value('area_name');
            $district = db('area')->where('area_id',$supply2['district_id'])->value('area_name');
            $buy['area'] = $area.$city.$district;
            $buy['num'] = $supply2['supply_num'];
            $buy['num_unit'] = $supply2['num_unit'];
            $buy['price'] = $supply2['supply_price'];
            $buy['price_unit'] = $supply2['price_unit'];
            $data->buy_arr = $buy;
        }else{
            $data->buy_arr = json_decode('{}');;
        }
        $data->intro = $info['company_intro'];
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');

        exit(json_encode($output));
    }
    //////添加收藏
    public function addCollect($token='',$goodsId='',$active=''){
        $data = json_decode('{}');
        if ($goodsId=='' || $token=='' ){
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $userId = $this->getuserid($token);
        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }

        if($active==2){
            $id = db('collect')->where(['user_id'=>$userId,'goods_id'=>$goodsId])->delete();
            if($id){
                $output = array('data' => $data, 'msg' => '取消成功', 'code' => '200');
                exit(json_encode($output));
            }else{
                $output = array('data' => $data, 'msg' => '取消失败', 'code' => '400');
                exit(json_encode($output));
            }
        }else{
            $goodsInfo = db('goods')->where('goods_id',$goodsId)->find();
            $is_collect = db('collect')->where(['user_id'=>$userId,'goods_id'=>$goodsId])->find();
            if($is_collect){
                $output = array('data' => $data, 'msg' => '您已经收藏', 'code' => '400');
                exit(json_encode($output));
            }
            $insert['user_id'] = $userId;
            $insert['goods_id'] = $goodsId;
            $insert['time'] = time();

            $id = db('collect')->insert($insert);
            if($id){
                $output = array('data' => $data, 'msg' => '添加成功', 'code' => '200');
                exit(json_encode($output));
            }else{
                $output = array('data' => $data, 'msg' => '系统繁忙', 'code' => '400');
                exit(json_encode($output));
            }
        }




    }

    ////取消收藏
    public function deleteCollect($goodsId='',$token=''){
        $data = json_decode('{}');
        if ($goodsId=='' || $token==''){
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $userid = $this->getuserid($token);
        if($userid==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }

        $id = db('collect')->where(['user_id'=>$userid,'goods_id'=>$goodsId])->delete();
        if($id){
            $output = array('data' => $data, 'msg' => '取消成功', 'code' => '200');
            exit(json_encode($output));
        }else{
            $output = array('data' => $data, 'msg' => '取消失败', 'code' => '400');
            exit(json_encode($output));
        }

    }

    ////获取收藏列表
    public function CollectList($token,$page='',$type=''){
        $data = json_decode('{}');
        $list = [];
        if ( $token=='' ||$page=='' || $type==''){
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $userId = $this->getuserid($token);
        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }

        $page-=1;
        if($type==1) {

            $res = db('collect')->where('user_id', $userId)->limit($page * 20, 20)->select();
            $count = db('collect')->where('user_id', $userId)->count();
            $totalpage = ceil($count / 20);
            $data->totalpage = $totalpage;
            foreach ($res as $v) {
                $arr = json_decode('{}');
                $goods_id = $v['goods_id'];
                $arr->id = $goods_id;

                $goodsinfo = db('goods')->where('goods_id', $goods_id)->find();
                if ($goodsinfo != '') {
                    $arr->image = 'http://' . $_SERVER['HTTP_HOST'] . "/uploads/home/store/goods/" . $goodsinfo['goods_image'];
                    $arr->price = $goodsinfo['goods_price'];
                    $arr->price_unit = $goodsinfo['price_unit'];
                    $arr->name = $goodsinfo['goods_name'];
                    $list[] = $arr;
                }

            }
            $data->list = $list;
            $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
            exit(json_encode($output));
        }

        if($type==2){

            $res = db('supplycollect')->where('user_id',$userId)->where('type',1)->limit($page*20,20)->select();
            $count = db('collect')->where('user_id',$userId)->count();
            $totalpage = ceil($count/20);
            $data->totalpage = $totalpage;
            foreach($res as $v){
                $arr = json_decode('{}');
                $supply_id = $v['supply_id'];
                $arr->id = $supply_id;

                $goodsinfo = db('supply')->where('id',$supply_id)->find();
                if($goodsinfo !=''){
                    $arr->image = $goodsinfo['image1'];
                    $arr->price = $goodsinfo['supply_price'];
                    $arr->price_unit = $goodsinfo['price_unit'];
                    $arr->name = $goodsinfo['title'];
                    $list[] = $arr;
                }

            }
            $data->list = $list;
            $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
            exit(json_encode($output));
        }

        if($type==3){

            $res = db('supplycollect')->where('user_id',$userId)->where('type',2)->limit($page*20,20)->select();
            $count = db('collect')->where('user_id',$userId)->count();
            $totalpage = ceil($count/20);
            $data->totalpage = $totalpage;
            foreach($res as $v){
                $arr = json_decode('{}');
                $supply_id = $v['supply_id'];
                $arr->id = $supply_id;
                $goodsinfo = db('supply')->where('id',$supply_id)->find();
                if($goodsinfo !=''){
                    $area = db('area')->where('area_id',$goodsinfo['area_id'])->value('area_name');
                    $city = db('area')->where('area_id',$goodsinfo['city_id'])->value('area_name');
                    $district = db('area')->where('area_id',$goodsinfo['district_id'])->value('area_name');
                    $arr->area  =$area.$city.$district;
                    $arr->num = $goodsinfo['supply_num'];
                    $arr->num_unit = $goodsinfo['num_unit'];
                    $arr->price = $goodsinfo['supply_price'];
                    $arr->price_unit = $goodsinfo['price_unit'];
                    $arr->price_unit = $goodsinfo['price_unit'];
                    $arr->name = $goodsinfo['title'];
                    $list[] = $arr;
                }

            }
            $data->list = $list;
            $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
            exit(json_encode($output));
        }

    }


    ////获取充值列表
    public function chargeList(){
        $data = json_decode('{}');
        $res = db('membervip')->select();
        foreach ($res as $k=>$v){
            $res[$k]['charge_time'] = $res[$k]['charge_time']."个月";
        }
        $data->list = $res;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }



    //////我发布的列表
    public function my_publish($token='',$type='',$page='',$state=''){
        $data = json_decode('{}');
        $userid = $this->getuserid($token);
        $page-=1;
        if($userid==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }
        if($type==1){
            $where['state'] = $state;
            $where['userid'] = $userid;
            $order='time DESC';
            $res = db('supply')->where($where)->limit($page*20,20)->order($order)->select();
            $count =  db('supply')->where($where)->count();
            $totalpage = ceil($count/20);
            $data->totalpage = $totalpage;
            $list = [];
            foreach($res as $v){
                $arr = json_decode('{}');
                $arr->id = $v['id'];
                $arr->name = $v['name'];
                $arr->time  = date("Y-m-d",$v['time']);
                $list[] = $arr;
            }
            $data->list = $list;
            $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
            exit(json_encode($output));

        }

        if($type==2){

            $where['user_id'] = $userid;
            if($state==0){
                $where['goods_state'] = 1;
            }else{
                $where['goods_state'] = 2;
            }
            $where['goods_type'] = 1;
            $order='goods_id DESC';
            $res = db('goods')->where($where)->limit($page*20,20)->order($order)->select();
            $count =  db('goods')->where($where)->count();
            $totalpage = ceil($count/20);
            $data->totalpage = $totalpage;
            $list = [];
            foreach($res as $v){
                $arr = json_decode('{}');
                $common_info =  db('goodscommon')->where('goods_commonid',$v['goods_id'] )->find();
                $arr->image = 'http://' . $_SERVER['HTTP_HOST'] . "/uploads/home/store/goods/" . $v['goods_image'];
                $arr->name = $v['goods_name'];
                $arr->id = $v['goods_id'];
                $arr->intro = $common_info['goods_body'];
                $arr->num = $common_info['goods_storage'];
                $arr->time =date("Y-m-d",$v["goods_addtime"]);
                $arr->price = (string)$v['goods_price'];
                $list[] = $arr;
            }
            $data->list =$list;
            $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
            exit(json_encode($output));
        }

        if($type==3){

            $where['user_id'] = $userid;
            $where['goods_type'] = 2;
            if($state==0){
                $where['goods_state'] = 1;
            }else{
                $where['goods_state'] = 2;
            }
            $order='goods_id DESC';
            $res = db('goods')->where($where)->limit($page*20,20)->order($order)->select();
            $count =  db('goods')->where($where)->count();
            $totalpage = ceil($count/20);
            $data->totalpage = $totalpage;
            $list = [];
            foreach($res as $v){
                $arr = json_decode('{}');
                $common_info =  db('goodscommon')->where('goods_commonid',$v['goods_id'] )->find();
                $arr->image = 'http://' . $_SERVER['HTTP_HOST'] . "/uploads/home/store/goods/" . $v['goods_image'];
                $arr->name = $v['goods_name'];
                $arr->id = $v['goods_id'];
                $arr->intro = $common_info['goods_body'];
                $arr->num = $common_info['goods_storage'];
                $arr->time =date("Y-m-d",$v["goods_addtime"]);
                $arr->price = (string)$v['goods_price'];
                $list[] = $arr;
            }
            $data->list =$list;
            $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
            exit(json_encode($output));
        }

    }




    ///发布询价
    public function publish_askprice($type='',$token='',$name='',$gc_id_1='',$gc_id_2='',$gc_id_3='',$num='',$image1='',$image2='',$image3='',$image4=''
        ,$image5='',$image6='',$image7='',$image8='',$image9='',$intro='')
    {
        $data = json_decode('{}');

        $userid = $this->getuserid($token);

        if($userid==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }

        $userinfo = db('member')->where('member_id',$userid)->find();
        if($userinfo['supply_auth']==1 && $userinfo['membervip_endtime']<time()){

            $output = array('data' => json_decode('{}'), 'msg' => '您不是供应商或供应商已过期', 'code' => '400');
            exit(json_encode($output));
        }else{
            if( $userinfo['member_auth']==0 && $userinfo['shop_auth']==0 && $userinfo['company_auth']==0 && $userinfo['supply_auth']==0){
                $output = array('data' => json_decode('{}'), 'msg' => '您不是采购商无法发布询价', 'code' => '400');
                exit(json_encode($output));
            }

        }



        $insert['type'] = $type;
        $insert['name'] = $name;
        $insert['userid'] = $userid;
        $insert['gc_id_1'] = $gc_id_1;
        $insert['gc_id_2'] = $gc_id_2;
        $insert['gc_id_3'] = $gc_id_3;
        $insert['time'] = time();
        $insert['intro'] = $intro;
        $insert['num'] = $num;
        if($_FILES){
            if(isset($_FILES['image1'])){

                $date = date('Y-m-d');
                $time = time();
                $rand = rand(10000,99999);
                $img_name = $rand . $time . '.jpg';
                $savepath = 'uploads/image/' . $date . '/';
                if (!file_exists($savepath)) {
                    mkdir($savepath, 0777, true);
                }
                $road = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name;
                move_uploaded_file($_FILES["image1"]["tmp_name"],$savepath.$img_name);
                $insert['image1'] = $road;
            }
            if(isset($_FILES['image2'])){

                $date = date('Y-m-d');
                $time = time();
                $rand = rand(10000,99999);
                $img_name = $rand . $time . '.jpg';
                $savepath = 'uploads/image/' . $date . '/';
                if (!file_exists($savepath)) {
                    mkdir($savepath, 0777, true);
                }
                $road = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name;
                move_uploaded_file($_FILES["image2"]["tmp_name"],$savepath.$img_name);
                $insert['image2'] = $road;
            }
            if(isset($_FILES['image3'])){

                $date = date('Y-m-d');
                $time = time();
                $rand = rand(10000,99999);
                $img_name = $rand . $time . '.jpg';
                $savepath = 'uploads/image/' . $date . '/';
                if (!file_exists($savepath)) {
                    mkdir($savepath, 0777, true);
                }
                $road = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name;
                move_uploaded_file($_FILES["image3"]["tmp_name"],$savepath.$img_name);
                $insert['image3'] = $road;
            }
            if(isset($_FILES['image4'])){

                $date = date('Y-m-d');
                $time = time();
                $rand = rand(10000,99999);
                $img_name = $rand . $time . '.jpg';
                $savepath = 'uploads/image/' . $date . '/';
                if (!file_exists($savepath)) {
                    mkdir($savepath, 0777, true);
                }
                $road = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name;
                move_uploaded_file($_FILES["image4"]["tmp_name"],$savepath.$img_name);
                $insert['image4'] = $road;
            }
            if(isset($_FILES['image5'])){

                $date = date('Y-m-d');
                $time = time();
                $rand = rand(10000,99999);
                $img_name = $rand . $time . '.jpg';
                $savepath = 'uploads/image/' . $date . '/';
                if (!file_exists($savepath)) {
                    mkdir($savepath, 0777, true);
                }
                $road = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name;
                move_uploaded_file($_FILES["image5"]["tmp_name"],$savepath.$img_name);
                $insert['image5'] = $road;
            }
            if(isset($_FILES['image6'])){

                $date = date('Y-m-d');
                $time = time();
                $rand = rand(10000,99999);
                $img_name = $rand . $time . '.jpg';
                $savepath = 'uploads/image/' . $date . '/';
                if (!file_exists($savepath)) {
                    mkdir($savepath, 0777, true);
                }
                $road = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name;
                move_uploaded_file($_FILES["image6"]["tmp_name"],$savepath.$img_name);
                $insert['image6'] = $road;
            }
            if(isset($_FILES['image7'])){

                $date = date('Y-m-d');
                $time = time();
                $rand = rand(10000,99999);
                $img_name = $rand . $time . '.jpg';
                $savepath = 'uploads/image/' . $date . '/';
                if (!file_exists($savepath)) {
                    mkdir($savepath, 0777, true);
                }
                $road = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name;
                move_uploaded_file($_FILES["image7"]["tmp_name"],$savepath.$img_name);
                $insert['image7'] = $road;
            }
            if(isset($_FILES['image8'])){

                $date = date('Y-m-d');
                $time = time();
                $rand = rand(10000,99999);
                $img_name = $rand . $time . '.jpg';
                $savepath = 'uploads/image/' . $date . '/';
                if (!file_exists($savepath)) {
                    mkdir($savepath, 0777, true);
                }
                $road = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name;
                move_uploaded_file($_FILES["image8"]["tmp_name"],$savepath.$img_name);
                $insert['image8'] = $road;
            }
            if(isset($_FILES['image9'])){

                $date = date('Y-m-d');
                $time = time();
                $rand = rand(10000,99999);
                $img_name = $rand . $time . '.jpg';
                $savepath = 'uploads/image/' . $date . '/';
                if (!file_exists($savepath)) {
                    mkdir($savepath, 0777, true);
                }
                $road = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name;
                move_uploaded_file($_FILES["image9"]["tmp_name"],$savepath.$img_name);
                $insert['image9'] = $road;
            }

        }
        $id = db('supply')->insertGetId($insert);
        $where['supply_type'] = array("like","%".$gc_id_3."%");
        $to_member_arr = db('member')->where($where)->column('member_id');
        $userinfo = db('member')->where('member_id',$userid)->find();
        if($userinfo['supply_auth']==0){
            $auth_info = db('member_auth')->where('userid',$userid)->where('state',1)->find();
            $area =  db('area')->where('area_id',$auth_info['area_id'])->value('area_name');
            $city =  db('area')->where('area_id',$auth_info['city_id'])->value('area_name');
        }else{
            $supply_info = db('membersupply')->where('userid',$userid)->where('state',1)->find();
            $area =  db('area')->where('area_id',$supply_info['area_id'])->value('area_name');
            $city =  db('area')->where('area_id',$supply_info['city_id'])->value('area_name');
        }
        $token_arr=[];
        foreach($to_member_arr as $vv){
            $message=[];
            $message['to_member_id'] = $vv;
            $message['message_title'] = $userinfo['member_name']."(".$area.$city.")";
            $message['message_body'] = "发布一条网片询价";
            $message['goods_id'] = $id;
            $message['message_time'] = time();
            db('message')->insert($message);
            $member_token = db('member')->where('member_id',$vv)->value('token');
            $token_arr[] = $member_token;
        }
        $push_model = model('push');
        $push_model->pushone("发布一条网片询价,请查看",$token_arr);

        if($id){
            $output = array('data' => json_decode('{}'), 'msg' => '发布成功', 'code' => '200');
            exit(json_encode($output));
        }else{
            $output = array('data' => json_decode('{}'), 'msg' => '发布失败，请重新发布', 'code' => '400');
            exit(json_encode($output));
        }
    }

    public  function askprice_del($id){
        $data = json_decode('{}');
        $supply_info = db('supply')->where('id',$id)->find();
        db('message')->where('goods_id',$id)->delete();
        db('supply')->where('id',$id)->update(['state'=>1]);
        $output = array('data' => $data, 'msg' => '操作成功', 'code' => '200');
        exit(json_encode($output));
    }


    //////询价列表
    public  function askprice_list($token){
        $data = json_decode('{}');
        $time = time();
        $not_read=0;
        $userId = $this->getuserid($token);
        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }

        $where['to_member_id'] =$userId;
        $where['message_type'] = array('neq',4);
        $res = db('message')->where($where)->select();
        $list = [];
        if(count($res)!=0){
            foreach($res as $v){
                $arr = json_decode('{}');

                $arr->id = $v['message_id'];
                if($v['read_member_id']==$userId){
                    $arr->is_read = 1;

                }else{
                    $not_read +=1;
                    $arr->is_read = 0;
                }
                $arr->askprice_id = $v['goods_id'];

                $title = substr($v['message_title'],0,-1);
                $address = explode("(",$title);
                $arr->title  =$address[0];
                $arr->address  =$address[1];
                $arr->body  =$v['message_body'];
                if(($time-$v['message_time'])/86400<1){
                    $arr->time = date(" H:i",$v['message_time']);
                }else{
                    $arr->time = date("Y-m-d H:i:s",$v['message_time']);
                }

                $list[] = $arr;
            }
            $data->read_num = (string)$not_read;
            $data->list = $list;
            $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
            exit(json_encode($output));
        }else{
            $data->read_num = "0";
            $data->list = [];
            $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
            exit(json_encode($output));
        }

    }

    ////询价已读
    public  function askprice_read($id,$token){
        $data = json_decode('{}');
        if($token !=''){
            $userId = $this->getuserid($token);
            if($userId==false){
                $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
                exit(json_encode($output));
            }
        }
        $where['message_id'] = $id;
        db('message')->where($where)->update(['read_member_id'=>$userId]);
        return true;
    }


    //////发布商品图片路径获取
    public function image_upload($filename) {
        // 判断图片数量是否超限
        $album_model = model('album');
        $class_info = $album_model->getOne(array('aclass_isdefault' => 1), 'albumclass');

        /**
         * 上传图片
         */
        //上传文件保存路径
        $upload_path = ATTACH_GOODS;
        $save_name = date('YmdHis') . rand(10000, 99999);
        $file_name = $filename;

        $result = $this->upload_albumpic($upload_path, $file_name, $save_name);

        if ($result['code'] == '200') {
            $img_path = $result['result'];
            list($width, $height, $type, $attr) = getimagesize($img_path);
            $img_path = substr(strrchr($img_path, "/"), 1);
        } else {
            //未上传图片或出错不做后面处理
            exit;
        }

        // 存入相册
        $insert_array = array();
        $insert_array['apic_name'] = $img_path;
        $insert_array['apic_tag'] = '';
        $insert_array['aclass_id'] = $class_info['aclass_id'];
        $insert_array['apic_cover'] = $img_path;
        $insert_array['apic_size'] = intval($_FILES[$file_name]['size']);
        $insert_array['apic_spec'] = $width . 'x' . $height;
        $insert_array['apic_uploadtime'] = time();
        model('album')->addAlbumpic($insert_array);


        $data = array();
        $data ['thumb_name'] = goods_cthumb($img_path, 240);
        $data ['name'] = $img_path;

        // 整理为json格式
        $output = $data;
        return $output;
    }

    //////商品保存图片
    function upload_albumpic($upload_path, $file_name = 'file', $save_name)
    {
        //判断是否上传图片
        if (!empty($_FILES[$file_name]['name'])) {


            //本地图片保存
            $file_object = request()->file($file_name);
            $upload_path = BASE_UPLOAD_PATH . DS . $upload_path;
            $info = $file_object->rule('uniqid')->validate(['ext' => ALLOW_IMG_EXT])->move($upload_path, $save_name);
            if ($info) {
                $img_path = $upload_path . '/' . $info->getFilename();
                create_albumpic_thumb($upload_path,$info->getFilename());
                return array('code' => '200', 'message' => '', 'result' => $img_path);
            }
            else {
                $error = $file_object->getError();
                $data['code'] = '100';
                $data['message'] = $error;
                $data['result'] = $_FILES[$file_name]['name'];
                return $data;
            }
        }


    }



    ///发布商品
    public function  publish_goods($goods_id='',$type='',$token='')
    {

        $data = json_decode("{}");
        $goodsclass_model = model('goodsclass');
        $token = input('post.token');
        $userid = $this->getuserid($token);
        if($userid==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }
        $userinfo = db('member')->where('member_id',$userid)->find();
        if($userinfo['membervip_endtime']<time()){
            $output = array('data' => json_decode('{}'), 'msg' => '您不是供应商或供应商已过期', 'code' => '501');
            exit(json_encode($output));
        }
        if($type==''){
            $output = array('data' => $data, 'msg' => 'type不能为空', 'code' => '400');
            exit(json_encode($output));
        }
        if (isset($_POST['gc_id_1'])) {
            $gc_id = input('post.gc_id_1');
        }
        if (isset($_POST['gc_id_2'])) {
            $gc_id = input('post.gc_id_2');
        }
        if (isset($_POST['gc_id_3'])) {
            $gc_id = input('post.gc_id_3');
        }
        $goods_class = $goodsclass_model->getGoodsclassLineForTag($gc_id);
        // 分类信息
        $goods_model = model('goods');
        $update_common = array();
        $update_common['goods_name'] = input('post.g_name');
        $update_common['user_id'] = $userid;
        $update_common['gc_id'] = $gc_id;
        $update_common['goods_state'] = 1;
        $update_common['gc_id_1'] = isset($goods_class['gc_id_1']) ? intval($goods_class['gc_id_1']) : 0;
        $update_common['gc_id_2'] = isset($goods_class['gc_id_2']) ? intval($goods_class['gc_id_2']) : 0;
        $update_common['gc_id_3'] = isset($goods_class['gc_id_3']) ? intval($goods_class['gc_id_3']) : 0;
        $update_common['gc_name'] = $goods_class['gctag_name'];
        $update_common['goods_body'] = input('post.body');
        $temp=[];
        if (isset($_FILES['image1'])) {
            $arr1 = $this->image_upload('image1');
            $image1_name = $arr1['name'];
            $update_common['goods_image'] = $arr1['name'];
            $temp[] = $arr1['name'];
            $update_common['goods_image'] = $arr1['name'];
        }
        if (isset($_FILES['image2'])){
            $arr2 = $this->image_upload('image2');
            $image2_name = $arr2['name'];
            $temp[] = $arr2['name'];
        }
        if (isset($_FILES['image3'])) {
            $arr3 = $this->image_upload('image3');
            $image3_name = $arr3['name'];
            $temp[] = $arr3['name'];
        }
        if (isset($_FILES['image4'])) {
            $arr4 = $this->image_upload('image4');
            $image4_name = $arr4['name'];
            $temp[] = $arr4['name'];
        }
        if (isset($_FILES['image5'])) {
            $arr5 = $this->image_upload('image5');
            $image5_name = $arr5['name'];
            $temp[] = $arr5['name'];
        }
        if (isset($_FILES['image6'])) {
            $arr6 = $this->image_upload('image6');
            $image6_name = $arr6['name'];
            $temp[] = $arr6['name'];
        }
        if (isset($_FILES['image7'])) {
            $arr7 = $this->image_upload('image7');
            $image7_name = $arr7['name'];
            $temp[] = $arr7['name'];
        }
        if (isset($_FILES['image8'])) {
            $arr8 = $this->image_upload('image8');
            $image8_name = $arr8['name'];
            $temp[] = $arr8['name'];
        }
        if (isset($_FILES['image9'])) {
            $arr9 = $this->image_upload('image9');
            $image9_name = $arr9['name'];
            $temp[] = $arr9['name'];
        }
        if (isset($_FILES['image10'])) {
            $arr10 = $this->image_upload('image10');
            $image10_name = $arr10['name'];
            $temp[] = $arr10['name'];
        }
        $update_common['goods_price'] = floatval(input('post.price'));
        $update_common['goods_marketprice'] = floatval(input('post.price'));
        $update_common['goods_storage'] = input('post.storage');
        if($goods_id){
            $common_id = db('goodscommon')->where('goods_commonid',$goods_id)->update($update_common);

        }else{
            $common_id = db('goodscommon')->insertGetId($update_common);
        }

        foreach($temp as $k=>$v){
            $insert_image['goods_commonid'] = $common_id;
            $insert_image['goodsimage_url'] = $v;
            $insert_image['goodsimage_sort'] = 0;
            $insert_image['color_id'] = 0;
            if($k==0){
                $insert_image['goodsimage_isdefault'] = 1;
            }else{
                $insert_image['goodsimage_isdefault'] = 0;
            }
            db('goodsimages')->insert($insert_image);

        }

        // 开始事务
        model('goods')->startTrans();

        try{

            $goods_info = $goods_model->getGoodsInfo(array('goods_spec' => serialize(null), 'goods_commonid' => $common_id), 'goods_id');

            $insert = array();
            $insert['goods_commonid'] = $common_id;
            $insert['goods_type'] = $type;
            $insert['goods_name'] = $update_common['goods_name'];
            $insert['user_id'] = $update_common['user_id'];
            $insert['gc_id'] = $update_common['gc_id'];
            $insert['gc_id_1'] = $update_common['gc_id_1'];
            $insert['gc_id_2'] = $update_common['gc_id_2'];
            $insert['gc_id_3'] = $update_common['gc_id_3'];
            $insert['goods_price'] = $update_common['goods_price'];
            $insert['price_unit'] = input('post.price_unit');
            $insert['goods_promotion_price'] = $update_common['goods_price'];
            $insert['goods_marketprice'] = $update_common['goods_marketprice'];
            $insert['area_id'] = intval(input('post.area_id'));
            $insert['city_id'] = intval(input('post.city_id'));
            $insert['district_id'] = intval(input('post.district_id'));
            $insert['goods_storage'] = intval(input('post.goods_storage'));
            $insert['goods_image'] = $update_common['goods_image'];
            $insert['goods_state'] = 1;
            $insert['goods_addtime'] = TIMESTAMP;
            $insert['goods_edittime'] = TIMESTAMP;
            $insert['color_id'] = 0;
            if($goods_id){
                db('goods')->where('goods_id',$goods_id)->update($insert);

            }else{
                $goods_id = $goods_model->addGoods($insert);
                $goodsid_array[] = intval($goods_id);
            }



            // 添加操作日志



//            $return = $goods_model->editGoodsCommon($update_common, array('goods_commonid' => $common_id));

        } catch (\Exception $e){
            $goods_model->rollback();
            $output = array('data' => json_decode('{}'), 'msg' =>$e->getMessage(), 'code' => '400');
            exit(json_encode($output));
        }
        //提交事务
        model('goods')->commit();
        $output = array('data' => json_decode('{}'), 'msg' => '发布成功', 'code' => '200');
        exit(json_encode($output));

    }


    /////发布设备
    /////type  1 供应  2 求购
    public function publish_device($type='',$token='',$title='',$price='',$date='',$is_new='',$detail='',$image='',$company='',$class='',$area='',$city='',$district='',
                                   $material='',$name='',$mobile='')
    {
        $data = json_decode('{}');

        $userid = $this->getuserid($token);

        if($userid==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }
        $insert['type'] = $type;
        $insert['title'] = $title;
        $insert['price'] = $price;
        $insert['userid'] = $userid;
        $insert['date'] = $date;
        $insert['is_new'] = $is_new;
        $insert['detail'] = $detail;
        $insert['company'] = $company;
        $insert['time'] = time();
        $insert['class'] = $class;
        $insert['area'] = $area;
        $insert['city'] = $city;
        $insert['district'] = $district;
        $insert['material'] = $material;
        $insert['name'] = $name;
        $insert['mobile'] = $mobile;

        if($_FILES){
            if($_FILES['image1']){

                $date = date('Y-m-d');
                $time = time();
                $rand = rand(10000,99999);
                $img_name = $rand . $time . '.jpg';
                $savepath = 'uploads/image/' . $date . '/';
                if (!file_exists($savepath)) {
                    mkdir($savepath, 0755, true);
                }
                $road = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name;
                move_uploaded_file($_FILES["image1"]["tmp_name"],$savepath.$img_name);
                $insert['image1'] = $road;
            }
            if(isset($_FILES['image2'])){

                $date = date('Y-m-d');
                $time = time();
                $rand = rand(10000,99999);
                $img_name = $rand . $time . '.jpg';
                $savepath = 'uploads/image/' . $date . '/';
                if (!file_exists($savepath)) {
                    mkdir($savepath, 0755, true);
                }
                $road = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name;
                move_uploaded_file($_FILES["image2"]["tmp_name"],$savepath.$img_name);
                $insert['image2'] = $road;
            }
            if(isset($_FILES['image3'])){

                $date = date('Y-m-d');
                $time = time();
                $rand = rand(10000,99999);
                $img_name = $rand . $time . '.jpg';
                $savepath = 'uploads/image/' . $date . '/';
                if (!file_exists($savepath)) {
                    mkdir($savepath, 0755, true);
                }
                $road = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name;
                move_uploaded_file($_FILES["image3"]["tmp_name"],$savepath.$img_name);
                $insert['image3'] = $road;
            }

        }
        $id = db('device')->insertGetId($insert);
        if($id){
            $output = array('data' => json_decode('{}'), 'msg' => '发布成功，请等待审核', 'code' => '200');
            exit(json_encode($output));
        }else{
            $output = array('data' => json_decode('{}'), 'msg' => '发布失败，请重新发布', 'code' => '400');
            exit(json_encode($output));
        }
    }




    ///发布圈子
    public function publish_circle($content='',$token='',$area_id='',$city_id='',$district_id='',$image='')
    {
        $data = json_decode('{}');

        $userid = $this->getuserid($token);

        if($userid==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }

        $insert['userid'] = $userid;
        $insert['circle_content'] = $content;
        $insert['article_time'] = time();
        $insert['area_id'] = $area_id;
        $insert['city_id'] = $city_id;
        $insert['district_id'] = $district_id;
        $image_str = '';
        if($_FILES){
            if(isset($_FILES['image1'])){

                $date = date('Y-m-d');
                $time = time();
                $rand = rand(10000,99999);
                $img_name = $rand . $time . '.jpg';
                $savepath = 'uploads/image/' . $date . '/';
                if (!file_exists($savepath)) {
                    mkdir($savepath, 0777, true);
                }
                $road = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name;
                move_uploaded_file($_FILES["image1"]["tmp_name"],$savepath.$img_name);
                $image_str .= $road.",";
            }
            if(isset($_FILES['image2'])){

                $date = date('Y-m-d');
                $time = time();
                $rand = rand(10000,99999);
                $img_name = $rand . $time . '.jpg';
                $savepath = 'uploads/image/' . $date . '/';
                if (!file_exists($savepath)) {
                    mkdir($savepath, 0777, true);
                }
                $road = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name;
                move_uploaded_file($_FILES["image2"]["tmp_name"],$savepath.$img_name);
                $image_str .= $road.",";
            }
            if(isset($_FILES['image3'])){

                $date = date('Y-m-d');
                $time = time();
                $rand = rand(10000,99999);
                $img_name = $rand . $time . '.jpg';
                $savepath = 'uploads/image/' . $date . '/';
                if (!file_exists($savepath)) {
                    mkdir($savepath, 0777, true);
                }
                $road = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name;
                move_uploaded_file($_FILES["image3"]["tmp_name"],$savepath.$img_name);
                $image_str .= $road.",";
            }
            if(isset($_FILES['image4'])){

                $date = date('Y-m-d');
                $time = time();
                $rand = rand(10000,99999);
                $img_name = $rand . $time . '.jpg';
                $savepath = 'uploads/image/' . $date . '/';
                if (!file_exists($savepath)) {
                    mkdir($savepath, 0777, true);
                }
                $road = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name;
                move_uploaded_file($_FILES["image4"]["tmp_name"],$savepath.$img_name);
                $image_str .= $road.",";
            }
            $insert['image'] = $image_str;
        }

        $id = db('circle')->insertGetId($insert);
        if($id){
            $output = array('data' => json_decode('{}'), 'msg' => '发布成功，请等待审核', 'code' => '200');
            exit(json_encode($output));
        }else{
            $output = array('data' => json_decode('{}'), 'msg' => '发布失败，请重新发布', 'code' => '400');
            exit(json_encode($output));
        }
    }

    ///发布报价
    public function publish_offer($title='',$content='',$token='',$class_id='',$image='')
    {
        $data = json_decode('{}');

        $userid = $this->getuserid($token);

        if($userid==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }

        $insert['userid'] = $userid;
        $insert['type'] = 2;
        $insert['offer_content'] = $content;
        $insert['class_id'] = $class_id;
        $insert['offer_title'] = $title;
        $insert['article_time'] = time();
        $image_str = '';
        if($_FILES){
            if(isset($_FILES['image1'])){

                $date = date('Y-m-d');
                $time = time();
                $rand = rand(10000,99999);
                $img_name = $rand . $time . '.jpg';
                $savepath = 'uploads/image/' . $date . '/';
                if (!file_exists($savepath)) {
                    mkdir($savepath, 0777, true);
                }
                $road = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name;
                move_uploaded_file($_FILES["image1"]["tmp_name"],$savepath.$img_name);
                $insert['image1'] = $road;
            }
            if(isset($_FILES['image2'])){

                $date = date('Y-m-d');
                $time = time();
                $rand = rand(10000,99999);
                $img_name = $rand . $time . '.jpg';
                $savepath = 'uploads/image/' . $date . '/';
                if (!file_exists($savepath)) {
                    mkdir($savepath, 0777, true);
                }
                $road = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name;
                move_uploaded_file($_FILES["image2"]["tmp_name"],$savepath.$img_name);
                $insert['image2'] = $road;
            }
            if(isset($_FILES['image3'])){

                $date = date('Y-m-d');
                $time = time();
                $rand = rand(10000,99999);
                $img_name = $rand . $time . '.jpg';
                $savepath = 'uploads/image/' . $date . '/';
                if (!file_exists($savepath)) {
                    mkdir($savepath, 0777, true);
                }
                $road = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name;
                move_uploaded_file($_FILES["image3"]["tmp_name"],$savepath.$img_name);
                $insert['image3'] = $road;
            }


        }

        $id = db('offer')->insertGetId($insert);
        if($id){
            $output = array('data' => json_decode('{}'), 'msg' => '发布成功，请等待审核', 'code' => '200');
            exit(json_encode($output));
        }else{
            $output = array('data' => json_decode('{}'), 'msg' => '发布失败，请重新发布', 'code' => '400');
            exit(json_encode($output));
        }
    }


    ///发布成交分享
    public function publish_share($content='',$token='',$image='')
    {
        $data = json_decode('{}');

        $userid = $this->getuserid($token);

        if($userid==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }

        $insert['userid'] = $userid;
        $insert['article_time'] = time();
        $insert['share_content'] = $content;

        $image_str = '';
        if($_FILES){
            if(isset($_FILES['image1'])){

                $date = date('Y-m-d');
                $time = time();
                $rand = rand(10000,99999);
                $img_name = $rand . $time . '.jpg';
                $savepath = 'uploads/image/' . $date . '/';
                if (!file_exists($savepath)) {
                    mkdir($savepath, 0777, true);
                }
                $road = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name;
                move_uploaded_file($_FILES["image1"]["tmp_name"],$savepath.$img_name);
                $insert['image1'] = $road;
            }
            if(isset($_FILES['image2'])){

                $date = date('Y-m-d');
                $time = time();
                $rand = rand(10000,99999);
                $img_name = $rand . $time . '.jpg';
                $savepath = 'uploads/image/' . $date . '/';
                if (!file_exists($savepath)) {
                    mkdir($savepath, 0777, true);
                }
                $road = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name;
                move_uploaded_file($_FILES["image2"]["tmp_name"],$savepath.$img_name);
                $insert['image2'] = $road;
            }
            if(isset($_FILES['image3'])){

                $date = date('Y-m-d');
                $time = time();
                $rand = rand(10000,99999);
                $img_name = $rand . $time . '.jpg';
                $savepath = 'uploads/image/' . $date . '/';
                if (!file_exists($savepath)) {
                    mkdir($savepath, 0777, true);
                }
                $road = 'http://'.$_SERVER['HTTP_HOST'].'/' . $savepath . $img_name;
                move_uploaded_file($_FILES["image3"]["tmp_name"],$savepath.$img_name);
                $insert['image3'] = $road;
            }


        }

        $id = db('share')->insertGetId($insert);
        if($id){
            $output = array('data' => json_decode('{}'), 'msg' => '发布成功，请等待审核', 'code' => '200');
            exit(json_encode($output));
        }else{
            $output = array('data' => json_decode('{}'), 'msg' => '发布失败，请重新发布', 'code' => '400');
            exit(json_encode($output));
        }
    }

///////////////////////////////////////////////////////发布完毕///////////////////////////////////////////////////////



//////////////////////////列表开始////////////////////////////

//////供应商列表
    public  function supply_list($page='',$token='',$keyword='',$class_id=''){
        $data = json_decode('{}');
        $time = time();
        if($token !=''){
            $userId = $this->getuserid($token);
            if($userId==false){
                $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
                exit(json_encode($output));
            }
        }
        $where['state'] = 1;
        if($keyword !=''){
            $where['company_name'] =array('like',"%".$keyword."%");
        }
        $where['supply_type'] = array('like',"%".$class_id."%");
        $where['membervip_endtime'] = array('gt',$time);
        $page-=1;
        $res = db('view_membersupply')->where($where)->order('is_first DESC')->limit($page*20,20)->select();
        $count =  db('view_membersupply')->where($where)->count();
        $totalpage = ceil($count/20);
        $data->totalpage = $totalpage;
        $image = db('adv')->where('adv_id',9)->find();
        $data->bannar = 'http://'.$_SERVER['HTTP_HOST']."/uploads/home/adv/".$image['adv_code'];
        $data->supplyid = $image['adv_goodsid'];
        $list = [];
        foreach($res as $v){
            $arr = json_decode('{}');

            $arr->id = $v['id'];
            $arr->isfirst =$v['is_first'];
            $arr->image[] = $v['image6'];
            $arr->image[] = $v['image7'];
            $arr->image[] = $v['image8'];
            $arr->image[] = $v['image9'];
            $arr->image = array_filter($arr->image);
            $arr->name  =$v['company_name'];
            $userinfo = db('member')->where('member_id',$v['userid'])->find();
            $arr->is_shidi = $userinfo['is_shidi'];
            $arr->manage_type = $v['manage_type'];
            $arr->main = $v['main'];
            $list[] = $arr;
        }
        $data->list = $list;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));

    }


    public  function  getmobile($userid){
        $userinfo= db('member')->where('member_id',$userid)->find();
        $data = json_decode('{}');
        $data->mobile = $userinfo['member_mobile'];
        if($userinfo['member_auth']==1){
            $where=[];
            $where['type']= 1;
            $where['state']= 1;
            $where['userid'] = $userid;
            $data->mobile = db('member_auth')->where($where)->value('mobile');
        }
        if($userinfo['shop_auth']==1){
            $where=[];
            $where['type']= 2;
            $where['state']= 1;
            $where['userid'] = $userid;
            $data->mobile = db('member_auth')->where($where)->value('mobile');
        }
        if($userinfo['company_auth']==1){
            $where=[];
            $where['type']= 3;
            $where['state']= 1;
            $where['userid'] = $userid;
            $data->mobile = db('member_auth')->where($where)->value('mobile');
        }
        if($userinfo['supply_auth']==1){
            $where=[];
            $where['state']= 1;
            $where['userid'] = $userid;
            $data->mobile = db('membersupply')->where($where)->value('mobile1');
        }
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }


    ///////////////////供应商详情
    public function  supply_detail($supply_id,$token=''){
        $data = json_decode('{}');
        $userId = $this->getuserid($token);

        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }

        $supply_info = db('membersupply')->where('id',$supply_id)->find();
        $member_info =  db('member')->where('member_id',$supply_info['userid'])->find();
        $data->id = $supply_info['id'];
        $data->name = $supply_info['company_name'];
        $data->manage_type = $supply_info['manage_type'];
        $data->main = $supply_info['main'];
        $data->intro = $supply_info['intro'];
        $data->mobile1 = $supply_info['mobile1'];
        $data->mobile2 = $supply_info['mobile2'];
        $data->mobile3 = $supply_info['mobile3'];
        $data->rong_token = $member_info['rong_token'];
        $data->is_shidi = $member_info['is_shidi'];
        $data->legal = $supply_info['legal'];
        $data->time = $supply_info['time'];
        $data->money = $supply_info['money'];
        $data->userid = $supply_info['userid'];
        $data->member_name = $member_info['member_name'];
        $area_name = db('area')->where('area_id',$supply_info['area_id'])->value('area_name');
        $city_name = db('area')->where('area_id',$supply_info['city_id'])->value('area_name');
        $district_name = db('area')->where('area_id',$supply_info['district_id'])->value('area_name');
        $address_name = $area_name.$city_name.$district_name.$supply_info['address'];
        $data->member_address = $address_name;

        $data->image[] = $supply_info['image6'];
        $data->image[] = $supply_info['image7'];
        $data->image[] = $supply_info['image8'];
        $data->image[] = $supply_info['image9'];
        $data->image[] = $supply_info['image10'];
        $data->image[] = $supply_info['image11'];
        $data->image[] = $supply_info['image12'];
        $data->image[] = $supply_info['image13'];
        $data->image[] = $supply_info['image14'];
        $data->image = array_filter($data->image);

        $data->image2[] = $supply_info['image2'];
        $data->image2[] = $supply_info['image3'];
        $data->image2[] = $supply_info['image4'];
        $data->image2[] = $supply_info['image5'];

        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }
    //////询价详情
    public function  askprice_detail($id,$token=''){
        $data = json_decode('{}');
        $userId = $this->getuserid($token);

        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }
        $message_id = db('message')->where('to_member_id',$userId)->where('goods_id',$id)->value('message_id');
        $this->askprice_read($message_id,$token);

        $supply_info = db('supply')->where('id',$id)->find();
        $member_info =  db('member')->where('member_id',$supply_info['userid'])->find();
        $data->id = $supply_info['id'];
        $data->name = $supply_info['name'];
        $data->manage_type = $supply_info['type'];
        $data->userid = $supply_info['userid'];
        $data->num = $supply_info['num'];
        $data->class_name = db('goodsclass')->where('gc_id',$supply_info['gc_id_3'])->value('gc_name');
        $data->intro = $supply_info['intro'];
        $auth_info = db('member_auth')->where('userid',$userId)->where('state',1)->find();
        ////买家类型
        $data->user_type = $auth_info['type'];
        if( $auth_info['type']==1){
            $data->user_name = $auth_info['name'];
        }
        if( $auth_info['type']==2){
            $data->user_name = $auth_info['shop_name'];
        }
        if( $auth_info['type']==3){
            $data->user_name = $auth_info['company_name'];
        }

        $area_name = db('area')->where('area_id',$auth_info['area_id'])->value('area_name');
        $city_name = db('area')->where('area_id',$auth_info['city_id'])->value('area_name');
        $district_name = db('area')->where('area_id',$auth_info['district_id'])->value('area_name');
        $address_name = $area_name.$city_name.$district_name;
        $data->member_address = $address_name;
        if(empty($auth_info)){
            $auth_info = db('membersupply')->where('userid',$userId)->where('state',1)->find();
            $data->user_type = 4;
            $data->user_name = $auth_info['name'];
            $area_name = db('area')->where('area_id',$auth_info['area_id'])->value('area_name');
            $city_name = db('area')->where('area_id',$auth_info['city_id'])->value('area_name');
            $district_name = db('area')->where('area_id',$auth_info['district_id'])->value('area_name');
            $address_name = $area_name.$city_name.$district_name;
            $data->member_address = $address_name.$auth_info['address'];
        }

        $data->rong_token = $member_info['rong_token'];
        $data->member_name = $member_info['member_name'];


        $data->image[] = $supply_info['image1'];
        $data->image[] = $supply_info['image2'];
        $data->image[] = $supply_info['image3'];
        $data->image[] = $supply_info['image4'];
        $data->image[] = $supply_info['image5'];
        $data->image[] = $supply_info['image6'];
        $data->image[] = $supply_info['image7'];
        $data->image[] = $supply_info['image8'];
        $data->image[] = $supply_info['image9'];
        $data->image = array_filter($data->image);



        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }




    //////求购列表
    public  function shop_list($page='',$token='',$gc_id_1='',$gc_id_2='',$area_id='',$city_id='',$keyword='',$new=''){
        $data = json_decode('{}');
        if($token !=''){
            $userId = $this->getuserid($token);
            if($userId==false){
                $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
                exit(json_encode($output));
            }
        }

        $order='';
        if($new == 1){
            $order='id DESC';
        }
        $where['type'] = 2;
        if($gc_id_1 !='' && $gc_id_2 !=''){
            $where['gc_id_2'] = $gc_id_2;
        }
        if($area_id !='' && $city_id !=''){
            $where['area_id'] = $area_id;
            $where['city_id'] = $city_id;
        }
        if($keyword !=''){
            $where['title'] =array('like',"%".$keyword."%");
        }
        $page-=1;
        $res = db('supply')->where($where)->order('id desc')->limit($page*20,20)->order($order)->select();
        $count =  db('supply')->where($where)->count();
        $totalpage = ceil($count/20);
        $data->totalpage = $totalpage;
        $image = db('adv')->where('adv_id',10)->value('adv_code');
        $data->bannar = 'http://'.$_SERVER['HTTP_HOST']."/uploads/home/adv/".$image;
        $list = [];
        foreach($res as $v){
            $arr = json_decode('{}');
            $arr->id  =$v['id'];
            $arr->name  =$v['title'];
            $area = db('area')->where('area_id',$v['area_id'])->value('area_name');
            $city = db('area')->where('area_id',$v['city_id'])->value('area_name');
            $arr->area  =$area.$city;
            $arr->buycount = $v['supply_num'].$v['num_unit'];
            $arr->buyprice = $v['supply_price'];
            $arr->buynum = $v['price_unit'];
            $list[] = $arr;
        }
        $data->list =$list;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));

    }



    ///////////////////求购详情
    public function  shop_detail($shop_id,$token=''){
        $data = json_decode('{}');
        $userId = $this->getuserid($token);

        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }
        $supply_info = db('supply')->where('id',$shop_id)->find();
        $data->supply_id = $supply_info['id'];
        $str = strip_tags(htmlspecialchars_decode($supply_info['material2_id']));
        $data->str = $str;
        $str2 = str_replace(PHP_EOL, '', $str);
        $temp = json_decode(($str2));
        $data->str2 = $temp;
        $data->title = $supply_info['title'];
        $data->keyword = $supply_info['keyword'];
        $data->gc_id_1 = db('goodsclass')->where('gc_id',$supply_info['gc_id_1'])->value('gc_name') ;
        $data->gc_id_2 = db('goodsclass')->where('gc_id',$supply_info['gc_id_2'])->value('gc_name') ;
        $data->gc_id_3 = db('goodsclass')->where('gc_id',$supply_info['gc_id_3'])->value('gc_name') ;
        $is_collect = db('supplycollect')->where(['user_id'=>$userId,'supply_id'=>$shop_id])->find();
        if($is_collect){
            $data->is_collect = 1;
        }else{
            $data->is_collect = 0;
        }
        $area = db('area')->where('area_id',$supply_info['area_id'])->value('area_name');
        $city = db('area')->where('area_id',$supply_info['city_id'])->value('area_name');
        $data->area  =$area.$city;
        $data->price = $supply_info['supply_price'];
        $data->is_shui = $supply_info['is_shui'];
        $data->price_unit = $supply_info['price_unit'];
        $data->num = $supply_info['supply_num'];
        $data->num_unit = $supply_info['num_unit'];
        $userid2 = $supply_info['userid'];
        $member = db('member')->where('member_id',$userid2)->find() ;
        $data->username = $member['member_name'];
        $data->mobile = $member['member_mobile'];
        if($member['company_name'] !=''){
            $data->company = $member['company_name'];
        }else{
            $data->company = '';
        }
        if($member['company_address'] !=''){
            $data->company_address = $member['company_address'];
        }else{
            $data->company_address = '';
        }
        $image_arr =[];
        if( $supply_info['image1']!=''){
            $image_arr[] = $supply_info['image1'];
        }
        if( $supply_info['image2'] !=''){
            $image_arr[] = $supply_info['image2'];
        }
        if( $supply_info['image3']!=''){
            $image_arr[] = $supply_info['image3'];
        }
        $data->image_arr = $image_arr;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }


    ///////收藏供应求购 active 1:收藏  2取消点赞
    public function  supply_collect($token,$supply_id,$active=''){
        $data = json_decode('{}');
        if ($token == '' || $supply_id=='' || $active=='') {
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $userId = $this->getuserid($token);
        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }
        if($active==1){
            $where['user_id'] = $userId;
            $where['supply_id'] = $supply_id;
            $supply_info = db('supply')->where('id',$supply_id)->find();
            $insert['type'] = $supply_info['type'];
            $is_collect = db('supplycollect')->where($where)->find();
            if($is_collect){
                $output = array('data' => json_decode('{}'), 'msg' => '请勿重复收藏', 'code' => '400');
                exit(json_encode($output));
            }
            $insert['user_id'] = $userId;
            $insert['supply_id'] = $supply_id;
            $insert['time'] = time();
            $id =  db('supplycollect')->insert($insert);
            if(isset($id)){
                $output = array('data' => json_decode('{}'), 'msg' => '收藏成功', 'code' => '200');
                exit(json_encode($output));
            }else{
                $output = array('data' => json_decode('{}'), 'msg' => '收藏失败', 'code' => '400');
                exit(json_encode($output));
            }
        }else{

            $where['user_id'] = $userId;
            $where['supply_id'] = $supply_id;
            $zan_info =db('supplycollect')->where($where)->find();
            if($zan_info==''){
                $output = array('data' => json_decode('{}'), 'msg' => '无法取消收藏', 'code' => '400');
                exit(json_encode($output));
            }
            db('supplycollect')->where($where)->delete();
            $output = array('data' => json_decode('{}'), 'msg' => '取消成功', 'code' => '200');
            exit(json_encode($output));
        }

    }
    //////圈子列表
    public  function circle_list($page='',$area_id='',$city_id='',$token='',$user_id='',$keyword=''){
        $data = json_decode('{}');


        $where = [];
        if($area_id !='' && $city_id !=''){
            $where['city_id'] = $city_id;
        }
        if($user_id !=''){
            $where['userid'] = $user_id;
        }
        if($keyword !=''){
            $where['circle_content'] =array('like',"%".$keyword."%");
        }
        $page-=1;
        $res = db('circle')->where($where)->order('id desc')->limit($page*20,20)->select();
        $count = db('circle')->where($where)->count();
        $totalpage = ceil($count/20);
        $data->totalpage = $totalpage;
        $bannar = db('adv')->where('ap_id',3)->value('adv_code');
        $image2 =   'http://' . $_SERVER['HTTP_HOST'] . '/uploads/' . ATTACH_ADV  .'/'. $bannar;

        $data->bannar = $image2;
        $list = [];
        foreach($res as $v){
            $arr = json_decode('{}');
            $image = $v['image'];
            $image = substr($image,0,-1);
            $image_arr = explode(",",$image);
            $arr->id = $v['id'];
            $arr->image = $image_arr;
            $arr->zan = $v['zan'];
            $arr->discuss = $v['discuss'];
            if($token !=''){
                $where2['userid'] = $this->getuserid($token);
                $where2['circle_id'] = $v['id'];
                $is_zan = db('circle_zan')->where($where2)->find();
                if($is_zan){
                    $arr->is_zan = 1;
                }else{
                    $arr->is_zan = 0;
                }
            }else{
                $arr->is_zan = 0;
            }


            $arr->content  =$v['circle_content'];
            $userid = $v['userid'];
            $name = db('member')->where('member_id',$userid)->value('member_name');
            if($name !=''){
                $arr->name = $name;
            }else{
                $arr->name = '';
            }

            $area =  db('area')->where('area_id',$v['area_id'])->value('area_name');
            $city =  db('area')->where('area_id',$v['city_id'])->value('area_name');
            $arr->address = $area.$city;
            $avatar = db('member')->where('member_id',$userid)->value('member_avatar');
            if($avatar !=''){
                $arr->avatar = $avatar;
            }else{
                $arr->avatar = '';
            }



            $arr->time  =date("Y-m-d",$v['article_time']);
            $list[] = $arr;
        }
        $data->list = $list;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));

    }


    //////成交分享列表
    public  function share_list($page='',$token=''){
        $data = json_decode('{}');


        $where = [];
        $where['state'] = 1;
        $page-=1;
        $res = db('share')->where($where)->order('share_id desc')->limit($page*20,20)->select();
        $count = db('share')->where($where)->count();
        $totalpage = ceil($count/20);
        $data->totalpage = $totalpage;
        $list = [];
        foreach($res as $v){
            $arr = json_decode('{}');
            $image_arr=[];
            if($v['image1'] !=''){
                $image_arr[] = $v['image1'];
            }
            if($v['image2'] !=''){
                $image_arr[] = $v['image2'];
            }
            if($v['image3'] !=''){
                $image_arr[] = $v['image3'];
            }
            $arr->id = $v['id'];
            $arr->image = $image_arr;
            $arr->zan = $v['zan'];

            if($token !=''){
                $where2['userid'] = $this->getuserid($token);
                $where2['share_id'] = $v['id'];
                $is_zan = db('share_zan')->where($where2)->find();
                if($is_zan){
                    $arr->is_zan = '1';
                }else{
                    $arr->is_zan = '0';
                }
            }else{
                $arr->is_zan = '0';
            }
            $arr->content  =$v['share_content'];
            $userid = $v['userid'];
            $name = db('member')->where('member_id',$userid)->value('member_name');
            if($name !=''){
                $arr->name = $name;
            }else{
                $arr->name = '';
            }
            $avatar = db('member')->where('member_id',$userid)->value('member_avatar');
            if($avatar !=''){
                $arr->avatar = $avatar;
            }else{
                $arr->avatar = '';
            }



            $arr->time  =date("Y-m-d",$v['article_time']);
            $list[] = $arr;
        }
        $data->list = $list;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));

    }


    ////////设备列表
    public  function device_list($page='',$area_id='',$city_id='',$token='',$keyword='',$class='',$type='',$is_new=''){
        $data = json_decode('{}');


        $where = [];
        if($area_id !='' && $city_id !=''){
            $where['city'] = $city_id;
        }
        if($class !=''){
            $where['class'] = $class;
        }
        if($type !=''){
            $where['type'] = $type;
        }
        if($is_new !=''){
            $where['is_new'] = $is_new;
        }
        if($keyword !=''){
            $where['title'] = array('like',"%".$keyword."%");
        }

        $page-=1;
        $res = db('device')->where($where)->order('device_id desc')->limit($page*20,20)->select();
        $count = db('device')->where($where)->count();
        $totalpage = ceil($count/20);
        $data->totalpage = $totalpage;
        $adv_code = db('adv')->where('adv_id',8)->value('adv_code');
        $data->bannar =  'http://'.$_SERVER['HTTP_HOST']."/uploads/home/adv/".$adv_code;
        $list = [];
        foreach($res as $v){
            $arr = json_decode('{}');
            $image_arr=[];
            if($v['image1'] !=''){
                $image_arr[] = $v['image1'];
            }
            if($v['image2'] !=''){
                $image_arr[] = $v['image2'];
            }
            if($v['image3'] !=''){
                $image_arr[] = $v['image3'];
            }
            $arr->id = $v['device_id'];
            $arr->image = $image_arr;
            $arr->name = $v['title'];
            $arr->type = $v['type'];
            if($v['is_new']==1){
                $arr->is_new = $v['is_new'];
            }else{
                $arr->is_new = 0;
            }

            $arr->price = $v['price'];
            $list[] = $arr;
        }
        $data->list = $list;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));

    }


    ///////////////////设备详情
    public function  device_detail($device_id,$token=''){
        $data = json_decode('{}');
        $userId = $this->getuserid($token);

        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }
        $device_info = db('device')->where('device_id',$device_id)->find();
        $data->device_id = $device_info['device_id'];


        $data->member_name =$device_info['name'];
        $data->mobile =$device_info['mobile'];
        if($device_info['is_new']==1){
            $data->is_new = '全新';
        }else{
            $data->is_new = '半成新';
        }
        $data->date = $device_info['date'];
        $data->company = $device_info['company'];
        $data->detail = $device_info['detail'];
        $data->name = $device_info['title'];
        $data->price = $device_info['price'];
        $image_arr =[];
        if( $device_info['image1']!=''){
            $image_arr[] = $device_info['image1'];
        }
        if( $device_info['image2'] !=''){
            $image_arr[] = $device_info['image2'];
        }
        if( $device_info['image3']!=''){
            $image_arr[] = $device_info['image3'];
        }
        $data->image = $image_arr;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }

    ////////报价列表
    /// type: 1官方报价  2个人报价  3资讯
    public  function offer_list($page='',$type='',$token='',$keyword='',$class=''){
        $data = json_decode('{}');


        $where = [];

        if($type !=''){
            $where['type'] = $type;
        }
        if($keyword !=''){
            $where['offer_title'] = array('like',"%".$keyword."%");
        }
        if($class !=''){
            $where['class_id'] = $class;
        }

        $page-=1;
        $res = db('offer')->where($where)->order('id desc')->limit($page*20,20)->select();
        $roll = db('offer')->order('article_time DESC')->limit(3)->field('offer_title')->select();
        $temp=[];
        foreach($roll as $v){
            $temp[] = $v['offer_title'];
        }

        $text = $temp;
        $data->text = $text;
        $count = db('offer')->where($where)->count();
        $totalpage = ceil($count/20);
        $data->totalpage = $totalpage;
        $list = [];
        foreach($res as $v){
            $arr = json_decode('{}');

            $arr->id = $v['id'];
            $arr->title = $v['offer_title'];
            $list[] = $arr;
        }
        $data->list = $list;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));

    }

    /////报价详细
    public  function offer_detail($token='',$offer_id='')
    {
        $data = json_decode('{}');
        if ($token == '' ) {
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $userId = $this->getuserid($token);
        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }
        $info = db('offer')->where('id',$offer_id)->find();
        $image_arr=[];
        if($info['image1']!=''){
            $image_arr[]=$info['image1'];
        }
        if($info['image2']!=''){
            $image_arr[]=$info['image2'];
        }
        if($info['image3']!=''){
            $image_arr[]=$info['image3'];
        }
        $data->image = $image_arr;
        $data->title = $info['offer_title'];
        $data->content = $info['offer_content'];
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }

    ////////////分享增加次数
    public function add_times($token,$type){
        $data = json_decode('{}');
        if ($token == '' ) {
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $userId = $this->getuserid($token);
        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }
        $userinfo = db('member')->where('member_id',$userId)->find();
        ////设备
        if($type==1){
            db('member')->where('member_id',$userId)->update(['device_share'=>1]);
            $output = array('data' => json_decode('{}'), 'msg' => '次数已增加', 'code' => '200');
            exit(json_encode($output));
        }
        ////报价
        if($type==2){
            db('member')->where('member_id',$userId)->update(['offer_share'=>1]);
            $output = array('data' => json_decode('{}'), 'msg' => '次数已增加', 'code' => '200');
            exit(json_encode($output));
        }
        /////资讯
        if($type==3){
            db('member')->where('member_id',$userId)->update(['article_share'=>1]);
            $output = array('data' => json_decode('{}'), 'msg' => '次数已增加', 'code' => '200');
            exit(json_encode($output));
        }
        /////黄页
        if($type==4){
            db('member')->where('member_id',$userId)->update(['yellow_share'=>1]);
            $output = array('data' => json_decode('{}'), 'msg' => '次数已增加', 'code' => '200');
            exit(json_encode($output));
        }
    }


    ///////分享链接接口
    public function share_url($token=''){
        $data = json_decode('{}');
        if ($token == '' ) {
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $userId = $this->getuserid($token);
        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }
        $url = 'http://' . $_SERVER['HTTP_HOST'].'/index.php/Admin/invent/invent?memberid='.$userId;
        $data->url = $url;
        $data->image ='http://' . $_SERVER['HTTP_HOST']."/uploads/image/2019-10-12/share.png";
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));

    }

////////////////////////////列表结束////////////////////////////
///////////三种认证
    public function check_memberauth($token='',$type=''){

        $data = json_decode('{}');
        if ($token == '' ) {
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $userId = $this->getuserid($token);
        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }
        $userinfo = db('member')->where('member_id',$userId)->find();
        $data->member_auth = $userinfo['member_auth'];
        $data->shop_auth = $userinfo['shop_auth'];
        $data->company_auth = $userinfo['company_auth'];
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));


    }



    /** 权限是否满足*
     * @param string $token
     * @param string $type
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function check_auth($token='',$type=''){
        $data = json_decode('{}');
        if ($token == '' ) {
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $userId = $this->getuserid($token);
        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }
        $userinfo = db('member')->where('member_id',$userId)->find();
        ////查询用户资料是否完善
        if($type==1){

            if($userinfo['member_name']=='' || $userinfo['member_mobile']=='' ||$userinfo['member_identity']=='' ||$userinfo['member_areaid']==''
                ||$userinfo['member_cityid']=='' ||$userinfo['member_provinceid']=='' ||$userinfo['main']=='' ||$userinfo['company_name']==''
                ||$userinfo['company_intro']==''){
                $output = array('data' => $data, 'msg' => '资料不完善', 'code' => '400');
                exit(json_encode($output));
            }else{
                $output = array('data' => $data, 'msg' => '已完善', 'code' => '200');
                exit(json_encode($output));
            }
        }
        ////////发布商品是否超过限制
        if($type==2){
            $is_vip = $this->is_vip($userId);
            if($is_vip==true){
                if($userinfo['member_name']=='' || $userinfo['member_mobile']=='' ||$userinfo['member_identity']=='' ||$userinfo['member_areaid']==''
                    ||$userinfo['member_cityid']=='' ||$userinfo['member_provinceid']=='' ||$userinfo['main']=='' ||$userinfo['company_name']==''
                    ||$userinfo['company_intro']==''){
                    $output = array('data' => $data, 'msg' => '资料不完善', 'code' => '400');
                    exit(json_encode($output));
                }
                $count = $userinfo['shop_number'];
                $real_count = db('goods')->where('user_id',$userId)->count();
                if($real_count<$count){
                    $output = array('data' => $data, 'msg' => '可以发布', 'code' => '200');
                    exit(json_encode($output));
                }else{
                    $output = array('data' => $data, 'msg' => '超过限制', 'code' => '400');
                    exit(json_encode($output));
                }
            }else{
                $output = array('data' => $data, 'msg' => '非会员不可发布', 'code' => '400');
                exit(json_encode($output));
            }


        }
        //////判断是否是会员
        if($type==3){
            $endtime = $userinfo['membervip_endtime'];
            $time = time();

            if($endtime<$time){
                $output = array('data' => $data, 'msg' => '会员已过期', 'code' => '400');
                /////下架所有发布的商品，修改会员状态
                db('goodscommon')->where('user_id',$userId)->delete();
                db('goods')->where('user_id',$userId)->delete();
                db('member')->where('member_id',$userId)->update(['member_isvip'=>0]);
                exit(json_encode($output));


            }else{
                $output = array('data' => $data, 'msg' => '会员未过期', 'code' => '200');
                exit(json_encode($output));
            }
        }
        /////供应查看次数限制
        if($type==4){
            $is_vip = $this->is_vip($userId);
            if($is_vip==false){
                $see_time = db('member')->where('member_id',$userId)->value('supply_see');
                if($see_time<5){
                    db('member')->where('member_id',$userId)->setInc('supply_see');
                    $output = array('data' => $data, 'msg' => '可观看', 'code' => '200');
                    exit(json_encode($output));
                }else{
                    $output = array('data' => $data, 'msg' => '不可观看', 'code' => '400');
                    exit(json_encode($output));
                }
            }else{

                $output = array('data' => $data, 'msg' => '可观看', 'code' => '200');
                exit(json_encode($output));
            }


        }

        //////// 直购
        if($type==5){
            $is_vip = $this->is_vip($userId);
            if($is_vip==false){
                $see_time = db('member')->where('member_id',$userId)->value('shop_see');
                if($see_time<5){
                    db('member')->where('member_id',$userId)->setInc('shop_see');
                    $output = array('data' => $data, 'msg' => '可观看', 'code' => '200');
                    exit(json_encode($output));
                }else{
                    $output = array('data' => $data, 'msg' => '不可观看', 'code' => '400');
                    exit(json_encode($output));
                }
            }else{
                $output = array('data' => $data, 'msg' => '可观看', 'code' => '200');
                exit(json_encode($output));
            }
        }
        /////////圈子
        if($type==6){
            $is_vip = $this->is_vip($userId);
            if($is_vip==false){
                $where['userid'] = $userId;
                $time1 = strtotime(date("Y-m-d",time())) ;
                $time2 = $time1 +86400;

                $where['article_time'] = array("between",array("$time1",$time2));

                $today = db('circle')->where($where)->find();
                if($today){
                    $output = array('data' => $data, 'msg' => '发布已达上限', 'code' => '400');
                    exit(json_encode($output));
                }else{
                    $output = array('data' => $data, 'msg' => '可发布', 'code' => '200');
                    exit(json_encode($output));

                }
            }else{
                $where['userid'] = $userId;
                $time1 = time() ;
                $time3 = $time1 - 300;
                $where['article_time'] = array("between",array("$time3",$time1));
                $today = db('circle')->where($where)->count();

                if($today<10){
                    $output = array('data' => $data, 'msg' => '可发布', 'code' => '200');
                    exit(json_encode($output));
                }
                $output = array('data' => $data, 'msg' => '不可发布，休息一下', 'code' => '401');
                exit(json_encode($output));
            }
        }
        //////设备
        if($type==7){
            $is_vip = $this->is_vip($userId);
            if($is_vip==false){
                $see_time = db('member')->where('member_id',$userId)->value('device_see');
                if($see_time<5 ){
                    db('member')->where('member_id',$userId)->setInc('device_see');
                    $output = array('data' => $data, 'msg' => '可观看', 'code' => '200');
                    exit(json_encode($output));
                }else if($see_time==5 && $userinfo['device_share']==0){
                    $output = array('data' => $data, 'msg' => '分享增加一次机会', 'code' => '401');
                    exit(json_encode($output));
                }else if($see_time==5 && $userinfo['device_share']==1){
                    db('member')->where('member_id',$userId)->setInc('device_see');
                    $output = array('data' => $data, 'msg' => '可观看', 'code' => '200');
                    exit(json_encode($output));
                }
                else{
                    $output = array('data' => $data, 'msg' => '不可观看', 'code' => '400');
                    exit(json_encode($output));
                }
            }else{
                $output = array('data' => $data, 'msg' => '可观看', 'code' => '200');
                exit(json_encode($output));
            }
        }
        //////报价
        if($type==8){
            $is_vip = $this->is_vip($userId);
            if($is_vip==false){
                $see_time = db('member')->where('member_id',$userId)->value('offer_see');
                if($see_time<5 ){
                    db('member')->where('member_id',$userId)->setInc('offer_see');
                    $output = array('data' => $data, 'msg' => '可观看', 'code' => '200');
                    exit(json_encode($output));
                }else if($see_time==5 && $userinfo['offer_share']==0){
                    $output = array('data' => $data, 'msg' => '分享增加一次机会', 'code' => '401');
                    exit(json_encode($output));
                }else if($see_time==5 && $userinfo['offer_share']==1){
                    db('member')->where('member_id',$userId)->setInc('offer_see');
                    $output = array('data' => $data, 'msg' => '可观看', 'code' => '200');
                    exit(json_encode($output));
                }
                else{
                    $output = array('data' => $data, 'msg' => '不可观看', 'code' => '400');
                    exit(json_encode($output));
                }
            }else{
                $output = array('data' => $data, 'msg' => '可观看', 'code' => '200');
                exit(json_encode($output));
            }
        }
        /////资讯
        if($type==9){
            $is_vip = $this->is_vip($userId);
            if($is_vip==false){
                $see_time = db('member')->where('member_id',$userId)->value('article_see');
                if($see_time<5 ){
                    db('member')->where('member_id',$userId)->setInc('article_see');
                    $output = array('data' => $data, 'msg' => '可观看', 'code' => '200');
                    exit(json_encode($output));
                }else if($see_time==5 && $userinfo['article_share']==0){
                    $output = array('data' => $data, 'msg' => '分享增加一次机会', 'code' => '401');
                    exit(json_encode($output));
                }else if($see_time==5 && $userinfo['article_share']==1){
                    db('member')->where('member_id',$userId)->setInc('article_see');
                    $output = array('data' => $data, 'msg' => '可观看', 'code' => '200');
                    exit(json_encode($output));
                }
                else{
                    $output = array('data' => $data, 'msg' => '不可观看', 'code' => '400');
                    exit(json_encode($output));
                }
            }else{
                $output = array('data' => $data, 'msg' => '可观看', 'code' => '200');
                exit(json_encode($output));
            }
        }
        /////黄页
        if($type==10){
            $is_vip = $this->is_vip($userId);

            if($is_vip==false){
                $see_time = db('member')->where('member_id',$userId)->value('yellow_see');
                if($see_time<5 && $userinfo['yellow_share']==1){
                    db('member')->where('member_id',$userId)->setInc('yellow_see');
                    $output = array('data' => $data, 'msg' => '可观看', 'code' => '200');
                    exit(json_encode($output));
                }else if($see_time==5 && $userinfo['yellow_share']==1){
                    $output = array('data' => $data, 'msg' => '不可观看', 'code' => '400');
                    exit(json_encode($output));
                }else if($see_time==0 && $userinfo['yellow_share']==0){
                    $output = array('data' => $data, 'msg' => '分享增加次数', 'code' => '401');
                    exit(json_encode($output));
                }else{
                    $output = array('data' => $data, 'msg' => '不可观看', 'code' => '400');
                    exit(json_encode($output));
                }
            }else{
                $see_time = db('member')->where('member_id',$userId)->value('yellow_see');
                if($see_time==300){
                    $output = array('data' => $data, 'msg' => '一天最多看300次', 'code' => '402');
                    exit(json_encode($output));
                }
                db('member')->where('member_id',$userId)->setInc('yellow_see');
                $output = array('data' => $data, 'msg' => '可观看', 'code' => '200');
                exit(json_encode($output));
            }
        }
        //////成交分享
        if($type==11){
            $is_vip = $this->is_vip($userId);
            $where['userid'] = $userId;
            $time1 = strtotime(date("Y-m-d",time())) ;
            $time3 = $time1 + 300;
            $where['article_time'] = array("between",array("$time1",$time3));
            $today = db('circle')->where($where)->count();
            if($today<3){
                $output = array('data' => $data, 'msg' => '可发布', 'code' => '200');
                exit(json_encode($output));
            }else{
                $output = array('data' => $data, 'msg' => '超过限制', 'code' => '400');
                exit(json_encode($output));
            }
        }
    }

    private  function is_vip($userId){

        $userinfo = db('member')->where('member_id',$userId)->find();
        $endtime = $userinfo['membervip_endtime'];
        $time = time();

        if($endtime<$time){
            /////下架所有发布的商品，修改会员状态
            db('goodscommon')->where('user_id',$userId)->delete();
            db('goods')->where('user_id',$userId)->delete();
            db('member')->where('member_id',$userId)->update(['member_isvip'=>0]);
            return false;
        }else{
            return true;
        }
    }

    //////////////给圈子点赞//////////////   active 1:点赞  2取消点赞
    public function  circle_zan($token,$circle_id,$active=''){
        $data = json_decode('{}');
        if ($token == '' || $circle_id=='' || $active=='') {
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $userId = $this->getuserid($token);
        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }
        if($active==1){
            $where['userid'] = $userId;
            $where['circle_id'] = $circle_id;
            $is_zan = db('circle_zan')->where($where)->find();
            if($is_zan){
                $output = array('data' => json_decode('{}'), 'msg' => '请勿重复点赞', 'code' => '400');
                exit(json_encode($output));
            }
            db('circle')->where('id',$circle_id)->setInc('zan');
            $insert['userid'] = $userId;
            $insert['circle_id'] = $circle_id;
            $insert['time'] = time();
            $id =  db('circle_zan')->insert($insert);
            if(isset($id)){
                $output = array('data' => json_decode('{}'), 'msg' => '点赞成功', 'code' => '200');
                exit(json_encode($output));
            }else{
                $output = array('data' => json_decode('{}'), 'msg' => '点赞失败', 'code' => '400');
                exit(json_encode($output));
            }
        }else{

            $where['circle_id'] = $circle_id;
            $where['userid'] = $userId;
            $zan_info =db('circle_zan')->where($where)->find();
            if($zan_info==''){
                $output = array('data' => json_decode('{}'), 'msg' => '无法取消点赞', 'code' => '400');
                exit(json_encode($output));
            }
            db('circle')->where('id',$circle_id)->setDec('zan');
            db('circle_zan')->where($where)->delete();
            $output = array('data' => json_decode('{}'), 'msg' => '取消成功', 'code' => '200');
            exit(json_encode($output));
        }

    }

    //////////////给成交分享点赞//////////////   active 1:点赞  2取消点赞
    public function  share_zan($token,$share_id,$active=''){
        $data = json_decode('{}');
        if ($token == '' || $share_id=='' || $active=='') {
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $userId = $this->getuserid($token);
        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }
        if($active==1){
            $where['userid'] = $userId;
            $where['share_id'] = $share_id;
            $is_zan = db('share_zan')->where($where)->find();
            if($is_zan){
                $output = array('data' => json_decode('{}'), 'msg' => '请勿重复点赞', 'code' => '400');
                exit(json_encode($output));
            }
            db('share')->where('id',$share_id)->setInc('zan');
            $insert['userid'] = $userId;
            $insert['share_id'] = $share_id;
            $insert['time'] = time();
            $id =  db('share_zan')->insert($insert);
            if(isset($id)){
                $output = array('data' => json_decode('{}'), 'msg' => '点赞成功', 'code' => '200');
                exit(json_encode($output));
            }else{
                $output = array('data' => json_decode('{}'), 'msg' => '点赞失败', 'code' => '400');
                exit(json_encode($output));
            }
        }else{

            $where['share_id'] = $share_id;
            $where['userid'] = $userId;
            $zan_info =db('share_zan')->where($where)->find();
            if($zan_info==''){
                $output = array('data' => json_decode('{}'), 'msg' => '无法取消点赞', 'code' => '400');
                exit(json_encode($output));
            }
            db('share')->where('id',$share_id)->setDec('zan');
            db('share_zan')->where($where)->delete();
            $output = array('data' => json_decode('{}'), 'msg' => '取消成功', 'code' => '200');
            exit(json_encode($output));
        }

    }

    ///////////圈子详情
    public  function  circle_detail($circle_id,$token=''){
        $data = json_decode('{}');
        if ( $circle_id=='' ) {
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $user_id = $this->getuserid($token);
        $is_zan= db('circle_zan')->where('circle_id',$circle_id)->where('userid',$user_id)->find();

        $info = db('circle')->where('id',$circle_id)->find();
        if($is_zan){
            $info['is_zan'] = 1;
        }else{
            $info['is_zan'] = 0;
        }
        $area =  db('area')->where('area_id',$info['area_id'])->value('area_name');
        $city =  db('area')->where('area_id',$info['city_id'])->value('area_name');

        $info['address'] = $area.$city;
        $info['content'] = $info['circle_content'];

        $userid = $info['userid'];
        $member_info = db('member')->where('member_id',$userid)->find();
        $info['date'] = $this->formatTime($info['article_time']);
        unset($info['time']);
        $info['avatar'] = $member_info['member_avatar'];
        $info['name'] =  $member_info['member_name'];
        unset($info['circle_content']);
        unset($info['ac_id']);
        unset($info['article_url']);
        unset($info['state']);
        unset($info['article_sort']);
        unset($info['article_title']);
        $image = substr($info['image'],0,-1);
        $image_arr = explode(",",$image);
        $info['image'] = $image_arr;
        $output = array('data' => $info, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }


    /////////资讯列表
    public  function news_list($page='',$token='',$keyword=''){
        $data = json_decode('{}');


        $where['ac_id'] =8;


        if($keyword !=''){
            $where['title'] = array('like',"%".$keyword."%");
        }


        $page-=1;
        $res = db('article')->where($where)->order('article_id desc')->limit($page*20,20)->select();
        $count =  db('article')->where($where)->count();
        $data->count = $count;
        $totalpage = ceil($count/20);
        $data->totalpage = $totalpage;
        $list = [];
        foreach($res as $v){
            $arr = json_decode('{}');
            $arr->image = 'http://' . $_SERVER['HTTP_HOST'] . '/uploads/' . ATTACH_ARTICLE  .'/'. $v['article_pic'];
            $arr->id = $v['article_id'];
            $arr->title = $v['article_title'];
            $arr->time = date('Y-m-d',$v['article_time']);
            $list[] = $arr;
        }
        $data->list = $list;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));

    }

    /////////资讯详情
    public  function news_detail($news_id){
        $data = json_decode('{}');
//        if ($token == '' ) {
//            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
//            exit(json_encode($output));
//        }
//        $userId = $this->getuserid($token);
//        if($userId==false){
//            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
//            exit(json_encode($output));
//        }
        $where['ac_id'] = 8;
        $where['article_id'] = $news_id;
        $info = db('article')->where($where)->find();
        $data->image = 'http://' . $_SERVER['HTTP_HOST'] . '/uploads/' . ATTACH_ARTICLE  .'/'. $info['article_pic'];
        $data->title = $info['article_title'];
        $data->time =date("Y-m-d",$info['article_time']) ;
        $data->content =htmlspecialchars_decode($info['article_content']);
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }


    //////////////圈子评论列表//////////////
    public function  discuss_list($token,$circle_id,$page=''){
        $page-=1;
        $data = json_decode('{}');
        if ($token == '' || $circle_id=='' ) {
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $userId = $this->getuserid($token);
        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }

        $where['circle_id'] = $circle_id;
        $res = db('circle_discuss')->where($where)->order('time DESC')->limit($page*20,20)->select();
        $count = db('circle_discuss')->where($where)->count();
        $data->count = $count;
        $totalpage = ceil($count/20);
        $data->totalpage = $totalpage;
        foreach($res as $k=> $v){
            $userid = $v['userid'];
            $member = db('member')->where('member_id',$userid)->find();
            $res[$k]['avatar'] = $member['member_avatar'];
            $res[$k]['mmeber_name'] = $member['member_name'];
            $time = $v['time'];
            $date = $this->formatTime($time);
            $res[$k]['date'] = $date;
            unset($res[$k]['time']);
        }
        $data->list =$res;

        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));



    }
    ////时间转文字
    function formatTime($time) {
        $now_time = time();
        $t = $now_time - $time;
        $mon = (int) ($t / (86400 * 30));
        if ($mon >= 1) {
            return '一个月前';
        }
        $day = (int) ($t / 86400);
        if ($day >= 1) {
            return $day . '天前';
        }
        $h = (int) ($t / 3600);
        if ($h >= 1) {
            return $h . '小时前';
        }
        $min = (int) ($t / 60);
        if ($min >= 1) {
            return $min . '分钟前';
        }
        return '刚刚';
    }

    ///////////圈子评论
    public function circle_discuss($circle_id='',$token='',$content=''){
        $data = json_decode('{}');
        if ($token == '' || $circle_id=='') {
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $userId = $this->getuserid($token);
        if($userId==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }
        $insert['userid'] = $userId;
        $insert['circle_id'] = $circle_id;
        $insert['time'] = time();
        $insert['discuss'] = $content;
        db('circle_discuss')->insert($insert);
        db('circle')->where('id',$circle_id)->setInc('discuss');
        $output = array('data' => json_decode('{}'), 'msg' => '评论成功', 'code' => '200');
        exit(json_encode($output));
    }

    /** 生成订单 **/
//   ////json格式 key goodsid  value num
//   public function createOrder ($token = '', $addressId = '', $json='', $total = '', $mark='', $arrive_time, $voucherId='',$cartId='')
//    {
//
//        $data = json_decode('{}');
//        if ($token == '' || $addressId == '' || $json=='' ||  $total == '') {
//            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
//            exit(json_encode($output));
//        }
//        $time =time();
//        $userId = $this->getuserid($token);
//if($userId==false){
//$output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
//            exit(json_encode($output));
//}
//
//        $userInfo = db('member')->where('member_id',$userId)->find();
//        $json2 =str_replace("&quot;","\"",$json);
//
//
//
//        $price=0;
//        $goods_arr = json_decode($json2);
//        $transport = 0;
//        foreach($goods_arr as $v){
//            $goodsid = $v->goodsid;
//            $num= $v->num;
//            $tag = $v->tag;
//            if($tag==1){
//                $temp = db('goodscommon')->where('goods_commonid',$goodsid)->value('goods_state');
//
//                $goods_freight = db('goodscommon')->where('goods_commonid',$goodsid)->value('goods_freight');
//                $goods_name = db('goodscommon')->where('goods_commonid',$goodsid)->value('goods_name');
//                if($temp !=1){
//                    $output = array('data' => $data, 'msg' => '商品已下架', 'code' => '400');
//                    exit(json_encode($output));
//                }
//                $standards = $v->standards_id;
//                $standards_price =db('goods_standards')->where('id',$standards)->value('goods_price');
//                $goods_storage = db('goods_standards')->where('id',$standards)->value('goods_storage');
//                if(($goods_storage-$num)<0 ){
//
//                    $output = array('data' => $data, 'msg' => '商品没有库存，'.$goods_name.'无法购买', 'code' => '400');
//                    exit(json_encode($output));
//                }
//                if($goods_freight>$transport){
//                    $transport = $goods_freight;
//                }
//                $price += $standards_price*($v->num);
//            }
//            if($tag==2){
//                $temp = db('pbundling')->where('bl_id',$goodsid)->find();
//                $make_arr = $v->make;
//
//                foreach($make_arr as $vv){
//                    if($vv->is_make !=0){
//                        $price += $vv->make_price;
//                    }
//                }
//                if(empty($temp)){
//                    $output = array('data' => $data, 'msg' => '菜单已下架', 'code' => '400');
//                    exit(json_encode($output));
//                }
//                if($temp['bl_freight']>$transport){
//                    $transport = $temp['bl_freight'];
//                }
//                $price += $temp['bl_discount_price']*($v->num);
//            }
//
//
//        }
//
//        $price2 = $price+$transport;
//        $voucher_money=0;
//        if($voucherId !=''){
//            //////去掉用户优惠券
//            $voucher_arr = explode(',',$userInfo['voucher_id']);
//            foreach($voucher_arr as $kk=>$vv){
//                if($vv == $voucherId || $vv==''){
//                    unset($voucher_arr[$kk]);
//                    break;
//                }
//            }
//            $voucher_arr = array_filter($voucher_arr);
//            $voucher_str = implode(',',$voucher_arr);
//
//            $voucher_str.=",";
//            db('member')->where('member_id',$userId)->update(['voucher_id'=>$voucher_str]);
//        	$voucher_money = db('vouchertemplate')->where('vouchertemplate_id',$voucherId)->value('vouchertemplate_price');
//        	$price2 -=$voucher_money;
//        }
//
//        //添加未支付订单 order表
//        $order_sn = $this->getOrderSn();
//        $insert['order_sn'] = $order_sn;//订单号
//
//        $insert['buyer_id'] = $userId;//购买人Id
//        $insert['add_time'] = $time;
//        $insert['order_state'] = '10';
//        $insert['address_id'] = $addressId;
//        $insert['order_voucherid'] = $voucherId;
//        $insert['order_amount'] = $price2;
//        $insert['mark'] = $mark;
//        $insert['arrive_time'] = $arrive_time;
//        $insert['arrive_time'] = $price2;
//        $insert['shipping_fee'] = $transport;
//        $address_info = db('address')->where('address_id',$addressId)->find();
//        $insert['buyer_name'] = $address_info['address_realname'];
//        $id = db('order')->insertGetId($insert);
//        $insert2['order_id'] = $id;
//        $insert2['voucher_price'] = $voucher_money;
//
//        $insert2['reciver_name'] = $address_info['address_realname'];
//        $insert2['reciver_province_id'] = $address_info['area_id'];
//        $insert2['reciver_city_id'] = $address_info['city_id'];
//        $insert2['reciver_city_id'] = $address_info['city_id'];
//        $area = db('area')->where('area_id',$address_info['area_id'])->value('area_name');
//        $city = db('area')->where('area_id',$address_info['city_id'])->value('area_name');
//
//         $reciver_info = array(
//            'address' => $area . ' ' . $city,
//            'phone' => trim($address_info['address_mob_phone'] . ',' . '', ','),
//            'area' => '',
//            'street' => $address_info['district_id'],
//            'mob_phone' => $address_info['address_mob_phone'],
//            'tel_phone' => '',
//            'dlyp' => 'N',
//        );
//        $info2 = serialize($reciver_info);
//        $insert2['reciver_info'] = $info2;
//        db('ordercommon')->insert($insert2);
//
//        foreach($goods_arr as $k=>$v){
//        	$insert['goods_id'] = $v->goodsid;
//        	if($v->tag==1){
//                $standards_info = db('goods_standards')->where('id',$v->standards_id)->find();
//                $goods = db('goods')->where('goods_id',$v->goodsid)->find();
//                $insert['goods_num'] = $v->num;
//                if($standards_info['goods_storage']-$v->num >=0){
//                    db('goods_standards')->where('id',$v->standards_id)->setDec('goods_storage',$v->num);
//                }
//
//                unset($insert['order_voucherid']);
//                unset($insert['shipping_fee']);
//                unset($insert['address_id']);
//                unset($insert['add_time']);
//                unset($insert['mark']);
//                unset($insert['buyer_name']);
//                unset($insert['arrive_time']);
//                unset($insert['order_state']);
//                unset($insert['order_amount']);
//                unset($insert['order_sn']);
//
//                $insert['order_id'] = $id;
//                $insert['goods_name'] = $goods['goods_name'];
//                $insert['goods_price'] = $standards_info['goods_marketprice'];
//                $insert['goods_pay_price'] = $standards_info['goods_price'];
//                $insert['goods_image'] = $goods['goods_image'];
//                $insert['standards_id'] = $v->standards_id;
//                $insert['goods_type'] =1;
//                $insert['transport'] =$goods['goods_freight'];
//
//                $id2 = db('ordergoods')->insert($insert);
//            }else{
//
//               $make_arr = $v->make;
//
//               foreach($make_arr as $kk=> $vv){
//
//                   $goods_info = db('pbundlinggoods')->where('goods_id',$vv->foodid)->where('bl_id',$v->goodsid)->find();
//                   $bl_info = db('pbundling')->where('bl_id',$v->goodsid)->find();
//                   $insert3['order_id']=$id;
//                   $insert3['goods_id']=$vv->foodid;
//                   $insert3['goods_type'] =4;
//                   $insert3['goods_name'] =$goods_info['goods_name'];
//                   $insert3['goods_image'] =$goods_info['goods_image'];
//                   $insert3['goods_price'] = $goods_info['blgoods_price'];
//
//                   if($make_arr[$kk]->is_make ==1){
//
//                       $insert3['goods_pay_price'] = $goods_info['blgoods_price']+$make_arr[$kk]->make_price;
//                       $insert3['make_price'] = $make_arr[$kk]->make_price;
//                   }else{
//                       $insert3['goods_pay_price'] = $goods_info['blgoods_price'];
//                   }
//
//                   $insert3['buyer_id'] =$userId;
//                   $insert3['promotions_id'] =$goods_info['bl_id'];
//                   $insert3['goods_image'] =$goods_info['goods_image'];
//                   $insert3['goods_num'] =1;
//                   $insert3['transport'] =$bl_info['bl_freight'];
//                   $id2 = db('ordergoods')->insert($insert3);
//                   unset($insert3);
//               }
//
//
//
//
//            }
//        	if(empty($id2)){
//        		$output = array('data' => json_decode('{}'), 'msg' => '添加失败', 'code' => '402');
//                exit(json_encode($output));
//        	}
//        }
//        if($cartId !=''){
//            $cartlist = explode(',',$cartId);
//            foreach($cartlist as $v){
//                db('cart')->where('cart_id',$v)->delete();
//            }
//        }
//        $data->paystate = '0';
//        $data->order_sn = $order_sn;
//        $data->orderid = $id;
//        $output = array('data' => $data, 'msg' => '添加成功', 'code' => '200');
//        exit(json_encode($output));
//
//    }


    /////充值

    public  function  charge($token='',$id=''){
        $data = json_decode('{}');

        $userid = $this->getuserid($token);
        if($userid==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }
        $order_sn = $this->getOrderSn();
        $goodsinfo = db('membervip')->where('id',$id)->find();
        $insert['order_sn'] = $order_sn;
        $insert['order_amount'] = $goodsinfo['charge_money'];
        $insert['buyer_id'] = $userid;
        $insert['goods_id'] = $id;
        $orderid = db('order')->insertGetId($insert);
        $data->orderid = $orderid;
        $output = array('data' => $data, 'msg' => '添加成功', 'code' => '200');
        exit(json_encode($output));


    }

    public function  membervip_time($token){
        $data = json_decode('{}');
        if ($token == '') {
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }

        $userid = $this->getuserid($token);
        if($userid==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }
        $time = db('member')->where('member_id',$userid)->value('membervip_endtime');
        $data->time = date("Y-m-d",$time);
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }

    ////客服电话
    public function service_phone(){
        $data = json_decode('{}');
        $phone =   db('config')->where('id',2)->value('value');
        $data->phone = $phone;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }
    /////订单列表
    public function orderList($token='',$status='',$page=''){
        $data = json_decode('{}');
        $list=[];
        $list2=[];
        if ($token == ''  || $page=='') {
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $page-=1;
        $userid = $this->getuserid($token);
        if($userid==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }

        $where='';
        $where['buyer_id'] = $userid;

        if($status !=''){
            $where['order_state'] = $status;
        }
        $orderlist = db('order')->where($where)->limit($page*20,20)->order('add_time DESC')->select();

        $count = db('order')->where($where)->count();
        $totalpage = ceil($count/20);
        $data->totalpage = $totalpage;
        foreach($orderlist as $k=>$v){

            $goods_list = db('ordergoods')->where('order_id',$v['order_id'])->where('promotions_id',0)->select();
            $menu_list = db('ordergoods')->where('order_id',$v['order_id'])
                ->where('promotions_id','neq',0)->group('promotions_id')->select();
            $arr=json_decode('{}');
            $arr->date = date("Y-m-d H:i:s",$v['add_time']);
            $arr->status = $v['order_state'];
            $arr->order_sn = $v['order_sn'];
            $arr->order_id = $v['order_id'];
            $arr->taransport_sn = $v['shipping_code'];
            $goods_marketprice=0;
            $goods_count = 0;
            $list2=[];
            if(count($goods_list) !=0){
                foreach($goods_list as $kk=>$vv){
                    $goods_info = db('ordergoods')->where('order_id',$v['order_id'])->where('promotions_id',0)->where('goods_id',$goods_list[$kk]['goods_id'])->find();

                    $arr2 = json_decode('{}');
                    $arr2->goods_image = 'http://' . $_SERVER['HTTP_HOST']."/uploads/home/store/goods/".$goods_info['goods_image'];

                    $standards_info  = db('goods_standards')->where('id',$goods_list[$kk]['standards_id'])->find();

                    $arr2->standards = $standards_info;
                    $arr2->goods_marketprice = $standards_info['goods_marketprice'];
                    $arr2->goods_price = $standards_info['goods_price'];
                    $arr2->transport = $goods_info['transport'];
                    $arr2->goods_num = $goods_list[$kk]['goods_num'];
                    $arr2->goods_name = $goods_info['goods_name'];
                    $arr2->menu = [];
                    $arr2->tag = 1;
                    $list2[] =$arr2;
                    $goods_count+=$goods_list[$kk]['goods_num'];
                    $goods_marketprice += $standards_info['goods_marketprice']*$vv['goods_num'];


                }
            }

            ////菜单

            if(count($menu_list) !=0){
                foreach($menu_list as $kk=>$vv){
                    $pbundling_id = $vv['promotions_id'];
                    $arr2 = json_decode('{}');
                    $menu = db('pbundling')->where('bl_id',$pbundling_id)->find();
                    if(empty($menu)){
                        continue;
                    }
                    $menu_food = db('pbundlinggoods')->where('bl_id',$pbundling_id)->field('goods_name,use')->select();
                    $arr2->menu = $menu_food;
                    $arr2->goods_name = $menu['bl_name'];
                    $arr2->goods_image = 'http://' . $_SERVER['HTTP_HOST']."/uploads/home/store/goods/".$menu['image'];
                    $arr2->goods_num = $vv['goods_num'];
                    $arr2->goods_marketprice = $menu['bl_discount_price'];
                    $arr2->goods_price =$menu['bl_discount_price'];
                    $arr2->standards = json_decode('{}');
                    $arr2->tag = 2;
                    $list2[] =$arr2;
                    $goods_count+=$menu_list[$kk]['goods_num'];
                    $goods_marketprice += $menu['bl_discount_price']*$vv['goods_num'];

                }
            }
            $arr->goods_count = $goods_count;
            $arr->list =$list2;
            $arr->goods_marketprice = (string)$goods_marketprice;
            $arr->goods_price = (string)$v['order_amount'];
            $list[] = $arr;
        }
        $data->list = $list;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }

    ////订单详情
    public function orderInfo($orderId=''){
        $data = json_decode('{}');
        $list=[];
        if ($orderId == ''  ) {
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $where['order_id']=  $orderId;
        $orderlist = db('order')->where($where)->find();

        $goods_marketprice=0;
        $goods_price = 0;
        $goods_list = db('ordergoods')->where('order_id',$orderlist['order_id'])->where('promotions_id',0)->select();
        $menu_list = db('ordergoods')->where('order_id',$orderlist['order_id'])
            ->where('promotions_id','neq',0)->group('promotions_id')->select();

        if(count($goods_list) !=0) {
            foreach ($goods_list as $k => $v) {
                $arr = json_decode('{}');
                $goods_info = db('goods')->where('goods_id', $v['goods_id'])->find();
                $arr->goods_name = $v['goods_name'];
                $arr->transport = $goods_info['goods_freight'];

                $standards = db('goods_standards')->where('id', $v['standards_id'])->find();
                $arr->standards = $standards;
                $arr->goods_num = $v['goods_num'];
                $arr->goods_price = (string)$standards['goods_price'];
                $arr->goods_marketprice = (string)$standards['goods_marketprice'];
                $arr->goods_image = 'http://' . $_SERVER['HTTP_HOST'] . "/uploads/home/store/goods/" . $v['goods_image'];
                $arr->menu=[];
                $arr->tag=1;
                $goods_marketprice += $standards['goods_marketprice'] * $v['goods_num'];
                $goods_price += $standards['goods_price'] * $v['goods_num'];
                $list[] = $arr;
            }
        }

        if(count($menu_list) !=0){
            foreach($menu_list as $kk=>$vv){
                $pbundling_id = $vv['promotions_id'];
                $arr2 = json_decode('{}');
                $menu = db('pbundling')->where('bl_id',$pbundling_id)->find();
                $menu_food = db('pbundlinggoods')->where('bl_id',$pbundling_id)->field('goods_name,use')->select();
                $arr2->menu = $menu_food;
                $arr2->goods_name = $menu['bl_name'];
                $arr2->goods_image = 'http://' . $_SERVER['HTTP_HOST']."/uploads/home/store/goods/".$menu['image'];
                $arr2->goods_num = $vv['goods_num'];
                $arr2->goods_marketprice = $menu['bl_discount_price'];
                $arr2->goods_price = $menu['bl_discount_price'];
                $arr2->standards = json_decode('{}');
                $arr2->tag=2;
                $list[] =$arr2;
                $goods_marketprice += $menu['bl_discount_price']*$vv['goods_num'];

            }
        }

        $addressId = $orderlist['address_id'];

        $address = db('address')->where('address_id',$addressId)->find();
        $area = db('area')->where('area_id',$address['area_id'])->value('area_name');
        $city = db('area')->where('area_id',$address['city_id'])->value('area_name');
        $district = db('area')->where('area_id',$address['district_id'])->value('area_name');
        $address['address_name'] = $area.$city.$district;
        unset($address['address_tel_phone']);
        $data->address = $address;
        $data->date = date("Y-m-d H:i:s",$orderlist['add_time']);
        $data->arrive_date = $orderlist['mark'];
        if($orderlist['shipping_code'] !=0){
            $express_id = db('ordercommon')->where('order_id',$orderId)->value('shipping_express_id');
            $company_name =  db('express')->where('express_id',$express_id)->value('express_name');
            $data->company = $company_name;
        }else{
            $data->company = '';
        }


        $data->transport = $orderlist['shipping_fee'];
        $data->transport_sn = $orderlist['shipping_code'];
        $data->order_status = $orderlist['order_state'];
        if($orderlist['payment_code']=='alipay'){
            $orderlist['payment_code']='支付宝';
        }
        if($orderlist['payment_code']=='wxpay'){
            $orderlist['payment_code']='微信';
        }
        $data->payment = $orderlist['payment_code'];
        $data->order_sn = $orderlist['order_sn'];
        $data->goods_marketprice = (string)$goods_marketprice;
        if($orderlist['order_voucherid'] !=''){
            $money = db('vouchertemplate')->where('vouchertemplate_id',$orderlist['order_voucherid'])->value('vouchertemplate_price');
            $data->goods_price = (string)$orderlist['order_amount']-$orderlist['shipping_fee']+$money;
        }else{
            $data->goods_price = (string)$orderlist['order_amount']-$orderlist['shipping_fee'];
        }

        $data->order_price = (string)$orderlist['order_amount'];
        $data->list = $list;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }
    //////支付宝支付
    public function pay($orderId='',$type=''){
        $data = json_decode("{}");
        if($orderId=='' || $type=='' ){
            $output = array('data' => $data, 'msg' => '缺少参数', 'code' => '400');
            exit(json_encode($output));
        }

        $order = db('order')->where('order_id',$orderId)->find();

        $order_sn = $order['order_sn'];

        if($type==1){

            vendor('alipay.AopSdk');
            vendor("alipay.aop.request.AlipayTradeAppPayRequest");
            $aop = new \AopClient ();
            $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
            $aop->appId = '2019092067632222';
            $aop->rsaPrivateKey = 'MIIEpAIBAAKCAQEAyqB5fmhNgVAxGFDF/fe4ebRk8GEA5DfFfLNRzsA0/0JfHUcb0pulZHjUTBKONyU2GABBSBdOToYiFRwFxvSSjGbXBt5BF7lorupt6RilEVmtCKtROutwP/SPlIaoXT4VrilblT6NqY8uGApPFAGTBzIrGa2Ib2i1ufopwL7YnBIjzH0CJH+bGkffOvETotU1Pk812CgOLugHQGbCrzFwVeFqciX6JeABZKuMdeynNd5tIZ2sdaq1F8Ol5IMH/MKQ0jcS8vLQwS9743YFtZcQZv0wtqmWIwiUajqZdzmTE3I7JpS40kbXnpZL7hUQqYjmc2QYc/szym0jY8DzTY3AtQIDAQABAoIBAGDXVXmeq/wzsWMnp5j7vgUcvGlOUzi/lvlEUsL3hdzBgefiRl/f16ovPXemHqYoeLP72zdzPA+3d66TGAfAeBH2TKqRqpaGHIwMqr8O8kVakKJmDoqUX6+RWNXpjaoStBXq2kR4AwiYz7TZqHWtUvHLfmHlWCG277OU5kOicrAT+Nm87ZfJXIv6EMpcUEFdvlFXSKqgSFBMkhqPwuErTlwHF44RRnH7wI+N7nfoNIBDQbWt6IGG+50SJgJi1aRVGC3XEf2PR6PoquM6y8yQ39ulSEeo/sd93Vavy6F0BFIDX3NRKx/JivdQRwLqKnzPpB50AjHjy+8XOu48H9RBSXkCgYEA+DEoihPV99/mr8P3tzLSqQOjMiBJx+5kKR410ejtaSwRqUx3lBCLur3Pb9FH3JuGYRSowUZgeMlnXtNS4PST74a8GwlGWASaqkUjnXFy44qTExd8Tlfc24E5UnSVRjmR1JNIKs+Cy8kN2KvCdtorkU8Pbl18ffHjLMUrSyspaG8CgYEA0QBaJYr9p7ItS72MbI7B5ozZJY5O4G0JCbyAb/UtnvoqnF/y+9rCN42us5pXC355tFQZXpgwSNwRnXQC20YDqu55zyf97pWC1NK7GOCR/Il2wIWNPwcsRXOUeTt/Sz+3fXzAnDENnWyHwtLEABCRrquHvc1pGhc3tVpYqwZIkxsCgYA13fV+gm+eLOpUm6PYDx/JrxBsgLWCvyreAcCMnpFokjgDFqWdbTnmfevXyQRfzSGNUH6P9EZb8NqOqi8CxBKXmhaZh5nM4LLw4bCpK0ZUPG9PZXmFR2yX96QJUWRUqYoNKSowoHky4aAvtpeuVAvArfgbbA7pBubXgLO0zNlf4QKBgQC+kZCg/NwOxYNRtXLOJVkeDD2PZfP75M/B5fRCoY9Ijxi9Xyuig7RljTXHpCpMW7VDPQ+o1iHovWj+ZaKZJ3z+pdXBktiSbBdQURmyNEpIt1rlbqD84GB4r0upQxvtlBqOPGsvv/aHHUeo2B9JY9JCLztlUF/OH293V/rTbrZMEwKBgQDV33h75lGTuFW+3u+tCOyp4Ir3yjupuuqV59lGriV0h9PBN85UcqdHZ9eSTWs/u2bOpNdDTWaWFS5KjLlq8CsN6+ad5xbGWKS4hOiBqgmW4Q7a9x7bFNO92hhIC93BdIYf+Qeg1TSuMYdHsNwZqQCSs9ufb7uURgyEid+V4kwyFg==';
            $aop->alipayrsaPublicKey='MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAjYrOrIaD0mQHSTbOwf7ORQPq5fjSIz7LnfBBdF7To1CVIQZA1AxZJns4cUR87G0+e27E/gS3yTbpauHCnNGpBoKdih+yWm1W0grVZJbINzLRmENWjf5OnOohFyG17NuHjyf8xP9A6302sae6DdZ4Q8fb3QG13SAMZKwSeFo7RjuL7EGJKUopilNBFCMhLcJo5gWG/6mExjTilVeg68rm4z00/uCKvGWcke5vuSHUXFgOmjIabmKVPEbG1sQ8hXl4FcXJiZP8ghryR71AKwll88SGhbeZQQSpGRHKHKMSkacg3JWx+TZZt9vOLDc3uC/bDzoCaxBejU56i2GeNrw59wIDAQAB';
            $aop->apiVersion = '1.0';
            $aop->signType = 'RSA2';
            $aop->postCharset='UTF-8';
            $aop->format='json';
            $goodsname = '订单支付';
            $order_sn = $order['order_sn'];

            $order_amount = $order['order_amount'];
            $request = new \AlipayTradeAppPayRequest ();
            $request->setBizContent("{" .
                "\"total_amount\":\"0.01\"," .
                "\"product_code\":\"QUICK_MSECURITY_PAY\"," .
                "\"subject\":\"订单支付\"," .
                "\"out_trade_no\":\"$order_sn\"," .
                "  }");

            $notify_url = "http://".$_SERVER['HTTP_HOST']."/index.php/home/api/aliNotify";
            $request->setNotifyUrl($notify_url);

            $response = $aop->sdkExecute($request);

            $output = array('data' => ['data'=>$response], 'msg' => '查询成功', 'code' => '200');
            $output2 = json_encode($output);
//            dump($output2);
//            $rsa = $this->rsa($output2);
            exit($output2);

        }
        if($type==2){
            ///////secret    c39af6964824b85401e0f20f953c36bc
            vendor('wxpay.lib.WxPay#Api');
            $ip = $_SERVER['REMOTE_ADDR'];
            $notify_url = "http://".$_SERVER['HTTP_HOST']."/index.php/home/api/wxNotify";
            ////32位秘钥
            $key='129f1b9ed6264093e9bd4f04a32ce7ef';
            $order_amount=$order['order_amount'];
            $order = [
                'appid' => 'wx003999af6c8848d5',
                'mch_id' => '1555668951',
                'nonce_str' => md5(rand(10000,99999)),
                'body' => '订单支付',
                'out_trade_no' => $order_sn,
                'total_fee' =>  $order_amount*100,
                'spbill_create_ip' => $ip,
                "notify_url" => $notify_url,
                'trade_type' => 'APP',
            ];

            $strSignTmp = "appid=".$order['appid'].
                "&body=".$order['body'].
                "&mch_id=".$order['mch_id'].
                "&nonce_str=".$order['nonce_str'].
                "&notify_url=".$order['notify_url'].
                "&out_trade_no=".$order['out_trade_no'].
                "&spbill_create_ip=".$order['spbill_create_ip'].
                "&total_fee=".$order['total_fee'].
                "&trade_type=".$order['trade_type'].
                "&key=".$key;

            $sign = strtoupper(md5($strSignTmp));

            $appid = $order["appid"];
            $mch_id = $order["mch_id"];
            $nonce_str =  $order["nonce_str"];
            $body = $order['body'];
            $out_trade_no =  $order["out_trade_no"];
            $total_fee =  $order["total_fee"];
            $notify_url =  $order["notify_url"];
            $trade_type =  $order["trade_type"];
            $spbill_create_ip = $order["spbill_create_ip"];

            $post_data = "<xml>
                       <appid>$appid</appid>  
                       <body>$body</body>
                       <mch_id>$mch_id</mch_id>
                       <nonce_str>$nonce_str</nonce_str>
                       <notify_url>$notify_url</notify_url>
                       <out_trade_no>$out_trade_no</out_trade_no>
                       <spbill_create_ip>$spbill_create_ip</spbill_create_ip>
                       <total_fee>$total_fee</total_fee>
                       <trade_type>$trade_type</trade_type>
                       <sign>$sign</sign>
                      </xml>";


            $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";

            $ch = curl_init();
            // 设置curl允许执行的最长秒数
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
            curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
            // 获取的信息以文件流的形式返回，而不是直接输出。
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            //发送一个常规的POST请求。
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            //要传送的所有数据
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

            $response = curl_exec($ch);
            //将xml格式的$response 转成数组
            $objectxml = json_decode( json_encode( simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA) ), true );
            //若预下单成功，return_code 和result_code为SUCCESS。
            //  return $objectxml['return_code'];
            if($objectxml['return_code'] == 'SUCCESS')  {

                curl_close($ch);

                $data = array();
                $data['appId'] = $objectxml['appid'];
                $data['partnerId'] = $objectxml['mch_id'];
                $data['prepayId'] = $objectxml['prepay_id'];
                $data['package'] = 'Sign=WXPay';
                $data['nonceStr'] = $objectxml['nonce_str'];
                $data['timestamp'] = time();

                $strSignTmpSecond = "appid=".$objectxml['appid'].
                    "&noncestr=".$objectxml['nonce_str'].
                    "&package=".'Sign=WXPay'.
                    "&partnerid=".$objectxml['mch_id'].
                    "&prepayid=".$objectxml['prepay_id'].
                    "&timestamp=".time().
                    "&key=".$key;

                $signSecond = strtoupper(md5($strSignTmpSecond));

                $data['sign'] = $signSecond;
                $data =json_encode($data);
                $output = array('data' => ['data'=>$data], 'msg' => '查询成功', 'code' => '200');
                exit(json_encode($output));
            }else{
                $output = array('data' => $data, 'msg' => '该订单已支付', 'code' => '400');
                exit(json_encode($output));
            }
        }
    }

    /////支付宝退款
    public function transfer($token='',$money=''){
        $order = db('order')->where('order_sn',"201910181107326865028128")->find();
        $number = "201910181107326865028128";
        vendor('alipay.AopSdk');
        vendor("alipay.aop.request.AlipayTradeRefund");
        $aop = new \AopClient ();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = '2019092067632222';
        $aop->rsaPrivateKey = 'MIIEpAIBAAKCAQEAyqB5fmhNgVAxGFDF/fe4ebRk8GEA5DfFfLNRzsA0/0JfHUcb0pulZHjUTBKONyU2GABBSBdOToYiFRwFxvSSjGbXBt5BF7lorupt6RilEVmtCKtROutwP/SPlIaoXT4VrilblT6NqY8uGApPFAGTBzIrGa2Ib2i1ufopwL7YnBIjzH0CJH+bGkffOvETotU1Pk812CgOLugHQGbCrzFwVeFqciX6JeABZKuMdeynNd5tIZ2sdaq1F8Ol5IMH/MKQ0jcS8vLQwS9743YFtZcQZv0wtqmWIwiUajqZdzmTE3I7JpS40kbXnpZL7hUQqYjmc2QYc/szym0jY8DzTY3AtQIDAQABAoIBAGDXVXmeq/wzsWMnp5j7vgUcvGlOUzi/lvlEUsL3hdzBgefiRl/f16ovPXemHqYoeLP72zdzPA+3d66TGAfAeBH2TKqRqpaGHIwMqr8O8kVakKJmDoqUX6+RWNXpjaoStBXq2kR4AwiYz7TZqHWtUvHLfmHlWCG277OU5kOicrAT+Nm87ZfJXIv6EMpcUEFdvlFXSKqgSFBMkhqPwuErTlwHF44RRnH7wI+N7nfoNIBDQbWt6IGG+50SJgJi1aRVGC3XEf2PR6PoquM6y8yQ39ulSEeo/sd93Vavy6F0BFIDX3NRKx/JivdQRwLqKnzPpB50AjHjy+8XOu48H9RBSXkCgYEA+DEoihPV99/mr8P3tzLSqQOjMiBJx+5kKR410ejtaSwRqUx3lBCLur3Pb9FH3JuGYRSowUZgeMlnXtNS4PST74a8GwlGWASaqkUjnXFy44qTExd8Tlfc24E5UnSVRjmR1JNIKs+Cy8kN2KvCdtorkU8Pbl18ffHjLMUrSyspaG8CgYEA0QBaJYr9p7ItS72MbI7B5ozZJY5O4G0JCbyAb/UtnvoqnF/y+9rCN42us5pXC355tFQZXpgwSNwRnXQC20YDqu55zyf97pWC1NK7GOCR/Il2wIWNPwcsRXOUeTt/Sz+3fXzAnDENnWyHwtLEABCRrquHvc1pGhc3tVpYqwZIkxsCgYA13fV+gm+eLOpUm6PYDx/JrxBsgLWCvyreAcCMnpFokjgDFqWdbTnmfevXyQRfzSGNUH6P9EZb8NqOqi8CxBKXmhaZh5nM4LLw4bCpK0ZUPG9PZXmFR2yX96QJUWRUqYoNKSowoHky4aAvtpeuVAvArfgbbA7pBubXgLO0zNlf4QKBgQC+kZCg/NwOxYNRtXLOJVkeDD2PZfP75M/B5fRCoY9Ijxi9Xyuig7RljTXHpCpMW7VDPQ+o1iHovWj+ZaKZJ3z+pdXBktiSbBdQURmyNEpIt1rlbqD84GB4r0upQxvtlBqOPGsvv/aHHUeo2B9JY9JCLztlUF/OH293V/rTbrZMEwKBgQDV33h75lGTuFW+3u+tCOyp4Ir3yjupuuqV59lGriV0h9PBN85UcqdHZ9eSTWs/u2bOpNdDTWaWFS5KjLlq8CsN6+ad5xbGWKS4hOiBqgmW4Q7a9x7bFNO92hhIC93BdIYf+Qeg1TSuMYdHsNwZqQCSs9ufb7uURgyEid+V4kwyFg==';
        $aop->alipayrsaPublicKey='MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAjYrOrIaD0mQHSTbOwf7ORQPq5fjSIz7LnfBBdF7To1CVIQZA1AxZJns4cUR87G0+e27E/gS3yTbpauHCnNGpBoKdih+yWm1W0grVZJbINzLRmENWjf5OnOohFyG17NuHjyf8xP9A6302sae6DdZ4Q8fb3QG13SAMZKwSeFo7RjuL7EGJKUopilNBFCMhLcJo5gWG/6mExjTilVeg68rm4z00/uCKvGWcke5vuSHUXFgOmjIabmKVPEbG1sQ8hXl4FcXJiZP8ghryR71AKwll88SGhbeZQQSpGRHKHKMSkacg3JWx+TZZt9vOLDc3uC/bDzoCaxBejU56i2GeNrw59wIDAQAB';
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='UTF-8';
        $aop->format='json';
        $request = new \AlipayTradeRefundRequest();
        $request->setBizContent("{" .
            "\"out_trade_no\":\"201910181143264553783125\"," .
            "\"refund_amount\":0.01," .
            "\"operator_id\":\"OP001\"," .
            "\"store_id\":\"NJ_S_001\"," .
            "\"terminal_id\":\"NJ_T_001\"" .
            "  }");
        $result = $aop->execute ( $request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";

        $resultCode = $result->$responseNode->code;

        if(!empty($resultCode)&&$resultCode == 10000){
            echo "成功";
        } else {
            echo "失败";
        }
    }
    /////删除订单
    public function delete_order($orderId){
        $data = json_decode("{}");
        if($orderId==''){
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        db('order')->where('order_id',$orderId)->delete();
        db('ordergoods')->where('order_id',$orderId)->delete();
        $output = array('data' => $data, 'msg' => '删除成功', 'code' => '200');
        exit(json_encode($output));
    }
    //////取消订单
    public function cancel_order($orderId){
        $data = json_decode("{}");
        if($orderId==''){
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $orderinfo = db('order')->where('order_id',$orderId)->find();
//    	$point = $orderinfo['order_point'];
        $voucherid = $orderinfo['order_voucherid'];
        $buyer_id = $orderinfo['buyer_id'];
        $member = db('member')->where('member_id',$buyer_id)->find();
        $member_voucherid = $member['voucher_id'];

        if($voucherid !=''){
            $member_voucherid .= $voucherid.",";
            db('member')->where('member_id',$buyer_id)->update(['voucher_id'=>$member_voucherid]);
        }
        db('order')->where('order_id',$orderId)->update(['order_state'=>0]);
        $order_goods = db('ordergoods')->where('order_id',$orderId)->where('goods_type',1)->select();
        foreach($order_goods as $v){
            $standards_id = $v['standards_id'];
            db('goods_standards')->where('id',$standards_id)->setInc('goods_storage',$v['goods_num']);
        }
        $output = array('data' => $data, 'msg' => '取消成功', 'code' => '200');
        exit(json_encode($output));
    }

    /////确认订单
    public function check_order($orderId){
        $data = json_decode("{}");
        $time = time();
        if($orderId==''){
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $ordercommon = db('order')->where('order_id',$orderId)->value('order_state');
        if($ordercommon !=30){
            $output = array('data' => $data, 'msg' => '商家未发货', 'code' => '400');
            exit(json_encode($output));
        }
        $id =db('order')->where('order_id',$orderId)->update(['order_state'=>40]);

        $list2 = db('ordergoods')->where('order_id',$orderId)->select();
        foreach($list2 as $v){
            $order = db('order')->where('order_id',$orderId)->find();
            $message['to_member_id'] = $order['buyer_id'];
            $message['goods_id'] = $v['goods_id'];
            $message['message_type']=2;
            $message['message_body']='订单已签收';
            $message['message_time']=$time;
            $message['message_title']='订单已签收';
            $message['order_sn']=db('order')->where('order_id',$orderId)->value('order_sn');
            db('message')->insert($message);

        }
        if($id){
            $output = array('data' => $data, 'msg' => '已确认', 'code' => '200');
            exit(json_encode($output));
        }else{
            $output = array('data' => $data, 'msg' => '确认失败', 'code' => '400');
            exit(json_encode($output));
        }

    }

    /////修改手机号
    public function change_mobile($token='',$mobile='',$code=''){
        $data = json_decode("{}");
        if($token=='' || $mobile=='' || $code==''){
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $userid = $this->getuserid($token);
        if($userid==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }

        $real_code = db('verify_code')->where('verify_code_user_name',$mobile)->where('verify_code_type',4)->order('verify_code_add_time DESC')->value('verify_code');
        if($real_code != $code){
            $output = array('data' => $data, 'msg' => '验证码不匹配', 'code' => '400');
            exit(json_encode($output));
        }
        $id = db('member')->where('member_id',$userid)->update(['member_mobile'=>$mobile]);
        if($id){
            $output = array('data' => $data, 'msg' => '修改成功', 'code' => '200');
            exit(json_encode($output));
        }else{
            $output = array('data' => $data, 'msg' => '修改失败', 'code' => '400');
            exit(json_encode($output));
        }
    }



    /////给安卓微信用的微信支付
    public function wxpay($orderId=''){
        $order = db('order')->where('order_id',$orderId)->find();

        $order_sn = $order['order_sn'];
        vendor('wxpay.lib.WxPay#Api');
        $ip = $_SERVER['REMOTE_ADDR'];
        $notify_url = "http://".$_SERVER['HTTP_HOST']."/index.php/home/api/wxNotify";
        $order = [
            'appid' => 'wx003999af6c8848d5',
            'mch_id' => '1555668951',
            'nonce_str' => md5(rand(10000,99999)),
            'body' => '订单支付',
            'out_trade_no' => $order_sn,
            'total_fee'=> $order['order_amount']*100,
            //订单金额，单位/分
//            'total_fee' => $order['order_amount']*100,
            'spbill_create_ip' => $ip,
            "notify_url" => $notify_url,
            'trade_type' => 'APP',
        ];

        $strSignTmp = "appid=".$order['appid'].
            "&body=".$order['body'].
            "&mch_id=".$order['mch_id'].
            "&nonce_str=".$order['nonce_str'].
            "&notify_url=".$order['notify_url'].
            "&out_trade_no=".$order['out_trade_no'].
            "&spbill_create_ip=".$order['spbill_create_ip'].
            "&total_fee=".$order['total_fee'].
            "&trade_type=".$order['trade_type'].
            "&key=129f1b9ed6264093e9bd4f04a32ce7ef";

        $sign = strtoupper(md5($strSignTmp));

        $appid = $order["appid"];
        $mch_id = $order["mch_id"];
        $nonce_str =  $order["nonce_str"];
        $body = $order['body'];
        $out_trade_no =  $order["out_trade_no"];
        $total_fee =  $order["total_fee"];
        $notify_url =  $order["notify_url"];
        $trade_type =  $order["trade_type"];
        $spbill_create_ip = $order["spbill_create_ip"];

        $post_data = "<xml>
                       <appid>$appid</appid>  
                       <body>$body</body>
                       <mch_id>$mch_id</mch_id>
                       <nonce_str>$nonce_str</nonce_str>
                       <notify_url>$notify_url</notify_url>
                       <out_trade_no>$out_trade_no</out_trade_no>
                       <spbill_create_ip>$spbill_create_ip</spbill_create_ip>
                       <total_fee>$total_fee</total_fee>
                       <trade_type>$trade_type</trade_type>
                       <sign>$sign</sign>
                      </xml>";


        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";

        $ch = curl_init();
        // 设置curl允许执行的最长秒数
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
        // 获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        //发送一个常规的POST请求。
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        //要传送的所有数据
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

        $response = curl_exec($ch);
        //将xml格式的$response 转成数组
        $objectxml = json_decode( json_encode( simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA) ), true );
        //若预下单成功，return_code 和result_code为SUCCESS。
        //  return $objectxml['return_code'];
        if($objectxml['return_code'] == 'SUCCESS')  {

            curl_close($ch);

            $data = array();
            $data['appId'] = $objectxml['appid'];
            $data['partnerId'] = $objectxml['mch_id'];
            $data['prepayId'] = $objectxml['prepay_id'];
            $data['package'] = 'Sign=WXPay';
            $data['nonceStr'] = $objectxml['nonce_str'];
            $data['timestamp'] = time();

            $strSignTmpSecond = "appid=".$objectxml['appid'].
                "&noncestr=".$objectxml['nonce_str'].
                "&package=".'Sign=WXPay'.
                "&partnerid=".$objectxml['mch_id'].
                "&prepayid=".$objectxml['prepay_id'].
                "&timestamp=".time().
                "&key=129f1b9ed6264093e9bd4f04a32ce7ef";

            $signSecond = strtoupper(md5($strSignTmpSecond));

            $data['sign'] = $signSecond;


            $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
            exit(json_encode($output));
        }
    }

    public function printf_info($data)
    {
        foreach($data as $key=>$value){
            echo "<font color='#00ff55;'>$key</font> :  ".htmlspecialchars($value, ENT_QUOTES)." <br/>";
        }
    }
    /** 支付宝回调 **/
    public function aliNotify () {

        vendor('alipay.AopSdk');
        $time = time();
        $aop = new \AopClient ();
        $aop->alipayrsaPublicKey='MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAjYrOrIaD0mQHSTbOwf7ORQPq5fjSIz7LnfBBdF7To1CVIQZA1AxZJns4cUR87G0+e27E/gS3yTbpauHCnNGpBoKdih+yWm1W0grVZJbINzLRmENWjf5OnOohFyG17NuHjyf8xP9A6302sae6DdZ4Q8fb3QG13SAMZKwSeFo7RjuL7EGJKUopilNBFCMhLcJo5gWG/6mExjTilVeg68rm4z00/uCKvGWcke5vuSHUXFgOmjIabmKVPEbG1sQ8hXl4FcXJiZP8ghryR71AKwll88SGhbeZQQSpGRHKHKMSkacg3JWx+TZZt9vOLDc3uC/bDzoCaxBejU56i2GeNrw59wIDAQAB';

        $flag = $aop->rsaCheckV1($_POST,$aop->alipayPublicKey,'RSA2');
        if ($flag)
        {
            $order_sn = $_POST['out_trade_no'];
            if ($_POST['trade_status'] == 'TRADE_SUCCESS' || $_POST['trade_status'] == 'TRADE_FINISHED')
            {
                $order = db('order')->where('order_sn',$order_sn)->find();
                $userid= $order['buyer_id'];
                $userinfo = db('member')->where('member_id',$userid)->find();
                $goodsid = $order['goods_id'];
                $update['member_isvip'] =1;
                $charge = db('membervip')->where('id',$goodsid)->value('charge_time');

                if( $userinfo['membervip_endtime'] !=''){
                    $endtime = $userinfo['membervip_endtime']+86400*30*$charge;
                }else{
                    $endtime = $time+86400*30*$charge;
                }
                $update['membervip_endtime'] =$endtime;
                $path1_money = db('config')->where('id',817)->value('value');
                $path2_money = db('config')->where('id',818)->value('value');
                $path3_money = db('config')->where('id',819)->value('value');
                $member_path1 = db('path')->where('uid',$userid)->find();
                $order_money = $order['order_amount'];
                if($member_path1['pid'] !=0){
                    $path1_id = $member_path1['pid'];
                    $givemoney1 = round($order_money*$path1_money/100);
                    db('member')->where('member_id',$path1_id)->setInc('available_predeposit',$givemoney1);
                    $insert1['userid'] = $path1_id;
                    $insert1['type'] = 1;
                    $insert1['time'] = time();
                    $insert1['money'] = $givemoney1;
                    db('moneychange')->insert($insert1);
                    $member_path2 = db('path')->where('uid',$path1_id)->find();
                    if($member_path2['pid'] !=0){
                        $path2_id = $member_path2['pid'];
                        $givemoney2 = round($order_money*$path2_money/100);
                        db('member')->where('member_id',$path2_id)->setInc('available_predeposit',$givemoney2);
                        $insert2['userid'] = $path2_id;
                        $insert2['type'] = 1;
                        $insert2['time'] = time();
                        $insert2['money'] = $givemoney2;
                        db('moneychange')->insert($insert2);
                        $member_path3 = db('path')->where('uid',$path2_id)->find();
                        if($member_path3['pid'] !=0){
                            $path3_id = $member_path3['pid'];
                            $givemoney3 = round($order_money*$path3_money/100);
                            db('member')->where('member_id',$path3_id)->setInc('available_predeposit',$givemoney1);
                            $insert3['userid'] = $path3_id;
                            $insert3['type'] = 1;
                            $insert3['time'] = time();
                            $insert3['money'] = $givemoney3;
                            db('moneychange')->insert($insert3);
                        }
                    }
                }

                $id =  db('member')->where('member_id',$userid)->update($update);
                if($id){
                    return 'success';
                } else{
                    return 'fail';
                }

            } else {
                return 'fail';
            }
        } else {
            return 'fail';
        }

    }

    /** 微信回调 **/
    public function wxNotify () {
        $time = time();

        $testxml = file_get_contents('php://input'); //获取xml格式
        $jsonxml = json_encode(simplexml_load_string($testxml, 'SimpleXMLElement', LIBXML_NOCDATA));
        $result = json_decode($jsonxml, true); //转成数组，

        if ($result) {
            $order_sn = $result['out_trade_no'];
            if($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {

                $order = db('order')->where('order_sn',$order_sn)->find();
                $userid= $order['buyer_id'];
                $userinfo = db('member')->where('member_id',$userid)->find();
                $goodsid = $order['goods_id'];
                $update['member_isvip'] =1;
                $charge = db('membervip')->where('id',$goodsid)->value('charge_time');

                if( $userinfo['membervip_endtime'] !=''){
                    $endtime = $userinfo['membervip_endtime']+86400*30*$charge;
                }else{
                    $endtime = $time+86400*30*$charge;
                }

                $update['membervip_endtime'] =$endtime;
                $path1_money = db('config')->where('id',817)->value('value');
                $path2_money = db('config')->where('id',818)->value('value');
                $path3_money = db('config')->where('id',819)->value('value');
                $member_path1 = db('path')->where('uid',$userid)->find();
                $order_money = $order['order_amount'];
                if($member_path1['pid'] !=0){
                    $path1_id = $member_path1['pid'];
                    $givemoney1 = round($order_money*$path1_money/100);
                    db('member')->where('member_id',$path1_id)->setInc('available_predeposit',$givemoney1);
                    $insert1['userid'] = $path1_id;
                    $insert1['type'] = 1;
                    $insert1['time'] = time();
                    $insert1['money'] = $givemoney1;
                    db('moneychange')->insert($insert1);
                    $member_path2 = db('path')->where('uid',$path1_id)->find();
                    if($member_path2['pid'] !=0){
                        $path2_id = $member_path2['pid'];
                        $givemoney2 = round($order_money*$path2_money/100);
                        db('member')->where('member_id',$path2_id)->setInc('available_predeposit',$givemoney2);
                        $insert2['userid'] = $path2_id;
                        $insert2['type'] = 1;
                        $insert2['time'] = time();
                        $insert2['money'] = $givemoney2;
                        db('moneychange')->insert($insert2);
                        $member_path3 = db('path')->where('uid',$path2_id)->find();
                        if($member_path3['pid'] !=0){
                            $path3_id = $member_path3['pid'];
                            $givemoney3 = round($order_money*$path3_money/100);
                            db('member')->where('member_id',$path3_id)->setInc('available_predeposit',$givemoney1);
                            $insert3['userid'] = $path3_id;
                            $insert3['type'] = 1;
                            $insert3['time'] = time();
                            $insert3['money'] = $givemoney3;
                            db('moneychange')->insert($insert3);
                        }
                    }
                }

                $info =  db('member')->where('member_id',$userid)->update($update);

                if ($info) {
                    return true;
                } else {

                    return false;
                }

            }else{
                return false;
            }
        }else{
            return false;

        }
    }

    public function http_get($url)
    {
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
        $tmpInfo = curl_exec($curl);     //返回api的json对象
        //关闭URL请求
        curl_close($curl);
        return $tmpInfo;    //返回json对象

    }
    /////微信登录
    public function wxlogin($code=''){
        $data = json_decode('{}');
        if ($code == ''  ) {
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }

        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=wx003999af6c8848d5&secret=c39af6964824b85401e0f20f953c36bc&code='.$code.'&grant_type=authorization_code';
        $res = json_decode($this->http_get($url));
        if(isset($res->errcode)){
            $output = array('data' => $data, 'msg' => '登录失败', 'code' => '401');
            exit(json_encode($output));

        }else{
            $token2 = $res->access_token;
            $openid = $res->openid;
            $info = db('member')->where('member_openid',$openid)->find();

            if($info){
                $time = time();
                db('member')->where('member_openid',$openid)->update(['member_logintime'=>$time]);
                db('member')->where('member_openid',$openid)->setInc('member_loginnum');
                if($info['member_logintime'] !=0){
                    db('member')->where('member_openid',$openid)->update(['member_isfirst'=>1]);
                }

                $data->access_token =$token2;
                $data->openid =$openid;
                $data->token =$info['token'];
                $data->rong_token =$info['rong_token'];
                $data->mobile =$info['member_mobile'];
                $output = array('data' => $data, 'msg' => '已授权', 'code' => '200');
                exit(json_encode($output));
            }else{
                $data->openid =$openid;
                $data->access_token =$token2;
                $data->token =$info['token'];
                $output = array('data' => $data, 'msg' => '未绑定手机号', 'code' => '400');
                exit(json_encode($output));
            }
        }


        $output = array('data' => $res, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }

    public function wx_register($code='',$phone='',$verify_code='',$access_token=''){
        $data = json_decode('{}');

        if ($code == '' ){

            $output = array('data' => $data, 'msg' => '缺少code', 'code' => '400');
            exit(json_encode($output));
        }
        if ($phone ==''  ){

            $output = array('data' => $data, 'msg' => '缺少phone', 'code' => '400');
            exit(json_encode($output));
        }
        if ($verify_code ==''){

            $output = array('data' => $data, 'msg' => '缺少验证码', 'code' => '400');
            exit(json_encode($output));
        }
        if ($access_token==''){

            $output = array('data' => $data, 'msg' => '缺少token', 'code' => '400');
            exit(json_encode($output));
        }
        $aa = db('member')->where('member_mobile',$phone)->find();

        if($aa){
            $output = array('data' => $data, 'msg' => '该手机号已注册，请更换手机号注册', 'code' => '400');
            exit(json_encode($output));
        }


        $real_code = db('verify_code')->where('verify_code_type',5)->where('verify_code_user_name',$phone)->order('verify_code_add_time DESC')->value('verify_code');
        if($real_code != $verify_code){
            $data->token='';
            $output = array('data' => $data, 'msg' => '验证码不匹配', 'code' => '400');
            exit(json_encode($output));
        }
        $update['member_openid'] = $code;
        $update['member_mobile'] = $phone;
        $update['token'] = $this->gettoken();
        $url2 = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$code;
        $res2 = json_decode($this->http_get($url2),true);
        $update['member_name'] =$res2['nickname'];
        $update['member_avatar'] =$res2['headimgurl'];
        $update['member_logintime'] =time();
        $update['member_addtime'] =time();
        for($i=0;$i<100;$i++){
            $invent_code = rand(100000,999999);
            $temp = db('member')->where('invent_code',$invent_code)->find();
            if($temp==''){
                break;
            }
        }
        $update['invent_code'] = $invent_code;

        $info = db('member')->insertGetId($update);
        if($info){
            $this->rong_register($info['token']);

            ////生成邀请二维码
            import('qrcode.phpqrcode', EXTEND_PATH);
            $value = ADMIN_SITE_URL. '/invent/invent/'.$info;
            $time = time();
            $file_name = BASE_UPLOAD_PATH."/home/qrcode/".$time."-".$info.".png";
            $errorCorrectionLevel = "L";
            $matrixPointSize = "4";
            \QRcode::png($value, $file_name, $errorCorrectionLevel, $matrixPointSize,2);
            $image =  'http://' . $_SERVER['HTTP_HOST']."/uploads/home/qrcode/".$time."-".$info.".png";
            db('member')->where('member_id',$info)->update(['invent_image'=>$image]);
            $message2['to_member_id'] = $info;
            $info3 = db('article')->where('article_id',42)->value('article_content');
            $info3 =strip_tags(htmlspecialchars_decode($info3));
//            $message2['message_body'] = $info3;
//            $message2['message_title'] = '注册返还优惠券';
//            $message2['message_time'] =time();
//            $message2['message_type'] =4;
//            db('message')->insert($message2);
            $info2 = db('member')->where('member_id',$info)->find();
            $data->token = $info2['token'];
            $data->rong_token = $info2['rong_token'];
            $path['pid'] = 0;
            $path['uid'] = $info;
            $path['path'] = "0,";
            db('path')->insert($path);
            $output = array('data' => $data, 'msg' => '授权成功', 'code' => '200');
            exit(json_encode($output));
        }else{
            $data->token='';
            $output = array('data' => $data, 'msg' => '授权失败', 'code' => '400');
            exit(json_encode($output));
        }
    }

    /////根据用户id获取用户名称和头像
    public  function getusername($id){
        $data = json_decode('{}');
        $member_info = db('member')->where('member_id',$id)->find();
        $data->name = $member_info['member_name'];
        $data->avatar = $member_info['member_avatar'];
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }


    //////积分规则
    public function pointsrule(){
        $list_setting = rkcache('config', true);
        $rule = $list_setting['points_ordermoney'];
        $data = json_decode('{}');
        $data->rule = $rule;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }

    /////版本号

    function edition($type=''){
        $data = json_decode('{}');
        $data->url = 'http://73y4.guangyanborui.cn/uw5iP';
        if($type==1){
            $data->edition = db('config')->where('id',815)->value('value');
        }
        if($type==2){
            $data->edition = db('config')->where('id',816)->value('value');
        }
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }
    //////优惠券列表
    public function voucherList($token='',$type='',$status='',$money=''){

        $data = json_decode('{}');

        if ($token == '' || $type =='' ) {
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $userid = $this->getuserid($token);
        if($userid==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }

        $user = db('member')->where('member_id',$userid)->find();
        $time = time();
        /////订单可用优惠券
        if($type==1){
            if($money ==''){
                $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
                exit(json_encode($output));
            }
            $voucher_arr = $user['voucher_id'];
            $voucher_arr = explode(',',$voucher_arr);
            $list=[];
            foreach($voucher_arr as $k=>$v){
                $info = db('vouchertemplate')->where('vouchertemplate_id',$v)->find();
                if($info !=''){
                    $list2['id'] = $info['vouchertemplate_id'];
                    $list2['image'] = 'http://' . $_SERVER['HTTP_HOST']."/uploads/home/voucher/".$info['vouchertemplate_customimg'];
                    $list2['title'] = $info['vouchertemplate_title'];
                    $list2['money'] = (string)$info['vouchertemplate_price'];
                    $list2['limit'] = (string)$info['vouchertemplate_limit'];
                    $list2['start_time'] = date('Y-m-d',$info['vouchertemplate_startdate']);
                    $list2['end_time'] = date('Y-m-d',$info['vouchertemplate_enddate']);

                    if($info['vouchertemplate_startdate']>$time || $info['vouchertemplate_enddate']<$time || ($info['vouchertemplate_limit'] >$money ) ){
                        unset($list2);
                    }
                    if(isset($list2)){
                        $list[] = $list2;
                    }
                }

            }
            $data->list = $list;
            $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
            exit(json_encode($output));
        }
        /////全部可用优惠券
        if($type==2){
            if($status ==''){
                $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
                exit(json_encode($output));
            }
            $voucher_arr = $user['voucher_id'];
            $voucher_arr = explode(',',$voucher_arr);
            $list=[];
            if($status==1){
                foreach($voucher_arr as $k=>$v){
                    $info = db('vouchertemplate')->where('vouchertemplate_id',$v)->find();
                    if($info !=''){
                        $list2['id'] = $info['vouchertemplate_id'];
                        $list2['image'] = 'http://' . $_SERVER['HTTP_HOST']."/uploads/home/voucher/".$info['vouchertemplate_customimg'];
                        $list2['title'] = $info['vouchertemplate_title'];
                        $list2['limit'] = (string)$info['vouchertemplate_limit'];
                        $list2['money'] = (string)$info['vouchertemplate_price'];
                        $list2['start_time'] = date("Y-m-d",$info['vouchertemplate_startdate']);
                        $list2['end_time'] = date("Y-m-d",$info['vouchertemplate_enddate']);
                        if( $info['vouchertemplate_enddate']<$time){

                            unset($list2);
                        }
                        if(isset($list2)){
                            $list[] = $list2;
                        }
                    }

                }
            }

            if($status==2){
                foreach($voucher_arr as $k=>$v){
                    $info = db('vouchertemplate')->where('vouchertemplate_id',$v)->find();
                    if($info !=''){
                        $list2['id'] = $info['vouchertemplate_id'];
                        $list2['image'] = 'http://' . $_SERVER['HTTP_HOST']."/uploads/home/voucher/".$info['vouchertemplate_customimg'];
                        $list2['title'] = $info['vouchertemplate_title'];
                        $list2['limit'] = (string)$info['vouchertemplate_limit'];
                        $list2['money'] = (string)$info['vouchertemplate_price'];
                        $list2['start_time'] =  date('Y-m-d',$info['vouchertemplate_startdate']);
                        $list2['end_time'] = date('Y-m-d',$info['vouchertemplate_enddate']);
                        if( $info['vouchertemplate_enddate']>$time){
                            unset($list2);
                        }
                        if(isset($list2)){
                            $list[] = $list2;
                        }
                    }

                }
            }

            $data->list = $list;
            $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
            exit(json_encode($output));
        }
    }
    ///组合reciver——info



    /////生成订单号
    public function getOrderSn ()
    {

        //生成24位唯一订单号码，格式：YYYY-MMDD-HHII-SS-NNNN,NNNN-CC，
        //其中：YYYY=年份，MM=月份，DD=日期，HH=24格式小时，II=分，SS=秒，NNNNNNNN=随机数，CC=检查码

        @date_default_timezone_set("PRC");
        while (true) {
            //订购日期
            $order_date = date('Y-m-d');
            //订单号码主体（YYYYMMDDHHIISSNNNNNNNN）
            $order_id_main = date('YmdHis') . rand(10000000, 99999999);
            //订单号码主体长度
            $order_id_len = strlen($order_id_main);
            $order_id_sum = 0;
            for ($i = 0; $i < $order_id_len; $i++) {
                $order_id_sum += (int)(substr($order_id_main, $i, 1));
            }
            //唯一订单号码（YYYYMMDDHHIISSNNNNNNNNCC）
            $order_id = $order_id_main . str_pad((100 - $order_id_sum % 100) % 100, 2, '0', STR_PAD_LEFT);

            return $order_id;
        }
    }



    ////生成token
    public function gettoken(){
        $time = time();
        $rand = rand(10000,99999);
        $token = $time.$rand;
        return $token;
    }
    ////生成token
    public function getuserid($token){
        $userId = db('member')->where('token',$token)->value('member_id');
        $time = time();
        if(empty($userId)){
            return false;
        }else{
            $userinfo =  db('member')->where('token',$token)->find();
            $member_state = $userinfo['member_state'];
            if($userinfo['membervip_endtime']<$time){
                db('member')->where('token',$token)->update(['supply_auth'=>0]);
            }
            if($member_state==0){
                return false;
            }else{
                return $userId;
            }
        }
    }

    ////私钥加密
    public function  rsa($data){
        $rsa = model('Rsa');
        $result = $rsa->privEncrypt($data);
        return $result;
    }
    public function  rsa_decode($data){
        $rsa = model('Rsa');
        $result = $rsa->privDecrypt($data,'');
        return $result;
    }

    /////融云
    function rong_register($token)
    {

        $data = json_decode('{}');
        if ($token == '' ) {
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $userid = $this->getuserid($token);
        if($userid==false){
            $output = array('data' => json_decode('{}'), 'msg' => 'token不存在', 'code' => '501');
            exit(json_encode($output));
        }
        $RongSDK = new RongCloud('82hegw5u8x4ix','XGETxjmV1Og');
        $userinfo = db('member')->where('member_id',$userid)->find();
        if($userinfo['member_avatar']==''){
            $portrait = 'http://47.92.235.179:10004/uploads/home/common/default_user_portrait.gif';
        }else{
            $portrait = $userinfo['member_avatar'];
        }
        $user = [
            'id'=> $userid,
            'name'=> $userinfo['member_name'],//用户名称
            'portrait'=> $portrait //用户头像
        ];
        $register = $RongSDK->getUser()->register($user);
        if($register['code']==200){
            $rong_token = $register['token'];
            db('member')->where('member_id',$userid)->update(['rong_token'=>$rong_token]);
            return json($rong_token);
        }else{
            $output = array('data' => json_decode('{}'), 'msg' => '融云注册失败', 'code' => '400');
            exit(json_encode($output));
        }

    }


    public function message_get($date)
    {
        $RongSDK = new RongCloud('82hegw5u8x4ix', 'XGETxjmV1Og');
        $message = [
            'date' => $date,//日期
        ];
        $Chartromm = $RongSDK->getMessage()->History()->get($message);
        $url = $Chartromm['url'];
        $path = "uploads/home/zip/";
//        $outPath = "/uploads/home/zip/";
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
//        unlink($newtxt);
        $result=array();
        preg_match_all("/(?:\{)(.*)(?:\})/i",$str, $result);
        $arr = $result[0];

    }



    public  function text2($message){
        $model = model('push');
        $a = $model->pushall($message);
    }

    public  function text3($token){
        $model = model('push');
        $a = $model->pushone('1111',$token);
    }

}
