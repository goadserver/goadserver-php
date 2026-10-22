# ApiV1AdspacesIdCodeGet200Response

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**adspace_id** | **int** |  | [optional]
**site** | [**\GoAdServerApi\Model\ApiV1AdspacesIdCodeGet200ResponseSite**](ApiV1AdspacesIdCodeGet200ResponseSite.md) |  | [optional]
**space_name** | **string** |  | [optional]
**js_code** | **string** | Per-zone JS call — wrap in &lt;script&gt;. | [optional]
**ad_tag** | **string** | Iframe alternative. | [optional]
**alternative** | **string** | No-JS fallback. | [optional]
**note** | **string** |  | [optional]
**amp** | [**\GoAdServerApi\Model\ApiV1AdspacesIdCodeGet200ResponseAmp**](ApiV1AdspacesIdCodeGet200ResponseAmp.md) |  | [optional]
**screen** | [**\GoAdServerApi\Model\ApiV1AdspacesIdCodeGet200ResponseScreen**](ApiV1AdspacesIdCodeGet200ResponseScreen.md) |  | [optional]
**ctv** | [**\GoAdServerApi\Model\ApiV1AdspacesIdCodeGet200ResponseCtv**](ApiV1AdspacesIdCodeGet200ResponseCtv.md) |  | [optional]
**layout** | **string** | Native and creator widget zones only: the ?layout the code was rendered with (empty &#x3D; default). | [optional]
**layouts** | [**\GoAdServerApi\Model\ApiV1AdspacesIdCodeGet200ResponseLayoutsInner[]**](ApiV1AdspacesIdCodeGet200ResponseLayoutsInner.md) | Native and creator widget zones only: the ready-made layouts ?layout accepts for this zone. | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
