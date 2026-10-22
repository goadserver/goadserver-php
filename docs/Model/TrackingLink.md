# TrackingLink

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**adzone_id** | **int** | The link&#39;s id — pass this as {id} on the link routes. | [optional]
**name** | **string** |  | [optional]
**siteid** | **int** | Traffic source the link belongs to. | [optional]
**source_name** | **string** |  | [optional]
**source_description** | **string** |  | [optional]
**source_template** | **string** | Macro template slug inherited from the source. | [optional]
**url_path** | **string** | The click URL path to hand the traffic source. | [optional]
**is_smartlink** | **bool** |  | [optional]
**offer_id** | **int** | Bound offer on a single-offer link. 0 on a smartlink. | [optional]
**offer_name** | **string** |  | [optional]
**offer_ids** | **int[]** | Offer pool on a smartlink. | [optional]
**weights** | **int[]** | Rotation weights, positionally matching offer_ids. | [optional]
**tokens** | **string[]** | Macro slots the click URL carries. | [optional]
**tags** | **string** |  | [optional]
**date_added** | **string** |  | [optional]
**is_deleted** | **bool** |  | [optional]
**adnetwork_id** | **int** | Bound ad network, 0 when unbound. | [optional]
**adnetwork_name** | **string** |  | [optional]
**adnetwork_campaignid** | **int** |  | [optional]
**adnetwork_campaign_kind** | **string** |  | [optional]
**adnetwork_state** | **string** |  | [optional]
**adnetwork_auto_api** | **bool** | Whether rules may push changes back to the network. | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
