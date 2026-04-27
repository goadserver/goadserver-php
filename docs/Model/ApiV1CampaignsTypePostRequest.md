# ApiV1CampaignsTypePostRequest

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**name** | **string** |  |
**ad_type** | **int** | See /campaigns/&lt;type&gt;/_schema for valid ids on this tenant. |
**daily_budget** | **float** |  | [optional]
**total_budget** | **float** |  | [optional]
**use_campaign_budget** | **bool** |  | [optional] [default to false]
**countries** | **string** |  | [optional]
**languages** | **string** | CSV of language ids; \&quot;0\&quot; &#x3D; all. | [optional]
**categories** | **string** |  | [optional]
**main_catid** | **int** |  | [optional]
**device_type_ids** | **string** |  | [optional]
**browser_ids** | **string** |  | [optional]
**os_ids** | **string** |  | [optional]
**isp_ids** | **string** |  | [optional]
**site_types** | **string** |  | [optional]
**regions** | **string** |  | [optional]
**display_times** | **string** |  | [optional]
**display_days** | **string** |  | [optional]
**use_capping** | **bool** |  | [optional]
**capping_hours** | **int** |  | [optional]
**capping_minutes** | **int** |  | [optional]
**domain_filter_id** | **int** |  | [optional]
**whitelist_id** | **int** |  | [optional]
**rt_include_id** | **int** |  | [optional]
**rt_exclude_id** | **int** |  | [optional]
**ip_pool_id** | **int** |  | [optional]
**fx_minprice** | **float** |  | [optional] [default to 0]
**is_paused** | **bool** | Start paused (default) so you can attach ads first. | [optional] [default to true]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
