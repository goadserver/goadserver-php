# GoAdServerApi\FundsApi

Account balance and deposit status.

All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1FundsBalanceSettingsPut()**](FundsApi.md#apiV1FundsBalanceSettingsPut) | **PUT** /api/v1/funds/balance-settings | Set the low-balance warning. |
| [**apiV1FundsDepositTypeFidGet()**](FundsApi.md#apiV1FundsDepositTypeFidGet) | **GET** /api/v1/funds/deposit/{type}/{fid} | Checkout data for an open deposit — or its paid state. |
| [**apiV1FundsDepositTypePost()**](FundsApi.md#apiV1FundsDepositTypePost) | **POST** /api/v1/funds/deposit/{type} | Open a deposit (or redeem a bonus / coupon code). |
| [**apiV1FundsDepositsIdDelete()**](FundsApi.md#apiV1FundsDepositsIdDelete) | **DELETE** /api/v1/funds/deposits/{id} | Cancel an unpaid deposit. |
| [**apiV1FundsDepositsIdProofGet()**](FundsApi.md#apiV1FundsDepositsIdProofGet) | **GET** /api/v1/funds/deposits/{id}/proof | Download the proof of payment you uploaded on a deposit. |
| [**apiV1FundsDepositsIdProofPost()**](FundsApi.md#apiV1FundsDepositsIdProofPost) | **POST** /api/v1/funds/deposits/{id}/proof | Upload the proof of payment of an open bank or Wise deposit. |
| [**apiV1FundsDepositsIdTxnHashPost()**](FundsApi.md#apiV1FundsDepositsIdTxnHashPost) | **POST** /api/v1/funds/deposits/{id}/txn-hash | Attach the transaction hash of a manual crypto deposit, or the transaction ID of a Payoneer deposit. |
| [**apiV1FundsGet()**](FundsApi.md#apiV1FundsGet) | **GET** /api/v1/funds | Current advertiser balance + pending deposits. |
| [**apiV1FundsPayTypesGet()**](FundsApi.md#apiV1FundsPayTypesGet) | **GET** /api/v1/funds/pay-types | Payment methods this account can deposit with. |
| [**apiV1FundsTransactionsGet()**](FundsApi.md#apiV1FundsTransactionsGet) | **GET** /api/v1/funds/transactions | Funds ledger — deposits, bonuses, transfers, refunds. |
| [**apiV1FundsTransferPost()**](FundsApi.md#apiV1FundsTransferPost) | **POST** /api/v1/funds/transfer | Move balance to a connected advertiser account. |


## `apiV1FundsBalanceSettingsPut()`

```php
apiV1FundsBalanceSettingsPut($api_v1_funds_balance_settings_put_request)
```

Set the low-balance warning.

`ad_sendbalancemail` (0/1) turns the low-balance email on or off; `ad_minbalance` is the threshold in **currency units** (your account currency, e.g. `50.00`; stored as cents). Send either or both — an omitted field keeps its value. Read them back as `min_balance` / `low_balance` on `GET /funds`. Same handler as the panel.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\FundsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_funds_balance_settings_put_request = {"ad_sendbalancemail":1,"ad_minbalance":50}; // \GoAdServerApi\Model\ApiV1FundsBalanceSettingsPutRequest

try {
    $apiInstance->apiV1FundsBalanceSettingsPut($api_v1_funds_balance_settings_put_request);
} catch (Exception $e) {
    echo 'Exception when calling FundsApi->apiV1FundsBalanceSettingsPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_funds_balance_settings_put_request** | [**\GoAdServerApi\Model\ApiV1FundsBalanceSettingsPutRequest**](../Model/ApiV1FundsBalanceSettingsPutRequest.md)|  | |

### Return type

void (empty response body)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1FundsDepositTypeFidGet()`

```php
apiV1FundsDepositTypeFidGet($type, $fid): \GoAdServerApi\Model\ApiV1FundsDepositTypeFidGet200Response
```

Checkout data for an open deposit — or its paid state.

Same handler as the panel's pay dialog. For an unpaid deposit it returns what is needed to pay: `view` names the method; hosted gateways (mollie, inxy, payhound, bybitpay, capitalist, trustpay, commercegate, yaadpay, whitebit fiat) return a checkout `url` to send the payer to (they come back to the tenant's panel); bank returns `bank_details` + `payee` + the transfer reference (`transaction_id`); wallet types return `wallet_id` / `wallet_network`; `wise` returns `bank_details` + `payee` like bank (the tenant's Wise account details for the deposit currency). Manual crypto wallets (btc_wallet, usdt_wallet*, usdc_wallet_erc20) also return `accepts_txn_hash: true` and the last `txn_claim` — after sending, POST the transaction hash to /api/v1/funds/deposits/{id}/txn-hash. `payoneer` returns the same (with `txn_ref_kind: reference`): POST the Payoneer transaction ID there. `bank` / `wise` return `accepts_proof: true` and the current `proof` where the tenant takes a proof of payment — upload it to /api/v1/funds/deposits/{id}/proof. `paypal`, `revolut` and `stripe` need the browser widget (client secret / order id) and are not completable by a server-side caller — use a URL-based type for agents. Amounts (`money`, `totalmoney`, `vat_amount`, `transaction_costs`) are cents.  Calling it can open a new order at the gateway (some types issue a fresh checkout each call), so it needs `funds.deposit`, not `funds.read`.  Once the deposit can no longer be paid the body is a single flag instead: `{\"invoice_paid\": true}` (paid), `{\"invoice_in_progress\": true}` (payment being processed) or `{\"unknown_invoice\": true}` (not yours / cancelled) — poll for `invoice_paid`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\FundsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = bank; // string | The pay type the deposit was opened with.
$fid = 56; // int | Deposit id from POST /api/v1/funds/deposit/{type}.

try {
    $result = $apiInstance->apiV1FundsDepositTypeFidGet($type, $fid);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling FundsApi->apiV1FundsDepositTypeFidGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**| The pay type the deposit was opened with. | |
| **fid** | **int**| Deposit id from POST /api/v1/funds/deposit/{type}. | |

### Return type

[**\GoAdServerApi\Model\ApiV1FundsDepositTypeFidGet200Response**](../Model/ApiV1FundsDepositTypeFidGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1FundsDepositTypePost()`

```php
apiV1FundsDepositTypePost($type, $api_v1_funds_deposit_type_post_request): \GoAdServerApi\Model\ApiV1FundsDepositTypePost200Response
```

Open a deposit (or redeem a bonus / coupon code).

Same handler as the panel. For a payment type from GET /api/v1/funds/pay-types this books an unpaid deposit of `amount` (currency units, >= the type's `min_amount`) and returns its id as `fid` — no payment happens yet. Then call GET /api/v1/funds/deposit/{type}/{fid} for the checkout URL / bank details, and poll it (or GET /api/v1/funds/transactions) for the paid state.  Special `{type}` values that settle directly and need no pay type: - `bonus` — redeem a bonus code: body `{bonus: \"CODE\"}`, no amount. - `coupon` — redeem a voucher: body `{code: \"CODE\"}`, no amount. - `advertiser` — transfer `amount` to a connected advertiser   (`connectedUsers` = its user id).  `amount_deduction` and `credit_no_invoice` exist but need an admin session — an API key always gets 403.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\FundsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = bank; // string | A `key` from GET /api/v1/funds/pay-types, or bonus / coupon / advertiser.
$api_v1_funds_deposit_type_post_request = {"amount":250}; // \GoAdServerApi\Model\ApiV1FundsDepositTypePostRequest

try {
    $result = $apiInstance->apiV1FundsDepositTypePost($type, $api_v1_funds_deposit_type_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling FundsApi->apiV1FundsDepositTypePost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**| A &#x60;key&#x60; from GET /api/v1/funds/pay-types, or bonus / coupon / advertiser. | |
| **api_v1_funds_deposit_type_post_request** | [**\GoAdServerApi\Model\ApiV1FundsDepositTypePostRequest**](../Model/ApiV1FundsDepositTypePostRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1FundsDepositTypePost200Response**](../Model/ApiV1FundsDepositTypePost200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1FundsDepositsIdDelete()`

```php
apiV1FundsDepositsIdDelete($id): \GoAdServerApi\Model\ApiV1RetargetingPixelsIdDelete200Response
```

Cancel an unpaid deposit.

Deletes an open deposit of yours (same handler as the panel). A paid deposit, or one a card / PayPal / Stripe payment is already processing, can't be cancelled (404).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\FundsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Deposit id (`fid`).

try {
    $result = $apiInstance->apiV1FundsDepositsIdDelete($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling FundsApi->apiV1FundsDepositsIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Deposit id (&#x60;fid&#x60;). | |

### Return type

[**\GoAdServerApi\Model\ApiV1RetargetingPixelsIdDelete200Response**](../Model/ApiV1RetargetingPixelsIdDelete200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1FundsDepositsIdProofGet()`

```php
apiV1FundsDepositsIdProofGet($id): \SplFileObject
```

Download the proof of payment you uploaded on a deposit.

Streams your uploaded proof inline with its type (`application/pdf`, `image/jpeg` or `image/png`). 404 when the deposit is not yours or has no proof.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\FundsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Deposit id (`fid`).

try {
    $result = $apiInstance->apiV1FundsDepositsIdProofGet($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling FundsApi->apiV1FundsDepositsIdProofGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Deposit id (&#x60;fid&#x60;). | |

### Return type

**\SplFileObject**

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/pdf`, `image/jpeg`, `image/png`, `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1FundsDepositsIdProofPost()`

```php
apiV1FundsDepositsIdProofPost($id, $file): \GoAdServerApi\Model\ApiV1FundsDepositsIdProofPost200Response
```

Upload the proof of payment of an open bank or Wise deposit.

Same handler as the panel's deposit list and pay dialog. After paying an open `bank` or `wise` deposit by transfer, upload the proof of payment (bank receipt / transfer confirmation) so the tenant's finance team can approve the deposit without waiting for the money to show up. Only where the tenant switched it on for that method — the deposit rows and the deposit payload say so with `accepts_proof: true`.  Multipart, field `file`: a PDF, JPG or PNG of at most 10 MB. The type is read from the file content, not its name. A new upload replaces the earlier proof; cancelling the deposit removes it. **The proof never credits the balance** — an admin approves the deposit. At most 30 uploads per hour per account.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\FundsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Deposit id (`fid`).
$file = '/path/to/file.txt'; // \SplFileObject | PDF, JPG or PNG, max 10 MB.

try {
    $result = $apiInstance->apiV1FundsDepositsIdProofPost($id, $file);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling FundsApi->apiV1FundsDepositsIdProofPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Deposit id (&#x60;fid&#x60;). | |
| **file** | **\SplFileObject****\SplFileObject**| PDF, JPG or PNG, max 10 MB. | |

### Return type

[**\GoAdServerApi\Model\ApiV1FundsDepositsIdProofPost200Response**](../Model/ApiV1FundsDepositsIdProofPost200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `multipart/form-data`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1FundsDepositsIdTxnHashPost()`

```php
apiV1FundsDepositsIdTxnHashPost($id, $api_v1_funds_deposits_id_txn_hash_post_request): \GoAdServerApi\Model\ApiV1FundsDepositsIdTxnHashPost200Response
```

Attach the transaction hash of a manual crypto deposit, or the transaction ID of a Payoneer deposit.

Same handler as the panel's pay dialog. After sending a manual crypto deposit (btc_wallet, usdt_wallet, usdt_wallet_trc20 / _bep20 / _erc20, usdc_wallet_erc20) to the tenant's wallet, post the transaction hash. It is looked up on the public blockchain and the result is stored on the deposit for the tenant's finance team.  **The hash never credits the balance.** An admin approves the deposit after the check (or with a written override). The check confirms: the tenant's wallet received the deposit's asset (the real USDT / USDC contract, or BTC), at least the invoice total, in a final block, sent no earlier than the deposit was opened.  `txn_claim.status`: `verified`, `pending` (not final yet — post the same hash again later to re-check), `mismatch` (wrong asset / wallet / amount / date — see `reason`), `not_found`, `unchecked` (the tenant's wallet or network is not configured) or `error` (the explorer could not be reached). Posting a different hash replaces the earlier one while the deposit is open. A hash already used on another deposit is refused. At most 20 checks per hour per account.  **Payoneer deposits** take the Payoneer transaction ID on the same route: 4–64 letters, digits and `. _ / # : -`, no spaces. It is stored as given for the finance team to match with the payment (`txn_claim.status: submitted`) and not checked automatically; one ID per deposit, an ID already on another deposit is refused, posting a new one replaces it while the deposit is open.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\FundsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Deposit id (`fid`).
$api_v1_funds_deposits_id_txn_hash_post_request = {"txn_hash":"d6f659cce4d1bf2f1c724593fde8d9bb7c0385d5224ad410557f85297cdc8f71"}; // \GoAdServerApi\Model\ApiV1FundsDepositsIdTxnHashPostRequest

try {
    $result = $apiInstance->apiV1FundsDepositsIdTxnHashPost($id, $api_v1_funds_deposits_id_txn_hash_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling FundsApi->apiV1FundsDepositsIdTxnHashPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Deposit id (&#x60;fid&#x60;). | |
| **api_v1_funds_deposits_id_txn_hash_post_request** | [**\GoAdServerApi\Model\ApiV1FundsDepositsIdTxnHashPostRequest**](../Model/ApiV1FundsDepositsIdTxnHashPostRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1FundsDepositsIdTxnHashPost200Response**](../Model/ApiV1FundsDepositsIdTxnHashPost200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1FundsGet()`

```php
apiV1FundsGet(): \GoAdServerApi\Model\ApiV1FundsGet200Response
```

Current advertiser balance + pending deposits.

Use this before deciding to launch or pause a campaign — `low_balance` is true when current balance is below the user-configured minimum.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\FundsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1FundsGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling FundsApi->apiV1FundsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1FundsGet200Response**](../Model/ApiV1FundsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1FundsPayTypesGet()`

```php
apiV1FundsPayTypesGet(): \GoAdServerApi\Model\ApiV1FundsPayTypesGet200Response
```

Payment methods this account can deposit with.

The `{type}` values POST /api/v1/funds/deposit/{type} accepts for this account: active on the tenant, allowed by the account's rights and offered in its currency. Same handler as the panel's Add Funds page. `initiates_payment: false` means the deposit only books an invoice to settle out of band (bank transfer, wallet address, Pay ID). `min_amount` is in currency units.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\FundsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1FundsPayTypesGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling FundsApi->apiV1FundsPayTypesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1FundsPayTypesGet200Response**](../Model/ApiV1FundsPayTypesGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1FundsTransactionsGet()`

```php
apiV1FundsTransactionsGet($from, $to, $filter_id, $filter_paytype, $sort_by, $sort_dir, $page, $per_page): \GoAdServerApi\Model\ApiV1FundsTransactionsGet200Response
```

Funds ledger — deposits, bonuses, transfers, refunds.

Paginated `advertiser_money` rows for the account (paid and unpaid, incl. transfers received). Same handler as the panel's Transactions tab. `money` is in cents; `amount` is the same value in currency units (negative for a transfer you sent). `status` is `pending`, `processing` or `completed`. `payment_methods` lists every paytype the history carries, for filtering with `filter_paytype`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\FundsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$from = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | dateadded >= from
$to = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | dateadded <= to
$filter_id = 56; // int
$filter_paytype = 56; // int | Numeric paytype (negative = internal: -1 bonus, -2 transfer, -3 flat refund, -4 coupon, -5 from publisher, -6 RON refund, -7 credit, -8 deduction).
$sort_by = 'dateadded'; // string
$sort_dir = 'desc'; // string
$page = 1; // int
$per_page = 20; // int

try {
    $result = $apiInstance->apiV1FundsTransactionsGet($from, $to, $filter_id, $filter_paytype, $sort_by, $sort_dir, $page, $per_page);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling FundsApi->apiV1FundsTransactionsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **from** | **\DateTime**| dateadded &gt;&#x3D; from | [optional] |
| **to** | **\DateTime**| dateadded &lt;&#x3D; to | [optional] |
| **filter_id** | **int**|  | [optional] |
| **filter_paytype** | **int**| Numeric paytype (negative &#x3D; internal: -1 bonus, -2 transfer, -3 flat refund, -4 coupon, -5 from publisher, -6 RON refund, -7 credit, -8 deduction). | [optional] |
| **sort_by** | **string**|  | [optional] [default to &#39;dateadded&#39;] |
| **sort_dir** | **string**|  | [optional] [default to &#39;desc&#39;] |
| **page** | **int**|  | [optional] [default to 1] |
| **per_page** | **int**|  | [optional] [default to 20] |

### Return type

[**\GoAdServerApi\Model\ApiV1FundsTransactionsGet200Response**](../Model/ApiV1FundsTransactionsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1FundsTransferPost()`

```php
apiV1FundsTransferPost($api_v1_funds_transfer_post_request)
```

Move balance to a connected advertiser account.

Transfers `amount` from your advertiser balance to another account your network has connected to yours. Every panel safeguard applies:  - the account needs the `transfer_money_other_advertiser` right   (403 otherwise); - `connectedUsers` must be on the account's connected-transfer list,   which only network staff maintain (403 otherwise); - `amount` is in **currency units** of your account currency, at   least 5.00; - the balance is checked and debited atomically (422 `insufficient`,   409 `balance_changed` on a race); the recipient is credited in   their own currency at the current rate, and a ledger row   (paytype −2) plus an action-log entry record it.  `funds.write` is required for every key path that moves money, including `POST /funds/deposit/advertiser`. Not idempotent — do not blindly retry a timed-out call; check `GET /funds/transactions`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\FundsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_funds_transfer_post_request = {"connectedUsers":10234,"amount":250}; // \GoAdServerApi\Model\ApiV1FundsTransferPostRequest

try {
    $apiInstance->apiV1FundsTransferPost($api_v1_funds_transfer_post_request);
} catch (Exception $e) {
    echo 'Exception when calling FundsApi->apiV1FundsTransferPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_funds_transfer_post_request** | [**\GoAdServerApi\Model\ApiV1FundsTransferPostRequest**](../Model/ApiV1FundsTransferPostRequest.md)|  | |

### Return type

void (empty response body)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
