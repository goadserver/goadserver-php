# ApiV1PublisherFinancePayoutsGet200ResponseDataInner

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**id** | **int** |  | [optional]
**transactionid** | **int** | Per-account payout / invoice number. | [optional]
**transacttype** | **int** | Payout method id; 0 &#x3D; transfer to advertiser budget. | [optional]
**dateadded** | **\DateTime** |  | [optional]
**money** | **int** | Amount, cents. | [optional]
**ispayed** | **int** |  | [optional]
**datepayed** | **string** |  | [optional]
**isunpayable** | **int** | 1 &#x3D; staff marked the request as not payable (e.g. unvalidated payment details). | [optional]
**txt** | **string** |  | [optional]
**refund_note** | **string** |  | [optional]
**paid_currency** | **string** |  | [optional]
**companyid** | **int** |  | [optional]
**description** | **string** |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
