# ApiV1CampaignsTypeIdActivePatch200Response

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**id** | **int** |  | [optional]
**is_active** | **bool** |  | [optional]
**changed** | **bool** |  | [optional]
**pending_state** | **int** | Only when &#x60;changed&#x60; is true. 0 &#x3D; applied. 1 (pause) or 3 (resume) &#x3D; the network validates campaign changes, so the toggle also went to an admin as Pending Pause / Pending Active; the admin keeps it or reverts it. An admin acting on the user&#39;s behalf never pends (0) and settles a pause / resume the user still has waiting. RON only. | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
