# ApiV1TrackerTrackingLinksPostRequest

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**name** | **string** |  |
**source_id** | **int** | Traffic source this link belongs to (see /sources). |
**offer_id** | **int** | Single-offer link. | [optional]
**offer_ids** | **int[]** | Smartlink — several offers, narrowed per click by targeting and eCPM. | [optional]
**weights** | **int[]** | Rotation weights, positionally matching offer_ids. | [optional]
**tags** | **string** |  | [optional]
**tokens** | **string[]** | Macro slots to carry on the click URL — see /tokens. | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
