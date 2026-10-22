# GoAdServerApi\ProductFeedsApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1ProductFeedsGet()**](ProductFeedsApi.md#apiV1ProductFeedsGet) | **GET** /api/v1/product-feeds | List product feeds. |
| [**apiV1ProductFeedsIdDelete()**](ProductFeedsApi.md#apiV1ProductFeedsIdDelete) | **DELETE** /api/v1/product-feeds/{id} | Delete a product feed. |
| [**apiV1ProductFeedsIdGet()**](ProductFeedsApi.md#apiV1ProductFeedsIdGet) | **GET** /api/v1/product-feeds/{id} | Show a product feed. |
| [**apiV1ProductFeedsIdItemsGet()**](ProductFeedsApi.md#apiV1ProductFeedsIdItemsGet) | **GET** /api/v1/product-feeds/{id}/items | The feed&#39;s products and their ads. |
| [**apiV1ProductFeedsIdPatch()**](ProductFeedsApi.md#apiV1ProductFeedsIdPatch) | **PATCH** /api/v1/product-feeds/{id} | Change a product feed. |
| [**apiV1ProductFeedsIdSyncPost()**](ProductFeedsApi.md#apiV1ProductFeedsIdSyncPost) | **POST** /api/v1/product-feeds/{id}/sync | Sync a product feed now. |
| [**apiV1ProductFeedsPost()**](ProductFeedsApi.md#apiV1ProductFeedsPost) | **POST** /api/v1/product-feeds | Add a product feed. |
| [**apiV1ProductFeedsPreviewPost()**](ProductFeedsApi.md#apiV1ProductFeedsPreviewPost) | **POST** /api/v1/product-feeds/preview | Preview a dynamic banner. |


## `apiV1ProductFeedsGet()`

```php
apiV1ProductFeedsGet()
```

List product feeds.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\ProductFeedsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $apiInstance->apiV1ProductFeedsGet();
} catch (Exception $e) {
    echo 'Exception when calling ProductFeedsApi->apiV1ProductFeedsGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1ProductFeedsIdDelete()`

```php
apiV1ProductFeedsIdDelete($id)
```

Delete a product feed.

The feed goes; its ads stay in the campaign, paused, so their stats remain.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\ProductFeedsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1ProductFeedsIdDelete($id);
} catch (Exception $e) {
    echo 'Exception when calling ProductFeedsApi->apiV1ProductFeedsIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |

### Return type

void (empty response body)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: Not defined

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1ProductFeedsIdGet()`

```php
apiV1ProductFeedsIdGet($id)
```

Show a product feed.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\ProductFeedsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1ProductFeedsIdGet($id);
} catch (Exception $e) {
    echo 'Exception when calling ProductFeedsApi->apiV1ProductFeedsIdGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |

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

## `apiV1ProductFeedsIdItemsGet()`

```php
apiV1ProductFeedsIdItemsGet($id)
```

The feed's products and their ads.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\ProductFeedsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1ProductFeedsIdItemsGet($id);
} catch (Exception $e) {
    echo 'Exception when calling ProductFeedsApi->apiV1ProductFeedsIdItemsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |

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

## `apiV1ProductFeedsIdPatch()`

```php
apiV1ProductFeedsIdPatch($id)
```

Change a product feed.

Any of the POST fields, plus `is_active` (false stops scheduled syncs; existing ads stay as they are).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\ProductFeedsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1ProductFeedsIdPatch($id);
} catch (Exception $e) {
    echo 'Exception when calling ProductFeedsApi->apiV1ProductFeedsIdPatch: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |

### Return type

void (empty response body)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: Not defined

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1ProductFeedsIdSyncPost()`

```php
apiV1ProductFeedsIdSyncPost($id)
```

Sync a product feed now.

Starts a run in the background (one at a time per feed); poll GET /product-feeds/{id} for the result. A run creates at most 1000 new ads.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\ProductFeedsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1ProductFeedsIdSyncPost($id);
} catch (Exception $e) {
    echo 'Exception when calling ProductFeedsApi->apiV1ProductFeedsIdSyncPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |

### Return type

void (empty response body)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: Not defined

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1ProductFeedsPost()`

```php
apiV1ProductFeedsPost($api_v1_product_feeds_post_request)
```

Add a product feed.

Saved, then synced once in the background (watch `running`, `last_status`, `last_message`). Body: `campaign_id` (required — one of your RON campaigns with ad type native 3 or Search Ads 42), `url` (required, http(s), public host, at most 20 MB), `name`, `format` (`auto` | `xml` | `csv`, default auto), `refresh_hours` (0 = manual, 1, 6, 12 or 24), `max_items` (1-5000), `price_in_title` (bool, the price is appended to the ad title), `bid` (the new ads' bid; 0 = the bid of the campaign's newest ad). Each product needs `id`, `title`, an http(s) `link` and `image_link`; `sale_price` wins over `price`; availability `out_of_stock` / `discontinued` pauses its ad.  Dynamic banners (banner campaigns, ad type 2 — required there, refused on native ones): `template` (`card` | `overlay` | `minimal` | `custom`), `bansizeid` (one of `banner_sizes`), `cta` (button text, ≤ 30, default \"Shop now\"), `accent` (#rrggbb button / price colour), `template_html` (custom only, ≤ 20000 bytes; slots {{title}} {{price}} {{image}} {{description}} {{display}} {{cta}} {{accent}}, values HTML-escaped; link to {click_url} — without it the whole banner is wrapped in the click link). Preview with POST /product-feeds/preview. `retarget` (bool): every product ad carries ad_options.feed {pk, rt}; put `pid=<product id>[,<id>…]` on your retargeting pixel (`/rtpixel.go?id=<pixel>&pid=SKU-1`) on product pages and a visitor who viewed products of this feed sees those first (30 days).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\ProductFeedsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_product_feeds_post_request = {"campaign_id":454450,"url":"https://shop.example.com/feed.xml","name":"Shop catalogue","refresh_hours":6,"max_items":500,"price_in_title":true}; // \GoAdServerApi\Model\ApiV1ProductFeedsPostRequest

try {
    $apiInstance->apiV1ProductFeedsPost($api_v1_product_feeds_post_request);
} catch (Exception $e) {
    echo 'Exception when calling ProductFeedsApi->apiV1ProductFeedsPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_product_feeds_post_request** | [**\GoAdServerApi\Model\ApiV1ProductFeedsPostRequest**](../Model/ApiV1ProductFeedsPostRequest.md)|  | |

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

## `apiV1ProductFeedsPreviewPost()`

```php
apiV1ProductFeedsPreviewPost($api_v1_product_feeds_preview_post_request)
```

Preview a dynamic banner.

Renders one product into a banner template without saving anything: body `{template, template_html, cta, accent, bansizeid, product?}` (`product` = {title, price, image_link, link, description}; a sample product without it). Answers `{html, width, height}`; the html holds the `{click_url}` placeholder the ad server fills when it serves.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\ProductFeedsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_product_feeds_preview_post_request = {"template":"card","bansizeid":3,"cta":"Shop now","accent":"#0b7a4b"}; // \GoAdServerApi\Model\ApiV1ProductFeedsPreviewPostRequest

try {
    $apiInstance->apiV1ProductFeedsPreviewPost($api_v1_product_feeds_preview_post_request);
} catch (Exception $e) {
    echo 'Exception when calling ProductFeedsApi->apiV1ProductFeedsPreviewPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_product_feeds_preview_post_request** | [**\GoAdServerApi\Model\ApiV1ProductFeedsPreviewPostRequest**](../Model/ApiV1ProductFeedsPreviewPostRequest.md)|  | |

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
