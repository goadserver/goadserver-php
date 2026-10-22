# ApiV1FiltersTypeIdPutRequest

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**name** | **string** |  | [optional]
**items** | **string[]** |  | [optional]
**ad_type** | **int** | Block lists and domain_incl only. | [optional]
**campaign_ids** | **int[]** |  | [optional]
**campaign_type** | **string** | Block lists only; allow-lists always link RON campaigns. | [optional]
**ip_list** | **string** | type&#x3D;ip only. | [optional]
**replace_ip_ranges** | **bool** | type&#x3D;ip only; needs ip_list. | [optional] [default to false]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
