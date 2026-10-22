# GoAdServerApi\CreativePoolsApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1CreativePoolsGet()**](CreativePoolsApi.md#apiV1CreativePoolsGet) | **GET** /api/v1/creative-pools | List creative pools. |
| [**apiV1CreativePoolsIdAddCreativesPost()**](CreativePoolsApi.md#apiV1CreativePoolsIdAddCreativesPost) | **POST** /api/v1/creative-pools/{id}/add-creatives | Add media-bank creatives to a pool. |
| [**apiV1CreativePoolsIdAddUrlsPost()**](CreativePoolsApi.md#apiV1CreativePoolsIdAddUrlsPost) | **POST** /api/v1/creative-pools/{id}/add-urls | Add hotlinked image / video / HTML URLs to a pool. |
| [**apiV1CreativePoolsIdClonePost()**](CreativePoolsApi.md#apiV1CreativePoolsIdClonePost) | **POST** /api/v1/creative-pools/{id}/clone | Clone a creative pool with all its items. |
| [**apiV1CreativePoolsIdDelete()**](CreativePoolsApi.md#apiV1CreativePoolsIdDelete) | **DELETE** /api/v1/creative-pools/{id} | Soft-delete a creative pool. |
| [**apiV1CreativePoolsIdGet()**](CreativePoolsApi.md#apiV1CreativePoolsIdGet) | **GET** /api/v1/creative-pools/{id} | Show a single creative pool. |
| [**apiV1CreativePoolsIdItemsExportGet()**](CreativePoolsApi.md#apiV1CreativePoolsIdItemsExportGet) | **GET** /api/v1/creative-pools/{id}/items/export | Download pool items&#39; creative files as a ZIP. |
| [**apiV1CreativePoolsIdItemsGet()**](CreativePoolsApi.md#apiV1CreativePoolsIdItemsGet) | **GET** /api/v1/creative-pools/{id}/items | List items in a creative pool. |
| [**apiV1CreativePoolsIdItemsItemIdDelete()**](CreativePoolsApi.md#apiV1CreativePoolsIdItemsItemIdDelete) | **DELETE** /api/v1/creative-pools/{id}/items/{itemId} | Soft-delete a single pool item. |
| [**apiV1CreativePoolsIdItemsItemIdPut()**](CreativePoolsApi.md#apiV1CreativePoolsIdItemsItemIdPut) | **PUT** /api/v1/creative-pools/{id}/items/{itemId} | Update a single pool item. |
| [**apiV1CreativePoolsIdItemsItemIdTogglePut()**](CreativePoolsApi.md#apiV1CreativePoolsIdItemsItemIdTogglePut) | **PUT** /api/v1/creative-pools/{id}/items/{itemId}/toggle | Toggle a pool item on / off. |
| [**apiV1CreativePoolsIdItemsMassActionPut()**](CreativePoolsApi.md#apiV1CreativePoolsIdItemsMassActionPut) | **PUT** /api/v1/creative-pools/{id}/items/mass-action | Bulk-change items of a creative pool. |
| [**apiV1CreativePoolsIdItemsPost()**](CreativePoolsApi.md#apiV1CreativePoolsIdItemsPost) | **POST** /api/v1/creative-pools/{id}/items | Add an item to a creative pool. |
| [**apiV1CreativePoolsIdItemsRecoverPost()**](CreativePoolsApi.md#apiV1CreativePoolsIdItemsRecoverPost) | **POST** /api/v1/creative-pools/{id}/items/recover | Put removed items back into a creative pool. |
| [**apiV1CreativePoolsIdPut()**](CreativePoolsApi.md#apiV1CreativePoolsIdPut) | **PUT** /api/v1/creative-pools/{id} | Update creative pool meta. |
| [**apiV1CreativePoolsIdRecoverPost()**](CreativePoolsApi.md#apiV1CreativePoolsIdRecoverPost) | **POST** /api/v1/creative-pools/{id}/recover | Restore an archived creative pool. |
| [**apiV1CreativePoolsIdUsedInGet()**](CreativePoolsApi.md#apiV1CreativePoolsIdUsedInGet) | **GET** /api/v1/creative-pools/{id}/used-in | List campaigns / ads that reference this pool. |
| [**apiV1CreativePoolsPost()**](CreativePoolsApi.md#apiV1CreativePoolsPost) | **POST** /api/v1/creative-pools | Create a creative pool. |
| [**apiV1CreativesBulkDeletePost()**](CreativePoolsApi.md#apiV1CreativesBulkDeletePost) | **POST** /api/v1/creatives/bulk-delete | Soft-delete many media-bank creatives. |
| [**apiV1CreativesBulkUndeletePost()**](CreativePoolsApi.md#apiV1CreativesBulkUndeletePost) | **POST** /api/v1/creatives/bulk-undelete | Restore many deleted creatives. |
| [**apiV1CreativesIdUndeletePut()**](CreativePoolsApi.md#apiV1CreativesIdUndeletePut) | **PUT** /api/v1/creatives/{id}/undelete | Restore one deleted creative. |


## `apiV1CreativePoolsGet()`

```php
apiV1CreativePoolsGet($filter_type, $filter_types, $filter_title, $filter_id, $filter_name, $filter_orientation, $state, $search, $fit_orientation, $select_stats, $from, $to, $page, $per_page, $sort_by, $sort_dir): \GoAdServerApi\Model\ApiV1CreativePoolsGet200Response
```

List creative pools.

Returns every creative pool owned by the caller. Each row carries `items_count` (live items, `is_deleted=0`) and `total_sizes` (count of distinct banner sizes). Tags are returned both as the raw CSV string (`tags`) and as an array (`tagids`).  Filters use the `filter_` prefix (`filter_type`, `filter_title`, `filter_id`, `filter_orientation`); `search` matches the title. Sort with `sort_by` / `sort_dir`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativePoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$filter_type = 'filter_type_example'; // string
$filter_types = native,creator; // string | Comma-separated list of pool types (banner, video, reel, image, native, creator).
$filter_title = 'filter_title_example'; // string | Title contains.
$filter_id = 56; // int
$filter_name = 'filter_name_example'; // string | Same as filter_title.
$filter_orientation = 'filter_orientation_example'; // string | The pool's own orientation label — empty on most pools; fit_orientation looks at the creatives instead.
$state = 'live'; // string | deleted = the archived pools (deleted from the pool page or archived from Cleanup), to restore with POST /{id}/recover. Purged pools are in neither list.
$search = 'search_example'; // string | Title contains.
$fit_orientation = 'fit_orientation_example'; // string | Adds how many of each pool's servable creatives have this orientation, so a pool that would produce an unservable ad can be told apart.
$select_stats = 'select_stats_example'; // string | 1 = add views, clicks and conversions per pool for the from / to window (default the last 7 days).
$from = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | With select_stats=1: first day of the stats window (YYYY-MM-DD). Both from and to are needed; otherwise the last 7 days.
$to = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | With select_stats=1: last day of the stats window (YYYY-MM-DD).
$page = 1; // int
$per_page = new \GoAdServerApi\Model\\GoAdServerApi\Model\ApiV1CreativePoolsGetPerPageParameter(); // \GoAdServerApi\Model\ApiV1CreativePoolsGetPerPageParameter
$sort_by = 'id'; // string
$sort_dir = 'desc'; // string

try {
    $result = $apiInstance->apiV1CreativePoolsGet($filter_type, $filter_types, $filter_title, $filter_id, $filter_name, $filter_orientation, $state, $search, $fit_orientation, $select_stats, $from, $to, $page, $per_page, $sort_by, $sort_dir);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CreativePoolsApi->apiV1CreativePoolsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **filter_type** | **string**|  | [optional] |
| **filter_types** | **string**| Comma-separated list of pool types (banner, video, reel, image, native, creator). | [optional] |
| **filter_title** | **string**| Title contains. | [optional] |
| **filter_id** | **int**|  | [optional] |
| **filter_name** | **string**| Same as filter_title. | [optional] |
| **filter_orientation** | **string**| The pool&#39;s own orientation label — empty on most pools; fit_orientation looks at the creatives instead. | [optional] |
| **state** | **string**| deleted &#x3D; the archived pools (deleted from the pool page or archived from Cleanup), to restore with POST /{id}/recover. Purged pools are in neither list. | [optional] [default to &#39;live&#39;] |
| **search** | **string**| Title contains. | [optional] |
| **fit_orientation** | **string**| Adds how many of each pool&#39;s servable creatives have this orientation, so a pool that would produce an unservable ad can be told apart. | [optional] |
| **select_stats** | **string**| 1 &#x3D; add views, clicks and conversions per pool for the from / to window (default the last 7 days). | [optional] |
| **from** | **\DateTime**| With select_stats&#x3D;1: first day of the stats window (YYYY-MM-DD). Both from and to are needed; otherwise the last 7 days. | [optional] |
| **to** | **\DateTime**| With select_stats&#x3D;1: last day of the stats window (YYYY-MM-DD). | [optional] |
| **page** | **int**|  | [optional] [default to 1] |
| **per_page** | [**\GoAdServerApi\Model\ApiV1CreativePoolsGetPerPageParameter**](../Model/.md)|  | [optional] |
| **sort_by** | **string**|  | [optional] [default to &#39;id&#39;] |
| **sort_dir** | **string**|  | [optional] [default to &#39;desc&#39;] |

### Return type

[**\GoAdServerApi\Model\ApiV1CreativePoolsGet200Response**](../Model/ApiV1CreativePoolsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CreativePoolsIdAddCreativesPost()`

```php
apiV1CreativePoolsIdAddCreativesPost($id, $api_v1_creative_pools_id_add_creatives_post_request)
```

Add media-bank creatives to a pool.

Adds existing creatives (from `GET /api/v1/creatives` or `POST /api/v1/creatives/upload`) to the pool by id — the panel's \"Add from library\". Only creatives of the pool's own type are added; ids that are not yours, not that type, or already in the pool are skipped silently (`total` counts what was added). New items need moderation unless the account auto-validates. A key pinned to `creative_ids` may only add creatives on its pin (403 otherwise).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativePoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_creative_pools_id_add_creatives_post_request = {"creative_ids":[1082426,1082427]}; // \GoAdServerApi\Model\ApiV1CreativePoolsIdAddCreativesPostRequest

try {
    $apiInstance->apiV1CreativePoolsIdAddCreativesPost($id, $api_v1_creative_pools_id_add_creatives_post_request);
} catch (Exception $e) {
    echo 'Exception when calling CreativePoolsApi->apiV1CreativePoolsIdAddCreativesPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_creative_pools_id_add_creatives_post_request** | [**\GoAdServerApi\Model\ApiV1CreativePoolsIdAddCreativesPostRequest**](../Model/ApiV1CreativePoolsIdAddCreativesPostRequest.md)|  | |

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

## `apiV1CreativePoolsIdAddUrlsPost()`

```php
apiV1CreativePoolsIdAddUrlsPost($id, $api_v1_creative_pools_id_add_urls_post_request)
```

Add hotlinked image / video / HTML URLs to a pool.

Bulk import of hotlinked creatives — the panel's \"Add URLs\". One URL per line in `urls`; each becomes a media-bank creative plus a pool item. The protocol is normalised to https. The file extension decides the kind: jpg/jpeg/png/bmp/webp image, gif animated image, mp4 video; anything else (or an image into a video pool) is reported back in `err_data`. `type: html_url` hotlinks the URL as an iframe banner instead. Creator pools refuse URLs (422). Keys pinned to `creative_ids` cannot use this (it creates creatives).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativePoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_creative_pools_id_add_urls_post_request = {"urls":"https://cdn.example.com/a.jpg\nhttps://cdn.example.com/b.mp4","sizeid":71}; // \GoAdServerApi\Model\ApiV1CreativePoolsIdAddUrlsPostRequest

try {
    $apiInstance->apiV1CreativePoolsIdAddUrlsPost($id, $api_v1_creative_pools_id_add_urls_post_request);
} catch (Exception $e) {
    echo 'Exception when calling CreativePoolsApi->apiV1CreativePoolsIdAddUrlsPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_creative_pools_id_add_urls_post_request** | [**\GoAdServerApi\Model\ApiV1CreativePoolsIdAddUrlsPostRequest**](../Model/ApiV1CreativePoolsIdAddUrlsPostRequest.md)|  | |

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

## `apiV1CreativePoolsIdClonePost()`

```php
apiV1CreativePoolsIdClonePost($id): \GoAdServerApi\Model\ApiV1CreativePoolsIdClonePost201Response
```

Clone a creative pool with all its items.

Duplicates the pool row and every live item (`is_deleted=0`). Cloned items share the same underlying `creative_id`, so the binary creative file is not re-uploaded — both pools reference the same row in the `creatives` table. The new pool's title is suffixed with \" (Copy)\".

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativePoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $result = $apiInstance->apiV1CreativePoolsIdClonePost($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CreativePoolsApi->apiV1CreativePoolsIdClonePost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1CreativePoolsIdClonePost201Response**](../Model/ApiV1CreativePoolsIdClonePost201Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CreativePoolsIdDelete()`

```php
apiV1CreativePoolsIdDelete($id): \GoAdServerApi\Model\ApiV1CreativePoolsIdDelete200Response
```

Soft-delete a creative pool.

Deletes the pool **and** its items. Inactive items always go; an active item is kept only when it is the last active item of its banner size / rating that campaign ads still use. If any item had to be kept the call returns 409 (`total_items`, `deleted`) and the pool itself stays — the deletable items ARE already gone. Note the 200 body spells the count `totalitems`, the 409 body `total_items`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativePoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $result = $apiInstance->apiV1CreativePoolsIdDelete($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CreativePoolsApi->apiV1CreativePoolsIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1CreativePoolsIdDelete200Response**](../Model/ApiV1CreativePoolsIdDelete200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CreativePoolsIdGet()`

```php
apiV1CreativePoolsIdGet($id): \GoAdServerApi\Model\ApiV1CreativePoolsIdGet200Response
```

Show a single creative pool.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativePoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $result = $apiInstance->apiV1CreativePoolsIdGet($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CreativePoolsApi->apiV1CreativePoolsIdGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1CreativePoolsIdGet200Response**](../Model/ApiV1CreativePoolsIdGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CreativePoolsIdItemsExportGet()`

```php
apiV1CreativePoolsIdItemsExportGet($id, $ids): \SplFileObject
```

Download pool items' creative files as a ZIP.

Returns `application/zip` (`Content-Disposition: attachment; filename=\"pool-{id}-export.zip\"`) with one file per item plus a `_manifest.txt` listing which items were exported, skipped (no exportable source) or failed. Hotlinked items are fetched server-side (public addresses only). Max 500 items.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativePoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$ids = 'ids_example'; // string | Comma-separated item ids, e.g. `55120,55121`.

try {
    $result = $apiInstance->apiV1CreativePoolsIdItemsExportGet($id, $ids);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CreativePoolsApi->apiV1CreativePoolsIdItemsExportGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **ids** | **string**| Comma-separated item ids, e.g. &#x60;55120,55121&#x60;. | |

### Return type

**\SplFileObject**

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/zip`, `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CreativePoolsIdItemsGet()`

```php
apiV1CreativePoolsIdItemsGet($id, $include_sizes, $select_stats, $from, $to, $page, $per_page, $filter_bansizeid, $filter_isactive, $sort_by, $sort_dir): \GoAdServerApi\Model\ApiV1CreativesGet200Response
```

List items in a creative pool.

Returns every banner / video / native item in the pool with size, creative metadata and lifetime stats (views, clicks, ctr, conv), plus its schedule: `start_at` / `end_at` (unix seconds, 0 = no limit) and `is_fallback` (0/1) — see the item update endpoint.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativePoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$include_sizes = 'include_sizes_example'; // string | Banner pools: 1 = also return the pool's banner sizes with an item count each, saving a second request.
$select_stats = 'select_stats_example'; // string | 1 = add stats per item for the from / to window (default the last 7 days).
$from = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | With select_stats=1: first day of the stats window (YYYY-MM-DD). Both from and to are needed; otherwise the last 7 days.
$to = new \DateTime('2013-10-20T19:20:30+01:00'); // \DateTime | With select_stats=1: last day of the stats window (YYYY-MM-DD).
$page = 1; // int
$per_page = 20; // int
$filter_bansizeid = 56; // int | Banner size id.
$filter_isactive = 56; // int
$sort_by = 'id'; // string
$sort_dir = 'desc'; // string

try {
    $result = $apiInstance->apiV1CreativePoolsIdItemsGet($id, $include_sizes, $select_stats, $from, $to, $page, $per_page, $filter_bansizeid, $filter_isactive, $sort_by, $sort_dir);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CreativePoolsApi->apiV1CreativePoolsIdItemsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **include_sizes** | **string**| Banner pools: 1 &#x3D; also return the pool&#39;s banner sizes with an item count each, saving a second request. | [optional] |
| **select_stats** | **string**| 1 &#x3D; add stats per item for the from / to window (default the last 7 days). | [optional] |
| **from** | **\DateTime**| With select_stats&#x3D;1: first day of the stats window (YYYY-MM-DD). Both from and to are needed; otherwise the last 7 days. | [optional] |
| **to** | **\DateTime**| With select_stats&#x3D;1: last day of the stats window (YYYY-MM-DD). | [optional] |
| **page** | **int**|  | [optional] [default to 1] |
| **per_page** | **int**|  | [optional] [default to 20] |
| **filter_bansizeid** | **int**| Banner size id. | [optional] |
| **filter_isactive** | **int**|  | [optional] |
| **sort_by** | **string**|  | [optional] [default to &#39;id&#39;] |
| **sort_dir** | **string**|  | [optional] [default to &#39;desc&#39;] |

### Return type

[**\GoAdServerApi\Model\ApiV1CreativesGet200Response**](../Model/ApiV1CreativesGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CreativePoolsIdItemsItemIdDelete()`

```php
apiV1CreativePoolsIdItemsItemIdDelete($id, $item_id)
```

Soft-delete a single pool item.

Refuses if the item is still referenced by a live ad — unhook the ad first.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativePoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$item_id = 56; // int

try {
    $apiInstance->apiV1CreativePoolsIdItemsItemIdDelete($id, $item_id);
} catch (Exception $e) {
    echo 'Exception when calling CreativePoolsApi->apiV1CreativePoolsIdItemsItemIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **item_id** | **int**|  | |

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

## `apiV1CreativePoolsIdItemsItemIdPut()`

```php
apiV1CreativePoolsIdItemsItemIdPut($id, $item_id, $api_v1_creative_pools_id_items_item_id_put_request)
```

Update a single pool item.

Editable fields: `title`, `lang`, `properties`, `ratingid`, `url_prefix`, `weight` (whole number 0–1000000; drives `sort_type: wr` rotation — 400 `invalid_weight` otherwise), `is_fallback`, `start_at`, `end_at`. Active state is toggled via the `/toggle` endpoint below.  **Scheduling.** `start_at` / `end_at` make the item serve only inside `[start_at, end_at)`. Send unix seconds or an RFC 3339 time with its offset (`2026-10-20T09:00:00-04:00` = 9:00 AM New York); `null`, `0` or `\"\"` clears that side. The list endpoint returns both as unix seconds (0 = no limit). The ad servers pick up an edit within 5 minutes and then switch on the exact second, so save a schedule a few minutes ahead of its start.  **Fallback.** `is_fallback: true` makes the item serve only while no other item of the same pool and size is live. Typical promo swap: mark the regular creative as fallback and give the promo a `start_at` (and `end_at`); the regular one steps aside while the promo runs and returns when it ends.  A change that would leave a size still used by campaign ads with nothing to serve at some moment is refused with 409 `SCHEDULE_LEAVES_POOL_EMPTY`; `gap_at` is the first uncovered unix second. Pausing or deleting an item runs the same check over the other items' schedules.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativePoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$item_id = 56; // int
$api_v1_creative_pools_id_items_item_id_put_request = {"is_fallback":false,"start_at":"2026-10-20T09:00:00-04:00","end_at":"2026-10-27T09:00:00-04:00"}; // \GoAdServerApi\Model\ApiV1CreativePoolsIdItemsItemIdPutRequest

try {
    $apiInstance->apiV1CreativePoolsIdItemsItemIdPut($id, $item_id, $api_v1_creative_pools_id_items_item_id_put_request);
} catch (Exception $e) {
    echo 'Exception when calling CreativePoolsApi->apiV1CreativePoolsIdItemsItemIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **item_id** | **int**|  | |
| **api_v1_creative_pools_id_items_item_id_put_request** | [**\GoAdServerApi\Model\ApiV1CreativePoolsIdItemsItemIdPutRequest**](../Model/ApiV1CreativePoolsIdItemsItemIdPutRequest.md)|  | |

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

## `apiV1CreativePoolsIdItemsItemIdTogglePut()`

```php
apiV1CreativePoolsIdItemsItemIdTogglePut($id, $item_id)
```

Toggle a pool item on / off.

Flips `isactive` between 0 and 1. Inactive items are skipped by the ad-server when picking a creative from the pool. Pausing the last active item of a format that campaign ads still use is refused (409).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativePoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$item_id = 56; // int

try {
    $apiInstance->apiV1CreativePoolsIdItemsItemIdTogglePut($id, $item_id);
} catch (Exception $e) {
    echo 'Exception when calling CreativePoolsApi->apiV1CreativePoolsIdItemsItemIdTogglePut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **item_id** | **int**|  | |

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

## `apiV1CreativePoolsIdItemsMassActionPut()`

```php
apiV1CreativePoolsIdItemsMassActionPut($id, $api_v1_creative_pools_id_items_mass_action_put_request)
```

Bulk-change items of a creative pool.

`action`:  - `activate` / `deactivate` — set `isactive`. - `delete` — soft-delete the items. - `url_prefix` — set each item's `url_prefix` to `url_prefix`. - `language` — set each item's `lang` to `lang`.  Deactivate and delete refuse (409, nothing written) when an item is the last active one of its size / rating still used by a campaign ad — `blocked_ids` lists them, the same rule as the single-item toggle / delete. Same handler as the panel.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativePoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_creative_pools_id_items_mass_action_put_request = {"action":"deactivate","ids":[55120,55121]}; // \GoAdServerApi\Model\ApiV1CreativePoolsIdItemsMassActionPutRequest

try {
    $apiInstance->apiV1CreativePoolsIdItemsMassActionPut($id, $api_v1_creative_pools_id_items_mass_action_put_request);
} catch (Exception $e) {
    echo 'Exception when calling CreativePoolsApi->apiV1CreativePoolsIdItemsMassActionPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_creative_pools_id_items_mass_action_put_request** | [**\GoAdServerApi\Model\ApiV1CreativePoolsIdItemsMassActionPutRequest**](../Model/ApiV1CreativePoolsIdItemsMassActionPutRequest.md)|  | |

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

## `apiV1CreativePoolsIdItemsPost()`

```php
apiV1CreativePoolsIdItemsPost($id, $api_v1_creative_pools_id_items_post_request)
```

Add an item to a creative pool.

Adds one creative to the pool. Reference an uploaded creative by `creative_id` (from `POST /api/v1/creatives/upload`; must be yours, and on the key's creative pin if it has one) or supply a hotlink `banner_url`. The pool's `type` decides the creative slot (banner / video / image). To add many library creatives at once use `POST …/{id}/add-creatives`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativePoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_creative_pools_id_items_post_request = {"creative_id":1082426,"title":"Q3 banner"}; // \GoAdServerApi\Model\ApiV1CreativePoolsIdItemsPostRequest

try {
    $apiInstance->apiV1CreativePoolsIdItemsPost($id, $api_v1_creative_pools_id_items_post_request);
} catch (Exception $e) {
    echo 'Exception when calling CreativePoolsApi->apiV1CreativePoolsIdItemsPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_creative_pools_id_items_post_request** | [**\GoAdServerApi\Model\ApiV1CreativePoolsIdItemsPostRequest**](../Model/ApiV1CreativePoolsIdItemsPostRequest.md)|  | |

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

## `apiV1CreativePoolsIdItemsRecoverPost()`

```php
apiV1CreativePoolsIdItemsRecoverPost($id, $api_v1_creative_pools_id_items_recover_post_request)
```

Put removed items back into a creative pool.

The undo of DELETE /creative-pools/{id}/items/{itemId} (removing an item also switches it off). `active_ids` are switched back on.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativePoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_creative_pools_id_items_recover_post_request = new \GoAdServerApi\Model\ApiV1CreativePoolsIdItemsRecoverPostRequest(); // \GoAdServerApi\Model\ApiV1CreativePoolsIdItemsRecoverPostRequest

try {
    $apiInstance->apiV1CreativePoolsIdItemsRecoverPost($id, $api_v1_creative_pools_id_items_recover_post_request);
} catch (Exception $e) {
    echo 'Exception when calling CreativePoolsApi->apiV1CreativePoolsIdItemsRecoverPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_creative_pools_id_items_recover_post_request** | [**\GoAdServerApi\Model\ApiV1CreativePoolsIdItemsRecoverPostRequest**](../Model/ApiV1CreativePoolsIdItemsRecoverPostRequest.md)|  | |

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

## `apiV1CreativePoolsIdPut()`

```php
apiV1CreativePoolsIdPut($id, $api_v1_creative_pools_id_put_request)
```

Update creative pool meta.

Updates any of `title` (alias `name`), `sort_type`, `conv_type`, `tags`, `type`, `orientation`. `sort_type: wr` (weighted rotation on item `weight`) needs the tenant's weighted-rotation option — 400 `weighted_rotation_disabled` otherwise.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativePoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_creative_pools_id_put_request = new \GoAdServerApi\Model\ApiV1CreativePoolsIdPutRequest(); // \GoAdServerApi\Model\ApiV1CreativePoolsIdPutRequest

try {
    $apiInstance->apiV1CreativePoolsIdPut($id, $api_v1_creative_pools_id_put_request);
} catch (Exception $e) {
    echo 'Exception when calling CreativePoolsApi->apiV1CreativePoolsIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_creative_pools_id_put_request** | [**\GoAdServerApi\Model\ApiV1CreativePoolsIdPutRequest**](../Model/ApiV1CreativePoolsIdPutRequest.md)|  | |

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

## `apiV1CreativePoolsIdRecoverPost()`

```php
apiV1CreativePoolsIdRecoverPost($id)
```

Restore an archived creative pool.

Reverses the soft delete of DELETE /creative-pools/{id} (and of Cleanup's Archive): the pool is listed and usable again. The items the delete took down come back as they were; for a pool deleted before 2026-10-16 every deleted item comes back paused (`items_paused: true`). 404 when the pool does not exist, is not archived, or was purged.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativePoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1CreativePoolsIdRecoverPost($id);
} catch (Exception $e) {
    echo 'Exception when calling CreativePoolsApi->apiV1CreativePoolsIdRecoverPost: ', $e->getMessage(), PHP_EOL;
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

## `apiV1CreativePoolsIdUsedInGet()`

```php
apiV1CreativePoolsIdUsedInGet($id): \GoAdServerApi\Model\ApiV1CreativePoolsIdUsedInGet200Response
```

List campaigns / ads that reference this pool.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativePoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $result = $apiInstance->apiV1CreativePoolsIdUsedInGet($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CreativePoolsApi->apiV1CreativePoolsIdUsedInGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1CreativePoolsIdUsedInGet200Response**](../Model/ApiV1CreativePoolsIdUsedInGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CreativePoolsPost()`

```php
apiV1CreativePoolsPost($api_v1_creative_pools_post_request): \GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response
```

Create a creative pool.

Creates an empty pool. Fill it with `POST …/{id}/add-creatives` (media-bank ids), `POST …/{id}/add-urls` (hotlinks) or `POST …/{id}/items` (one creative). Title collisions are auto-suffixed with `_<unix>`. An unknown `type` is a 400 `invalid_type` (it used to fall back to banner).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativePoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_creative_pools_post_request = new \GoAdServerApi\Model\ApiV1CreativePoolsPostRequest(); // \GoAdServerApi\Model\ApiV1CreativePoolsPostRequest

try {
    $result = $apiInstance->apiV1CreativePoolsPost($api_v1_creative_pools_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CreativePoolsApi->apiV1CreativePoolsPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_creative_pools_post_request** | [**\GoAdServerApi\Model\ApiV1CreativePoolsPostRequest**](../Model/ApiV1CreativePoolsPostRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response**](../Model/ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1CreativesBulkDeletePost()`

```php
apiV1CreativesBulkDeletePost($api_v1_creatives_bulk_delete_post_request)
```

Soft-delete many media-bank creatives.

Sets `isdeleted = 1` on up to 500 of your creatives. Ads and pool items already pointing at them keep working; deleted creatives just leave the library. Restore with `POST /creatives/bulk-undelete` or `PUT /creatives/{id}/undelete`. A key pinned to `creative_ids` may only name pinned ids (403 `forbidden_resource`). Account members with own-scoped creative rights only affect creatives they manage. Same handler as the panel.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativePoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_creatives_bulk_delete_post_request = {"ids":[99102,99103]}; // \GoAdServerApi\Model\ApiV1CreativesBulkDeletePostRequest

try {
    $apiInstance->apiV1CreativesBulkDeletePost($api_v1_creatives_bulk_delete_post_request);
} catch (Exception $e) {
    echo 'Exception when calling CreativePoolsApi->apiV1CreativesBulkDeletePost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_creatives_bulk_delete_post_request** | [**\GoAdServerApi\Model\ApiV1CreativesBulkDeletePostRequest**](../Model/ApiV1CreativesBulkDeletePostRequest.md)|  | |

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

## `apiV1CreativesBulkUndeletePost()`

```php
apiV1CreativesBulkUndeletePost($api_v1_creatives_bulk_undelete_post_request)
```

Restore many deleted creatives.

Inverse of `POST /creatives/bulk-delete` — same limits, pin and member rules.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativePoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_creatives_bulk_undelete_post_request = {ids=[99102, 99103]}; // \GoAdServerApi\Model\ApiV1CreativesBulkUndeletePostRequest

try {
    $apiInstance->apiV1CreativesBulkUndeletePost($api_v1_creatives_bulk_undelete_post_request);
} catch (Exception $e) {
    echo 'Exception when calling CreativePoolsApi->apiV1CreativesBulkUndeletePost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_creatives_bulk_undelete_post_request** | [**\GoAdServerApi\Model\ApiV1CreativesBulkUndeletePostRequest**](../Model/ApiV1CreativesBulkUndeletePostRequest.md)|  | |

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

## `apiV1CreativesIdUndeletePut()`

```php
apiV1CreativesIdUndeletePut($id)
```

Restore one deleted creative.

Undoes `DELETE /creatives/{id}`. Same handler as the panel. No body.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativePoolsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1CreativesIdUndeletePut($id);
} catch (Exception $e) {
    echo 'Exception when calling CreativePoolsApi->apiV1CreativesIdUndeletePut: ', $e->getMessage(), PHP_EOL;
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
