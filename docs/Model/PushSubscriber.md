# PushSubscriber

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**id** | **int** |  | [optional]
**captured** | **string** | Opt-in timestamp, YYYY-MM-DD HH:MM. | [optional]
**last_seen** | **string** | YYYY-MM-DD HH:MM. | [optional]
**spaceid** | **int** | The pushads adzone that captured the opt-in. | [optional]
**source_name** | **string** | Name of that adzone, empty when it no longer exists. | [optional]
**clickid** | **string** | Tracker clickid carried at opt-in, when there was one. | [optional]
**lang** | **string** | Browser language at opt-in. | [optional]
**ip** | **string** | Masked to a /24 (IPv4) or /48 (IPv6) prefix — the raw address never leaves the database. | [optional]
**is_active** | **bool** |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
