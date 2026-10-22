# ApiV1FundsTransactionsGet200ResponseDataInner

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**id** | **int** |  | [optional]
**dateadded** | **string** |  | [optional]
**date** | **string** |  | [optional]
**money** | **int** | Cents. | [optional]
**amount** | **float** | Currency units. | [optional]
**used_currency** | **string** |  | [optional]
**paytype** | **int** |  | [optional]
**ispayed** | **int** |  | [optional]
**status** | **string** |  | [optional]
**description** | **string** |  | [optional]
**accepts_txn_hash** | **bool** | Open manual crypto or Payoneer deposit — takes a transaction hash / ID (POST /api/v1/funds/deposits/{id}/txn-hash). Absent otherwise. | [optional]
**txn_ref_kind** | **string** | With accepts_txn_hash: &#x60;hash&#x60; (crypto, checked on the blockchain) or &#x60;reference&#x60; (Payoneer transaction ID, stored as given). | [optional]
**paytype_key** | **string** | Pay type key of an open manual crypto or Payoneer deposit (for GET /api/v1/funds/deposit/{type}/{fid}). | [optional]
**txn_claim** | [**\GoAdServerApi\Model\FundsTxnClaim**](FundsTxnClaim.md) |  | [optional]
**accepts_proof** | **bool** | Open bank / Wise deposit on a tenant that takes a proof of payment (POST /api/v1/funds/deposits/{id}/proof). Absent otherwise. | [optional]
**proof** | [**\GoAdServerApi\Model\FundsDepositProof**](FundsDepositProof.md) |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
