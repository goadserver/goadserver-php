# ApiKeyDetails

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**id** | **int** |  | [optional]
**name** | **string** |  | [optional]
**prefix** | **string** |  | [optional]
**scopes** | **string[]** |  | [optional]
**ip_allowlist** | **string** | CSV of IPs/CIDRs, empty &#x3D; any | [optional]
**rate_limit_rpm** | **int** | 0 &#x3D; system default (60) | [optional]
**expires_at** | **\DateTime** |  | [optional]
**last_used_at** | **\DateTime** |  | [optional]
**last_used_ip** | **string** |  | [optional]
**call_count** | **int** |  | [optional]
**revoked_at** | **\DateTime** |  | [optional]
**created_at** | **\DateTime** |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
