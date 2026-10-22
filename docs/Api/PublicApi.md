# GoAdServerApi\PublicApi

No-auth discovery endpoints.

All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiPublicDemoGet()**](PublicApi.md#apiPublicDemoGet) | **GET** /api/public/demo | The network&#39;s ad format demo pages. |
| [**apiPublicDemoSlugGet()**](PublicApi.md#apiPublicDemoSlugGet) | **GET** /api/public/demo/{slug} | One ad format demo page (HTML). |
| [**apiPublicOfferwallIdGet()**](PublicApi.md#apiPublicOfferwallIdGet) | **GET** /api/public/offerwall/{id} | Offerwall list of an offer zone. |
| [**apiPublicOpenapiJsonGet()**](PublicApi.md#apiPublicOpenapiJsonGet) | **GET** /api/public/openapi.json | OpenAPI spec as JSON. |
| [**apiPublicOpenapiYamlGet()**](PublicApi.md#apiPublicOpenapiYamlGet) | **GET** /api/public/openapi.yaml | Raw OpenAPI spec (this document). |
| [**apiPublicRoutesTxtGet()**](PublicApi.md#apiPublicRoutesTxtGet) | **GET** /api/public/routes.txt | Flat list of public routes — agent-friendly. |


## `apiPublicDemoGet()`

```php
apiPublicDemoGet(): \GoAdServerApi\Model\ApiPublicDemoGet200Response
```

The network's ad format demo pages.

White-label live demos of the ad formats this network offers, shown in the panel at /demo (optional per tenant; the admin setting `panel_settings.options.pages.options.show_demo_pages`, off by default). Lists only pages with at least one demo whose zone types the network has active. No authentication; 404 while the setting is off.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');



$apiInstance = new GoAdServerApi\Api\PublicApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);

try {
    $result = $apiInstance->apiPublicDemoGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PublicApi->apiPublicDemoGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiPublicDemoGet200Response**](../Model/ApiPublicDemoGet200Response.md)

### Authorization

No authorization required

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiPublicDemoSlugGet()`

```php
apiPublicDemoSlugGet($slug): string
```

One ad format demo page (HTML).

The page itself, under the network's name, cut down to the demos whose zone types the network offers and renumbered. The panel shows it in an iframe at /demo/{slug}. Served sandboxed (`Content-Security-Policy: sandbox allow-scripts allow-popups …`, no `allow-same-origin`), so its ad scripts get an opaque origin and cannot reach the panel's session. The demo zones run on a demo account; the advertisers are made up.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');



$apiInstance = new GoAdServerApi\Api\PublicApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$slug = native; // string

try {
    $result = $apiInstance->apiPublicDemoSlugGet($slug);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PublicApi->apiPublicDemoSlugGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **slug** | **string**|  | |

### Return type

**string**

### Authorization

No authorization required

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `text/html`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiPublicOfferwallIdGet()`

```php
apiPublicOfferwallIdGet($id): \GoAdServerApi\Model\ApiPublicOfferwallIdGet200Response
```

Offerwall list of an offer zone.

The offers of an offer zone (adzone type 32, its `offerids`) as goCtrl's `offerwall` plugin shows them: title, description, thumbnail, the publisher's payout per conversion (in `currency`) and the countries the offer converts in (empty = all). No authentication; cached 5 minutes, CORS open. GET /api/v1/adspaces/{id}/code of an offer zone returns the offerwall snippet pointing here. The visitor's id rides the offer click as `subid1`; the publisher's conversion postback gets it back with `{subid1}`, `{payout}` and `{offerid}`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');



$apiInstance = new GoAdServerApi\Api\PublicApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$id = 56; // int

try {
    $result = $apiInstance->apiPublicOfferwallIdGet($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling PublicApi->apiPublicOfferwallIdGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |

### Return type

[**\GoAdServerApi\Model\ApiPublicOfferwallIdGet200Response**](../Model/ApiPublicOfferwallIdGet200Response.md)

### Authorization

No authorization required

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiPublicOpenapiJsonGet()`

```php
apiPublicOpenapiJsonGet()
```

OpenAPI spec as JSON.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');



$apiInstance = new GoAdServerApi\Api\PublicApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);

try {
    $apiInstance->apiPublicOpenapiJsonGet();
} catch (Exception $e) {
    echo 'Exception when calling PublicApi->apiPublicOpenapiJsonGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

void (empty response body)

### Authorization

No authorization required

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: Not defined

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiPublicOpenapiYamlGet()`

```php
apiPublicOpenapiYamlGet()
```

Raw OpenAPI spec (this document).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');



$apiInstance = new GoAdServerApi\Api\PublicApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);

try {
    $apiInstance->apiPublicOpenapiYamlGet();
} catch (Exception $e) {
    echo 'Exception when calling PublicApi->apiPublicOpenapiYamlGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

void (empty response body)

### Authorization

No authorization required

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: Not defined

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiPublicRoutesTxtGet()`

```php
apiPublicRoutesTxtGet()
```

Flat list of public routes — agent-friendly.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');



$apiInstance = new GoAdServerApi\Api\PublicApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);

try {
    $apiInstance->apiPublicRoutesTxtGet();
} catch (Exception $e) {
    echo 'Exception when calling PublicApi->apiPublicRoutesTxtGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

void (empty response body)

### Authorization

No authorization required

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: Not defined

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
