<?php
/**
 * ApiV1AccountBillingInvoicesGet200ResponseResultsInner
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
 * ApiV1AccountBillingInvoicesGet200ResponseResultsInner Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<string, mixed>
 */
class ApiV1AccountBillingInvoicesGet200ResponseResultsInner implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
     * The original name of the model.
     *
     * @var string
     */
    protected static $openAPIModelName = '_api_v1_account_billing_invoices_get_200_response_results_inner';

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $openAPITypes = [
        'id' => 'int',
        'dateadded' => 'string',
        'pay_subject' => 'string',
        'money' => 'int',
        'used_currency' => 'string',
        'ispayed' => 'bool',
        'paytype' => 'int',
        'paytype_string' => 'string',
        'bandwidth_costs' => 'int',
        'credit_state' => 'int',
        'datepayed' => 'string',
        'pro_adearned' => 'int',
        'paystatus_string' => 'string'
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
        'dateadded' => null,
        'pay_subject' => null,
        'money' => null,
        'used_currency' => null,
        'ispayed' => null,
        'paytype' => null,
        'paytype_string' => null,
        'bandwidth_costs' => null,
        'credit_state' => null,
        'datepayed' => null,
        'pro_adearned' => null,
        'paystatus_string' => null
    ];

    /**
     * Array of nullable properties. Used for (de)serialization
     *
     * @var boolean[]
     */
    protected static array $openAPINullables = [
        'id' => false,
        'dateadded' => false,
        'pay_subject' => false,
        'money' => false,
        'used_currency' => false,
        'ispayed' => false,
        'paytype' => false,
        'paytype_string' => false,
        'bandwidth_costs' => false,
        'credit_state' => false,
        'datepayed' => false,
        'pro_adearned' => false,
        'paystatus_string' => false
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
        'dateadded' => 'dateadded',
        'pay_subject' => 'pay_subject',
        'money' => 'money',
        'used_currency' => 'used_currency',
        'ispayed' => 'ispayed',
        'paytype' => 'paytype',
        'paytype_string' => 'paytype_string',
        'bandwidth_costs' => 'bandwidth_costs',
        'credit_state' => 'credit_state',
        'datepayed' => 'datepayed',
        'pro_adearned' => 'pro_adearned',
        'paystatus_string' => 'paystatus_string'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'id' => 'setId',
        'dateadded' => 'setDateadded',
        'pay_subject' => 'setPaySubject',
        'money' => 'setMoney',
        'used_currency' => 'setUsedCurrency',
        'ispayed' => 'setIspayed',
        'paytype' => 'setPaytype',
        'paytype_string' => 'setPaytypeString',
        'bandwidth_costs' => 'setBandwidthCosts',
        'credit_state' => 'setCreditState',
        'datepayed' => 'setDatepayed',
        'pro_adearned' => 'setProAdearned',
        'paystatus_string' => 'setPaystatusString'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'id' => 'getId',
        'dateadded' => 'getDateadded',
        'pay_subject' => 'getPaySubject',
        'money' => 'getMoney',
        'used_currency' => 'getUsedCurrency',
        'ispayed' => 'getIspayed',
        'paytype' => 'getPaytype',
        'paytype_string' => 'getPaytypeString',
        'bandwidth_costs' => 'getBandwidthCosts',
        'credit_state' => 'getCreditState',
        'datepayed' => 'getDatepayed',
        'pro_adearned' => 'getProAdearned',
        'paystatus_string' => 'getPaystatusString'
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

    public const USED_CURRENCY_EUR = 'eur';
    public const USED_CURRENCY_USD = 'usd';
    public const PAYSTATUS_STRING_OPEN = 'Open';
    public const PAYSTATUS_STRING_PROCESSING = 'Processing';
    public const PAYSTATUS_STRING_PAID = 'Paid';
    public const PAYSTATUS_STRING_PREVALIDATED = 'Prevalidated';

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getUsedCurrencyAllowableValues()
    {
        return [
            self::USED_CURRENCY_EUR,
            self::USED_CURRENCY_USD,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getPaystatusStringAllowableValues()
    {
        return [
            self::PAYSTATUS_STRING_OPEN,
            self::PAYSTATUS_STRING_PROCESSING,
            self::PAYSTATUS_STRING_PAID,
            self::PAYSTATUS_STRING_PREVALIDATED,
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
        $this->setIfExists('dateadded', $data ?? [], null);
        $this->setIfExists('pay_subject', $data ?? [], null);
        $this->setIfExists('money', $data ?? [], null);
        $this->setIfExists('used_currency', $data ?? [], null);
        $this->setIfExists('ispayed', $data ?? [], null);
        $this->setIfExists('paytype', $data ?? [], null);
        $this->setIfExists('paytype_string', $data ?? [], null);
        $this->setIfExists('bandwidth_costs', $data ?? [], null);
        $this->setIfExists('credit_state', $data ?? [], null);
        $this->setIfExists('datepayed', $data ?? [], null);
        $this->setIfExists('pro_adearned', $data ?? [], null);
        $this->setIfExists('paystatus_string', $data ?? [], null);
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

        $allowedValues = $this->getUsedCurrencyAllowableValues();
        if (!is_null($this->container['used_currency']) && !in_array($this->container['used_currency'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'used_currency', must be one of '%s'",
                $this->container['used_currency'],
                implode("', '", $allowedValues)
            );
        }

        $allowedValues = $this->getPaystatusStringAllowableValues();
        if (!is_null($this->container['paystatus_string']) && !in_array($this->container['paystatus_string'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'paystatus_string', must be one of '%s'",
                $this->container['paystatus_string'],
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
     * Gets dateadded
     *
     * @return string|null
     */
    public function getDateadded()
    {
        return $this->container['dateadded'];
    }

    /**
     * Sets dateadded
     *
     * @param string|null $dateadded dateadded
     *
     * @return self
     */
    public function setDateadded($dateadded)
    {
        if (is_null($dateadded)) {
            throw new \InvalidArgumentException('non-nullable dateadded cannot be null');
        }
        $this->container['dateadded'] = $dateadded;

        return $this;
    }

    /**
     * Gets pay_subject
     *
     * @return string|null
     */
    public function getPaySubject()
    {
        return $this->container['pay_subject'];
    }

    /**
     * Sets pay_subject
     *
     * @param string|null $pay_subject pay_subject
     *
     * @return self
     */
    public function setPaySubject($pay_subject)
    {
        if (is_null($pay_subject)) {
            throw new \InvalidArgumentException('non-nullable pay_subject cannot be null');
        }
        $this->container['pay_subject'] = $pay_subject;

        return $this;
    }

    /**
     * Gets money
     *
     * @return int|null
     */
    public function getMoney()
    {
        return $this->container['money'];
    }

    /**
     * Sets money
     *
     * @param int|null $money Cents.
     *
     * @return self
     */
    public function setMoney($money)
    {
        if (is_null($money)) {
            throw new \InvalidArgumentException('non-nullable money cannot be null');
        }
        $this->container['money'] = $money;

        return $this;
    }

    /**
     * Gets used_currency
     *
     * @return string|null
     */
    public function getUsedCurrency()
    {
        return $this->container['used_currency'];
    }

    /**
     * Sets used_currency
     *
     * @param string|null $used_currency used_currency
     *
     * @return self
     */
    public function setUsedCurrency($used_currency)
    {
        if (is_null($used_currency)) {
            throw new \InvalidArgumentException('non-nullable used_currency cannot be null');
        }
        $allowedValues = $this->getUsedCurrencyAllowableValues();
        if (!in_array($used_currency, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'used_currency', must be one of '%s'",
                    $used_currency,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['used_currency'] = $used_currency;

        return $this;
    }

    /**
     * Gets ispayed
     *
     * @return bool|null
     */
    public function getIspayed()
    {
        return $this->container['ispayed'];
    }

    /**
     * Sets ispayed
     *
     * @param bool|null $ispayed ispayed
     *
     * @return self
     */
    public function setIspayed($ispayed)
    {
        if (is_null($ispayed)) {
            throw new \InvalidArgumentException('non-nullable ispayed cannot be null');
        }
        $this->container['ispayed'] = $ispayed;

        return $this;
    }

    /**
     * Gets paytype
     *
     * @return int|null
     */
    public function getPaytype()
    {
        return $this->container['paytype'];
    }

    /**
     * Sets paytype
     *
     * @param int|null $paytype paytype
     *
     * @return self
     */
    public function setPaytype($paytype)
    {
        if (is_null($paytype)) {
            throw new \InvalidArgumentException('non-nullable paytype cannot be null');
        }
        $this->container['paytype'] = $paytype;

        return $this;
    }

    /**
     * Gets paytype_string
     *
     * @return string|null
     */
    public function getPaytypeString()
    {
        return $this->container['paytype_string'];
    }

    /**
     * Sets paytype_string
     *
     * @param string|null $paytype_string paytype_string
     *
     * @return self
     */
    public function setPaytypeString($paytype_string)
    {
        if (is_null($paytype_string)) {
            throw new \InvalidArgumentException('non-nullable paytype_string cannot be null');
        }
        $this->container['paytype_string'] = $paytype_string;

        return $this;
    }

    /**
     * Gets bandwidth_costs
     *
     * @return int|null
     */
    public function getBandwidthCosts()
    {
        return $this->container['bandwidth_costs'];
    }

    /**
     * Sets bandwidth_costs
     *
     * @param int|null $bandwidth_costs Cents.
     *
     * @return self
     */
    public function setBandwidthCosts($bandwidth_costs)
    {
        if (is_null($bandwidth_costs)) {
            throw new \InvalidArgumentException('non-nullable bandwidth_costs cannot be null');
        }
        $this->container['bandwidth_costs'] = $bandwidth_costs;

        return $this;
    }

    /**
     * Gets credit_state
     *
     * @return int|null
     */
    public function getCreditState()
    {
        return $this->container['credit_state'];
    }

    /**
     * Sets credit_state
     *
     * @param int|null $credit_state credit_state
     *
     * @return self
     */
    public function setCreditState($credit_state)
    {
        if (is_null($credit_state)) {
            throw new \InvalidArgumentException('non-nullable credit_state cannot be null');
        }
        $this->container['credit_state'] = $credit_state;

        return $this;
    }

    /**
     * Gets datepayed
     *
     * @return string|null
     */
    public function getDatepayed()
    {
        return $this->container['datepayed'];
    }

    /**
     * Sets datepayed
     *
     * @param string|null $datepayed Date, empty when unpaid.
     *
     * @return self
     */
    public function setDatepayed($datepayed)
    {
        if (is_null($datepayed)) {
            throw new \InvalidArgumentException('non-nullable datepayed cannot be null');
        }
        $this->container['datepayed'] = $datepayed;

        return $this;
    }

    /**
     * Gets pro_adearned
     *
     * @return int|null
     */
    public function getProAdearned()
    {
        return $this->container['pro_adearned'];
    }

    /**
     * Sets pro_adearned
     *
     * @param int|null $pro_adearned Cents.
     *
     * @return self
     */
    public function setProAdearned($pro_adearned)
    {
        if (is_null($pro_adearned)) {
            throw new \InvalidArgumentException('non-nullable pro_adearned cannot be null');
        }
        $this->container['pro_adearned'] = $pro_adearned;

        return $this;
    }

    /**
     * Gets paystatus_string
     *
     * @return string|null
     */
    public function getPaystatusString()
    {
        return $this->container['paystatus_string'];
    }

    /**
     * Sets paystatus_string
     *
     * @param string|null $paystatus_string paystatus_string
     *
     * @return self
     */
    public function setPaystatusString($paystatus_string)
    {
        if (is_null($paystatus_string)) {
            throw new \InvalidArgumentException('non-nullable paystatus_string cannot be null');
        }
        $allowedValues = $this->getPaystatusStringAllowableValues();
        if (!in_array($paystatus_string, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'paystatus_string', must be one of '%s'",
                    $paystatus_string,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['paystatus_string'] = $paystatus_string;

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


