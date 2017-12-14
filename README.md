# XingeUnify

### description

统一了信鸽推送sdk中android和ios平台推送方法割裂的问题，通过XingeUnify类可以更简单实现推送服务

### usage


```
composer require "papajo/xingeunify"
```

```php
$push = new \XingeUnify\XingeUnify('access_key', 'secret_key', 'ios');
$ret1 = $push->PushSingleDevice('device_token', 'title', 'content');
//or continue switch app config and push
$ret2 = $push->setAccess('access_key2', 'secret_key2')->setXingeAppObj('android')->setXingeMessObj()->PushSingleDevice('device_token', 'title', 'content');
```
