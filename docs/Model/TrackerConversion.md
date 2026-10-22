# TrackerConversion

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**id** | **int** |  | [optional]
**datetime** | **string** |  | [optional]
**clickid** | **string** | Pass to /conversions/{clickid}/postbacks for what was forwarded upstream. | [optional]
**offer_id** | **int** |  | [optional]
**offer_name** | **string** |  | [optional]
**link_id** | **int** |  | [optional]
**link_name** | **string** |  | [optional]
**payout** | **float** |  | [optional]
**state** | **int** | Settlement state. | [optional]
**status** | **string** | Human-readable form of state. | [optional]
**reason_id** | **int** | Why the conversion is held or rejected. 0 when clean. | [optional]
**reason** | **string** |  | [optional]
**conv_type** | **int** |  | [optional]
**conv_type_letter** | **string** | A–Z. | [optional]
**conv_type_name** | **string** | The account&#39;s label for that letter — see /conversion-types. | [optional]
**subid** | **string** |  | [optional]
**ipaddress** | **string** |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
