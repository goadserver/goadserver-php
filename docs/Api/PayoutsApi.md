# GoAdServerApi\PayoutsApi

Publisher earnings and payouts: balance, earnings, transactions, payout requests, transfers to the advertiser wallet and the payout method. &#x60;payouts.read&#x60; reads; &#x60;payouts.write&#x60; requests a payout or a transfer; &#x60;payouts.method&#x60; changes where the money is paid (kept separate so a key that may ask for money cannot redirect it). Every money field states its unit (cents or currency units).

All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1PublisherFinanceEarningsGet()**](PayoutsApi.md#apiV1PublisherFinanceEarningsGet) | **GET** /api/v1/publisher/finance/earnings | Earnings per day, week or month. |
| [**apiV1PublisherFinanceGet()**](PayoutsApi.md#apiV1PublisherFinanceGet) | **GET** /api/v1/publisher/finance | Publisher balance and payout rules. |
| [**apiV1PublisherFinanceMonthEarningsGet()**](PayoutsApi.md#apiV1PublisherFinanceMonthEarningsGet) | **GET** /api/v1/publisher/finance/month-earnings | Monthly earnings statements. |
| [**apiV1PublisherFinancePayoutRequestPost()**](PayoutsApi.md#apiV1PublisherFinancePayoutRequestPost) | **POST** /api/v1/publisher/finance/payout-request | Request a payout of the balance. |
| [**apiV1PublisherFinancePayoutTypesGet()**](PayoutsApi.md#apiV1PublisherFinancePayoutTypesGet) | **GET** /api/v1/publisher/finance/payout-types | Payout methods this account may choose. |
| [**apiV1PublisherFinancePayoutsGet()**](PayoutsApi.md#apiV1PublisherFinancePayoutsGet) | **GET** /api/v1/publisher/finance/payouts | List payout requests and payouts. |
| [**apiV1PublisherFinanceSettingsGet()**](PayoutsApi.md#apiV1PublisherFinanceSettingsGet) | **GET** /api/v1/publisher/finance/settings | Payout method and payment details. |
| [**apiV1PublisherFinanceSettingsPut()**](PayoutsApi.md#apiV1PublisherFinanceSettingsPut) | **PUT** /api/v1/publisher/finance/settings | Change the payout method or payment details. |
| [**apiV1PublisherFinanceTransactionsGet()**](PayoutsApi.md#apiV1PublisherFinanceTransactionsGet) | **GET** /api/v1/publisher/finance/transactions | List payout transactions (with USD totals). |
| [**apiV1PublisherFinanceTransactionsIdGet()**](PayoutsApi.md#apiV1PublisherFinanceTransactionsIdGet) | **GET** /api/v1/publisher/finance/transactions/{id} | One payout transaction. |
| [**apiV1PublisherFinanceTransactionsIdInvoicePdfGet()**](PayoutsApi.md#apiV1PublisherFinanceTransactionsIdInvoicePdfGet) | **GET** /api/v1/publisher/finance/transactions/{id}/invoice-pdf | Download the self-billing invoice PDF of a paid payout. |
| [**apiV1PublisherFinanceTransferToAdvertiserPost()**](PayoutsApi.md#apiV1PublisherFinanceTransferToAdvertiserPost) | **POST** /api/v1/publisher/finance/transfer-to-advertiser | Move publisher balance to the advertiser budget. |


## `apiV1PublisherFinanceEarningsGet()`

```php
apiV1PublisherFinanceEarningsGet($from, $to, $group_by): \GoAdServerApi\Model\ApiV1PublisherFinanceEarningsGet200Response
```

Earnings per day, week or month.

Publisher earnings from the stats store, bucketed by `group_by`.  **Money units.** `amount` and `summary.total` are **whole currency units** rounded to 2 decimals (unlike the balance fields, which are cents). Recent hours may still be incomplete.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\PayoutsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$from = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Inclusive start (YYYY-MM-DD). Both `from` and `to` must be given, else the last 30 days are used.
$to = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Inclusive end (YYYY-MM-DD).
$group_by = 'day'; // string | `week` buckets are labelled `YYYY-WW` (ISO week), `month` buckets `YYYY-MM`.

try {
    $result = $apiInstance->apiV1PublisherFinanceEarningsGet($from, $to, $group_by);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PayoutsApi->apiV1PublisherFinanceEarningsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **from** | **\DateTime**| Inclusive start (YYYY-MM-DD). Both &#x60;from&#x60; and &#x60;to&#x60; must be given, else the last 30 days are used. | [optional] |
| **to** | **\DateTime**| Inclusive end (YYYY-MM-DD). | [optional] |
| **group_by** | **string**| &#x60;week&#x60; buckets are labelled &#x60;YYYY-WW&#x60; (ISO week), &#x60;month&#x60; buckets &#x60;YYYY-MM&#x60;. | [optional] [default to &#39;day&#39;] |

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherFinanceEarningsGet200Response**](../Model/ApiV1PublisherFinanceEarningsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1PublisherFinanceGet()`

```php
apiV1PublisherFinanceGet(): \GoAdServerApi\Model\ApiV1PublisherFinanceGet200Response
```

Publisher balance and payout rules.

The publisher's current balance, the sum of open payout requests, the total already paid out, and the network's payout rules — the header of the panel's Billing page.  **Money units.** `balance.current`, `balance.pending` and `balance.total_earned` are **cents** of the network's main currency. `payout_threshold` is **whole currency units** (50 = 50.00).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\PayoutsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1PublisherFinanceGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PayoutsApi->apiV1PublisherFinanceGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherFinanceGet200Response**](../Model/ApiV1PublisherFinanceGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1PublisherFinanceMonthEarningsGet()`

```php
apiV1PublisherFinanceMonthEarningsGet(): \GoAdServerApi\Model\ApiV1PublisherFinanceMonthEarningsGet200ResponseInner[]
```

Monthly earnings statements.

One row per month from the monthly earnings ledger (the panel's Earnings tab), newest first. Not paginated.  **Money units.** `earned`, `pro_earned`, `ad_earned` and `total_earned` are **cents**.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\PayoutsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1PublisherFinanceMonthEarningsGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PayoutsApi->apiV1PublisherFinanceMonthEarningsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherFinanceMonthEarningsGet200ResponseInner[]**](../Model/ApiV1PublisherFinanceMonthEarningsGet200ResponseInner.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1PublisherFinancePayoutRequestPost()`

```php
apiV1PublisherFinancePayoutRequestPost($api_v1_publisher_finance_payout_request_post_request): \GoAdServerApi\Model\ApiV1PublisherFinancePayoutRequestPost201Response
```

Request a payout of the balance.

Creates a payout request (paid out by staff, by the method in the payout settings) and deducts the amount from the balance at once.  **Money units.** The request `amount` is **whole currency units** (`12.50`); every amount in the response is **cents**.  Rules (same as the panel):  - `total_amount: true` requests the whole balance; otherwise `amount`   is required and is capped to the balance. - The balance must reach the network `payout_threshold`, and the   requested amount must reach the larger of that threshold and the   payout method's `min_payout`. - Only one open request at a time. - A fee is deducted when the method has one configured for amounts   below its `fee_below`; `net_amount` is what is paid. - Payouts go to the payment details on file. If those are not yet   approved by staff (`isvalidbanking: 0` in the settings) the request   is still recorded, and staff validate the details before paying.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\PayoutsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_publisher_finance_payout_request_post_request = {"total_amount":true}; // \GoAdServerApi\Model\ApiV1PublisherFinancePayoutRequestPostRequest

try {
    $result = $apiInstance->apiV1PublisherFinancePayoutRequestPost($api_v1_publisher_finance_payout_request_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PayoutsApi->apiV1PublisherFinancePayoutRequestPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_publisher_finance_payout_request_post_request** | [**\GoAdServerApi\Model\ApiV1PublisherFinancePayoutRequestPostRequest**](../Model/ApiV1PublisherFinancePayoutRequestPostRequest.md)|  | [optional] |

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherFinancePayoutRequestPost201Response**](../Model/ApiV1PublisherFinancePayoutRequestPost201Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1PublisherFinancePayoutTypesGet()`

```php
apiV1PublisherFinancePayoutTypesGet(): \GoAdServerApi\Model\ApiV1PublisherFinancePayoutTypesGet200ResponseInner[]
```

Payout methods this account may choose.

The methods offered in the panel's payout-method picker — active on the tenant, allowed by the account's rights. The advertiser-budget method is not listed here (use `POST …/transfer-to-advertiser`). `id` is the value for `payouttype` in `PUT /api/v1/publisher/finance/settings`; any other id is refused there.  **Money units.** `min_payout` is **whole currency units**.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\PayoutsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1PublisherFinancePayoutTypesGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PayoutsApi->apiV1PublisherFinancePayoutTypesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherFinancePayoutTypesGet200ResponseInner[]**](../Model/ApiV1PublisherFinancePayoutTypesGet200ResponseInner.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1PublisherFinancePayoutsGet()`

```php
apiV1PublisherFinancePayoutsGet($page, $per_page, $sort_by, $sort_dir, $filter_id, $filter_ispayed, $filter_transacttype): \GoAdServerApi\Model\ApiV1PublisherFinancePayoutsGet200Response
```

List payout requests and payouts.

Rows of `publisher_transactions` — every payout request (paid or not) and every transfer to the advertiser budget. Paginated.  **Money units.** `money` is **cents** in the network's main currency; `paid_currency` is the currency the payout is made in.  `transacttype` is the payout method id (see `GET /api/v1/publisher/finance/payout-types`); `0` is a transfer to the advertiser budget.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\PayoutsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$page = 1; // int
$per_page = 20; // int
$sort_by = 'id'; // string
$sort_dir = 'desc'; // string
$filter_id = 56; // int
$filter_ispayed = 56; // int
$filter_transacttype = 56; // int

try {
    $result = $apiInstance->apiV1PublisherFinancePayoutsGet($page, $per_page, $sort_by, $sort_dir, $filter_id, $filter_ispayed, $filter_transacttype);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PayoutsApi->apiV1PublisherFinancePayoutsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **page** | **int**|  | [optional] [default to 1] |
| **per_page** | **int**|  | [optional] [default to 20] |
| **sort_by** | **string**|  | [optional] [default to &#39;id&#39;] |
| **sort_dir** | **string**|  | [optional] [default to &#39;desc&#39;] |
| **filter_id** | **int**|  | [optional] |
| **filter_ispayed** | **int**|  | [optional] |
| **filter_transacttype** | **int**|  | [optional] |

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherFinancePayoutsGet200Response**](../Model/ApiV1PublisherFinancePayoutsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1PublisherFinanceSettingsGet()`

```php
apiV1PublisherFinanceSettingsGet(): \GoAdServerApi\Model\ApiV1PublisherFinanceSettingsGet200Response
```

Payout method and payment details.

The account's payout settings. When none were ever saved, the defaults are returned with `payouttype: -1`.  **Money units.** `minimum_payout` is **whole currency units**.  Read-only here: `isvalidbanking` (staff approval of the payment details — reset whenever the destination changes), `isvalidcompany`, `last_time_validated`, `allowweeklypayments` (whether `directpayout: 2` is permitted) and the invoice-address block (`inv_*`, `useaccountadress`), which is edited through the account settings, not here.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\PayoutsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1PublisherFinanceSettingsGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PayoutsApi->apiV1PublisherFinanceSettingsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherFinanceSettingsGet200Response**](../Model/ApiV1PublisherFinanceSettingsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1PublisherFinanceSettingsPut()`

```php
apiV1PublisherFinanceSettingsPut($api_v1_publisher_finance_settings_put_request): \GoAdServerApi\Model\ApiV1RetargetingPixelsIdDelete200Response
```

Change the payout method or payment details.

Partial update: send only the fields to change. Needs its own scope, `payouts.method` — a key holding `payouts.write` (request a payout) cannot redirect where the money goes.  Same rules as the panel's Billing → Payout settings save:  - `payouttype` must be one of `GET …/payout-types` (or ≤ 0). - `minimum_payout` (**whole currency units**) may not be below the   selected method's `min_payout`. - `p_currency` must be `eur` or `usd`. - `directpayout: 2` (weekly) silently becomes `0` without the   `weekly_payout` right; values outside 0–2 become `0`. - IBAN / BIC are stored uppercased, IBAN without separators. Strings   longer than the column are truncated to its width. - **Changing a destination** (`bankaccountnumber`, `ibancode`,   `routingnumber`, `paypaladress`, `paxumadress`, `payoneeradress`,   `paymentdata`) **resets `isvalidbanking` to 0**: the new details   need staff approval again before payouts are made to them.  Neither the panel nor this endpoint asks for 2FA or an email confirmation. Read-only fields of the GET (`isvalidbanking`, `isvalidcompany`, `last_time_validated`, `allowweeklypayments`) and the invoice-address block (`inv_*`, `useaccountadress` — edited via the account settings) are not accepted: strip them before sending a GET body back, or the call answers 422 `unknown_fields`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\PayoutsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_publisher_finance_settings_put_request = {"payouttype":1,"ibancode":"NL91 ABNA 0417 1643 00","biccode":"abnanl2a","bankaccountname":"Example Media BV","minimum_payout":100}; // \GoAdServerApi\Model\ApiV1PublisherFinanceSettingsPutRequest

try {
    $result = $apiInstance->apiV1PublisherFinanceSettingsPut($api_v1_publisher_finance_settings_put_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PayoutsApi->apiV1PublisherFinanceSettingsPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_publisher_finance_settings_put_request** | [**\GoAdServerApi\Model\ApiV1PublisherFinanceSettingsPutRequest**](../Model/ApiV1PublisherFinanceSettingsPutRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1RetargetingPixelsIdDelete200Response**](../Model/ApiV1RetargetingPixelsIdDelete200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1PublisherFinanceTransactionsGet()`

```php
apiV1PublisherFinanceTransactionsGet($page, $per_page, $sort_by, $sort_dir, $filter_id, $filter_ispayed, $filter_transacttype, $filter_dateadded): \GoAdServerApi\Model\ApiV1PublisherFinanceTransactionsGet200Response
```

List payout transactions (with USD totals).

Same rows as `GET /api/v1/publisher/finance/payouts` with the field names the panel's Transactions tab uses (`ispaid`, `datepaid`) plus the USD conversion of paid rows.  **Money units.** `money` and `totalusd` are **cents**; `usdrate` is the conversion rate applied.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\PayoutsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$page = 1; // int
$per_page = 20; // int
$sort_by = 'id'; // string
$sort_dir = 'desc'; // string
$filter_id = 56; // int
$filter_ispayed = 56; // int
$filter_transacttype = 56; // int
$filter_dateadded = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime

try {
    $result = $apiInstance->apiV1PublisherFinanceTransactionsGet($page, $per_page, $sort_by, $sort_dir, $filter_id, $filter_ispayed, $filter_transacttype, $filter_dateadded);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PayoutsApi->apiV1PublisherFinanceTransactionsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **page** | **int**|  | [optional] [default to 1] |
| **per_page** | **int**|  | [optional] [default to 20] |
| **sort_by** | **string**|  | [optional] [default to &#39;id&#39;] |
| **sort_dir** | **string**|  | [optional] [default to &#39;desc&#39;] |
| **filter_id** | **int**|  | [optional] |
| **filter_ispayed** | **int**|  | [optional] |
| **filter_transacttype** | **int**|  | [optional] |
| **filter_dateadded** | **\DateTime**|  | [optional] |

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherFinanceTransactionsGet200Response**](../Model/ApiV1PublisherFinanceTransactionsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1PublisherFinanceTransactionsIdGet()`

```php
apiV1PublisherFinanceTransactionsIdGet($id): \GoAdServerApi\Model\ApiV1PublisherFinanceTransactionsIdGet200Response
```

One payout transaction.

A single row of the caller's `publisher_transactions`. **`money` is cents.** Another account's id answers 404.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\PayoutsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $result = $apiInstance->apiV1PublisherFinanceTransactionsIdGet($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PayoutsApi->apiV1PublisherFinanceTransactionsIdGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherFinanceTransactionsIdGet200Response**](../Model/ApiV1PublisherFinanceTransactionsIdGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1PublisherFinanceTransactionsIdInvoicePdfGet()`

```php
apiV1PublisherFinanceTransactionsIdInvoicePdfGet($id): \SplFileObject
```

Download the self-billing invoice PDF of a paid payout.

Returns `application/pdf` as an attachment named after the invoice number. Only **paid** payouts have an invoice; an unpaid or unknown id answers 404.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\PayoutsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $result = $apiInstance->apiV1PublisherFinanceTransactionsIdInvoicePdfGet($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PayoutsApi->apiV1PublisherFinanceTransactionsIdInvoicePdfGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |

### Return type

**\SplFileObject**

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/pdf`, `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1PublisherFinanceTransferToAdvertiserPost()`

```php
apiV1PublisherFinanceTransferToAdvertiserPost($api_v1_publisher_finance_transfer_to_advertiser_post_request): \GoAdServerApi\Model\ApiV1PublisherFinanceTransferToAdvertiserPost200Response
```

Move publisher balance to the advertiser budget.

Moves money from the publisher balance to the same account's advertiser budget, immediately (recorded as a paid transaction with `transacttype: 0`). Only for accounts that are also advertisers on a tenant offering the advertiser-budget method — see `can_transfer_to_advertiser` in `GET /api/v1/publisher/finance`.  **Money units.** Request `amount` and response `amount_transferred` are both **whole currency units**.  The amount must reach the network `payout_threshold`; a larger amount than the balance is capped to the balance.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\PayoutsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_publisher_finance_transfer_to_advertiser_post_request = {"amount":75.0}; // \GoAdServerApi\Model\ApiV1PublisherFinanceTransferToAdvertiserPostRequest

try {
    $result = $apiInstance->apiV1PublisherFinanceTransferToAdvertiserPost($api_v1_publisher_finance_transfer_to_advertiser_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PayoutsApi->apiV1PublisherFinanceTransferToAdvertiserPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_publisher_finance_transfer_to_advertiser_post_request** | [**\GoAdServerApi\Model\ApiV1PublisherFinanceTransferToAdvertiserPostRequest**](../Model/ApiV1PublisherFinanceTransferToAdvertiserPostRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherFinanceTransferToAdvertiserPost200Response**](../Model/ApiV1PublisherFinanceTransferToAdvertiserPost200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
