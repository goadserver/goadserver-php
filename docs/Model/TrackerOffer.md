# TrackerOffer

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**id** | **int** |  | [optional]
**name** | **string** |  | [optional]
**paytype** | **string** | Payout model — fixed CPA or revenue share. | [optional]
**fix_cpa** | **float** | Fixed payout per conversion when paytype is CPA. | [optional]
**countrys** | **string** | Comma-separated country codes the offer targets. Empty &#x3D; worldwide. | [optional]
**affiliate_network_id** | **int** | 0 when the offer belongs to no network. | [optional]
**network_name** | **string** | Resolved name for affiliate_network_id, empty when unset. | [optional]
**failover_offer_id** | **int** | Offer redirected to once this one caps out. 0 &#x3D; none. | [optional]
**failover_offer_name** | **string** |  | [optional]
**as_failover** | **bool** | Failover-only — never picked directly. | [optional]
**ispaused** | **bool** |  | [optional]
**dayactive** | **int** | Conversions counted against the daily cap today. | [optional]
**offertypeid** | **int** |  | [optional]
**tracker_tags** | **string** | Comma-separated tags. | [optional]
**groupid** | **string** |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
