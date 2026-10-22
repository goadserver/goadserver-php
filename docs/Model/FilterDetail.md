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
**items** | **string[]** | Domain IDs / adzone IDs / ISP IDs / SubID strings — depends on filter type. Not present for &#x60;ip&#x60; (see ranges). | [optional]
**campaign_ids** | **string[]** | Block lists (&#x60;*_excl&#x60;): the campaigns in the filter&#39;s own list — read it together with &#x60;applies_to&#x60;. Allow-lists (&#x60;*_incl&#x60;) and &#x60;ip&#x60;: the RON campaigns whose allow-list / IP pool is this filter. | [optional]
**campaign_type** | **string** | Block lists only: which campaign kind the listed ids are. | [optional]
**applies_to** | **string** | Block lists only — what the ad server does with the row: &#x60;ad_type&#x60; 0 blocks across every campaign (campaign_ids are ignored); &#x60;ad_type&#x60; &gt; 0 with no campaign_ids covers every campaign of that ad type; otherwise only the listed ones. | [optional]
**ranges** | [**\GoAdServerApi\Model\FilterDetailAllOfRanges[]**](FilterDetailAllOfRanges.md) | IP ranges with their captured IP counts. (&#x60;type&#x3D;ip&#x60; only.) &#x60;range&#x60; is a display string — either a single address or &#x60;start - end&#x60;; &#x60;ip_start&#x60; / &#x60;ip_end&#x60; carry the bounds separately. &#x60;id&#x60; addresses the range in the /ranges/{rangeId} and /items/{item} routes. | [optional]
**imported** | **int** | &#x60;type&#x3D;ip&#x60; writes only: ranges imported by this call. | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
