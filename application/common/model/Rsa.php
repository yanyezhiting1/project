<?php

namespace app\common\model;

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
class  Rsa extends Model {
    private static $PRIVATE_KEY='-----BEGIN PRIVATE KEY-----
MIICeAIBADANBgkqhkiG9w0BAQEFAASCAmIwggJeAgEAAoGBAM4I1AkeWyR/kYH/
BaAQY1ep+F+Bef5dIYlFbMtaOx0mchy1gEIOseDCICYLfHeZyffEQUU5EmNKXgP7
v43vUlCEYv0SfgO3Z0kbayUF5orVotIIwy+fqc43pqewuh+qPdYYoenf/j2tKzVA
YEbetgP+AnV83ZGqW9QxuRm4xZRTAgMBAAECgYEAnWzbbng2VcXvTS+pgarj7Qif
EYJhzzwjsrpMLXitMFG+4TbBYDfQLBbH76nZGZ11V44p/RVlel5JRavmqjGRZzr2
fw1UyxK9FX741jKdBHGYziHB4ikFZzT+Of3w6JIf3zPDEf6xfH9kuQ6h0dxDRwY0
i9ZBcRB+GHTssCRF+yECQQDuuxHK33QlmXOFuYDeiKc5wjox0mMko77WaCH9fF9E
LN5Hf7N/zgudBZF0s0zWOaW0DourGlQqA8NrfLARxouxAkEA3PBGGJogWRo0y+iS
hi8fBsEjaeJgldXAo0CfnmtQrcJlGuiGrnNTGX3CP3nKHWZP76P6CrUeS8c31Ry0
p9GVQwJBAOlQZpkxPeApUp/Upj/WqihmzF040rBSYAZHi0Cjtq94clzKT3GOvAbg
FEJLocKUYH/S32l/t9XAC9MW7zTQKGECQAjhN1AB0c8C+KBBZrIx7qNM2+mDibI7
9xQYotGxKnrxVzLvqYoVZH+fyFDYykDIPeo5wvDvOpp9FUdhcflUuRUCQQCCEQbb
1U8atrtaowheQjrdpbkOBfD9xyZqYNHMrTaGz99qS1Ryn1CC/N9ZY/BxrkoyyD1j
4uuMdZL1A+AAr+We
-----END PRIVATE KEY-----';

    private static $PUBLIC_KEY = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDOCNQJHlskf5GB/wWgEGNXqfhf
gXn+XSGJRWzLWjsdJnIctYBCDrHgwiAmC3x3mcn3xEFFORJjSl4D+7+N71JQhGL9
En4Dt2dJG2slBeaK1aLSCMMvn6nON6ansLofqj3WGKHp3/49rSs1QGBG3rYD/gJ1
fN2RqlvUMbkZuMWUUwIDAQAB
-----END PUBLIC KEY-----';
    public $clientPublicKey = '';

    /**
     * 设置客户端的公钥
     */
    public function setClientPublicKey($cPubKey){
        $this->clientPublicKey = $cPubKey;
    }

    /**
     *获取服务端私钥
     */
    private static function getPrivateKey()
    {
        $privKey = self::$PRIVATE_KEY;
        $passphrase = '';
        return openssl_pkey_get_private($privKey,$passphrase);
    }

    /**
     * 服务端公钥加密数据
     * @param unknown $data
     */
    public static function publEncrypt($data){
        $publKey = self::$PUBLIC_KEY;
        $publickey = openssl_pkey_get_public($publKey);
        //使用公钥进行加密
        $encryptData = '';
        openssl_public_encrypt($data, $encryptData, $publickey);
        return base64_encode($encryptData);
    }

    /**
     * 服务端私钥加密
     */
    public static function privEncrypt($data){
        if(!is_string($data))
        {
            return null;
        }
        return openssl_private_encrypt($data,$encrypted,self::getPrivateKey())? base64_encode($encrypted) : null;

//        $crypto = '';
//        foreach (str_split($data, 117) as $chunk) {
//            openssl_private_encrypt($chunk, $encryptData, self::getPrivateKey());
//            $crypto .= $encryptData;
//        }
//        $encrypted = base64_encode($crypto);//加密后的内容通常含有特殊字符，需要编码转换下，在网络间通过url传输时要注意base64编码是否是url安全的
//        return $encrypted;


    }

    /**
     * 服务端私钥解密
     */
    public static function privDecrypt($encrypted,$str)
    {
        if(!is_string($encrypted)){
            return null;
        }
        $privatekey = self::getPrivateKey();
        $sensitivData = '';
        //return (openssl_private_decrypt(base64_decode($encrypted), $decrypted, self::getPrivateKey()))? $decrypted : null;
        openssl_private_decrypt(base64_decode($encrypted), $sensitivData, $privatekey);
        //var_dump($sensitivData);
        return $sensitivData;
    }

    /**
     * 客户端的公钥解密数据
     * @param unknown $publicKey
     * @param unknown $encryptString
     */
    public function clientPublicDecrypt($encryptString){
        if(!is_string($encryptString)) return null;
        $encodeKey = self::$PUBLIC_KEY;
        $publicKey = openssl_pkey_get_public($encodeKey);
        if(!$publicKey) {
            exit("\nClient Publickey Can not used");
        }
        $sensitivData = '';
        openssl_public_decrypt(base64_decode($encryptString), $sensitivData, $publicKey);
        return $sensitivData;
    }

    /**
     * 客户端公钥加密数据
     * @param string $string 需要加密的字符串
     * @return string Base64编码的密文
     */
    public function clientPublicEncrypt($string){
        $publKey = $this->clientPublicKey;
        $publicKey = openssl_pkey_get_public($publKey);
        if(!$publicKey) {
            exit("\nClient Publickey Can not used");
        }
        //使用公钥进行加密
        $encryptData = '';
        openssl_public_encrypt($string, $encryptData, $publicKey);
        return base64_encode($encryptData);
    }

    public function formatKey($key, $type = 'public1'){
        if($type == 'public'){
            $begin = "-----BEGIN PUBLIC KEY-----\n";
            $end = "-----END PUBLIC KEY-----";
        }else{
            $begin = "-----BEGIN PRIVATE KEY-----\n";
            $end = "-----END PRIVATE KEY-----";
        }
        //$key = ereg_replace("\s", "", $key);
        $key= preg_replace('/\s/','',$key);
        $str = $begin;
        $str .= substr($key, 0,64);
        $str .= "\n" . substr($key, 64,64);
        $str .= "\n" . substr($key, 128,64);
        $str .= "\n" . substr($key, 192,64);
        $str .= "\n" . substr($key, 256,64);
        $str .= "\n" . substr($key, 320,64);
        $str .= "\n" . substr($key, 384,64);
        $str .= "\n" . substr($key, 448,64);
        $str .= "\n" . substr($key,512,64);
        $str .= "\n" . substr($key,576,64);
        $str .= "\n" . substr($key,640,64);
        $str .= "\n" . substr($key,704,64);
        $str .= "\n" . substr($key,768,64);
        $str .= "\n" . substr($key,832,16);
        $str .= "\n" . $end;
        return $str;
    }
    
}

