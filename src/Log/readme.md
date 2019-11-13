## Laravel PHP 日志组件说明
日志格式：参照http://git.dev.enbrands.com/techdoc/home/blob/master/%E6%97%A5%E5%BF%97%E8%A7%84%E8%8C%83.md 文档

[时间] dev.INFO: [文件名.类名.方法名.行号]日志标签...

日志组件参数要求
公共参数：
ip-客户端真实ip地址
traceId-traceId
domain-域名
spanId-spanId
cspandId-cspandId
hintContent-hintContent(格式为数组)
msg-提示文字
uid-用户的id
fileName-文件名
className-类名
functionName-方法名
number-行号
merchantNum-商家编号
mobile-手机号
uri-接口路径
request-请求参数(格式为数组)
logTag-日志标签

_request_in：请求进入系统的第一条日志, 核心字段是url(接口的URL),request(参数)。例：[2019-11-11 14:26:21] dev.INFO: [ActivityController.php.ActivityController.postJoinactivity.127]_request_in||trace_id=123||host=qizhengning.ews.dev.enbrands.com||client_ip=61.185.204.247||span_id=||cspand_id=||hintContent=||url=apis/activity/joinactivity||request={"merchantNum":"1000012","instId":"10","ab":"test"}||uid=||merchant_num=1000012||phone=||msg=会员参与互动-开始
    参数
    logTag：_request_in

_request_out：请求返回的响应日志，为该请求在当前系统的最后一条日志，核心字段是url(接口的URL),response(返回的响应体),elapsed(耗时毫秒),code(接口响应码)。例：[2019-11-11 14:26:21] dev.INFO: [ActivityController.php.ActivityController.postJoinactivity.165]_request_out||trace_id=123||host=qizhengning.ews.dev.enbrands.com||client_ip=61.185.204.247||span_id=||cspand_id=||hintContent=||url=apis/activity/joinactivity||request={"merchantNum":"1000012","instId":"10","ab":"test","member_id":9,"account":"\u661301jA5O1NGSOBu71Osp0qZUXc16em85Fa0GBxDkCy7kcI8=","account_type":4,"userInfo":{"member_id":9}}||uid=||merchant_num=1000012||phone=||response={"code":6201,"msg":"\u6821\u9a8c\u53c2\u6570\u5f02\u5e38\uff1ainteractionId\u53c2\u6570\u540d\u79f0\u4e3a\u7a7a","data":[]}||elapsed=110||code=6201||msg=会员参与互动-结束
    参数
    logTag：_request_out
    response：返回的响应体(格式为json)
    elapsed：存进入系统到请求返回的耗时（单位毫秒）
    code：接口相应码

_http_success：rpc访问成功日志，核心字段是caller(调用方),callee(被调用方),uri(接口的URL),response(返回的响应体),elapsed(耗时毫秒),code(接口响应码)。例：[2019-11-11 14:26:21] dev.INFO: [ActivityLogic.php.ActivityLogic.verifyThreshold.535]_http_failure||trace_id=123||host=http://39.98.134.148:30001||client_ip=61.185.204.247||span_id=||cspand_id=||caller=enbrands_client||callee=http://39.98.134.148:30001||hintContent=||url=apis/activity/joinactivity||request={"merchantNum":"1000012","memberId":9,"interactionId":0,"activityType":1,"activityId":"10","ip":"61.185.204.247","timestamp":1573453581,"sessionKey":"050c31ef14c42a961c85da9c42bcd0da"}||uid=9||merchant_num=1000012||phone=||response={"retCode":"000039","retMsg":"校验参数异常：interactionId参数名称为空","retObj":null,"extendInfoMap":null}||elapsed=24||err_no=0||code=000039||msg=校验活动门槛(java)
    参数
    logTag：_http_success
    response：返回的响应体(格式为json)
    elapsed：存进入系统到请求返回的耗时（单位毫秒）
    code：接口相应码
    caller：调用方（商家端-enbrands 会员端enbrands_client）
    callee：被调用方（调用java或外部接口的地址）
    err_no：curl接口请求错误码

_http_failure：rpc访问失败日志，核心字段是caller(调用方),callee(被调用方),uri(接口的URL),response(返回的响应体),elapsed(耗时毫秒),code(接口响应码)。例：[2019-10-31 21:58:56] dev.INFO: [MemberLogic.php.MemberLogic.unbind.197]_http_success||trace_id=c8651ef6e5db7723||host=http://39.98.134.148:30001||client_ip=61.185.204.247||span_id=||cspand_id=||caller=enbrands_client||callee=http://39.98.134.148:30001/dockplat/unBindRecord.do||hintContent=||url=apis/member/unbind||request={"merchantNum":"1000012","account":"1","accountType":1,"ip":"61.185.204.247","timestamp":1572530336,"sessionKey":"e8ecfd43093535255f7a1a153f34ffd7"}||uid=||merchant_num=1000012||phone=||response={"retCode":"0","retMsg":"","retObj":0,"extendInfoMap":null}||elapsed=125||err_no=0||code=0||timeout=10||msg=会员解绑(java)
    参数
    logTag：_http_failure
    response：返回的响应体(格式为json)
    elapsed：存进入系统到请求返回的耗时（单位毫秒）
    code：接口相应码
    caller：调用方（商家端-enbrands 会员端enbrands_client）
    callee：被调用方（调用java或外部接口的地址）
    errNo：curl接口请求错误码

###安装说明（仅支持larave框架）
1.在项目composer.json文件里面增加相关信息
    repositories里面增加组件type和url
    "repositories": [
            {
                "type":"git",
                "url":"ssh://git@git.dev.enbrands.com:22/foundation/PHPCommon.git"
            }
        ]
    require里面增加组件名称	
    "require": { 
        "foundation/phpcommon": "dev-qizhengning_logComponent_20191108"
    }

2.执行 composer update foundation/phpcommon (必须指定要更新的包，否则会全部更新)

3.手动删除掉vendor/foundation/phpcommon/.git文件（目前还没有好的办法）

4. \config\app.php 文件里面注释掉 App\Providers\MyLogerProvider ，在该位置增加 \PHPCommon\Log\MyLogerProvider::class,

5.执行命令 composer dumpautoload -ao

###调用说明
 只是部分参数和方法   
 $log_param = [
            'msg'=>'会员参与互动-开始',
            'fileName'=>'',
            'className'=>'',
            'functionName'=>'',
            'number'=>'',
        ];
 FormatLogData类：命名空间-PHPCommon\Log\FormatLogData; 
 getLogName()：获取日志的文件名
 getlogNormalFormat()：返回统一的日志格式
 getTraceId()：获取traceId
 
 使用上面的方法时，需要导入命名空间  use PHPCommon\Log\FormatLogData;
 \MyLog::logBuryingPoint(FormatLogData::getLogName(), FormatLogData::getlogNormalFormat($log_param));
 
 日志名称：normal_format_log（暂定，支持传入名称但不建议）
 日志路径：storage/logs/normal_format_log/年-月-日/后面是每个小时的日志文件。例： normal_format_log_14.log
	

