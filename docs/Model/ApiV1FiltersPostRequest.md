# ApiV1FiltersPostRequest

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**type** | **string** |  |
**name** | **string** |  |
**items** | **string[]** |  |
**campaign_ids** | **int[]** | Exclusion filters: limits this filter to these campaigns. Empty/omitted &#x3D; applies to all. | [optional]
**ad_type** | **int** | 0 &#x3D; all ad types, otherwise the adtypeid this filter applies to. | [optional] [default to 0]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
