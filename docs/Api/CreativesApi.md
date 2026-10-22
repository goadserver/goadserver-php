# GoAdServerApi\CreativesApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1CreativeFoldersGet()**](CreativesApi.md#apiV1CreativeFoldersGet) | **GET** /api/v1/creative-folders | List media-bank folders. |
| [**apiV1CreativeFoldersIdDelete()**](CreativesApi.md#apiV1CreativeFoldersIdDelete) | **DELETE** /api/v1/creative-folders/{id} | Delete a folder. |
| [**apiV1CreativeFoldersIdPut()**](CreativesApi.md#apiV1CreativeFoldersIdPut) | **PUT** /api/v1/creative-folders/{id} | Rename or move a folder. |
| [**apiV1CreativeFoldersPost()**](CreativesApi.md#apiV1CreativeFoldersPost) | **POST** /api/v1/creative-folders | Create a folder. |
| [**apiV1CreativesBulkMovePost()**](CreativesApi.md#apiV1CreativesBulkMovePost) | **POST** /api/v1/creatives/bulk-move | File creatives into a folder. |
| [**apiV1CreativesGet()**](CreativesApi.md#apiV1CreativesGet) | **GET** /api/v1/creatives | List the creative library. |
| [**apiV1CreativesIdDelete()**](CreativesApi.md#apiV1CreativesIdDelete) | **DELETE** /api/v1/creatives/{id} | Delete a creative. |
| [**apiV1CreativesIdGet()**](CreativesApi.md#apiV1CreativesIdGet) | **GET** /api/v1/creatives/{id} | Show one creative. |
| [**apiV1CreativesIdNamePut()**](CreativesApi.md#apiV1CreativesIdNamePut) | **PUT** /api/v1/creatives/{id}/name | Rename a creative (panel inline edit). |
| [**apiV1CreativesIdPut()**](CreativesApi.md#apiV1CreativesIdPut) | **PUT** /api/v1/creatives/{id} | Rename and/or re-tag a creative. |
| [**apiV1CreativesIdTagsPut()**](CreativesApi.md#apiV1CreativesIdTagsPut) | **PUT** /api/v1/creatives/{id}/tags | Replace a creative&#39;s tags (panel inline edit). |
| [**apiV1CreativesIdUsedInGet()**](CreativesApi.md#apiV1CreativesIdUsedInGet) | **GET** /api/v1/creatives/{id}/used-in | List ads / pools referencing this creative. |
| [**apiV1CreativesUploadPost()**](CreativesApi.md#apiV1CreativesUploadPost) | **POST** /api/v1/creatives/upload | Upload creative files. |


## `apiV1CreativeFoldersGet()`

```php
apiV1CreativeFoldersGet($with_counts, $creative_type)
```

List media-bank folders.

The account's folder tree: `{id, name, pid, level}` rows, `pid` 0 for a root, nested at most 3 tiers deep. Folders are the media bank's exclusive axis — one folder per creative — and are separate from the multi-valued groups on `creatives.tags`.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$with_counts = 'with_counts_example'; // string | Add `count` (live creatives) per folder plus meta.unfiled_count and meta.creative_count.
$creative_type = 'creative_type_example'; // string | Narrow the counts to one creative type (comma-separated list accepted).

try {
    $apiInstance->apiV1CreativeFoldersGet($with_counts, $creative_type);
} catch (Exception $e) {
    echo 'Exception when calling CreativesApi->apiV1CreativeFoldersGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **with_counts** | **string**| Add &#x60;count&#x60; (live creatives) per folder plus meta.unfiled_count and meta.creative_count. | [optional] |
| **creative_type** | **string**| Narrow the counts to one creative type (comma-separated list accepted). | [optional] |

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

## `apiV1CreativeFoldersIdDelete()`

```php
apiV1CreativeFoldersIdDelete($id, $move_to)
```

Delete a folder.

Never deletes creatives. The folder's creatives become unfiled — or move to `move_to` — and its subfolders are reparented to the deleted folder's own parent.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$move_to = 56; // int | File the folder's creatives here instead of unfiling them.

try {
    $apiInstance->apiV1CreativeFoldersIdDelete($id, $move_to);
} catch (Exception $e) {
    echo 'Exception when calling CreativesApi->apiV1CreativeFoldersIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **move_to** | **int**| File the folder&#39;s creatives here instead of unfiling them. | [optional] |

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

## `apiV1CreativeFoldersIdPut()`

```php
apiV1CreativeFoldersIdPut($id, $api_v1_creative_folders_id_put_request)
```

Rename or move a folder.

`name` is required. `pid` is optional — omitting it leaves the folder where it is; sending it reparents the folder and its whole subtree, which is refused if that would exceed the depth cap or form a cycle.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_creative_folders_id_put_request = new \GoAdServerApi\Model\ApiV1CreativeFoldersIdPutRequest(); // \GoAdServerApi\Model\ApiV1CreativeFoldersIdPutRequest

try {
    $apiInstance->apiV1CreativeFoldersIdPut($id, $api_v1_creative_folders_id_put_request);
} catch (Exception $e) {
    echo 'Exception when calling CreativesApi->apiV1CreativeFoldersIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_creative_folders_id_put_request** | [**\GoAdServerApi\Model\ApiV1CreativeFoldersIdPutRequest**](../Model/ApiV1CreativeFoldersIdPutRequest.md)|  | |

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

## `apiV1CreativeFoldersPost()`

```php
apiV1CreativeFoldersPost($api_v1_creative_folders_post_request)
```

Create a folder.

A folder whose name already exists under the same parent is returned as-is with `existing: true` rather than duplicated, so this is safe to call as create-or-reuse.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_creative_folders_post_request = new \GoAdServerApi\Model\ApiV1CreativeFoldersPostRequest(); // \GoAdServerApi\Model\ApiV1CreativeFoldersPostRequest

try {
    $apiInstance->apiV1CreativeFoldersPost($api_v1_creative_folders_post_request);
} catch (Exception $e) {
    echo 'Exception when calling CreativesApi->apiV1CreativeFoldersPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_creative_folders_post_request** | [**\GoAdServerApi\Model\ApiV1CreativeFoldersPostRequest**](../Model/ApiV1CreativeFoldersPostRequest.md)|  | |

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

## `apiV1CreativesBulkMovePost()`

```php
apiV1CreativesBulkMovePost($api_v1_creatives_bulk_move_post_request)
```

File creatives into a folder.

Moves creatives into one media-bank folder. A creative sits in exactly one folder, so this is an assignment: rows already filed elsewhere are moved, and `folder_id: 0` unfiles them.  Two mutually exclusive selections:    * `ids` — an explicit list (max 20000, applied in chunks).   * `filter` — every creative matching a listing filter, given as the     same query params `GET /api/v1/creatives` accepts     (`creative_type`, `sizeid`, `orientation`, `folder_id`,     `include_subfolders`, `deleted`/`all`). Use this to file a whole     library; it is applied in batches server-side.  Nothing outside the key's own account can be reached by either form.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_creatives_bulk_move_post_request = new \GoAdServerApi\Model\ApiV1CreativesBulkMovePostRequest(); // \GoAdServerApi\Model\ApiV1CreativesBulkMovePostRequest

try {
    $apiInstance->apiV1CreativesBulkMovePost($api_v1_creatives_bulk_move_post_request);
} catch (Exception $e) {
    echo 'Exception when calling CreativesApi->apiV1CreativesBulkMovePost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_creatives_bulk_move_post_request** | [**\GoAdServerApi\Model\ApiV1CreativesBulkMovePostRequest**](../Model/ApiV1CreativesBulkMovePostRequest.md)|  | |

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

## `apiV1CreativesGet()`

```php
apiV1CreativesGet($creative_type, $sizeid, $orientation, $deleted, $all, $filter_name, $search, $sort_by, $sort_dir, $folder_id, $include_subfolders, $page, $per_page, $limit, $offset, $filter_id, $filter_type, $filter_sizeid): \GoAdServerApi\Model\ApiV1CreativesGet200Response
```

List the creative library.

Uploaded creatives (banner / icon / native / video / reel) owned by the key.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$creative_type = native,creator; // string | One creative type, or a comma-separated list: banner, icon, native, push, video, reel, image, creator. (A bare `type` parameter is not read.)
$sizeid = 56; // int | Banner size id.
$orientation = 'orientation_example'; // string
$deleted = 'deleted_example'; // string | 1 = only deleted creatives.
$all = 'all_example'; // string | 1 = live and deleted creatives together.
$filter_name = 'filter_name_example'; // string | Name contains.
$search = 'search_example'; // string | Name contains.
$sort_by = 'id'; // string
$sort_dir = 'desc'; // string
$folder_id = 56; // int | Media-bank folder id. 0 selects the unfiled creatives; omit for every folder.
$include_subfolders = '1'; // string | With folder_id, also return creatives filed in that folder's subfolders.
$page = 1; // int
$per_page = new \GoAdServerApi\Model\\GoAdServerApi\Model\ApiV1CreativesGetPerPageParameter(); // \GoAdServerApi\Model\ApiV1CreativesGetPerPageParameter | Page size (max 100), or `all` for the whole library in one page.
$limit = 56; // int | Offset paging: page size (per_page wins when both are sent).
$offset = 56; // int | Offset paging: rows to skip.
$filter_id = 56; // int
$filter_type = 'filter_type_example'; // string | Exactly one type; creative_type also takes a list.
$filter_sizeid = 56; // int | Same as sizeid.

try {
    $result = $apiInstance->apiV1CreativesGet($creative_type, $sizeid, $orientation, $deleted, $all, $filter_name, $search, $sort_by, $sort_dir, $folder_id, $include_subfolders, $page, $per_page, $limit, $offset, $filter_id, $filter_type, $filter_sizeid);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CreativesApi->apiV1CreativesGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **creative_type** | **string**| One creative type, or a comma-separated list: banner, icon, native, push, video, reel, image, creator. (A bare &#x60;type&#x60; parameter is not read.) | [optional] |
| **sizeid** | **int**| Banner size id. | [optional] |
| **orientation** | **string**|  | [optional] |
| **deleted** | **string**| 1 &#x3D; only deleted creatives. | [optional] |
| **all** | **string**| 1 &#x3D; live and deleted creatives together. | [optional] |
| **filter_name** | **string**| Name contains. | [optional] |
| **search** | **string**| Name contains. | [optional] |
| **sort_by** | **string**|  | [optional] [default to &#39;id&#39;] |
| **sort_dir** | **string**|  | [optional] [default to &#39;desc&#39;] |
| **folder_id** | **int**| Media-bank folder id. 0 selects the unfiled creatives; omit for every folder. | [optional] |
| **include_subfolders** | **string**| With folder_id, also return creatives filed in that folder&#39;s subfolders. | [optional] [default to &#39;1&#39;] |
| **page** | **int**|  | [optional] [default to 1] |
| **per_page** | [**\GoAdServerApi\Model\ApiV1CreativesGetPerPageParameter**](../Model/.md)| Page size (max 100), or &#x60;all&#x60; for the whole library in one page. | [optional] |
| **limit** | **int**| Offset paging: page size (per_page wins when both are sent). | [optional] |
| **offset** | **int**| Offset paging: rows to skip. | [optional] |
| **filter_id** | **int**|  | [optional] |
| **filter_type** | **string**| Exactly one type; creative_type also takes a list. | [optional] |
| **filter_sizeid** | **int**| Same as sizeid. | [optional] |

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

## `apiV1CreativesIdDelete()`

```php
apiV1CreativesIdDelete($id)
```

Delete a creative.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1CreativesIdDelete($id);
} catch (Exception $e) {
    echo 'Exception when calling CreativesApi->apiV1CreativesIdDelete: ', $e->getMessage(), PHP_EOL;
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

## `apiV1CreativesIdGet()`

```php
apiV1CreativesIdGet($id)
```

Show one creative.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1CreativesIdGet($id);
} catch (Exception $e) {
    echo 'Exception when calling CreativesApi->apiV1CreativesIdGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1CreativesIdNamePut()`

```php
apiV1CreativesIdNamePut($id, $api_v1_creatives_id_name_put_request)
```

Rename a creative (panel inline edit).

The panel's own rename call. Also retitles the pool items built from this creative. Prefer `PUT /api/v1/creatives/{id}` for new code.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_creatives_id_name_put_request = new \GoAdServerApi\Model\ApiV1CreativesIdNamePutRequest(); // \GoAdServerApi\Model\ApiV1CreativesIdNamePutRequest

try {
    $apiInstance->apiV1CreativesIdNamePut($id, $api_v1_creatives_id_name_put_request);
} catch (Exception $e) {
    echo 'Exception when calling CreativesApi->apiV1CreativesIdNamePut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_creatives_id_name_put_request** | [**\GoAdServerApi\Model\ApiV1CreativesIdNamePutRequest**](../Model/ApiV1CreativesIdNamePutRequest.md)|  | |

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

## `apiV1CreativesIdPut()`

```php
apiV1CreativesIdPut($id, $api_v1_creatives_id_put_request)
```

Rename and/or re-tag a creative.

The panel's two inline creative edits in one strict call: `name` (also retitles every pool item built from this creative, like the panel rename) and `tags`. Send either or both; unknown fields are a 422. The creative's file / content cannot be changed — upload a new creative instead. Same ownership and member-scope checks as the panel (an own-scoped team member can only edit creatives they made).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_creatives_id_put_request = {"name":"Summer promo 300x250","tags":[12,17]}; // \GoAdServerApi\Model\ApiV1CreativesIdPutRequest

try {
    $apiInstance->apiV1CreativesIdPut($id, $api_v1_creatives_id_put_request);
} catch (Exception $e) {
    echo 'Exception when calling CreativesApi->apiV1CreativesIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_creatives_id_put_request** | [**\GoAdServerApi\Model\ApiV1CreativesIdPutRequest**](../Model/ApiV1CreativesIdPutRequest.md)|  | |

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

## `apiV1CreativesIdTagsPut()`

```php
apiV1CreativesIdTagsPut($id, $api_v1_creatives_id_tags_put_request)
```

Replace a creative's tags (panel inline edit).

The panel's own tag edit. `tags` is an array of tenant tag ids; non-numeric entries are dropped and [] clears the tags.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int
$api_v1_creatives_id_tags_put_request = {"tags":[12,17]}; // \GoAdServerApi\Model\ApiV1CreativesIdTagsPutRequest

try {
    $apiInstance->apiV1CreativesIdTagsPut($id, $api_v1_creatives_id_tags_put_request);
} catch (Exception $e) {
    echo 'Exception when calling CreativesApi->apiV1CreativesIdTagsPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**|  | |
| **api_v1_creatives_id_tags_put_request** | [**\GoAdServerApi\Model\ApiV1CreativesIdTagsPutRequest**](../Model/ApiV1CreativesIdTagsPutRequest.md)|  | |

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

## `apiV1CreativesIdUsedInGet()`

```php
apiV1CreativesIdUsedInGet($id)
```

List ads / pools referencing this creative.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int

try {
    $apiInstance->apiV1CreativesIdUsedInGet($id);
} catch (Exception $e) {
    echo 'Exception when calling CreativesApi->apiV1CreativesIdUsedInGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1CreativesUploadPost()`

```php
apiV1CreativesUploadPost($files, $type, $banner_size, $tags, $folder_id, $folder_name, $upload_zip, $folder_from_zip, $folder_from_zip_paths, $zipfile, $icontags, $properties, $skiperrors, $use_overlays, $clone_per_overlay, $overlay, $overlays_zip)
```

Upload creative files.

`multipart/form-data` upload of one or more real creative files. The server stores each file, creates a `creatives` row and returns the new ids — reference them later via `creative_id` on ad-create or pool-item-add. Field `files` (repeatable) carries the uploads (`files[]`, `files[0]`…, `image`, `image[]`, `image[0]`… and a singular `file` / `file[]` are accepted too); `type` selects the creative kind. `creator` takes a poster image paired with a hover video; `skin` a site-skin image; `html5` takes HTML5 rich-media banners — each file is one .zip with an index.html at the top (or inside one top folder), max 5 MB zipped / 20 MB unpacked / 300 files, only web file types (html, css, js, json, images, fonts, mp4/webm/mp3). The size is `banner_size` or the bundle's `<meta name=\"ad.size\" content=\"width=300,height=250\">`. It is created as a banner creative (content_type `html5`) and served as an iframe with the IAB `clickTag` query parameter (also `clickTAG`, `clicktag`) set to its click URL — open it from the creative's click handler (`window.open(clickTag)`) so the click is counted. `upload_zip=1` is refused for html5 (each zip is one banner).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\CreativesApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$files = array('/path/to/file.txt'); // \SplFileObject[] | One or more creative files.
$type = 'banner'; // string | audio = audio-ad creatives (mp3, m4a, aac, ogg, opus or wav, 3-120 s): re-encoded to a 128 kbps mp3, stored as <id>.mp3, the duration measured. Use the id as creative_id with video_type=creative on an audio ad (adtype 43).
$banner_size = 56; // int | Banner size id; auto-derived from image dimensions when omitted. An image exactly 2x or 3x a size is kept at full resolution under that size (served at the size, sharp on high-density screens); pass the 1x size id when the 2x dimensions are a size of their own.
$tags = 'tags_example'; // string
$folder_id = 56; // int | File the batch into this existing media-bank folder. Omit or 0 for unfiled.
$folder_name = 'folder_name_example'; // string | Create-or-reuse a folder with this name under folder_id and file the batch there.
$upload_zip = 'upload_zip_example'; // string | Send one archive as `zipfile` instead of `files`; the server extracts it.
$folder_from_zip = 'folder_from_zip_example'; // string | ZIP uploads only: name the folder after the archive (extension dropped), under folder_id / folder_name.
$folder_from_zip_paths = 'folder_from_zip_paths_example'; // string | ZIP uploads only: recreate the archive's own directories as subfolders, up to 3 tiers.
$zipfile = '/path/to/file.txt'; // \SplFileObject | With upload_zip=1: the archive to extract.
$icontags = 'icontags_example'; // string | type=icon only: tags for the icons; overrides `tags` when sent.
$properties = 'properties_example'; // string | Comma-separated property ids stamped on every created creative.
$skiperrors = 'skiperrors_example'; // string | 1 = keep going past a file that fails and report it in `errors`; 0 stops at the first failure.
$use_overlays = 'use_overlays_example'; // string | 1 = composite the PNG overlays sent on `overlay` / `overlays_zip` onto the uploaded images.
$clone_per_overlay = 'clone_per_overlay_example'; // string | With use_overlays=1: create one variant per overlay instead of rotating the overlays across the files.
$overlay = array('/path/to/file.txt'); // \SplFileObject[] | Overlay PNGs (repeatable, also accepted as overlay[]). Order sets the rotation.
$overlays_zip = array('/path/to/file.txt'); // \SplFileObject[] | ZIP archives of overlay PNGs (repeatable, also overlays_zip[]); entries are used in name order after the loose overlays.

try {
    $apiInstance->apiV1CreativesUploadPost($files, $type, $banner_size, $tags, $folder_id, $folder_name, $upload_zip, $folder_from_zip, $folder_from_zip_paths, $zipfile, $icontags, $properties, $skiperrors, $use_overlays, $clone_per_overlay, $overlay, $overlays_zip);
} catch (Exception $e) {
    echo 'Exception when calling CreativesApi->apiV1CreativesUploadPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **files** | **\SplFileObject[]**| One or more creative files. | |
| **type** | **string**| audio &#x3D; audio-ad creatives (mp3, m4a, aac, ogg, opus or wav, 3-120 s): re-encoded to a 128 kbps mp3, stored as &lt;id&gt;.mp3, the duration measured. Use the id as creative_id with video_type&#x3D;creative on an audio ad (adtype 43). | [optional] [default to &#39;banner&#39;] |
| **banner_size** | **int**| Banner size id; auto-derived from image dimensions when omitted. An image exactly 2x or 3x a size is kept at full resolution under that size (served at the size, sharp on high-density screens); pass the 1x size id when the 2x dimensions are a size of their own. | [optional] |
| **tags** | **string**|  | [optional] |
| **folder_id** | **int**| File the batch into this existing media-bank folder. Omit or 0 for unfiled. | [optional] |
| **folder_name** | **string**| Create-or-reuse a folder with this name under folder_id and file the batch there. | [optional] |
| **upload_zip** | **string**| Send one archive as &#x60;zipfile&#x60; instead of &#x60;files&#x60;; the server extracts it. | [optional] |
| **folder_from_zip** | **string**| ZIP uploads only: name the folder after the archive (extension dropped), under folder_id / folder_name. | [optional] |
| **folder_from_zip_paths** | **string**| ZIP uploads only: recreate the archive&#39;s own directories as subfolders, up to 3 tiers. | [optional] |
| **zipfile** | **\SplFileObject****\SplFileObject**| With upload_zip&#x3D;1: the archive to extract. | [optional] |
| **icontags** | **string**| type&#x3D;icon only: tags for the icons; overrides &#x60;tags&#x60; when sent. | [optional] |
| **properties** | **string**| Comma-separated property ids stamped on every created creative. | [optional] |
| **skiperrors** | **string**| 1 &#x3D; keep going past a file that fails and report it in &#x60;errors&#x60;; 0 stops at the first failure. | [optional] |
| **use_overlays** | **string**| 1 &#x3D; composite the PNG overlays sent on &#x60;overlay&#x60; / &#x60;overlays_zip&#x60; onto the uploaded images. | [optional] |
| **clone_per_overlay** | **string**| With use_overlays&#x3D;1: create one variant per overlay instead of rotating the overlays across the files. | [optional] |
| **overlay** | **\SplFileObject[]**| Overlay PNGs (repeatable, also accepted as overlay[]). Order sets the rotation. | [optional] |
| **overlays_zip** | **\SplFileObject[]**| ZIP archives of overlay PNGs (repeatable, also overlays_zip[]); entries are used in name order after the loose overlays. | [optional] |

### Return type

void (empty response body)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `multipart/form-data`
- **Accept**: Not defined

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
