<?php
/**
 * ApiV1ProductFeedsPostRequest
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
 * ApiV1ProductFeedsPostRequest Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<string, mixed>
 */
class ApiV1ProductFeedsPostRequest implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
     * The original name of the model.
     *
     * @var string
     */
    protected static $openAPIModelName = '_api_v1_product_feeds_post_request';

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $openAPITypes = [
        'campaign_id' => 'int',
        'url' => 'string',
        'name' => 'string',
        'format' => 'string',
        'refresh_hours' => 'int',
        'max_items' => 'int',
        'price_in_title' => 'bool',
        'bid' => 'float',
        'template' => 'string',
        'template_html' => 'string',
        'cta' => 'string',
        'accent' => 'string',
        'bansizeid' => 'int',
        'retarget' => 'bool'
    ];

    /**
     * Array of property to format mappings. Used for (de)serialization
     *
     * @var string[]
     * @phpstan-var array<string, string|null>
     * @psalm-var array<string, string|null>
     */
    protected static $openAPIFormats = [
        'campaign_id' => null,
        'url' => null,
        'name' => null,
        'format' => null,
        'refresh_hours' => null,
        'max_items' => null,
        'price_in_title' => null,
        'bid' => null,
        'template' => null,
        'template_html' => null,
        'cta' => null,
        'accent' => null,
        'bansizeid' => null,
        'retarget' => null
    ];

    /**
     * Array of nullable properties. Used for (de)serialization
     *
     * @var boolean[]
     */
    protected static array $openAPINullables = [
        'campaign_id' => false,
        'url' => false,
        'name' => false,
        'format' => false,
        'refresh_hours' => false,
        'max_items' => false,
        'price_in_title' => false,
        'bid' => false,
        'template' => false,
        'template_html' => false,
        'cta' => false,
        'accent' => false,
        'bansizeid' => false,
        'retarget' => false
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
        'campaign_id' => 'campaign_id',
        'url' => 'url',
        'name' => 'name',
        'format' => 'format',
        'refresh_hours' => 'refresh_hours',
        'max_items' => 'max_items',
        'price_in_title' => 'price_in_title',
        'bid' => 'bid',
        'template' => 'template',
        'template_html' => 'template_html',
        'cta' => 'cta',
        'accent' => 'accent',
        'bansizeid' => 'bansizeid',
        'retarget' => 'retarget'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'campaign_id' => 'setCampaignId',
        'url' => 'setUrl',
        'name' => 'setName',
        'format' => 'setFormat',
        'refresh_hours' => 'setRefreshHours',
        'max_items' => 'setMaxItems',
        'price_in_title' => 'setPriceInTitle',
        'bid' => 'setBid',
        'template' => 'setTemplate',
        'template_html' => 'setTemplateHtml',
        'cta' => 'setCta',
        'accent' => 'setAccent',
        'bansizeid' => 'setBansizeid',
        'retarget' => 'setRetarget'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'campaign_id' => 'getCampaignId',
        'url' => 'getUrl',
        'name' => 'getName',
        'format' => 'getFormat',
        'refresh_hours' => 'getRefreshHours',
        'max_items' => 'getMaxItems',
        'price_in_title' => 'getPriceInTitle',
        'bid' => 'getBid',
        'template' => 'getTemplate',
        'template_html' => 'getTemplateHtml',
        'cta' => 'getCta',
        'accent' => 'getAccent',
        'bansizeid' => 'getBansizeid',
        'retarget' => 'getRetarget'
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

    public const FORMAT_AUTO = 'auto';
    public const FORMAT_XML = 'xml';
    public const FORMAT_CSV = 'csv';
    public const REFRESH_HOURS_NUMBER_0 = 0;
    public const REFRESH_HOURS_NUMBER_1 = 1;
    public const REFRESH_HOURS_NUMBER_6 = 6;
    public const REFRESH_HOURS_NUMBER_12 = 12;
    public const REFRESH_HOURS_NUMBER_24 = 24;
    public const TEMPLATE_NATIVE = 'native';
    public const TEMPLATE_CARD = 'card';
    public const TEMPLATE_OVERLAY = 'overlay';
    public const TEMPLATE_MINIMAL = 'minimal';
    public const TEMPLATE_CUSTOM = 'custom';

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getFormatAllowableValues()
    {
        return [
            self::FORMAT_AUTO,
            self::FORMAT_XML,
            self::FORMAT_CSV,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getRefreshHoursAllowableValues()
    {
        return [
            self::REFRESH_HOURS_NUMBER_0,
            self::REFRESH_HOURS_NUMBER_1,
            self::REFRESH_HOURS_NUMBER_6,
            self::REFRESH_HOURS_NUMBER_12,
            self::REFRESH_HOURS_NUMBER_24,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getTemplateAllowableValues()
    {
        return [
            self::TEMPLATE_NATIVE,
            self::TEMPLATE_CARD,
            self::TEMPLATE_OVERLAY,
            self::TEMPLATE_MINIMAL,
            self::TEMPLATE_CUSTOM,
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
        $this->setIfExists('campaign_id', $data ?? [], null);
        $this->setIfExists('url', $data ?? [], null);
        $this->setIfExists('name', $data ?? [], null);
        $this->setIfExists('format', $data ?? [], 'auto');
        $this->setIfExists('refresh_hours', $data ?? [], null);
        $this->setIfExists('max_items', $data ?? [], null);
        $this->setIfExists('price_in_title', $data ?? [], null);
        $this->setIfExists('bid', $data ?? [], null);
        $this->setIfExists('template', $data ?? [], null);
        $this->setIfExists('template_html', $data ?? [], null);
        $this->setIfExists('cta', $data ?? [], null);
        $this->setIfExists('accent', $data ?? [], null);
        $this->setIfExists('bansizeid', $data ?? [], null);
        $this->setIfExists('retarget', $data ?? [], null);
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

        if ($this->container['campaign_id'] === null) {
            $invalidProperties[] = "'campaign_id' can't be null";
        }
        if ($this->container['url'] === null) {
            $invalidProperties[] = "'url' can't be null";
        }
        $allowedValues = $this->getFormatAllowableValues();
        if (!is_null($this->container['format']) && !in_array($this->container['format'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'format', must be one of '%s'",
                $this->container['format'],
                implode("', '", $allowedValues)
            );
        }

        $allowedValues = $this->getRefreshHoursAllowableValues();
        if (!is_null($this->container['refresh_hours']) && !in_array($this->container['refresh_hours'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'refresh_hours', must be one of '%s'",
                $this->container['refresh_hours'],
                implode("', '", $allowedValues)
            );
        }

        if (!is_null($this->container['max_items']) && ($this->container['max_items'] > 5000)) {
            $invalidProperties[] = "invalid value for 'max_items', must be smaller than or equal to 5000.";
        }

        if (!is_null($this->container['max_items']) && ($this->container['max_items'] < 1)) {
            $invalidProperties[] = "invalid value for 'max_items', must be bigger than or equal to 1.";
        }

        $allowedValues = $this->getTemplateAllowableValues();
        if (!is_null($this->container['template']) && !in_array($this->container['template'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'template', must be one of '%s'",
                $this->container['template'],
                implode("', '", $allowedValues)
            );
        }

        if (!is_null($this->container['template_html']) && (mb_strlen($this->container['template_html']) > 20000)) {
            $invalidProperties[] = "invalid value for 'template_html', the character length must be smaller than or equal to 20000.";
        }

        if (!is_null($this->container['cta']) && (mb_strlen($this->container['cta']) > 30)) {
            $invalidProperties[] = "invalid value for 'cta', the character length must be smaller than or equal to 30.";
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
     * Gets campaign_id
     *
     * @return int
     */
    public function getCampaignId()
    {
        return $this->container['campaign_id'];
    }

    /**
     * Sets campaign_id
     *
     * @param int $campaign_id One of your RON campaigns (native 3, Search Ads 42 or banner 2).
     *
     * @return self
     */
    public function setCampaignId($campaign_id)
    {
        if (is_null($campaign_id)) {
            throw new \InvalidArgumentException('non-nullable campaign_id cannot be null');
        }
        $this->container['campaign_id'] = $campaign_id;

        return $this;
    }

    /**
     * Gets url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->container['url'];
    }

    /**
     * Sets url
     *
     * @param string $url Feed URL, http(s), public host, at most 20 MB.
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
     * Gets format
     *
     * @return string|null
     */
    public function getFormat()
    {
        return $this->container['format'];
    }

    /**
     * Sets format
     *
     * @param string|null $format format
     *
     * @return self
     */
    public function setFormat($format)
    {
        if (is_null($format)) {
            throw new \InvalidArgumentException('non-nullable format cannot be null');
        }
        $allowedValues = $this->getFormatAllowableValues();
        if (!in_array($format, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'format', must be one of '%s'",
                    $format,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['format'] = $format;

        return $this;
    }

    /**
     * Gets refresh_hours
     *
     * @return int|null
     */
    public function getRefreshHours()
    {
        return $this->container['refresh_hours'];
    }

    /**
     * Sets refresh_hours
     *
     * @param int|null $refresh_hours 0 = manual.
     *
     * @return self
     */
    public function setRefreshHours($refresh_hours)
    {
        if (is_null($refresh_hours)) {
            throw new \InvalidArgumentException('non-nullable refresh_hours cannot be null');
        }
        $allowedValues = $this->getRefreshHoursAllowableValues();
        if (!in_array($refresh_hours, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'refresh_hours', must be one of '%s'",
                    $refresh_hours,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['refresh_hours'] = $refresh_hours;

        return $this;
    }

    /**
     * Gets max_items
     *
     * @return int|null
     */
    public function getMaxItems()
    {
        return $this->container['max_items'];
    }

    /**
     * Sets max_items
     *
     * @param int|null $max_items max_items
     *
     * @return self
     */
    public function setMaxItems($max_items)
    {
        if (is_null($max_items)) {
            throw new \InvalidArgumentException('non-nullable max_items cannot be null');
        }

        if (($max_items > 5000)) {
            throw new \InvalidArgumentException('invalid value for $max_items when calling ApiV1ProductFeedsPostRequest., must be smaller than or equal to 5000.');
        }
        if (($max_items < 1)) {
            throw new \InvalidArgumentException('invalid value for $max_items when calling ApiV1ProductFeedsPostRequest., must be bigger than or equal to 1.');
        }

        $this->container['max_items'] = $max_items;

        return $this;
    }

    /**
     * Gets price_in_title
     *
     * @return bool|null
     */
    public function getPriceInTitle()
    {
        return $this->container['price_in_title'];
    }

    /**
     * Sets price_in_title
     *
     * @param bool|null $price_in_title price_in_title
     *
     * @return self
     */
    public function setPriceInTitle($price_in_title)
    {
        if (is_null($price_in_title)) {
            throw new \InvalidArgumentException('non-nullable price_in_title cannot be null');
        }
        $this->container['price_in_title'] = $price_in_title;

        return $this;
    }

    /**
     * Gets bid
     *
     * @return float|null
     */
    public function getBid()
    {
        return $this->container['bid'];
    }

    /**
     * Sets bid
     *
     * @param float|null $bid The new ads' bid; 0 = the bid of the campaign's newest ad.
     *
     * @return self
     */
    public function setBid($bid)
    {
        if (is_null($bid)) {
            throw new \InvalidArgumentException('non-nullable bid cannot be null');
        }
        $this->container['bid'] = $bid;

        return $this;
    }

    /**
     * Gets template
     *
     * @return string|null
     */
    public function getTemplate()
    {
        return $this->container['template'];
    }

    /**
     * Sets template
     *
     * @param string|null $template Dynamic banners (banner campaigns): card, overlay, minimal or custom. native (or leaving it out) = plain native ads.
     *
     * @return self
     */
    public function setTemplate($template)
    {
        if (is_null($template)) {
            throw new \InvalidArgumentException('non-nullable template cannot be null');
        }
        $allowedValues = $this->getTemplateAllowableValues();
        if (!in_array($template, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'template', must be one of '%s'",
                    $template,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['template'] = $template;

        return $this;
    }

    /**
     * Gets template_html
     *
     * @return string|null
     */
    public function getTemplateHtml()
    {
        return $this->container['template_html'];
    }

    /**
     * Sets template_html
     *
     * @param string|null $template_html custom template only.
     *
     * @return self
     */
    public function setTemplateHtml($template_html)
    {
        if (is_null($template_html)) {
            throw new \InvalidArgumentException('non-nullable template_html cannot be null');
        }
        if ((mb_strlen($template_html) > 20000)) {
            throw new \InvalidArgumentException('invalid length for $template_html when calling ApiV1ProductFeedsPostRequest., must be smaller than or equal to 20000.');
        }

        $this->container['template_html'] = $template_html;

        return $this;
    }

    /**
     * Gets cta
     *
     * @return string|null
     */
    public function getCta()
    {
        return $this->container['cta'];
    }

    /**
     * Sets cta
     *
     * @param string|null $cta cta
     *
     * @return self
     */
    public function setCta($cta)
    {
        if (is_null($cta)) {
            throw new \InvalidArgumentException('non-nullable cta cannot be null');
        }
        if ((mb_strlen($cta) > 30)) {
            throw new \InvalidArgumentException('invalid length for $cta when calling ApiV1ProductFeedsPostRequest., must be smaller than or equal to 30.');
        }

        $this->container['cta'] = $cta;

        return $this;
    }

    /**
     * Gets accent
     *
     * @return string|null
     */
    public function getAccent()
    {
        return $this->container['accent'];
    }

    /**
     * Sets accent
     *
     * @param string|null $accent #rrggbb
     *
     * @return self
     */
    public function setAccent($accent)
    {
        if (is_null($accent)) {
            throw new \InvalidArgumentException('non-nullable accent cannot be null');
        }
        $this->container['accent'] = $accent;

        return $this;
    }

    /**
     * Gets bansizeid
     *
     * @return int|null
     */
    public function getBansizeid()
    {
        return $this->container['bansizeid'];
    }

    /**
     * Sets bansizeid
     *
     * @param int|null $bansizeid Dynamic banners: one of the campaign's banner_sizes.
     *
     * @return self
     */
    public function setBansizeid($bansizeid)
    {
        if (is_null($bansizeid)) {
            throw new \InvalidArgumentException('non-nullable bansizeid cannot be null');
        }
        $this->container['bansizeid'] = $bansizeid;

        return $this;
    }

    /**
     * Gets retarget
     *
     * @return bool|null
     */
    public function getRetarget()
    {
        return $this->container['retarget'];
    }

    /**
     * Sets retarget
     *
     * @param bool|null $retarget retarget
     *
     * @return self
     */
    public function setRetarget($retarget)
    {
        if (is_null($retarget)) {
            throw new \InvalidArgumentException('non-nullable retarget cannot be null');
        }
        $this->container['retarget'] = $retarget;

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


