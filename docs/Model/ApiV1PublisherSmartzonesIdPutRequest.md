# ApiV1PublisherSmartzonesIdPutRequest

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**name** | **string** |  | [optional]
**spaceid** | **int** |  | [optional]
**smart_networkid** | **int** |  | [optional]
**siteid** | **int** | Ignored unless &#x60;spaceid&#x60; is sent — then derived from it. | [optional]
**countries** | **string** |  | [optional]
**device_type_ids** | **string** |  | [optional]
**is_active** | [**\GoAdServerApi\Model\ApiV1PublisherSmartzonesIdPutRequestIsActive**](ApiV1PublisherSmartzonesIdPutRequestIsActive.md) |  | [optional]
**select_type** | **int** | 0 &#x3D; percentage, 1 &#x3D; eCPM, 2 &#x3D; RTB, 3 &#x3D; eCPM + limit %. A numeric string is accepted too. | [optional]
**select_pct** | **int** |  | [optional]
**notes** | **string** |  | [optional]
**creative** | **string** | JSON-encoded string | [optional]
**settings** | **string** | JSON-encoded string | [optional]
**use_adblock** | **int** |  | [optional]
**unique_only** | [**\GoAdServerApi\Model\ApiV1PublisherSmartzonesIdPutRequestUniqueOnly**](ApiV1PublisherSmartzonesIdPutRequestUniqueOnly.md) |  | [optional]
**network_zoneid** | **int** |  | [optional]
**network_siteid** | **int** |  | [optional]
**ssp_campaignid** | **int** |  | [optional]
**scanning_enabled** | [**\GoAdServerApi\Model\ApiV1PublisherSmartzonesIdPutRequestIsActive**](ApiV1PublisherSmartzonesIdPutRequestIsActive.md) |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
