<?php


namespace app\common\model;
use JPush\Client as JPush;

use think\console\input\Option;
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
class  Push extends Model
{
    public $page_info;
    /**
     * 友情链接列表
     * @access public
     * @author csdeshang
     * @param type $condition  查询条件
     * @param type $page       分页页数
     * @param type $order      排序
     * @return type            返回结果
     */
    public function pushall($message)
{
    vendor('jpush.jpush.autoload');
    $client = new \JPush\Client('a33b2809a74e1dabc09d03c8', 'eab3ae05a6ffb34a32b17dc8');
    $pusher = $client->push();
    $pusher->setPlatform('android','ios');
    $pusher->addAllAudience();
    $pusher->setNotificationAlert($message);
    try {
        $pusher->send();
    } catch (\JPush\Exceptions\JPushException $e) {
        // try something else here
        print $e;
    }
}


    public function pushone($message,$token)
    {
        vendor('jpush.jpush.autoload');
        $client = new \JPush\Client('a33b2809a74e1dabc09d03c8', 'eab3ae05a6ffb34a32b17dc8');
        $pusher = $client->push();
        $pusher->setPlatform('all');
        $pusher->addAlias($token);
        $pusher->setNotificationAlert($message);

        try {
            $pusher->send();
        } catch (\JPush\Exceptions\JPushException $e) {
            // try something else here
            print $e;
        }
    }


}