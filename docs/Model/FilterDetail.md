# FilterDetail

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**type** | **string** |  | [optional]
**id** | **int** |  | [optional]
**name** | **string** |  | [optional]
**is_active** | **bool** |  | [optional]
**ad_type** | **int** | 0 &#x3D; applies to all ad types, otherwise the adtypeid this filter targets. | [optional]
**item_count** | **int** | Number of items (domains/ISPs/etc.) currently in the filter. | [optional]
**items** | **string[]** | Domain IDs / adzone IDs / ISP IDs / SubID strings — depends on filter type. | [optional]
**campaign_ids** | **string[]** | Campaigns this filter applies to. Empty &#x3D; applies to all campaigns. (Exclusion filters only.) | [optional]
**ranges** | [**\GoAdServerApi\Model\FilterDetailAllOfRanges[]**](FilterDetailAllOfRanges.md) | IP CIDR ranges with their captured IP counts. (&#x60;type&#x3D;ip&#x60; only.) | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
