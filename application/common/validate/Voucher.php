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
class  Voucher extends Validate
{
    protected $rule = [
        ['vouchertemplate_title', 'require|length:1,50', '模版名称不能为空且不能大于50个字符'],

        ['vouchertemplate_price', 'require', '模版面额不能为空且必须为整数'],
        ['vouchertemplate_limit', 'require', '模版使用消费限额不能为空且必须是数字'],
        ['vouchertemplate_desc', 'require|length:1,255', '模版描述不能为空且不能大于255个字符']
    ];

    protected $scene = [
        'templateadd' => ['vouchertemplate_title', 'vouchertemplate_price', 'vouchertemplate_limit', 'vouchertemplate_desc'],
        'templateedit' => ['vouchertemplate_title', 'vouchertemplate_price', 'vouchertemplate_limit', 'vouchertemplate_desc'],
    ];
}