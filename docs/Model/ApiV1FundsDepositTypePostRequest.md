# ApiV1FundsDepositTypePostRequest

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**amount** | **float** | Deposit amount in currency units. Required for pay types and &#x60;advertiser&#x60;; not used for bonus / coupon. | [optional]
**bonus** | **string** | Bonus code (type &#x60;bonus&#x60;). | [optional]
**code** | **string** | Voucher code (type &#x60;coupon&#x60;). | [optional]
**connected_users** | **int** | Target user id (type &#x60;advertiser&#x60;). | [optional]
**whitebit_mode** | **string** | WhiteBit only (default crypto). | [optional]
**whitebit_ticker** | **string** | WhiteBit only (default USDT). | [optional]
**whitebit_network** | **string** | WhiteBit only (default TRC20). | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
