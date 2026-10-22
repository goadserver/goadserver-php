# ApiV1AdspacesIdClonePostRequest

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**name** | **string** |  |
**copy_campaigns** | **bool** | Whitelisted campaigns + weights. | [optional] [default to false]
**copy_own_ads** | **bool** | The zone&#39;s own (house) ads. | [optional] [default to false]
**copy_settings** | **bool** | Costs, fixed payouts, subid and domain lists. | [optional] [default to false]
**start_active** | **bool** | Sets isvisible on the copy. | [optional] [default to false]
**copy_buying_policy** | **bool** | Media-buying policy (buying zones only). | [optional] [default to false]
**inherit_blocks** | **string** | Carry blocked placements to the copy&#39;s own network campaign on its first connection. | [optional] [default to '']

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
