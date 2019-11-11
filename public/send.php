<?php
namespace AlibabaCloud\Client;
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;



AlibabaCloud::accessKeyClient('LTAIoIb6DxdqJ1zZ', 'EWOsICDT5ceECQSGKZLQcuh0qwLnHt')
                        ->regionId('cn-hangzhou')
                        ->asDefaultClient();

try {
    $result = AlibabaCloud::rpc()
                          ->product('Dysmsapi')
                          // ->scheme('https') // https | http
                          ->version('2017-05-25')
                          ->action('SendSms')
                          ->method('POST')
                          ->options([
                                        'query' => [
                                          'RegionId' => "cn-hangzhou",
                                          'PhoneNumbers' => "13644032046",
                                          'SignName' => "送家家",
                                          'TemplateCode' => "SMS_171193669",
                                          'TemplateParam' => "{\"code\": \"123456\"}",
                                        ],
                                    ])
                          ->request();
    print_r($result->toArray());
} catch (ClientException $e) {
    echo $e->getErrorMessage() . PHP_EOL;
} catch (ServerException $e) {
    echo $e->getErrorMessage() . PHP_EOL;
}