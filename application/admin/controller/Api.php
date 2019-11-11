<?php
namespace app\admin\controller;
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
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
        if (empty($account) || empty($code) || empty($password) ) {
            $output = array('data' => $data, 'msg' => '请输入完整信息', 'code' => '400');
            exit(json_encode($output));
        }

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

//        if ($code== $res['verify_code']) {
        if (1==1) {

            $list['member_password'] = md5(md5($password));
            $list['member_mobile'] = $account;
            $list['member_truename'] = $account;
            $list['member_addtime'] = time();
            $list['member_logintime'] = time();
            $list['member_name'] = "用户".$account;
            $token = $this->gettoken();
            $list['token'] = $token;

            for($i=0;$i<100;$i++){
                $invent_code2 = rand(100000,999999);
                $temp = db('member')->where('invent_code',$invent_code2)->find();
                if($temp==''){
                    break;
                }
            }
            $list['invent_code'] = $invent_code2;

            $id = db('member')->insertGetId($list);

            if ($id){
                $insertid = $id;
                if($insertid){
                    ////生成邀请二维码
                    import('qrcode.phpqrcode', EXTEND_PATH);
                    $value = ADMIN_SITE_URL. '/invent/invent/'.$id;
                    $time = time();
                    $file_name = BASE_UPLOAD_PATH."/home/qrcode/".$time."-".$id.".png";
                    $errorCorrectionLevel = "L";
                    $matrixPointSize = "4";
                    \QRcode::png($value, $file_name, $errorCorrectionLevel, $matrixPointSize,2);
                    $image =  'http://' . $_SERVER['HTTP_HOST']."/uploads/home/qrcode/".$time."-".$id.".png";
                    db('member')->where('member_id',$id)->update(['invent_image'=>$image]);
                    $voucher_list = db('vouchertemplate')->where('invent',1)->select();
                    foreach($voucher_list as $vv){
                        $temp[] = $vv['vouchertemplate_id'];
                    }

                    $str = implode(',',$temp);
                    $path['uid'] = $insertid;
                    $str.=",";
                    if($invent_code !=''){
                        //////发送邀请成功消息
                        $vid = db('member')->where('invent_code',$invent_code)->value('voucher_id');
                        $insertid2 =db('member')->where('invent_code',$invent_code)->value('member_id');
                        $vid .=$str;
                        db('member')->where('invent_code',$invent_code)->update(['voucher_id'=>$vid]);

                        db('member')->where('invent_code',$invent_code)->setInc('invent_num');
                        $message3['to_member_id'] = $insertid2;
                        $info = db('article')->where('article_id',42)->value('article_content');
                        $info =strip_tags(htmlspecialchars_decode($info));
                        $message3['message_body'] = $info;
                        $message3['message_title'] = '您已经成功邀请';
                        $message3['message_time'] =time();
                        $message3['message_type'] =3;
                        db('message')->insert($message3);

                        ////////增加邀请path
                        $path['pid'] = $insertid2;
                        $parent_pathinfo = db('path')->where('uid',$insertid2)->find();
                        $parent_path = $parent_pathinfo['path'];
                        $path['path'] = $parent_path.$insertid2.",";
                        $invent_arr = explode($parent_path.$insertid2,",");
                        $invent_arr = array_reverse($invent_arr);
                        array_pop($invent_arr);
                        if(count($invent_arr)==0){
                            $path['path_1'] = $insertid2;
                        }
                        if(count($invent_arr)==1){
                            $path['path_1'] = $insertid2;
                            $path['path_2'] = $invent_arr[0];
                        }
                        if(count($invent_arr)==2){
                            $path['path_1'] = $insertid2;
                            $path['path_2'] = $invent_arr[0];
                            $path['path_3'] = $invent_arr[1];
                        }



                    }else{
                        $path['pid'] = 0;
                        $path['path'] ="0,";
                    }
                    db('path')->insert($path);
                    $message2['to_member_id'] = $insertid;
                    $info = db('article')->where('article_id',42)->value('article_content');
                    $info =strip_tags(htmlspecialchars_decode($info));
                    $message2['message_body'] = $info;
                    $message2['message_title'] = '注册返还优惠券';
                    $message2['message_time'] =time();
                    $message2['message_type'] =4;
                    db('message')->insert($message2);
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

            $where['member_mobile'] = $account;
            $where['member_password'] = md5(md5($password));

            $res = db('member')->where($where)->find();

            if ($res){
                $time = time();
                db('member')->where($where)->update(['member_logintime'=>$time]);
                if($res['member_logintime'] !=0){
                    db('member')->where($where)->update(['member_isfirst'=>1]);
                }
                $data->mobile = (string)$res['member_mobile'];
                $data->token = (string)$res['token'];

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

    ///////团队人数
    public function invent_list($token=''){
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
        $list = db('path')->where('pid',$userid)->select();
        $temp =[];
        foreach($list as $k=>$v){
            $path1_id = $v['uid'];
            $count+=1;
            $arr = [];
            $inventuser = db('member')->where('member_id',$path1_id)->find();
            $arr['name'] = $inventuser['member_name'];
            $addtime = $inventuser['member_addtime'];

            $arr['time'] = date("Y-m-d",$addtime);
            $arr['level'] = 1;
            $temp[] = $arr;
            $list2 = db('path')->where('pid',$path1_id)->select();
            if(!empty($list2)){
                foreach($list2 as $vv){
                    $path2_id = $vv['uid'];
                    $count+=1;
                    $arr = [];
                    $inventuser = db('member')->where('member_id',$path2_id)->find();
                    $arr['name'] = $inventuser['member_name'];
                    $addtime = $inventuser['member_addtime'];
                    $arr['time'] = date("Y-m-d",$addtime);
                    $arr['level'] = 2;
                    $temp[] = $arr;
                    $list3 = db('path')->where('pid',$path2_id)->select();
                    if(!empty($list3)){
                        foreach($list3 as $vvv){

                            $path3_id = $vvv['uid'];
                            $count+=1;
                            $arr = [];
                            $inventuser = db('member')->where('member_id',$path3_id)->find();
                            $arr['name'] = $inventuser['member_name'];
                            $addtime = $inventuser['member_addtime'];
                            $arr['time'] = date("Y-m-d",$addtime);
                            $arr['level'] = 3;
                            $temp[] = $arr;



                        }
                    }
                }
            }
        }
        $data->count = $count;
        $data->list = $temp;
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
        AlibabaCloud::accessKeyClient('LTAI4FtzMwEccWgesgiEVLpS', 'kaqELCx8qwhh1ncFynNz8V5RUgkBW7')
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
                        'SignName' => "中原再生",
                        'TemplateCode' => "SMS_174985705",
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
    
    ////首页轮播图
    public function adlist(){
    	$data = json_decode('{}');
    	$list1 = [];
    	$where1['ap_id'] = 1;
       
        $adarr = db('adv')->where($where1)->select();
        foreach ($adarr as $key1 => $v1) {
            $arr = json_decode('{}');

            $image = 'http://' . $_SERVER['HTTP_HOST'] . '/uploads/' . ATTACH_ADV  .'/'. $v1['adv_code'];
            $arr->imgUrl = $image;
            $arr->goodsId = $v1['adv_goodsid'];
            $list1[] = $arr;
        }

        $data->adList = $list1;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }
    ////首页商品分类列表
    public function homeGoodsClass(){
    	$data = json_decode('{}');
    	$list1 = [];

    	$res = db('goodsclass')->where('gc_parent_id',0)->where('is_homepage',1)->order('gc_id')->limit(8)->select();
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
            $img2 = substr($img,24);
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
    public function messageList($token=''){
    	
    	$userId = $this->getuserid($token);
    	$data = json_decode('{}');
        $list1 =db('message')->where('message_type',1)->order('message_time DESC')->select();
       
        $list2 =db('message')->where('message_type',2)->where('to_member_id',$userId)->order('message_time DESC')->select();
       
        $list3 =db('message')->where('message_type',3)->where('to_member_id',$userId)->order('message_time DESC')->select();
        $list4 =db('message')->where('message_type',4)->where('to_member_id',$userId)->order('message_time DESC')->select();
        $count =0;
        
       
       
       $systemList = [];
       $count =0;
        foreach($list1 as $v){
        	$arr = json_decode('{}');   
        	 	 
        	if(strpos($v['read_member_id'],$userId.",") === false){
        		
        		if($count==0){
        		$arr->message = $v['message_body'];	
        		$arr->time = date("Y-m-d",$v['message_time']);	
        		
        		}
        		$count+=1;
        	}	       	
        }
        $message = db('message')->where('message_type',1)->order('message_time desc')->find();
        $message2 = db('message')->where('message_type',2)->where('to_member_id',$userId)->order('message_time desc')->find();
        $message3 = db('message')->where('message_type',3)->where('to_member_id',$userId)->order('message_time desc')->find();
        $message4 = db('message')->where('message_type',4)->order('message_time desc')->find();


        $read_id = $message['read_member_id'];
        $read_id2 = $message2['read_member_id'];
        $read_id3 = $message3['read_member_id'];
        $read_id4 = $message4['read_member_id'];

        $systemList=[];
        $count = 0;
        if($message){

            foreach($list1 as $v){
                $arr = json_decode('{}');

                if(strpos($v['read_member_id'],$userId.",") === false){

                    if($count==0){
                        $arr->message = $v['message_body'];

                        $arr->time = '';

                    }
                    $count+=1;
                }
            }
            if(strpos($read_id,$userId.",") === false){
                $systemList['is_read'] = '0';
            }else{
                $systemList['is_read'] = '1';
            }
            $systemList['info']=$message['message_body'];
            $systemList['time']=date("Y-m-d",$message['message_time']);
            $systemList['count']=$count;
            $systemList['type']=0;
            $data->systemList = $systemList;
        }else{
            $systemList['info']='';
            $systemList['time']='';
            $systemList['count']=0;
            $systemList['is_read'] ='';
            $systemList['type']=1;
            $systemList['title']='';
            $data->systemList = $systemList;
        }
        
        $transferList=[];
        $count = 0;
        if($message2){

        foreach($list2 as $v){
        	$arr = json_decode('{}');

        	if(strpos($v['read_member_id'],$userId.",") === false){

        		if($count==0){
        		$arr->message = $v['message_body'];	
        		$arr->time = '';
        		
        		}
        		$count+=1;
        	}	       	
        }
            if(strpos($read_id2,$userId.",") === false){
                $transferList['is_read'] = '0';
            }else{
                $transferList['is_read'] = '1';
            }
        $transferList['info']=$message2['message_body'];
        $transferList['time']=date("Y-m-d",$message2['message_time']);
        $transferList['count']=$count;
        $transferList['type']=1;
        $data->transferList = $transferList;
        }else{
            $transferList['info']='';
            $transferList['time']='';
            $transferList['count']=0;
            $transferList['is_read'] ='';
            $transferList['type']=1;
            $transferList['title']='';
        $data->transferList = $transferList;
        }




        $inventList=[];
        $count = 0;
        if($message3){

            foreach($list3 as $v){
                $arr = json_decode('{}');

                if(strpos($v['read_member_id'],$userId.",") === false){

                    if($count==0){
                        $arr->message = $v['message_body'];
                        $arr->time = '';

                    }
                    $count+=1;
                }
            }
            if(strpos($read_id3,$userId.",") === false){
                $inventList['is_read'] = '0';
            }else{
                $inventList['is_read'] = '1';
            }
            $inventList['title']=$message3['message_title'];
            $inventList['info']=$message3['message_body'];
            $inventList['time']=date("Y-m-d",$message3['message_time']);
            $inventList['count']=$count;
            $inventList['type']=2;
            $data->inventList = $inventList;
        }else{
            $inventList['info']='';
            $inventList['time']='';
            $inventList['count']=0;
            $inventList['is_read'] ='';
            $inventList['type']=2;
            $inventList['title']='';
            $data->inventList = $inventList;
        }

        $couponList=[];
        $count = 0;
        if($message4){

            foreach($list4 as $v){
                $arr = json_decode('{}');

                if(strpos($v['read_member_id'],$userId.",") === false){

                    if($count==0){
                        $arr->message = $v['message_body'];
                        $arr->time = '';

                    }
                    $count+=1;
                }
            }
            if(strpos($read_id4,$userId.",") === false){
                $couponList['is_read'] = '0';
            }else{
                $couponList['is_read'] = '1';
            }
            $couponList['info']=$message4['message_body'];
            $couponList['title']=$message4['message_title'];
            $couponList['time']=date("Y-m-d",$message4['message_time']);
            $couponList['count']=$count;
            $couponList['type']=3;
            $data->couponList = $couponList;
        }else{
            $couponList['info']='';
            $couponList['time']='';
            $couponList['count']=0;
            $couponList['is_read'] ='';
            $couponList['type']=3;
            $couponList['title']='';
            $data->couponList = $couponList;
        }

        
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
    public function messageDetail($token='',$type='',$page=''){
    	
    	$time = time();
    	$data = json_decode('{}');
    	if (empty($token) || $type=='' || $page=='') {
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $userId = $this->getuserid($token);
        $page-=1;
    	if($type==0){
    		$res =db('message')->where('message_type',1)->limit($page*20,20)->order('message_time DESC')->select();
    		
    		$count = db('message')->where('message_type',1)->count();
        	$totalpage = ceil($count/20);
        	$data->totalpage = $totalpage;
        	$list=[];
        foreach($res as $v){
        	
        		$arr = json_decode('{}');        	 
        
        		
        		$arr->message = $v['message_body'];
        		
        		$arr->time = date("Y-m-d",$v['message_time']);
        		$arr->title = $v['message_title'];	
        		$str = $v['read_member_id'];
               if(strpos($v['read_member_id'],$userId.",") === false){

                   $str2 = $str."$userId".",";
                   db('message')->where('message_id',$v['message_id'])->update(['read_member_id'=>$str2]);
               }

        	    $list[] = $arr;
        
        	
        	     	
        }
        $data->list = $list;       
    	}

    	if($type==1){
    		
    		$res =db('message')->where('message_type',2)->where('to_member_id',$userId)->limit($page*20,20)->order('message_time DESC')->select();
    		
    		$count = db('message')->where('message_type',2)->where('to_member_id',$userId)->count();

        	$totalpage = ceil($count/20);
        	$data->totalpage = $totalpage;
        	$list=[];

        foreach($res as $v){
        	
        		$arr = json_decode('{}');        	 
        
        		
        		$arr->message = $v['message_body'];	
        		$goods_id = $v['goods_id'];
        		$goods_info = db('goods')->where('goods_id',$goods_id)->find();
        		$arr->goods_name = $goods_info['goods_name'];
        		$arr->order_sn = $v['order_sn'];
        		$arr->goods_image = 'http://' . $_SERVER['HTTP_HOST']."/uploads/home/store/goods/".$goods_info['goods_image'];
        		$arr->time = date("Y-m-d",$v['message_time']);
        		$arr->title = $v['message_title'];	
        		$str = $v['read_member_id'];

        		if(strpos($str,"$userId".",") === false){

        		$str2 = $str."$userId".",";
        		db('message')->where('message_id',$v['message_id'])->update(['read_member_id'=>$str2]);
        		}
        	     $list[] = $arr;
        	   
        	   
        
        	
        	     	
        }
        $data->list = $list;       
    	}

        if($type==2){
            $res =db('message')->where('message_type',3)->where('to_member_id',$userId)->limit($page*20,20)->order('message_time DESC')->select();

            $count = db('message')->where('message_type',3)->where('to_member_id',$userId)->count();
            $totalpage = ceil($count/20);
            $data->totalpage = $totalpage;
            $list=[];
            foreach($res as $v){

                $arr = json_decode('{}');


                $arr->message = $v['message_body'];

                $arr->time = date("Y-m-d",$v['message_time']);
                $arr->title = $v['message_title'];
                $str = $v['read_member_id'];
                if(strpos($v['read_member_id'],$userId.",") === false) {
                    $str2 = $str . "$userId" . ",";
                    db('message')->where('message_id', $v['message_id'])->update(['read_member_id' => $str2]);
                }
                $list[] = $arr;
            }
            $data->list = $list;
        }

        if($type==3){
            $res =db('message')->where('message_type',4)->where('to_member_id',$userId)->limit($page*20,20)->order('message_time DESC')->select();

            $count = db('message')->where('message_type',4)->where('to_member_id',$userId)->count();
            $totalpage = ceil($count/20);
            $data->totalpage = $totalpage;
            $list=[];
            foreach($res as $v){

                $arr = json_decode('{}');


                $arr->message = $v['message_body'];

                $arr->time = date("Y-m-d",$v['message_time']);
                $arr->title = $v['message_title'];
                $str = $v['read_member_id'];
                if(strpos($v['read_member_id'],$userId.",") === false) {
                    $str2 = $str . "$userId" . ",";
                    db('message')->where('message_id', $v['message_id'])->update(['read_member_id' => $str2]);
                }
                $list[] = $arr;
            }
            $data->list = $list;
        }
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
    	$res2 = db('goodsclass')->where('gc_id',133)->find();
          $arr = json_decode('{}');
          $arr->Id = $res2['gc_id'];
          $arr->name = $res2['gc_name'];
          $list2 =$arr;
          array_unshift($list,$list2);
          $data->list = $list;
          $image = db('adv')->where('ap_id',2)->value('adv_code');
    	  $data->image = 'http://'.$_SERVER['HTTP_HOST']."/uploads/home/adv/".$image;

    	
    	
    	$output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }

    /////获取全部二级列表接口
    public function secondClassList($classId,$page=''){

        $data = json_decode('{}');
        $list = [];
        $image='';
        $page -=1;
        $res = db('goodsclass')->where('gc_parent_id',$classId)->order('gc_id')->limit($page*20,20)->select();
        foreach($res as $v){
            $arr = json_decode('{}');
            $arr->Id = $v['gc_id'];
            $arr->name = $v['gc_name'];
            $image = $v['pic'];
            $image2 = substr($image,40);

            $arr->image = 'http://'.$_SERVER['HTTP_HOST']."/uploads/home".$image2;
            $list[] =$arr;
        }

        $data->list = $list;

        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }
    
   /// 获取下级商品列表
    public function getGoodsList($classId='',$page='',$sale='',$price='',$search=''){
    	$data = json_decode('{}');
    	$page -=1;
    	if (  $page===''){
    		
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $where['goods_state']= 1;
        $list=[];


        if($classId !=''){

            $order='';

            /////判断该商品是否为套餐商品
            $parent_class = db('goodsclass')->where('gc_id', $classId)->value('gc_parent_id');
            if ($parent_class == 133) {
                $order='bl_id DESC';
                if ($sale == 1) {
                    $order = "goods_salenum DESC";
                }
                if ($sale == 2) {
                    $order = "goods_salenum ASC";
                }
                if ($price == 1) {
                    $order = "bl_discount_price DESC";
                }
                if ($price == 2) {
                    $order = "bl_discount_price ASC";
                }
                if ($search != '') {
                    $where['bl_name'] = array('like', '%' . $search . '%');
                }
                $res = db('pbundling')->where($where)->where('classid', $classId)->order($order)->limit($page * 20, 20)->select();
                $count = db('pbundling')->where('classid', $classId)->count();
                $totalpage = ceil($count / 20);
                foreach ($res as $v) {
                    $goods_list = db('pbundlinggoods')->where('bl_id',$v['bl_id'])->select();
                    foreach($goods_list as $kk=>$vv){
                        $goods_info = db('goods')->where('goods_id',$vv['goods_id'])->find();
                        if(empty($goods_info)){
                            $count-=1;
                            continue 2;
                        }
                    }
                    $arr = json_decode('{}');
                    $arr->image = 'http://' . $_SERVER['HTTP_HOST'] . "/uploads/home/store/goods/" . $v['image'];
                    $arr->name = $v['bl_name'];
                    $arr->id = $v['bl_id'];
                    $arr->market_price = '';
                    $arr->price = (string)$v['bl_discount_price'];
                    $arr->tag = 2;
                    $list[] = $arr;
                }
            } else {
                $order='goods_addtime DESC';
                $where['gc_id'] = $classId;
                if ($sale == 1) {
                    $order = "goods_salenum DESC";
                }
                if ($sale == 2) {
                    $order = "goods_salenum ASC";
                }
                if ($price == 1) {
                    $order = "goods_price DESC";
                }
                if ($price == 2) {
                    $order = "goods_price ASC";
                }
                if ($search != '') {
                    $where['goods_name'] = array('like', '%' . $search . '%');
                }
                $res = db('goods')->where($where)->order($order)->limit($page * 20, 20)->select();

                $count = db('goods')->where($where)->count();
                $totalpage = ceil($count / 20);
                foreach ($res as $v) {
                    $arr = json_decode('{}');
                    $arr->image = 'http://' . $_SERVER['HTTP_HOST'] . "/uploads/home/store/goods/" . $v['goods_image'];
                    $arr->name = $v['goods_name'];
                    $arr->id = $v['goods_id'];
                    $arr->market_price = (string)$v['goods_marketprice'];
                    $arr->price = (string)$v['goods_price'];
                    $standardslist = db('goods_standards')->where('goods_id',$v['goods_id'])->select();
                    $arr->standards = $standardslist;
                    $arr->tag = 1;
                    $list[] = $arr;
                }
            }
        }else{
            $where1='';
            $where2 = '';
            $order2 = 'bl_id DESC';
            $order1 = 'goods_addtime DESC';
            if ($sale == 1) {
                $order1 = "goods_salenum DESC";
                $order2 = "goods_salenum DESC";
            }
            if ($sale == 2) {
                $order1 = "goods_salenum ASC";
                $order2 = "goods_salenum ASC";
            }
            if ($price == 1) {
                $order1 = "goods_price DESC";
                $order2 = "bl_discount_price DESC";
            }
            if ($price == 2) {
                $order1 = "goods_price ASC";
                $order2 = "bl_discount_price ASC";
            }
            if ($search != '') {
                $where1['bl_name'] = array('like', '%' . $search . '%');
                $where2['goods_name'] = array('like', '%' . $search . '%');
            }
            ////套餐列表

            $res1 =  db('pbundling')->where($where1)->order($order2)->select();
            $res2 =  db('goods')->where($where2)->where($where)->order($order1)->select();

                foreach($res1 as $k=>$v){
                    $flag=0;
                $goods_list = db('pbundlinggoods')->where('bl_id',$v['bl_id'])->select();
                foreach($goods_list as $kk=>$vv){
                    $goods_info = db('goods')->where('goods_id',$vv['goods_id'])->find();

                    if($goods_info==''){
                        $flag=1;
                        break;
                    }
                }
                if($flag==1){
                    continue;
                }
                $arr = json_decode('{}');
                $arr->image = 'http://' . $_SERVER['HTTP_HOST'] . "/uploads/home/store/goods/" . $v['image'];
                $arr->name = $v['bl_name'];
                $arr->id = $v['bl_id'];
                $arr->market_price = '';
                $arr->price = $v['bl_discount_price'];
                $arr->tag = 2;

                $list[] = $arr;
            }

            foreach ($res2 as $v) {
                $arr = json_decode('{}');
                $arr->image = 'http://' . $_SERVER['HTTP_HOST'] . "/uploads/home/store/goods/" . $v['goods_image'];
                $arr->name = $v['goods_name'];
                $arr->id = $v['goods_id'];
                $arr->market_price = (string)$v['goods_marketprice'];
                $arr->price = (string)$v['goods_price'];
                $standardslist = db('goods_standards')->where('goods_id',$v['goods_id'])->select();
                $arr->standards = $standardslist;
                $arr->tag = 1;
                $list2[] = $arr;
            }

            if(count($res1)==0 && count($res2)!=0){
                $list = $list2;
            }else if(count($res2)==0 && count($res1)!=0){
                $list = $list;
            }else if(count($res2)==0 && count($res1)==0){
                $list=[];
            }else{
                $list = array_merge($list,$list2);
            }

            $list = array_slice($list,$page*20,20);

        }
        $count = count($list);
        $totalpage = ceil($count/20);
        $data->totalpage = $totalpage;




        $data->list = $list;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }
    /////商品详情
    public function getgoodsInfo($goodsId='',$token=''){
    	$data = json_decode('{}');
    	if ($goodsId=='' ){    		
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }

            $goods_info = db('goods')->where('goods_id',$goodsId)->find();

            $common_info = db('goodscommon')->where('goods_commonid',$goodsId)->find();
            $list=[];
            $list['goods_name'] = $goods_info['goods_name'];
            $goods_image =explode(',',$goods_info['goods_image']);

            foreach($goods_image as $k=>$v){

                $goods_image[$k] = 'http://' . $_SERVER['HTTP_HOST']."/uploads/home/store/goods/".$v;
            }
            $groupbuy = db('groupbuy')->where('goods_id',$goodsId)->find();
            if($groupbuy !=''){
                $list['goods_price'] =$groupbuy['groupbuy_price'];
                $list['market_price'] =$groupbuy['goods_price'];
            }else{
                $list['goods_price'] =$common_info['goods_price'];
                $list['market_price'] =$common_info['goods_marketprice'];
            }



            $list['transport'] = $goods_info['goods_freight'];
            $list['salenum'] = $goods_info['goods_salenum'];

            $list['standards'] = db('goods_standards')->where('goods_id',$goodsId)->select();
            $list['intro'] = $goods_info['goods_advword'];
            $describe = db('goodscommon')->where('goods_commonid',$goodsId)->value('mobile_body');
            $goods_body = db('goodscommon')->where('goods_commonid',$goodsId)->value('goods_body');
            $list['describe'] = strip_tags(htmlspecialchars_decode($goods_body));
            $list['brand'] = $goods_info['brand_name'];
            $list['factory_name'] = $goods_info['factory_name'];
            $list['factory_address'] = $goods_info['factory_address'];
            $list['exper_date'] = $goods_info['exper_date'];
            $list['start_place'] = $goods_info['start_place'];

            if($token!=''){
                $userId = $this->getuserid($token);
                $is_collect = db('collect')->where(['user_id'=>$userId,'goods_id'=>$goodsId])->find();
                if($is_collect !=''){
                    $list['is_collect']='1';
                }else{
                    $list['is_collect']='0';
                }
            }else{

                $list['is_collect']='';
            }

            $list['art'] = $goods_info['art'];
            $list['phone'] = db('config')->where('id',2)->value('value');
            $intro_image = db('goodsimages')->where('goods_commonid',$goodsId)->select();
            $list2=[];
            foreach($intro_image as $k=>$v){
                $image = $v['goodsimage_url'];
                $image2 = 'http://' . $_SERVER['HTTP_HOST']."/uploads/home/store/goods/".$image;
                if($v['goodsimage_isdefault']==1){
                    array_unshift($list2,$image2);
                }else{
                    $list2[] = $image2;
                }

            }

            $list['goods_image'] = $list2;

            if($describe !=''){
                $mobile_body = unserialize($describe);
                $mobile_body = json_encode($mobile_body);
                $mobile_body = str_replace('&quot;', '"', $mobile_body);

                $mobile_body = json_decode($mobile_body);
                foreach($mobile_body as $v){
                    unset($v->type);
                    $intro_image2[] = $v->value;
                }
                $list['intro_image'] = $intro_image2;
            }else{
                $list['intro_image'] =[];
            }



            $list['packet'] = $goods_info['packet'];
            $list['sell'] = $goods_info['sell'];
            $list['samecity'] = $goods_info['samecity'];
            if($goods_info['score']!=0){
                $list['is_score'] = '1';
            }else{
                $list['is_score'] = '0';
            }
            $tmp2 = db('pbundlinggoods')->where('goods_id',$goodsId)->select();
            $introList=[];
            foreach($tmp2 as $k=> $v){
                $introList[$k]['bl_id'] = $v['bl_id'];
                $pbundling_info = db('pbundling')->where('bl_id',$v['bl_id'])->find();
                $introList[$k]['image'] = 'http://' . $_SERVER['HTTP_HOST'] . "/uploads/home/store/goods/" . $pbundling_info['image'];
                $introList[$k]['name'] = $pbundling_info['bl_name'];
            }
            $list['introlist'] = $introList;
            $data->list = $list;
            $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
            exit(json_encode($output));


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
        $cart_count='';
        if($token !=''){
            $cart_list = db('cart')->where('buyer_id',$userId)->select();
            $cart_count = 0;
            if(count($cart_list) !=0){
                foreach($cart_list as $v){
                    $cart_count += $v['goods_num'];
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
            if($goodsinfo !='' && $goods_state ==1 && $standards_info['goods_storage'] !=0){
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
                 $lis3[] = $arr;
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
    	
        
        $res = db('area')->where('area_parent_id',0)->field('area_id,area_name')->select();
        foreach($res as $k=>$v){
            $area_id = $v['area_id'];
            $list2 =  db('area')->where('area_parent_id',$area_id)->select();
            
           
            foreach($list2 as $kk=>$vv){
            	$res[$k]['list2'][$kk]['area_id'] = $vv['area_id'];
            	$res[$k]['list2'][$kk]['area_name'] = $vv['area_name'];
            	$area_id_2 = $vv['area_id'];
            	$list3 =  db('area')->where('area_parent_id',$area_id_2)->field('area_id,area_name')->select();
            	foreach($list3 as $kkk=>$vvv){
            		$res[$k]['list2'][$kk]['list3'][$kkk]['area_id'] = $vvv['area_id'];
            		$res[$k]['list2'][$kk]['list3'][$kkk]['area_name'] = $vvv['area_name'];
            	}
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
    public function advice($token='',$info=''){
    	$data = json_decode('{}');
    	if ( $token=='' || $info==''){
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
    	$userId = $this->getuserid($token);
    	$insert['member_id'] = $userId;
    	$insert['mallconsult_content'] = $info;
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
    		$info = db('article')->where('article_id',6)->value('article_content');
    		$data->info = htmlspecialchars_decode($info);
    	}
    	if($type==2){
    		$info = db('article')->where('article_id',7)->value('article_content');
    		$data->info =strip_tags(htmlspecialchars_decode($info));
    	}
    	if($type==3){
    		$info = db('article')->where('article_id',22)->value('article_content');
    		$data->info =htmlspecialchars_decode($info);
    	}
    	if($type==4){
    		$info = db('article')->where('article_id',40)->value('article_content');
    		$data->info =htmlspecialchars_decode($info);
    	}
    	
    	$output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }
    
     
     /////获取个人信息
   public function userInfo($token=''){
    	$data = json_decode('{}');
    	if ( $token=='' ){
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
    	$userId = $this->getuserid($token);
    	$info = db('member')->where('member_id',$userId)->find();
    	$data->avatar = $info['member_avatar'];
    	if($info['member_name']){
    		$data->nickname = $info['member_name'];
    	}else{
    		$data->nickname = "";
    	}
    	$data->invent_code = $info['invent_code'];
        $data->invent_image = $info['invent_image'];
        $data->invent_num = $info['invent_num'];
    	$data->mobile = $info['member_mobile'];
    	if($info['inviter_id']!=''){
            $data->is_invent = '1';
        }else{
            $data->is_invent = '0';
        }
    	$output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }
    
   
    ////修改个人信息
   public function changeuserInfo($token='',$avatar='',$nickname='',$mobile=''){
    	$data = json_decode('{}');
    	$userId = $this->getuserid($token);
    	
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
    	if($nickname !=''){
    		$update['member_name'] = $nickname;
    		
    	}
    	
    	if($mobile !=''){
    		$update['member_mobile'] = $mobile;
    		
    	}

    	$id = db('member')->where('member_id',$userId)->update($update);
    	if($id){
    		$output = array('data' => $data, 'msg' => '修改成功', 'code' => '200');
            exit(json_encode($output));
    	}else{
    		$output = array('data' => $data, 'msg' => '修改失败', 'code' => '400');
            exit(json_encode($output));
    	}
    	
    }
    
    //////添加收藏
   public function addCollect($token='',$goodsId=''){
    	$data = json_decode('{}');
    	if ($goodsId=='' || $token=='' ){
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
       $userId = $this->getuserid($token);
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
    
    ////取消收藏
   public function deleteCollect($goodsId='',$token=''){
    	$data = json_decode('{}');
    	if ($goodsId=='' || $token==''){
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $userid = $this->getuserid($token);
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
   public function getCollectList($token,$page=''){
    	$data = json_decode('{}');
    	$list = [];
    	if ( $token=='' ||$page==''){
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $userId = $this->getuserid($token);
        $page-=1;
        $res = db('collect')->where('user_id',$userId)->limit($page*20,20)->order('')->select();
        $count = db('collect')->where('user_id',$userId)->count();
        $totalpage = ceil($count/20);
        $data->totalpage = $totalpage;
        foreach($res as $v){
        	$arr = json_decode('{}');
        	$goods_id = $v['goods_id'];
        	$arr->goods_id = $goods_id;
        	
        	$goodsinfo = db('goods')->where('goods_id',$goods_id)->find();
        	if($goodsinfo !=''){
                $arr->image = 'http://' . $_SERVER['HTTP_HOST']."/uploads/home/store/goods/".$goodsinfo['goods_image'];
                $arr->price = $goodsinfo['goods_price'];
                $arr->advword = $goodsinfo['goods_advword'];
                $arr->name = $goodsinfo['goods_name'];
                $list[] = $arr;
            }

        }
        $data->list = $list;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }
    

    

    
    /** 生成订单 **/
   ////json格式 key goodsid  value num
   public function createOrder ($token = '', $addressId = '', $json='', $total = '', $mark='', $arrive_time, $voucherId='',$cartId='')
    {   
       
        $data = json_decode('{}');
        if ($token == '' || $addressId == '' || $json=='' ||  $total == '') {
            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        $time =time();
        $userId = $this->getuserid($token);
        $userInfo = db('member')->where('member_id',$userId)->find();
        $json2 =str_replace("&quot;","\"",$json);



        $price=0;
        $goods_arr = json_decode($json2);
        $transport = 0;
        foreach($goods_arr as $v){
            $goodsid = $v->goodsid;
            $num= $v->num;
            $tag = $v->tag;
            if($tag==1){
                $temp = db('goodscommon')->where('goods_commonid',$goodsid)->value('goods_state');

                $goods_freight = db('goodscommon')->where('goods_commonid',$goodsid)->value('goods_freight');
                $goods_name = db('goodscommon')->where('goods_commonid',$goodsid)->value('goods_name');
                if($temp !=1){
                    $output = array('data' => $data, 'msg' => '商品已下架', 'code' => '400');
                    exit(json_encode($output));
                }
                $standards = $v->standards_id;
                $standards_price =db('goods_standards')->where('id',$standards)->value('goods_price');
                $goods_storage = db('goods_standards')->where('id',$standards)->value('goods_storage');
                if(($goods_storage-$num)<0 ){
                    $output = array('data' => $data, 'msg' => '商品没有库存，'.$goods_name.'无法购买', 'code' => '400');
                    exit(json_encode($output));
                }
                if($goods_freight>$transport){
                    $transport = $goods_freight;
                }
                $price += $standards_price*($v->num);
            }
            if($tag==2){
                $temp = db('pbundling')->where('bl_id',$goodsid)->find();
                $make_arr = $v->make;

                foreach($make_arr as $vv){
                    if($vv->is_make !=0){
                        $price += $vv->make_price;
                    }
                }
                if(empty($temp)){
                    $output = array('data' => $data, 'msg' => '菜单已下架', 'code' => '400');
                    exit(json_encode($output));
                }
                if($temp['bl_freight']>$transport){
                    $transport = $temp['bl_freight'];
                }
                $price += $temp['bl_discount_price']*($v->num);
            }


        }

        $price2 = $price+$transport;
        $voucher_money=0;
        if($voucherId !=''){
            //////去掉用户优惠券
            $voucher_arr = explode(',',$userInfo['voucher_id']);
            foreach($voucher_arr as $kk=>$vv){
                if($vv == $voucherId || $vv==''){
                    unset($voucher_arr[$kk]);
                    break;
                }
            }
            $voucher_arr = array_filter($voucher_arr);
            $voucher_str = implode(',',$voucher_arr);

            $voucher_str.=",";
            db('member')->where('member_id',$userId)->update(['voucher_id'=>$voucher_str]);
        	$voucher_money = db('vouchertemplate')->where('vouchertemplate_id',$voucherId)->value('vouchertemplate_price');
        	$price2 -=$voucher_money;
        }
         
        //添加未支付订单 order表
        $order_sn = $this->getOrderSn();
        $insert['order_sn'] = $order_sn;//订单号

        $insert['buyer_id'] = $userId;//购买人Id
        $insert['add_time'] = $time;
        $insert['order_state'] = '10';
        $insert['address_id'] = $addressId;
        $insert['order_voucherid'] = $voucherId;
        $insert['order_amount'] = $price2;
        $insert['mark'] = $mark;
        $insert['arrive_time'] = $arrive_time;
        $insert['arrive_time'] = $price2;
        $id = db('order')->insertGetId($insert);
        $insert2['order_id'] = $id;
        $insert2['voucher_price'] = $voucher_money;
        $address_info = db('address')->where('address_id',$addressId)->find();
        $insert2['reciver_name'] = $address_info['address_realname'];
        $insert2['reciver_province_id'] = $address_info['area_id'];
        $insert2['reciver_city_id'] = $address_info['city_id'];
        $insert2['reciver_city_id'] = $address_info['city_id'];
        $area = db('area')->where('area_id',$address_info['area_id'])->value('area_name');
        $city = db('area')->where('area_id',$address_info['city_id'])->value('area_name');

         $reciver_info = array(
            'address' => $area . ' ' . $city,
            'phone' => trim($address_info['address_mob_phone'] . ',' . '', ','),
            'area' => '',
            'street' => $address_info['district_id'],
            'mob_phone' => $address_info['address_mob_phone'],
            'tel_phone' => '',
            'dlyp' => 'N',
        );
        $info2 = serialize($reciver_info);
        $insert2['reciver_info'] = $info2;
        db('ordercommon')->insert($insert2);

        foreach($goods_arr as $k=>$v){
        	$insert['goods_id'] = $v->goodsid;
        	if($v->tag==1){
                $standards_info = db('goods_standards')->where('id',$v->standards_id)->find();
                $goods = db('goods')->where('goods_id',$v->goodsid)->find();
                $insert['goods_num'] = $v->num;
                unset($insert['order_voucherid']);
                unset($insert['address_id']);
                unset($insert['add_time']);
                unset($insert['mark']);
                unset($insert['arrive_time']);
                unset($insert['order_state']);
                unset($insert['order_amount']);
                unset($insert['order_sn']);
                $insert['order_id'] = $id;
                $insert['goods_name'] = $goods['goods_name'];
                $insert['goods_price'] = $standards_info['goods_marketprice'];
                $insert['goods_pay_price'] = $standards_info['goods_price'];
                $insert['goods_image'] = $goods['goods_image'];
                $insert['standards_id'] = $v->standards_id;
                $insert['goods_type'] =1;

                $id2 = db('ordergoods')->insert($insert);
            }else{

               $make_arr = $v->make;

               foreach($make_arr as $kk=> $vv){

                   $goods_info = db('pbundlinggoods')->where('goods_id',$vv->foodid)->where('bl_id',$v->goodsid)->find();

                   $insert3['order_id']=$id;
                   $insert3['goods_id']=$vv->foodid;
                   $insert3['goods_type'] =4;
                   $insert3['goods_name'] =$goods_info['goods_name'];
                   $insert3['goods_image'] =$goods_info['goods_image'];
                   $insert3['goods_price'] = $goods_info['blgoods_price'];

                   if($make_arr[$kk]->is_make ==1){

                       $insert3['goods_pay_price'] = $goods_info['blgoods_price']+$make_arr[$kk]->make_price;
                       $insert3['make_price'] = $make_arr[$kk]->make_price;
                   }else{
                       $insert3['goods_pay_price'] = $goods_info['blgoods_price'];
                   }

                   $insert3['buyer_id'] =$userId;
                   $insert3['promotions_id'] =$goods_info['bl_id'];
                   $insert3['goods_image'] =$goods_info['goods_image'];
                   $insert3['goods_num'] =1;

                   $id2 = db('ordergoods')->insert($insert3);
                   unset($insert3);
               }




            }
        	if(empty($id2)){
        		$output = array('data' => json_decode('{}'), 'msg' => '添加失败', 'code' => '402');
                exit(json_encode($output));
        	}
        }
        if($cartId !=''){
            $cartlist = explode(',',$cartId);
            foreach($cartlist as $v){
                db('cart')->where('cart_id',$v)->delete();
            }
        }
        $data->paystate = '0';
        $data->order_sn = $order_sn;
        $data->orderid = $id;
        $output = array('data' => $data, 'msg' => '添加成功', 'code' => '200');
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
            $goods_price = 0;
            $transport = 0;
            $transport_sn = 0;
            $goods_count = 0;
            $list2=[];
            if(count($goods_list) !=0){
                foreach($goods_list as $kk=>$vv){
                    $goods_info = db('goods')->where('goods_id',$goods_list[$kk]['goods_id'])->find();
                    $arr2 = json_decode('{}');
                    $arr2->goods_image = 'http://' . $_SERVER['HTTP_HOST']."/uploads/home/store/goods/".$goods_info['goods_image'];

                    $standards_info  = db('goods_standards')->where('id',$goods_list[$kk]['standards_id'])->find();

                    $arr2->standards = $standards_info;
                    $arr2->goods_marketprice = $standards_info['goods_marketprice'];
                    $arr2->goods_price = $standards_info['goods_price'];
                    $arr2->transport = $goods_info['goods_freight'];
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
                    $menu_food = db('pbundlinggoods')->where('bl_id',$pbundling_id)->field('goods_name,use')->select();
                    $arr2->menu = $menu_food;
                    $arr2->goods_name = $menu['bl_name'];
                    $arr2->goods_image = 'http://' . $_SERVER['HTTP_HOST']."/uploads/home/store/goods/".$menu['image'];
                    $arr2->goods_num = $vv['goods_num'];
                    $arr2->goods_marketprice = $menu['bl_discount_price'];
                    $arr2->goods_price = $vv['goods_pay_price'];
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
        $transport = 0;
        $transport_sn = 0;
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
                 $arr2->goods_price = $vv['goods_pay_price'];
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
        $data->goods_price = (string)$orderlist['order_amount'];
        $data->order_price = (string)$orderlist['order_amount'];
        $data->list = $list;
        $output = array('data' => $data, 'msg' => '查询成功', 'code' => '200');
        exit(json_encode($output));
    }    
    
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
            $aop->appId = '2019071965944062';
            $aop->rsaPrivateKey = 'MIIEowIBAAKCAQEAxBsEMy1Imdan3fJ1/AgfTmzXxI0UkoHIWic3LytSTHf+ZGqDVPpMgk8kLMFslfUswHwuAmNLFJL6gNo7X85a3UInabF2isPlQC0VJSb+gHQuDu2f4kIVqgsIRMJunVYJ9PoTqf+V6GVt2rGTX1Afp+yiLLK0ydSbnYh44qZisOTGJpXPLboXAGo26XRa+HSmwgbsbRnzyvn3l0I5/9uU25IiC0gZENvKxocgnRAasxOCeZ/hA1mr7ZYQWb4fzTte7oUuScHNM2v8xZAbAmQOqh+v0RiPG/lEmdJtppMGllc9AxwnmzJ0ITnp5hzC367vZ1wkAPrQXv1vaIKDsJrmZQIDAQABAoIBAB7Y9Rcrx2DToEJMcny7tlj6zBIR8yt8mMx9oLOdx+tZcL3Q92m1mbVhx5n2ryMDlw+MORyNC/FnkVoVegN/DVICpvp8PN5lJDmtHcdjU+NW4yvb+yt3I7tE0v8l6op7T0Om1tMF7knJMreU+U6j5ubeVUBcuA2LkRL9ta5JZ/rVKu1CCldX2EnRsRJN1jMRo7CcqTz+HaJykt58wPUUPaqm+fN+cVjaabF1uQU4gPS1CiI9AAUfb3gd1w/FLRTmReVHQ872GzMNbi/G/zECU7zU+I29dsJMN9FIqwfAbW5cGE490Oj9fguuJ6RdI6u4l0YxQ/zLBKzYVW6DrX6yy2ECgYEA+FCtiNe/U+brmlX9I5GEtROIstsa74lnI5JH2eZgVqtyAHeMiUJtRTj5UOPi+bDkvZDJwJnA40DUtLErYtok0CVYiBYVsoqPF+JYluTRKYgKS0Ucy58z8oD+AfNE3RtE2HU3VOz6HS+tYmRvtJt2n7j9be9qbXBf9HA94LsfRn0CgYEAyiyyxTJKVctldXb1xqoDqs2Mzi1297n7ZAXIM81dRrVP3pEdji9Vs0XmwAfO7yoDsD3o6ySzHdyst7kOYjWIEq7sG2GweH+TSLwNGkdN/Z08a46KcJO3fGyaZ159icmBVCGrh72IJqPEnkpBQZldXJl457uExyj/HZPw//kG3AkCgYB8xAS9ijHPFWrx7By532b/mKYJv/+DtdVF0T0a5h8nzRMF2wuY9/BxZJQYqbgk31W/Td2hUV/Sj4OQmqiDDbqLfwhBsF8Mi4Qkaw06HBRgOsN5WGuEgCSYx2lZT4MdWZM484RuvndZbNhAZNiftbFfxZJIx5ABFwKPjkn4/exT/QKBgQCwJ04EP5NBOZ1HQcVXuZ7EXaCHrFlx8xw0xEzxxce16hiTJgGId3nGX+tz+dm1zPe1wduFtk5SgIoE8jq0G0xaawrRuMZX12BvCVqpQOOVTEDuvh2lhS9kPAQ0eMINIgOLWGLqPJu6fwq7mJtPGt+b5STRVDOWjAPss5BSl5oAcQKBgEXXhwLmZ/fWUnp1Vf9rBSSQE0u/5HxUJ2+KqtssnFRz9AnDhMFt9t6p4UCVvrPZTMNxDOZsINVCRHMPRoJXJ1tgL2goVxqXIQ+1+Z3bOzawYFXYCNFChx0Pu75aLcMWTppm/o5dCEjCHjWPulLluZITDJiSlhqwSCJ5dBLYBfhY';
            $aop->alipayrsaPublicKey='MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAhirao61AEiQeZgJ7ooeNvoY1rbpIZxmYhTjO+G4ss71jtaMKPzzgDFB/EDEYulMV+4dq5MU2OFVmgESWLpFOgsmvbMP+Bu21j2rzqoefs2j2KieftLf2gjtOqX6t+HT9KgKwBcNVKqPuzzRcMws+nFB8DH/pIfT2xgTuU4yVqZGcFn93H9GIM+EOOx4lqmGSnMemR8qGI5kLbhCepx/8OlKIGRPCj7hVxDWVH9mhCvhiEQR44CbXBActHmQPxPHBC/55WDes55w2nL6Yh59ATtd6Kp4vqHq0P+AUsp07+TeBCGCr4kdqb5IdCzFfr1IwVnZE9X1PnRJQHcoi0px30wIDAQAB';
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
            exit(json_encode($output));
    	}
    	if($type==2){
    		vendor('wxpay.lib.WxPay#Api'); 
        $ip = $_SERVER['REMOTE_ADDR'];
        $notify_url = "http://".$_SERVER['HTTP_HOST']."/index.php/home/api/wxNotify";
        $order_amount=$order['order_amount'];
        $order = [
            'appid' => 'wx09405c012d2f0794',
            'mch_id' => '1547527421',
            'nonce_str' => md5(rand(10000,99999)),
            'body' => '订单支付',
            'out_trade_no' => $order_sn,
            // 'total_fee'=>$price*100,//订单金额，单位/分
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
                      "&key=15cbe9646fd1b43c89c5f94e3bb737cf";

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
                                "&key=15cbe9646fd1b43c89c5f94e3bb737cf";

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
    	$point = $orderinfo['order_point'];
    	$voucherid = $orderinfo['order_voucherid'];
    	$buyer_id = $orderinfo['buyer_id'];
    	$member = db('member')->where('member_id',$buyer_id)->find();
        $member_voucherid = $member['voucher_id'];

         if($voucherid !=''){
             $member_voucherid .= $voucherid.",";
             db('member')->where('member_id',$buyer_id)->update(['voucher_id'=>$member_voucherid]);
         }
    	db('order')->where('order_id',$orderId)->update(['order_state'=>0]);
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
    	$real_code = db('verify_code')->where('verify_code_user_name',$mobile)->order('verify_code_add_time DESC')->value('verify_code');
    	if($real_code != $mobile){
    		$output = array('data' => $data, 'msg' => '手机号不匹配', 'code' => '400');
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
            'appid' => 'wx09405c012d2f0794',
            'mch_id' => '1547527421',
            'nonce_str' => md5(rand(10000,99999)),
            'body' => '订单支付',
            'out_trade_no' => $order_sn,
             'total_fee'=>1,
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
                      "&key=15cbe9646fd1b43c89c5f94e3bb737cf";

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
                                "&key=15cbe9646fd1b43c89c5f94e3bb737cf";

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
        $aop->alipayrsaPublicKey='MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAhirao61AEiQeZgJ7ooeNvoY1rbpIZxmYhTjO+G4ss71jtaMKPzzgDFB/EDEYulMV+4dq5MU2OFVmgESWLpFOgsmvbMP+Bu21j2rzqoefs2j2KieftLf2gjtOqX6t+HT9KgKwBcNVKqPuzzRcMws+nFB8DH/pIfT2xgTuU4yVqZGcFn93H9GIM+EOOx4lqmGSnMemR8qGI5kLbhCepx/8OlKIGRPCj7hVxDWVH9mhCvhiEQR44CbXBActHmQPxPHBC/55WDes55w2nL6Yh59ATtd6Kp4vqHq0P+AUsp07+TeBCGCr4kdqb5IdCzFfr1IwVnZE9X1PnRJQHcoi0px30wIDAQAB';

        $flag = $aop->rsaCheckV1($_POST,$aop->alipayPublicKey,'RSA2');
        if ($flag)
        {
            $order_sn = $_POST['out_trade_no'];
            if ($_POST['trade_status'] == 'TRADE_SUCCESS' || $_POST['trade_status'] == 'TRADE_FINISHED')
            {
                $order = db('order')->where('order_sn',$order_sn)->find();

                    $update['payment_code'] = 'alipay';
                    $update['payment_time'] = $time;
                    $update['order_state'] = '20';

                    $info = db('order')->where('order_id', $order['order_id'])->update($update);
                    unset($update);



                    $list = db('ordergoods')->where('order_id', $order['order_id'])->select();
                    foreach($list as $v){
                        if($v['goods_type']==1){
                            $goods_id = $v['goods_id'];

                            db('goods')->where('goods_id',$goods_id)->setInc('goods_salenum',$v['goods_num']);

                            db('goods_standards')->where('id',$v['standards_id'])->setDec('goods_storage',$v['goods_num']);
                        }


                    }
                    
                    if ($info) {
                       
                        return 'success';
                    } else {
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


                    $update['payment_code'] = 'wxpay';
                    $update['payment_time'] = $time;
                    $update['order_state'] = '20';
                    $info = db('order')->where('order_id', $order['order_id'])->update($update);
                    $list = db('ordergoods')->where('order_id', $order['order_id'])->select();

                    foreach($list as $v){
                    if($v['goods_type']==1){
                        $goods_id = $v['goods_id'];

                        db('goods')->where('goods_id',$goods_id)->setInc('goods_salenum',$v['goods_num']);

                        db('goods_standards')->where('id',$v['standards_id'])->setDec('goods_storage',$v['goods_num']);
                    }

                    }
                   
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
       
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=wx09405c012d2f0794&secret=2bbf9662edd8e542b0fd59e7af26026d&code='.$code.'&grant_type=authorization_code';
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
	        if($info['member_logintime'] !=0){
	        db('member')->where('member_openid',$openid)->update(['member_isfirst'=>1]);
	        }
	       
	        $data->access_token =$token2;	
	        $data->openid =$openid;
	        $data->token =$info['token'];
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
    	
        if ($code == '' || $phone ==''  || $verify_code ==''||$access_token==''){

            $output = array('data' => $data, 'msg' => '缺少必要参数', 'code' => '400');
            exit(json_encode($output));
        }
        
	        
	    	$real_code = db('verify_code')->where('verify_code_type',5)->where('verify_code_user_name',$phone)->order('verify_code_add_time DESC')->value('verify_code');
	    	if($real_code != $verify_code){
	    	$data->token='';
	    	$output = array('data' => $data, 'msg' => '手机号不匹配', 'code' => '400');
        	exit(json_encode($output));
	    	}	    		    	
	    	$update['member_openid'] = $code;
	    	$update['member_mobile'] = $phone;
	    	$update['token'] = $this->gettoken();
	        $url2 = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$code;
	        $res2 = json_decode($this->http_get($url2));
	      
	        $update['member_name'] =$res2->nickname;
	        $update['member_avatar'] =$res2->headimgurl;
	        $update['member_logintime'] =time();
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
                $message2['message_body'] = $info3;
                $message2['message_title'] = '注册返还优惠券';
                $message2['message_time'] =time();
                $message2['message_type'] =4;
                db('message')->insert($message2);
	        $info2 = db('member')->where('member_mobile',$phone)->find();
	        $data->token = $info2['token'];
	        $output = array('data' => $data, 'msg' => '授权成功', 'code' => '200');
        	exit(json_encode($output));
	        }else{
	        $data->token='';	
	        $output = array('data' => $data, 'msg' => '授权失败', 'code' => '400');
        	exit(json_encode($output));	
	        }
	    
        
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
    	return $userId;
    }
    public function task(){
    	$list = db('message')->select();
    	$time = time();
    	foreach($list as $v){
    		if($v['message_time']>time()){
    			db('message')->where('message_id',$v['message_id'])->delete();
    		}
    	}
    }
    
}