# ApiV1CampaignsTypeIdBidsPatchRequest

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**mode** | **string** |  |
**value** | **float** | Required when mode&#x3D;set. New absolute bid (&gt;&#x3D; 0). | [optional]
**pct** | **float** | Required when mode&#x3D;pct_inc / pct_dec. Percent change (&gt; 0). | [optional]
**ad_ids** | **int[]** | Optional. Subset of ad ids to update. Omit/empty for \&quot;all ads\&quot;. | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
