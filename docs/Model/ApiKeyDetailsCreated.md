# ApiKeyDetailsCreated

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**id** | **int** |  | [optional]
**name** | **string** |  | [optional]
**prefix** | **string** |  | [optional]
**scopes** | **string[]** |  | [optional]
**ip_allowlist** | **string** | CSV of IPs/CIDRs, empty &#x3D; any | [optional]
**rate_limit_rpm** | **int** | 0 &#x3D; system default (60) | [optional]
**strict_fields** | **bool** | Unknown body fields are a 422 by default for this key (true for keys created from 2026-10-12). ?strict&#x3D;0/1 overrides per request. | [optional]
**expires_at** | **\DateTime** |  | [optional]
**last_used_at** | **\DateTime** |  | [optional]
**last_used_ip** | **string** |  | [optional]
**call_count** | **int** |  | [optional]
**revoked_at** | **\DateTime** |  | [optional]
**created_at** | **\DateTime** |  | [optional]
**key** | **string** | **Plaintext key — shown ONCE.** Save it now; it cannot be retrieved again. To rotate, create a new key and revoke this one. | [optional]
**message** | **string** |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
