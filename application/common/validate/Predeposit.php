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
class  Predeposit extends Validate
{
    protected $rule = [
        ['member_id', 'require|number', '用户名必须存在|用户名错误'],
        ['amount', 'require', '金额为必填'],
        ['operatetype', 'require', '增减类型为必填'],
        ['pdc_amount','require|min:0.01','提现金额为大于或者等于0.01的数字'],
        ['pdc_bank_name','require','请填写收款银行'],
        ['pdc_bank_no','require','请填写收款账号'],
        ['pdc_bank_user','require','请填写收款人姓名'],
        ['password','require', '请输入支付密码']
    ];

    protected $scene = [
        'pd_add' => ['member_id', 'amount', 'operatetype'],
        'pd_cash_add' => ['pdc_amount', 'pdc_bank_name', 'pdc_bank_no', 'pdc_bank_user', 'password'],
    ];
}