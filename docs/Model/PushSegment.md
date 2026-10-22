# PushSegment

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**id** | **int** |  | [optional]
**name** | **string** | Segment label. | [optional]
**source_spaceid** | **int** | Only subscribers captured on this pushads adzone. 0 &#x3D; any. | [optional]
**domainid** | **int** | Only subscribers captured on this landing domain. 0 &#x3D; any. | [optional]
**clickid_only** | **bool** | Only subscribers that carried a tracker clickid at opt-in. | [optional]
**age_min_days** | **int** | Subscribed at least this many days ago. 0 &#x3D; no lower bound. | [optional]
**age_max_days** | **int** | Subscribed at most this many days ago. 0 &#x3D; no upper bound. | [optional]
**active_only** | **bool** | Exclude subscribers that have been deactivated. | [optional]
**subscriber_count** | **int** | Live count of subscribers the segment matches right now. | [optional]
**created_at** | **string** | YYYY-MM-DD HH:MM. | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
