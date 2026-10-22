# GoAdServerApi\AIAssistantApi



All URIs are relative to https://up.go-adserver.com, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiV1McpPost()**](AIAssistantApi.md#apiV1McpPost) | **POST** /api/v1/mcp | MCP endpoint — connect an AI assistant to this account. |


## `apiV1McpPost()`

```php
apiV1McpPost($api_v1_mcp_post_request): \GoAdServerApi\Model\ApiV1McpPost200Response
```

MCP endpoint — connect an AI assistant to this account.

Model Context Protocol server (JSON-RPC 2.0 over HTTP). Point an MCP client at this URL with `Authorization: Bearer gas_live_…` and the assistant can use the account through a curated set of tools.  The key must be marked **\"Allow this key to connect an AI assistant\"** in MyAccount → API access; keys minted before that option existed are not agent-capable. The tools an assistant sees are filtered by the key's scopes, so a read-only key is never shown a write tool.  Methods: `initialize`, `ping`, `tools/list`, `tools/call`. One JSON-RPC request per POST — batching is not accepted. Tool calls are rate limited separately from the REST API (default 240/min per key); `initialize`, `ping` and `tools/list` are free.

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure Bearer (gas_live_<32 chars>) authorization: apiKey
$config = GoAdServerApi\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');


$apiInstance = new GoAdServerApi\Api\AIAssistantApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$api_v1_mcp_post_request = new \GoAdServerApi\Model\ApiV1McpPostRequest(); // \GoAdServerApi\Model\ApiV1McpPostRequest

try {
    $result = $apiInstance->apiV1McpPost($api_v1_mcp_post_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AIAssistantApi->apiV1McpPost: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **api_v1_mcp_post_request** | [**\GoAdServerApi\Model\ApiV1McpPostRequest**](../Model/ApiV1McpPostRequest.md)|  | |

### Return type

[**\GoAdServerApi\Model\ApiV1McpPost200Response**](../Model/ApiV1McpPost200Response.md)

### Authorization

[apiKey](../../README.md#apiKey)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
