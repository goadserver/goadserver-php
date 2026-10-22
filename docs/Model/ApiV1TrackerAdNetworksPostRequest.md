# ApiV1TrackerAdNetworksPostRequest

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**name** | **string** |  |
**networkid** | **int** | Id of the network&#39;s reporting integration; 0 &#x3D; none. | [optional]
**currency** | **string** | Three-letter currency the network reports in, lower-case (e.g. usd). | [optional]
**apikey** | **string** | Credential for the network&#39;s reporting API, when it has one. | [optional]
**api_data** | **string** | Extra API settings as a JSON string (per-network fields): &#x60;{\&quot;username\&quot;}&#x60; for traforama (the account e-mail); &#x60;{\&quot;username\&quot;,\&quot;password\&quot;}&#x60; for bidvertiser (the Bidvertiser login - its API needs it next to &#x60;apikey&#x60; for its access token). | [optional]
**low_balance_threshold** | **float** | Low-balance alert line in the account&#39;s currency; 0 &#x3D; off. | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
