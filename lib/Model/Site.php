<?php
/**
 * Site
 *
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

namespace GoAdServerApi\Model;

use \ArrayAccess;
use \GoAdServerApi\ObjectSerializer;

/**
 * Site Class Doc Comment
 *
 * @category Class
 * @description Full site record returned by &#x60;GET /api/v1/sites/{id}&#x60;. Body shape is round-trippable with &#x60;SiteCreate&#x60; plus read-only fields (&#x60;id&#x60;, &#x60;domainid&#x60;, &#x60;is_verified&#x60;, &#x60;is_deleted&#x60;, &#x60;date_added&#x60;, and the review state &#x60;sitestate&#x60; / &#x60;sitestate_name&#x60; / &#x60;isvalid&#x60; / &#x60;rejectedreason&#x60;, which only staff change).
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<string, mixed>
 */
class Site implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
     * The original name of the model.
     *
     * @var string
     */
    protected static $openAPIModelName = 'Site';

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $openAPITypes = [
        'id' => 'int',
        'name' => 'string',
        'url' => 'string',
        'description' => 'string',
        'typeid' => 'int',
        'main_catid' => 'int',
        'categories' => 'int[]',
        'sitelanguages' => 'string[]',
        'postbacks' => 'mixed',
        'ad_domainid' => 'int',
        'domainid' => 'int',
        'is_verified' => 'bool',
        'is_deleted' => 'bool',
        'date_added' => '\DateTime',
        'tags' => 'int[]',
        'sitestate' => 'int',
        'sitestate_name' => 'string',
        'isvalid' => 'bool',
        'rejectedreason' => 'string',
        'isapp' => 'bool',
        'app_bundle' => 'string',
        'app_store_url' => 'string',
        'app_platform' => 'string',
        'verify_code' => 'string',
        'verify_tag_prefix' => 'string',
        'verify_meta_tag' => 'string'
    ];

    /**
     * Array of property to format mappings. Used for (de)serialization
     *
     * @var string[]
     * @phpstan-var array<string, string|null>
     * @psalm-var array<string, string|null>
     */
    protected static $openAPIFormats = [
        'id' => null,
        'name' => null,
        'url' => null,
        'description' => null,
        'typeid' => null,
        'main_catid' => null,
        'categories' => null,
        'sitelanguages' => null,
        'postbacks' => null,
        'ad_domainid' => null,
        'domainid' => null,
        'is_verified' => null,
        'is_deleted' => null,
        'date_added' => 'date',
        'tags' => null,
        'sitestate' => null,
        'sitestate_name' => null,
        'isvalid' => null,
        'rejectedreason' => null,
        'isapp' => null,
        'app_bundle' => null,
        'app_store_url' => null,
        'app_platform' => null,
        'verify_code' => null,
        'verify_tag_prefix' => null,
        'verify_meta_tag' => null
    ];

    /**
     * Array of nullable properties. Used for (de)serialization
     *
     * @var boolean[]
     */
    protected static array $openAPINullables = [
        'id' => false,
        'name' => false,
        'url' => false,
        'description' => false,
        'typeid' => false,
        'main_catid' => false,
        'categories' => false,
        'sitelanguages' => false,
        'postbacks' => true,
        'ad_domainid' => false,
        'domainid' => false,
        'is_verified' => false,
        'is_deleted' => false,
        'date_added' => false,
        'tags' => false,
        'sitestate' => false,
        'sitestate_name' => false,
        'isvalid' => false,
        'rejectedreason' => false,
        'isapp' => false,
        'app_bundle' => false,
        'app_store_url' => false,
        'app_platform' => false,
        'verify_code' => false,
        'verify_tag_prefix' => false,
        'verify_meta_tag' => false
    ];

    /**
     * If a nullable field gets set to null, insert it here
     *
     * @var boolean[]
     */
    protected array $openAPINullablesSetToNull = [];

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @return array
     */
    public static function openAPITypes()
    {
        return self::$openAPITypes;
    }

    /**
     * Array of property to format mappings. Used for (de)serialization
     *
     * @return array
     */
    public static function openAPIFormats()
    {
        return self::$openAPIFormats;
    }

    /**
     * Array of nullable properties
     *
     * @return array
     */
    protected static function openAPINullables(): array
    {
        return self::$openAPINullables;
    }

    /**
     * Array of nullable field names deliberately set to null
     *
     * @return boolean[]
     */
    private function getOpenAPINullablesSetToNull(): array
    {
        return $this->openAPINullablesSetToNull;
    }

    /**
     * Setter - Array of nullable field names deliberately set to null
     *
     * @param boolean[] $openAPINullablesSetToNull
     */
    private function setOpenAPINullablesSetToNull(array $openAPINullablesSetToNull): void
    {
        $this->openAPINullablesSetToNull = $openAPINullablesSetToNull;
    }

    /**
     * Checks if a property is nullable
     *
     * @param string $property
     * @return bool
     */
    public static function isNullable(string $property): bool
    {
        return self::openAPINullables()[$property] ?? false;
    }

    /**
     * Checks if a nullable property is set to null.
     *
     * @param string $property
     * @return bool
     */
    public function isNullableSetToNull(string $property): bool
    {
        return in_array($property, $this->getOpenAPINullablesSetToNull(), true);
    }

    /**
     * Array of attributes where the key is the local name,
     * and the value is the original name
     *
     * @var string[]
     */
    protected static $attributeMap = [
        'id' => 'id',
        'name' => 'name',
        'url' => 'url',
        'description' => 'description',
        'typeid' => 'typeid',
        'main_catid' => 'main_catid',
        'categories' => 'categories',
        'sitelanguages' => 'sitelanguages',
        'postbacks' => 'postbacks',
        'ad_domainid' => 'ad_domainid',
        'domainid' => 'domainid',
        'is_verified' => 'is_verified',
        'is_deleted' => 'is_deleted',
        'date_added' => 'date_added',
        'tags' => 'tags',
        'sitestate' => 'sitestate',
        'sitestate_name' => 'sitestate_name',
        'isvalid' => 'isvalid',
        'rejectedreason' => 'rejectedreason',
        'isapp' => 'isapp',
        'app_bundle' => 'app_bundle',
        'app_store_url' => 'app_store_url',
        'app_platform' => 'app_platform',
        'verify_code' => 'verify_code',
        'verify_tag_prefix' => 'verify_tag_prefix',
        'verify_meta_tag' => 'verify_meta_tag'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'id' => 'setId',
        'name' => 'setName',
        'url' => 'setUrl',
        'description' => 'setDescription',
        'typeid' => 'setTypeid',
        'main_catid' => 'setMainCatid',
        'categories' => 'setCategories',
        'sitelanguages' => 'setSitelanguages',
        'postbacks' => 'setPostbacks',
        'ad_domainid' => 'setAdDomainid',
        'domainid' => 'setDomainid',
        'is_verified' => 'setIsVerified',
        'is_deleted' => 'setIsDeleted',
        'date_added' => 'setDateAdded',
        'tags' => 'setTags',
        'sitestate' => 'setSitestate',
        'sitestate_name' => 'setSitestateName',
        'isvalid' => 'setIsvalid',
        'rejectedreason' => 'setRejectedreason',
        'isapp' => 'setIsapp',
        'app_bundle' => 'setAppBundle',
        'app_store_url' => 'setAppStoreUrl',
        'app_platform' => 'setAppPlatform',
        'verify_code' => 'setVerifyCode',
        'verify_tag_prefix' => 'setVerifyTagPrefix',
        'verify_meta_tag' => 'setVerifyMetaTag'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'id' => 'getId',
        'name' => 'getName',
        'url' => 'getUrl',
        'description' => 'getDescription',
        'typeid' => 'getTypeid',
        'main_catid' => 'getMainCatid',
        'categories' => 'getCategories',
        'sitelanguages' => 'getSitelanguages',
        'postbacks' => 'getPostbacks',
        'ad_domainid' => 'getAdDomainid',
        'domainid' => 'getDomainid',
        'is_verified' => 'getIsVerified',
        'is_deleted' => 'getIsDeleted',
        'date_added' => 'getDateAdded',
        'tags' => 'getTags',
        'sitestate' => 'getSitestate',
        'sitestate_name' => 'getSitestateName',
        'isvalid' => 'getIsvalid',
        'rejectedreason' => 'getRejectedreason',
        'isapp' => 'getIsapp',
        'app_bundle' => 'getAppBundle',
        'app_store_url' => 'getAppStoreUrl',
        'app_platform' => 'getAppPlatform',
        'verify_code' => 'getVerifyCode',
        'verify_tag_prefix' => 'getVerifyTagPrefix',
        'verify_meta_tag' => 'getVerifyMetaTag'
    ];

    /**
     * Array of attributes where the key is the local name,
     * and the value is the original name
     *
     * @return array
     */
    public static function attributeMap()
    {
        return self::$attributeMap;
    }

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @return array
     */
    public static function setters()
    {
        return self::$setters;
    }

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @return array
     */
    public static function getters()
    {
        return self::$getters;
    }

    /**
     * The original name of the model.
     *
     * @return string
     */
    public function getModelName()
    {
        return self::$openAPIModelName;
    }

    public const SITESTATE_MINUS_1 = -1;
    public const SITESTATE_NUMBER_0 = 0;
    public const SITESTATE_NUMBER_1 = 1;
    public const SITESTATE_NAME_VALIDATED = 'validated';
    public const SITESTATE_NAME_PENDING = 'pending';
    public const SITESTATE_NAME_REJECTED = 'rejected';
    public const APP_PLATFORM_IOS = 'ios';
    public const APP_PLATFORM_ANDROID = 'android';
    public const APP_PLATFORM_OTHER = 'other';
    public const APP_PLATFORM_EMPTY = '';

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getSitestateAllowableValues()
    {
        return [
            self::SITESTATE_MINUS_1,
            self::SITESTATE_NUMBER_0,
            self::SITESTATE_NUMBER_1,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getSitestateNameAllowableValues()
    {
        return [
            self::SITESTATE_NAME_VALIDATED,
            self::SITESTATE_NAME_PENDING,
            self::SITESTATE_NAME_REJECTED,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getAppPlatformAllowableValues()
    {
        return [
            self::APP_PLATFORM_IOS,
            self::APP_PLATFORM_ANDROID,
            self::APP_PLATFORM_OTHER,
            self::APP_PLATFORM_EMPTY,
        ];
    }

    /**
     * Associative array for storing property values
     *
     * @var mixed[]
     */
    protected $container = [];

    /**
     * Constructor
     *
     * @param mixed[]|null $data Associated array of property values
     *                      initializing the model
     */
    public function __construct(?array $data = null)
    {
        $this->setIfExists('id', $data ?? [], null);
        $this->setIfExists('name', $data ?? [], null);
        $this->setIfExists('url', $data ?? [], null);
        $this->setIfExists('description', $data ?? [], null);
        $this->setIfExists('typeid', $data ?? [], null);
        $this->setIfExists('main_catid', $data ?? [], null);
        $this->setIfExists('categories', $data ?? [], null);
        $this->setIfExists('sitelanguages', $data ?? [], null);
        $this->setIfExists('postbacks', $data ?? [], null);
        $this->setIfExists('ad_domainid', $data ?? [], null);
        $this->setIfExists('domainid', $data ?? [], null);
        $this->setIfExists('is_verified', $data ?? [], null);
        $this->setIfExists('is_deleted', $data ?? [], null);
        $this->setIfExists('date_added', $data ?? [], null);
        $this->setIfExists('tags', $data ?? [], null);
        $this->setIfExists('sitestate', $data ?? [], null);
        $this->setIfExists('sitestate_name', $data ?? [], null);
        $this->setIfExists('isvalid', $data ?? [], null);
        $this->setIfExists('rejectedreason', $data ?? [], null);
        $this->setIfExists('isapp', $data ?? [], null);
        $this->setIfExists('app_bundle', $data ?? [], null);
        $this->setIfExists('app_store_url', $data ?? [], null);
        $this->setIfExists('app_platform', $data ?? [], null);
        $this->setIfExists('verify_code', $data ?? [], null);
        $this->setIfExists('verify_tag_prefix', $data ?? [], null);
        $this->setIfExists('verify_meta_tag', $data ?? [], null);
    }

    /**
     * Sets $this->container[$variableName] to the given data or to the given default Value; if $variableName
     * is nullable and its value is set to null in the $fields array, then mark it as "set to null" in the
     * $this->openAPINullablesSetToNull array
     *
     * @param string $variableName
     * @param array  $fields
     * @param mixed  $defaultValue
     */
    private function setIfExists(string $variableName, array $fields, $defaultValue): void
    {
        if (self::isNullable($variableName) && array_key_exists($variableName, $fields) && is_null($fields[$variableName])) {
            $this->openAPINullablesSetToNull[] = $variableName;
        }

        $this->container[$variableName] = $fields[$variableName] ?? $defaultValue;
    }

    /**
     * Show all the invalid properties with reasons.
     *
     * @return array invalid properties with reasons
     */
    public function listInvalidProperties()
    {
        $invalidProperties = [];

        $allowedValues = $this->getSitestateAllowableValues();
        if (!is_null($this->container['sitestate']) && !in_array($this->container['sitestate'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'sitestate', must be one of '%s'",
                $this->container['sitestate'],
                implode("', '", $allowedValues)
            );
        }

        $allowedValues = $this->getSitestateNameAllowableValues();
        if (!is_null($this->container['sitestate_name']) && !in_array($this->container['sitestate_name'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'sitestate_name', must be one of '%s'",
                $this->container['sitestate_name'],
                implode("', '", $allowedValues)
            );
        }

        $allowedValues = $this->getAppPlatformAllowableValues();
        if (!is_null($this->container['app_platform']) && !in_array($this->container['app_platform'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'app_platform', must be one of '%s'",
                $this->container['app_platform'],
                implode("', '", $allowedValues)
            );
        }

        return $invalidProperties;
    }

    /**
     * Validate all the properties in the model
     * return true if all passed
     *
     * @return bool True if all properties are valid
     */
    public function valid()
    {
        return count($this->listInvalidProperties()) === 0;
    }


    /**
     * Gets id
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->container['id'];
    }

    /**
     * Sets id
     *
     * @param int|null $id id
     *
     * @return self
     */
    public function setId($id)
    {
        if (is_null($id)) {
            throw new \InvalidArgumentException('non-nullable id cannot be null');
        }
        $this->container['id'] = $id;

        return $this;
    }

    /**
     * Gets name
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->container['name'];
    }

    /**
     * Sets name
     *
     * @param string|null $name name
     *
     * @return self
     */
    public function setName($name)
    {
        if (is_null($name)) {
            throw new \InvalidArgumentException('non-nullable name cannot be null');
        }
        $this->container['name'] = $name;

        return $this;
    }

    /**
     * Gets url
     *
     * @return string|null
     */
    public function getUrl()
    {
        return $this->container['url'];
    }

    /**
     * Sets url
     *
     * @param string|null $url url
     *
     * @return self
     */
    public function setUrl($url)
    {
        if (is_null($url)) {
            throw new \InvalidArgumentException('non-nullable url cannot be null');
        }
        $this->container['url'] = $url;

        return $this;
    }

    /**
     * Gets description
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->container['description'];
    }

    /**
     * Sets description
     *
     * @param string|null $description description
     *
     * @return self
     */
    public function setDescription($description)
    {
        if (is_null($description)) {
            throw new \InvalidArgumentException('non-nullable description cannot be null');
        }
        $this->container['description'] = $description;

        return $this;
    }

    /**
     * Gets typeid
     *
     * @return int|null
     */
    public function getTypeid()
    {
        return $this->container['typeid'];
    }

    /**
     * Sets typeid
     *
     * @param int|null $typeid typeid
     *
     * @return self
     */
    public function setTypeid($typeid)
    {
        if (is_null($typeid)) {
            throw new \InvalidArgumentException('non-nullable typeid cannot be null');
        }
        $this->container['typeid'] = $typeid;

        return $this;
    }

    /**
     * Gets main_catid
     *
     * @return int|null
     */
    public function getMainCatid()
    {
        return $this->container['main_catid'];
    }

    /**
     * Sets main_catid
     *
     * @param int|null $main_catid main_catid
     *
     * @return self
     */
    public function setMainCatid($main_catid)
    {
        if (is_null($main_catid)) {
            throw new \InvalidArgumentException('non-nullable main_catid cannot be null');
        }
        $this->container['main_catid'] = $main_catid;

        return $this;
    }

    /**
     * Gets categories
     *
     * @return int[]|null
     */
    public function getCategories()
    {
        return $this->container['categories'];
    }

    /**
     * Sets categories
     *
     * @param int[]|null $categories categories
     *
     * @return self
     */
    public function setCategories($categories)
    {
        if (is_null($categories)) {
            throw new \InvalidArgumentException('non-nullable categories cannot be null');
        }
        $this->container['categories'] = $categories;

        return $this;
    }

    /**
     * Gets sitelanguages
     *
     * @return string[]|null
     */
    public function getSitelanguages()
    {
        return $this->container['sitelanguages'];
    }

    /**
     * Sets sitelanguages
     *
     * @param string[]|null $sitelanguages sitelanguages
     *
     * @return self
     */
    public function setSitelanguages($sitelanguages)
    {
        if (is_null($sitelanguages)) {
            throw new \InvalidArgumentException('non-nullable sitelanguages cannot be null');
        }
        $this->container['sitelanguages'] = $sitelanguages;

        return $this;
    }

    /**
     * Gets postbacks
     *
     * @return mixed|null
     */
    public function getPostbacks()
    {
        return $this->container['postbacks'];
    }

    /**
     * Sets postbacks
     *
     * @param mixed|null $postbacks postbacks
     *
     * @return self
     */
    public function setPostbacks($postbacks)
    {
        if (is_null($postbacks)) {
            array_push($this->openAPINullablesSetToNull, 'postbacks');
        } else {
            $nullablesSetToNull = $this->getOpenAPINullablesSetToNull();
            $index = array_search('postbacks', $nullablesSetToNull);
            if ($index !== FALSE) {
                unset($nullablesSetToNull[$index]);
                $this->setOpenAPINullablesSetToNull($nullablesSetToNull);
            }
        }
        $this->container['postbacks'] = $postbacks;

        return $this;
    }

    /**
     * Gets ad_domainid
     *
     * @return int|null
     */
    public function getAdDomainid()
    {
        return $this->container['ad_domainid'];
    }

    /**
     * Sets ad_domainid
     *
     * @param int|null $ad_domainid ad_domainid
     *
     * @return self
     */
    public function setAdDomainid($ad_domainid)
    {
        if (is_null($ad_domainid)) {
            throw new \InvalidArgumentException('non-nullable ad_domainid cannot be null');
        }
        $this->container['ad_domainid'] = $ad_domainid;

        return $this;
    }

    /**
     * Gets domainid
     *
     * @return int|null
     */
    public function getDomainid()
    {
        return $this->container['domainid'];
    }

    /**
     * Sets domainid
     *
     * @param int|null $domainid Resolved tracker_domains.id for the site URL.
     *
     * @return self
     */
    public function setDomainid($domainid)
    {
        if (is_null($domainid)) {
            throw new \InvalidArgumentException('non-nullable domainid cannot be null');
        }
        $this->container['domainid'] = $domainid;

        return $this;
    }

    /**
     * Gets is_verified
     *
     * @return bool|null
     */
    public function getIsVerified()
    {
        return $this->container['is_verified'];
    }

    /**
     * Sets is_verified
     *
     * @param bool|null $is_verified True after the publisher completes the verification flow.
     *
     * @return self
     */
    public function setIsVerified($is_verified)
    {
        if (is_null($is_verified)) {
            throw new \InvalidArgumentException('non-nullable is_verified cannot be null');
        }
        $this->container['is_verified'] = $is_verified;

        return $this;
    }

    /**
     * Gets is_deleted
     *
     * @return bool|null
     */
    public function getIsDeleted()
    {
        return $this->container['is_deleted'];
    }

    /**
     * Sets is_deleted
     *
     * @param bool|null $is_deleted is_deleted
     *
     * @return self
     */
    public function setIsDeleted($is_deleted)
    {
        if (is_null($is_deleted)) {
            throw new \InvalidArgumentException('non-nullable is_deleted cannot be null');
        }
        $this->container['is_deleted'] = $is_deleted;

        return $this;
    }

    /**
     * Gets date_added
     *
     * @return \DateTime|null
     */
    public function getDateAdded()
    {
        return $this->container['date_added'];
    }

    /**
     * Sets date_added
     *
     * @param \DateTime|null $date_added date_added
     *
     * @return self
     */
    public function setDateAdded($date_added)
    {
        if (is_null($date_added)) {
            throw new \InvalidArgumentException('non-nullable date_added cannot be null');
        }
        $this->container['date_added'] = $date_added;

        return $this;
    }

    /**
     * Gets tags
     *
     * @return int[]|null
     */
    public function getTags()
    {
        return $this->container['tags'];
    }

    /**
     * Sets tags
     *
     * @param int[]|null $tags Site groups (campaign_groups ids).
     *
     * @return self
     */
    public function setTags($tags)
    {
        if (is_null($tags)) {
            throw new \InvalidArgumentException('non-nullable tags cannot be null');
        }
        $this->container['tags'] = $tags;

        return $this;
    }

    /**
     * Gets sitestate
     *
     * @return int|null
     */
    public function getSitestate()
    {
        return $this->container['sitestate'];
    }

    /**
     * Sets sitestate
     *
     * @param int|null $sitestate Review state: 1 = validated, 0 = pending review, -1 = rejected.
     *
     * @return self
     */
    public function setSitestate($sitestate)
    {
        if (is_null($sitestate)) {
            throw new \InvalidArgumentException('non-nullable sitestate cannot be null');
        }
        $allowedValues = $this->getSitestateAllowableValues();
        if (!in_array($sitestate, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'sitestate', must be one of '%s'",
                    $sitestate,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['sitestate'] = $sitestate;

        return $this;
    }

    /**
     * Gets sitestate_name
     *
     * @return string|null
     */
    public function getSitestateName()
    {
        return $this->container['sitestate_name'];
    }

    /**
     * Sets sitestate_name
     *
     * @param string|null $sitestate_name sitestate_name
     *
     * @return self
     */
    public function setSitestateName($sitestate_name)
    {
        if (is_null($sitestate_name)) {
            throw new \InvalidArgumentException('non-nullable sitestate_name cannot be null');
        }
        $allowedValues = $this->getSitestateNameAllowableValues();
        if (!in_array($sitestate_name, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'sitestate_name', must be one of '%s'",
                    $sitestate_name,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['sitestate_name'] = $sitestate_name;

        return $this;
    }

    /**
     * Gets isvalid
     *
     * @return bool|null
     */
    public function getIsvalid()
    {
        return $this->container['isvalid'];
    }

    /**
     * Sets isvalid
     *
     * @param bool|null $isvalid Approved by staff (or auto-validated).
     *
     * @return self
     */
    public function setIsvalid($isvalid)
    {
        if (is_null($isvalid)) {
            throw new \InvalidArgumentException('non-nullable isvalid cannot be null');
        }
        $this->container['isvalid'] = $isvalid;

        return $this;
    }

    /**
     * Gets rejectedreason
     *
     * @return string|null
     */
    public function getRejectedreason()
    {
        return $this->container['rejectedreason'];
    }

    /**
     * Sets rejectedreason
     *
     * @param string|null $rejectedreason Why staff rejected the site (meaningful when sitestate = -1).
     *
     * @return self
     */
    public function setRejectedreason($rejectedreason)
    {
        if (is_null($rejectedreason)) {
            throw new \InvalidArgumentException('non-nullable rejectedreason cannot be null');
        }
        $this->container['rejectedreason'] = $rejectedreason;

        return $this;
    }

    /**
     * Gets isapp
     *
     * @return bool|null
     */
    public function getIsapp()
    {
        return $this->container['isapp'];
    }

    /**
     * Sets isapp
     *
     * @param bool|null $isapp A mobile app (see SiteCreate.isapp); url is then its .inapp pseudo-domain.
     *
     * @return self
     */
    public function setIsapp($isapp)
    {
        if (is_null($isapp)) {
            throw new \InvalidArgumentException('non-nullable isapp cannot be null');
        }
        $this->container['isapp'] = $isapp;

        return $this;
    }

    /**
     * Gets app_bundle
     *
     * @return string|null
     */
    public function getAppBundle()
    {
        return $this->container['app_bundle'];
    }

    /**
     * Sets app_bundle
     *
     * @param string|null $app_bundle app_bundle
     *
     * @return self
     */
    public function setAppBundle($app_bundle)
    {
        if (is_null($app_bundle)) {
            throw new \InvalidArgumentException('non-nullable app_bundle cannot be null');
        }
        $this->container['app_bundle'] = $app_bundle;

        return $this;
    }

    /**
     * Gets app_store_url
     *
     * @return string|null
     */
    public function getAppStoreUrl()
    {
        return $this->container['app_store_url'];
    }

    /**
     * Sets app_store_url
     *
     * @param string|null $app_store_url app_store_url
     *
     * @return self
     */
    public function setAppStoreUrl($app_store_url)
    {
        if (is_null($app_store_url)) {
            throw new \InvalidArgumentException('non-nullable app_store_url cannot be null');
        }
        $this->container['app_store_url'] = $app_store_url;

        return $this;
    }

    /**
     * Gets app_platform
     *
     * @return string|null
     */
    public function getAppPlatform()
    {
        return $this->container['app_platform'];
    }

    /**
     * Sets app_platform
     *
     * @param string|null $app_platform app_platform
     *
     * @return self
     */
    public function setAppPlatform($app_platform)
    {
        if (is_null($app_platform)) {
            throw new \InvalidArgumentException('non-nullable app_platform cannot be null');
        }
        $allowedValues = $this->getAppPlatformAllowableValues();
        if (!in_array($app_platform, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'app_platform', must be one of '%s'",
                    $app_platform,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['app_platform'] = $app_platform;

        return $this;
    }

    /**
     * Gets verify_code
     *
     * @return string|null
     */
    public function getVerifyCode()
    {
        return $this->container['verify_code'];
    }

    /**
     * Sets verify_code
     *
     * @param string|null $verify_code MD5 of (userid + \"_\" + url) — the unique token the publisher must include in the verify meta tag. Only present when need_verification is on.
     *
     * @return self
     */
    public function setVerifyCode($verify_code)
    {
        if (is_null($verify_code)) {
            throw new \InvalidArgumentException('non-nullable verify_code cannot be null');
        }
        $this->container['verify_code'] = $verify_code;

        return $this;
    }

    /**
     * Gets verify_tag_prefix
     *
     * @return string|null
     */
    public function getVerifyTagPrefix()
    {
        return $this->container['verify_tag_prefix'];
    }

    /**
     * Sets verify_tag_prefix
     *
     * @param string|null $verify_tag_prefix Tag-name prefix the verify meta tag uses (default \"ero_verify\"). Only present when need_verification is on.
     *
     * @return self
     */
    public function setVerifyTagPrefix($verify_tag_prefix)
    {
        if (is_null($verify_tag_prefix)) {
            throw new \InvalidArgumentException('non-nullable verify_tag_prefix cannot be null');
        }
        $this->container['verify_tag_prefix'] = $verify_tag_prefix;

        return $this;
    }

    /**
     * Gets verify_meta_tag
     *
     * @return string|null
     */
    public function getVerifyMetaTag()
    {
        return $this->container['verify_meta_tag'];
    }

    /**
     * Sets verify_meta_tag
     *
     * @param string|null $verify_meta_tag Pre-rendered <meta> tag the publisher pastes on the site root before POSTing /verify. Only present when need_verification is on.
     *
     * @return self
     */
    public function setVerifyMetaTag($verify_meta_tag)
    {
        if (is_null($verify_meta_tag)) {
            throw new \InvalidArgumentException('non-nullable verify_meta_tag cannot be null');
        }
        $this->container['verify_meta_tag'] = $verify_meta_tag;

        return $this;
    }
    /**
     * Returns true if offset exists. False otherwise.
     *
     * @param integer|string $offset Offset
     *
     * @return boolean
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->container[$offset]);
    }

    /**
     * Gets offset.
     *
     * @param integer|string $offset Offset
     *
     * @return mixed|null
     */
    #[\ReturnTypeWillChange]
    public function offsetGet(mixed $offset)
    {
        return $this->container[$offset] ?? null;
    }

    /**
     * Sets value based on offset.
     *
     * @param int|null $offset Offset
     * @param mixed    $value  Value to be set
     *
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    /**
     * Unsets offset.
     *
     * @param integer|string $offset Offset
     *
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->container[$offset]);
    }

    /**
     * Serializes the object to a value that can be serialized natively by json_encode().
     * @link https://www.php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return mixed Returns data which can be serialized by json_encode(), which is a value
     * of any type other than a resource.
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
       return ObjectSerializer::sanitizeForSerialization($this);
    }

    /**
     * Gets the string presentation of the object
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode(
            ObjectSerializer::sanitizeForSerialization($this),
            JSON_PRETTY_PRINT
        );
    }

    /**
     * Gets a header-safe presentation of the object
     *
     * @return string
     */
    public function toHeaderValue()
    {
        return json_encode(ObjectSerializer::sanitizeForSerialization($this));
    }
}


