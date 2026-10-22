# GoAdServerApi\LookupsApi

Reference data shared across endpoints — geo regions, etc.

All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1LookupsAdspaceTypesGet()**](LookupsApi.md#apiV1LookupsAdspaceTypesGet) | **GET** /api/v1/lookups/adspace-types | Adspace types the caller can publish on this tenant. |
| [**apiV1LookupsAdzoneLocationsGet()**](LookupsApi.md#apiV1LookupsAdzoneLocationsGet) | **GET** /api/v1/lookups/adzone-locations | Adzone locations for the tenant. |
| [**apiV1LookupsAdzonesGet()**](LookupsApi.md#apiV1LookupsAdzonesGet) | **GET** /api/v1/lookups/adzones | Find adzone ids (for adzone filters). |
| [**apiV1LookupsBannerSizesGet()**](LookupsApi.md#apiV1LookupsBannerSizesGet) | **GET** /api/v1/lookups/banner-sizes | Banner sizes for banner-flavored adspaces. |
| [**apiV1LookupsBrowsersGet()**](LookupsApi.md#apiV1LookupsBrowsersGet) | **GET** /api/v1/lookups/browsers | Browser ids (optionally scoped by device type). |
| [**apiV1LookupsCarriersGet()**](LookupsApi.md#apiV1LookupsCarriersGet) | **GET** /api/v1/lookups/carriers | Mobile carriers (ids for carrier targeting). |
| [**apiV1LookupsCategoriesGet()**](LookupsApi.md#apiV1LookupsCategoriesGet) | **GET** /api/v1/lookups/categories | Active sub-categories (optionally scoped to a main). |
| [**apiV1LookupsDeviceTypesGet()**](LookupsApi.md#apiV1LookupsDeviceTypesGet) | **GET** /api/v1/lookups/device-types | Active device types for this tenant. |
| [**apiV1LookupsDomainsGet()**](LookupsApi.md#apiV1LookupsDomainsGet) | **GET** /api/v1/lookups/domains | Resolve domains to the ids domain filters store. |
| [**apiV1LookupsIspsGet()**](LookupsApi.md#apiV1LookupsIspsGet) | **GET** /api/v1/lookups/isps | Find ISP ids (for ISP filters). |
| [**apiV1LookupsLinkTypesGet()**](LookupsApi.md#apiV1LookupsLinkTypesGet) | **GET** /api/v1/lookups/link-types | Active link types for the tenant. |
| [**apiV1LookupsMainCategoriesGet()**](LookupsApi.md#apiV1LookupsMainCategoriesGet) | **GET** /api/v1/lookups/main-categories | Active main categories for the tenant. |
| [**apiV1LookupsOperatingSystemsGet()**](LookupsApi.md#apiV1LookupsOperatingSystemsGet) | **GET** /api/v1/lookups/operating-systems | Operating-system ids (optionally scoped by device type). |
| [**apiV1LookupsPublishersGet()**](LookupsApi.md#apiV1LookupsPublishersGet) | **GET** /api/v1/lookups/publishers | Find publisher ids (for publisher_excl filters). |
| [**apiV1LookupsRatingsGet()**](LookupsApi.md#apiV1LookupsRatingsGet) | **GET** /api/v1/lookups/ratings | Active content ratings for the tenant. |
| [**apiV1LookupsReelTypesGet()**](LookupsApi.md#apiV1LookupsReelTypesGet) | **GET** /api/v1/lookups/reel-types | Active reel types for the tenant. |
| [**apiV1LookupsRegionsGet()**](LookupsApi.md#apiV1LookupsRegionsGet) | **GET** /api/v1/lookups/regions | Walk the geo region tree (one level per call). |
| [**apiV1LookupsWebsiteLanguagesGet()**](LookupsApi.md#apiV1LookupsWebsiteLanguagesGet) | **GET** /api/v1/lookups/website-languages | Supported website languages (ISO codes). |
| [**apiV1LookupsWebsiteTypesGet()**](LookupsApi.md#apiV1LookupsWebsiteTypesGet) | **GET** /api/v1/lookups/website-types | Active website types for the tenant. |


## `apiV1LookupsAdspaceTypesGet()`

```php
apiV1LookupsAdspaceTypesGet(): \GoAdServerApi\Model\ApiV1LookupsAdspaceTypesGet200Response
```

Adspace types the caller can publish on this tenant.

Returns the adspace types exposable via the public API for the current tenant + caller. Filters applied (same as the panel adspace-create page):  - `is_active = true` — tenant has enabled the type - `in_api = true` — type is exposable via the public API - `mod_enabled` chain enabled for the user - `need_rights` gate satisfied (when set)  Each entry carries the metadata needed to drive a create form:  - `id` / `key` / `name` / `adtype_id` - `settings_key` — the body key for the type-specific block   on `POST /api/v1/adspaces/{type}` (e.g. `banner_settings`,   `pop_settings`, `native_settings`). Empty string means the   type takes no nested settings block. - `selling_types` — `[\"ron\"]`, `[\"ron\",\"cpm\"]`, etc. - `use_min_price` / `use_smartzone` / `has_own_ads` / `has_preview` - `options` — config-driven flag bag (boolean toggles, banner-size   selects, country exclude lists, …) that map to top-level body   fields on create. The same `options` shape is mirrored on   `GET /api/v1/adspaces/{type}/_schema`.  Use the `key` value as the `{type}` path parameter on `GET /api/v1/adspaces/{type}/_schema` and `POST /api/v1/adspaces/{type}`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\LookupsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1LookupsAdspaceTypesGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling LookupsApi->apiV1LookupsAdspaceTypesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1LookupsAdspaceTypesGet200Response**](../Model/ApiV1LookupsAdspaceTypesGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1LookupsAdzoneLocationsGet()`

```php
apiV1LookupsAdzoneLocationsGet(): \GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response
```

Adzone locations for the tenant.

Returns the configured adzone locations for the tenant. Use `id` to fill `locationid` on adspace create, and in `location_ids` on a banner campaign (ad type 2) — required on campaign create when this list is non-empty (a campaign may not target every placement at the floor price). Only meaningful when `modules.global.adzone_locations` is enabled — empty list otherwise.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\LookupsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1LookupsAdzoneLocationsGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling LookupsApi->apiV1LookupsAdzoneLocationsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response**](../Model/ApiV1LookupsDeviceTypesGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1LookupsAdzonesGet()`

```php
apiV1LookupsAdzonesGet($term, $bulk)
```

Find adzone ids (for adzone filters).

The panel's adzone picker. Two modes:  * `term` (≥ 2 characters) — up to 50 zones whose name or site URL   contains it, or whose id equals it. Answer: a bare array   `[{id, label}]` (label = `zone name (site url)`). * `bulk` — newline-separated adzone ids; answer   `{data: [{id, label}], not_found: [...]}`.  Labels follow the panel's source-name rule: an account without the \"see source names\" right gets the id as the label, and `term` then matches exact ids only.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\LookupsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$term = 'term_example'; // string
$bulk = 'bulk_example'; // string | Newline-separated adzone ids; wins over term.

try {
    $apiInstance->apiV1LookupsAdzonesGet($term, $bulk);
} catch (Exception $e) {
    echo 'Exception when calling LookupsApi->apiV1LookupsAdzonesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **term** | **string**|  | [optional] |
| **bulk** | **string**| Newline-separated adzone ids; wins over term. | [optional] |

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

## `apiV1LookupsBannerSizesGet()`

```php
apiV1LookupsBannerSizesGet(): \GoAdServerApi\Model\ApiV1LookupsBannerSizesGet200Response
```

Banner sizes for banner-flavored adspaces.

Returns the active banner sizes for the tenant. Use the `id` value as `adsizeid` (or `banner_settings.sizeid`) when creating a banner-flavored adspace (`bannerads`, `fixim_banner`, `impopup`, `interstitialads`, `interstitial_banner`, `invideo_banner`).  Sizes are sorted with common IAB formats first (300x250 → 728x90 → 160x600 → …).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\LookupsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1LookupsBannerSizesGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling LookupsApi->apiV1LookupsBannerSizesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1LookupsBannerSizesGet200Response**](../Model/ApiV1LookupsBannerSizesGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1LookupsBrowsersGet()`

```php
apiV1LookupsBrowsersGet($device_type_ids): \GoAdServerApi\Model\ApiV1LookupsBrowsersGet200Response
```

Browser ids (optionally scoped by device type).

Returns `{id, name}` pairs for browsers known to the platform. Browsers are stored **per device type**, so callers that already know which devices their campaign targets should pass `?device_type_ids=<csv>` to scope the listing — e.g. `?device_type_ids=1,2` for desktop + mobile.  Without the filter, returns the tenant-active set across all device types (some entries may not actually serve on every device).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\LookupsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$device_type_ids = 1,2; // string | Comma-separated device-type ids from `GET /api/v1/lookups/device-types`. Empty = all.

try {
    $result = $apiInstance->apiV1LookupsBrowsersGet($device_type_ids);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling LookupsApi->apiV1LookupsBrowsersGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **device_type_ids** | **string**| Comma-separated device-type ids from &#x60;GET /api/v1/lookups/device-types&#x60;. Empty &#x3D; all. | [optional] |

### Return type

[**\GoAdServerApi\Model\ApiV1LookupsBrowsersGet200Response**](../Model/ApiV1LookupsBrowsersGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1LookupsCarriersGet()`

```php
apiV1LookupsCarriersGet(): \GoAdServerApi\Model\ApiV1LookupsCarriersGet200Response
```

Mobile carriers (ids for carrier targeting).

Every mobile carrier the ad server can target, with its country. Use `id` for campaign carrier targeting (`carrier_id`) and for `carrier_id` conditions in bid rulesets. Same cached list as the panel's carrier dropdown (~1,150 rows, refreshed every 5 minutes).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\LookupsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1LookupsCarriersGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling LookupsApi->apiV1LookupsCarriersGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1LookupsCarriersGet200Response**](../Model/ApiV1LookupsCarriersGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1LookupsCategoriesGet()`

```php
apiV1LookupsCategoriesGet($main_catid): \GoAdServerApi\Model\ApiV1LookupsCategoriesGet200Response
```

Active sub-categories (optionally scoped to a main).

Returns active categories. Pass `?main_catid=N` to limit the result to categories belonging to that main category — the legacy `main_categories` column on each row is a CSV of main-id memberships, so a category can belong to several mains.  Use `id` to fill `catid` on adspace create or populate the `categories` array on site create.  **Cross-resource lookup** — accessible to keys with any of `sites.read`, `adspaces.read`, `tracking.read`, or `campaigns.read`. (Any scope that legitimately needs category ids opens it.)

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\LookupsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$main_catid = 56; // int | Filter to categories under this main.

try {
    $result = $apiInstance->apiV1LookupsCategoriesGet($main_catid);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling LookupsApi->apiV1LookupsCategoriesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **main_catid** | **int**| Filter to categories under this main. | [optional] |

### Return type

[**\GoAdServerApi\Model\ApiV1LookupsCategoriesGet200Response**](../Model/ApiV1LookupsCategoriesGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1LookupsDeviceTypesGet()`

```php
apiV1LookupsDeviceTypesGet(): \GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response
```

Active device types for this tenant.

Returns `{id, name}` pairs for the device types active on this tenant (typically Desktop, Mobile, Tablet, …). Use these ids:   - in the `device_type_ids` field on campaign create / update,   - and as the `device_type_ids` query filter for     `/api/v1/lookups/browsers` and     `/api/v1/lookups/operating-systems` (browsers + OSes are     stored per device type).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\LookupsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1LookupsDeviceTypesGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling LookupsApi->apiV1LookupsDeviceTypesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response**](../Model/ApiV1LookupsDeviceTypesGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1LookupsDomainsGet()`

```php
apiV1LookupsDomainsGet($bulk)
```

Resolve domains to the ids domain filters store.

The panel's bulk domain picker. `bulk` is a newline-separated list of domains, URLs (protocol, path and `www.` are stripped) or domain ids; each known one comes back as `{id, label}` and the rest are listed in `not_found`. Domain filters accept hostnames directly too (`items` on POST /api/v1/filters), so this is mostly for showing what an id means. Without `bulk` the answer is an empty array `[]`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\LookupsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$bulk = example.com
https://www.spam.test/x
123; // string

try {
    $apiInstance->apiV1LookupsDomainsGet($bulk);
} catch (Exception $e) {
    echo 'Exception when calling LookupsApi->apiV1LookupsDomainsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **bulk** | **string**|  | |

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

## `apiV1LookupsIspsGet()`

```php
apiV1LookupsIspsGet($term, $bulk)
```

Find ISP ids (for ISP filters).

The panel's ISP picker. `term` (≥ 2 characters) returns up to 50 ISPs whose name contains it as a bare array `[{id, label}]` (label = `name (country) (connection type)`). `bulk` takes newline-separated name fragments and returns every matching ISP (one organisation can span many ASNs) as `{data, not_found, truncated, limit}` — capped at `limit` (1000) rows, with `truncated: true` when the cap clipped it (then `not_found` is not computed).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\LookupsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$term = 'term_example'; // string
$bulk = 'bulk_example'; // string | Newline-separated name fragments; wins over term.

try {
    $apiInstance->apiV1LookupsIspsGet($term, $bulk);
} catch (Exception $e) {
    echo 'Exception when calling LookupsApi->apiV1LookupsIspsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **term** | **string**|  | [optional] |
| **bulk** | **string**| Newline-separated name fragments; wins over term. | [optional] |

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

## `apiV1LookupsLinkTypesGet()`

```php
apiV1LookupsLinkTypesGet(): \GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response
```

Active link types for the tenant.

The tenant's link types. Use `id` in `location_ids` on a link campaign (ad type 6); required on create when this list is non-empty. Empty unless `modules.global.link_types` is active.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\LookupsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1LookupsLinkTypesGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling LookupsApi->apiV1LookupsLinkTypesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response**](../Model/ApiV1LookupsDeviceTypesGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1LookupsMainCategoriesGet()`

```php
apiV1LookupsMainCategoriesGet(): \GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response
```

Active main categories for the tenant.

Returns active main categories. Use `id` to fill `main_catid` on site create or adspace create, or as an input to `/api/v1/lookups/categories?main_catid=N` for the sub-categories.  **Cross-resource lookup** — accessible to keys with any of `sites.read`, `adspaces.read`, `tracking.read`, or `campaigns.read`. (Any scope that legitimately needs category ids opens it.)

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\LookupsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1LookupsMainCategoriesGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling LookupsApi->apiV1LookupsMainCategoriesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response**](../Model/ApiV1LookupsDeviceTypesGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1LookupsOperatingSystemsGet()`

```php
apiV1LookupsOperatingSystemsGet($device_type_ids): \GoAdServerApi\Model\ApiV1LookupsBrowsersGet200Response
```

Operating-system ids (optionally scoped by device type).

Returns `{id, name}` pairs for operating systems active on this tenant. OS visibility is **per device type** — pass `?device_type_ids=<csv>` to scope the listing to OSes valid on those devices (e.g. iOS / Android only show under mobile).  Without the filter, returns the tenant-active union across all device types (Mobile OSes will appear even when you're targeting Desktop, etc.).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\LookupsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$device_type_ids = 2; // string | Comma-separated device-type ids from `GET /api/v1/lookups/device-types`. Empty = all.

try {
    $result = $apiInstance->apiV1LookupsOperatingSystemsGet($device_type_ids);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling LookupsApi->apiV1LookupsOperatingSystemsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **device_type_ids** | **string**| Comma-separated device-type ids from &#x60;GET /api/v1/lookups/device-types&#x60;. Empty &#x3D; all. | [optional] |

### Return type

[**\GoAdServerApi\Model\ApiV1LookupsBrowsersGet200Response**](../Model/ApiV1LookupsBrowsersGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1LookupsPublishersGet()`

```php
apiV1LookupsPublishersGet($term, $bulk)
```

Find publisher ids (for publisher_excl filters).

The panel's publisher picker for the `publisher_excl` filter — only publishers on your own network. `term` is either a publisher id (exact match, `#` prefix allowed) or a name fragment (≥ 2 characters, company or contact name, up to 50 hits), answered as a bare array `[{id, label}]` with label = company or contact name. `bulk` takes newline-separated ids and returns `{data, not_found}`. Where the network hides traffic-source names from advertisers, the label is the bare id and only exact-id lookups match.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\LookupsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$term = 'term_example'; // string
$bulk = 'bulk_example'; // string | Newline-separated publisher ids; wins over term.

try {
    $apiInstance->apiV1LookupsPublishersGet($term, $bulk);
} catch (Exception $e) {
    echo 'Exception when calling LookupsApi->apiV1LookupsPublishersGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **term** | **string**|  | [optional] |
| **bulk** | **string**| Newline-separated publisher ids; wins over term. | [optional] |

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

## `apiV1LookupsRatingsGet()`

```php
apiV1LookupsRatingsGet(): \GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response
```

Active content ratings for the tenant.

Returns active content ratings. Use `id` to fill `ratingid` on adspace create. Only enforced when `modules.global.rating` is active — empty list otherwise.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\LookupsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1LookupsRatingsGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling LookupsApi->apiV1LookupsRatingsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response**](../Model/ApiV1LookupsDeviceTypesGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1LookupsReelTypesGet()`

```php
apiV1LookupsReelTypesGet(): \GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response
```

Active reel types for the tenant.

The tenant's reel types. Use `id` in `location_ids` on a reel campaign (ad type 20); required on create when this list is non-empty. Empty unless `modules.global.reel_types` is active.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\LookupsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1LookupsReelTypesGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling LookupsApi->apiV1LookupsReelTypesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response**](../Model/ApiV1LookupsDeviceTypesGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1LookupsRegionsGet()`

```php
apiV1LookupsRegionsGet($parent_id, $ids): \GoAdServerApi\Model\ApiV1LookupsRegionsGet200Response
```

Walk the geo region tree (one level per call).

Hierarchical drill-down browser for geo region targeting. Returns only the direct children of `parent_id` per call so callers walk the tree top-down, one level at a time:  ``` parent_id absent / 0  → continents (level 0) parent_id = <continent id>  → countries on that continent (level 1) parent_id = <country id>    → states / provinces in that country (level 2) parent_id = <state id>      → cities / sub-regions (level 3, if any) parent_id = <city id>       → leaf items (level 4, if any) ```  Each item carries `level` so the caller can stop drilling once they've hit the granularity they want. Campaigns most commonly target countries (level 1) or states / provinces (level 2), but deeper levels are valid too.  **The id you collect from this endpoint is what you send back to `regions` on a campaign create / update.** Always send the most specific (leaf) id you actually want — see the `regions` field notes on `POST /api/v1/campaigns/{type}` and `PATCH /api/v1/campaigns/{type}/{id}` for why.  **Performance:** the geo tree is preloaded into memory at server startup (~158k rows), so every call is a map lookup, not a DB query. A response is capped at 2000 children per call (no single legitimate parent has more than that).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\LookupsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$parent_id = 0; // int | Parent region id to list children of. 0 (default) returns continents.
$ids = 2300660,2635167; // string | Comma-separated region ids to look up directly instead of walking the tree (e.g. the ids in a campaign's `regions` field), up to 500; unknown ids are left out. The answer then carries `ids: true` and no `parent_id`.

try {
    $result = $apiInstance->apiV1LookupsRegionsGet($parent_id, $ids);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling LookupsApi->apiV1LookupsRegionsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **parent_id** | **int**| Parent region id to list children of. 0 (default) returns continents. | [optional] [default to 0] |
| **ids** | **string**| Comma-separated region ids to look up directly instead of walking the tree (e.g. the ids in a campaign&#39;s &#x60;regions&#x60; field), up to 500; unknown ids are left out. The answer then carries &#x60;ids: true&#x60; and no &#x60;parent_id&#x60;. | [optional] |

### Return type

[**\GoAdServerApi\Model\ApiV1LookupsRegionsGet200Response**](../Model/ApiV1LookupsRegionsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1LookupsWebsiteLanguagesGet()`

```php
apiV1LookupsWebsiteLanguagesGet(): \GoAdServerApi\Model\ApiV1LookupsWebsiteLanguagesGet200Response
```

Supported website languages (ISO codes).

Returns the supported website languages from the `adlanguages` table. Pass the `code` values in the `sitelanguages` array on site create. Required when `modules.global.website_languages` is active. Non-trusted publishers may select only one.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\LookupsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1LookupsWebsiteLanguagesGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling LookupsApi->apiV1LookupsWebsiteLanguagesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1LookupsWebsiteLanguagesGet200Response**](../Model/ApiV1LookupsWebsiteLanguagesGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1LookupsWebsiteTypesGet()`

```php
apiV1LookupsWebsiteTypesGet(): \GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response
```

Active website types for the tenant.

Returns the configured website types. Use `id` to fill `typeid` on site create. Only meaningful when `modules.global.website_types` is enabled — empty list otherwise.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\LookupsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1LookupsWebsiteTypesGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling LookupsApi->apiV1LookupsWebsiteTypesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1LookupsDeviceTypesGet200Response**](../Model/ApiV1LookupsDeviceTypesGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
