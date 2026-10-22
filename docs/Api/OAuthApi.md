# GoAdServerApi\OAuthApi

Our OAuth 2.0 provider for third-party apps — token, revocation (RFC 7009), introspection (RFC 7662) and server metadata (RFC 8414), plus the inbound webhook endpoint of the Incoming webhook connector. These authenticate the *client* (client secret, or PKCE for public clients), not an API key. Full walkthrough: /api/docs/integrations.

All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiPublicIntegrationsHooksTokenPost()**](OAuthApi.md#apiPublicIntegrationsHooksTokenPost) | **POST** /api/public/integrations/hooks/{token} | Incoming webhook — create a lead, contact or task from JSON. |
| [**apiPublicOauthIntrospectPost()**](OAuthApi.md#apiPublicOauthIntrospectPost) | **POST** /api/public/oauth/introspect | Introspect one of the calling app&#39;s tokens (RFC 7662). |
| [**apiPublicOauthMetadataGet()**](OAuthApi.md#apiPublicOauthMetadataGet) | **GET** /api/public/oauth/metadata | Authorization server metadata (RFC 8414) for this host. |
| [**apiPublicOauthRevokePost()**](OAuthApi.md#apiPublicOauthRevokePost) | **POST** /api/public/oauth/revoke | Revoke an access or refresh token (RFC 7009). |
| [**apiPublicOauthTokenPost()**](OAuthApi.md#apiPublicOauthTokenPost) | **POST** /api/public/oauth/token | Exchange an authorization code, or rotate a refresh token. |


## `apiPublicIntegrationsHooksTokenPost()`

```php
apiPublicIntegrationsHooksTokenPost($token, $request_body): \GoAdServerApi\Model\ApiPublicIntegrationsHooksTokenPost200Response
```

Incoming webhook — create a lead, contact or task from JSON.

The URL of an *Incoming webhook* connector (Admin → Integrations). The path token is the credential; a connector can additionally require `X-Integration-Signature: t=<unix>,v1=<hex HMAC-SHA256(secret, \"<t>.<body>\")>`. What the body becomes is the connector's field mapping. Max 64 KB.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');



$apiInstance = new GoAdServerApi\Api\OAuthApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$token = 'token_example'; // string | whin_… from the connector.
$request_body = NULL; // array<string,mixed>

try {
    $result = $apiInstance->apiPublicIntegrationsHooksTokenPost($token, $request_body);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OAuthApi->apiPublicIntegrationsHooksTokenPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **token** | **string**| whin_… from the connector. | |
| **request_body** | [**array<string,mixed>**](../Model/mixed.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiPublicIntegrationsHooksTokenPost200Response**](../Model/ApiPublicIntegrationsHooksTokenPost200Response.md)

### Authorization

No authorization required

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiPublicOauthIntrospectPost()`

```php
apiPublicOauthIntrospectPost($token, $client_id, $client_secret): \GoAdServerApi\Model\ApiPublicOauthIntrospectPost200Response
```

Introspect one of the calling app's tokens (RFC 7662).

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');



$apiInstance = new GoAdServerApi\Api\OAuthApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$token = 'token_example'; // string
$client_id = 'client_id_example'; // string
$client_secret = 'client_secret_example'; // string

try {
    $result = $apiInstance->apiPublicOauthIntrospectPost($token, $client_id, $client_secret);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OAuthApi->apiPublicOauthIntrospectPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **token** | **string**|  | |
| **client_id** | **string**|  | [optional] |
| **client_secret** | **string**|  | [optional] |

### Return type

[**\GoAdServerApi\Model\ApiPublicOauthIntrospectPost200Response**](../Model/ApiPublicOauthIntrospectPost200Response.md)

### Authorization

No authorization required

### HTTP request headers

- **Content-Type**: `application/x-www-form-urlencoded`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiPublicOauthMetadataGet()`

```php
apiPublicOauthMetadataGet(): array<string,mixed>
```

Authorization server metadata (RFC 8414) for this host.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');



$apiInstance = new GoAdServerApi\Api\OAuthApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);

try {
    $result = $apiInstance->apiPublicOauthMetadataGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OAuthApi->apiPublicOauthMetadataGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

**array<string,mixed>**

### Authorization

No authorization required

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiPublicOauthRevokePost()`

```php
apiPublicOauthRevokePost($token, $client_id, $client_secret)
```

Revoke an access or refresh token (RFC 7009).

Revoking a refresh token revokes every token of its authorization. Always 200 for an authenticated client.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');



$apiInstance = new GoAdServerApi\Api\OAuthApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$token = 'token_example'; // string
$client_id = 'client_id_example'; // string
$client_secret = 'client_secret_example'; // string

try {
    $apiInstance->apiPublicOauthRevokePost($token, $client_id, $client_secret);
} catch (Exception $e) {
    echo 'Exception when calling OAuthApi->apiPublicOauthRevokePost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **token** | **string**|  | |
| **client_id** | **string**|  | [optional] |
| **client_secret** | **string**|  | [optional] |

### Return type

void (empty response body)

### Authorization

No authorization required

### HTTP request headers

- **Content-Type**: `application/x-www-form-urlencoded`
- **Accept**: Not defined

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiPublicOauthTokenPost()`

```php
apiPublicOauthTokenPost($grant_type, $code, $redirect_uri, $code_verifier, $refresh_token, $scope, $client_id, $client_secret): \GoAdServerApi\Model\ApiPublicOauthTokenPost200Response
```

Exchange an authorization code, or rotate a refresh token.

`grant_type=authorization_code` needs `code`, `redirect_uri` (exactly as sent to `/oauth/authorize`) and `code_verifier`. `grant_type=refresh_token` needs `refresh_token` and may narrow with `scope`; the presented refresh token is spent and a new one returned — presenting a spent one again revokes every token of that authorization. Confidential apps authenticate with HTTP Basic (`client_id:client_secret`) or `client_id` + `client_secret` in the body; public apps send `client_id` only.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');



$apiInstance = new GoAdServerApi\Api\OAuthApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$grant_type = 'grant_type_example'; // string
$code = 'code_example'; // string | oac_… (authorization_code)
$redirect_uri = 'redirect_uri_example'; // string
$code_verifier = 'code_verifier_example'; // string | 43–128 chars
$refresh_token = 'refresh_token_example'; // string | ort_… (refresh_token)
$scope = 'scope_example'; // string | Space-separated; refresh may only narrow.
$client_id = 'client_id_example'; // string
$client_secret = 'client_secret_example'; // string

try {
    $result = $apiInstance->apiPublicOauthTokenPost($grant_type, $code, $redirect_uri, $code_verifier, $refresh_token, $scope, $client_id, $client_secret);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling OAuthApi->apiPublicOauthTokenPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **grant_type** | **string**|  | |
| **code** | **string**| oac_… (authorization_code) | [optional] |
| **redirect_uri** | **string**|  | [optional] |
| **code_verifier** | **string**| 43–128 chars | [optional] |
| **refresh_token** | **string**| ort_… (refresh_token) | [optional] |
| **scope** | **string**| Space-separated; refresh may only narrow. | [optional] |
| **client_id** | **string**|  | [optional] |
| **client_secret** | **string**|  | [optional] |

### Return type

[**\GoAdServerApi\Model\ApiPublicOauthTokenPost200Response**](../Model/ApiPublicOauthTokenPost200Response.md)

### Authorization

No authorization required

### HTTP request headers

- **Content-Type**: `application/x-www-form-urlencoded`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
