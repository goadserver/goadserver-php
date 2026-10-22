# GoAdServerApi\TrackerConversionsApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1TrackerConversionLoggingGet()**](TrackerConversionsApi.md#apiV1TrackerConversionLoggingGet) | **GET** /api/v1/tracker/conversion-logging | Get conversion-logging settings. |
| [**apiV1TrackerConversionLoggingPut()**](TrackerConversionsApi.md#apiV1TrackerConversionLoggingPut) | **PUT** /api/v1/tracker/conversion-logging | Change conversion-logging settings. |
| [**apiV1TrackerConversionTypesGet()**](TrackerConversionsApi.md#apiV1TrackerConversionTypesGet) | **GET** /api/v1/tracker/conversion-types | Get the conversion-type labels. |
| [**apiV1TrackerConversionTypesPut()**](TrackerConversionsApi.md#apiV1TrackerConversionTypesPut) | **PUT** /api/v1/tracker/conversion-types | Rename the conversion types. |
| [**apiV1TrackerConversionsClickidPostbacksGet()**](TrackerConversionsApi.md#apiV1TrackerConversionsClickidPostbacksGet) | **GET** /api/v1/tracker/conversions/{clickid}/postbacks | Outbound postbacks fired for one conversion. |
| [**apiV1TrackerConversionsGet()**](TrackerConversionsApi.md#apiV1TrackerConversionsGet) | **GET** /api/v1/tracker/conversions | List incoming conversions. |
| [**apiV1TrackerConversionsIdStatePut()**](TrackerConversionsApi.md#apiV1TrackerConversionsIdStatePut) | **PUT** /api/v1/tracker/conversions/{id}/state | Approve or reject one conversion. |
| [**apiV1TrackerConversionsRejectedGet()**](TrackerConversionsApi.md#apiV1TrackerConversionsRejectedGet) | **GET** /api/v1/tracker/conversions/rejected | List refused inbound postbacks. |


## `apiV1TrackerConversionLoggingGet()`

```php
apiV1TrackerConversionLoggingGet(): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Get conversion-logging settings.

Whether incoming conversions are logged, and whether they land pending review rather than settled.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerConversionsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1TrackerConversionLoggingGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerConversionsApi->apiV1TrackerConversionLoggingGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1TrackerConversionLoggingPut()`

```php
apiV1TrackerConversionLoggingPut($api_v1_tracker_conversion_logging_put_request): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Change conversion-logging settings.

Turning `pend_conversions` on routes new conversions to the pending queue instead of settling them on arrival.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerConversionsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_tracker_conversion_logging_put_request = new \GoAdServerApi\Model\ApiV1TrackerConversionLoggingPutRequest(); // \GoAdServerApi\Model\ApiV1TrackerConversionLoggingPutRequest

try {
    $result = $apiInstance->apiV1TrackerConversionLoggingPut($api_v1_tracker_conversion_logging_put_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerConversionsApi->apiV1TrackerConversionLoggingPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_tracker_conversion_logging_put_request** | [**\GoAdServerApi\Model\ApiV1TrackerConversionLoggingPutRequest**](../Model/ApiV1TrackerConversionLoggingPutRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response**](../Model/ApiV1TrackerTrackingLinksIdZoneCostsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerConversionTypesGet()`

```php
apiV1TrackerConversionTypesGet(): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Get the conversion-type labels.

The caller's A–Z map — which event each conversion-type letter means.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerConversionsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1TrackerConversionTypesGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerConversionsApi->apiV1TrackerConversionTypesGet: ', $e->getMessage(), PHP_EOL;
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

## `apiV1TrackerConversionTypesPut()`

```php
apiV1TrackerConversionTypesPut($api_v1_tracker_conversion_types_put_request): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Rename the conversion types.

Bulk replace of the letter → label map.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerConversionsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_tracker_conversion_types_put_request = new \GoAdServerApi\Model\ApiV1TrackerConversionTypesPutRequest(); // \GoAdServerApi\Model\ApiV1TrackerConversionTypesPutRequest

try {
    $result = $apiInstance->apiV1TrackerConversionTypesPut($api_v1_tracker_conversion_types_put_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerConversionsApi->apiV1TrackerConversionTypesPut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_tracker_conversion_types_put_request** | [**\GoAdServerApi\Model\ApiV1TrackerConversionTypesPutRequest**](../Model/ApiV1TrackerConversionTypesPutRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response**](../Model/ApiV1TrackerTrackingLinksIdZoneCostsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerConversionsClickidPostbacksGet()`

```php
apiV1TrackerConversionsClickidPostbacksGet($clickid, $conv_type): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

Outbound postbacks fired for one conversion.

Joined by clickid — what was forwarded upstream, when, and how it answered.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerConversionsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$clickid = 'clickid_example'; // string | The conversion's click id.
$conv_type = 'conv_type_example'; // string | Narrow to one conversion type letter.

try {
    $result = $apiInstance->apiV1TrackerConversionsClickidPostbacksGet($clickid, $conv_type);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerConversionsApi->apiV1TrackerConversionsClickidPostbacksGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **clickid** | **string**| The conversion&#39;s click id. | |
| **conv_type** | **string**| Narrow to one conversion type letter. | [optional] |

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

## `apiV1TrackerConversionsGet()`

```php
apiV1TrackerConversionsGet($start_date, $end_date, $state, $reason_id, $search, $sort_by, $sort_dir, $limit): \GoAdServerApi\Model\ApiV1TrackerConversionsGet200Response
```

List incoming conversions.

Conversions logged against the caller's offers, with the summary counts (clean / on hold / approved / rejected) the settlement view sums. Requires conversion logging to be on — see /conversion-logging.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerConversionsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$start_date = 'start_date_example'; // string | Start of the window, YYYY-MM-DD.
$end_date = 'end_date_example'; // string | End of the window, YYYY-MM-DD.
$state = 56; // int | Filter by settlement state.
$reason_id = 56; // int | Filter by hold/reject reason.
$search = 'search_example'; // string | Match against clickid, offer or link name.
$sort_by = 'sort_by_example'; // string | Column to sort on.
$sort_dir = 'sort_dir_example'; // string | asc or desc.
$limit = 56; // int | Maximum rows to return.

try {
    $result = $apiInstance->apiV1TrackerConversionsGet($start_date, $end_date, $state, $reason_id, $search, $sort_by, $sort_dir, $limit);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerConversionsApi->apiV1TrackerConversionsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **start_date** | **string**| Start of the window, YYYY-MM-DD. | [optional] |
| **end_date** | **string**| End of the window, YYYY-MM-DD. | [optional] |
| **state** | **int**| Filter by settlement state. | [optional] |
| **reason_id** | **int**| Filter by hold/reject reason. | [optional] |
| **search** | **string**| Match against clickid, offer or link name. | [optional] |
| **sort_by** | **string**| Column to sort on. | [optional] |
| **sort_dir** | **string**| asc or desc. | [optional] |
| **limit** | **int**| Maximum rows to return. | [optional] |

### Return type

[**\GoAdServerApi\Model\ApiV1TrackerConversionsGet200Response**](../Model/ApiV1TrackerConversionsGet200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiV1TrackerConversionsIdStatePut()`

```php
apiV1TrackerConversionsIdStatePut($id, $api_v1_tracker_conversions_id_state_put_request): \GoAdServerApi\Model\ApiV1PublisherPushCampaignsPost200Response
```

Approve or reject one conversion.

Settles a pending conversion. This is the one tracker write that moves money-bearing state, which is why it carries its own scope rather than riding on tracker.write.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerConversionsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 56; // int | Conversion ID.
$api_v1_tracker_conversions_id_state_put_request = new \GoAdServerApi\Model\ApiV1TrackerConversionsIdStatePutRequest(); // \GoAdServerApi\Model\ApiV1TrackerConversionsIdStatePutRequest

try {
    $result = $apiInstance->apiV1TrackerConversionsIdStatePut($id, $api_v1_tracker_conversions_id_state_put_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerConversionsApi->apiV1TrackerConversionsIdStatePut: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Conversion ID. | |
| **api_v1_tracker_conversions_id_state_put_request** | [**\GoAdServerApi\Model\ApiV1TrackerConversionsIdStatePutRequest**](../Model/ApiV1TrackerConversionsIdStatePutRequest.md)|  | |

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

## `apiV1TrackerConversionsRejectedGet()`

```php
apiV1TrackerConversionsRejectedGet(): \GoAdServerApi\Model\ApiV1TrackerTrackingLinksIdZoneCostsGet200Response
```

List refused inbound postbacks.

The other half of the inbound stream: postbacks that never became a conversion, with the reason each was refused. The first place to look when a partner insists they fired one.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\TrackerConversionsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->apiV1TrackerConversionsRejectedGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling TrackerConversionsApi->apiV1TrackerConversionsRejectedGet: ', $e->getMessage(), PHP_EOL;
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
