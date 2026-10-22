# GoAdServerApi\SitesApi

Publisher websites — create, edit, review state, verify, clone, HTML snippets.

All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1DomainsGet()**](SitesApi.md#apiV1DomainsGet) | **GET** /api/v1/domains | List your custom domains (Domain Manager). |
| [**apiV1DomainsIdDelete()**](SitesApi.md#apiV1DomainsIdDelete) | **DELETE** /api/v1/domains/{id} | Delete a custom domain. |
| [**apiV1DomainsIdGet()**](SitesApi.md#apiV1DomainsIdGet) | **GET** /api/v1/domains/{id} | Show one custom domain. |
| [**apiV1DomainsIdNotesPut()**](SitesApi.md#apiV1DomainsIdNotesPut) | **PUT** /api/v1/domains/{id}/notes | Set your notes on a domain. |
| [**apiV1DomainsIdRedoPost()**](SitesApi.md#apiV1DomainsIdRedoPost) | **POST** /api/v1/domains/{id}/redo | Retry setting up a domain (DNS / certificate check again). |
| [**apiV1DomainsIdToggleActivePost()**](SitesApi.md#apiV1DomainsIdToggleActivePost) | **POST** /api/v1/domains/{id}/toggle-active | Switch a custom domain on or off. |
| [**apiV1DomainsPost()**](SitesApi.md#apiV1DomainsPost) | **POST** /api/v1/domains | Add a custom domain. |
| [**apiV1SitesGet()**](SitesApi.md#apiV1SitesGet) | **GET** /api/v1/sites | List the caller&#39;s websites. |
| [**apiV1SitesIdClonePost()**](SitesApi.md#apiV1SitesIdClonePost) | **POST** /api/v1/sites/{id}/clone | Duplicate a site under a new name and URL, with its ad zones. |
| [**apiV1SitesIdDelete()**](SitesApi.md#apiV1SitesIdDelete) | **DELETE** /api/v1/sites/{id} | Soft-delete a site. |
| [**apiV1SitesIdGet()**](SitesApi.md#apiV1SitesIdGet) | **GET** /api/v1/sites/{id} | Get a single site. |
| [**apiV1SitesIdPatch()**](SitesApi.md#apiV1SitesIdPatch) | **PATCH** /api/v1/sites/{id} | Update a site. |
| [**apiV1SitesIdRecoverPost()**](SitesApi.md#apiV1SitesIdRecoverPost) | **POST** /api/v1/sites/{id}/recover | Reverse a soft-delete on a site. |
| [**apiV1SitesIdSnippetsGet()**](SitesApi.md#apiV1SitesIdSnippetsGet) | **GET** /api/v1/sites/{id}/snippets | List the site&#39;s HTML snippets. |
| [**apiV1SitesIdSnippetsPost()**](SitesApi.md#apiV1SitesIdSnippetsPost) | **POST** /api/v1/sites/{id}/snippets | Add an HTML snippet. |
| [**apiV1SitesIdSnippetsSnippetIdDelete()**](SitesApi.md#apiV1SitesIdSnippetsSnippetIdDelete) | **DELETE** /api/v1/sites/{id}/snippets/{snippetId} | Delete an HTML snippet. |
| [**apiV1SitesIdSnippetsSnippetIdPut()**](SitesApi.md#apiV1SitesIdSnippetsSnippetIdPut) | **PUT** /api/v1/sites/{id}/snippets/{snippetId} | Replace an HTML snippet. |
| [**apiV1SitesIdVerifyPost()**](SitesApi.md#apiV1SitesIdVerifyPost) | **POST** /api/v1/sites/{id}/verify | Trigger HTTP re-check of the site verification meta tag. |
| [**apiV1SitesPost()**](SitesApi.md#apiV1SitesPost) | **POST** /api/v1/sites | Create a new website. |
| [**apiV1SitesSchemaGet()**](SitesApi.md#apiV1SitesSchemaGet) | **GET** /api/v1/sites/_schema | Field metadata for site create/update. |


## `apiV1DomainsGet()`

```php
apiV1DomainsGet($type, $state, $isactive, $page, $per_page)
```

List your custom domains (Domain Manager).

The domains you serve ads (`ads`), static files (`static`) or your branded panel (`up`) from. `dns_target` is the host an `ads`/`static` domain must CNAME to (your network's ad domain); `dns_target_up` the host an `up` domain must CNAME to. Paginated like every list (`page`, `per_page` ≤ 100); filters: `type`, `state`, `isactive`, `domain` (LIKE).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SitesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = 'type_example'; // string
$state = 56; // int
$isactive = 56; // int
$page = 56; // int
$per_page = 56; // int

try {
    $apiInstance->apiV1DomainsGet($type, $state, $isactive, $page, $per_page);
} catch (Exception $e) {
    echo 'Exception when calling SitesApi->apiV1DomainsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | **string**|  | [optional] |
| **state** | **int**|  | [optional] |
| **isactive** | **int**|  | [optional] |
| **page** | **int**|  | [optional] |
| **per_page** | **int**|  | [optional] |

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

## `apiV1DomainsIdDelete()`

```php
apiV1DomainsIdDelete($id)
```

Delete a custom domain.

Refused with 409 `brand_in_use` while the domain is your branded panel domain.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SitesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1DomainsIdDelete($id);
} catch (Exception $e) {
    echo 'Exception when calling SitesApi->apiV1DomainsIdDelete: ', $e->getMessage(), PHP_EOL;
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

## `apiV1DomainsIdGet()`

```php
apiV1DomainsIdGet($id)
```

Show one custom domain.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SitesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1DomainsIdGet($id);
} catch (Exception $e) {
    echo 'Exception when calling SitesApi->apiV1DomainsIdGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1DomainsIdNotesPut()`

```php
apiV1DomainsIdNotesPut($id, $api_v1_domains_id_notes_put_request)
```

Set your notes on a domain.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SitesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_domains_id_notes_put_request = new \GoAdServerApi\Model\ApiV1DomainsIdNotesPutRequest(); // \GoAdServerApi\Model\ApiV1DomainsIdNotesPutRequest

try {
    $apiInstance->apiV1DomainsIdNotesPut($id, $api_v1_domains_id_notes_put_request);
} catch (Exception $e) {
    echo 'Exception when calling SitesApi->apiV1DomainsIdNotesPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_domains_id_notes_put_request** | [**\GoAdServerApi\Model\ApiV1DomainsIdNotesPutRequest**](../Model/ApiV1DomainsIdNotesPutRequest.md)|  | |

### Return type

void (empty response body)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: Not defined

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1DomainsIdRedoPost()`

```php
apiV1DomainsIdRedoPost($id)
```

Retry setting up a domain (DNS / certificate check again).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SitesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1DomainsIdRedoPost($id);
} catch (Exception $e) {
    echo 'Exception when calling SitesApi->apiV1DomainsIdRedoPost: ', $e->getMessage(), PHP_EOL;
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

## `apiV1DomainsIdToggleActivePost()`

```php
apiV1DomainsIdToggleActivePost($id)
```

Switch a custom domain on or off.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SitesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1DomainsIdToggleActivePost($id);
} catch (Exception $e) {
    echo 'Exception when calling SitesApi->apiV1DomainsIdToggleActivePost: ', $e->getMessage(), PHP_EOL;
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

## `apiV1DomainsPost()`

```php
apiV1DomainsPost($api_v1_domains_post_request)
```

Add a custom domain.

Create the CNAME first (see `dns_target` on GET /api/v1/domains), then add the domain. The server checks that it resolves and that it really reaches our servers before it is accepted; the certificate is then issued automatically and `state` moves on as it goes live. Errors (409): `invalid_domain`, `invalid_dns` (does not resolve), `invalid_dns_point` (resolves, but not to us yet — DNS can take a few hours), `domain_exists`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SitesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_domains_post_request = new \GoAdServerApi\Model\ApiV1DomainsPostRequest(); // \GoAdServerApi\Model\ApiV1DomainsPostRequest

try {
    $apiInstance->apiV1DomainsPost($api_v1_domains_post_request);
} catch (Exception $e) {
    echo 'Exception when calling SitesApi->apiV1DomainsPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_domains_post_request** | [**\GoAdServerApi\Model\ApiV1DomainsPostRequest**](../Model/ApiV1DomainsPostRequest.md)|  | |

### Return type

void (empty response body)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: Not defined

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1SitesGet()`

```php
apiV1SitesGet($state, $sitestate, $search, $page, $per_page): \GoAdServerApi\Model\ApiV1SitesGet200Response
```

List the caller's websites.

Paged list (sorted by name). `state=deleted` lists soft-deleted sites so `POST /api/v1/sites/{id}/recover` has ids to work with. Per-site period stats (the panel's Websites listing stats columns) come from `GET /api/v1/publisher/stats?group_by=website&from=&to=`, keyed by site id; per-zone ones from `group_by=adzone`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SitesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$state = 'live'; // string
$sitestate = 56; // int | Review state: 1 validated, 0 pending, -1 rejected.
$search = 'search_example'; // string | Substring of name or URL.
$page = 1; // int
$per_page = 500; // int

try {
    $result = $apiInstance->apiV1SitesGet($state, $sitestate, $search, $page, $per_page);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling SitesApi->apiV1SitesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **state** | **string**|  | [optional] [default to &#39;live&#39;] |
| **sitestate** | **int**| Review state: 1 validated, 0 pending, -1 rejected. | [optional] |
| **search** | **string**| Substring of name or URL. | [optional] |
| **page** | **int**|  | [optional] [default to 1] |
| **per_page** | **int**|  | [optional] [default to 500] |

### Return type

[**\GoAdServerApi\Model\ApiV1SitesGet200Response**](../Model/ApiV1SitesGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1SitesIdClonePost()`

```php
apiV1SitesIdClonePost($id, $api_v1_sites_id_clone_post_request)
```

Duplicate a site under a new name and URL, with its ad zones.

Creates a new site from this one (same settings) and copies its live ad zones onto it. The new URL goes through the same domain checks as `POST /api/v1/sites`. Keys pinned to `site_ids` / `adzone_ids` are refused (the copy could never be on the pin).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SitesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_sites_id_clone_post_request = {"name":"My Blog (DE)","url":"https://de.example.com"}; // \GoAdServerApi\Model\ApiV1SitesIdClonePostRequest

try {
    $apiInstance->apiV1SitesIdClonePost($id, $api_v1_sites_id_clone_post_request);
} catch (Exception $e) {
    echo 'Exception when calling SitesApi->apiV1SitesIdClonePost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_sites_id_clone_post_request** | [**\GoAdServerApi\Model\ApiV1SitesIdClonePostRequest**](../Model/ApiV1SitesIdClonePostRequest.md)|  | |

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

## `apiV1SitesIdDelete()`

```php
apiV1SitesIdDelete($id)
```

Soft-delete a site.

Sets `isdeleted = 1`. Refused with **422 `has_adzones`** while the site still has live ad zones — delete or move them first (`POST /api/v1/adspaces/mass-delete`). Undo with `POST /api/v1/sites/{id}/recover`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SitesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1SitesIdDelete($id);
} catch (Exception $e) {
    echo 'Exception when calling SitesApi->apiV1SitesIdDelete: ', $e->getMessage(), PHP_EOL;
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

## `apiV1SitesIdGet()`

```php
apiV1SitesIdGet($id): \GoAdServerApi\Model\Site
```

Get a single site.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SitesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $result = $apiInstance->apiV1SitesIdGet($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling SitesApi->apiV1SitesIdGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |

### Return type

[**\GoAdServerApi\Model\Site**](../Model/Site.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1SitesIdPatch()`

```php
apiV1SitesIdPatch($id, $request_body)
```

Update a site.

Update fields on an existing site. Send only the keys you want to change — omitted fields keep their current value. The `url` domain is re-validated when changed. A change of `url`, `categories` or `sitelanguages` puts a validated site back to pending review where the tenant reviews sites. `tags` (site groups) replaces the list; `[]` clears it.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SitesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$request_body = {"description":"Updated description","categories":[12,47,99]}; // array<string,mixed>

try {
    $apiInstance->apiV1SitesIdPatch($id, $request_body);
} catch (Exception $e) {
    echo 'Exception when calling SitesApi->apiV1SitesIdPatch: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **request_body** | [**array<string,mixed>**](../Model/mixed.md)|  | |

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

## `apiV1SitesIdRecoverPost()`

```php
apiV1SitesIdRecoverPost($id)
```

Reverse a soft-delete on a site.

Sets `isdeleted = 0` so the site appears in listings again (the panel's Restore in the websites list's Deleted view). Returns 404 if the site doesn't exist, isn't currently soft-deleted, or was purged.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SitesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1SitesIdRecoverPost($id);
} catch (Exception $e) {
    echo 'Exception when calling SitesApi->apiV1SitesIdRecoverPost: ', $e->getMessage(), PHP_EOL;
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

## `apiV1SitesIdSnippetsGet()`

```php
apiV1SitesIdSnippetsGet($id)
```

List the site's HTML snippets.

Managed `<meta>` / `<script>` / `<link>` / `<style>` tags injected through the site's one container tag (the panel's HTML snippets panel). Up to 100 per site.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SitesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1SitesIdSnippetsGet($id);
} catch (Exception $e) {
    echo 'Exception when calling SitesApi->apiV1SitesIdSnippetsGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1SitesIdSnippetsPost()`

```php
apiV1SitesIdSnippetsPost($id, $site_snippet_write)
```

Add an HTML snippet.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SitesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$site_snippet_write = {"snippet_key":"google-verify","tag_type":"meta","position":"head","enabled":true,"attrs":{"name":"google-site-verification","content":"abc123"}}; // \GoAdServerApi\Model\SiteSnippetWrite

try {
    $apiInstance->apiV1SitesIdSnippetsPost($id, $site_snippet_write);
} catch (Exception $e) {
    echo 'Exception when calling SitesApi->apiV1SitesIdSnippetsPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **site_snippet_write** | [**\GoAdServerApi\Model\SiteSnippetWrite**](../Model/SiteSnippetWrite.md)|  | |

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

## `apiV1SitesIdSnippetsSnippetIdDelete()`

```php
apiV1SitesIdSnippetsSnippetIdDelete($id, $snippet_id)
```

Delete an HTML snippet.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SitesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$snippet_id = 56; // int

try {
    $apiInstance->apiV1SitesIdSnippetsSnippetIdDelete($id, $snippet_id);
} catch (Exception $e) {
    echo 'Exception when calling SitesApi->apiV1SitesIdSnippetsSnippetIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **snippet_id** | **int**|  | |

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

## `apiV1SitesIdSnippetsSnippetIdPut()`

```php
apiV1SitesIdSnippetsSnippetIdPut($id, $snippet_id, $site_snippet_write)
```

Replace an HTML snippet.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SitesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$snippet_id = 56; // int
$site_snippet_write = new \GoAdServerApi\Model\SiteSnippetWrite(); // \GoAdServerApi\Model\SiteSnippetWrite

try {
    $apiInstance->apiV1SitesIdSnippetsSnippetIdPut($id, $snippet_id, $site_snippet_write);
} catch (Exception $e) {
    echo 'Exception when calling SitesApi->apiV1SitesIdSnippetsSnippetIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **snippet_id** | **int**|  | |
| **site_snippet_write** | [**\GoAdServerApi\Model\SiteSnippetWrite**](../Model/SiteSnippetWrite.md)|  | |

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

## `apiV1SitesIdVerifyPost()`

```php
apiV1SitesIdVerifyPost($id)
```

Trigger HTTP re-check of the site verification meta tag.

Tells the server to fetch the site URL and look for the verify meta tag the publisher embedded. On success the site flips to `is_verified=true` and can start serving ads.  The verify code + tag prefix are exposed on `GET /api/v1/sites/{id}` (`verify_code`, `verify_tag_prefix`, `verify_meta_tag`) — the publisher pastes the meta tag on the site root, then POSTs here.  Only meaningful when `modules.publisher.websites.options.need_verification` is on (otherwise the site is auto-verified at create time).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SitesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1SitesIdVerifyPost($id);
} catch (Exception $e) {
    echo 'Exception when calling SitesApi->apiV1SitesIdVerifyPost: ', $e->getMessage(), PHP_EOL;
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

## `apiV1SitesPost()`

```php
apiV1SitesPost($site_create)
```

Create a new website.

Create a website (publisher_sites row). Required: `name` + `url`. The domain in `url` is checked against the publisher-domain blocklist; subdomain duplicates of an existing site are rejected.  Module-gated fields become required when their module is active on the tenant — see `GET /api/v1/sites/_schema` for the exact field list applicable to this tenant.  On create, the server resolves the domain to a `domainid`. If `modules.publisher.websites.options.need_verification` is on, the new site starts at `is_verified=false` — the publisher must complete the dashboard verification flow before the site can serve ads.  API keys with `resource_filter.site_ids` set cannot create new sites — pinning to a fixed list is incompatible with adding new ones (returns 403).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SitesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$site_create = {"name":"My Blog","url":"https://www.example.com","description":"Personal blog about hiking","main_catid":12,"categories":[12,47],"sitelanguages":["en"]}; // \GoAdServerApi\Model\SiteCreate

try {
    $apiInstance->apiV1SitesPost($site_create);
} catch (Exception $e) {
    echo 'Exception when calling SitesApi->apiV1SitesPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **site_create** | [**\GoAdServerApi\Model\SiteCreate**](../Model/SiteCreate.md)|  | |

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

## `apiV1SitesSchemaGet()`

```php
apiV1SitesSchemaGet(): \GoAdServerApi\Model\ApiV1SitesSchemaGet200Response
```

Field metadata for site create/update.

Returns the canonical field list for `POST /api/v1/sites` and `PATCH /api/v1/sites/{id}`. Module-gated fields (`typeid`, `categories`, `sitelanguages`, `postbacks`) only appear when the relevant module is active on the calling tenant.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\SitesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1SitesSchemaGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling SitesApi->apiV1SitesSchemaGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1SitesSchemaGet200Response**](../Model/ApiV1SitesSchemaGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
