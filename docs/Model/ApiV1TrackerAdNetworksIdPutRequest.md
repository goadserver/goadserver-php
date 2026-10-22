# ApiV1TrackerAdNetworksIdPutRequest

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**name** | **string** |  | [optional]
**networkid** | **int** |  | [optional]
**currency** | **string** |  | [optional]
**is_active** | **bool** |  | [optional]
**apikey** | **string** |  | [optional]
**api_data** | **string** | Extra API settings as a JSON string (per-network fields): &#x60;{\&quot;username\&quot;}&#x60; for traforama (the account e-mail); &#x60;{\&quot;username\&quot;,\&quot;password\&quot;}&#x60; for bidvertiser (the Bidvertiser login - its API needs it next to &#x60;apikey&#x60; for its access token). | [optional]
**low_balance_threshold** | **float** | Low-balance alert line in the account&#39;s currency; 0 &#x3D; off. | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
