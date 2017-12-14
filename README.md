# XingeUnify

##统一了信鸽推送sdk中android和ios平台推送方法割裂的问题，通过XingeUnify类可以更简单实现推送服务

#用法


```

composer require "papajo/xingeunify"

```

```php
$push = new \XingeUnify\XingeUnify('access_key', 'secret_key', 'ios');
$push->PushSingleDevice('device_token', 'title', 'content');
```
