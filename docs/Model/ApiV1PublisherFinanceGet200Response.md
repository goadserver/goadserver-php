# ApiV1PublisherFinanceGet200Response

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**balance** | [**\GoAdServerApi\Model\ApiV1PublisherFinanceGet200ResponseBalance**](ApiV1PublisherFinanceGet200ResponseBalance.md) |  | [optional]
**payout_threshold** | **float** | Network minimum for a payout request, whole currency units. | [optional]
**manual_payouts_only** | **bool** | Payouts are only made on request (no automatic payout runs). | [optional]
**manual_admin_only** | **bool** | Only staff can create a payout; a publisher request is not offered in the panel. | [optional]
**can_transfer_to_advertiser** | **bool** | True when the tenant offers the advertiser-budget method AND this account is an advertiser — i.e. &#x60;POST …/transfer-to-advertiser&#x60; will not answer 403 not_advertiser. | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
