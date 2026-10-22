<?php
/**
 * FundsApi
 * PHP version 8.1
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */

/**
 * GoAdServer Public API
 *
 * Public REST API for advertisers and publishers on the GoAdServer platform.  **Authentication.** All endpoints (except `/api/public/_*`) require an API key passed as `Authorization: Bearer gas_live_…`. Create and manage keys in your account settings — each key has its own scopes, optional resource pinning, optional IP allowlist, and per-key rate limit.  **Apps (OAuth 2.0).** A tool other people connect uses an app token instead: authorization code + PKCE at `/oauth/authorize`, tokens from `/api/public/oauth/token`, passed as `Authorization: Bearer oat_…` on the same endpoints with the same scopes. See the [Build an integration](/api/docs/integrations) guide.  **System rules apply over API rules.** The public API never lets a key bypass restrictions the same user faces in the panel:  - **Source names** (`stats_source_names` right). When the user lacks   this right, advertiser stats responses return `{id, name}` with   `name` equal to the id — no human label leaks through. - **SubID encoding** (`modules.global.subid_encoding`). When enabled   for the system, the user only ever sees encoded SubIDs in the panel.   The API mirrors this both ways: `subid_excl`/`subid_incl` filter   endpoints accept the encoded form on write (decoded server-side   before storage) and emit the encoded form on read. A plain SubID   sent on write is stored as sent — a value is decoded only when it is   a genuine encoded token. - **Module gating.** Endpoints whose module is disabled for the   tenant don't appear in the spec or routes.txt at all.  **Rate limits.** Every response to an API key or app token carries the caller's budget for the current one-minute window:  | Header | Meaning | |---|---| | `X-RateLimit-Limit` | Requests per minute for this key (its `rate_limit_rpm`, default 60; an app's per-installation limit for app tokens). | | `X-RateLimit-Remaining` | Calls left in the current window. | | `X-RateLimit-Reset` | Unix seconds at which the window resets. | | `Retry-After` | On `429 rate_limited` only: seconds until a retry can succeed. |  Windows are fixed and start with the first call, so `Reset` is exact. The MCP endpoint (`POST /api/v1/mcp`) is charged per `tools/call` against its own bucket (default 240/min), and its response headers report that bucket; an exhausted MCP bucket answers the tool call with an error result rather than an HTTP 429, and the HTTP response then carries `Retry-After`.  **Idempotent writes.** Send `Idempotency-Key: <1–255 printable ASCII characters>` (a UUID is ideal) on a `POST` or `PATCH` to make a retry safe. For 24 hours per key (per app installation for app tokens):  - the first request runs and its response — status, body, `Content-Type`,   `Location`, `ETag` — is stored; - the same key with the same method, path, query and body replays that   response without running anything, adding `Idempotent-Replayed: true`; - the same key with a different request is `422 idempotency_key_reused`; - the same key while the first request is still running is   `409 idempotency_key_in_progress` (with `Retry-After: 1`).  `5xx` results are not stored — the key is released and a retry runs again. Bodies over 32 MB cannot carry the header (`413`). An invalid key is `400 invalid_idempotency_key`.  **Concurrency control (ETag / If-Match).** Every JSON `GET` response (up to 1 MB) carries a weak `ETag`, a hash of the body. Send it back:  - `If-None-Match: <etag>` on a `GET` — `304 Not Modified` when unchanged; - `If-Match: <etag>` on a `PATCH`, `PUT` or `DELETE` — the server reads the   resource's current representation (the `GET` of the same path, or of the   nearest parent path that has a `GET`: `PATCH …/campaigns/ron/7/active` is   checked against `GET …/campaigns/ron/7`) and answers   `412 precondition_failed` when it no longer matches, with the current tag in   `ETag` and `current_etag`. `If-Match: *` only requires the resource to   exist. A resource with no readable representation is also `412`.  The check is optimistic, not a lock: it closes the read-modify-write window two agents (or an agent and a person in the panel) would otherwise race in. Fields that change on their own (live spend counters) change the tag too — re-read and retry. A write response carries no new ETag; `GET` again.  **Strict fields.** Unknown body fields are a client error, not something to ignore: a request that sends a field the endpoint does not accept gets `422 unknown_fields` naming it. This is the default for keys created from 2026-10-12 (the key's `strict_fields` setting, shown on the key screen); older keys keep ignoring unknown fields (except on the few endpoints that have always refused them — the budget and on/off toggles). Override per request with `?strict=1` (reject) or `?strict=0` (ignore, everywhere). App tokens default to ignoring unknown fields; `?strict=1` opts in.  **Errors.** Every non-2xx response has the same shape:  ```json { \"error\": \"validation_error\", \"message\": \"Name is required.\", \"errors\": { \"name\": [\"Name is required.\"] } } ```  `error` is a stable machine-readable code — branch on it, never on `message`. `errors` (field → messages) appears on field-level validation failures; some codes add their own members (`scope`, `accepted_scopes`, `unknown_fields`, `current_etag`). Codes:  | Status | Codes | |---|---| | 400 | `bad_request`, `invalid_idempotency_key`, `invalid_body` | | 401 | `unauthenticated`, `invalid_api_key`, `revoked_api_key`, `expired_api_key`, `invalid_token`, `revoked_token`, `expired_token`, `user_not_found`, `account_deleted`, `account_disabled`, `admin_key_on_user_api`, `user_key_on_admin_api` | | 403 | `forbidden`, `missing_scope`, `forbidden_resource`, `ip_not_allowed`, `api_disabled`, `systemid_mismatch`, `endpoint_not_api_accessible`, `oauth_token_wrong_surface`, `mcp_not_enabled_for_app` | | 404 | `not_found` (also a disabled module or missing right — see ModuleDisabled) | | 405 | `method_not_allowed` — the `Allow` header (and the message) lists the methods the path does accept | | 409 | `conflict`, `idempotency_key_in_progress`, and resource-specific conflicts (e.g. `email_exists`) | | 412 | `precondition_failed` | | 413 | `payload_too_large` | | 422 | `validation_error`, `unknown_fields`, `idempotency_key_reused` | | 429 | `rate_limited` | | 5xx | `server_error`, `service_unavailable`, `bad_gateway`, `gateway_timeout` |  Endpoint-specific codes are listed on the endpoint. A response that would otherwise lack a code gets the generic one for its status from this table.  **Client SDKs.** Each SDK is generated from this spec and published as a public GitHub repository under the `goadserver` organization. The same tarball is also embedded in the API server and downloadable from `/api/public/sdk/<lang>.tgz` — useful when GitHub is unreachable.  > **Per-network host.** The default `https://up.go-adserver.com` > baked into the published SDKs is a reference host. Every white-label > tenant runs on its own panel domain — override the host on first use > (`setHost()` / `Configuration.host` / `basePath` etc.). Or, fetch the > spec from your own tenant (`/api/public/openapi.yaml` returns it with > *your* hostname as the default) and regenerate the SDK locally — the > regenerated client has your URL baked in.  - **PHP** — `github.com/goadserver/goadserver-php`   (namespace `GoAdServer\\Api`, composer name `goadserver/sdk`).    ```bash   composer config repositories.goadserver vcs https://github.com/goadserver/goadserver-php   composer require goadserver/sdk:dev-main   ```    ```php   $cfg = GoAdServer\\Api\\Configuration::getDefaultConfiguration()           ->setHost('https://up.go-adserver.com')           ->setAccessToken('gas_live_…');   $api = new GoAdServer\\Api\\Api\\AccountAPIApi(null, $cfg);   $me  = $api->apiV1AccountGet();   ```  - **Go** — `github.com/goadserver/goadserver-go`.    ```bash   go get github.com/goadserver/goadserver-go@latest   ```    ```go   import gas \"github.com/goadserver/goadserver-go\"    cfg := gas.NewConfiguration()   cfg.Servers = gas.ServerConfigurations{{URL: \"https://up.go-adserver.com\"}}   cli := gas.NewAPIClient(cfg)   ctx := context.WithValue(context.Background(),       gas.ContextAccessToken, \"gas_live_…\")   me, _, err := cli.AccountAPI.ApiV1AccountGet(ctx).Execute()   ```  - **Python** — `github.com/goadserver/goadserver-python` (package   `goadserver`). Pulls `pydantic` + `urllib3` automatically.    ```bash   pip install git+https://github.com/goadserver/goadserver-python.git   ```    ```python   from goadserver import ApiClient, Configuration   from goadserver.api import AccountApi, CampaignsApi    cfg = Configuration(host=\"https://up.go-adserver.com\")   cfg.access_token = \"gas_live_…\"   client = ApiClient(cfg)    me = AccountApi(client).api_v1_account_get()   camps = CampaignsApi(client).api_v1_campaigns_get(per_page=10)   ```  - **JavaScript / TypeScript** — `github.com/goadserver/goadserver-js`   (npm name `goadserver-sdk`, generator `typescript-fetch`).    ```bash   npm install github:goadserver/goadserver-js   ```    ```ts   import { Configuration, AccountApi, CampaignsApi }     from \"goadserver-sdk\";    const cfg = new Configuration({     basePath: \"https://up.go-adserver.com\",     accessToken: \"gas_live_…\",   });    const me   = await new AccountApi(cfg).apiV1AccountGet();   const list = await new CampaignsApi(cfg).apiV1CampaignsGet({ perPage: 10 });   ```    The CJS build is broadly compatible (Node + bundlers); the ESM   build is missing trailing `.js` extensions in its imports, a known   `typescript-fetch` quirk — most consumers use CJS.  **Offline / firewalled install.** If the customer's network can't reach GitHub, every SDK's source is also served as a tarball from the API host:  ```bash curl -O https://up.go-adserver.com/api/public/sdk/python.tgz tar xzf python.tgz && pip install ./python ```  Round-trip smoke tests for all four SDKs live at `clients/_smoke/` in the platform repo.
 *
 * The version of the OpenAPI document: 1.0.0
 * Generated by: https://openapi-generator.tech
 * Generator version: 7.21.0
 */

/**
 * NOTE: This class is auto generated by OpenAPI Generator (https://openapi-generator.tech).
 * https://openapi-generator.tech
 * Do not edit the class manually.
 */

namespace GoAdServerApi\Api;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GoAdServerApi\ApiException;
use GoAdServerApi\Configuration;
use GoAdServerApi\FormDataProcessor;
use GoAdServerApi\HeaderSelector;
use GoAdServerApi\ObjectSerializer;

/**
 * FundsApi Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class FundsApi
{
    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @var HeaderSelector
     */
    protected $headerSelector;

    /**
     * @var int Host index
     */
    protected $hostIndex;

    /** @var string[] $contentTypes **/
    public const contentTypes = [
        'apiV1FundsBalanceSettingsPut' => [
            'application/json',
        ],
        'apiV1FundsDepositTypeFidGet' => [
            'application/json',
        ],
        'apiV1FundsDepositTypePost' => [
            'application/json',
        ],
        'apiV1FundsDepositsIdDelete' => [
            'application/json',
        ],
        'apiV1FundsDepositsIdProofGet' => [
            'application/json',
        ],
        'apiV1FundsDepositsIdProofPost' => [
            'multipart/form-data',
        ],
        'apiV1FundsDepositsIdTxnHashPost' => [
            'application/json',
        ],
        'apiV1FundsGet' => [
            'application/json',
        ],
        'apiV1FundsPayTypesGet' => [
            'application/json',
        ],
        'apiV1FundsTransactionsGet' => [
            'application/json',
        ],
        'apiV1FundsTransferPost' => [
            'application/json',
        ],
    ];

    /**
     * @param ClientInterface $client
     * @param Configuration   $config
     * @param HeaderSelector  $selector
     * @param int             $hostIndex (Optional) host index to select the list of hosts if defined in the OpenAPI spec
     */
    public function __construct(
        ?ClientInterface $client = null,
        ?Configuration $config = null,
        ?HeaderSelector $selector = null,
        int $hostIndex = 0
    ) {
        $this->client = $client ?: new Client();
        $this->config = $config ?: Configuration::getDefaultConfiguration();
        $this->headerSelector = $selector ?: new HeaderSelector();
        $this->hostIndex = $hostIndex;
    }

    /**
     * Set the host index
     *
     * @param int $hostIndex Host index (required)
     */
    public function setHostIndex($hostIndex): void
    {
        $this->hostIndex = $hostIndex;
    }

    /**
     * Get the host index
     *
     * @return int Host index
     */
    public function getHostIndex()
    {
        return $this->hostIndex;
    }

    /**
     * @return Configuration
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Operation apiV1FundsBalanceSettingsPut
     *
     * Set the low-balance warning.
     *
     * @param  \GoAdServerApi\Model\ApiV1FundsBalanceSettingsPutRequest $api_v1_funds_balance_settings_put_request api_v1_funds_balance_settings_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsBalanceSettingsPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1FundsBalanceSettingsPut($api_v1_funds_balance_settings_put_request, string $contentType = self::contentTypes['apiV1FundsBalanceSettingsPut'][0])
    {
        $this->apiV1FundsBalanceSettingsPutWithHttpInfo($api_v1_funds_balance_settings_put_request, $contentType);
    }

    /**
     * Operation apiV1FundsBalanceSettingsPutWithHttpInfo
     *
     * Set the low-balance warning.
     *
     * @param  \GoAdServerApi\Model\ApiV1FundsBalanceSettingsPutRequest $api_v1_funds_balance_settings_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsBalanceSettingsPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1FundsBalanceSettingsPutWithHttpInfo($api_v1_funds_balance_settings_put_request, string $contentType = self::contentTypes['apiV1FundsBalanceSettingsPut'][0])
    {
        $request = $this->apiV1FundsBalanceSettingsPutRequest($api_v1_funds_balance_settings_put_request, $contentType);

        try {
            $options = $this->createHttpClientOption();
            try {
                $response = $this->client->send($request, $options);
            } catch (RequestException $e) {
                throw new ApiException(
                    "[{$e->getCode()}] {$e->getMessage()}",
                    (int) $e->getCode(),
                    $e->getResponse() ? $e->getResponse()->getHeaders() : null,
                    $e->getResponse() ? (string) $e->getResponse()->getBody() : null
                );
            } catch (ConnectException $e) {
                throw new ApiException(
                    "[{$e->getCode()}] {$e->getMessage()}",
                    (int) $e->getCode(),
                    null,
                    null
                );
            }

            $statusCode = $response->getStatusCode();


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 403:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1FundsBalanceSettingsPutAsync
     *
     * Set the low-balance warning.
     *
     * @param  \GoAdServerApi\Model\ApiV1FundsBalanceSettingsPutRequest $api_v1_funds_balance_settings_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsBalanceSettingsPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FundsBalanceSettingsPutAsync($api_v1_funds_balance_settings_put_request, string $contentType = self::contentTypes['apiV1FundsBalanceSettingsPut'][0])
    {
        return $this->apiV1FundsBalanceSettingsPutAsyncWithHttpInfo($api_v1_funds_balance_settings_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1FundsBalanceSettingsPutAsyncWithHttpInfo
     *
     * Set the low-balance warning.
     *
     * @param  \GoAdServerApi\Model\ApiV1FundsBalanceSettingsPutRequest $api_v1_funds_balance_settings_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsBalanceSettingsPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FundsBalanceSettingsPutAsyncWithHttpInfo($api_v1_funds_balance_settings_put_request, string $contentType = self::contentTypes['apiV1FundsBalanceSettingsPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1FundsBalanceSettingsPutRequest($api_v1_funds_balance_settings_put_request, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
                },
                function ($exception) {
                    $response = $exception->getResponse();
                    $statusCode = $response->getStatusCode();
                    throw new ApiException(
                        sprintf(
                            '[%d] Error connecting to the API (%s)',
                            $statusCode,
                            $exception->getRequest()->getUri()
                        ),
                        $statusCode,
                        $response->getHeaders(),
                        (string) $response->getBody()
                    );
                }
            );
    }

    /**
     * Create request for operation 'apiV1FundsBalanceSettingsPut'
     *
     * @param  \GoAdServerApi\Model\ApiV1FundsBalanceSettingsPutRequest $api_v1_funds_balance_settings_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsBalanceSettingsPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1FundsBalanceSettingsPutRequest($api_v1_funds_balance_settings_put_request, string $contentType = self::contentTypes['apiV1FundsBalanceSettingsPut'][0])
    {

        // verify the required parameter 'api_v1_funds_balance_settings_put_request' is set
        if ($api_v1_funds_balance_settings_put_request === null || (is_array($api_v1_funds_balance_settings_put_request) && count($api_v1_funds_balance_settings_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_funds_balance_settings_put_request when calling apiV1FundsBalanceSettingsPut'
            );
        }


        $resourcePath = '/api/v1/funds/balance-settings';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;





        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_funds_balance_settings_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_funds_balance_settings_put_request));
            } else {
                $httpBody = $api_v1_funds_balance_settings_put_request;
            }
        } elseif (count($formParams) > 0) {
            if ($multipart) {
                $multipartContents = [];
                foreach ($formParams as $formParamName => $formParamValue) {
                    $formParamValueItems = is_array($formParamValue) ? $formParamValue : [$formParamValue];
                    foreach ($formParamValueItems as $formParamValueItem) {
                        $multipartContents[] = [
                            'name' => $formParamName,
                            'contents' => $formParamValueItem
                        ];
                    }
                }
                // for HTTP post (form)
                $httpBody = new MultipartStream($multipartContents);

            } elseif (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the form parameters
                $httpBody = \GuzzleHttp\Utils::jsonEncode($formParams);
            } else {
                // for HTTP post (form)
                $httpBody = ObjectSerializer::buildQuery($formParams);
            }
        }

        // this endpoint requires Bearer (gas_live_<32 chars>) authentication (access token)
        if (!empty($this->config->getAccessToken())) {
            $headers['Authorization'] = 'Bearer ' . $this->config->getAccessToken();
        }

        $defaultHeaders = [];
        if ($this->config->getUserAgent()) {
            $defaultHeaders['User-Agent'] = $this->config->getUserAgent();
        }

        $headers = array_merge(
            $defaultHeaders,
            $headerParams,
            $headers
        );

        $operationHost = $this->config->getHost();
        $query = ObjectSerializer::buildQuery($queryParams);
        return new Request(
            'PUT',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1FundsDepositTypeFidGet
     *
     * Checkout data for an open deposit — or its paid state.
     *
     * @param  string $type The pay type the deposit was opened with. (required)
     * @param  int $fid Deposit id from POST /api/v1/funds/deposit/{type}. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsDepositTypeFidGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1FundsDepositTypeFidGet200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1FundsDepositTypeFidGet($type, $fid, string $contentType = self::contentTypes['apiV1FundsDepositTypeFidGet'][0])
    {
        list($response) = $this->apiV1FundsDepositTypeFidGetWithHttpInfo($type, $fid, $contentType);
        return $response;
    }

    /**
     * Operation apiV1FundsDepositTypeFidGetWithHttpInfo
     *
     * Checkout data for an open deposit — or its paid state.
     *
     * @param  string $type The pay type the deposit was opened with. (required)
     * @param  int $fid Deposit id from POST /api/v1/funds/deposit/{type}. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsDepositTypeFidGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1FundsDepositTypeFidGet200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1FundsDepositTypeFidGetWithHttpInfo($type, $fid, string $contentType = self::contentTypes['apiV1FundsDepositTypeFidGet'][0])
    {
        $request = $this->apiV1FundsDepositTypeFidGetRequest($type, $fid, $contentType);

        try {
            $options = $this->createHttpClientOption();
            try {
                $response = $this->client->send($request, $options);
            } catch (RequestException $e) {
                throw new ApiException(
                    "[{$e->getCode()}] {$e->getMessage()}",
                    (int) $e->getCode(),
                    $e->getResponse() ? $e->getResponse()->getHeaders() : null,
                    $e->getResponse() ? (string) $e->getResponse()->getBody() : null
                );
            } catch (ConnectException $e) {
                throw new ApiException(
                    "[{$e->getCode()}] {$e->getMessage()}",
                    (int) $e->getCode(),
                    null,
                    null
                );
            }

            $statusCode = $response->getStatusCode();


            switch($statusCode) {
                case 200:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\ApiV1FundsDepositTypeFidGet200Response',
                        $request,
                        $response,
                    );
                case 403:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
            }

            

            if ($statusCode < 200 || $statusCode > 299) {
                throw new ApiException(
                    sprintf(
                        '[%d] Error connecting to the API (%s)',
                        $statusCode,
                        (string) $request->getUri()
                    ),
                    $statusCode,
                    $response->getHeaders(),
                    (string) $response->getBody()
                );
            }

            return $this->handleResponseWithDataType(
                '\GoAdServerApi\Model\ApiV1FundsDepositTypeFidGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1FundsDepositTypeFidGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 403:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1FundsDepositTypeFidGetAsync
     *
     * Checkout data for an open deposit — or its paid state.
     *
     * @param  string $type The pay type the deposit was opened with. (required)
     * @param  int $fid Deposit id from POST /api/v1/funds/deposit/{type}. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsDepositTypeFidGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FundsDepositTypeFidGetAsync($type, $fid, string $contentType = self::contentTypes['apiV1FundsDepositTypeFidGet'][0])
    {
        return $this->apiV1FundsDepositTypeFidGetAsyncWithHttpInfo($type, $fid, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1FundsDepositTypeFidGetAsyncWithHttpInfo
     *
     * Checkout data for an open deposit — or its paid state.
     *
     * @param  string $type The pay type the deposit was opened with. (required)
     * @param  int $fid Deposit id from POST /api/v1/funds/deposit/{type}. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsDepositTypeFidGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FundsDepositTypeFidGetAsyncWithHttpInfo($type, $fid, string $contentType = self::contentTypes['apiV1FundsDepositTypeFidGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1FundsDepositTypeFidGet200Response';
        $request = $this->apiV1FundsDepositTypeFidGetRequest($type, $fid, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    if ($returnType === '\SplFileObject') {
                        $content = $response->getBody(); //stream goes to serializer
                    } else {
                        $content = (string) $response->getBody();
                        if ($returnType !== 'string') {
                            $content = json_decode($content);
                        }
                    }

                    return [
                        ObjectSerializer::deserialize($content, $returnType, []),
                        $response->getStatusCode(),
                        $response->getHeaders()
                    ];
                },
                function ($exception) {
                    $response = $exception->getResponse();
                    $statusCode = $response->getStatusCode();
                    throw new ApiException(
                        sprintf(
                            '[%d] Error connecting to the API (%s)',
                            $statusCode,
                            $exception->getRequest()->getUri()
                        ),
                        $statusCode,
                        $response->getHeaders(),
                        (string) $response->getBody()
                    );
                }
            );
    }

    /**
     * Create request for operation 'apiV1FundsDepositTypeFidGet'
     *
     * @param  string $type The pay type the deposit was opened with. (required)
     * @param  int $fid Deposit id from POST /api/v1/funds/deposit/{type}. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsDepositTypeFidGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1FundsDepositTypeFidGetRequest($type, $fid, string $contentType = self::contentTypes['apiV1FundsDepositTypeFidGet'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1FundsDepositTypeFidGet'
            );
        }

        // verify the required parameter 'fid' is set
        if ($fid === null || (is_array($fid) && count($fid) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $fid when calling apiV1FundsDepositTypeFidGet'
            );
        }


        $resourcePath = '/api/v1/funds/deposit/{type}/{fid}';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }
        // path params
        if ($fid !== null) {
            $resourcePath = str_replace(
                '{' . 'fid' . '}',
                ObjectSerializer::toPathValue($fid),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (count($formParams) > 0) {
            if ($multipart) {
                $multipartContents = [];
                foreach ($formParams as $formParamName => $formParamValue) {
                    $formParamValueItems = is_array($formParamValue) ? $formParamValue : [$formParamValue];
                    foreach ($formParamValueItems as $formParamValueItem) {
                        $multipartContents[] = [
                            'name' => $formParamName,
                            'contents' => $formParamValueItem
                        ];
                    }
                }
                // for HTTP post (form)
                $httpBody = new MultipartStream($multipartContents);

            } elseif (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the form parameters
                $httpBody = \GuzzleHttp\Utils::jsonEncode($formParams);
            } else {
                // for HTTP post (form)
                $httpBody = ObjectSerializer::buildQuery($formParams);
            }
        }

        // this endpoint requires Bearer (gas_live_<32 chars>) authentication (access token)
        if (!empty($this->config->getAccessToken())) {
            $headers['Authorization'] = 'Bearer ' . $this->config->getAccessToken();
        }

        $defaultHeaders = [];
        if ($this->config->getUserAgent()) {
            $defaultHeaders['User-Agent'] = $this->config->getUserAgent();
        }

        $headers = array_merge(
            $defaultHeaders,
            $headerParams,
            $headers
        );

        $operationHost = $this->config->getHost();
        $query = ObjectSerializer::buildQuery($queryParams);
        return new Request(
            'GET',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1FundsDepositTypePost
     *
     * Open a deposit (or redeem a bonus / coupon code).
     *
     * @param  string $type A &#x60;key&#x60; from GET /api/v1/funds/pay-types, or bonus / coupon / advertiser. (required)
     * @param  \GoAdServerApi\Model\ApiV1FundsDepositTypePostRequest $api_v1_funds_deposit_type_post_request api_v1_funds_deposit_type_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsDepositTypePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1FundsDepositTypePost200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1FundsDepositTypePost($type, $api_v1_funds_deposit_type_post_request, string $contentType = self::contentTypes['apiV1FundsDepositTypePost'][0])
    {
        list($response) = $this->apiV1FundsDepositTypePostWithHttpInfo($type, $api_v1_funds_deposit_type_post_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1FundsDepositTypePostWithHttpInfo
     *
     * Open a deposit (or redeem a bonus / coupon code).
     *
     * @param  string $type A &#x60;key&#x60; from GET /api/v1/funds/pay-types, or bonus / coupon / advertiser. (required)
     * @param  \GoAdServerApi\Model\ApiV1FundsDepositTypePostRequest $api_v1_funds_deposit_type_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsDepositTypePost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1FundsDepositTypePost200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1FundsDepositTypePostWithHttpInfo($type, $api_v1_funds_deposit_type_post_request, string $contentType = self::contentTypes['apiV1FundsDepositTypePost'][0])
    {
        $request = $this->apiV1FundsDepositTypePostRequest($type, $api_v1_funds_deposit_type_post_request, $contentType);

        try {
            $options = $this->createHttpClientOption();
            try {
                $response = $this->client->send($request, $options);
            } catch (RequestException $e) {
                throw new ApiException(
                    "[{$e->getCode()}] {$e->getMessage()}",
                    (int) $e->getCode(),
                    $e->getResponse() ? $e->getResponse()->getHeaders() : null,
                    $e->getResponse() ? (string) $e->getResponse()->getBody() : null
                );
            } catch (ConnectException $e) {
                throw new ApiException(
                    "[{$e->getCode()}] {$e->getMessage()}",
                    (int) $e->getCode(),
                    null,
                    null
                );
            }

            $statusCode = $response->getStatusCode();


            switch($statusCode) {
                case 200:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\ApiV1FundsDepositTypePost200Response',
                        $request,
                        $response,
                    );
                case 403:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
            }

            

            if ($statusCode < 200 || $statusCode > 299) {
                throw new ApiException(
                    sprintf(
                        '[%d] Error connecting to the API (%s)',
                        $statusCode,
                        (string) $request->getUri()
                    ),
                    $statusCode,
                    $response->getHeaders(),
                    (string) $response->getBody()
                );
            }

            return $this->handleResponseWithDataType(
                '\GoAdServerApi\Model\ApiV1FundsDepositTypePost200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1FundsDepositTypePost200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 403:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1FundsDepositTypePostAsync
     *
     * Open a deposit (or redeem a bonus / coupon code).
     *
     * @param  string $type A &#x60;key&#x60; from GET /api/v1/funds/pay-types, or bonus / coupon / advertiser. (required)
     * @param  \GoAdServerApi\Model\ApiV1FundsDepositTypePostRequest $api_v1_funds_deposit_type_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsDepositTypePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FundsDepositTypePostAsync($type, $api_v1_funds_deposit_type_post_request, string $contentType = self::contentTypes['apiV1FundsDepositTypePost'][0])
    {
        return $this->apiV1FundsDepositTypePostAsyncWithHttpInfo($type, $api_v1_funds_deposit_type_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1FundsDepositTypePostAsyncWithHttpInfo
     *
     * Open a deposit (or redeem a bonus / coupon code).
     *
     * @param  string $type A &#x60;key&#x60; from GET /api/v1/funds/pay-types, or bonus / coupon / advertiser. (required)
     * @param  \GoAdServerApi\Model\ApiV1FundsDepositTypePostRequest $api_v1_funds_deposit_type_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsDepositTypePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FundsDepositTypePostAsyncWithHttpInfo($type, $api_v1_funds_deposit_type_post_request, string $contentType = self::contentTypes['apiV1FundsDepositTypePost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1FundsDepositTypePost200Response';
        $request = $this->apiV1FundsDepositTypePostRequest($type, $api_v1_funds_deposit_type_post_request, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    if ($returnType === '\SplFileObject') {
                        $content = $response->getBody(); //stream goes to serializer
                    } else {
                        $content = (string) $response->getBody();
                        if ($returnType !== 'string') {
                            $content = json_decode($content);
                        }
                    }

                    return [
                        ObjectSerializer::deserialize($content, $returnType, []),
                        $response->getStatusCode(),
                        $response->getHeaders()
                    ];
                },
                function ($exception) {
                    $response = $exception->getResponse();
                    $statusCode = $response->getStatusCode();
                    throw new ApiException(
                        sprintf(
                            '[%d] Error connecting to the API (%s)',
                            $statusCode,
                            $exception->getRequest()->getUri()
                        ),
                        $statusCode,
                        $response->getHeaders(),
                        (string) $response->getBody()
                    );
                }
            );
    }

    /**
     * Create request for operation 'apiV1FundsDepositTypePost'
     *
     * @param  string $type A &#x60;key&#x60; from GET /api/v1/funds/pay-types, or bonus / coupon / advertiser. (required)
     * @param  \GoAdServerApi\Model\ApiV1FundsDepositTypePostRequest $api_v1_funds_deposit_type_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsDepositTypePost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1FundsDepositTypePostRequest($type, $api_v1_funds_deposit_type_post_request, string $contentType = self::contentTypes['apiV1FundsDepositTypePost'][0])
    {

        // verify the required parameter 'type' is set
        if ($type === null || (is_array($type) && count($type) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $type when calling apiV1FundsDepositTypePost'
            );
        }

        // verify the required parameter 'api_v1_funds_deposit_type_post_request' is set
        if ($api_v1_funds_deposit_type_post_request === null || (is_array($api_v1_funds_deposit_type_post_request) && count($api_v1_funds_deposit_type_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_funds_deposit_type_post_request when calling apiV1FundsDepositTypePost'
            );
        }


        $resourcePath = '/api/v1/funds/deposit/{type}';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($type !== null) {
            $resourcePath = str_replace(
                '{' . 'type' . '}',
                ObjectSerializer::toPathValue($type),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_funds_deposit_type_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_funds_deposit_type_post_request));
            } else {
                $httpBody = $api_v1_funds_deposit_type_post_request;
            }
        } elseif (count($formParams) > 0) {
            if ($multipart) {
                $multipartContents = [];
                foreach ($formParams as $formParamName => $formParamValue) {
                    $formParamValueItems = is_array($formParamValue) ? $formParamValue : [$formParamValue];
                    foreach ($formParamValueItems as $formParamValueItem) {
                        $multipartContents[] = [
                            'name' => $formParamName,
                            'contents' => $formParamValueItem
                        ];
                    }
                }
                // for HTTP post (form)
                $httpBody = new MultipartStream($multipartContents);

            } elseif (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the form parameters
                $httpBody = \GuzzleHttp\Utils::jsonEncode($formParams);
            } else {
                // for HTTP post (form)
                $httpBody = ObjectSerializer::buildQuery($formParams);
            }
        }

        // this endpoint requires Bearer (gas_live_<32 chars>) authentication (access token)
        if (!empty($this->config->getAccessToken())) {
            $headers['Authorization'] = 'Bearer ' . $this->config->getAccessToken();
        }

        $defaultHeaders = [];
        if ($this->config->getUserAgent()) {
            $defaultHeaders['User-Agent'] = $this->config->getUserAgent();
        }

        $headers = array_merge(
            $defaultHeaders,
            $headerParams,
            $headers
        );

        $operationHost = $this->config->getHost();
        $query = ObjectSerializer::buildQuery($queryParams);
        return new Request(
            'POST',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1FundsDepositsIdDelete
     *
     * Cancel an unpaid deposit.
     *
     * @param  int $id Deposit id (&#x60;fid&#x60;). (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsDepositsIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1RetargetingPixelsIdDelete200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1FundsDepositsIdDelete($id, string $contentType = self::contentTypes['apiV1FundsDepositsIdDelete'][0])
    {
        list($response) = $this->apiV1FundsDepositsIdDeleteWithHttpInfo($id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1FundsDepositsIdDeleteWithHttpInfo
     *
     * Cancel an unpaid deposit.
     *
     * @param  int $id Deposit id (&#x60;fid&#x60;). (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsDepositsIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1RetargetingPixelsIdDelete200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1FundsDepositsIdDeleteWithHttpInfo($id, string $contentType = self::contentTypes['apiV1FundsDepositsIdDelete'][0])
    {
        $request = $this->apiV1FundsDepositsIdDeleteRequest($id, $contentType);

        try {
            $options = $this->createHttpClientOption();
            try {
                $response = $this->client->send($request, $options);
            } catch (RequestException $e) {
                throw new ApiException(
                    "[{$e->getCode()}] {$e->getMessage()}",
                    (int) $e->getCode(),
                    $e->getResponse() ? $e->getResponse()->getHeaders() : null,
                    $e->getResponse() ? (string) $e->getResponse()->getBody() : null
                );
            } catch (ConnectException $e) {
                throw new ApiException(
                    "[{$e->getCode()}] {$e->getMessage()}",
                    (int) $e->getCode(),
                    null,
                    null
                );
            }

            $statusCode = $response->getStatusCode();


            switch($statusCode) {
                case 200:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\ApiV1RetargetingPixelsIdDelete200Response',
                        $request,
                        $response,
                    );
                case 403:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
            }

            

            if ($statusCode < 200 || $statusCode > 299) {
                throw new ApiException(
                    sprintf(
                        '[%d] Error connecting to the API (%s)',
                        $statusCode,
                        (string) $request->getUri()
                    ),
                    $statusCode,
                    $response->getHeaders(),
                    (string) $response->getBody()
                );
            }

            return $this->handleResponseWithDataType(
                '\GoAdServerApi\Model\ApiV1RetargetingPixelsIdDelete200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1RetargetingPixelsIdDelete200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 403:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1FundsDepositsIdDeleteAsync
     *
     * Cancel an unpaid deposit.
     *
     * @param  int $id Deposit id (&#x60;fid&#x60;). (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsDepositsIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FundsDepositsIdDeleteAsync($id, string $contentType = self::contentTypes['apiV1FundsDepositsIdDelete'][0])
    {
        return $this->apiV1FundsDepositsIdDeleteAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1FundsDepositsIdDeleteAsyncWithHttpInfo
     *
     * Cancel an unpaid deposit.
     *
     * @param  int $id Deposit id (&#x60;fid&#x60;). (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsDepositsIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FundsDepositsIdDeleteAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1FundsDepositsIdDelete'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1RetargetingPixelsIdDelete200Response';
        $request = $this->apiV1FundsDepositsIdDeleteRequest($id, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    if ($returnType === '\SplFileObject') {
                        $content = $response->getBody(); //stream goes to serializer
                    } else {
                        $content = (string) $response->getBody();
                        if ($returnType !== 'string') {
                            $content = json_decode($content);
                        }
                    }

                    return [
                        ObjectSerializer::deserialize($content, $returnType, []),
                        $response->getStatusCode(),
                        $response->getHeaders()
                    ];
                },
                function ($exception) {
                    $response = $exception->getResponse();
                    $statusCode = $response->getStatusCode();
                    throw new ApiException(
                        sprintf(
                            '[%d] Error connecting to the API (%s)',
                            $statusCode,
                            $exception->getRequest()->getUri()
                        ),
                        $statusCode,
                        $response->getHeaders(),
                        (string) $response->getBody()
                    );
                }
            );
    }

    /**
     * Create request for operation 'apiV1FundsDepositsIdDelete'
     *
     * @param  int $id Deposit id (&#x60;fid&#x60;). (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsDepositsIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1FundsDepositsIdDeleteRequest($id, string $contentType = self::contentTypes['apiV1FundsDepositsIdDelete'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1FundsDepositsIdDelete'
            );
        }


        $resourcePath = '/api/v1/funds/deposits/{id}';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($id !== null) {
            $resourcePath = str_replace(
                '{' . 'id' . '}',
                ObjectSerializer::toPathValue($id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (count($formParams) > 0) {
            if ($multipart) {
                $multipartContents = [];
                foreach ($formParams as $formParamName => $formParamValue) {
                    $formParamValueItems = is_array($formParamValue) ? $formParamValue : [$formParamValue];
                    foreach ($formParamValueItems as $formParamValueItem) {
                        $multipartContents[] = [
                            'name' => $formParamName,
                            'contents' => $formParamValueItem
                        ];
                    }
                }
                // for HTTP post (form)
                $httpBody = new MultipartStream($multipartContents);

            } elseif (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the form parameters
                $httpBody = \GuzzleHttp\Utils::jsonEncode($formParams);
            } else {
                // for HTTP post (form)
                $httpBody = ObjectSerializer::buildQuery($formParams);
            }
        }

        // this endpoint requires Bearer (gas_live_<32 chars>) authentication (access token)
        if (!empty($this->config->getAccessToken())) {
            $headers['Authorization'] = 'Bearer ' . $this->config->getAccessToken();
        }

        $defaultHeaders = [];
        if ($this->config->getUserAgent()) {
            $defaultHeaders['User-Agent'] = $this->config->getUserAgent();
        }

        $headers = array_merge(
            $defaultHeaders,
            $headerParams,
            $headers
        );

        $operationHost = $this->config->getHost();
        $query = ObjectSerializer::buildQuery($queryParams);
        return new Request(
            'DELETE',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1FundsDepositsIdProofGet
     *
     * Download the proof of payment you uploaded on a deposit.
     *
     * @param  int $id Deposit id (&#x60;fid&#x60;). (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsDepositsIdProofGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \SplFileObject|\GoAdServerApi\Model\Error
     */
    public function apiV1FundsDepositsIdProofGet($id, string $contentType = self::contentTypes['apiV1FundsDepositsIdProofGet'][0])
    {
        list($response) = $this->apiV1FundsDepositsIdProofGetWithHttpInfo($id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1FundsDepositsIdProofGetWithHttpInfo
     *
     * Download the proof of payment you uploaded on a deposit.
     *
     * @param  int $id Deposit id (&#x60;fid&#x60;). (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsDepositsIdProofGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \SplFileObject|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1FundsDepositsIdProofGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1FundsDepositsIdProofGet'][0])
    {
        $request = $this->apiV1FundsDepositsIdProofGetRequest($id, $contentType);

        try {
            $options = $this->createHttpClientOption();
            try {
                $response = $this->client->send($request, $options);
            } catch (RequestException $e) {
                throw new ApiException(
                    "[{$e->getCode()}] {$e->getMessage()}",
                    (int) $e->getCode(),
                    $e->getResponse() ? $e->getResponse()->getHeaders() : null,
                    $e->getResponse() ? (string) $e->getResponse()->getBody() : null
                );
            } catch (ConnectException $e) {
                throw new ApiException(
                    "[{$e->getCode()}] {$e->getMessage()}",
                    (int) $e->getCode(),
                    null,
                    null
                );
            }

            $statusCode = $response->getStatusCode();


            switch($statusCode) {
                case 200:
                    return $this->handleResponseWithDataType(
                        '\SplFileObject',
                        $request,
                        $response,
                    );
                case 403:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
            }

            

            if ($statusCode < 200 || $statusCode > 299) {
                throw new ApiException(
                    sprintf(
                        '[%d] Error connecting to the API (%s)',
                        $statusCode,
                        (string) $request->getUri()
                    ),
                    $statusCode,
                    $response->getHeaders(),
                    (string) $response->getBody()
                );
            }

            return $this->handleResponseWithDataType(
                '\SplFileObject',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\SplFileObject',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 403:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1FundsDepositsIdProofGetAsync
     *
     * Download the proof of payment you uploaded on a deposit.
     *
     * @param  int $id Deposit id (&#x60;fid&#x60;). (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsDepositsIdProofGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FundsDepositsIdProofGetAsync($id, string $contentType = self::contentTypes['apiV1FundsDepositsIdProofGet'][0])
    {
        return $this->apiV1FundsDepositsIdProofGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1FundsDepositsIdProofGetAsyncWithHttpInfo
     *
     * Download the proof of payment you uploaded on a deposit.
     *
     * @param  int $id Deposit id (&#x60;fid&#x60;). (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsDepositsIdProofGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FundsDepositsIdProofGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1FundsDepositsIdProofGet'][0])
    {
        $returnType = '\SplFileObject';
        $request = $this->apiV1FundsDepositsIdProofGetRequest($id, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    if ($returnType === '\SplFileObject') {
                        $content = $response->getBody(); //stream goes to serializer
                    } else {
                        $content = (string) $response->getBody();
                        if ($returnType !== 'string') {
                            $content = json_decode($content);
                        }
                    }

                    return [
                        ObjectSerializer::deserialize($content, $returnType, []),
                        $response->getStatusCode(),
                        $response->getHeaders()
                    ];
                },
                function ($exception) {
                    $response = $exception->getResponse();
                    $statusCode = $response->getStatusCode();
                    throw new ApiException(
                        sprintf(
                            '[%d] Error connecting to the API (%s)',
                            $statusCode,
                            $exception->getRequest()->getUri()
                        ),
                        $statusCode,
                        $response->getHeaders(),
                        (string) $response->getBody()
                    );
                }
            );
    }

    /**
     * Create request for operation 'apiV1FundsDepositsIdProofGet'
     *
     * @param  int $id Deposit id (&#x60;fid&#x60;). (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsDepositsIdProofGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1FundsDepositsIdProofGetRequest($id, string $contentType = self::contentTypes['apiV1FundsDepositsIdProofGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1FundsDepositsIdProofGet'
            );
        }


        $resourcePath = '/api/v1/funds/deposits/{id}/proof';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($id !== null) {
            $resourcePath = str_replace(
                '{' . 'id' . '}',
                ObjectSerializer::toPathValue($id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/pdf', 'image/jpeg', 'image/png', 'application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (count($formParams) > 0) {
            if ($multipart) {
                $multipartContents = [];
                foreach ($formParams as $formParamName => $formParamValue) {
                    $formParamValueItems = is_array($formParamValue) ? $formParamValue : [$formParamValue];
                    foreach ($formParamValueItems as $formParamValueItem) {
                        $multipartContents[] = [
                            'name' => $formParamName,
                            'contents' => $formParamValueItem
                        ];
                    }
                }
                // for HTTP post (form)
                $httpBody = new MultipartStream($multipartContents);

            } elseif (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the form parameters
                $httpBody = \GuzzleHttp\Utils::jsonEncode($formParams);
            } else {
                // for HTTP post (form)
                $httpBody = ObjectSerializer::buildQuery($formParams);
            }
        }

        // this endpoint requires Bearer (gas_live_<32 chars>) authentication (access token)
        if (!empty($this->config->getAccessToken())) {
            $headers['Authorization'] = 'Bearer ' . $this->config->getAccessToken();
        }

        $defaultHeaders = [];
        if ($this->config->getUserAgent()) {
            $defaultHeaders['User-Agent'] = $this->config->getUserAgent();
        }

        $headers = array_merge(
            $defaultHeaders,
            $headerParams,
            $headers
        );

        $operationHost = $this->config->getHost();
        $query = ObjectSerializer::buildQuery($queryParams);
        return new Request(
            'GET',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1FundsDepositsIdProofPost
     *
     * Upload the proof of payment of an open bank or Wise deposit.
     *
     * @param  int $id Deposit id (&#x60;fid&#x60;). (required)
     * @param  \SplFileObject $file PDF, JPG or PNG, max 10 MB. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsDepositsIdProofPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1FundsDepositsIdProofPost200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1FundsDepositsIdProofPost($id, $file, string $contentType = self::contentTypes['apiV1FundsDepositsIdProofPost'][0])
    {
        list($response) = $this->apiV1FundsDepositsIdProofPostWithHttpInfo($id, $file, $contentType);
        return $response;
    }

    /**
     * Operation apiV1FundsDepositsIdProofPostWithHttpInfo
     *
     * Upload the proof of payment of an open bank or Wise deposit.
     *
     * @param  int $id Deposit id (&#x60;fid&#x60;). (required)
     * @param  \SplFileObject $file PDF, JPG or PNG, max 10 MB. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsDepositsIdProofPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1FundsDepositsIdProofPost200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1FundsDepositsIdProofPostWithHttpInfo($id, $file, string $contentType = self::contentTypes['apiV1FundsDepositsIdProofPost'][0])
    {
        $request = $this->apiV1FundsDepositsIdProofPostRequest($id, $file, $contentType);

        try {
            $options = $this->createHttpClientOption();
            try {
                $response = $this->client->send($request, $options);
            } catch (RequestException $e) {
                throw new ApiException(
                    "[{$e->getCode()}] {$e->getMessage()}",
                    (int) $e->getCode(),
                    $e->getResponse() ? $e->getResponse()->getHeaders() : null,
                    $e->getResponse() ? (string) $e->getResponse()->getBody() : null
                );
            } catch (ConnectException $e) {
                throw new ApiException(
                    "[{$e->getCode()}] {$e->getMessage()}",
                    (int) $e->getCode(),
                    null,
                    null
                );
            }

            $statusCode = $response->getStatusCode();


            switch($statusCode) {
                case 200:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\ApiV1FundsDepositsIdProofPost200Response',
                        $request,
                        $response,
                    );
                case 403:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
            }

            

            if ($statusCode < 200 || $statusCode > 299) {
                throw new ApiException(
                    sprintf(
                        '[%d] Error connecting to the API (%s)',
                        $statusCode,
                        (string) $request->getUri()
                    ),
                    $statusCode,
                    $response->getHeaders(),
                    (string) $response->getBody()
                );
            }

            return $this->handleResponseWithDataType(
                '\GoAdServerApi\Model\ApiV1FundsDepositsIdProofPost200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1FundsDepositsIdProofPost200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 403:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1FundsDepositsIdProofPostAsync
     *
     * Upload the proof of payment of an open bank or Wise deposit.
     *
     * @param  int $id Deposit id (&#x60;fid&#x60;). (required)
     * @param  \SplFileObject $file PDF, JPG or PNG, max 10 MB. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsDepositsIdProofPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FundsDepositsIdProofPostAsync($id, $file, string $contentType = self::contentTypes['apiV1FundsDepositsIdProofPost'][0])
    {
        return $this->apiV1FundsDepositsIdProofPostAsyncWithHttpInfo($id, $file, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1FundsDepositsIdProofPostAsyncWithHttpInfo
     *
     * Upload the proof of payment of an open bank or Wise deposit.
     *
     * @param  int $id Deposit id (&#x60;fid&#x60;). (required)
     * @param  \SplFileObject $file PDF, JPG or PNG, max 10 MB. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsDepositsIdProofPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FundsDepositsIdProofPostAsyncWithHttpInfo($id, $file, string $contentType = self::contentTypes['apiV1FundsDepositsIdProofPost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1FundsDepositsIdProofPost200Response';
        $request = $this->apiV1FundsDepositsIdProofPostRequest($id, $file, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    if ($returnType === '\SplFileObject') {
                        $content = $response->getBody(); //stream goes to serializer
                    } else {
                        $content = (string) $response->getBody();
                        if ($returnType !== 'string') {
                            $content = json_decode($content);
                        }
                    }

                    return [
                        ObjectSerializer::deserialize($content, $returnType, []),
                        $response->getStatusCode(),
                        $response->getHeaders()
                    ];
                },
                function ($exception) {
                    $response = $exception->getResponse();
                    $statusCode = $response->getStatusCode();
                    throw new ApiException(
                        sprintf(
                            '[%d] Error connecting to the API (%s)',
                            $statusCode,
                            $exception->getRequest()->getUri()
                        ),
                        $statusCode,
                        $response->getHeaders(),
                        (string) $response->getBody()
                    );
                }
            );
    }

    /**
     * Create request for operation 'apiV1FundsDepositsIdProofPost'
     *
     * @param  int $id Deposit id (&#x60;fid&#x60;). (required)
     * @param  \SplFileObject $file PDF, JPG or PNG, max 10 MB. (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsDepositsIdProofPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1FundsDepositsIdProofPostRequest($id, $file, string $contentType = self::contentTypes['apiV1FundsDepositsIdProofPost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1FundsDepositsIdProofPost'
            );
        }

        // verify the required parameter 'file' is set
        if ($file === null || (is_array($file) && count($file) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $file when calling apiV1FundsDepositsIdProofPost'
            );
        }


        $resourcePath = '/api/v1/funds/deposits/{id}/proof';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($id !== null) {
            $resourcePath = str_replace(
                '{' . 'id' . '}',
                ObjectSerializer::toPathValue($id),
                $resourcePath
            );
        }

        // form params
        $formDataProcessor = new FormDataProcessor();

        $formData = $formDataProcessor->prepare([
            'file' => $file,
        ]);

        $formParams = $formDataProcessor->flatten($formData);
        $multipart = $formDataProcessor->has_file;

        $multipart = true;
        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (count($formParams) > 0) {
            if ($multipart) {
                $multipartContents = [];
                foreach ($formParams as $formParamName => $formParamValue) {
                    $formParamValueItems = is_array($formParamValue) ? $formParamValue : [$formParamValue];
                    foreach ($formParamValueItems as $formParamValueItem) {
                        $multipartContents[] = [
                            'name' => $formParamName,
                            'contents' => $formParamValueItem
                        ];
                    }
                }
                // for HTTP post (form)
                $httpBody = new MultipartStream($multipartContents);

            } elseif (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the form parameters
                $httpBody = \GuzzleHttp\Utils::jsonEncode($formParams);
            } else {
                // for HTTP post (form)
                $httpBody = ObjectSerializer::buildQuery($formParams);
            }
        }

        // this endpoint requires Bearer (gas_live_<32 chars>) authentication (access token)
        if (!empty($this->config->getAccessToken())) {
            $headers['Authorization'] = 'Bearer ' . $this->config->getAccessToken();
        }

        $defaultHeaders = [];
        if ($this->config->getUserAgent()) {
            $defaultHeaders['User-Agent'] = $this->config->getUserAgent();
        }

        $headers = array_merge(
            $defaultHeaders,
            $headerParams,
            $headers
        );

        $operationHost = $this->config->getHost();
        $query = ObjectSerializer::buildQuery($queryParams);
        return new Request(
            'POST',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1FundsDepositsIdTxnHashPost
     *
     * Attach the transaction hash of a manual crypto deposit, or the transaction ID of a Payoneer deposit.
     *
     * @param  int $id Deposit id (&#x60;fid&#x60;). (required)
     * @param  \GoAdServerApi\Model\ApiV1FundsDepositsIdTxnHashPostRequest $api_v1_funds_deposits_id_txn_hash_post_request api_v1_funds_deposits_id_txn_hash_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsDepositsIdTxnHashPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1FundsDepositsIdTxnHashPost200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1FundsDepositsIdTxnHashPost($id, $api_v1_funds_deposits_id_txn_hash_post_request, string $contentType = self::contentTypes['apiV1FundsDepositsIdTxnHashPost'][0])
    {
        list($response) = $this->apiV1FundsDepositsIdTxnHashPostWithHttpInfo($id, $api_v1_funds_deposits_id_txn_hash_post_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1FundsDepositsIdTxnHashPostWithHttpInfo
     *
     * Attach the transaction hash of a manual crypto deposit, or the transaction ID of a Payoneer deposit.
     *
     * @param  int $id Deposit id (&#x60;fid&#x60;). (required)
     * @param  \GoAdServerApi\Model\ApiV1FundsDepositsIdTxnHashPostRequest $api_v1_funds_deposits_id_txn_hash_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsDepositsIdTxnHashPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1FundsDepositsIdTxnHashPost200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1FundsDepositsIdTxnHashPostWithHttpInfo($id, $api_v1_funds_deposits_id_txn_hash_post_request, string $contentType = self::contentTypes['apiV1FundsDepositsIdTxnHashPost'][0])
    {
        $request = $this->apiV1FundsDepositsIdTxnHashPostRequest($id, $api_v1_funds_deposits_id_txn_hash_post_request, $contentType);

        try {
            $options = $this->createHttpClientOption();
            try {
                $response = $this->client->send($request, $options);
            } catch (RequestException $e) {
                throw new ApiException(
                    "[{$e->getCode()}] {$e->getMessage()}",
                    (int) $e->getCode(),
                    $e->getResponse() ? $e->getResponse()->getHeaders() : null,
                    $e->getResponse() ? (string) $e->getResponse()->getBody() : null
                );
            } catch (ConnectException $e) {
                throw new ApiException(
                    "[{$e->getCode()}] {$e->getMessage()}",
                    (int) $e->getCode(),
                    null,
                    null
                );
            }

            $statusCode = $response->getStatusCode();


            switch($statusCode) {
                case 200:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\ApiV1FundsDepositsIdTxnHashPost200Response',
                        $request,
                        $response,
                    );
                case 403:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
            }

            

            if ($statusCode < 200 || $statusCode > 299) {
                throw new ApiException(
                    sprintf(
                        '[%d] Error connecting to the API (%s)',
                        $statusCode,
                        (string) $request->getUri()
                    ),
                    $statusCode,
                    $response->getHeaders(),
                    (string) $response->getBody()
                );
            }

            return $this->handleResponseWithDataType(
                '\GoAdServerApi\Model\ApiV1FundsDepositsIdTxnHashPost200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1FundsDepositsIdTxnHashPost200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 403:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1FundsDepositsIdTxnHashPostAsync
     *
     * Attach the transaction hash of a manual crypto deposit, or the transaction ID of a Payoneer deposit.
     *
     * @param  int $id Deposit id (&#x60;fid&#x60;). (required)
     * @param  \GoAdServerApi\Model\ApiV1FundsDepositsIdTxnHashPostRequest $api_v1_funds_deposits_id_txn_hash_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsDepositsIdTxnHashPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FundsDepositsIdTxnHashPostAsync($id, $api_v1_funds_deposits_id_txn_hash_post_request, string $contentType = self::contentTypes['apiV1FundsDepositsIdTxnHashPost'][0])
    {
        return $this->apiV1FundsDepositsIdTxnHashPostAsyncWithHttpInfo($id, $api_v1_funds_deposits_id_txn_hash_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1FundsDepositsIdTxnHashPostAsyncWithHttpInfo
     *
     * Attach the transaction hash of a manual crypto deposit, or the transaction ID of a Payoneer deposit.
     *
     * @param  int $id Deposit id (&#x60;fid&#x60;). (required)
     * @param  \GoAdServerApi\Model\ApiV1FundsDepositsIdTxnHashPostRequest $api_v1_funds_deposits_id_txn_hash_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsDepositsIdTxnHashPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FundsDepositsIdTxnHashPostAsyncWithHttpInfo($id, $api_v1_funds_deposits_id_txn_hash_post_request, string $contentType = self::contentTypes['apiV1FundsDepositsIdTxnHashPost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1FundsDepositsIdTxnHashPost200Response';
        $request = $this->apiV1FundsDepositsIdTxnHashPostRequest($id, $api_v1_funds_deposits_id_txn_hash_post_request, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    if ($returnType === '\SplFileObject') {
                        $content = $response->getBody(); //stream goes to serializer
                    } else {
                        $content = (string) $response->getBody();
                        if ($returnType !== 'string') {
                            $content = json_decode($content);
                        }
                    }

                    return [
                        ObjectSerializer::deserialize($content, $returnType, []),
                        $response->getStatusCode(),
                        $response->getHeaders()
                    ];
                },
                function ($exception) {
                    $response = $exception->getResponse();
                    $statusCode = $response->getStatusCode();
                    throw new ApiException(
                        sprintf(
                            '[%d] Error connecting to the API (%s)',
                            $statusCode,
                            $exception->getRequest()->getUri()
                        ),
                        $statusCode,
                        $response->getHeaders(),
                        (string) $response->getBody()
                    );
                }
            );
    }

    /**
     * Create request for operation 'apiV1FundsDepositsIdTxnHashPost'
     *
     * @param  int $id Deposit id (&#x60;fid&#x60;). (required)
     * @param  \GoAdServerApi\Model\ApiV1FundsDepositsIdTxnHashPostRequest $api_v1_funds_deposits_id_txn_hash_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsDepositsIdTxnHashPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1FundsDepositsIdTxnHashPostRequest($id, $api_v1_funds_deposits_id_txn_hash_post_request, string $contentType = self::contentTypes['apiV1FundsDepositsIdTxnHashPost'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1FundsDepositsIdTxnHashPost'
            );
        }

        // verify the required parameter 'api_v1_funds_deposits_id_txn_hash_post_request' is set
        if ($api_v1_funds_deposits_id_txn_hash_post_request === null || (is_array($api_v1_funds_deposits_id_txn_hash_post_request) && count($api_v1_funds_deposits_id_txn_hash_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_funds_deposits_id_txn_hash_post_request when calling apiV1FundsDepositsIdTxnHashPost'
            );
        }


        $resourcePath = '/api/v1/funds/deposits/{id}/txn-hash';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;



        // path params
        if ($id !== null) {
            $resourcePath = str_replace(
                '{' . 'id' . '}',
                ObjectSerializer::toPathValue($id),
                $resourcePath
            );
        }


        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_funds_deposits_id_txn_hash_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_funds_deposits_id_txn_hash_post_request));
            } else {
                $httpBody = $api_v1_funds_deposits_id_txn_hash_post_request;
            }
        } elseif (count($formParams) > 0) {
            if ($multipart) {
                $multipartContents = [];
                foreach ($formParams as $formParamName => $formParamValue) {
                    $formParamValueItems = is_array($formParamValue) ? $formParamValue : [$formParamValue];
                    foreach ($formParamValueItems as $formParamValueItem) {
                        $multipartContents[] = [
                            'name' => $formParamName,
                            'contents' => $formParamValueItem
                        ];
                    }
                }
                // for HTTP post (form)
                $httpBody = new MultipartStream($multipartContents);

            } elseif (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the form parameters
                $httpBody = \GuzzleHttp\Utils::jsonEncode($formParams);
            } else {
                // for HTTP post (form)
                $httpBody = ObjectSerializer::buildQuery($formParams);
            }
        }

        // this endpoint requires Bearer (gas_live_<32 chars>) authentication (access token)
        if (!empty($this->config->getAccessToken())) {
            $headers['Authorization'] = 'Bearer ' . $this->config->getAccessToken();
        }

        $defaultHeaders = [];
        if ($this->config->getUserAgent()) {
            $defaultHeaders['User-Agent'] = $this->config->getUserAgent();
        }

        $headers = array_merge(
            $defaultHeaders,
            $headerParams,
            $headers
        );

        $operationHost = $this->config->getHost();
        $query = ObjectSerializer::buildQuery($queryParams);
        return new Request(
            'POST',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1FundsGet
     *
     * Current advertiser balance + pending deposits.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1FundsGet200Response
     */
    public function apiV1FundsGet(string $contentType = self::contentTypes['apiV1FundsGet'][0])
    {
        list($response) = $this->apiV1FundsGetWithHttpInfo($contentType);
        return $response;
    }

    /**
     * Operation apiV1FundsGetWithHttpInfo
     *
     * Current advertiser balance + pending deposits.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1FundsGet200Response, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1FundsGetWithHttpInfo(string $contentType = self::contentTypes['apiV1FundsGet'][0])
    {
        $request = $this->apiV1FundsGetRequest($contentType);

        try {
            $options = $this->createHttpClientOption();
            try {
                $response = $this->client->send($request, $options);
            } catch (RequestException $e) {
                throw new ApiException(
                    "[{$e->getCode()}] {$e->getMessage()}",
                    (int) $e->getCode(),
                    $e->getResponse() ? $e->getResponse()->getHeaders() : null,
                    $e->getResponse() ? (string) $e->getResponse()->getBody() : null
                );
            } catch (ConnectException $e) {
                throw new ApiException(
                    "[{$e->getCode()}] {$e->getMessage()}",
                    (int) $e->getCode(),
                    null,
                    null
                );
            }

            $statusCode = $response->getStatusCode();


            switch($statusCode) {
                case 200:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\ApiV1FundsGet200Response',
                        $request,
                        $response,
                    );
            }

            

            if ($statusCode < 200 || $statusCode > 299) {
                throw new ApiException(
                    sprintf(
                        '[%d] Error connecting to the API (%s)',
                        $statusCode,
                        (string) $request->getUri()
                    ),
                    $statusCode,
                    $response->getHeaders(),
                    (string) $response->getBody()
                );
            }

            return $this->handleResponseWithDataType(
                '\GoAdServerApi\Model\ApiV1FundsGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1FundsGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1FundsGetAsync
     *
     * Current advertiser balance + pending deposits.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FundsGetAsync(string $contentType = self::contentTypes['apiV1FundsGet'][0])
    {
        return $this->apiV1FundsGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1FundsGetAsyncWithHttpInfo
     *
     * Current advertiser balance + pending deposits.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FundsGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1FundsGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1FundsGet200Response';
        $request = $this->apiV1FundsGetRequest($contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    if ($returnType === '\SplFileObject') {
                        $content = $response->getBody(); //stream goes to serializer
                    } else {
                        $content = (string) $response->getBody();
                        if ($returnType !== 'string') {
                            $content = json_decode($content);
                        }
                    }

                    return [
                        ObjectSerializer::deserialize($content, $returnType, []),
                        $response->getStatusCode(),
                        $response->getHeaders()
                    ];
                },
                function ($exception) {
                    $response = $exception->getResponse();
                    $statusCode = $response->getStatusCode();
                    throw new ApiException(
                        sprintf(
                            '[%d] Error connecting to the API (%s)',
                            $statusCode,
                            $exception->getRequest()->getUri()
                        ),
                        $statusCode,
                        $response->getHeaders(),
                        (string) $response->getBody()
                    );
                }
            );
    }

    /**
     * Create request for operation 'apiV1FundsGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1FundsGetRequest(string $contentType = self::contentTypes['apiV1FundsGet'][0])
    {


        $resourcePath = '/api/v1/funds';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;





        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (count($formParams) > 0) {
            if ($multipart) {
                $multipartContents = [];
                foreach ($formParams as $formParamName => $formParamValue) {
                    $formParamValueItems = is_array($formParamValue) ? $formParamValue : [$formParamValue];
                    foreach ($formParamValueItems as $formParamValueItem) {
                        $multipartContents[] = [
                            'name' => $formParamName,
                            'contents' => $formParamValueItem
                        ];
                    }
                }
                // for HTTP post (form)
                $httpBody = new MultipartStream($multipartContents);

            } elseif (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the form parameters
                $httpBody = \GuzzleHttp\Utils::jsonEncode($formParams);
            } else {
                // for HTTP post (form)
                $httpBody = ObjectSerializer::buildQuery($formParams);
            }
        }

        // this endpoint requires Bearer (gas_live_<32 chars>) authentication (access token)
        if (!empty($this->config->getAccessToken())) {
            $headers['Authorization'] = 'Bearer ' . $this->config->getAccessToken();
        }

        $defaultHeaders = [];
        if ($this->config->getUserAgent()) {
            $defaultHeaders['User-Agent'] = $this->config->getUserAgent();
        }

        $headers = array_merge(
            $defaultHeaders,
            $headerParams,
            $headers
        );

        $operationHost = $this->config->getHost();
        $query = ObjectSerializer::buildQuery($queryParams);
        return new Request(
            'GET',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1FundsPayTypesGet
     *
     * Payment methods this account can deposit with.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsPayTypesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1FundsPayTypesGet200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1FundsPayTypesGet(string $contentType = self::contentTypes['apiV1FundsPayTypesGet'][0])
    {
        list($response) = $this->apiV1FundsPayTypesGetWithHttpInfo($contentType);
        return $response;
    }

    /**
     * Operation apiV1FundsPayTypesGetWithHttpInfo
     *
     * Payment methods this account can deposit with.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsPayTypesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1FundsPayTypesGet200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1FundsPayTypesGetWithHttpInfo(string $contentType = self::contentTypes['apiV1FundsPayTypesGet'][0])
    {
        $request = $this->apiV1FundsPayTypesGetRequest($contentType);

        try {
            $options = $this->createHttpClientOption();
            try {
                $response = $this->client->send($request, $options);
            } catch (RequestException $e) {
                throw new ApiException(
                    "[{$e->getCode()}] {$e->getMessage()}",
                    (int) $e->getCode(),
                    $e->getResponse() ? $e->getResponse()->getHeaders() : null,
                    $e->getResponse() ? (string) $e->getResponse()->getBody() : null
                );
            } catch (ConnectException $e) {
                throw new ApiException(
                    "[{$e->getCode()}] {$e->getMessage()}",
                    (int) $e->getCode(),
                    null,
                    null
                );
            }

            $statusCode = $response->getStatusCode();


            switch($statusCode) {
                case 200:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\ApiV1FundsPayTypesGet200Response',
                        $request,
                        $response,
                    );
                case 403:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
            }

            

            if ($statusCode < 200 || $statusCode > 299) {
                throw new ApiException(
                    sprintf(
                        '[%d] Error connecting to the API (%s)',
                        $statusCode,
                        (string) $request->getUri()
                    ),
                    $statusCode,
                    $response->getHeaders(),
                    (string) $response->getBody()
                );
            }

            return $this->handleResponseWithDataType(
                '\GoAdServerApi\Model\ApiV1FundsPayTypesGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1FundsPayTypesGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 403:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1FundsPayTypesGetAsync
     *
     * Payment methods this account can deposit with.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsPayTypesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FundsPayTypesGetAsync(string $contentType = self::contentTypes['apiV1FundsPayTypesGet'][0])
    {
        return $this->apiV1FundsPayTypesGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1FundsPayTypesGetAsyncWithHttpInfo
     *
     * Payment methods this account can deposit with.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsPayTypesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FundsPayTypesGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1FundsPayTypesGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1FundsPayTypesGet200Response';
        $request = $this->apiV1FundsPayTypesGetRequest($contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    if ($returnType === '\SplFileObject') {
                        $content = $response->getBody(); //stream goes to serializer
                    } else {
                        $content = (string) $response->getBody();
                        if ($returnType !== 'string') {
                            $content = json_decode($content);
                        }
                    }

                    return [
                        ObjectSerializer::deserialize($content, $returnType, []),
                        $response->getStatusCode(),
                        $response->getHeaders()
                    ];
                },
                function ($exception) {
                    $response = $exception->getResponse();
                    $statusCode = $response->getStatusCode();
                    throw new ApiException(
                        sprintf(
                            '[%d] Error connecting to the API (%s)',
                            $statusCode,
                            $exception->getRequest()->getUri()
                        ),
                        $statusCode,
                        $response->getHeaders(),
                        (string) $response->getBody()
                    );
                }
            );
    }

    /**
     * Create request for operation 'apiV1FundsPayTypesGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsPayTypesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1FundsPayTypesGetRequest(string $contentType = self::contentTypes['apiV1FundsPayTypesGet'][0])
    {


        $resourcePath = '/api/v1/funds/pay-types';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;





        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (count($formParams) > 0) {
            if ($multipart) {
                $multipartContents = [];
                foreach ($formParams as $formParamName => $formParamValue) {
                    $formParamValueItems = is_array($formParamValue) ? $formParamValue : [$formParamValue];
                    foreach ($formParamValueItems as $formParamValueItem) {
                        $multipartContents[] = [
                            'name' => $formParamName,
                            'contents' => $formParamValueItem
                        ];
                    }
                }
                // for HTTP post (form)
                $httpBody = new MultipartStream($multipartContents);

            } elseif (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the form parameters
                $httpBody = \GuzzleHttp\Utils::jsonEncode($formParams);
            } else {
                // for HTTP post (form)
                $httpBody = ObjectSerializer::buildQuery($formParams);
            }
        }

        // this endpoint requires Bearer (gas_live_<32 chars>) authentication (access token)
        if (!empty($this->config->getAccessToken())) {
            $headers['Authorization'] = 'Bearer ' . $this->config->getAccessToken();
        }

        $defaultHeaders = [];
        if ($this->config->getUserAgent()) {
            $defaultHeaders['User-Agent'] = $this->config->getUserAgent();
        }

        $headers = array_merge(
            $defaultHeaders,
            $headerParams,
            $headers
        );

        $operationHost = $this->config->getHost();
        $query = ObjectSerializer::buildQuery($queryParams);
        return new Request(
            'GET',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1FundsTransactionsGet
     *
     * Funds ledger — deposits, bonuses, transfers, refunds.
     *
     * @param  \DateTime|null $from dateadded &gt;&#x3D; from (optional)
     * @param  \DateTime|null $to dateadded &lt;&#x3D; to (optional)
     * @param  int|null $filter_id filter_id (optional)
     * @param  int|null $filter_paytype Numeric paytype (negative &#x3D; internal: -1 bonus, -2 transfer, -3 flat refund, -4 coupon, -5 from publisher, -6 RON refund, -7 credit, -8 deduction). (optional)
     * @param  string|null $sort_by sort_by (optional, default to 'dateadded')
     * @param  string|null $sort_dir sort_dir (optional, default to 'desc')
     * @param  int|null $page page (optional, default to 1)
     * @param  int|null $per_page per_page (optional, default to 20)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsTransactionsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1FundsTransactionsGet200Response|\GoAdServerApi\Model\Error
     */
    public function apiV1FundsTransactionsGet($from = null, $to = null, $filter_id = null, $filter_paytype = null, $sort_by = 'dateadded', $sort_dir = 'desc', $page = 1, $per_page = 20, string $contentType = self::contentTypes['apiV1FundsTransactionsGet'][0])
    {
        list($response) = $this->apiV1FundsTransactionsGetWithHttpInfo($from, $to, $filter_id, $filter_paytype, $sort_by, $sort_dir, $page, $per_page, $contentType);
        return $response;
    }

    /**
     * Operation apiV1FundsTransactionsGetWithHttpInfo
     *
     * Funds ledger — deposits, bonuses, transfers, refunds.
     *
     * @param  \DateTime|null $from dateadded &gt;&#x3D; from (optional)
     * @param  \DateTime|null $to dateadded &lt;&#x3D; to (optional)
     * @param  int|null $filter_id (optional)
     * @param  int|null $filter_paytype Numeric paytype (negative &#x3D; internal: -1 bonus, -2 transfer, -3 flat refund, -4 coupon, -5 from publisher, -6 RON refund, -7 credit, -8 deduction). (optional)
     * @param  string|null $sort_by (optional, default to 'dateadded')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsTransactionsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1FundsTransactionsGet200Response|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1FundsTransactionsGetWithHttpInfo($from = null, $to = null, $filter_id = null, $filter_paytype = null, $sort_by = 'dateadded', $sort_dir = 'desc', $page = 1, $per_page = 20, string $contentType = self::contentTypes['apiV1FundsTransactionsGet'][0])
    {
        $request = $this->apiV1FundsTransactionsGetRequest($from, $to, $filter_id, $filter_paytype, $sort_by, $sort_dir, $page, $per_page, $contentType);

        try {
            $options = $this->createHttpClientOption();
            try {
                $response = $this->client->send($request, $options);
            } catch (RequestException $e) {
                throw new ApiException(
                    "[{$e->getCode()}] {$e->getMessage()}",
                    (int) $e->getCode(),
                    $e->getResponse() ? $e->getResponse()->getHeaders() : null,
                    $e->getResponse() ? (string) $e->getResponse()->getBody() : null
                );
            } catch (ConnectException $e) {
                throw new ApiException(
                    "[{$e->getCode()}] {$e->getMessage()}",
                    (int) $e->getCode(),
                    null,
                    null
                );
            }

            $statusCode = $response->getStatusCode();


            switch($statusCode) {
                case 200:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\ApiV1FundsTransactionsGet200Response',
                        $request,
                        $response,
                    );
                case 403:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
            }

            

            if ($statusCode < 200 || $statusCode > 299) {
                throw new ApiException(
                    sprintf(
                        '[%d] Error connecting to the API (%s)',
                        $statusCode,
                        (string) $request->getUri()
                    ),
                    $statusCode,
                    $response->getHeaders(),
                    (string) $response->getBody()
                );
            }

            return $this->handleResponseWithDataType(
                '\GoAdServerApi\Model\ApiV1FundsTransactionsGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1FundsTransactionsGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 403:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1FundsTransactionsGetAsync
     *
     * Funds ledger — deposits, bonuses, transfers, refunds.
     *
     * @param  \DateTime|null $from dateadded &gt;&#x3D; from (optional)
     * @param  \DateTime|null $to dateadded &lt;&#x3D; to (optional)
     * @param  int|null $filter_id (optional)
     * @param  int|null $filter_paytype Numeric paytype (negative &#x3D; internal: -1 bonus, -2 transfer, -3 flat refund, -4 coupon, -5 from publisher, -6 RON refund, -7 credit, -8 deduction). (optional)
     * @param  string|null $sort_by (optional, default to 'dateadded')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsTransactionsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FundsTransactionsGetAsync($from = null, $to = null, $filter_id = null, $filter_paytype = null, $sort_by = 'dateadded', $sort_dir = 'desc', $page = 1, $per_page = 20, string $contentType = self::contentTypes['apiV1FundsTransactionsGet'][0])
    {
        return $this->apiV1FundsTransactionsGetAsyncWithHttpInfo($from, $to, $filter_id, $filter_paytype, $sort_by, $sort_dir, $page, $per_page, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1FundsTransactionsGetAsyncWithHttpInfo
     *
     * Funds ledger — deposits, bonuses, transfers, refunds.
     *
     * @param  \DateTime|null $from dateadded &gt;&#x3D; from (optional)
     * @param  \DateTime|null $to dateadded &lt;&#x3D; to (optional)
     * @param  int|null $filter_id (optional)
     * @param  int|null $filter_paytype Numeric paytype (negative &#x3D; internal: -1 bonus, -2 transfer, -3 flat refund, -4 coupon, -5 from publisher, -6 RON refund, -7 credit, -8 deduction). (optional)
     * @param  string|null $sort_by (optional, default to 'dateadded')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsTransactionsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FundsTransactionsGetAsyncWithHttpInfo($from = null, $to = null, $filter_id = null, $filter_paytype = null, $sort_by = 'dateadded', $sort_dir = 'desc', $page = 1, $per_page = 20, string $contentType = self::contentTypes['apiV1FundsTransactionsGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1FundsTransactionsGet200Response';
        $request = $this->apiV1FundsTransactionsGetRequest($from, $to, $filter_id, $filter_paytype, $sort_by, $sort_dir, $page, $per_page, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    if ($returnType === '\SplFileObject') {
                        $content = $response->getBody(); //stream goes to serializer
                    } else {
                        $content = (string) $response->getBody();
                        if ($returnType !== 'string') {
                            $content = json_decode($content);
                        }
                    }

                    return [
                        ObjectSerializer::deserialize($content, $returnType, []),
                        $response->getStatusCode(),
                        $response->getHeaders()
                    ];
                },
                function ($exception) {
                    $response = $exception->getResponse();
                    $statusCode = $response->getStatusCode();
                    throw new ApiException(
                        sprintf(
                            '[%d] Error connecting to the API (%s)',
                            $statusCode,
                            $exception->getRequest()->getUri()
                        ),
                        $statusCode,
                        $response->getHeaders(),
                        (string) $response->getBody()
                    );
                }
            );
    }

    /**
     * Create request for operation 'apiV1FundsTransactionsGet'
     *
     * @param  \DateTime|null $from dateadded &gt;&#x3D; from (optional)
     * @param  \DateTime|null $to dateadded &lt;&#x3D; to (optional)
     * @param  int|null $filter_id (optional)
     * @param  int|null $filter_paytype Numeric paytype (negative &#x3D; internal: -1 bonus, -2 transfer, -3 flat refund, -4 coupon, -5 from publisher, -6 RON refund, -7 credit, -8 deduction). (optional)
     * @param  string|null $sort_by (optional, default to 'dateadded')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsTransactionsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1FundsTransactionsGetRequest($from = null, $to = null, $filter_id = null, $filter_paytype = null, $sort_by = 'dateadded', $sort_dir = 'desc', $page = 1, $per_page = 20, string $contentType = self::contentTypes['apiV1FundsTransactionsGet'][0])
    {







        if ($page !== null && $page < 1) {
            throw new \InvalidArgumentException('invalid value for "$page" when calling FundsApi.apiV1FundsTransactionsGet, must be bigger than or equal to 1.');
        }
        
        if ($per_page !== null && $per_page > 100) {
            throw new \InvalidArgumentException('invalid value for "$per_page" when calling FundsApi.apiV1FundsTransactionsGet, must be smaller than or equal to 100.');
        }
        if ($per_page !== null && $per_page < 1) {
            throw new \InvalidArgumentException('invalid value for "$per_page" when calling FundsApi.apiV1FundsTransactionsGet, must be bigger than or equal to 1.');
        }
        

        $resourcePath = '/api/v1/funds/transactions';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $from,
            'from', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $to,
            'to', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $filter_id,
            'filter_id', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $filter_paytype,
            'filter_paytype', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $sort_by,
            'sort_by', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $sort_dir,
            'sort_dir', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $page,
            'page', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $per_page,
            'per_page', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);




        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (count($formParams) > 0) {
            if ($multipart) {
                $multipartContents = [];
                foreach ($formParams as $formParamName => $formParamValue) {
                    $formParamValueItems = is_array($formParamValue) ? $formParamValue : [$formParamValue];
                    foreach ($formParamValueItems as $formParamValueItem) {
                        $multipartContents[] = [
                            'name' => $formParamName,
                            'contents' => $formParamValueItem
                        ];
                    }
                }
                // for HTTP post (form)
                $httpBody = new MultipartStream($multipartContents);

            } elseif (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the form parameters
                $httpBody = \GuzzleHttp\Utils::jsonEncode($formParams);
            } else {
                // for HTTP post (form)
                $httpBody = ObjectSerializer::buildQuery($formParams);
            }
        }

        // this endpoint requires Bearer (gas_live_<32 chars>) authentication (access token)
        if (!empty($this->config->getAccessToken())) {
            $headers['Authorization'] = 'Bearer ' . $this->config->getAccessToken();
        }

        $defaultHeaders = [];
        if ($this->config->getUserAgent()) {
            $defaultHeaders['User-Agent'] = $this->config->getUserAgent();
        }

        $headers = array_merge(
            $defaultHeaders,
            $headerParams,
            $headers
        );

        $operationHost = $this->config->getHost();
        $query = ObjectSerializer::buildQuery($queryParams);
        return new Request(
            'GET',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1FundsTransferPost
     *
     * Move balance to a connected advertiser account.
     *
     * @param  \GoAdServerApi\Model\ApiV1FundsTransferPostRequest $api_v1_funds_transfer_post_request api_v1_funds_transfer_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsTransferPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1FundsTransferPost($api_v1_funds_transfer_post_request, string $contentType = self::contentTypes['apiV1FundsTransferPost'][0])
    {
        $this->apiV1FundsTransferPostWithHttpInfo($api_v1_funds_transfer_post_request, $contentType);
    }

    /**
     * Operation apiV1FundsTransferPostWithHttpInfo
     *
     * Move balance to a connected advertiser account.
     *
     * @param  \GoAdServerApi\Model\ApiV1FundsTransferPostRequest $api_v1_funds_transfer_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsTransferPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1FundsTransferPostWithHttpInfo($api_v1_funds_transfer_post_request, string $contentType = self::contentTypes['apiV1FundsTransferPost'][0])
    {
        $request = $this->apiV1FundsTransferPostRequest($api_v1_funds_transfer_post_request, $contentType);

        try {
            $options = $this->createHttpClientOption();
            try {
                $response = $this->client->send($request, $options);
            } catch (RequestException $e) {
                throw new ApiException(
                    "[{$e->getCode()}] {$e->getMessage()}",
                    (int) $e->getCode(),
                    $e->getResponse() ? $e->getResponse()->getHeaders() : null,
                    $e->getResponse() ? (string) $e->getResponse()->getBody() : null
                );
            } catch (ConnectException $e) {
                throw new ApiException(
                    "[{$e->getCode()}] {$e->getMessage()}",
                    (int) $e->getCode(),
                    null,
                    null
                );
            }

            $statusCode = $response->getStatusCode();


            return [null, $statusCode, $response->getHeaders()];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1FundsTransferPostAsync
     *
     * Move balance to a connected advertiser account.
     *
     * @param  \GoAdServerApi\Model\ApiV1FundsTransferPostRequest $api_v1_funds_transfer_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsTransferPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FundsTransferPostAsync($api_v1_funds_transfer_post_request, string $contentType = self::contentTypes['apiV1FundsTransferPost'][0])
    {
        return $this->apiV1FundsTransferPostAsyncWithHttpInfo($api_v1_funds_transfer_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1FundsTransferPostAsyncWithHttpInfo
     *
     * Move balance to a connected advertiser account.
     *
     * @param  \GoAdServerApi\Model\ApiV1FundsTransferPostRequest $api_v1_funds_transfer_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsTransferPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1FundsTransferPostAsyncWithHttpInfo($api_v1_funds_transfer_post_request, string $contentType = self::contentTypes['apiV1FundsTransferPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1FundsTransferPostRequest($api_v1_funds_transfer_post_request, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    return [null, $response->getStatusCode(), $response->getHeaders()];
                },
                function ($exception) {
                    $response = $exception->getResponse();
                    $statusCode = $response->getStatusCode();
                    throw new ApiException(
                        sprintf(
                            '[%d] Error connecting to the API (%s)',
                            $statusCode,
                            $exception->getRequest()->getUri()
                        ),
                        $statusCode,
                        $response->getHeaders(),
                        (string) $response->getBody()
                    );
                }
            );
    }

    /**
     * Create request for operation 'apiV1FundsTransferPost'
     *
     * @param  \GoAdServerApi\Model\ApiV1FundsTransferPostRequest $api_v1_funds_transfer_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1FundsTransferPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1FundsTransferPostRequest($api_v1_funds_transfer_post_request, string $contentType = self::contentTypes['apiV1FundsTransferPost'][0])
    {

        // verify the required parameter 'api_v1_funds_transfer_post_request' is set
        if ($api_v1_funds_transfer_post_request === null || (is_array($api_v1_funds_transfer_post_request) && count($api_v1_funds_transfer_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_funds_transfer_post_request when calling apiV1FundsTransferPost'
            );
        }


        $resourcePath = '/api/v1/funds/transfer';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;





        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (isset($api_v1_funds_transfer_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_funds_transfer_post_request));
            } else {
                $httpBody = $api_v1_funds_transfer_post_request;
            }
        } elseif (count($formParams) > 0) {
            if ($multipart) {
                $multipartContents = [];
                foreach ($formParams as $formParamName => $formParamValue) {
                    $formParamValueItems = is_array($formParamValue) ? $formParamValue : [$formParamValue];
                    foreach ($formParamValueItems as $formParamValueItem) {
                        $multipartContents[] = [
                            'name' => $formParamName,
                            'contents' => $formParamValueItem
                        ];
                    }
                }
                // for HTTP post (form)
                $httpBody = new MultipartStream($multipartContents);

            } elseif (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the form parameters
                $httpBody = \GuzzleHttp\Utils::jsonEncode($formParams);
            } else {
                // for HTTP post (form)
                $httpBody = ObjectSerializer::buildQuery($formParams);
            }
        }

        // this endpoint requires Bearer (gas_live_<32 chars>) authentication (access token)
        if (!empty($this->config->getAccessToken())) {
            $headers['Authorization'] = 'Bearer ' . $this->config->getAccessToken();
        }

        $defaultHeaders = [];
        if ($this->config->getUserAgent()) {
            $defaultHeaders['User-Agent'] = $this->config->getUserAgent();
        }

        $headers = array_merge(
            $defaultHeaders,
            $headerParams,
            $headers
        );

        $operationHost = $this->config->getHost();
        $query = ObjectSerializer::buildQuery($queryParams);
        return new Request(
            'POST',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Create http client option
     *
     * @throws \RuntimeException on file opening failure
     * @return array of http client options
     */
    protected function createHttpClientOption()
    {
        $options = [];
        if ($this->config->getDebug()) {
            $options[RequestOptions::DEBUG] = fopen($this->config->getDebugFile(), 'a');
            if (!$options[RequestOptions::DEBUG]) {
                throw new \RuntimeException('Failed to open the debug file: ' . $this->config->getDebugFile());
            }
        }

        if ($this->config->getCertFile()) {
            $options[RequestOptions::CERT] = $this->config->getCertFile();
        }

        if ($this->config->getKeyFile()) {
            $options[RequestOptions::SSL_KEY] = $this->config->getKeyFile();
        }

        return $options;
    }

    private function handleResponseWithDataType(
        string $dataType,
        RequestInterface $request,
        ResponseInterface $response
    ): array {
        if ($dataType === '\SplFileObject') {
            $content = $response->getBody(); //stream goes to serializer
        } else {
            $content = (string) $response->getBody();
            if ($dataType !== 'string') {
                try {
                    $content = json_decode($content, false, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $exception) {
                    throw new ApiException(
                        sprintf(
                            'Error JSON decoding server response (%s)',
                            $request->getUri()
                        ),
                        $response->getStatusCode(),
                        $response->getHeaders(),
                        $content
                    );
                }
            }
        }

        return [
            ObjectSerializer::deserialize($content, $dataType, []),
            $response->getStatusCode(),
            $response->getHeaders()
        ];
    }

    private function responseWithinRangeCode(
        string $rangeCode,
        int $statusCode
    ): bool {
        $left = (int) ($rangeCode[0].'00');
        $right = (int) ($rangeCode[0].'99');

        return $statusCode >= $left && $statusCode <= $right;
    }
}
