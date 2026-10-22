# UserWebhook

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**id** | **int** |  | [optional]
**url** | **string** |  | [optional]
**events** | **string[]** |  | [optional]
**description** | **string** |  | [optional]
**status** | **string** |  | [optional]
**paused_reason** | **string** | &#x60;auto_failing&#x60; when paused after 20 failures in a row; empty when paused by hand. | [optional]
**fail_count** | **int** |  | [optional]
**credential** | **string** | What created it — its events follow that credential&#39;s access. | [optional]
**api_key_id** | **int** |  | [optional]
**api_key_name** | **string** |  | [optional]
**app_id** | **int** |  | [optional]
**app_name** | **string** |  | [optional]
**credential_active** | **bool** | false once the key is revoked or the app disconnected — nothing is sent then. | [optional]
**key_id** | **string** | Current signing secret generation (X-Integration-Key-Id). | [optional]
**previous_key_id** | **string** |  | [optional]
**previous_valid_until** | **\DateTime** |  | [optional]
**waiting** | **int** | Deliveries queued (held while paused). | [optional]
**created_at** | **\DateTime** |  | [optional]
**updated_at** | **\DateTime** |  | [optional]
**secret** | **string** | Only on create and rotate-secret. | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
