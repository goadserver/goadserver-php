<?php
/**
 * SmartZonesApi
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
 * SmartZonesApi Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class SmartZonesApi
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
        'apiV1PublisherSmartzonesDistributionAdspacesIdGet' => [
            'application/json',
        ],
        'apiV1PublisherSmartzonesDistributionPerSiteGet' => [
            'application/json',
        ],
        'apiV1PublisherSmartzonesDistributionSitesGet' => [
            'application/json',
        ],
        'apiV1PublisherSmartzonesGet' => [
            'application/json',
        ],
        'apiV1PublisherSmartzonesIdDelete' => [
            'application/json',
        ],
        'apiV1PublisherSmartzonesIdGet' => [
            'application/json',
        ],
        'apiV1PublisherSmartzonesIdPut' => [
            'application/json',
        ],
        'apiV1PublisherSmartzonesImportPost' => [
            'application/json',
        ],
        'apiV1PublisherSmartzonesNetworksGet' => [
            'application/json',
        ],
        'apiV1PublisherSmartzonesNetworksIdDelete' => [
            'application/json',
        ],
        'apiV1PublisherSmartzonesNetworksPost' => [
            'application/json',
        ],
        'apiV1PublisherSmartzonesPost' => [
            'application/json',
        ],
        'apiV1PublisherSmartzonesSetupDataGet' => [
            'application/json',
        ],
        'apiV1PublisherSmartzonesSspCampaignsGet' => [
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
     * Operation apiV1PublisherSmartzonesDistributionAdspacesIdGet
     *
     * Network split for one ad zone.
     *
     * @param  int $id Ad zone (adspace) id. (required)
     * @param  \DateTime|null $from from (optional)
     * @param  \DateTime|null $to to (optional)
     * @param  string|null $basis basis (optional, default to 'imp')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesDistributionAdspacesIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1PublisherSmartzonesDistributionAdspacesIdGet($id, $from = null, $to = null, $basis = 'imp', string $contentType = self::contentTypes['apiV1PublisherSmartzonesDistributionAdspacesIdGet'][0])
    {
        $this->apiV1PublisherSmartzonesDistributionAdspacesIdGetWithHttpInfo($id, $from, $to, $basis, $contentType);
    }

    /**
     * Operation apiV1PublisherSmartzonesDistributionAdspacesIdGetWithHttpInfo
     *
     * Network split for one ad zone.
     *
     * @param  int $id Ad zone (adspace) id. (required)
     * @param  \DateTime|null $from (optional)
     * @param  \DateTime|null $to (optional)
     * @param  string|null $basis (optional, default to 'imp')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesDistributionAdspacesIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherSmartzonesDistributionAdspacesIdGetWithHttpInfo($id, $from = null, $to = null, $basis = 'imp', string $contentType = self::contentTypes['apiV1PublisherSmartzonesDistributionAdspacesIdGet'][0])
    {
        $request = $this->apiV1PublisherSmartzonesDistributionAdspacesIdGetRequest($id, $from, $to, $basis, $contentType);

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
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1PublisherSmartzonesDistributionAdspacesIdGetAsync
     *
     * Network split for one ad zone.
     *
     * @param  int $id Ad zone (adspace) id. (required)
     * @param  \DateTime|null $from (optional)
     * @param  \DateTime|null $to (optional)
     * @param  string|null $basis (optional, default to 'imp')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesDistributionAdspacesIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSmartzonesDistributionAdspacesIdGetAsync($id, $from = null, $to = null, $basis = 'imp', string $contentType = self::contentTypes['apiV1PublisherSmartzonesDistributionAdspacesIdGet'][0])
    {
        return $this->apiV1PublisherSmartzonesDistributionAdspacesIdGetAsyncWithHttpInfo($id, $from, $to, $basis, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherSmartzonesDistributionAdspacesIdGetAsyncWithHttpInfo
     *
     * Network split for one ad zone.
     *
     * @param  int $id Ad zone (adspace) id. (required)
     * @param  \DateTime|null $from (optional)
     * @param  \DateTime|null $to (optional)
     * @param  string|null $basis (optional, default to 'imp')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesDistributionAdspacesIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSmartzonesDistributionAdspacesIdGetAsyncWithHttpInfo($id, $from = null, $to = null, $basis = 'imp', string $contentType = self::contentTypes['apiV1PublisherSmartzonesDistributionAdspacesIdGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1PublisherSmartzonesDistributionAdspacesIdGetRequest($id, $from, $to, $basis, $contentType);

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
     * Create request for operation 'apiV1PublisherSmartzonesDistributionAdspacesIdGet'
     *
     * @param  int $id Ad zone (adspace) id. (required)
     * @param  \DateTime|null $from (optional)
     * @param  \DateTime|null $to (optional)
     * @param  string|null $basis (optional, default to 'imp')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesDistributionAdspacesIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherSmartzonesDistributionAdspacesIdGetRequest($id, $from = null, $to = null, $basis = 'imp', string $contentType = self::contentTypes['apiV1PublisherSmartzonesDistributionAdspacesIdGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1PublisherSmartzonesDistributionAdspacesIdGet'
            );
        }





        $resourcePath = '/api/v1/publisher/smartzones/distribution/adspaces/{id}';
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
            $basis,
            'basis', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);


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
     * Operation apiV1PublisherSmartzonesDistributionPerSiteGet
     *
     * Traffic and earnings split across networks for one site.
     *
     * @param  int $siteid siteid (required)
     * @param  \DateTime|null $from Inclusive start. Defaults to 6 days ago (both &#x60;from&#x60; and &#x60;to&#x60; must be sent to override). (optional)
     * @param  \DateTime|null $to Inclusive end. Defaults to today. (optional)
     * @param  string|null $basis Share basis — impressions or earnings. (optional, default to 'imp')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesDistributionPerSiteGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1PublisherSmartzonesDistributionPerSiteGet($siteid, $from = null, $to = null, $basis = 'imp', string $contentType = self::contentTypes['apiV1PublisherSmartzonesDistributionPerSiteGet'][0])
    {
        $this->apiV1PublisherSmartzonesDistributionPerSiteGetWithHttpInfo($siteid, $from, $to, $basis, $contentType);
    }

    /**
     * Operation apiV1PublisherSmartzonesDistributionPerSiteGetWithHttpInfo
     *
     * Traffic and earnings split across networks for one site.
     *
     * @param  int $siteid (required)
     * @param  \DateTime|null $from Inclusive start. Defaults to 6 days ago (both &#x60;from&#x60; and &#x60;to&#x60; must be sent to override). (optional)
     * @param  \DateTime|null $to Inclusive end. Defaults to today. (optional)
     * @param  string|null $basis Share basis — impressions or earnings. (optional, default to 'imp')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesDistributionPerSiteGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherSmartzonesDistributionPerSiteGetWithHttpInfo($siteid, $from = null, $to = null, $basis = 'imp', string $contentType = self::contentTypes['apiV1PublisherSmartzonesDistributionPerSiteGet'][0])
    {
        $request = $this->apiV1PublisherSmartzonesDistributionPerSiteGetRequest($siteid, $from, $to, $basis, $contentType);

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
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1PublisherSmartzonesDistributionPerSiteGetAsync
     *
     * Traffic and earnings split across networks for one site.
     *
     * @param  int $siteid (required)
     * @param  \DateTime|null $from Inclusive start. Defaults to 6 days ago (both &#x60;from&#x60; and &#x60;to&#x60; must be sent to override). (optional)
     * @param  \DateTime|null $to Inclusive end. Defaults to today. (optional)
     * @param  string|null $basis Share basis — impressions or earnings. (optional, default to 'imp')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesDistributionPerSiteGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSmartzonesDistributionPerSiteGetAsync($siteid, $from = null, $to = null, $basis = 'imp', string $contentType = self::contentTypes['apiV1PublisherSmartzonesDistributionPerSiteGet'][0])
    {
        return $this->apiV1PublisherSmartzonesDistributionPerSiteGetAsyncWithHttpInfo($siteid, $from, $to, $basis, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherSmartzonesDistributionPerSiteGetAsyncWithHttpInfo
     *
     * Traffic and earnings split across networks for one site.
     *
     * @param  int $siteid (required)
     * @param  \DateTime|null $from Inclusive start. Defaults to 6 days ago (both &#x60;from&#x60; and &#x60;to&#x60; must be sent to override). (optional)
     * @param  \DateTime|null $to Inclusive end. Defaults to today. (optional)
     * @param  string|null $basis Share basis — impressions or earnings. (optional, default to 'imp')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesDistributionPerSiteGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSmartzonesDistributionPerSiteGetAsyncWithHttpInfo($siteid, $from = null, $to = null, $basis = 'imp', string $contentType = self::contentTypes['apiV1PublisherSmartzonesDistributionPerSiteGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1PublisherSmartzonesDistributionPerSiteGetRequest($siteid, $from, $to, $basis, $contentType);

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
     * Create request for operation 'apiV1PublisherSmartzonesDistributionPerSiteGet'
     *
     * @param  int $siteid (required)
     * @param  \DateTime|null $from Inclusive start. Defaults to 6 days ago (both &#x60;from&#x60; and &#x60;to&#x60; must be sent to override). (optional)
     * @param  \DateTime|null $to Inclusive end. Defaults to today. (optional)
     * @param  string|null $basis Share basis — impressions or earnings. (optional, default to 'imp')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesDistributionPerSiteGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherSmartzonesDistributionPerSiteGetRequest($siteid, $from = null, $to = null, $basis = 'imp', string $contentType = self::contentTypes['apiV1PublisherSmartzonesDistributionPerSiteGet'][0])
    {

        // verify the required parameter 'siteid' is set
        if ($siteid === null || (is_array($siteid) && count($siteid) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $siteid when calling apiV1PublisherSmartzonesDistributionPerSiteGet'
            );
        }





        $resourcePath = '/api/v1/publisher/smartzones/distribution/per-site';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $siteid,
            'siteid', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            true // required
        ) ?? []);
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
            $basis,
            'basis', // param base name
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
     * Operation apiV1PublisherSmartzonesDistributionSitesGet
     *
     * Sites that carry smart zones.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesDistributionSitesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1PublisherSmartzonesDistributionSitesGet(string $contentType = self::contentTypes['apiV1PublisherSmartzonesDistributionSitesGet'][0])
    {
        $this->apiV1PublisherSmartzonesDistributionSitesGetWithHttpInfo($contentType);
    }

    /**
     * Operation apiV1PublisherSmartzonesDistributionSitesGetWithHttpInfo
     *
     * Sites that carry smart zones.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesDistributionSitesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherSmartzonesDistributionSitesGetWithHttpInfo(string $contentType = self::contentTypes['apiV1PublisherSmartzonesDistributionSitesGet'][0])
    {
        $request = $this->apiV1PublisherSmartzonesDistributionSitesGetRequest($contentType);

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
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1PublisherSmartzonesDistributionSitesGetAsync
     *
     * Sites that carry smart zones.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesDistributionSitesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSmartzonesDistributionSitesGetAsync(string $contentType = self::contentTypes['apiV1PublisherSmartzonesDistributionSitesGet'][0])
    {
        return $this->apiV1PublisherSmartzonesDistributionSitesGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherSmartzonesDistributionSitesGetAsyncWithHttpInfo
     *
     * Sites that carry smart zones.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesDistributionSitesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSmartzonesDistributionSitesGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1PublisherSmartzonesDistributionSitesGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1PublisherSmartzonesDistributionSitesGetRequest($contentType);

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
     * Create request for operation 'apiV1PublisherSmartzonesDistributionSitesGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesDistributionSitesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherSmartzonesDistributionSitesGetRequest(string $contentType = self::contentTypes['apiV1PublisherSmartzonesDistributionSitesGet'][0])
    {


        $resourcePath = '/api/v1/publisher/smartzones/distribution/sites';
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
     * Operation apiV1PublisherSmartzonesGet
     *
     * List smart zones.
     *
     * @param  int|null $page page (optional, default to 1)
     * @param  int|null $per_page per_page (optional, default to 20)
     * @param  string|null $sort_by sort_by (optional, default to 'id')
     * @param  string|null $sort_dir sort_dir (optional, default to 'desc')
     * @param  int|null $network_id Only zones on this smart network account (&#x60;smart_networkid&#x60;). (optional)
     * @param  int|null $siteid Only zones under this site. &#x60;site_id&#x60; is accepted too. (optional)
     * @param  int|null $spaceid Only zones under this ad zone. (optional)
     * @param  int|null $select_type 0 &#x3D; percentage, 1 &#x3D; eCPM, 2 &#x3D; RTB, 3 &#x3D; eCPM + limit %. (optional)
     * @param  int|null $adzone_typeid Only zones whose parent ad zone has this type id. (optional)
     * @param  string|null $status status (optional)
     * @param  int|null $filter_id filter_id (optional)
     * @param  string|null $filter_name Substring match on the zone name. (optional)
     * @param  string|null $filter_adzone_name Substring match on the parent ad zone name. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1PublisherSmartzonesGet($page = 1, $per_page = 20, $sort_by = 'id', $sort_dir = 'desc', $network_id = null, $siteid = null, $spaceid = null, $select_type = null, $adzone_typeid = null, $status = null, $filter_id = null, $filter_name = null, $filter_adzone_name = null, string $contentType = self::contentTypes['apiV1PublisherSmartzonesGet'][0])
    {
        $this->apiV1PublisherSmartzonesGetWithHttpInfo($page, $per_page, $sort_by, $sort_dir, $network_id, $siteid, $spaceid, $select_type, $adzone_typeid, $status, $filter_id, $filter_name, $filter_adzone_name, $contentType);
    }

    /**
     * Operation apiV1PublisherSmartzonesGetWithHttpInfo
     *
     * List smart zones.
     *
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $network_id Only zones on this smart network account (&#x60;smart_networkid&#x60;). (optional)
     * @param  int|null $siteid Only zones under this site. &#x60;site_id&#x60; is accepted too. (optional)
     * @param  int|null $spaceid Only zones under this ad zone. (optional)
     * @param  int|null $select_type 0 &#x3D; percentage, 1 &#x3D; eCPM, 2 &#x3D; RTB, 3 &#x3D; eCPM + limit %. (optional)
     * @param  int|null $adzone_typeid Only zones whose parent ad zone has this type id. (optional)
     * @param  string|null $status (optional)
     * @param  int|null $filter_id (optional)
     * @param  string|null $filter_name Substring match on the zone name. (optional)
     * @param  string|null $filter_adzone_name Substring match on the parent ad zone name. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherSmartzonesGetWithHttpInfo($page = 1, $per_page = 20, $sort_by = 'id', $sort_dir = 'desc', $network_id = null, $siteid = null, $spaceid = null, $select_type = null, $adzone_typeid = null, $status = null, $filter_id = null, $filter_name = null, $filter_adzone_name = null, string $contentType = self::contentTypes['apiV1PublisherSmartzonesGet'][0])
    {
        $request = $this->apiV1PublisherSmartzonesGetRequest($page, $per_page, $sort_by, $sort_dir, $network_id, $siteid, $spaceid, $select_type, $adzone_typeid, $status, $filter_id, $filter_name, $filter_adzone_name, $contentType);

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
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1PublisherSmartzonesGetAsync
     *
     * List smart zones.
     *
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $network_id Only zones on this smart network account (&#x60;smart_networkid&#x60;). (optional)
     * @param  int|null $siteid Only zones under this site. &#x60;site_id&#x60; is accepted too. (optional)
     * @param  int|null $spaceid Only zones under this ad zone. (optional)
     * @param  int|null $select_type 0 &#x3D; percentage, 1 &#x3D; eCPM, 2 &#x3D; RTB, 3 &#x3D; eCPM + limit %. (optional)
     * @param  int|null $adzone_typeid Only zones whose parent ad zone has this type id. (optional)
     * @param  string|null $status (optional)
     * @param  int|null $filter_id (optional)
     * @param  string|null $filter_name Substring match on the zone name. (optional)
     * @param  string|null $filter_adzone_name Substring match on the parent ad zone name. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSmartzonesGetAsync($page = 1, $per_page = 20, $sort_by = 'id', $sort_dir = 'desc', $network_id = null, $siteid = null, $spaceid = null, $select_type = null, $adzone_typeid = null, $status = null, $filter_id = null, $filter_name = null, $filter_adzone_name = null, string $contentType = self::contentTypes['apiV1PublisherSmartzonesGet'][0])
    {
        return $this->apiV1PublisherSmartzonesGetAsyncWithHttpInfo($page, $per_page, $sort_by, $sort_dir, $network_id, $siteid, $spaceid, $select_type, $adzone_typeid, $status, $filter_id, $filter_name, $filter_adzone_name, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherSmartzonesGetAsyncWithHttpInfo
     *
     * List smart zones.
     *
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $network_id Only zones on this smart network account (&#x60;smart_networkid&#x60;). (optional)
     * @param  int|null $siteid Only zones under this site. &#x60;site_id&#x60; is accepted too. (optional)
     * @param  int|null $spaceid Only zones under this ad zone. (optional)
     * @param  int|null $select_type 0 &#x3D; percentage, 1 &#x3D; eCPM, 2 &#x3D; RTB, 3 &#x3D; eCPM + limit %. (optional)
     * @param  int|null $adzone_typeid Only zones whose parent ad zone has this type id. (optional)
     * @param  string|null $status (optional)
     * @param  int|null $filter_id (optional)
     * @param  string|null $filter_name Substring match on the zone name. (optional)
     * @param  string|null $filter_adzone_name Substring match on the parent ad zone name. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSmartzonesGetAsyncWithHttpInfo($page = 1, $per_page = 20, $sort_by = 'id', $sort_dir = 'desc', $network_id = null, $siteid = null, $spaceid = null, $select_type = null, $adzone_typeid = null, $status = null, $filter_id = null, $filter_name = null, $filter_adzone_name = null, string $contentType = self::contentTypes['apiV1PublisherSmartzonesGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1PublisherSmartzonesGetRequest($page, $per_page, $sort_by, $sort_dir, $network_id, $siteid, $spaceid, $select_type, $adzone_typeid, $status, $filter_id, $filter_name, $filter_adzone_name, $contentType);

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
     * Create request for operation 'apiV1PublisherSmartzonesGet'
     *
     * @param  int|null $page (optional, default to 1)
     * @param  int|null $per_page (optional, default to 20)
     * @param  string|null $sort_by (optional, default to 'id')
     * @param  string|null $sort_dir (optional, default to 'desc')
     * @param  int|null $network_id Only zones on this smart network account (&#x60;smart_networkid&#x60;). (optional)
     * @param  int|null $siteid Only zones under this site. &#x60;site_id&#x60; is accepted too. (optional)
     * @param  int|null $spaceid Only zones under this ad zone. (optional)
     * @param  int|null $select_type 0 &#x3D; percentage, 1 &#x3D; eCPM, 2 &#x3D; RTB, 3 &#x3D; eCPM + limit %. (optional)
     * @param  int|null $adzone_typeid Only zones whose parent ad zone has this type id. (optional)
     * @param  string|null $status (optional)
     * @param  int|null $filter_id (optional)
     * @param  string|null $filter_name Substring match on the zone name. (optional)
     * @param  string|null $filter_adzone_name Substring match on the parent ad zone name. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherSmartzonesGetRequest($page = 1, $per_page = 20, $sort_by = 'id', $sort_dir = 'desc', $network_id = null, $siteid = null, $spaceid = null, $select_type = null, $adzone_typeid = null, $status = null, $filter_id = null, $filter_name = null, $filter_adzone_name = null, string $contentType = self::contentTypes['apiV1PublisherSmartzonesGet'][0])
    {

        if ($page !== null && $page < 1) {
            throw new \InvalidArgumentException('invalid value for "$page" when calling SmartZonesApi.apiV1PublisherSmartzonesGet, must be bigger than or equal to 1.');
        }
        
        if ($per_page !== null && $per_page > 2000) {
            throw new \InvalidArgumentException('invalid value for "$per_page" when calling SmartZonesApi.apiV1PublisherSmartzonesGet, must be smaller than or equal to 2000.');
        }
        if ($per_page !== null && $per_page < 1) {
            throw new \InvalidArgumentException('invalid value for "$per_page" when calling SmartZonesApi.apiV1PublisherSmartzonesGet, must be bigger than or equal to 1.');
        }
        












        $resourcePath = '/api/v1/publisher/smartzones';
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
            $network_id,
            'network_id', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $siteid,
            'siteid', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $spaceid,
            'spaceid', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $select_type,
            'select_type', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $adzone_typeid,
            'adzone_typeid', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $status,
            'status', // param base name
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
            $filter_name,
            'filter_name', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $filter_adzone_name,
            'filter_adzone_name', // param base name
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
     * Operation apiV1PublisherSmartzonesIdDelete
     *
     * Delete a smart zone.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1PublisherSmartzonesIdDelete($id, string $contentType = self::contentTypes['apiV1PublisherSmartzonesIdDelete'][0])
    {
        $this->apiV1PublisherSmartzonesIdDeleteWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1PublisherSmartzonesIdDeleteWithHttpInfo
     *
     * Delete a smart zone.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherSmartzonesIdDeleteWithHttpInfo($id, string $contentType = self::contentTypes['apiV1PublisherSmartzonesIdDelete'][0])
    {
        $request = $this->apiV1PublisherSmartzonesIdDeleteRequest($id, $contentType);

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
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1PublisherSmartzonesIdDeleteAsync
     *
     * Delete a smart zone.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSmartzonesIdDeleteAsync($id, string $contentType = self::contentTypes['apiV1PublisherSmartzonesIdDelete'][0])
    {
        return $this->apiV1PublisherSmartzonesIdDeleteAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherSmartzonesIdDeleteAsyncWithHttpInfo
     *
     * Delete a smart zone.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSmartzonesIdDeleteAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1PublisherSmartzonesIdDelete'][0])
    {
        $returnType = '';
        $request = $this->apiV1PublisherSmartzonesIdDeleteRequest($id, $contentType);

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
     * Create request for operation 'apiV1PublisherSmartzonesIdDelete'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherSmartzonesIdDeleteRequest($id, string $contentType = self::contentTypes['apiV1PublisherSmartzonesIdDelete'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1PublisherSmartzonesIdDelete'
            );
        }


        $resourcePath = '/api/v1/publisher/smartzones/{id}';
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
     * Operation apiV1PublisherSmartzonesIdGet
     *
     * Get one smart zone.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1PublisherSmartzonesIdGet($id, string $contentType = self::contentTypes['apiV1PublisherSmartzonesIdGet'][0])
    {
        $this->apiV1PublisherSmartzonesIdGetWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1PublisherSmartzonesIdGetWithHttpInfo
     *
     * Get one smart zone.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesIdGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherSmartzonesIdGetWithHttpInfo($id, string $contentType = self::contentTypes['apiV1PublisherSmartzonesIdGet'][0])
    {
        $request = $this->apiV1PublisherSmartzonesIdGetRequest($id, $contentType);

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
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1PublisherSmartzonesIdGetAsync
     *
     * Get one smart zone.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSmartzonesIdGetAsync($id, string $contentType = self::contentTypes['apiV1PublisherSmartzonesIdGet'][0])
    {
        return $this->apiV1PublisherSmartzonesIdGetAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherSmartzonesIdGetAsyncWithHttpInfo
     *
     * Get one smart zone.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSmartzonesIdGetAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1PublisherSmartzonesIdGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1PublisherSmartzonesIdGetRequest($id, $contentType);

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
     * Create request for operation 'apiV1PublisherSmartzonesIdGet'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesIdGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherSmartzonesIdGetRequest($id, string $contentType = self::contentTypes['apiV1PublisherSmartzonesIdGet'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1PublisherSmartzonesIdGet'
            );
        }


        $resourcePath = '/api/v1/publisher/smartzones/{id}';
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
     * Operation apiV1PublisherSmartzonesIdPut
     *
     * Update a smart zone.
     *
     * @param  int $id id (required)
     * @param  \GoAdServerApi\Model\ApiV1PublisherSmartzonesIdPutRequest $api_v1_publisher_smartzones_id_put_request api_v1_publisher_smartzones_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1PublisherSmartzonesIdPut($id, $api_v1_publisher_smartzones_id_put_request, string $contentType = self::contentTypes['apiV1PublisherSmartzonesIdPut'][0])
    {
        $this->apiV1PublisherSmartzonesIdPutWithHttpInfo($id, $api_v1_publisher_smartzones_id_put_request, $contentType);
    }

    /**
     * Operation apiV1PublisherSmartzonesIdPutWithHttpInfo
     *
     * Update a smart zone.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1PublisherSmartzonesIdPutRequest $api_v1_publisher_smartzones_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesIdPut'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherSmartzonesIdPutWithHttpInfo($id, $api_v1_publisher_smartzones_id_put_request, string $contentType = self::contentTypes['apiV1PublisherSmartzonesIdPut'][0])
    {
        $request = $this->apiV1PublisherSmartzonesIdPutRequest($id, $api_v1_publisher_smartzones_id_put_request, $contentType);

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
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1PublisherSmartzonesIdPutAsync
     *
     * Update a smart zone.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1PublisherSmartzonesIdPutRequest $api_v1_publisher_smartzones_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSmartzonesIdPutAsync($id, $api_v1_publisher_smartzones_id_put_request, string $contentType = self::contentTypes['apiV1PublisherSmartzonesIdPut'][0])
    {
        return $this->apiV1PublisherSmartzonesIdPutAsyncWithHttpInfo($id, $api_v1_publisher_smartzones_id_put_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherSmartzonesIdPutAsyncWithHttpInfo
     *
     * Update a smart zone.
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1PublisherSmartzonesIdPutRequest $api_v1_publisher_smartzones_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSmartzonesIdPutAsyncWithHttpInfo($id, $api_v1_publisher_smartzones_id_put_request, string $contentType = self::contentTypes['apiV1PublisherSmartzonesIdPut'][0])
    {
        $returnType = '';
        $request = $this->apiV1PublisherSmartzonesIdPutRequest($id, $api_v1_publisher_smartzones_id_put_request, $contentType);

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
     * Create request for operation 'apiV1PublisherSmartzonesIdPut'
     *
     * @param  int $id (required)
     * @param  \GoAdServerApi\Model\ApiV1PublisherSmartzonesIdPutRequest $api_v1_publisher_smartzones_id_put_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesIdPut'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherSmartzonesIdPutRequest($id, $api_v1_publisher_smartzones_id_put_request, string $contentType = self::contentTypes['apiV1PublisherSmartzonesIdPut'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1PublisherSmartzonesIdPut'
            );
        }

        // verify the required parameter 'api_v1_publisher_smartzones_id_put_request' is set
        if ($api_v1_publisher_smartzones_id_put_request === null || (is_array($api_v1_publisher_smartzones_id_put_request) && count($api_v1_publisher_smartzones_id_put_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_publisher_smartzones_id_put_request when calling apiV1PublisherSmartzonesIdPut'
            );
        }


        $resourcePath = '/api/v1/publisher/smartzones/{id}';
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
        if (isset($api_v1_publisher_smartzones_id_put_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_publisher_smartzones_id_put_request));
            } else {
                $httpBody = $api_v1_publisher_smartzones_id_put_request;
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
     * Operation apiV1PublisherSmartzonesImportPost
     *
     * Bulk import smart zones.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherSmartzonesImportPostRequest $api_v1_publisher_smartzones_import_post_request api_v1_publisher_smartzones_import_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesImportPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1PublisherSmartzonesImportPost($api_v1_publisher_smartzones_import_post_request, string $contentType = self::contentTypes['apiV1PublisherSmartzonesImportPost'][0])
    {
        $this->apiV1PublisherSmartzonesImportPostWithHttpInfo($api_v1_publisher_smartzones_import_post_request, $contentType);
    }

    /**
     * Operation apiV1PublisherSmartzonesImportPostWithHttpInfo
     *
     * Bulk import smart zones.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherSmartzonesImportPostRequest $api_v1_publisher_smartzones_import_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesImportPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherSmartzonesImportPostWithHttpInfo($api_v1_publisher_smartzones_import_post_request, string $contentType = self::contentTypes['apiV1PublisherSmartzonesImportPost'][0])
    {
        $request = $this->apiV1PublisherSmartzonesImportPostRequest($api_v1_publisher_smartzones_import_post_request, $contentType);

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
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1PublisherSmartzonesImportPostAsync
     *
     * Bulk import smart zones.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherSmartzonesImportPostRequest $api_v1_publisher_smartzones_import_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesImportPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSmartzonesImportPostAsync($api_v1_publisher_smartzones_import_post_request, string $contentType = self::contentTypes['apiV1PublisherSmartzonesImportPost'][0])
    {
        return $this->apiV1PublisherSmartzonesImportPostAsyncWithHttpInfo($api_v1_publisher_smartzones_import_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherSmartzonesImportPostAsyncWithHttpInfo
     *
     * Bulk import smart zones.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherSmartzonesImportPostRequest $api_v1_publisher_smartzones_import_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesImportPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSmartzonesImportPostAsyncWithHttpInfo($api_v1_publisher_smartzones_import_post_request, string $contentType = self::contentTypes['apiV1PublisherSmartzonesImportPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1PublisherSmartzonesImportPostRequest($api_v1_publisher_smartzones_import_post_request, $contentType);

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
     * Create request for operation 'apiV1PublisherSmartzonesImportPost'
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherSmartzonesImportPostRequest $api_v1_publisher_smartzones_import_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesImportPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherSmartzonesImportPostRequest($api_v1_publisher_smartzones_import_post_request, string $contentType = self::contentTypes['apiV1PublisherSmartzonesImportPost'][0])
    {

        // verify the required parameter 'api_v1_publisher_smartzones_import_post_request' is set
        if ($api_v1_publisher_smartzones_import_post_request === null || (is_array($api_v1_publisher_smartzones_import_post_request) && count($api_v1_publisher_smartzones_import_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_publisher_smartzones_import_post_request when calling apiV1PublisherSmartzonesImportPost'
            );
        }


        $resourcePath = '/api/v1/publisher/smartzones/import';
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
        if (isset($api_v1_publisher_smartzones_import_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_publisher_smartzones_import_post_request));
            } else {
                $httpBody = $api_v1_publisher_smartzones_import_post_request;
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
     * Operation apiV1PublisherSmartzonesNetworksGet
     *
     * List your smart network accounts.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesNetworksGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1PublisherSmartzonesNetworksGet(string $contentType = self::contentTypes['apiV1PublisherSmartzonesNetworksGet'][0])
    {
        $this->apiV1PublisherSmartzonesNetworksGetWithHttpInfo($contentType);
    }

    /**
     * Operation apiV1PublisherSmartzonesNetworksGetWithHttpInfo
     *
     * List your smart network accounts.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesNetworksGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherSmartzonesNetworksGetWithHttpInfo(string $contentType = self::contentTypes['apiV1PublisherSmartzonesNetworksGet'][0])
    {
        $request = $this->apiV1PublisherSmartzonesNetworksGetRequest($contentType);

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
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1PublisherSmartzonesNetworksGetAsync
     *
     * List your smart network accounts.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesNetworksGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSmartzonesNetworksGetAsync(string $contentType = self::contentTypes['apiV1PublisherSmartzonesNetworksGet'][0])
    {
        return $this->apiV1PublisherSmartzonesNetworksGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherSmartzonesNetworksGetAsyncWithHttpInfo
     *
     * List your smart network accounts.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesNetworksGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSmartzonesNetworksGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1PublisherSmartzonesNetworksGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1PublisherSmartzonesNetworksGetRequest($contentType);

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
     * Create request for operation 'apiV1PublisherSmartzonesNetworksGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesNetworksGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherSmartzonesNetworksGetRequest(string $contentType = self::contentTypes['apiV1PublisherSmartzonesNetworksGet'][0])
    {


        $resourcePath = '/api/v1/publisher/smartzones/networks';
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
     * Operation apiV1PublisherSmartzonesNetworksIdDelete
     *
     * Remove a smart network account.
     *
     * @param  int $id id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesNetworksIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1PublisherSmartzonesNetworksIdDelete($id, string $contentType = self::contentTypes['apiV1PublisherSmartzonesNetworksIdDelete'][0])
    {
        $this->apiV1PublisherSmartzonesNetworksIdDeleteWithHttpInfo($id, $contentType);
    }

    /**
     * Operation apiV1PublisherSmartzonesNetworksIdDeleteWithHttpInfo
     *
     * Remove a smart network account.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesNetworksIdDelete'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherSmartzonesNetworksIdDeleteWithHttpInfo($id, string $contentType = self::contentTypes['apiV1PublisherSmartzonesNetworksIdDelete'][0])
    {
        $request = $this->apiV1PublisherSmartzonesNetworksIdDeleteRequest($id, $contentType);

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
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1PublisherSmartzonesNetworksIdDeleteAsync
     *
     * Remove a smart network account.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesNetworksIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSmartzonesNetworksIdDeleteAsync($id, string $contentType = self::contentTypes['apiV1PublisherSmartzonesNetworksIdDelete'][0])
    {
        return $this->apiV1PublisherSmartzonesNetworksIdDeleteAsyncWithHttpInfo($id, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherSmartzonesNetworksIdDeleteAsyncWithHttpInfo
     *
     * Remove a smart network account.
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesNetworksIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSmartzonesNetworksIdDeleteAsyncWithHttpInfo($id, string $contentType = self::contentTypes['apiV1PublisherSmartzonesNetworksIdDelete'][0])
    {
        $returnType = '';
        $request = $this->apiV1PublisherSmartzonesNetworksIdDeleteRequest($id, $contentType);

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
     * Create request for operation 'apiV1PublisherSmartzonesNetworksIdDelete'
     *
     * @param  int $id (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesNetworksIdDelete'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherSmartzonesNetworksIdDeleteRequest($id, string $contentType = self::contentTypes['apiV1PublisherSmartzonesNetworksIdDelete'][0])
    {

        // verify the required parameter 'id' is set
        if ($id === null || (is_array($id) && count($id) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $id when calling apiV1PublisherSmartzonesNetworksIdDelete'
            );
        }


        $resourcePath = '/api/v1/publisher/smartzones/networks/{id}';
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
     * Operation apiV1PublisherSmartzonesNetworksPost
     *
     * Add or update a smart network account.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherSmartzonesNetworksPostRequest $api_v1_publisher_smartzones_networks_post_request api_v1_publisher_smartzones_networks_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesNetworksPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1PublisherSmartzonesNetworksPost($api_v1_publisher_smartzones_networks_post_request, string $contentType = self::contentTypes['apiV1PublisherSmartzonesNetworksPost'][0])
    {
        $this->apiV1PublisherSmartzonesNetworksPostWithHttpInfo($api_v1_publisher_smartzones_networks_post_request, $contentType);
    }

    /**
     * Operation apiV1PublisherSmartzonesNetworksPostWithHttpInfo
     *
     * Add or update a smart network account.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherSmartzonesNetworksPostRequest $api_v1_publisher_smartzones_networks_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesNetworksPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherSmartzonesNetworksPostWithHttpInfo($api_v1_publisher_smartzones_networks_post_request, string $contentType = self::contentTypes['apiV1PublisherSmartzonesNetworksPost'][0])
    {
        $request = $this->apiV1PublisherSmartzonesNetworksPostRequest($api_v1_publisher_smartzones_networks_post_request, $contentType);

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
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1PublisherSmartzonesNetworksPostAsync
     *
     * Add or update a smart network account.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherSmartzonesNetworksPostRequest $api_v1_publisher_smartzones_networks_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesNetworksPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSmartzonesNetworksPostAsync($api_v1_publisher_smartzones_networks_post_request, string $contentType = self::contentTypes['apiV1PublisherSmartzonesNetworksPost'][0])
    {
        return $this->apiV1PublisherSmartzonesNetworksPostAsyncWithHttpInfo($api_v1_publisher_smartzones_networks_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherSmartzonesNetworksPostAsyncWithHttpInfo
     *
     * Add or update a smart network account.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherSmartzonesNetworksPostRequest $api_v1_publisher_smartzones_networks_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesNetworksPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSmartzonesNetworksPostAsyncWithHttpInfo($api_v1_publisher_smartzones_networks_post_request, string $contentType = self::contentTypes['apiV1PublisherSmartzonesNetworksPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1PublisherSmartzonesNetworksPostRequest($api_v1_publisher_smartzones_networks_post_request, $contentType);

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
     * Create request for operation 'apiV1PublisherSmartzonesNetworksPost'
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherSmartzonesNetworksPostRequest $api_v1_publisher_smartzones_networks_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesNetworksPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherSmartzonesNetworksPostRequest($api_v1_publisher_smartzones_networks_post_request, string $contentType = self::contentTypes['apiV1PublisherSmartzonesNetworksPost'][0])
    {

        // verify the required parameter 'api_v1_publisher_smartzones_networks_post_request' is set
        if ($api_v1_publisher_smartzones_networks_post_request === null || (is_array($api_v1_publisher_smartzones_networks_post_request) && count($api_v1_publisher_smartzones_networks_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_publisher_smartzones_networks_post_request when calling apiV1PublisherSmartzonesNetworksPost'
            );
        }


        $resourcePath = '/api/v1/publisher/smartzones/networks';
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
        if (isset($api_v1_publisher_smartzones_networks_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_publisher_smartzones_networks_post_request));
            } else {
                $httpBody = $api_v1_publisher_smartzones_networks_post_request;
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
     * Operation apiV1PublisherSmartzonesPost
     *
     * Create a smart zone.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherSmartzonesPostRequest $api_v1_publisher_smartzones_post_request api_v1_publisher_smartzones_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1PublisherSmartzonesPost($api_v1_publisher_smartzones_post_request, string $contentType = self::contentTypes['apiV1PublisherSmartzonesPost'][0])
    {
        $this->apiV1PublisherSmartzonesPostWithHttpInfo($api_v1_publisher_smartzones_post_request, $contentType);
    }

    /**
     * Operation apiV1PublisherSmartzonesPostWithHttpInfo
     *
     * Create a smart zone.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherSmartzonesPostRequest $api_v1_publisher_smartzones_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesPost'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherSmartzonesPostWithHttpInfo($api_v1_publisher_smartzones_post_request, string $contentType = self::contentTypes['apiV1PublisherSmartzonesPost'][0])
    {
        $request = $this->apiV1PublisherSmartzonesPostRequest($api_v1_publisher_smartzones_post_request, $contentType);

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
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1PublisherSmartzonesPostAsync
     *
     * Create a smart zone.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherSmartzonesPostRequest $api_v1_publisher_smartzones_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSmartzonesPostAsync($api_v1_publisher_smartzones_post_request, string $contentType = self::contentTypes['apiV1PublisherSmartzonesPost'][0])
    {
        return $this->apiV1PublisherSmartzonesPostAsyncWithHttpInfo($api_v1_publisher_smartzones_post_request, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherSmartzonesPostAsyncWithHttpInfo
     *
     * Create a smart zone.
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherSmartzonesPostRequest $api_v1_publisher_smartzones_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSmartzonesPostAsyncWithHttpInfo($api_v1_publisher_smartzones_post_request, string $contentType = self::contentTypes['apiV1PublisherSmartzonesPost'][0])
    {
        $returnType = '';
        $request = $this->apiV1PublisherSmartzonesPostRequest($api_v1_publisher_smartzones_post_request, $contentType);

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
     * Create request for operation 'apiV1PublisherSmartzonesPost'
     *
     * @param  \GoAdServerApi\Model\ApiV1PublisherSmartzonesPostRequest $api_v1_publisher_smartzones_post_request (required)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesPost'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherSmartzonesPostRequest($api_v1_publisher_smartzones_post_request, string $contentType = self::contentTypes['apiV1PublisherSmartzonesPost'][0])
    {

        // verify the required parameter 'api_v1_publisher_smartzones_post_request' is set
        if ($api_v1_publisher_smartzones_post_request === null || (is_array($api_v1_publisher_smartzones_post_request) && count($api_v1_publisher_smartzones_post_request) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $api_v1_publisher_smartzones_post_request when calling apiV1PublisherSmartzonesPost'
            );
        }


        $resourcePath = '/api/v1/publisher/smartzones';
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
        if (isset($api_v1_publisher_smartzones_post_request)) {
            if (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the body
                $httpBody = \GuzzleHttp\Utils::jsonEncode(ObjectSerializer::sanitizeForSerialization($api_v1_publisher_smartzones_post_request));
            } else {
                $httpBody = $api_v1_publisher_smartzones_post_request;
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
     * Operation apiV1PublisherSmartzonesSetupDataGet
     *
     * Lookups for the smart-zone form.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesSetupDataGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1PublisherSmartzonesSetupDataGet(string $contentType = self::contentTypes['apiV1PublisherSmartzonesSetupDataGet'][0])
    {
        $this->apiV1PublisherSmartzonesSetupDataGetWithHttpInfo($contentType);
    }

    /**
     * Operation apiV1PublisherSmartzonesSetupDataGetWithHttpInfo
     *
     * Lookups for the smart-zone form.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesSetupDataGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherSmartzonesSetupDataGetWithHttpInfo(string $contentType = self::contentTypes['apiV1PublisherSmartzonesSetupDataGet'][0])
    {
        $request = $this->apiV1PublisherSmartzonesSetupDataGetRequest($contentType);

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
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1PublisherSmartzonesSetupDataGetAsync
     *
     * Lookups for the smart-zone form.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesSetupDataGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSmartzonesSetupDataGetAsync(string $contentType = self::contentTypes['apiV1PublisherSmartzonesSetupDataGet'][0])
    {
        return $this->apiV1PublisherSmartzonesSetupDataGetAsyncWithHttpInfo($contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherSmartzonesSetupDataGetAsyncWithHttpInfo
     *
     * Lookups for the smart-zone form.
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesSetupDataGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSmartzonesSetupDataGetAsyncWithHttpInfo(string $contentType = self::contentTypes['apiV1PublisherSmartzonesSetupDataGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1PublisherSmartzonesSetupDataGetRequest($contentType);

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
     * Create request for operation 'apiV1PublisherSmartzonesSetupDataGet'
     *
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesSetupDataGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherSmartzonesSetupDataGetRequest(string $contentType = self::contentTypes['apiV1PublisherSmartzonesSetupDataGet'][0])
    {


        $resourcePath = '/api/v1/publisher/smartzones/setup-data';
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
     * Operation apiV1PublisherSmartzonesSspCampaignsGet
     *
     * SSP campaigns usable as a smart zone&#39;s RTB feed.
     *
     * @param  int $networkid Catalogue network id (setup-data &#x60;smartzone_networks[].id&#x60;). (required)
     * @param  int|null $adtypeid Ad type id of the parent ad zone. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesSspCampaignsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return void
     */
    public function apiV1PublisherSmartzonesSspCampaignsGet($networkid, $adtypeid = null, string $contentType = self::contentTypes['apiV1PublisherSmartzonesSspCampaignsGet'][0])
    {
        $this->apiV1PublisherSmartzonesSspCampaignsGetWithHttpInfo($networkid, $adtypeid, $contentType);
    }

    /**
     * Operation apiV1PublisherSmartzonesSspCampaignsGetWithHttpInfo
     *
     * SSP campaigns usable as a smart zone&#39;s RTB feed.
     *
     * @param  int $networkid Catalogue network id (setup-data &#x60;smartzone_networks[].id&#x60;). (required)
     * @param  int|null $adtypeid Ad type id of the parent ad zone. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesSspCampaignsGet'] to see the possible values for this operation
     *
     * @throws \GoAdServerApi\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of null, HTTP status code, HTTP response headers (array of strings)
     */
    public function apiV1PublisherSmartzonesSspCampaignsGetWithHttpInfo($networkid, $adtypeid = null, string $contentType = self::contentTypes['apiV1PublisherSmartzonesSspCampaignsGet'][0])
    {
        $request = $this->apiV1PublisherSmartzonesSspCampaignsGetRequest($networkid, $adtypeid, $contentType);

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
            }
        

            throw $e;
        }
    }

    /**
     * Operation apiV1PublisherSmartzonesSspCampaignsGetAsync
     *
     * SSP campaigns usable as a smart zone&#39;s RTB feed.
     *
     * @param  int $networkid Catalogue network id (setup-data &#x60;smartzone_networks[].id&#x60;). (required)
     * @param  int|null $adtypeid Ad type id of the parent ad zone. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesSspCampaignsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSmartzonesSspCampaignsGetAsync($networkid, $adtypeid = null, string $contentType = self::contentTypes['apiV1PublisherSmartzonesSspCampaignsGet'][0])
    {
        return $this->apiV1PublisherSmartzonesSspCampaignsGetAsyncWithHttpInfo($networkid, $adtypeid, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation apiV1PublisherSmartzonesSspCampaignsGetAsyncWithHttpInfo
     *
     * SSP campaigns usable as a smart zone&#39;s RTB feed.
     *
     * @param  int $networkid Catalogue network id (setup-data &#x60;smartzone_networks[].id&#x60;). (required)
     * @param  int|null $adtypeid Ad type id of the parent ad zone. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesSspCampaignsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function apiV1PublisherSmartzonesSspCampaignsGetAsyncWithHttpInfo($networkid, $adtypeid = null, string $contentType = self::contentTypes['apiV1PublisherSmartzonesSspCampaignsGet'][0])
    {
        $returnType = '';
        $request = $this->apiV1PublisherSmartzonesSspCampaignsGetRequest($networkid, $adtypeid, $contentType);

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
     * Create request for operation 'apiV1PublisherSmartzonesSspCampaignsGet'
     *
     * @param  int $networkid Catalogue network id (setup-data &#x60;smartzone_networks[].id&#x60;). (required)
     * @param  int|null $adtypeid Ad type id of the parent ad zone. (optional)
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['apiV1PublisherSmartzonesSspCampaignsGet'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function apiV1PublisherSmartzonesSspCampaignsGetRequest($networkid, $adtypeid = null, string $contentType = self::contentTypes['apiV1PublisherSmartzonesSspCampaignsGet'][0])
    {

        // verify the required parameter 'networkid' is set
        if ($networkid === null || (is_array($networkid) && count($networkid) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $networkid when calling apiV1PublisherSmartzonesSspCampaignsGet'
            );
        }



        $resourcePath = '/api/v1/publisher/smartzones/ssp-campaigns';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $networkid,
            'networkid', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            true // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $adtypeid,
            'adtypeid', // param base name
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
