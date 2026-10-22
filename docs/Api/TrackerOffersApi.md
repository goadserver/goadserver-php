# GoAdServerApi\TrackerOffersApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1TrackerOffersBulkPost()**](TrackerOffersApi.md#apiV1TrackerOffersBulkPost) | **POST** /api/v1/tracker/offers/bulk | Pause, activate or delete many offers. |
| [**apiV1TrackerOffersGet()**](TrackerOffersApi.md#apiV1TrackerOffersGet) | **GET** /api/v1/tracker/offers | List tracker offers. |
| [**apiV1TrackerOffersIdDelete()**](TrackerOffersApi.md#apiV1TrackerOffersIdDelete) | **DELETE** /api/v1/tracker/offers/{id} | Delete an offer. |
| [**apiV1TrackerOffersIdGet()**](TrackerOffersApi.md#apiV1TrackerOffersIdGet) | **GET** /api/v1/tracker/offers/{id} | Get one offer. |
| [**apiV1TrackerOffersIdPut()**](TrackerOffersApi.md#apiV1TrackerOffersIdPut) | **PUT** /api/v1/tracker/offers/{id} | Update an offer. |
| [**apiV1TrackerOffersIdUrlsPost()**](TrackerOffersApi.md#apiV1TrackerOffersIdUrlsPost) | **POST** /api/v1/tracker/offers/{id}/urls | Add a landing URL to an offer. |
| [**apiV1TrackerOffersIdUrlsUrlIdDelete()**](TrackerOffersApi.md#apiV1TrackerOffersIdUrlsUrlIdDelete) | **DELETE** /api/v1/tracker/offers/{id}/urls/{urlId} | Delete one landing URL. |
| [**apiV1TrackerOffersIdUrlsUrlIdPut()**](TrackerOffersApi.md#apiV1TrackerOffersIdUrlsUrlIdPut) | **PUT** /api/v1/tracker/offers/{id}/urls/{urlId} | Update one landing URL. |
| [**apiV1TrackerOffersPost()**](TrackerOffersApi.md#apiV1TrackerOffersPost) | **POST** /api/v1/tracker/offers | Create an offer. |


## `apiV1TrackerOffersBulkPost()`

```php
apiV1TrackerOffersBulkPost($api_v1_tracker_offers_bulk_post_request): \GoAdServerApi\Model\ApiV1PublisherPushSubscribersActionPost200Response
```

Pause, activate or delete many offers.

One action across a list of offer ids the caller owns. Ids that are not the caller's are skipped rather than failing the batch.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerOffersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_tracker_offers_bulk_post_request = new \GoAdServerApi\Model\ApiV1TrackerOffersBulkPostRequest(); // \GoAdServerApi\Model\ApiV1TrackerOffersBulkPostRequest

try {
    $result = $apiInstance->apiV1TrackerOffersBulkPost($api_v1_tracker_offers_bulk_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerOffersApi->apiV1TrackerOffersBulkPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_tracker_offers_bulk_post_request** | [**\GoAdServerApi\Model\ApiV1TrackerOffersBulkPostRequest**](../Model/ApiV1TrackerOffersBulkPostRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1PublisherPushSubscribersActionPost200Response**](../Model/ApiV1PublisherPushSubscribersActionPost200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerOffersGet()`

```php
apiV1TrackerOffersGet(): \GoAdServerApi\Model\ApiV1TrackerOffersGet200Response
```

List tracker offers.

Every offer the caller owns. An offer is a `roncampaigns` row with `adtypeid=21` — the same row the click pipeline, conversion settlement and stats already key off, so an offer created here is immediately servable through a tracking link.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerOffersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1TrackerOffersGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerOffersApi->apiV1TrackerOffersGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerOffersGet200Response**](../Model/ApiV1TrackerOffersGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerOffersIdDelete()`

```php
apiV1TrackerOffersIdDelete($id): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response
```

Delete an offer.

Soft delete (`isdeleted=1`). Clicks, conversions and stats history stay intact and keep reporting under the offer's name.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerOffersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Offer ID.

try {
    $result = $apiInstance->apiV1TrackerOffersIdDelete($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerOffersApi->apiV1TrackerOffersIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Offer ID. | |

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

## `apiV1TrackerOffersIdGet()`

```php
apiV1TrackerOffersIdGet($id): \GoAdServerApi\Model\ApiV1TrackerOffersIdGet200Response
```

Get one offer.

The full offer including its landing URLs and targeting.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerOffersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Offer ID.

try {
    $result = $apiInstance->apiV1TrackerOffersIdGet($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerOffersApi->apiV1TrackerOffersIdGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Offer ID. | |

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerOffersIdGet200Response**](../Model/ApiV1TrackerOffersIdGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerOffersIdPut()`

```php
apiV1TrackerOffersIdPut($id, $api_v1_tracker_offers_id_put_request): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
```

Update an offer.

Partial update — only the fields present in the body are written, so a caller that knows about one field cannot blank the rest.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerOffersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Offer ID.
$api_v1_tracker_offers_id_put_request = new \GoAdServerApi\Model\ApiV1TrackerOffersIdPutRequest(); // \GoAdServerApi\Model\ApiV1TrackerOffersIdPutRequest

try {
    $result = $apiInstance->apiV1TrackerOffersIdPut($id, $api_v1_tracker_offers_id_put_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerOffersApi->apiV1TrackerOffersIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Offer ID. | |
| **api_v1_tracker_offers_id_put_request** | [**\GoAdServerApi\Model\ApiV1TrackerOffersIdPutRequest**](../Model/ApiV1TrackerOffersIdPutRequest.md)|  | |

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

## `apiV1TrackerOffersIdUrlsPost()`

```php
apiV1TrackerOffersIdUrlsPost($id, $api_v1_tracker_offers_id_urls_post_request): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
```

Add a landing URL to an offer.

Adds one rotator URL without round-tripping the whole offer.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerOffersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Offer ID.
$api_v1_tracker_offers_id_urls_post_request = new \GoAdServerApi\Model\ApiV1TrackerOffersIdUrlsPostRequest(); // \GoAdServerApi\Model\ApiV1TrackerOffersIdUrlsPostRequest

try {
    $result = $apiInstance->apiV1TrackerOffersIdUrlsPost($id, $api_v1_tracker_offers_id_urls_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerOffersApi->apiV1TrackerOffersIdUrlsPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Offer ID. | |
| **api_v1_tracker_offers_id_urls_post_request** | [**\GoAdServerApi\Model\ApiV1TrackerOffersIdUrlsPostRequest**](../Model/ApiV1TrackerOffersIdUrlsPostRequest.md)|  | |

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

## `apiV1TrackerOffersIdUrlsUrlIdDelete()`

```php
apiV1TrackerOffersIdUrlsUrlIdDelete($id, $url_id): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsIdDelete200Response
```

Delete one landing URL.

Soft delete, so the URL's click and conversion history survives.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerOffersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Offer ID.
$url_id = 56; // int | URL row ID.

try {
    $result = $apiInstance->apiV1TrackerOffersIdUrlsUrlIdDelete($id, $url_id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerOffersApi->apiV1TrackerOffersIdUrlsUrlIdDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Offer ID. | |
| **url_id** | **int**| URL row ID. | |

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

## `apiV1TrackerOffersIdUrlsUrlIdPut()`

```php
apiV1TrackerOffersIdUrlsUrlIdPut($id, $url_id, $api_v1_tracker_offers_id_urls_url_id_put_request): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
```

Update one landing URL.

Writes only the columns the payload carries, so saving one row cannot clobber fields another editor changed.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerOffersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Offer ID.
$url_id = 56; // int | URL row ID.
$api_v1_tracker_offers_id_urls_url_id_put_request = new \GoAdServerApi\Model\ApiV1TrackerOffersIdUrlsUrlIdPutRequest(); // \GoAdServerApi\Model\ApiV1TrackerOffersIdUrlsUrlIdPutRequest

try {
    $result = $apiInstance->apiV1TrackerOffersIdUrlsUrlIdPut($id, $url_id, $api_v1_tracker_offers_id_urls_url_id_put_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerOffersApi->apiV1TrackerOffersIdUrlsUrlIdPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Offer ID. | |
| **url_id** | **int**| URL row ID. | |
| **api_v1_tracker_offers_id_urls_url_id_put_request** | [**\GoAdServerApi\Model\ApiV1TrackerOffersIdUrlsUrlIdPutRequest**](../Model/ApiV1TrackerOffersIdUrlsUrlIdPutRequest.md)|  | |

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

## `apiV1TrackerOffersPost()`

```php
apiV1TrackerOffersPost($api_v1_tracker_offers_post_request): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
```

Create an offer.

Creates the offer and its first landing URL in one call. Give either `destination_url` for a single URL or `urls` for a weighted rotator.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerOffersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_tracker_offers_post_request = new \GoAdServerApi\Model\ApiV1TrackerOffersPostRequest(); // \GoAdServerApi\Model\ApiV1TrackerOffersPostRequest

try {
    $result = $apiInstance->apiV1TrackerOffersPost($api_v1_tracker_offers_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerOffersApi->apiV1TrackerOffersPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_tracker_offers_post_request** | [**\GoAdServerApi\Model\ApiV1TrackerOffersPostRequest**](../Model/ApiV1TrackerOffersPostRequest.md)|  | |

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
