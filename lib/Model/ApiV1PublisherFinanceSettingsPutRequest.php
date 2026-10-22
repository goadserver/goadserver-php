<?php
/**
 * ApiV1PublisherFinanceSettingsPutRequest
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
 * ApiV1PublisherFinanceSettingsPutRequest Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<string, mixed>
 */
class ApiV1PublisherFinanceSettingsPutRequest implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
     * The original name of the model.
     *
     * @var string
     */
    protected static $openAPIModelName = '_api_v1_publisher_finance_settings_put_request';

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $openAPITypes = [
        'payouttype' => 'int',
        'minimum_payout' => 'float',
        'p_currency' => 'string',
        'directpayout' => 'int',
        'payoutmethod' => 'int',
        'paypaladress' => 'string',
        'epaymentsadress' => 'string',
        'paxumadress' => 'string',
        'payoneeradress' => 'string',
        'paymentdata' => 'string',
        'bankaccountnumber' => 'string',
        'bankaccountname' => 'string',
        'biccode' => 'string',
        'ibancode' => 'string',
        'routingnumber' => 'string',
        'bic_countryid' => 'int',
        'noiban' => 'bool',
        'vatnumber' => 'string',
        'payinfo' => 'string'
    ];

    /**
     * Array of property to format mappings. Used for (de)serialization
     *
     * @var string[]
     * @phpstan-var array<string, string|null>
     * @psalm-var array<string, string|null>
     */
    protected static $openAPIFormats = [
        'payouttype' => null,
        'minimum_payout' => null,
        'p_currency' => null,
        'directpayout' => null,
        'payoutmethod' => null,
        'paypaladress' => null,
        'epaymentsadress' => null,
        'paxumadress' => null,
        'payoneeradress' => null,
        'paymentdata' => null,
        'bankaccountnumber' => null,
        'bankaccountname' => null,
        'biccode' => null,
        'ibancode' => null,
        'routingnumber' => null,
        'bic_countryid' => null,
        'noiban' => null,
        'vatnumber' => null,
        'payinfo' => null
    ];

    /**
     * Array of nullable properties. Used for (de)serialization
     *
     * @var boolean[]
     */
    protected static array $openAPINullables = [
        'payouttype' => false,
        'minimum_payout' => false,
        'p_currency' => false,
        'directpayout' => false,
        'payoutmethod' => false,
        'paypaladress' => false,
        'epaymentsadress' => false,
        'paxumadress' => false,
        'payoneeradress' => false,
        'paymentdata' => false,
        'bankaccountnumber' => false,
        'bankaccountname' => false,
        'biccode' => false,
        'ibancode' => false,
        'routingnumber' => false,
        'bic_countryid' => false,
        'noiban' => false,
        'vatnumber' => false,
        'payinfo' => false
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
        'payouttype' => 'payouttype',
        'minimum_payout' => 'minimum_payout',
        'p_currency' => 'p_currency',
        'directpayout' => 'directpayout',
        'payoutmethod' => 'payoutmethod',
        'paypaladress' => 'paypaladress',
        'epaymentsadress' => 'epaymentsadress',
        'paxumadress' => 'paxumadress',
        'payoneeradress' => 'payoneeradress',
        'paymentdata' => 'paymentdata',
        'bankaccountnumber' => 'bankaccountnumber',
        'bankaccountname' => 'bankaccountname',
        'biccode' => 'biccode',
        'ibancode' => 'ibancode',
        'routingnumber' => 'routingnumber',
        'bic_countryid' => 'bic_countryid',
        'noiban' => 'noiban',
        'vatnumber' => 'vatnumber',
        'payinfo' => 'payinfo'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'payouttype' => 'setPayouttype',
        'minimum_payout' => 'setMinimumPayout',
        'p_currency' => 'setPCurrency',
        'directpayout' => 'setDirectpayout',
        'payoutmethod' => 'setPayoutmethod',
        'paypaladress' => 'setPaypaladress',
        'epaymentsadress' => 'setEpaymentsadress',
        'paxumadress' => 'setPaxumadress',
        'payoneeradress' => 'setPayoneeradress',
        'paymentdata' => 'setPaymentdata',
        'bankaccountnumber' => 'setBankaccountnumber',
        'bankaccountname' => 'setBankaccountname',
        'biccode' => 'setBiccode',
        'ibancode' => 'setIbancode',
        'routingnumber' => 'setRoutingnumber',
        'bic_countryid' => 'setBicCountryid',
        'noiban' => 'setNoiban',
        'vatnumber' => 'setVatnumber',
        'payinfo' => 'setPayinfo'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'payouttype' => 'getPayouttype',
        'minimum_payout' => 'getMinimumPayout',
        'p_currency' => 'getPCurrency',
        'directpayout' => 'getDirectpayout',
        'payoutmethod' => 'getPayoutmethod',
        'paypaladress' => 'getPaypaladress',
        'epaymentsadress' => 'getEpaymentsadress',
        'paxumadress' => 'getPaxumadress',
        'payoneeradress' => 'getPayoneeradress',
        'paymentdata' => 'getPaymentdata',
        'bankaccountnumber' => 'getBankaccountnumber',
        'bankaccountname' => 'getBankaccountname',
        'biccode' => 'getBiccode',
        'ibancode' => 'getIbancode',
        'routingnumber' => 'getRoutingnumber',
        'bic_countryid' => 'getBicCountryid',
        'noiban' => 'getNoiban',
        'vatnumber' => 'getVatnumber',
        'payinfo' => 'getPayinfo'
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

    public const P_CURRENCY_EUR = 'eur';
    public const P_CURRENCY_USD = 'usd';
    public const DIRECTPAYOUT_NUMBER_0 = 0;
    public const DIRECTPAYOUT_NUMBER_1 = 1;
    public const DIRECTPAYOUT_NUMBER_2 = 2;

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getPCurrencyAllowableValues()
    {
        return [
            self::P_CURRENCY_EUR,
            self::P_CURRENCY_USD,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getDirectpayoutAllowableValues()
    {
        return [
            self::DIRECTPAYOUT_NUMBER_0,
            self::DIRECTPAYOUT_NUMBER_1,
            self::DIRECTPAYOUT_NUMBER_2,
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
        $this->setIfExists('payouttype', $data ?? [], null);
        $this->setIfExists('minimum_payout', $data ?? [], null);
        $this->setIfExists('p_currency', $data ?? [], null);
        $this->setIfExists('directpayout', $data ?? [], null);
        $this->setIfExists('payoutmethod', $data ?? [], null);
        $this->setIfExists('paypaladress', $data ?? [], null);
        $this->setIfExists('epaymentsadress', $data ?? [], null);
        $this->setIfExists('paxumadress', $data ?? [], null);
        $this->setIfExists('payoneeradress', $data ?? [], null);
        $this->setIfExists('paymentdata', $data ?? [], null);
        $this->setIfExists('bankaccountnumber', $data ?? [], null);
        $this->setIfExists('bankaccountname', $data ?? [], null);
        $this->setIfExists('biccode', $data ?? [], null);
        $this->setIfExists('ibancode', $data ?? [], null);
        $this->setIfExists('routingnumber', $data ?? [], null);
        $this->setIfExists('bic_countryid', $data ?? [], null);
        $this->setIfExists('noiban', $data ?? [], null);
        $this->setIfExists('vatnumber', $data ?? [], null);
        $this->setIfExists('payinfo', $data ?? [], null);
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

        $allowedValues = $this->getPCurrencyAllowableValues();
        if (!is_null($this->container['p_currency']) && !in_array($this->container['p_currency'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'p_currency', must be one of '%s'",
                $this->container['p_currency'],
                implode("', '", $allowedValues)
            );
        }

        $allowedValues = $this->getDirectpayoutAllowableValues();
        if (!is_null($this->container['directpayout']) && !in_array($this->container['directpayout'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'directpayout', must be one of '%s'",
                $this->container['directpayout'],
                implode("', '", $allowedValues)
            );
        }

        if (!is_null($this->container['paypaladress']) && (mb_strlen($this->container['paypaladress']) > 255)) {
            $invalidProperties[] = "invalid value for 'paypaladress', the character length must be smaller than or equal to 255.";
        }

        if (!is_null($this->container['epaymentsadress']) && (mb_strlen($this->container['epaymentsadress']) > 255)) {
            $invalidProperties[] = "invalid value for 'epaymentsadress', the character length must be smaller than or equal to 255.";
        }

        if (!is_null($this->container['paxumadress']) && (mb_strlen($this->container['paxumadress']) > 255)) {
            $invalidProperties[] = "invalid value for 'paxumadress', the character length must be smaller than or equal to 255.";
        }

        if (!is_null($this->container['payoneeradress']) && (mb_strlen($this->container['payoneeradress']) > 255)) {
            $invalidProperties[] = "invalid value for 'payoneeradress', the character length must be smaller than or equal to 255.";
        }

        if (!is_null($this->container['paymentdata']) && (mb_strlen($this->container['paymentdata']) > 255)) {
            $invalidProperties[] = "invalid value for 'paymentdata', the character length must be smaller than or equal to 255.";
        }

        if (!is_null($this->container['bankaccountnumber']) && (mb_strlen($this->container['bankaccountnumber']) > 100)) {
            $invalidProperties[] = "invalid value for 'bankaccountnumber', the character length must be smaller than or equal to 100.";
        }

        if (!is_null($this->container['bankaccountname']) && (mb_strlen($this->container['bankaccountname']) > 255)) {
            $invalidProperties[] = "invalid value for 'bankaccountname', the character length must be smaller than or equal to 255.";
        }

        if (!is_null($this->container['biccode']) && (mb_strlen($this->container['biccode']) > 100)) {
            $invalidProperties[] = "invalid value for 'biccode', the character length must be smaller than or equal to 100.";
        }

        if (!is_null($this->container['ibancode']) && (mb_strlen($this->container['ibancode']) > 100)) {
            $invalidProperties[] = "invalid value for 'ibancode', the character length must be smaller than or equal to 100.";
        }

        if (!is_null($this->container['routingnumber']) && (mb_strlen($this->container['routingnumber']) > 100)) {
            $invalidProperties[] = "invalid value for 'routingnumber', the character length must be smaller than or equal to 100.";
        }

        if (!is_null($this->container['vatnumber']) && (mb_strlen($this->container['vatnumber']) > 100)) {
            $invalidProperties[] = "invalid value for 'vatnumber', the character length must be smaller than or equal to 100.";
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
     * Gets payouttype
     *
     * @return int|null
     */
    public function getPayouttype()
    {
        return $this->container['payouttype'];
    }

    /**
     * Sets payouttype
     *
     * @param int|null $payouttype payouttype
     *
     * @return self
     */
    public function setPayouttype($payouttype)
    {
        if (is_null($payouttype)) {
            throw new \InvalidArgumentException('non-nullable payouttype cannot be null');
        }
        $this->container['payouttype'] = $payouttype;

        return $this;
    }

    /**
     * Gets minimum_payout
     *
     * @return float|null
     */
    public function getMinimumPayout()
    {
        return $this->container['minimum_payout'];
    }

    /**
     * Sets minimum_payout
     *
     * @param float|null $minimum_payout Whole currency units.
     *
     * @return self
     */
    public function setMinimumPayout($minimum_payout)
    {
        if (is_null($minimum_payout)) {
            throw new \InvalidArgumentException('non-nullable minimum_payout cannot be null');
        }
        $this->container['minimum_payout'] = $minimum_payout;

        return $this;
    }

    /**
     * Gets p_currency
     *
     * @return string|null
     */
    public function getPCurrency()
    {
        return $this->container['p_currency'];
    }

    /**
     * Sets p_currency
     *
     * @param string|null $p_currency p_currency
     *
     * @return self
     */
    public function setPCurrency($p_currency)
    {
        if (is_null($p_currency)) {
            throw new \InvalidArgumentException('non-nullable p_currency cannot be null');
        }
        $allowedValues = $this->getPCurrencyAllowableValues();
        if (!in_array($p_currency, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'p_currency', must be one of '%s'",
                    $p_currency,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['p_currency'] = $p_currency;

        return $this;
    }

    /**
     * Gets directpayout
     *
     * @return int|null
     */
    public function getDirectpayout()
    {
        return $this->container['directpayout'];
    }

    /**
     * Sets directpayout
     *
     * @param int|null $directpayout directpayout
     *
     * @return self
     */
    public function setDirectpayout($directpayout)
    {
        if (is_null($directpayout)) {
            throw new \InvalidArgumentException('non-nullable directpayout cannot be null');
        }
        $allowedValues = $this->getDirectpayoutAllowableValues();
        if (!in_array($directpayout, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'directpayout', must be one of '%s'",
                    $directpayout,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['directpayout'] = $directpayout;

        return $this;
    }

    /**
     * Gets payoutmethod
     *
     * @return int|null
     */
    public function getPayoutmethod()
    {
        return $this->container['payoutmethod'];
    }

    /**
     * Sets payoutmethod
     *
     * @param int|null $payoutmethod payoutmethod
     *
     * @return self
     */
    public function setPayoutmethod($payoutmethod)
    {
        if (is_null($payoutmethod)) {
            throw new \InvalidArgumentException('non-nullable payoutmethod cannot be null');
        }
        $this->container['payoutmethod'] = $payoutmethod;

        return $this;
    }

    /**
     * Gets paypaladress
     *
     * @return string|null
     */
    public function getPaypaladress()
    {
        return $this->container['paypaladress'];
    }

    /**
     * Sets paypaladress
     *
     * @param string|null $paypaladress paypaladress
     *
     * @return self
     */
    public function setPaypaladress($paypaladress)
    {
        if (is_null($paypaladress)) {
            throw new \InvalidArgumentException('non-nullable paypaladress cannot be null');
        }
        if ((mb_strlen($paypaladress) > 255)) {
            throw new \InvalidArgumentException('invalid length for $paypaladress when calling ApiV1PublisherFinanceSettingsPutRequest., must be smaller than or equal to 255.');
        }

        $this->container['paypaladress'] = $paypaladress;

        return $this;
    }

    /**
     * Gets epaymentsadress
     *
     * @return string|null
     */
    public function getEpaymentsadress()
    {
        return $this->container['epaymentsadress'];
    }

    /**
     * Sets epaymentsadress
     *
     * @param string|null $epaymentsadress epaymentsadress
     *
     * @return self
     */
    public function setEpaymentsadress($epaymentsadress)
    {
        if (is_null($epaymentsadress)) {
            throw new \InvalidArgumentException('non-nullable epaymentsadress cannot be null');
        }
        if ((mb_strlen($epaymentsadress) > 255)) {
            throw new \InvalidArgumentException('invalid length for $epaymentsadress when calling ApiV1PublisherFinanceSettingsPutRequest., must be smaller than or equal to 255.');
        }

        $this->container['epaymentsadress'] = $epaymentsadress;

        return $this;
    }

    /**
     * Gets paxumadress
     *
     * @return string|null
     */
    public function getPaxumadress()
    {
        return $this->container['paxumadress'];
    }

    /**
     * Sets paxumadress
     *
     * @param string|null $paxumadress paxumadress
     *
     * @return self
     */
    public function setPaxumadress($paxumadress)
    {
        if (is_null($paxumadress)) {
            throw new \InvalidArgumentException('non-nullable paxumadress cannot be null');
        }
        if ((mb_strlen($paxumadress) > 255)) {
            throw new \InvalidArgumentException('invalid length for $paxumadress when calling ApiV1PublisherFinanceSettingsPutRequest., must be smaller than or equal to 255.');
        }

        $this->container['paxumadress'] = $paxumadress;

        return $this;
    }

    /**
     * Gets payoneeradress
     *
     * @return string|null
     */
    public function getPayoneeradress()
    {
        return $this->container['payoneeradress'];
    }

    /**
     * Sets payoneeradress
     *
     * @param string|null $payoneeradress payoneeradress
     *
     * @return self
     */
    public function setPayoneeradress($payoneeradress)
    {
        if (is_null($payoneeradress)) {
            throw new \InvalidArgumentException('non-nullable payoneeradress cannot be null');
        }
        if ((mb_strlen($payoneeradress) > 255)) {
            throw new \InvalidArgumentException('invalid length for $payoneeradress when calling ApiV1PublisherFinanceSettingsPutRequest., must be smaller than or equal to 255.');
        }

        $this->container['payoneeradress'] = $payoneeradress;

        return $this;
    }

    /**
     * Gets paymentdata
     *
     * @return string|null
     */
    public function getPaymentdata()
    {
        return $this->container['paymentdata'];
    }

    /**
     * Sets paymentdata
     *
     * @param string|null $paymentdata USDT wallet address.
     *
     * @return self
     */
    public function setPaymentdata($paymentdata)
    {
        if (is_null($paymentdata)) {
            throw new \InvalidArgumentException('non-nullable paymentdata cannot be null');
        }
        if ((mb_strlen($paymentdata) > 255)) {
            throw new \InvalidArgumentException('invalid length for $paymentdata when calling ApiV1PublisherFinanceSettingsPutRequest., must be smaller than or equal to 255.');
        }

        $this->container['paymentdata'] = $paymentdata;

        return $this;
    }

    /**
     * Gets bankaccountnumber
     *
     * @return string|null
     */
    public function getBankaccountnumber()
    {
        return $this->container['bankaccountnumber'];
    }

    /**
     * Sets bankaccountnumber
     *
     * @param string|null $bankaccountnumber bankaccountnumber
     *
     * @return self
     */
    public function setBankaccountnumber($bankaccountnumber)
    {
        if (is_null($bankaccountnumber)) {
            throw new \InvalidArgumentException('non-nullable bankaccountnumber cannot be null');
        }
        if ((mb_strlen($bankaccountnumber) > 100)) {
            throw new \InvalidArgumentException('invalid length for $bankaccountnumber when calling ApiV1PublisherFinanceSettingsPutRequest., must be smaller than or equal to 100.');
        }

        $this->container['bankaccountnumber'] = $bankaccountnumber;

        return $this;
    }

    /**
     * Gets bankaccountname
     *
     * @return string|null
     */
    public function getBankaccountname()
    {
        return $this->container['bankaccountname'];
    }

    /**
     * Sets bankaccountname
     *
     * @param string|null $bankaccountname bankaccountname
     *
     * @return self
     */
    public function setBankaccountname($bankaccountname)
    {
        if (is_null($bankaccountname)) {
            throw new \InvalidArgumentException('non-nullable bankaccountname cannot be null');
        }
        if ((mb_strlen($bankaccountname) > 255)) {
            throw new \InvalidArgumentException('invalid length for $bankaccountname when calling ApiV1PublisherFinanceSettingsPutRequest., must be smaller than or equal to 255.');
        }

        $this->container['bankaccountname'] = $bankaccountname;

        return $this;
    }

    /**
     * Gets biccode
     *
     * @return string|null
     */
    public function getBiccode()
    {
        return $this->container['biccode'];
    }

    /**
     * Sets biccode
     *
     * @param string|null $biccode biccode
     *
     * @return self
     */
    public function setBiccode($biccode)
    {
        if (is_null($biccode)) {
            throw new \InvalidArgumentException('non-nullable biccode cannot be null');
        }
        if ((mb_strlen($biccode) > 100)) {
            throw new \InvalidArgumentException('invalid length for $biccode when calling ApiV1PublisherFinanceSettingsPutRequest., must be smaller than or equal to 100.');
        }

        $this->container['biccode'] = $biccode;

        return $this;
    }

    /**
     * Gets ibancode
     *
     * @return string|null
     */
    public function getIbancode()
    {
        return $this->container['ibancode'];
    }

    /**
     * Sets ibancode
     *
     * @param string|null $ibancode ibancode
     *
     * @return self
     */
    public function setIbancode($ibancode)
    {
        if (is_null($ibancode)) {
            throw new \InvalidArgumentException('non-nullable ibancode cannot be null');
        }
        if ((mb_strlen($ibancode) > 100)) {
            throw new \InvalidArgumentException('invalid length for $ibancode when calling ApiV1PublisherFinanceSettingsPutRequest., must be smaller than or equal to 100.');
        }

        $this->container['ibancode'] = $ibancode;

        return $this;
    }

    /**
     * Gets routingnumber
     *
     * @return string|null
     */
    public function getRoutingnumber()
    {
        return $this->container['routingnumber'];
    }

    /**
     * Sets routingnumber
     *
     * @param string|null $routingnumber routingnumber
     *
     * @return self
     */
    public function setRoutingnumber($routingnumber)
    {
        if (is_null($routingnumber)) {
            throw new \InvalidArgumentException('non-nullable routingnumber cannot be null');
        }
        if ((mb_strlen($routingnumber) > 100)) {
            throw new \InvalidArgumentException('invalid length for $routingnumber when calling ApiV1PublisherFinanceSettingsPutRequest., must be smaller than or equal to 100.');
        }

        $this->container['routingnumber'] = $routingnumber;

        return $this;
    }

    /**
     * Gets bic_countryid
     *
     * @return int|null
     */
    public function getBicCountryid()
    {
        return $this->container['bic_countryid'];
    }

    /**
     * Sets bic_countryid
     *
     * @param int|null $bic_countryid bic_countryid
     *
     * @return self
     */
    public function setBicCountryid($bic_countryid)
    {
        if (is_null($bic_countryid)) {
            throw new \InvalidArgumentException('non-nullable bic_countryid cannot be null');
        }
        $this->container['bic_countryid'] = $bic_countryid;

        return $this;
    }

    /**
     * Gets noiban
     *
     * @return bool|null
     */
    public function getNoiban()
    {
        return $this->container['noiban'];
    }

    /**
     * Sets noiban
     *
     * @param bool|null $noiban noiban
     *
     * @return self
     */
    public function setNoiban($noiban)
    {
        if (is_null($noiban)) {
            throw new \InvalidArgumentException('non-nullable noiban cannot be null');
        }
        $this->container['noiban'] = $noiban;

        return $this;
    }

    /**
     * Gets vatnumber
     *
     * @return string|null
     */
    public function getVatnumber()
    {
        return $this->container['vatnumber'];
    }

    /**
     * Sets vatnumber
     *
     * @param string|null $vatnumber vatnumber
     *
     * @return self
     */
    public function setVatnumber($vatnumber)
    {
        if (is_null($vatnumber)) {
            throw new \InvalidArgumentException('non-nullable vatnumber cannot be null');
        }
        if ((mb_strlen($vatnumber) > 100)) {
            throw new \InvalidArgumentException('invalid length for $vatnumber when calling ApiV1PublisherFinanceSettingsPutRequest., must be smaller than or equal to 100.');
        }

        $this->container['vatnumber'] = $vatnumber;

        return $this;
    }

    /**
     * Gets payinfo
     *
     * @return string|null
     */
    public function getPayinfo()
    {
        return $this->container['payinfo'];
    }

    /**
     * Sets payinfo
     *
     * @param string|null $payinfo payinfo
     *
     * @return self
     */
    public function setPayinfo($payinfo)
    {
        if (is_null($payinfo)) {
            throw new \InvalidArgumentException('non-nullable payinfo cannot be null');
        }
        $this->container['payinfo'] = $payinfo;

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


