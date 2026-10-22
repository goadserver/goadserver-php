# ApiV1AdspacesExportCodesGet200ResponseInner

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**siteid** | **int** |  | [optional]
**site_name** | **string** |  | [optional]
**ctrl_js** | **string** | Controller &lt;script&gt; tag — once per page, before the zone snippets. | [optional]
**ctrl_js_failover** | **string** | Alternative to ctrl_js, pasted instead of it: the inline ad-blocker failover loader. Empty when fewer than two usable ad domains are available. | [optional]
**ctrl_name** | **string** |  | [optional]
**codes** | [**\GoAdServerApi\Model\ApiV1AdspacesExportCodesGet200ResponseInnerCodesInner[]**](ApiV1AdspacesExportCodesGet200ResponseInnerCodesInner.md) |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
