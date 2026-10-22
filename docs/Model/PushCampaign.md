# PushCampaign

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**id** | **int** |  | [optional]
**name** | **string** | Internal label — not shown to subscribers. | [optional]
**title** | **string** | Notification title. | [optional]
**body** | **string** | Notification body. | [optional]
**icon** | **string** | Small icon URL. | [optional]
**image** | **string** | Large image URL. | [optional]
**url** | **string** | Click destination. | [optional]
**status** | **string** | Lifecycle state. Only draft / sent / failed campaigns can be (re)queued via /send. | [optional]
**target_count** | **int** | Subscribers matched at send time. | [optional]
**sent_count** | **int** |  | [optional]
**fail_count** | **int** |  | [optional]
**created_at** | **string** | YYYY-MM-DD HH:MM. | [optional]
**sent_at** | **string** | YYYY-MM-DD HH:MM, or empty if never sent. | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
