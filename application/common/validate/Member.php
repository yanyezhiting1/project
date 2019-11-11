<?php
namespace app\common\validate;
use think\Validate;
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
 * 验证器
 */
class  Member extends Validate
{
    protected $rule = [
        ['member_name', 'require|length:3,12', '用户名必填|用户名长度在3到12位'],
        ['member_password', 'require|length:6,20', '密码为必填|密码长度必须为6-20之间'],
        ['member_email', 'email', '邮箱格式错误'],
        ['member_mobile', 'length:11,11', '手机格式错误'],
        ['member_nickname', 'max:10', '真实姓名长度超过10位'],
    ];
    protected $scene = [
        'add' => ['member_name', 'member_password', 'member_email'],
        'edit' => ['member_email', 'member_mobile', 'member_email'],
        'edit_information' => ['member_nickname'],
        'login' => ['member_name', 'member_password'],
        'register' => ['member_name', 'member_password'],
    ];
}