# ApiV1AdspacesAddMultiplePostRequest

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**siteid** | **int** | One of your sites. |
**adzone_typeid** | **int** | Numeric type id (&#x60;id&#x60; in /api/v1/lookups/adspace-types). Default 2 (bannerads). | [optional] [default to 2]
**sizeid** | **int** | Banner size id (/api/v1/lookups/banner-sizes) — required for banner types. | [optional] [default to 0]
**items** | [**\GoAdServerApi\Model\ApiV1CreativesIdNamePutRequest[]**](ApiV1CreativesIdNamePutRequest.md) |  |

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
