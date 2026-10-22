# GoAdServerApi\FiltersApi

Domain / ISP / adzone / SubID / IP filters — read, toggle on/off, delete.

All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1FiltersGet()**](FiltersApi.md#apiV1FiltersGet) | **GET** /api/v1/filters | List all filters across types. |
| [**apiV1FiltersIpIdRangesRangeIdDelete()**](FiltersApi.md#apiV1FiltersIpIdRangesRangeIdDelete) | **DELETE** /api/v1/filters/ip/{id}/ranges/{rangeId} | Delete one range from an IP pool. |
| [**apiV1FiltersIpIdRangesRangeIdTogglePut()**](FiltersApi.md#apiV1FiltersIpIdRangesRangeIdTogglePut) | **PUT** /api/v1/filters/ip/{id}/ranges/{rangeId}/toggle | Pause / resume one range of an IP pool. |
| [**apiV1FiltersMassActionPost()**](FiltersApi.md#apiV1FiltersMassActionPost) | **POST** /api/v1/filters/mass-action | Activate, deactivate or delete many filters. |
| [**apiV1FiltersPost()**](FiltersApi.md#apiV1FiltersPost) | **POST** /api/v1/filters | Create a filter and populate it with items. |
| [**apiV1FiltersTypeIdActivePatch()**](FiltersApi.md#apiV1FiltersTypeIdActivePatch) | **PATCH** /api/v1/filters/{type}/{id}/active | Activate or deactivate a filter (exclusion types only). |
| [**apiV1FiltersTypeIdDelete()**](FiltersApi.md#apiV1FiltersTypeIdDelete) | **DELETE** /api/v1/filters/{type}/{id} | Delete a filter (and its items, for IP pools). |
| [**apiV1FiltersTypeIdGet()**](FiltersApi.md#apiV1FiltersTypeIdGet) | **GET** /api/v1/filters/{type}/{id} | Get a single filter with its items. |
| [**apiV1FiltersTypeIdItemsItemDelete()**](FiltersApi.md#apiV1FiltersTypeIdItemsItemDelete) | **DELETE** /api/v1/filters/{type}/{id}/items/{item} | Remove a single item from a filter. |
| [**apiV1FiltersTypeIdItemsPost()**](FiltersApi.md#apiV1FiltersTypeIdItemsPost) | **POST** /api/v1/filters/{type}/{id}/items | Add items to an existing filter. |
| [**apiV1FiltersTypeIdPut()**](FiltersApi.md#apiV1FiltersTypeIdPut) | **PUT** /api/v1/filters/{type}/{id} | Edit a filter (rename, replace items, change scope). |


## `apiV1FiltersGet()`

```php
apiV1FiltersGet($type, $search): \GoAdServerApi\Model\ApiV1FiltersGet200Response
```

List all filters across types.

Each filter has a **type** and may apply globally or to specific campaigns. Types:  | type          | meaning                          | toggleable | |---------------|----------------------------------|:----------:| | `domain_excl` | Block these domains              | yes | | `domain_incl` | Only allow these domains         | no | | `adzone_excl` | Block these ad zones             | yes | | `adzone_incl` | Only allow these ad zones        | no | | `isp_excl`    | Block these ISPs                 | yes | | `subid_excl`  | Block these SubIDs               | yes | | `subid_incl`  | Only allow these SubIDs          | no | | `publisher_excl` | Block every ad zone of these publishers, including zones they add later | yes | | `ip`          | IP-pool blocking                 | no |  Unpaginated: `{data, total_rows}`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\FiltersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = new \GoAdServerApi\Model\\GoAdServerApi\Model\FilterType(); // \GoAdServerApi\Model\FilterType | Filter to one type.
$search = 'search_example'; // string | Name contains.

try {
    $result = $apiInstance->apiV1FiltersGet($type, $search);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling FiltersApi->apiV1FiltersGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | [**\GoAdServerApi\Model\FilterType**](../Model/.md)| Filter to one type. | [optional] |
| **search** | **string**| Name contains. | [optional] |

### Return type

[**\GoAdServerApi\Model\ApiV1FiltersGet200Response**](../Model/ApiV1FiltersGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1FiltersIpIdRangesRangeIdDelete()`

```php
apiV1FiltersIpIdRangesRangeIdDelete($id, $range_id)
```

Delete one range from an IP pool.

Same as `DELETE /api/v1/filters/ip/{id}/items/{rangeId}`; the panel's own route.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\FiltersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$range_id = 56; // int

try {
    $apiInstance->apiV1FiltersIpIdRangesRangeIdDelete($id, $range_id);
} catch (Exception $e) {
    echo 'Exception when calling FiltersApi->apiV1FiltersIpIdRangesRangeIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **range_id** | **int**|  | |

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

## `apiV1FiltersIpIdRangesRangeIdTogglePut()`

```php
apiV1FiltersIpIdRangesRangeIdTogglePut($id, $range_id)
```

Pause / resume one range of an IP pool.

Flips one range's active state (an inactive range stops matching). `rangeId` is `ranges[].id` from GET /api/v1/filters/ip/{id}. Same handler as the panel; logged on the pool's history.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\FiltersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$range_id = 56; // int

try {
    $apiInstance->apiV1FiltersIpIdRangesRangeIdTogglePut($id, $range_id);
} catch (Exception $e) {
    echo 'Exception when calling FiltersApi->apiV1FiltersIpIdRangesRangeIdTogglePut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **range_id** | **int**|  | |

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

## `apiV1FiltersMassActionPost()`

```php
apiV1FiltersMassActionPost($api_v1_filters_mass_action_post_request)
```

Activate, deactivate or delete many filters.

Each entry names a filter by `type` + `id` (as in `GET /filters`). `delete` runs the single-delete path for each (audit snapshot, IP list cascade, and campaigns linked to an allow-list / IP pool are unlinked). Activate / deactivate only applies to the types that have an on/off state; others are skipped. Filters that are not yours are skipped. `affected` counts what changed.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\FiltersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_filters_mass_action_post_request = {"action":"deactivate","filters":[{"id":1201,"type":"domain_excl"},{"id":88,"type":"isp_excl"}]}; // \GoAdServerApi\Model\ApiV1FiltersMassActionPostRequest

try {
    $apiInstance->apiV1FiltersMassActionPost($api_v1_filters_mass_action_post_request);
} catch (Exception $e) {
    echo 'Exception when calling FiltersApi->apiV1FiltersMassActionPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_filters_mass_action_post_request** | [**\GoAdServerApi\Model\ApiV1FiltersMassActionPostRequest**](../Model/ApiV1FiltersMassActionPostRequest.md)|  | |

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

## `apiV1FiltersPost()`

```php
apiV1FiltersPost($api_v1_filters_post_request): \GoAdServerApi\Model\FilterDetail
```

Create a filter and populate it with items.

Saved through the panel's own create path (same rows, campaign links and Action Log entries as the Filters page).  For `domain_excl` / `domain_incl`: items can be domain URLs (`\"https://evil.com/x\"`) — automatically resolved to internal domain IDs — or numeric domain IDs as strings. For `adzone_excl` / `adzone_incl` / `isp_excl`: items must be numeric IDs (find them with /api/v1/lookups/adzones and /api/v1/lookups/isps). For `publisher_excl`: publisher user ids (find them with /api/v1/lookups/publishers). Ids that are not a publisher on your network are dropped. For `subid_excl` / `subid_incl`: SubID strings (the encoded form when the account has SubID encoding on — stored decoded). For `ip`: ranges in `ip_list` (one per line: an IP, `a - b` or a CIDR) and/or `items`; `campaign_ids` is not accepted (link a pool from the campaign side).  **Campaign scope** (`campaign_ids`): * Block lists (`*_excl`) — `ad_type` 0 blocks across every campaign,   so campaign_ids needs `ad_type > 0` (422 otherwise). Each id must   be one of your live campaigns of that ad type and of   `campaign_type` (`ron` default; `rtb` for DSP campaigns; omitted   with `ad_type` 14 means rtb). Empty = every campaign of that ad   type. * Allow-lists (`*_incl`) — each id must be one of your live RON   campaigns; the filter becomes that campaign's allow-list   (replacing the one it had). Ids outside the key's campaign pin are a 403.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\FiltersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_filters_post_request = new \GoAdServerApi\Model\ApiV1FiltersPostRequest(); // \GoAdServerApi\Model\ApiV1FiltersPostRequest

try {
    $result = $apiInstance->apiV1FiltersPost($api_v1_filters_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling FiltersApi->apiV1FiltersPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_filters_post_request** | [**\GoAdServerApi\Model\ApiV1FiltersPostRequest**](../Model/ApiV1FiltersPostRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\FilterDetail**](../Model/FilterDetail.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1FiltersTypeIdActivePatch()`

```php
apiV1FiltersTypeIdActivePatch($type, $id, $api_v1_campaigns_type_id_active_patch_request): \GoAdServerApi\Model\ApiV1FiltersTypeIdActivePatch200Response
```

Activate or deactivate a filter (exclusion types only).

Only `*_excl` filters have an active/inactive state. Inclusion filters and IP pools always apply when a campaign references them — disable by removing the reference instead.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\FiltersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = new \GoAdServerApi\Model\\GoAdServerApi\Model\FilterType(); // \GoAdServerApi\Model\FilterType
$id = 56; // int
$api_v1_campaigns_type_id_active_patch_request = new \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest(); // \GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest

try {
    $result = $apiInstance->apiV1FiltersTypeIdActivePatch($type, $id, $api_v1_campaigns_type_id_active_patch_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling FiltersApi->apiV1FiltersTypeIdActivePatch: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | [**\GoAdServerApi\Model\FilterType**](../Model/.md)|  | |
| **id** | **int**|  | |
| **api_v1_campaigns_type_id_active_patch_request** | [**\GoAdServerApi\Model\ApiV1CampaignsTypeIdActivePatchRequest**](../Model/ApiV1CampaignsTypeIdActivePatchRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1FiltersTypeIdActivePatch200Response**](../Model/ApiV1FiltersTypeIdActivePatch200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1FiltersTypeIdDelete()`

```php
apiV1FiltersTypeIdDelete($type, $id)
```

Delete a filter (and its items, for IP pools).

Campaigns that use the filter as their allow-list or IP pool are unlinked (set back to none), the same as deleting it in the panel.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\FiltersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = new \GoAdServerApi\Model\\GoAdServerApi\Model\FilterType(); // \GoAdServerApi\Model\FilterType
$id = 56; // int

try {
    $apiInstance->apiV1FiltersTypeIdDelete($type, $id);
} catch (Exception $e) {
    echo 'Exception when calling FiltersApi->apiV1FiltersTypeIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | [**\GoAdServerApi\Model\FilterType**](../Model/.md)|  | |
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

## `apiV1FiltersTypeIdGet()`

```php
apiV1FiltersTypeIdGet($type, $id): \GoAdServerApi\Model\FilterDetail
```

Get a single filter with its items.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\FiltersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = new \GoAdServerApi\Model\\GoAdServerApi\Model\FilterType(); // \GoAdServerApi\Model\FilterType
$id = 56; // int

try {
    $result = $apiInstance->apiV1FiltersTypeIdGet($type, $id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling FiltersApi->apiV1FiltersTypeIdGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | [**\GoAdServerApi\Model\FilterType**](../Model/.md)|  | |
| **id** | **int**|  | |

### Return type

[**\GoAdServerApi\Model\FilterDetail**](../Model/FilterDetail.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1FiltersTypeIdItemsItemDelete()`

```php
apiV1FiltersTypeIdItemsItemDelete($type, $id, $item): \GoAdServerApi\Model\ApiV1FiltersTypeIdItemsItemDelete200Response
```

Remove a single item from a filter.

`{item}` is the stored value — domain ID (or the hostname it was added with), adzone ID, ISP ID, or SubID string (encoded form when SubID encoding is on). For `ip` it is the range id (`ranges[].id`). URL-encode special characters.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\FiltersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = new \GoAdServerApi\Model\\GoAdServerApi\Model\FilterType(); // \GoAdServerApi\Model\FilterType
$id = 56; // int
$item = 'item_example'; // string

try {
    $result = $apiInstance->apiV1FiltersTypeIdItemsItemDelete($type, $id, $item);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling FiltersApi->apiV1FiltersTypeIdItemsItemDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | [**\GoAdServerApi\Model\FilterType**](../Model/.md)|  | |
| **id** | **int**|  | |
| **item** | **string**|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1FiltersTypeIdItemsItemDelete200Response**](../Model/ApiV1FiltersTypeIdItemsItemDelete200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1FiltersTypeIdItemsPost()`

```php
apiV1FiltersTypeIdItemsPost($type, $id, $api_v1_filters_type_id_items_post_request): \GoAdServerApi\Model\ApiV1FiltersTypeIdItemsPost200Response
```

Add items to an existing filter.

Same input format as creating a filter — domain URLs are auto-resolved to IDs for domain filters, numeric IDs required for adzone/ISP filters, SubID strings for SubID filters. Already-present items are deduplicated silently. For `ip` each item is one range line (IP, `a - b` or CIDR) and the answer is the pool's FilterDetail with `imported`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\FiltersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = new \GoAdServerApi\Model\\GoAdServerApi\Model\FilterType(); // \GoAdServerApi\Model\FilterType
$id = 56; // int
$api_v1_filters_type_id_items_post_request = new \GoAdServerApi\Model\ApiV1FiltersTypeIdItemsPostRequest(); // \GoAdServerApi\Model\ApiV1FiltersTypeIdItemsPostRequest

try {
    $result = $apiInstance->apiV1FiltersTypeIdItemsPost($type, $id, $api_v1_filters_type_id_items_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling FiltersApi->apiV1FiltersTypeIdItemsPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | [**\GoAdServerApi\Model\FilterType**](../Model/.md)|  | |
| **id** | **int**|  | |
| **api_v1_filters_type_id_items_post_request** | [**\GoAdServerApi\Model\ApiV1FiltersTypeIdItemsPostRequest**](../Model/ApiV1FiltersTypeIdItemsPostRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1FiltersTypeIdItemsPost200Response**](../Model/ApiV1FiltersTypeIdItemsPost200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1FiltersTypeIdPut()`

```php
apiV1FiltersTypeIdPut($type, $id, $api_v1_filters_type_id_put_request): \GoAdServerApi\Model\FilterDetail
```

Edit a filter (rename, replace items, change scope).

Partial edit: every field you leave out keeps its current value, then the filter is saved through the panel's own edit path (same rows, campaign links and Action Log entries as the Filters page).  * `items` **replaces** the whole item list (same input format as   create). To add or remove single items use the `/items` routes. * `campaign_ids` replaces the campaign scope and is validated like   on create (needs `ad_type > 0` on block lists; campaigns must be   yours, of that ad type, and of `campaign_type`). Sending `[]`   makes a block list cover every campaign of its ad type, and   unlinks every campaign from an allow-list. * `ad_type` / `campaign_type` re-validate the kept campaign list. * `type=ip`: `name`, `ip_list` (ranges to import, one per line) and   `replace_ip_ranges` (true = drop the existing ranges first) only.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\FiltersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = new \GoAdServerApi\Model\\GoAdServerApi\Model\FilterType(); // \GoAdServerApi\Model\FilterType
$id = 56; // int
$api_v1_filters_type_id_put_request = {"name":"Fraud domains (Q4)"}; // \GoAdServerApi\Model\ApiV1FiltersTypeIdPutRequest

try {
    $result = $apiInstance->apiV1FiltersTypeIdPut($type, $id, $api_v1_filters_type_id_put_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling FiltersApi->apiV1FiltersTypeIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **type** | [**\GoAdServerApi\Model\FilterType**](../Model/.md)|  | |
| **id** | **int**|  | |
| **api_v1_filters_type_id_put_request** | [**\GoAdServerApi\Model\ApiV1FiltersTypeIdPutRequest**](../Model/ApiV1FiltersTypeIdPutRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\FilterDetail**](../Model/FilterDetail.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
