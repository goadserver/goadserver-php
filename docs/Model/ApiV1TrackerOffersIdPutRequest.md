# ApiV1TrackerOffersIdPutRequest

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**name** | **string** |  | [optional]
**destination_url** | **string** | Landing URL. Creates the offer&#39;s first URL row when the offer is created. | [optional]
**urls** | [**\GoAdServerApi\Model\ApiV1TrackerOffersPostRequestUrlsInner[]**](ApiV1TrackerOffersPostRequestUrlsInner.md) | Multiple landing URLs with rotation weights. Use instead of destination_url for a rotator. | [optional]
**payout** | **float** | Fixed payout per conversion, in account currency. | [optional]
**revshare_pct** | **float** | Revenue share percentage, when the offer pays a share rather than a fixed amount. | [optional]
**paytype** | **string** | Payout model — fixed CPA or revshare. | [optional]
**conv_type** | **string** | Conversion type letter (A–Z) this offer settles against. See /conversion-types. | [optional]
**country** | **string** | Comma-separated country codes the offer targets. Empty &#x3D; worldwide. | [optional]
**affiliate_network_id** | **int** | Owning affiliate network (see /affiliate-networks). 0 &#x3D; none. | [optional]
**failover_offer_id** | **int** | Offer to redirect to once this one caps out. 0 &#x3D; none. | [optional]
**daily_cap** | **int** | Conversions per day before the offer stops serving. 0 &#x3D; uncapped. | [optional]
**total_cap** | **int** | Lifetime conversion cap. 0 &#x3D; uncapped. | [optional]
**notes** | **string** |  | [optional]
**paused** | **bool** |  | [optional]
**as_failover** | **bool** | Mark the offer as a failover-only destination — it is not picked directly. | [optional]
**tags** | **string** | Comma-separated tracker tags. | [optional]
**offertypeid** | **int** |  | [optional]
**regionids** | **int[]** |  | [optional]
**device_type_ids** | **int[]** |  | [optional]
**osids** | **int[]** |  | [optional]
**browserids** | **int[]** |  | [optional]
**carrierids** | **int[]** |  | [optional]
**ispids** | **int[]** |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
