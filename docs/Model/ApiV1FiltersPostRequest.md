# ApiV1FiltersPostRequest

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**type** | [**\GoAdServerApi\Model\FilterType**](FilterType.md) |  |
**name** | **string** | HTML tags are stripped; unique per type. |
**items** | **string[]** | Required for every type but ip. | [optional]
**campaign_ids** | **int[]** | See Campaign scope above. Empty/omitted &#x3D; no campaign link (block lists: every campaign of ad_type). | [optional]
**campaign_type** | **string** | Block lists: the kind of the campaign_ids. Default ron (rtb when ad_type is 14). | [optional]
**ad_type** | **int** | Block lists and domain_incl: 0 &#x3D; all ad types / every campaign, otherwise the adtypeid this filter applies to. | [optional] [default to 0]
**ip_list** | **string** | type&#x3D;ip only: ranges, one per line. | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
