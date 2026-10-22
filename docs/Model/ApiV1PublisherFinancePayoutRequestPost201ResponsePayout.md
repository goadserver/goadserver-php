# ApiV1PublisherFinancePayoutRequestPost201ResponsePayout

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**id** | **int** | Transaction id (see GET …/transactions/{id}). | [optional]
**amount** | **int** | Deducted from the balance, cents. | [optional]
**fee** | **int** | Method fee, cents. | [optional]
**net_amount** | **int** | amount − fee, cents. | [optional]
**paid_currency** | **string** |  | [optional]
**ispayed** | **int** |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
