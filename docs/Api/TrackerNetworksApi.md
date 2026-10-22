# GoAdServerApi\TrackerNetworksApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1TrackerAdNetworksDeleteReportsPut()**](TrackerNetworksApi.md#apiV1TrackerAdNetworksDeleteReportsPut) | **PUT** /api/v1/tracker/ad-networks/delete-reports | Delete pulled spend reports. |
| [**apiV1TrackerAdNetworksGet()**](TrackerNetworksApi.md#apiV1TrackerAdNetworksGet) | **GET** /api/v1/tracker/ad-networks | List ad networks. |
| [**apiV1TrackerAdNetworksIdCampaignsGet()**](TrackerNetworksApi.md#apiV1TrackerAdNetworksIdCampaignsGet) | **GET** /api/v1/tracker/ad-networks/{id}/campaigns | Campaigns known on one ad network. |
| [**apiV1TrackerAdNetworksIdDelete()**](TrackerNetworksApi.md#apiV1TrackerAdNetworksIdDelete) | **DELETE** /api/v1/tracker/ad-networks/{id} | Delete an ad network. |
| [**apiV1TrackerAdNetworksIdGet()**](TrackerNetworksApi.md#apiV1TrackerAdNetworksIdGet) | **GET** /api/v1/tracker/ad-networks/{id} | Get one ad network. |
| [**apiV1TrackerAdNetworksIdPut()**](TrackerNetworksApi.md#apiV1TrackerAdNetworksIdPut) | **PUT** /api/v1/tracker/ad-networks/{id} | Update an ad network. |
| [**apiV1TrackerAdNetworksPost()**](TrackerNetworksApi.md#apiV1TrackerAdNetworksPost) | **POST** /api/v1/tracker/ad-networks | Create an ad network. |
| [**apiV1TrackerAdNetworksReportsGet()**](TrackerNetworksApi.md#apiV1TrackerAdNetworksReportsGet) | **GET** /api/v1/tracker/ad-networks/reports | Spend reports pulled from the ad networks. |
| [**apiV1TrackerAffiliateNetworkTemplatesGet()**](TrackerNetworksApi.md#apiV1TrackerAffiliateNetworkTemplatesGet) | **GET** /api/v1/tracker/affiliate-network-templates | List affiliate-network templates. |
| [**apiV1TrackerAffiliateNetworksGet()**](TrackerNetworksApi.md#apiV1TrackerAffiliateNetworksGet) | **GET** /api/v1/tracker/affiliate-networks | List affiliate networks. |
| [**apiV1TrackerAffiliateNetworksIdDelete()**](TrackerNetworksApi.md#apiV1TrackerAffiliateNetworksIdDelete) | **DELETE** /api/v1/tracker/affiliate-networks/{id} | Delete an affiliate network. |
| [**apiV1TrackerAffiliateNetworksIdPut()**](TrackerNetworksApi.md#apiV1TrackerAffiliateNetworksIdPut) | **PUT** /api/v1/tracker/affiliate-networks/{id} | Update an affiliate network. |
| [**apiV1TrackerAffiliateNetworksPost()**](TrackerNetworksApi.md#apiV1TrackerAffiliateNetworksPost) | **POST** /api/v1/tracker/affiliate-networks | Create an affiliate network. |


## `apiV1TrackerAdNetworksDeleteReportsPut()`

```php
apiV1TrackerAdNetworksDeleteReportsPut($api_v1_tracker_ad_networks_delete_reports_put_request): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response
```

Delete pulled spend reports.

Clears imported spend rows so a re-pull can replace them — the fix for a driver that imported a bad day.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerNetworksApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_tracker_ad_networks_delete_reports_put_request = new \GoAdServerApi\Model\ApiV1TrackerAdNetworksDeleteReportsPutRequest(); // \GoAdServerApi\Model\ApiV1TrackerAdNetworksDeleteReportsPutRequest

try {
    $result = $apiInstance->apiV1TrackerAdNetworksDeleteReportsPut($api_v1_tracker_ad_networks_delete_reports_put_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerNetworksApi->apiV1TrackerAdNetworksDeleteReportsPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_tracker_ad_networks_delete_reports_put_request** | [**\GoAdServerApi\Model\ApiV1TrackerAdNetworksDeleteReportsPutRequest**](../Model/ApiV1TrackerAdNetworksDeleteReportsPutRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response**](../Model/ApiV1PublisherPushCampaignsIdDelete200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerAdNetworksGet()`

```php
apiV1TrackerAdNetworksGet($status, $filter_id, $filter_name, $sort_by, $sort_dir, $page, $per_page): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

List ad networks.

The buying side — traffic sources spend is pulled from and campaigns are bound to.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerNetworksApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$status = 'status_example'; // string
$filter_id = 56; // int
$filter_name = 'filter_name_example'; // string | Name contains.
$sort_by = 'id'; // string
$sort_dir = 'desc'; // string
$page = 1; // int
$per_page = 20; // int

try {
    $result = $apiInstance->apiV1TrackerAdNetworksGet($status, $filter_id, $filter_name, $sort_by, $sort_dir, $page, $per_page);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerNetworksApi->apiV1TrackerAdNetworksGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **status** | **string**|  | [optional] |
| **filter_id** | **int**|  | [optional] |
| **filter_name** | **string**| Name contains. | [optional] |
| **sort_by** | **string**|  | [optional] [default to &#39;id&#39;] |
| **sort_dir** | **string**|  | [optional] [default to &#39;desc&#39;] |
| **page** | **int**|  | [optional] [default to 1] |
| **per_page** | **int**|  | [optional] [default to 20] |

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

## `apiV1TrackerAdNetworksIdCampaignsGet()`

```php
apiV1TrackerAdNetworksIdCampaignsGet($id): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Campaigns known on one ad network.

The bindable campaigns for tracking links on this network.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerNetworksApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Ad network ID.

try {
    $result = $apiInstance->apiV1TrackerAdNetworksIdCampaignsGet($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerNetworksApi->apiV1TrackerAdNetworksIdCampaignsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Ad network ID. | |

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

## `apiV1TrackerAdNetworksIdDelete()`

```php
apiV1TrackerAdNetworksIdDelete($id): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response
```

Delete an ad network.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerNetworksApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Ad network ID.

try {
    $result = $apiInstance->apiV1TrackerAdNetworksIdDelete($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerNetworksApi->apiV1TrackerAdNetworksIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Ad network ID. | |

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response**](../Model/ApiV1PublisherPushCampaignsIdDelete200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerAdNetworksIdGet()`

```php
apiV1TrackerAdNetworksIdGet($id): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Get one ad network.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerNetworksApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Ad network ID.

try {
    $result = $apiInstance->apiV1TrackerAdNetworksIdGet($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerNetworksApi->apiV1TrackerAdNetworksIdGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Ad network ID. | |

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

## `apiV1TrackerAdNetworksIdPut()`

```php
apiV1TrackerAdNetworksIdPut($id, $api_v1_tracker_ad_networks_id_put_request): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
```

Update an ad network.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerNetworksApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Ad network ID.
$api_v1_tracker_ad_networks_id_put_request = new \GoAdServerApi\Model\ApiV1TrackerAdNetworksIdPutRequest(); // \GoAdServerApi\Model\ApiV1TrackerAdNetworksIdPutRequest

try {
    $result = $apiInstance->apiV1TrackerAdNetworksIdPut($id, $api_v1_tracker_ad_networks_id_put_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerNetworksApi->apiV1TrackerAdNetworksIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Ad network ID. | |
| **api_v1_tracker_ad_networks_id_put_request** | [**\GoAdServerApi\Model\ApiV1TrackerAdNetworksIdPutRequest**](../Model/ApiV1TrackerAdNetworksIdPutRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response**](../Model/ApiV1PublisherPushCampaignsPost200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerAdNetworksPost()`

```php
apiV1TrackerAdNetworksPost($api_v1_tracker_ad_networks_post_request): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
```

Create an ad network.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerNetworksApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_tracker_ad_networks_post_request = new \GoAdServerApi\Model\ApiV1TrackerAdNetworksPostRequest(); // \GoAdServerApi\Model\ApiV1TrackerAdNetworksPostRequest

try {
    $result = $apiInstance->apiV1TrackerAdNetworksPost($api_v1_tracker_ad_networks_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerNetworksApi->apiV1TrackerAdNetworksPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_tracker_ad_networks_post_request** | [**\GoAdServerApi\Model\ApiV1TrackerAdNetworksPostRequest**](../Model/ApiV1TrackerAdNetworksPostRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response**](../Model/ApiV1PublisherPushCampaignsPost200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerAdNetworksReportsGet()`

```php
apiV1TrackerAdNetworksReportsGet($filter_id, $filter_ad_networkid, $filter_state, $sort_by, $sort_dir, $page, $per_page): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Spend reports pulled from the ad networks.

What the cost pull has imported per network, the raw material behind /cost-summary.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerNetworksApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$filter_id = 56; // int
$filter_ad_networkid = 56; // int | Reports for one ad network.
$filter_state = 56; // int
$sort_by = 'r.id'; // string
$sort_dir = 'desc'; // string
$page = 1; // int
$per_page = 20; // int

try {
    $result = $apiInstance->apiV1TrackerAdNetworksReportsGet($filter_id, $filter_ad_networkid, $filter_state, $sort_by, $sort_dir, $page, $per_page);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerNetworksApi->apiV1TrackerAdNetworksReportsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **filter_id** | **int**|  | [optional] |
| **filter_ad_networkid** | **int**| Reports for one ad network. | [optional] |
| **filter_state** | **int**|  | [optional] |
| **sort_by** | **string**|  | [optional] [default to &#39;r.id&#39;] |
| **sort_dir** | **string**|  | [optional] [default to &#39;desc&#39;] |
| **page** | **int**|  | [optional] [default to 1] |
| **per_page** | **int**|  | [optional] [default to 20] |

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

## `apiV1TrackerAffiliateNetworkTemplatesGet()`

```php
apiV1TrackerAffiliateNetworkTemplatesGet(): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

List affiliate-network templates.

Known networks with their postback token mapping preset.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerNetworksApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1TrackerAffiliateNetworkTemplatesGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerNetworksApi->apiV1TrackerAffiliateNetworkTemplatesGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1TrackerAffiliateNetworksGet()`

```php
apiV1TrackerAffiliateNetworksGet(): \GoAdServerApi\Model\ApiV1TrackerAffiliateNetworksGet200Response
```

List affiliate networks.

Where offers come from, with their postback token mapping.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerNetworksApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1TrackerAffiliateNetworksGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerNetworksApi->apiV1TrackerAffiliateNetworksGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerAffiliateNetworksGet200Response**](../Model/ApiV1TrackerAffiliateNetworksGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerAffiliateNetworksIdDelete()`

```php
apiV1TrackerAffiliateNetworksIdDelete($id): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response
```

Delete an affiliate network.

Hard delete — the table carries no soft-delete flag. Offers pointing at it keep their `affiliate_network_id` and simply resolve to no name.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerNetworksApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Affiliate network ID.

try {
    $result = $apiInstance->apiV1TrackerAffiliateNetworksIdDelete($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerNetworksApi->apiV1TrackerAffiliateNetworksIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Affiliate network ID. | |

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response**](../Model/ApiV1PublisherPushCampaignsIdDelete200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerAffiliateNetworksIdPut()`

```php
apiV1TrackerAffiliateNetworksIdPut($id, $api_v1_tracker_affiliate_networks_id_put_request): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
```

Update an affiliate network.

Partial update.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerNetworksApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Affiliate network ID.
$api_v1_tracker_affiliate_networks_id_put_request = new \GoAdServerApi\Model\ApiV1TrackerAffiliateNetworksIdPutRequest(); // \GoAdServerApi\Model\ApiV1TrackerAffiliateNetworksIdPutRequest

try {
    $result = $apiInstance->apiV1TrackerAffiliateNetworksIdPut($id, $api_v1_tracker_affiliate_networks_id_put_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerNetworksApi->apiV1TrackerAffiliateNetworksIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Affiliate network ID. | |
| **api_v1_tracker_affiliate_networks_id_put_request** | [**\GoAdServerApi\Model\ApiV1TrackerAffiliateNetworksIdPutRequest**](../Model/ApiV1TrackerAffiliateNetworksIdPutRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response**](../Model/ApiV1PublisherPushCampaignsPost200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerAffiliateNetworksPost()`

```php
apiV1TrackerAffiliateNetworksPost($api_v1_tracker_affiliate_networks_post_request): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
```

Create an affiliate network.

`template_slug` preloads a known network's token mapping — see /affiliate-network-templates.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerNetworksApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_tracker_affiliate_networks_post_request = new \GoAdServerApi\Model\ApiV1TrackerAffiliateNetworksPostRequest(); // \GoAdServerApi\Model\ApiV1TrackerAffiliateNetworksPostRequest

try {
    $result = $apiInstance->apiV1TrackerAffiliateNetworksPost($api_v1_tracker_affiliate_networks_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerNetworksApi->apiV1TrackerAffiliateNetworksPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_tracker_affiliate_networks_post_request** | [**\GoAdServerApi\Model\ApiV1TrackerAffiliateNetworksPostRequest**](../Model/ApiV1TrackerAffiliateNetworksPostRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response**](../Model/ApiV1PublisherPushCampaignsPost200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
