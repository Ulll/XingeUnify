<?php

namespace XingeUnify;

use XingeUnify\XingeSdk\XingeApp;
use XingeUnify\Exception\XingeException;
use XingeUnify\Log\Log;
use XingeUnify\XingeSdk\MessageIOS;
use XingeUnify\XingeSdk\Message;
use XingeUnify\XingeSdk\TimeInterval;
use XingeUnify\XingeSdk\ClickAction;
use XingeUnify\XingeSdk\TagTokenPair;
use XingeUnify\XingeSdk\Style;

class XingeUnify {
    /*
     * @var access_id
    */
    private $_access_id;
    /*
     * @var access_key
    */
    private $_access_key;
    /*
     * @var secret_key
    */
    private $_secret_key;
    /*
     * @var 消息体对象
    */
    private $_mess_obj;
    /*
     * @var 环境
    */
    private $_environment = 0;
    /**
     * @var Android收到消息后的默认跳转activity
     */
    private $_android_default_activity = 'com.xxxx.android.activities.MainActivity';
    /**
     * @var 推送实例
     */
    private $_pushobj;
    /**
     * @var 消息实例
     */
    private $_messobj;
    /**
     * @var 设备平台
     */
    private $_device;

    /**
     * 构造函数
     */
    function __construct($access_key, $secret_key, $device){
        $this->_device = $device;
        $this->_access_key = $access_key;
        $this->_secret_key = $secret_key;
        $this->setXingeAppObj($device)->setXingeMessObj();
    }


    /**
     * 设置信鸽实例
     * @param $device ios|android
     * @return object
     */
    public function setXingeAppObj($device)
    {
        $this->_pushobj = new XingeApp($this->_access_key, $this->_secret_key);
        switch ($device) {
            case 'ios':
                $this->_environment = XingeApp::IOSENV_PROD;
                $this->_device = $device;
                break;
            case 'android':
                $this->_environment = 0;
                $this->_device = $device;
                break;
            default:
                throw new XingeException("xinge device not support", "xinge device not support:$device");
                break;
        }
        return $this;
    }


    /**
     * 设置信鸽消息体
     */
    public function setXingeMessObj()
    {
        switch ($this->_device) {
            case 'ios':
                $this->_messobj = new MessageIOS();
                //设置声音
                $this->_messobj->setBadge(1);
                $this->_messobj->setSound("push_0.wav");
                break;
            case 'android':
                $this->_messobj = new Message();
                //设置声音，含义：样式编号0，响铃，震动，不可从通知栏清除，不影响先前通知
                $style = new Style(0,1,1,1,0);
                $style->setRingRaw('push');
                $this->_messobj->setStyle($style);
                //指定activity
                $action = new ClickAction();
                $action->setActivity($this->_android_default_activity);
                $this->_messobj->setAction($action);
                $this->_messobj->setType(Message::TYPE_NOTIFICATION);
                break;
            default:
                throw new XingeException("call setXingeAppObj first", "call setXingeAppObj first");
                break;
        }
        //设置消息离线保存时间为1天
        $this->_messobj->setExpireTime(86400);
        //设置推送时间
        $send_time = date('Y-m-d H:i:s');
        $this->_messobj->setSendTime($send_time);
        //设置推送时间段
        $acceptTime1 = new TimeInterval(08, 00, 23, 59);
        $this->_messobj->addAcceptTime($acceptTime1);
        return $this;
    }


    /**
     * 设置新的access配置
     * @param string $access_key
     * @param string $secret_key
     */
    public function setAccess($access_key, $secret_key)
    {
        $this->_access_key = $access_key;
        $this->_secret_key = $secret_key;
        return $this;
    }

    /**
     * 获取当前的access_key
     * @return string
     */
    private function getAccess()
    {
        return $this->_access_key;
    }


    /**
     * 设置android推送时的默认activity
     * @param string $activity
     */
    public function setAndroidDefaultActivity($activity)
    {
        $this->_android_default_activity = $activity;
        return $this;
    }


    /**
     * 区分平台设置消息体
     * @param 消息标题 $title
     * @param 消息正文 $content
     */
    public function setMsg($title, $content)
    {
        switch ($this->_device) {
            case 'ios':
                $this->_messobj->setAlert(['title' => $title, 'body' => $content]);
                break;
            case 'android':
                $this->_messobj->setTitle($title);
                $this->_messobj->setContent($content);
                break;
            default:
                throw new XingeException("xinge device not support", "xinge device not support:$device");
                break;
        }
    }



    /**
     * 推送单条消息给单个设备
     * @param string $deviceToken
     * @param string $title
     * @param string $content
     * @param int $expiretime
     * return array
     */
    public function PushSingleDevice($deviceToken, $title, $content, $expiretime = 86400, $custom = NULL)
    {
        $this->setMsg($title, $content);
        //设置自定义消息
        if ($custom){
            $this->_messobj->setCustom($custom);
        }
        $ret = $this->_pushobj->PushSingleDevice($deviceToken, $this->_messobj, $this->_environment);
        $this->PushLog($ret);
        return $ret;
    }


    /**
     * 推送单条消息给（一批）用户
     * @param string $deviceToken
     * @param string $title
     * @param string $content
     * @param int $expiretime
     * return array
     */
    public function PushByAccount($device_type, $title, $content, $account, $expiretime = 86400, $custom = NULL)
    {
        $this->setMsg($title, $content);
        //设置自定义消息
        if ($custom){
            $this->_messobj->setCustom($custom);
        }
        //判断是给单个账户还是多账户
        if(is_array($account)){
            $total = count($account);
            if ($total > 100){
                //单次发送account不超过100个
                $account = array_chunk($account, 100);
                foreach ($account as $key => $item){
                    $ret = $this->_pushobj->PushAccountList($device_type, $item, $this->_messobj,$this->_environment);
                    sleep(1);
                }
            }
            else{
                $ret = $this->_pushobj->PushAccountList($device_type, $account, $this->_messobj,$this->_environment);
            }
        } 
        else{
            $ret = $this->_pushobj->PushSingleAccount($device_type, $account, $this->_messobj, $this->_environment);
        }
        $this->PushLog($ret);
        return $ret;
    }


    /**
     * 
     * 给指定tags的android设备推送消息
     * @param string $device_type
     * @param string $title
     * @param string $content
     * @param array $tagList  标签数组   例如：array('Demo3','test1','test2')
     * @param string $tagsOp  多个tag 的运算关系，取值为AND 或OR
     * @param int $expiretime
     * return array
     */
    public function PushByTags($device_type, $title, $content, $tagList, $tagsOp, $expiretime = 86400, $custom = NULL)
    {
        $this->setMsg($title, $content);
        //设置自定义消息
        if ($custom){
            $this->_messobj->setCustom($custom);
        }
        $ret = $this->_pushobj->PushTags($device_type, $tagList, $tagsOp, $this->_messobj, $this->_environment);
        $this->PushLog($ret);
        return $ret;
    }
    
    /**
     * 
     * 查询消息推送状态
     * @param array $pushId_list  推送任务id 列表   例如：array(304,234,679,1205)
     * return array 
     */
    function QueryPushStatus($pushId_list)
    {
        $ret = $this->_pushobj->QueryPushStatus($pushId_list);
        $this->PushLog($ret);
        return $ret;
    }
    
    /**
     * 查询应用覆盖的设备数
     * return array
     */
    function QueryDeviceCount()
    {        
        $ret = $this->_pushobj->QueryDeviceCount();
        $this->PushLog($ret);
        return $ret;
    }
    
    /**
     * 查询应用的tags
     * @param int $start   开始位置
     * @param int $limit   限制结果数量
     * return array
     */
    function QueryTags($start=0,$limit=100)
    {
        $ret = $this->_pushobj->QueryTags($start,$limit);
        $this->PushLog($ret);
        return $ret;
    }
    
    
    /**
     * 查询某个tag下token的数量
     * @param string $tag
     * return array
     */
    function QueryTagTokenNum($tag)
    {
        $ret = $this->_pushobj->QueryTagTokenNum($tag);
        $this->PushLog($ret);
        return $ret;
    }
    
    /**
     * 
     * 查询token 的tags
     * @param string $deviceToken
     * return array
     */
    function QueryTokenTags($deviceToken)
    {
        $ret = $this->_pushobj->QueryTokenTags($deviceToken);
        $this->PushLog($ret);
        return $ret;
    }
    
    /**
     * 取消定时任务
     * @param string $push_id  推送任务id
     * return array
     */
    function CancelTimingPush($push_id)
    {
        $ret = $this->_pushobj->CancelTimingPush($push_id);
        $this->PushLog($ret);
        return $ret;
    }
    
    /**
     * 
     * 批量为token 设置标签
     * @param array $tagTokenPairs tag和token的键值对 如：array(array("tag1","token00000000000000000000000000000000001"),array("tag1","token00000000000000000000000000000000002"))
     * return array
     * 
     */
    function BatchSetTag($tagTokenPairs) {
        
        if (!empty($tagTokenPairs)) {            
            $tagTokenPairs = array_chunk($tagTokenPairs, 20,true);//每次调用最多允许输入20 个pair
            foreach ($tagTokenPairs as $arr) {
                $pairs = array();
                foreach ($arr as $key => $value) 
                {
                    array_push($pairs, new TagTokenPair($value[0],$value[1]));
                }
                $ret = $this->_pushobj->BatchSetTag($pairs);
            }
        }
        $this->PushLog($ret);
        return $ret;
        
    }
    
    /**
     * 
     * 批量为token 删除标签
     * @param array $tagTokenPairs tag和token的键值对 如：array(array("tag1","token00000000000000000000000000000000001"),array("tag1","token00000000000000000000000000000000002"))
     * return array
     * 
     */
    function BatchDelTag($tagTokenPairs) {
        
        if (!empty($tagTokenPairs)) {            
            $tagTokenPairs = array_chunk($tagTokenPairs, 20,true);//每次调用最多允许输入20 个pair
            foreach ($tagTokenPairs as $arr) {
                $pairs = array();
                foreach ($arr as $key => $value) 
                {
                    array_push($pairs, new TagTokenPair($value[0],$value[1]));
                }
                $ret = $this->_pushobj->BatchDelTag($pairs);
            }   
        }
        $this->PushLog($ret);
        return $ret;
    }


    /**
     * 
     * 消息推送日志
     * @param string $ret 
     * return boolean
     */
    public function PushLog($ret) 
    {
        return true;
    }
}
