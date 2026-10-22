# StatsResponse

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**from** | **\DateTime** |  | [optional]
**to** | **\DateTime** |  | [optional]
**group_by** | **string[]** |  | [optional]
**totals** | **array<string,float>** | Same metric keys as a row. A ratio that cannot be formed (e.g. advertiser &#x60;roi&#x60; below 1.00 of spend) is &#x60;null&#x60;. | [optional]
**rows** | **array[]** |  | [optional]
**page** | **int** |  | [optional]
**per_page** | **int** |  | [optional]
**total_rows** | **int** |  | [optional]
**timezone** | **string** | &#x60;/advertiser/stats&#x60;: the network&#39;s timezone — the clock dates and &#x60;interval&#x3D;hour&#x60; hours are counted in (and campaign &#x60;day_parting&#x60; runs on). | [optional]
**ignored_metrics** | **string[]** | Requested metric names the endpoint does not know (only where the endpoint documents lenient metric parsing; absent when empty). | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
