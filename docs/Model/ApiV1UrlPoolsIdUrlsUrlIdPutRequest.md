# ApiV1UrlPoolsIdUrlsUrlIdPutRequest

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**url** | **string** |  | [optional]
**title** | **string** |  | [optional]
**weight** | **int** |  | [optional]
**properties** | **string** |  | [optional]
**keywords** | **string** | Comma-separated targeting keywords; empty &#x3D; a keyword fallback URL (serves when no targeted URL matches). | [optional]
**is_fallback** | **bool** | Backup: used only while no other URL of the pool is live (\&quot;Backup\&quot; in the panel). | [optional]
**start_at** | [**\GoAdServerApi\Model\ApiV1CreativePoolsIdItemsItemIdPutRequestStartAt**](ApiV1CreativePoolsIdItemsItemIdPutRequestStartAt.md) |  | [optional]
**end_at** | [**\GoAdServerApi\Model\ApiV1CreativePoolsIdItemsItemIdPutRequestEndAt**](ApiV1CreativePoolsIdItemsItemIdPutRequestEndAt.md) |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
