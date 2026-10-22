# PushDeliveryRule

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**id** | **int** |  | [optional]
**name** | **string** |  | [optional]
**segment_id** | **int** | PushSegment id the rule targets. 0 &#x3D; the whole active audience. | [optional]
**segment_name** | **string** | Empty when segment_id is 0. | [optional]
**cadence_hours** | **int** | How often the rule fires. Minimum 1; defaults to 24. | [optional]
**freq_cap_hours** | **int** | Minimum gap between two sends to the same subscriber. 0 &#x3D; uncapped. | [optional]
**max_per_run** | **int** | Cap on subscribers reached per run. 0 &#x3D; unlimited. | [optional]
**is_active** | **bool** |  | [optional]
**last_run_at** | **string** | YYYY-MM-DD HH:MM, empty if the rule has never run. | [optional]
**last_sent_count** | **int** | Deliveries accepted on the last run. | [optional]
**last_failed_count** | **int** | Deliveries rejected by the push service on the last run. | [optional]
**last_no_ads_count** | **int** | Subscribers skipped on the last run because the auction returned no ad. | [optional]
**created_at** | **string** | YYYY-MM-DD HH:MM. | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
