# ApiV1FundsDepositTypeFidGet200Response

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**view** | **string** |  | [optional]
**url** | **string** | Hosted checkout URL (gateway types). | [optional]
**deposit_id** | **int** |  | [optional]
**transaction_id** | **int** | Invoice / transfer reference. | [optional]
**money** | **int** | Cents | [optional]
**totalmoney** | **int** | Cents to pay. | [optional]
**used_currency** | **string** |  | [optional]
**bank_details** | **string[]** |  | [optional]
**payee** | **string** |  | [optional]
**wallet_id** | **string** |  | [optional]
**wallet_network** | **string** |  | [optional]
**accepts_txn_hash** | **bool** | Manual crypto wallet or Payoneer deposit: takes the payer&#39;s transaction hash / ID (POST /api/v1/funds/deposits/{id}/txn-hash). | [optional]
**txn_ref_kind** | **string** | &#x60;hash&#x60; (crypto, checked on the blockchain) or &#x60;reference&#x60; (Payoneer transaction ID, stored as given). | [optional]
**accepts_proof** | **bool** | Bank / Wise deposit on a tenant that takes a proof of payment (POST /api/v1/funds/deposits/{id}/proof). | [optional]
**proof** | [**\GoAdServerApi\Model\FundsDepositProof**](FundsDepositProof.md) |  | [optional]
**txn_claim** | [**\GoAdServerApi\Model\FundsTxnClaim**](FundsTxnClaim.md) | The transaction hash attached so far and its blockchain check; null when none. | [optional]
**invoice_paid** | **bool** |  | [optional]
**invoice_in_progress** | **bool** |  | [optional]
**unknown_invoice** | **bool** |  | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
