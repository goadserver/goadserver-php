# ApiV1LookupsRegionsGet200Response

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**parent_id** | **int** |  | [optional]
**count** | **int** |  | [optional]
**truncated** | **bool** | Present (true) only when the response was capped at the 2000-item limit. | [optional]
**limit** | **int** | Cap value, only present when truncated&#x3D;true. | [optional]
**note** | **string** |  | [optional]
**regions** | [**\GoAdServerApi\Model\ApiV1LookupsRegionsGet200ResponseRegionsInner[]**](ApiV1LookupsRegionsGet200ResponseRegionsInner.md) |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
