# FundsTxnClaim

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**txn_hash** | **string** | Crypto: canonical form, lowercase; Ethereum / BNB Chain with 0x. Payoneer: the transaction ID as given (trimmed). | [optional]
**network** | **string** | Empty when the tenant&#39;s generic USDT wallet has no network set, and on Payoneer deposits. | [optional]
**asset** | **string** |  | [optional]
**status** | **string** | &#x60;submitted&#x60;: Payoneer transaction ID, stored as a reference for the finance team, never checked automatically. | [optional]
**reason** | **string** | Human-readable result of the check. | [optional]
**amount** | **string** | Amount of the asset the transaction sent to the tenant&#39;s wallet (decimal string). | [optional]
**confirmations** | **int** | Block confirmations (Ethereum 12 / BNB Chain 15 / Bitcoin 2 needed; TRON is checked against the irreversible ledger and reports 0). | [optional]
**checked_at** | **int** | Unix time of the check. | [optional]
**explorer_url** | **string** | Public block explorer page of the transaction. | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
