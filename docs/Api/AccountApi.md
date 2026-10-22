# GoAdServerApi\AccountApi

Identity, profile, settings, usage and per-member preferences.  Read-heavy on purpose. Anything that grants access or changes what the account is billed for stays in the panel: API keys cannot be listed, minted, revealed or revoked through the API (a key that could mint keys would escalate past its own scopes), members and their rights cannot be changed, and the email verification-code flow is not exposed. Profile and settings are readable but not writable here.

All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1AccountFeaturesGet()**](AccountApi.md#apiV1AccountFeaturesGet) | **GET** /api/v1/account/features | Self-service features and their state. |
| [**apiV1AccountGet()**](AccountApi.md#apiV1AccountGet) | **GET** /api/v1/account | Identity check — returns the authenticated user + scopes of the calling key. |
| [**apiV1AccountMembershipsGet()**](AccountApi.md#apiV1AccountMembershipsGet) | **GET** /api/v1/account/memberships | Accounts this login can act on. |
| [**apiV1AccountNoticesGet()**](AccountApi.md#apiV1AccountNoticesGet) | **GET** /api/v1/account/notices | Account notices. |
| [**apiV1AccountPendingChangesGet()**](AccountApi.md#apiV1AccountPendingChangesGet) | **GET** /api/v1/account/pending-changes | Profile changes awaiting verification. |
| [**apiV1AccountPreferencesGet()**](AccountApi.md#apiV1AccountPreferencesGet) | **GET** /api/v1/account/preferences | Per-member preferences. |
| [**apiV1AccountPreferencesKeyPut()**](AccountApi.md#apiV1AccountPreferencesKeyPut) | **PUT** /api/v1/account/preferences/{key} | Set one preference. |
| [**apiV1AccountProfileGet()**](AccountApi.md#apiV1AccountProfileGet) | **GET** /api/v1/account/profile | The account&#39;s contact and company details. |
| [**apiV1AccountSalesReferralsGet()**](AccountApi.md#apiV1AccountSalesReferralsGet) | **GET** /api/v1/account/sales-referrals | Your sales referrals — clients assigned to you, their deposits and your commission. |
| [**apiV1AccountSettingsGet()**](AccountApi.md#apiV1AccountSettingsGet) | **GET** /api/v1/account/settings | Account settings. |
| [**apiV1AccountUiLayoutsGet()**](AccountApi.md#apiV1AccountUiLayoutsGet) | **GET** /api/v1/account/ui-layouts | Saved menu layouts. |
| [**apiV1AccountUiLayoutsScopeDelete()**](AccountApi.md#apiV1AccountUiLayoutsScopeDelete) | **DELETE** /api/v1/account/ui-layouts/{scope} | Reset a menu layout. |
| [**apiV1AccountUiLayoutsScopePut()**](AccountApi.md#apiV1AccountUiLayoutsScopePut) | **PUT** /api/v1/account/ui-layouts/{scope} | Save a menu layout. |
| [**apiV1AccountUsageGet()**](AccountApi.md#apiV1AccountUsageGet) | **GET** /api/v1/account/usage | Metered usage for a period. |
| [**apiV1AccountUsageSummaryGet()**](AccountApi.md#apiV1AccountUsageSummaryGet) | **GET** /api/v1/account/usage-summary | Inventory counts at a glance. |


## `apiV1AccountFeaturesGet()`

```php
apiV1AccountFeaturesGet(): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Self-service features and their state.

Which optional features the account has switched on. Read-only: turning one on changes what the account is billed for, so activation stays in the panel.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AccountApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1AccountFeaturesGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AccountApi->apiV1AccountFeaturesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response**](../Model/ApiV1TrackerTrackingLinksIdZoneCostsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AccountGet()`

```php
apiV1AccountGet(): \GoAdServerApi\Model\ApiV1AccountGet200Response
```

Identity check — returns the authenticated user + scopes of the calling key.

Minimal identity payload. Use this to verify a key is alive, discover which user it belongs to, and inspect what scopes the key was granted. No PII beyond name/email/company.  Key management (create/list/revoke) is **not** exposed via the public API — it requires a browser session. Visit MyAccount → API access in the panel.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AccountApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1AccountGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AccountApi->apiV1AccountGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1AccountGet200Response**](../Model/ApiV1AccountGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AccountMembershipsGet()`

```php
apiV1AccountMembershipsGet(): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Accounts this login can act on.

Every account the authenticated user is an owner or member of, which of them is active, and the scopes the membership grants. An API key is pinned to one account, so this reports rather than switches — the panel's account switch is not on the public API.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AccountApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1AccountMembershipsGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AccountApi->apiV1AccountMembershipsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response**](../Model/ApiV1TrackerTrackingLinksIdZoneCostsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AccountNoticesGet()`

```php
apiV1AccountNoticesGet(): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Account notices.

Standing notices about the account — verification still outstanding, a field awaiting review, a credential that needs attention.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AccountApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1AccountNoticesGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AccountApi->apiV1AccountNoticesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response**](../Model/ApiV1TrackerTrackingLinksIdZoneCostsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AccountPendingChangesGet()`

```php
apiV1AccountPendingChangesGet(): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Profile changes awaiting verification.

Changes submitted in the panel that are waiting on an emailed code. Read-only, and account-admin only — the same gate the panel applies. Cancelling or completing one is part of the verification flow and is not exposed here.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AccountApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1AccountPendingChangesGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AccountApi->apiV1AccountPendingChangesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response**](../Model/ApiV1TrackerTrackingLinksIdZoneCostsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AccountPreferencesGet()`

```php
apiV1AccountPreferencesGet(): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Per-member preferences.

The key-value preferences this membership has set — the default dashboard and similar per-person choices. Scoped to the member, not the account.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AccountApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1AccountPreferencesGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AccountApi->apiV1AccountPreferencesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response**](../Model/ApiV1TrackerTrackingLinksIdZoneCostsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AccountPreferencesKeyPut()`

```php
apiV1AccountPreferencesKeyPut($key, $api_v1_account_preferences_key_put_request): \GoAdServerApi\Model\ApiV1WebhooksIdDelete200Response
```

Set one preference.

Upserts a single key. Values are opaque to the API.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AccountApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$key = 'key_example'; // string | Preference key.
$api_v1_account_preferences_key_put_request = new \GoAdServerApi\Model\ApiV1AccountPreferencesKeyPutRequest(); // \GoAdServerApi\Model\ApiV1AccountPreferencesKeyPutRequest

try {
    $result = $apiInstance->apiV1AccountPreferencesKeyPut($key, $api_v1_account_preferences_key_put_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AccountApi->apiV1AccountPreferencesKeyPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **key** | **string**| Preference key. | |
| **api_v1_account_preferences_key_put_request** | [**\GoAdServerApi\Model\ApiV1AccountPreferencesKeyPutRequest**](../Model/ApiV1AccountPreferencesKeyPutRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1WebhooksIdDelete200Response**](../Model/ApiV1WebhooksIdDelete200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AccountProfileGet()`

```php
apiV1AccountProfileGet(): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

The account's contact and company details.

Name, company, address, language and contact fields. Read-only here — changing them goes through the panel's verification flow, which the public API deliberately does not expose.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AccountApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1AccountProfileGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AccountApi->apiV1AccountProfileGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response**](../Model/ApiV1TrackerTrackingLinksIdZoneCostsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AccountSalesReferralsGet()`

```php
apiV1AccountSalesReferralsGet()
```

Your sales referrals — clients assigned to you, their deposits and your commission.

For an account the network marked as a sales man (sales-referral module). `data.is_active: false` when it is not. Otherwise `data` carries `pct` (your commission %), `referral_url` (your signup link: an account registered through it is assigned to you automatically), `clients[]` (`userid`, `name`, `company`, `assigned_at`, `deposits`, `commission`, `currency`), `commissions[]` (one per paid deposit: `deposit_id`, `client_id`, `name`, `company`, `paid_date`, `deposit`, `pct`, `commission`, `currency`) and `totals[]` per currency (`commission`, `commission_month`, `deposits`). Read-only; the network pays commission out itself. Same handler as the panel's Referrals tab.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AccountApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $apiInstance->apiV1AccountSalesReferralsGet();
} catch (Exception $e) {
    echo 'Exception when calling AccountApi->apiV1AccountSalesReferralsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

void (empty response body)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AccountSettingsGet()`

```php
apiV1AccountSettingsGet(): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Account settings.

Currency, payout preferences, email opt-ins and the account's default tracking parameters.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AccountApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1AccountSettingsGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AccountApi->apiV1AccountSettingsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response**](../Model/ApiV1TrackerTrackingLinksIdZoneCostsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AccountUiLayoutsGet()`

```php
apiV1AccountUiLayoutsGet(): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Saved menu layouts.

Returns the tenant's layout, this member's own overrides, and whether the caller may edit the tenant-wide one. See docs/UI_LAYOUT.md.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AccountApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1AccountUiLayoutsGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AccountApi->apiV1AccountUiLayoutsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response**](../Model/ApiV1TrackerTrackingLinksIdZoneCostsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AccountUiLayoutsScopeDelete()`

```php
apiV1AccountUiLayoutsScopeDelete($scope): \GoAdServerApi\Model\ApiV1WebhooksIdDelete200Response
```

Reset a menu layout.

Drops the override so the scope falls back to the tenant default.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AccountApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$scope = 'scope_example'; // string | Layout scope. `nav.user` — the only scope this API accepts.

try {
    $result = $apiInstance->apiV1AccountUiLayoutsScopeDelete($scope);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AccountApi->apiV1AccountUiLayoutsScopeDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **scope** | **string**| Layout scope. &#x60;nav.user&#x60; — the only scope this API accepts. | |

### Return type

[**\GoAdServerApi\Model\ApiV1WebhooksIdDelete200Response**](../Model/ApiV1WebhooksIdDelete200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AccountUiLayoutsScopePut()`

```php
apiV1AccountUiLayoutsScopePut($scope, $api_v1_account_ui_layouts_scope_put_request): \GoAdServerApi\Model\ApiV1WebhooksIdDelete200Response
```

Save a menu layout.

Writes the layout for one scope. Saving the tenant-wide layout needs the right the panel checks — `can_edit_tenant` in the read tells you whether this call will be accepted.  `scope` is a registry value, not free text: `nav.user` is the only one this API accepts. `nav.admin` exists in the same table but belongs to the admin panel, and the account API refuses it (400 `invalid_scope`) so a member cannot reach into the admin menu.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AccountApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$scope = 'scope_example'; // string | Layout scope, e.g. the panel area the menu belongs to.
$api_v1_account_ui_layouts_scope_put_request = new \GoAdServerApi\Model\ApiV1AccountUiLayoutsScopePutRequest(); // \GoAdServerApi\Model\ApiV1AccountUiLayoutsScopePutRequest

try {
    $result = $apiInstance->apiV1AccountUiLayoutsScopePut($scope, $api_v1_account_ui_layouts_scope_put_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AccountApi->apiV1AccountUiLayoutsScopePut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **scope** | **string**| Layout scope, e.g. the panel area the menu belongs to. | |
| **api_v1_account_ui_layouts_scope_put_request** | [**\GoAdServerApi\Model\ApiV1AccountUiLayoutsScopePutRequest**](../Model/ApiV1AccountUiLayoutsScopePutRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1WebhooksIdDelete200Response**](../Model/ApiV1WebhooksIdDelete200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AccountUsageGet()`

```php
apiV1AccountUsageGet($datestart, $dateend, $date_group): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Metered usage for a period.

What the account has consumed of the metered features it is billed for.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AccountApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$datestart = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | First day (YYYY-MM-DD). Default: 6 days ago.
$dateend = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | Last day (YYYY-MM-DD). Default: today.
$date_group = 'day'; // string | One row per day / month / year, or one total row for the whole range.

try {
    $result = $apiInstance->apiV1AccountUsageGet($datestart, $dateend, $date_group);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AccountApi->apiV1AccountUsageGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **datestart** | **\DateTime**| First day (YYYY-MM-DD). Default: 6 days ago. | [optional] |
| **dateend** | **\DateTime**| Last day (YYYY-MM-DD). Default: today. | [optional] |
| **date_group** | **string**| One row per day / month / year, or one total row for the whole range. | [optional] [default to &#39;day&#39;] |

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response**](../Model/ApiV1TrackerTrackingLinksIdZoneCostsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1AccountUsageSummaryGet()`

```php
apiV1AccountUsageSummaryGet(): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Inventory counts at a glance.

Campaign, website and ad-zone counts plus which roles the account holds — the cheapest way to size an account before pulling its listings.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AccountApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1AccountUsageSummaryGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AccountApi->apiV1AccountUsageSummaryGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response**](../Model/ApiV1TrackerTrackingLinksIdZoneCostsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
