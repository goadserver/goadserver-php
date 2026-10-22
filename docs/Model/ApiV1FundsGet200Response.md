# ApiV1FundsGet200Response

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**currency** | **string** | The account currency, lowercase (eur when unset). | [optional]
**balance** | **float** | Currency units. | [optional]
**pending** | **float** | Unpaid deposits, currency units. | [optional]
**pending_count** | **int** |  | [optional]
**min_balance** | **float** | Low-balance warning threshold, currency units. | [optional]
**low_balance** | **bool** |  | [optional]
**last_deposit** | **\DateTime** | Date of the last paid deposit; omitted when there is none. | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
