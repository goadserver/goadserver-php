# ApiV1UrlPoolsIdUrlsPostRequest

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**url** | **string** |  |
**title** | **string** | Required — handlers/advertiser/url_pools.go returns 422 NO_TITLE_GIVEN when omitted. |
**weight** | **int** | Share under sort_type wr. Omitted &#x3D; 0, which a weighted rotation never picks — send a weight when the pool uses wr. | [optional] [default to 0]
**properties** | **string** |  | [optional]
**keywords** | **string** | Comma-separated targeting keywords; empty &#x3D; a keyword fallback URL (serves when no targeted URL matches). | [optional]
**is_fallback** | **bool** | Backup: used only while no other URL of the pool is live (\&quot;Backup\&quot; in the panel). | [optional]
**start_at** | [**\GoAdServerApi\Model\ApiV1CreativePoolsIdItemsItemIdPutRequestStartAt**](ApiV1CreativePoolsIdItemsItemIdPutRequestStartAt.md) |  | [optional]
**end_at** | [**\GoAdServerApi\Model\ApiV1CreativePoolsIdItemsItemIdPutRequestEndAt**](ApiV1CreativePoolsIdItemsItemIdPutRequestEndAt.md) |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
