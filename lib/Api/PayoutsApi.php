<?php
/**
 * PayoutsApi
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
 * PayoutsApi Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class PayoutsApi
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
        'apiV1PublisherFinanceEarningsGet' => [
            'application/json',
        ],
        'apiV1PublisherFinanceGet' => [
            'application/json',
        ],
        'apiV1PublisherFinanceMonthEarningsGet' => [
            'application/json',
        ],
        'apiV1PublisherFinancePayoutRequestPost' => [
            'application/json',
        ],
        'apiV1PublisherFinancePayoutTypesGet' => [
            'application/json',
        ],
        'apiV1PublisherFinancePayoutsGet' => [
            'application/json',
        ],
        'apiV1PublisherFinanceSettingsGet' => [
            'application/json',
        ],
        'apiV1PublisherFinanceSettingsPut' => [
            'application/json',
        ],
        'apiV1PublisherFinanceTransactionsGet' => [
            'application/json',
        ],
        'apiV1PublisherFinanceTransactionsIdGet' => [
            'application/json',
        ],
        'apiV1PublisherFinanceTransactionsIdInvoicePdfGet' => [
            'application/json',
        ],
        'apiV1PublisherFinanceTransferToAdvertiserPost' => [
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
     * Operation apiV1PublisherFinanceEarningsGet
     *
     * Earnings per day, week or month.
     *
     * @param  \DateTime|null $from Inclusive start (YYYY-MM-DD). Both &#x60;from&#x60; and &#x60;to&#x60; must be given, else the last 30 days are used. (optional)
     * @param  \DateTime|null $to Inclusive end (YYYY-MM-DD). (optional)
     * @param  string|null $group_by &#x60;week&#x60; buckets are labelled &#x60;YYYY-WW&#x60; (ISO week), &#x60;month&#x60; buckets &#x60;YYYY-MM&#x60;. (optional, default to 'day')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceEarningsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherFinanceEarningsGet200Response|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error
     */
    public function apiV1PublisherFinanceEarningsGet($from = null, $to = null, $group_by = 'day', string $contentType = self::contentTypes['apiV1PublisherFinanceEarningsGet'][0])
    {
        list($response) = $this->apiV1PublisherFinanceEarningsGetWithHttpInfo($from, $to, $group_by, $contentType);
        return $response;
    }

    /**
     * Operation apiV1PublisherFinanceEarningsGetWithHttpInfo
     *
     * Earnings per day, week or month.
     *
     * @param  \DateTime|null $from Inclusive start (YYYY-MM-DD). Both &#x60;from&#x60; and &#x60;to&#x60; must be given, else the last 30 days are used. (optional)
     * @param  \DateTime|null $to Inclusive end (YYYY-MM-DD). (optional)
     * @param  string|null $group_by &#x60;week&#x60; buckets are labelled &#x60;YYYY-WW&#x60; (ISO week), &#x60;month&#x60; buckets &#x60;YYYY-MM&#x60;. (optional, default to 'day')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceEarningsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherFinanceEarningsGet200Response|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherFinanceEarningsGetWithHttpInfo($from = null, $to = null, $group_by = 'day', string $contentType = self::contentTypes['apiV1PublisherFinanceEarningsGet'][0])
    {
        $request = $this->apiV1PublisherFinanceEarningsGetRequest($from, $to, $group_by, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1PublisherFinanceEarningsGet200Response',
                        $request,
                        $response,
                    );
                case 401:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 403:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 404:
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
                '\GoAdServerApi\Model\ApiV1PublisherFinanceEarningsGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1PublisherFinanceEarningsGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 401:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
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
                case 404:
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
     * Operation apiV1PublisherFinanceEarningsGetAsync
     *
     * Earnings per day, week or month.
     *
     * @param  \DateTime|null $from Inclusive start (YYYY-MM-DD). Both &#x60;from&#x60; and &#x60;to&#x60; must be given, else the last 30 days are used. (optional)
     * @param  \DateTime|null $to Inclusive end (YYYY-MM-DD). (optional)
     * @param  string|null $group_by &#x60;week&#x60; buckets are labelled &#x60;YYYY-WW&#x60; (ISO week), &#x60;month&#x60; buckets &#x60;YYYY-MM&#x60;. (optional, default to 'day')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceEarningsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherFinanceEarningsGetAsync($from = null, $to = null, $group_by = 'day', string $contentType = self::contentTypes['apiV1PublisherFinanceEarningsGet'][0])
    {
        return $this->apiV1PublisherFinanceEarningsGetAsyncWithHttpInfo($from, $to, $group_by, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherFinanceEarningsGetAsyncWithHttpInfo
     *
     * Earnings per day, week or month.
     *
     * @param  \DateTime|null $from Inclusive start (YYYY-MM-DD). Both &#x60;from&#x60; and &#x60;to&#x60; must be given, else the last 30 days are used. (optional)
     * @param  \DateTime|null $to Inclusive end (YYYY-MM-DD). (optional)
     * @param  string|null $group_by &#x60;week&#x60; buckets are labelled &#x60;YYYY-WW&#x60; (ISO week), &#x60;month&#x60; buckets &#x60;YYYY-MM&#x60;. (optional, default to 'day')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceEarningsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherFinanceEarningsGetAsyncWithHttpInfo($from = null, $to = null, $group_by = 'day', string $contentType = self::contentTypes['apiV1PublisherFinanceEarningsGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherFinanceEarningsGet200Response';
        $request = $this->apiV1PublisherFinanceEarningsGetRequest($from, $to, $group_by, $contentType);

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
     * Create request for operation 'apiV1PublisherFinanceEarningsGet'
     *
     * @param  \DateTime|null $from Inclusive start (YYYY-MM-DD). Both &#x60;from&#x60; and &#x60;to&#x60; must be given, else the last 30 days are used. (optional)
     * @param  \DateTime|null $to Inclusive end (YYYY-MM-DD). (optional)
     * @param  string|null $group_by &#x60;week&#x60; buckets are labelled &#x60;YYYY-WW&#x60; (ISO week), &#x60;month&#x60; buckets &#x60;YYYY-MM&#x60;. (optional, default to 'day')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceEarningsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherFinanceEarningsGetRequest($from = null, $to = null, $group_by = 'day', string $contentType = self::contentTypes['apiV1PublisherFinanceEarningsGet'][0])
    {





        $resourcePath = '/api/v1/publisher/finance/earnings';
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
            $group_by,
            'group_by', // param base name
            'string', // openApiType
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
     * Operation apiV1PublisherFinanceGet
     *
     * Publisher balance and payout rules.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherFinanceGet200Response|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error
     */
    public function apiV1PublisherFinanceGet(string $contentType = self::contentTypes['apiV1PublisherFinanceGet'][0])
    {
        list($response) = $this->apiV1PublisherFinanceGetWithHttpInfo($contentType);
        return $response;
    }

    /**
     * Operation apiV1PublisherFinanceGetWithHttpInfo
     *
     * Publisher balance and payout rules.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherFinanceGet200Response|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherFinanceGetWithHttpInfo(string $contentType = self::contentTypes['apiV1PublisherFinanceGet'][0])
    {
        $request = $this->apiV1PublisherFinanceGetRequest($contentType);

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
                        '\GoAdServerApi\Model\ApiV1PublisherFinanceGet200Response',
                        $request,
                        $response,
                    );
                case 401:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 403:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 404:
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
                '\GoAdServerApi\Model\ApiV1PublisherFinanceGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1PublisherFinanceGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 401:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
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
                case 404:
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
     * Operation apiV1PublisherFinanceGetAsync
     *
     * Publisher balance and payout rules.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherFinanceGetAsync(string $contentType = self::contentTypes['apiV1PublisherFinanceGet'][0])
    {
        return $this->apiV1PublisherFinanceGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherFinanceGetAsyncWithHttpInfo
     *
     * Publisher balance and payout rules.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherFinanceGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1PublisherFinanceGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherFinanceGet200Response';
        $request = $this->apiV1PublisherFinanceGetRequest($contentType);

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
     * Create request for operation 'apiV1PublisherFinanceGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherFinanceGetRequest(string $contentType = self::contentTypes['apiV1PublisherFinanceGet'][0])
    {


        $resourcePath = '/api/v1/publisher/finance';
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
     * Operation apiV1PublisherFinanceMonthEarningsGet
     *
     * Monthly earnings statements.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceMonthEarningsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherFinanceMonthEarningsGet200ResponseInner[]|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error
     */
    public function apiV1PublisherFinanceMonthEarningsGet(string $contentType = self::contentTypes['apiV1PublisherFinanceMonthEarningsGet'][0])
    {
        list($response) = $this->apiV1PublisherFinanceMonthEarningsGetWithHttpInfo($contentType);
        return $response;
    }

    /**
     * Operation apiV1PublisherFinanceMonthEarningsGetWithHttpInfo
     *
     * Monthly earnings statements.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceMonthEarningsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherFinanceMonthEarningsGet200ResponseInner[]|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherFinanceMonthEarningsGetWithHttpInfo(string $contentType = self::contentTypes['apiV1PublisherFinanceMonthEarningsGet'][0])
    {
        $request = $this->apiV1PublisherFinanceMonthEarningsGetRequest($contentType);

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
                        '\GoAdServerApi\Model\ApiV1PublisherFinanceMonthEarningsGet200ResponseInner[]',
                        $request,
                        $response,
                    );
                case 401:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 403:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 404:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 500:
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
                '\GoAdServerApi\Model\ApiV1PublisherFinanceMonthEarningsGet200ResponseInner[]',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1PublisherFinanceMonthEarningsGet200ResponseInner[]',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 401:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
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
                case 404:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 500:
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
     * Operation apiV1PublisherFinanceMonthEarningsGetAsync
     *
     * Monthly earnings statements.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceMonthEarningsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherFinanceMonthEarningsGetAsync(string $contentType = self::contentTypes['apiV1PublisherFinanceMonthEarningsGet'][0])
    {
        return $this->apiV1PublisherFinanceMonthEarningsGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherFinanceMonthEarningsGetAsyncWithHttpInfo
     *
     * Monthly earnings statements.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceMonthEarningsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherFinanceMonthEarningsGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1PublisherFinanceMonthEarningsGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherFinanceMonthEarningsGet200ResponseInner[]';
        $request = $this->apiV1PublisherFinanceMonthEarningsGetRequest($contentType);

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
     * Create request for operation 'apiV1PublisherFinanceMonthEarningsGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceMonthEarningsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherFinanceMonthEarningsGetRequest(string $contentType = self::contentTypes['apiV1PublisherFinanceMonthEarningsGet'][0])
    {


        $resourcePath = '/api/v1/publisher/finance/month-earnings';
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
     * Operation apiV1PublisherFinancePayoutRequestPost
     *
     * Request a payout of the balance.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherFinancePayoutRequestPostRequest|null $api_v1_publisher_finance_payout_request_post_request api_v1_publisher_finance_payout_request_post_request (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinancePayoutRequestPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherFinancePayoutRequestPost201Response|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error
     */
    public function apiV1PublisherFinancePayoutRequestPost($api_v1_publisher_finance_payout_request_post_request = null, string $contentType = self::contentTypes['apiV1PublisherFinancePayoutRequestPost'][0])
    {
        list($response) = $this->apiV1PublisherFinancePayoutRequestPostWithHttpInfo($api_v1_publisher_finance_payout_request_post_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1PublisherFinancePayoutRequestPostWithHttpInfo
     *
     * Request a payout of the balance.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherFinancePayoutRequestPostRequest|null $api_v1_publisher_finance_payout_request_post_request (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinancePayoutRequestPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherFinancePayoutRequestPost201Response|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherFinancePayoutRequestPostWithHttpInfo($api_v1_publisher_finance_payout_request_post_request = null, string $contentType = self::contentTypes['apiV1PublisherFinancePayoutRequestPost'][0])
    {
        $request = $this->apiV1PublisherFinancePayoutRequestPostRequest($api_v1_publisher_finance_payout_request_post_request, $contentType);

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
                case 201:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\ApiV1PublisherFinancePayoutRequestPost201Response',
                        $request,
                        $response,
                    );
                case 400:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 401:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 403:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 404:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 409:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 422:
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
                '\GoAdServerApi\Model\ApiV1PublisherFinancePayoutRequestPost201Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 201:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1PublisherFinancePayoutRequestPost201Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 400:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 401:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
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
                case 404:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 409:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 422:
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
     * Operation apiV1PublisherFinancePayoutRequestPostAsync
     *
     * Request a payout of the balance.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherFinancePayoutRequestPostRequest|null $api_v1_publisher_finance_payout_request_post_request (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinancePayoutRequestPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherFinancePayoutRequestPostAsync($api_v1_publisher_finance_payout_request_post_request = null, string $contentType = self::contentTypes['apiV1PublisherFinancePayoutRequestPost'][0])
    {
        return $this->apiV1PublisherFinancePayoutRequestPostAsyncWithHttpInfo($api_v1_publisher_finance_payout_request_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherFinancePayoutRequestPostAsyncWithHttpInfo
     *
     * Request a payout of the balance.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherFinancePayoutRequestPostRequest|null $api_v1_publisher_finance_payout_request_post_request (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinancePayoutRequestPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherFinancePayoutRequestPostAsyncWithHttpInfo($api_v1_publisher_finance_payout_request_post_request = null, string $contentType = self::contentTypes['apiV1PublisherFinancePayoutRequestPost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherFinancePayoutRequestPost201Response';
        $request = $this->apiV1PublisherFinancePayoutRequestPostRequest($api_v1_publisher_finance_payout_request_post_request, $contentType);

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
     * Create request for operation 'apiV1PublisherFinancePayoutRequestPost'
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherFinancePayoutRequestPostRequest|null $api_v1_publisher_finance_payout_request_post_request (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinancePayoutRequestPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherFinancePayoutRequestPostRequest($api_v1_publisher_finance_payout_request_post_request = null, string $contentType = self::contentTypes['apiV1PublisherFinancePayoutRequestPost'][0])
    {



        $resourcePath = '/api/v1/publisher/finance/payout-request';
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
        if (isset($api_v1_publisher_finance_payout_request_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_publisher_finance_payout_request_post_request));
            } else {
                $httpBody = $api_v1_publisher_finance_payout_request_post_request;
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
     * Operation apiV1PublisherFinancePayoutTypesGet
     *
     * Payout methods this account may choose.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinancePayoutTypesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherFinancePayoutTypesGet200ResponseInner[]|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error
     */
    public function apiV1PublisherFinancePayoutTypesGet(string $contentType = self::contentTypes['apiV1PublisherFinancePayoutTypesGet'][0])
    {
        list($response) = $this->apiV1PublisherFinancePayoutTypesGetWithHttpInfo($contentType);
        return $response;
    }

    /**
     * Operation apiV1PublisherFinancePayoutTypesGetWithHttpInfo
     *
     * Payout methods this account may choose.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinancePayoutTypesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherFinancePayoutTypesGet200ResponseInner[]|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherFinancePayoutTypesGetWithHttpInfo(string $contentType = self::contentTypes['apiV1PublisherFinancePayoutTypesGet'][0])
    {
        $request = $this->apiV1PublisherFinancePayoutTypesGetRequest($contentType);

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
                        '\GoAdServerApi\Model\ApiV1PublisherFinancePayoutTypesGet200ResponseInner[]',
                        $request,
                        $response,
                    );
                case 401:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 403:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 404:
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
                '\GoAdServerApi\Model\ApiV1PublisherFinancePayoutTypesGet200ResponseInner[]',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1PublisherFinancePayoutTypesGet200ResponseInner[]',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 401:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
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
                case 404:
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
     * Operation apiV1PublisherFinancePayoutTypesGetAsync
     *
     * Payout methods this account may choose.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinancePayoutTypesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherFinancePayoutTypesGetAsync(string $contentType = self::contentTypes['apiV1PublisherFinancePayoutTypesGet'][0])
    {
        return $this->apiV1PublisherFinancePayoutTypesGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherFinancePayoutTypesGetAsyncWithHttpInfo
     *
     * Payout methods this account may choose.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinancePayoutTypesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherFinancePayoutTypesGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1PublisherFinancePayoutTypesGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherFinancePayoutTypesGet200ResponseInner[]';
        $request = $this->apiV1PublisherFinancePayoutTypesGetRequest($contentType);

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
     * Create request for operation 'apiV1PublisherFinancePayoutTypesGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinancePayoutTypesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherFinancePayoutTypesGetRequest(string $contentType = self::contentTypes['apiV1PublisherFinancePayoutTypesGet'][0])
    {


        $resourcePath = '/api/v1/publisher/finance/payout-types';
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
     * Operation apiV1PublisherFinancePayoutsGet
     *
     * List payout requests and payouts.
     *
     * @param  int|null $page page (optional, default to 1)
     * @param  int|null $per_page per_page (optional, default to 20)
     * @param  string|null $sort_by sort_by (optional, default to 'id')
     * @param  string|null $sort_dir sort_dir (optional, default to 'desc')
     * @param  int|null $filter_id filter_id (optional)
     * @param  int|null $filter_ispayed filter_ispayed (optional)
     * @param  int|null $filter_transacttype filter_transacttype (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinancePayoutsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherFinancePayoutsGet200Response|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error
     */
    public function apiV1PublisherFinancePayoutsGet($page = 1, $per_page = 20, $sort_by = 'id', $sort_dir = 'desc', $filter_id = null, $filter_ispayed = null, $filter_transacttype = null, string $contentType = self::contentTypes['apiV1PublisherFinancePayoutsGet'][0])
    {
        list($response) = $this->apiV1PublisherFinancePayoutsGetWithHttpInfo($page, $per_page, $sort_by, $sort_dir, $filter_id, $filter_ispayed, $filter_transacttype, $contentType);
        return $response;
    }

    /**
     * Operation apiV1PublisherFinancePayoutsGetWithHttpInfo
     *
     * List payout requests and payouts.
     *
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $filter_id (optional)
     * @param  int|null $filter_ispayed (optional)
     * @param  int|null $filter_transacttype (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinancePayoutsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherFinancePayoutsGet200Response|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherFinancePayoutsGetWithHttpInfo($page = 1, $per_page = 20, $sort_by = 'id', $sort_dir = 'desc', $filter_id = null, $filter_ispayed = null, $filter_transacttype = null, string $contentType = self::contentTypes['apiV1PublisherFinancePayoutsGet'][0])
    {
        $request = $this->apiV1PublisherFinancePayoutsGetRequest($page, $per_page, $sort_by, $sort_dir, $filter_id, $filter_ispayed, $filter_transacttype, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1PublisherFinancePayoutsGet200Response',
                        $request,
                        $response,
                    );
                case 401:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 403:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 404:
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
                '\GoAdServerApi\Model\ApiV1PublisherFinancePayoutsGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1PublisherFinancePayoutsGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 401:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
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
                case 404:
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
     * Operation apiV1PublisherFinancePayoutsGetAsync
     *
     * List payout requests and payouts.
     *
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $filter_id (optional)
     * @param  int|null $filter_ispayed (optional)
     * @param  int|null $filter_transacttype (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinancePayoutsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherFinancePayoutsGetAsync($page = 1, $per_page = 20, $sort_by = 'id', $sort_dir = 'desc', $filter_id = null, $filter_ispayed = null, $filter_transacttype = null, string $contentType = self::contentTypes['apiV1PublisherFinancePayoutsGet'][0])
    {
        return $this->apiV1PublisherFinancePayoutsGetAsyncWithHttpInfo($page, $per_page, $sort_by, $sort_dir, $filter_id, $filter_ispayed, $filter_transacttype, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherFinancePayoutsGetAsyncWithHttpInfo
     *
     * List payout requests and payouts.
     *
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $filter_id (optional)
     * @param  int|null $filter_ispayed (optional)
     * @param  int|null $filter_transacttype (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinancePayoutsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherFinancePayoutsGetAsyncWithHttpInfo($page = 1, $per_page = 20, $sort_by = 'id', $sort_dir = 'desc', $filter_id = null, $filter_ispayed = null, $filter_transacttype = null, string $contentType = self::contentTypes['apiV1PublisherFinancePayoutsGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherFinancePayoutsGet200Response';
        $request = $this->apiV1PublisherFinancePayoutsGetRequest($page, $per_page, $sort_by, $sort_dir, $filter_id, $filter_ispayed, $filter_transacttype, $contentType);

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
     * Create request for operation 'apiV1PublisherFinancePayoutsGet'
     *
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $filter_id (optional)
     * @param  int|null $filter_ispayed (optional)
     * @param  int|null $filter_transacttype (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinancePayoutsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherFinancePayoutsGetRequest($page = 1, $per_page = 20, $sort_by = 'id', $sort_dir = 'desc', $filter_id = null, $filter_ispayed = null, $filter_transacttype = null, string $contentType = self::contentTypes['apiV1PublisherFinancePayoutsGet'][0])
    {

        if ($page !== null && $page < 1) {
            throw new \InvalidArgumentException('invalid value for "$page" when calling PayoutsApi.apiV1PublisherFinancePayoutsGet, must be bigger than or equal to 1.');
        }
        
        if ($per_page !== null && $per_page > 100) {
            throw new \InvalidArgumentException('invalid value for "$per_page" when calling PayoutsApi.apiV1PublisherFinancePayoutsGet, must be smaller than or equal to 100.');
        }
        if ($per_page !== null && $per_page < 1) {
            throw new \InvalidArgumentException('invalid value for "$per_page" when calling PayoutsApi.apiV1PublisherFinancePayoutsGet, must be bigger than or equal to 1.');
        }
        






        $resourcePath = '/api/v1/publisher/finance/payouts';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

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
            $filter_id,
            'filter_id', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $filter_ispayed,
            'filter_ispayed', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $filter_transacttype,
            'filter_transacttype', // param base name
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
     * Operation apiV1PublisherFinanceSettingsGet
     *
     * Payout method and payment details.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceSettingsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherFinanceSettingsGet200Response|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error
     */
    public function apiV1PublisherFinanceSettingsGet(string $contentType = self::contentTypes['apiV1PublisherFinanceSettingsGet'][0])
    {
        list($response) = $this->apiV1PublisherFinanceSettingsGetWithHttpInfo($contentType);
        return $response;
    }

    /**
     * Operation apiV1PublisherFinanceSettingsGetWithHttpInfo
     *
     * Payout method and payment details.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceSettingsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherFinanceSettingsGet200Response|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherFinanceSettingsGetWithHttpInfo(string $contentType = self::contentTypes['apiV1PublisherFinanceSettingsGet'][0])
    {
        $request = $this->apiV1PublisherFinanceSettingsGetRequest($contentType);

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
                        '\GoAdServerApi\Model\ApiV1PublisherFinanceSettingsGet200Response',
                        $request,
                        $response,
                    );
                case 401:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 403:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 404:
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
                '\GoAdServerApi\Model\ApiV1PublisherFinanceSettingsGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1PublisherFinanceSettingsGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 401:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
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
                case 404:
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
     * Operation apiV1PublisherFinanceSettingsGetAsync
     *
     * Payout method and payment details.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceSettingsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherFinanceSettingsGetAsync(string $contentType = self::contentTypes['apiV1PublisherFinanceSettingsGet'][0])
    {
        return $this->apiV1PublisherFinanceSettingsGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherFinanceSettingsGetAsyncWithHttpInfo
     *
     * Payout method and payment details.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceSettingsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherFinanceSettingsGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1PublisherFinanceSettingsGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherFinanceSettingsGet200Response';
        $request = $this->apiV1PublisherFinanceSettingsGetRequest($contentType);

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
     * Create request for operation 'apiV1PublisherFinanceSettingsGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceSettingsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherFinanceSettingsGetRequest(string $contentType = self::contentTypes['apiV1PublisherFinanceSettingsGet'][0])
    {


        $resourcePath = '/api/v1/publisher/finance/settings';
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
     * Operation apiV1PublisherFinanceSettingsPut
     *
     * Change the payout method or payment details.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherFinanceSettingsPutRequest $api_v1_publisher_finance_settings_put_request api_v1_publisher_finance_settings_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceSettingsPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1RetargetingPixelsIdDelete200Response|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error
     */
    public function apiV1PublisherFinanceSettingsPut($api_v1_publisher_finance_settings_put_request, string $contentType = self::contentTypes['apiV1PublisherFinanceSettingsPut'][0])
    {
        list($response) = $this->apiV1PublisherFinanceSettingsPutWithHttpInfo($api_v1_publisher_finance_settings_put_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1PublisherFinanceSettingsPutWithHttpInfo
     *
     * Change the payout method or payment details.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherFinanceSettingsPutRequest $api_v1_publisher_finance_settings_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceSettingsPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1RetargetingPixelsIdDelete200Response|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherFinanceSettingsPutWithHttpInfo($api_v1_publisher_finance_settings_put_request, string $contentType = self::contentTypes['apiV1PublisherFinanceSettingsPut'][0])
    {
        $request = $this->apiV1PublisherFinanceSettingsPutRequest($api_v1_publisher_finance_settings_put_request, $contentType);

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
                case 400:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 401:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 403:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 404:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 413:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 422:
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
                case 400:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 401:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
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
                case 404:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 413:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 422:
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
     * Operation apiV1PublisherFinanceSettingsPutAsync
     *
     * Change the payout method or payment details.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherFinanceSettingsPutRequest $api_v1_publisher_finance_settings_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceSettingsPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherFinanceSettingsPutAsync($api_v1_publisher_finance_settings_put_request, string $contentType = self::contentTypes['apiV1PublisherFinanceSettingsPut'][0])
    {
        return $this->apiV1PublisherFinanceSettingsPutAsyncWithHttpInfo($api_v1_publisher_finance_settings_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherFinanceSettingsPutAsyncWithHttpInfo
     *
     * Change the payout method or payment details.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherFinanceSettingsPutRequest $api_v1_publisher_finance_settings_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceSettingsPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherFinanceSettingsPutAsyncWithHttpInfo($api_v1_publisher_finance_settings_put_request, string $contentType = self::contentTypes['apiV1PublisherFinanceSettingsPut'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1RetargetingPixelsIdDelete200Response';
        $request = $this->apiV1PublisherFinanceSettingsPutRequest($api_v1_publisher_finance_settings_put_request, $contentType);

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
     * Create request for operation 'apiV1PublisherFinanceSettingsPut'
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherFinanceSettingsPutRequest $api_v1_publisher_finance_settings_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceSettingsPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherFinanceSettingsPutRequest($api_v1_publisher_finance_settings_put_request, string $contentType = self::contentTypes['apiV1PublisherFinanceSettingsPut'][0])
    {

        // verify the required parameter 'api_v1_publisher_finance_settings_put_request' is set
        if ($api_v1_publisher_finance_settings_put_request === null || (is_array($api_v1_publisher_finance_settings_put_request) && count($api_v1_publisher_finance_settings_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_publisher_finance_settings_put_request when calling apiV1PublisherFinanceSettingsPut'
            );
        }


        $resourcePath = '/api/v1/publisher/finance/settings';
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
        if (isset($api_v1_publisher_finance_settings_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_publisher_finance_settings_put_request));
            } else {
                $httpBody = $api_v1_publisher_finance_settings_put_request;
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
     * Operation apiV1PublisherFinanceTransactionsGet
     *
     * List payout transactions (with USD totals).
     *
     * @param  int|null $page page (optional, default to 1)
     * @param  int|null $per_page per_page (optional, default to 20)
     * @param  string|null $sort_by sort_by (optional, default to 'id')
     * @param  string|null $sort_dir sort_dir (optional, default to 'desc')
     * @param  int|null $filter_id filter_id (optional)
     * @param  int|null $filter_ispayed filter_ispayed (optional)
     * @param  int|null $filter_transacttype filter_transacttype (optional)
     * @param  \DateTime|null $filter_dateadded filter_dateadded (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceTransactionsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherFinanceTransactionsGet200Response|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error
     */
    public function apiV1PublisherFinanceTransactionsGet($page = 1, $per_page = 20, $sort_by = 'id', $sort_dir = 'desc', $filter_id = null, $filter_ispayed = null, $filter_transacttype = null, $filter_dateadded = null, string $contentType = self::contentTypes['apiV1PublisherFinanceTransactionsGet'][0])
    {
        list($response) = $this->apiV1PublisherFinanceTransactionsGetWithHttpInfo($page, $per_page, $sort_by, $sort_dir, $filter_id, $filter_ispayed, $filter_transacttype, $filter_dateadded, $contentType);
        return $response;
    }

    /**
     * Operation apiV1PublisherFinanceTransactionsGetWithHttpInfo
     *
     * List payout transactions (with USD totals).
     *
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $filter_id (optional)
     * @param  int|null $filter_ispayed (optional)
     * @param  int|null $filter_transacttype (optional)
     * @param  \DateTime|null $filter_dateadded (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceTransactionsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherFinanceTransactionsGet200Response|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherFinanceTransactionsGetWithHttpInfo($page = 1, $per_page = 20, $sort_by = 'id', $sort_dir = 'desc', $filter_id = null, $filter_ispayed = null, $filter_transacttype = null, $filter_dateadded = null, string $contentType = self::contentTypes['apiV1PublisherFinanceTransactionsGet'][0])
    {
        $request = $this->apiV1PublisherFinanceTransactionsGetRequest($page, $per_page, $sort_by, $sort_dir, $filter_id, $filter_ispayed, $filter_transacttype, $filter_dateadded, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1PublisherFinanceTransactionsGet200Response',
                        $request,
                        $response,
                    );
                case 401:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 403:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 404:
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
                '\GoAdServerApi\Model\ApiV1PublisherFinanceTransactionsGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1PublisherFinanceTransactionsGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 401:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
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
                case 404:
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
     * Operation apiV1PublisherFinanceTransactionsGetAsync
     *
     * List payout transactions (with USD totals).
     *
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $filter_id (optional)
     * @param  int|null $filter_ispayed (optional)
     * @param  int|null $filter_transacttype (optional)
     * @param  \DateTime|null $filter_dateadded (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceTransactionsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherFinanceTransactionsGetAsync($page = 1, $per_page = 20, $sort_by = 'id', $sort_dir = 'desc', $filter_id = null, $filter_ispayed = null, $filter_transacttype = null, $filter_dateadded = null, string $contentType = self::contentTypes['apiV1PublisherFinanceTransactionsGet'][0])
    {
        return $this->apiV1PublisherFinanceTransactionsGetAsyncWithHttpInfo($page, $per_page, $sort_by, $sort_dir, $filter_id, $filter_ispayed, $filter_transacttype, $filter_dateadded, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherFinanceTransactionsGetAsyncWithHttpInfo
     *
     * List payout transactions (with USD totals).
     *
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $filter_id (optional)
     * @param  int|null $filter_ispayed (optional)
     * @param  int|null $filter_transacttype (optional)
     * @param  \DateTime|null $filter_dateadded (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceTransactionsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherFinanceTransactionsGetAsyncWithHttpInfo($page = 1, $per_page = 20, $sort_by = 'id', $sort_dir = 'desc', $filter_id = null, $filter_ispayed = null, $filter_transacttype = null, $filter_dateadded = null, string $contentType = self::contentTypes['apiV1PublisherFinanceTransactionsGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherFinanceTransactionsGet200Response';
        $request = $this->apiV1PublisherFinanceTransactionsGetRequest($page, $per_page, $sort_by, $sort_dir, $filter_id, $filter_ispayed, $filter_transacttype, $filter_dateadded, $contentType);

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
     * Create request for operation 'apiV1PublisherFinanceTransactionsGet'
     *
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $filter_id (optional)
     * @param  int|null $filter_ispayed (optional)
     * @param  int|null $filter_transacttype (optional)
     * @param  \DateTime|null $filter_dateadded (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceTransactionsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherFinanceTransactionsGetRequest($page = 1, $per_page = 20, $sort_by = 'id', $sort_dir = 'desc', $filter_id = null, $filter_ispayed = null, $filter_transacttype = null, $filter_dateadded = null, string $contentType = self::contentTypes['apiV1PublisherFinanceTransactionsGet'][0])
    {

        if ($page !== null && $page < 1) {
            throw new \InvalidArgumentException('invalid value for "$page" when calling PayoutsApi.apiV1PublisherFinanceTransactionsGet, must be bigger than or equal to 1.');
        }
        
        if ($per_page !== null && $per_page > 100) {
            throw new \InvalidArgumentException('invalid value for "$per_page" when calling PayoutsApi.apiV1PublisherFinanceTransactionsGet, must be smaller than or equal to 100.');
        }
        if ($per_page !== null && $per_page < 1) {
            throw new \InvalidArgumentException('invalid value for "$per_page" when calling PayoutsApi.apiV1PublisherFinanceTransactionsGet, must be bigger than or equal to 1.');
        }
        







        $resourcePath = '/api/v1/publisher/finance/transactions';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

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
            $filter_id,
            'filter_id', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $filter_ispayed,
            'filter_ispayed', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $filter_transacttype,
            'filter_transacttype', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $filter_dateadded,
            'filter_dateadded', // param base name
            'string', // openApiType
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
     * Operation apiV1PublisherFinanceTransactionsIdGet
     *
     * One payout transaction.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceTransactionsIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherFinanceTransactionsIdGet200Response|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error
     */
    public function apiV1PublisherFinanceTransactionsIdGet($id, string $contentType = self::contentTypes['apiV1PublisherFinanceTransactionsIdGet'][0])
    {
        list($response) = $this->apiV1PublisherFinanceTransactionsIdGetWithHttpInfo($id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1PublisherFinanceTransactionsIdGetWithHttpInfo
     *
     * One payout transaction.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceTransactionsIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherFinanceTransactionsIdGet200Response|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherFinanceTransactionsIdGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1PublisherFinanceTransactionsIdGet'][0])
    {
        $request = $this->apiV1PublisherFinanceTransactionsIdGetRequest($id, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1PublisherFinanceTransactionsIdGet200Response',
                        $request,
                        $response,
                    );
                case 401:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 403:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 404:
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
                '\GoAdServerApi\Model\ApiV1PublisherFinanceTransactionsIdGet200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1PublisherFinanceTransactionsIdGet200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 401:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
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
                case 404:
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
     * Operation apiV1PublisherFinanceTransactionsIdGetAsync
     *
     * One payout transaction.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceTransactionsIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherFinanceTransactionsIdGetAsync($id, string $contentType = self::contentTypes['apiV1PublisherFinanceTransactionsIdGet'][0])
    {
        return $this->apiV1PublisherFinanceTransactionsIdGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherFinanceTransactionsIdGetAsyncWithHttpInfo
     *
     * One payout transaction.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceTransactionsIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherFinanceTransactionsIdGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1PublisherFinanceTransactionsIdGet'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherFinanceTransactionsIdGet200Response';
        $request = $this->apiV1PublisherFinanceTransactionsIdGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1PublisherFinanceTransactionsIdGet'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceTransactionsIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherFinanceTransactionsIdGetRequest($id, string $contentType = self::contentTypes['apiV1PublisherFinanceTransactionsIdGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1PublisherFinanceTransactionsIdGet'
            );
        }


        $resourcePath = '/api/v1/publisher/finance/transactions/{id}';
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
            'GET',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Operation apiV1PublisherFinanceTransactionsIdInvoicePdfGet
     *
     * Download the self-billing invoice PDF of a paid payout.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceTransactionsIdInvoicePdfGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \SplFileObject|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error
     */
    public function apiV1PublisherFinanceTransactionsIdInvoicePdfGet($id, string $contentType = self::contentTypes['apiV1PublisherFinanceTransactionsIdInvoicePdfGet'][0])
    {
        list($response) = $this->apiV1PublisherFinanceTransactionsIdInvoicePdfGetWithHttpInfo($id, $contentType);
        return $response;
    }

    /**
     * Operation apiV1PublisherFinanceTransactionsIdInvoicePdfGetWithHttpInfo
     *
     * Download the self-billing invoice PDF of a paid payout.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceTransactionsIdInvoicePdfGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \SplFileObject|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherFinanceTransactionsIdInvoicePdfGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1PublisherFinanceTransactionsIdInvoicePdfGet'][0])
    {
        $request = $this->apiV1PublisherFinanceTransactionsIdInvoicePdfGetRequest($id, $contentType);

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
                case 401:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 403:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 404:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 500:
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
                case 401:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
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
                case 404:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 500:
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
     * Operation apiV1PublisherFinanceTransactionsIdInvoicePdfGetAsync
     *
     * Download the self-billing invoice PDF of a paid payout.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceTransactionsIdInvoicePdfGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherFinanceTransactionsIdInvoicePdfGetAsync($id, string $contentType = self::contentTypes['apiV1PublisherFinanceTransactionsIdInvoicePdfGet'][0])
    {
        return $this->apiV1PublisherFinanceTransactionsIdInvoicePdfGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherFinanceTransactionsIdInvoicePdfGetAsyncWithHttpInfo
     *
     * Download the self-billing invoice PDF of a paid payout.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceTransactionsIdInvoicePdfGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherFinanceTransactionsIdInvoicePdfGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1PublisherFinanceTransactionsIdInvoicePdfGet'][0])
    {
        $returnType = '\SplFileObject';
        $request = $this->apiV1PublisherFinanceTransactionsIdInvoicePdfGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1PublisherFinanceTransactionsIdInvoicePdfGet'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceTransactionsIdInvoicePdfGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherFinanceTransactionsIdInvoicePdfGetRequest($id, string $contentType = self::contentTypes['apiV1PublisherFinanceTransactionsIdInvoicePdfGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1PublisherFinanceTransactionsIdInvoicePdfGet'
            );
        }


        $resourcePath = '/api/v1/publisher/finance/transactions/{id}/invoice-pdf';
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
            ['application/pdf', 'application/json', ],
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
     * Operation apiV1PublisherFinanceTransferToAdvertiserPost
     *
     * Move publisher balance to the advertiser budget.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherFinanceTransferToAdvertiserPostRequest $api_v1_publisher_finance_transfer_to_advertiser_post_request api_v1_publisher_finance_transfer_to_advertiser_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceTransferToAdvertiserPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \GoAdServerApi\Model\ApiV1PublisherFinanceTransferToAdvertiserPost200Response|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error
     */
    public function apiV1PublisherFinanceTransferToAdvertiserPost($api_v1_publisher_finance_transfer_to_advertiser_post_request, string $contentType = self::contentTypes['apiV1PublisherFinanceTransferToAdvertiserPost'][0])
    {
        list($response) = $this->apiV1PublisherFinanceTransferToAdvertiserPostWithHttpInfo($api_v1_publisher_finance_transfer_to_advertiser_post_request, $contentType);
        return $response;
    }

    /**
     * Operation apiV1PublisherFinanceTransferToAdvertiserPostWithHttpInfo
     *
     * Move publisher balance to the advertiser budget.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherFinanceTransferToAdvertiserPostRequest $api_v1_publisher_finance_transfer_to_advertiser_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceTransferToAdvertiserPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \GoAdServerApi\Model\ApiV1PublisherFinanceTransferToAdvertiserPost200Response|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error|\GoAdServerApi\Model\Error, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherFinanceTransferToAdvertiserPostWithHttpInfo($api_v1_publisher_finance_transfer_to_advertiser_post_request, string $contentType = self::contentTypes['apiV1PublisherFinanceTransferToAdvertiserPost'][0])
    {
        $request = $this->apiV1PublisherFinanceTransferToAdvertiserPostRequest($api_v1_publisher_finance_transfer_to_advertiser_post_request, $contentType);

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
                        '\GoAdServerApi\Model\ApiV1PublisherFinanceTransferToAdvertiserPost200Response',
                        $request,
                        $response,
                    );
                case 400:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 401:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 403:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 404:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 409:
                    return $this->handleResponseWithDataType(
                        '\GoAdServerApi\Model\Error',
                        $request,
                        $response,
                    );
                case 422:
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
                '\GoAdServerApi\Model\ApiV1PublisherFinanceTransferToAdvertiserPost200Response',
                $request,
                $response,
            );
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\ApiV1PublisherFinanceTransferToAdvertiserPost200Response',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 400:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 401:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
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
                case 404:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 409:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\GoAdServerApi\Model\Error',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    throw $e;
                case 422:
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
     * Operation apiV1PublisherFinanceTransferToAdvertiserPostAsync
     *
     * Move publisher balance to the advertiser budget.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherFinanceTransferToAdvertiserPostRequest $api_v1_publisher_finance_transfer_to_advertiser_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceTransferToAdvertiserPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherFinanceTransferToAdvertiserPostAsync($api_v1_publisher_finance_transfer_to_advertiser_post_request, string $contentType = self::contentTypes['apiV1PublisherFinanceTransferToAdvertiserPost'][0])
    {
        return $this->apiV1PublisherFinanceTransferToAdvertiserPostAsyncWithHttpInfo($api_v1_publisher_finance_transfer_to_advertiser_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherFinanceTransferToAdvertiserPostAsyncWithHttpInfo
     *
     * Move publisher balance to the advertiser budget.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherFinanceTransferToAdvertiserPostRequest $api_v1_publisher_finance_transfer_to_advertiser_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceTransferToAdvertiserPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherFinanceTransferToAdvertiserPostAsyncWithHttpInfo($api_v1_publisher_finance_transfer_to_advertiser_post_request, string $contentType = self::contentTypes['apiV1PublisherFinanceTransferToAdvertiserPost'][0])
    {
        $returnType = '\GoAdServerApi\Model\ApiV1PublisherFinanceTransferToAdvertiserPost200Response';
        $request = $this->apiV1PublisherFinanceTransferToAdvertiserPostRequest($api_v1_publisher_finance_transfer_to_advertiser_post_request, $contentType);

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
     * Create request for operation 'apiV1PublisherFinanceTransferToAdvertiserPost'
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherFinanceTransferToAdvertiserPostRequest $api_v1_publisher_finance_transfer_to_advertiser_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherFinanceTransferToAdvertiserPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherFinanceTransferToAdvertiserPostRequest($api_v1_publisher_finance_transfer_to_advertiser_post_request, string $contentType = self::contentTypes['apiV1PublisherFinanceTransferToAdvertiserPost'][0])
    {

        // verify the required parameter 'api_v1_publisher_finance_transfer_to_advertiser_post_request' is set
        if ($api_v1_publisher_finance_transfer_to_advertiser_post_request === null || (is_array($api_v1_publisher_finance_transfer_to_advertiser_post_request) && count($api_v1_publisher_finance_transfer_to_advertiser_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_publisher_finance_transfer_to_advertiser_post_request when calling apiV1PublisherFinanceTransferToAdvertiserPost'
            );
        }


        $resourcePath = '/api/v1/publisher/finance/transfer-to-advertiser';
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
        if (isset($api_v1_publisher_finance_transfer_to_advertiser_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_publisher_finance_transfer_to_advertiser_post_request));
            } else {
                $httpBody = $api_v1_publisher_finance_transfer_to_advertiser_post_request;
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
