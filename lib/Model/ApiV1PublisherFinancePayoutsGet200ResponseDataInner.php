<?php
/**
 * ApiV1PublisherFinancePayoutsGet200ResponseDataInner
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
 * ApiV1PublisherFinancePayoutsGet200ResponseDataInner Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<string, mixed>
 */
class ApiV1PublisherFinancePayoutsGet200ResponseDataInner implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
     * The original name of the model.
     *
     * @var string
     */
    protected static $openAPIModelName = '_api_v1_publisher_finance_payouts_get_200_response_data_inner';

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $openAPITypes = [
        'id' => 'int',
        'transactionid' => 'int',
        'transacttype' => 'int',
        'dateadded' => '\DateTime',
        'money' => 'int',
        'ispayed' => 'int',
        'datepayed' => 'string',
        'isunpayable' => 'int',
        'txt' => 'string',
        'refund_note' => 'string',
        'paid_currency' => 'string',
        'companyid' => 'int',
        'description' => 'string'
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
        'transactionid' => null,
        'transacttype' => null,
        'dateadded' => 'date',
        'money' => null,
        'ispayed' => null,
        'datepayed' => null,
        'isunpayable' => null,
        'txt' => null,
        'refund_note' => null,
        'paid_currency' => null,
        'companyid' => null,
        'description' => null
    ];

    /**
     * Array of nullable properties. Used for (de)serialization
     *
     * @var boolean[]
     */
    protected static array $openAPINullables = [
        'id' => false,
        'transactionid' => false,
        'transacttype' => false,
        'dateadded' => false,
        'money' => false,
        'ispayed' => false,
        'datepayed' => true,
        'isunpayable' => false,
        'txt' => false,
        'refund_note' => false,
        'paid_currency' => false,
        'companyid' => false,
        'description' => false
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
        'transactionid' => 'transactionid',
        'transacttype' => 'transacttype',
        'dateadded' => 'dateadded',
        'money' => 'money',
        'ispayed' => 'ispayed',
        'datepayed' => 'datepayed',
        'isunpayable' => 'isunpayable',
        'txt' => 'txt',
        'refund_note' => 'refund_note',
        'paid_currency' => 'paid_currency',
        'companyid' => 'companyid',
        'description' => 'description'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'id' => 'setId',
        'transactionid' => 'setTransactionid',
        'transacttype' => 'setTransacttype',
        'dateadded' => 'setDateadded',
        'money' => 'setMoney',
        'ispayed' => 'setIspayed',
        'datepayed' => 'setDatepayed',
        'isunpayable' => 'setIsunpayable',
        'txt' => 'setTxt',
        'refund_note' => 'setRefundNote',
        'paid_currency' => 'setPaidCurrency',
        'companyid' => 'setCompanyid',
        'description' => 'setDescription'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'id' => 'getId',
        'transactionid' => 'getTransactionid',
        'transacttype' => 'getTransacttype',
        'dateadded' => 'getDateadded',
        'money' => 'getMoney',
        'ispayed' => 'getIspayed',
        'datepayed' => 'getDatepayed',
        'isunpayable' => 'getIsunpayable',
        'txt' => 'getTxt',
        'refund_note' => 'getRefundNote',
        'paid_currency' => 'getPaidCurrency',
        'companyid' => 'getCompanyid',
        'description' => 'getDescription'
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

    public const ISPAYED_NUMBER_0 = 0;
    public const ISPAYED_NUMBER_1 = 1;
    public const ISUNPAYABLE_NUMBER_0 = 0;
    public const ISUNPAYABLE_NUMBER_1 = 1;

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getIspayedAllowableValues()
    {
        return [
            self::ISPAYED_NUMBER_0,
            self::ISPAYED_NUMBER_1,
        ];
    }

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getIsunpayableAllowableValues()
    {
        return [
            self::ISUNPAYABLE_NUMBER_0,
            self::ISUNPAYABLE_NUMBER_1,
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
        $this->setIfExists('transactionid', $data ?? [], null);
        $this->setIfExists('transacttype', $data ?? [], null);
        $this->setIfExists('dateadded', $data ?? [], null);
        $this->setIfExists('money', $data ?? [], null);
        $this->setIfExists('ispayed', $data ?? [], null);
        $this->setIfExists('datepayed', $data ?? [], null);
        $this->setIfExists('isunpayable', $data ?? [], null);
        $this->setIfExists('txt', $data ?? [], null);
        $this->setIfExists('refund_note', $data ?? [], null);
        $this->setIfExists('paid_currency', $data ?? [], null);
        $this->setIfExists('companyid', $data ?? [], null);
        $this->setIfExists('description', $data ?? [], null);
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

        $allowedValues = $this->getIspayedAllowableValues();
        if (!is_null($this->container['ispayed']) && !in_array($this->container['ispayed'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'ispayed', must be one of '%s'",
                $this->container['ispayed'],
                implode("', '", $allowedValues)
            );
        }

        $allowedValues = $this->getIsunpayableAllowableValues();
        if (!is_null($this->container['isunpayable']) && !in_array($this->container['isunpayable'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'isunpayable', must be one of '%s'",
                $this->container['isunpayable'],
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
     * Gets transactionid
     *
     * @return int|null
     */
    public function getTransactionid()
    {
        return $this->container['transactionid'];
    }

    /**
     * Sets transactionid
     *
     * @param int|null $transactionid Per-account payout / invoice number.
     *
     * @return self
     */
    public function setTransactionid($transactionid)
    {
        if (is_null($transactionid)) {
            throw new \InvalidArgumentException('non-nullable transactionid cannot be null');
        }
        $this->container['transactionid'] = $transactionid;

        return $this;
    }

    /**
     * Gets transacttype
     *
     * @return int|null
     */
    public function getTransacttype()
    {
        return $this->container['transacttype'];
    }

    /**
     * Sets transacttype
     *
     * @param int|null $transacttype Payout method id; 0 = transfer to advertiser budget.
     *
     * @return self
     */
    public function setTransacttype($transacttype)
    {
        if (is_null($transacttype)) {
            throw new \InvalidArgumentException('non-nullable transacttype cannot be null');
        }
        $this->container['transacttype'] = $transacttype;

        return $this;
    }

    /**
     * Gets dateadded
     *
     * @return \DateTime|null
     */
    public function getDateadded()
    {
        return $this->container['dateadded'];
    }

    /**
     * Sets dateadded
     *
     * @param \DateTime|null $dateadded dateadded
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
     * @param int|null $money Amount, cents.
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
     * Gets ispayed
     *
     * @return int|null
     */
    public function getIspayed()
    {
        return $this->container['ispayed'];
    }

    /**
     * Sets ispayed
     *
     * @param int|null $ispayed ispayed
     *
     * @return self
     */
    public function setIspayed($ispayed)
    {
        if (is_null($ispayed)) {
            throw new \InvalidArgumentException('non-nullable ispayed cannot be null');
        }
        $allowedValues = $this->getIspayedAllowableValues();
        if (!in_array($ispayed, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'ispayed', must be one of '%s'",
                    $ispayed,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['ispayed'] = $ispayed;

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
     * @param string|null $datepayed datepayed
     *
     * @return self
     */
    public function setDatepayed($datepayed)
    {
        if (is_null($datepayed)) {
            array_push($this->openAPINullablesSetToNull, 'datepayed');
        } else {
            $nullablesSetToNull = $this->getOpenAPINullablesSetToNull();
            $index = array_search('datepayed', $nullablesSetToNull);
            if ($index !== FALSE) {
                unset($nullablesSetToNull[$index]);
                $this->setOpenAPINullablesSetToNull($nullablesSetToNull);
            }
        }
        $this->container['datepayed'] = $datepayed;

        return $this;
    }

    /**
     * Gets isunpayable
     *
     * @return int|null
     */
    public function getIsunpayable()
    {
        return $this->container['isunpayable'];
    }

    /**
     * Sets isunpayable
     *
     * @param int|null $isunpayable 1 = staff marked the request as not payable (e.g. unvalidated payment details).
     *
     * @return self
     */
    public function setIsunpayable($isunpayable)
    {
        if (is_null($isunpayable)) {
            throw new \InvalidArgumentException('non-nullable isunpayable cannot be null');
        }
        $allowedValues = $this->getIsunpayableAllowableValues();
        if (!in_array($isunpayable, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'isunpayable', must be one of '%s'",
                    $isunpayable,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['isunpayable'] = $isunpayable;

        return $this;
    }

    /**
     * Gets txt
     *
     * @return string|null
     */
    public function getTxt()
    {
        return $this->container['txt'];
    }

    /**
     * Sets txt
     *
     * @param string|null $txt txt
     *
     * @return self
     */
    public function setTxt($txt)
    {
        if (is_null($txt)) {
            throw new \InvalidArgumentException('non-nullable txt cannot be null');
        }
        $this->container['txt'] = $txt;

        return $this;
    }

    /**
     * Gets refund_note
     *
     * @return string|null
     */
    public function getRefundNote()
    {
        return $this->container['refund_note'];
    }

    /**
     * Sets refund_note
     *
     * @param string|null $refund_note refund_note
     *
     * @return self
     */
    public function setRefundNote($refund_note)
    {
        if (is_null($refund_note)) {
            throw new \InvalidArgumentException('non-nullable refund_note cannot be null');
        }
        $this->container['refund_note'] = $refund_note;

        return $this;
    }

    /**
     * Gets paid_currency
     *
     * @return string|null
     */
    public function getPaidCurrency()
    {
        return $this->container['paid_currency'];
    }

    /**
     * Sets paid_currency
     *
     * @param string|null $paid_currency paid_currency
     *
     * @return self
     */
    public function setPaidCurrency($paid_currency)
    {
        if (is_null($paid_currency)) {
            throw new \InvalidArgumentException('non-nullable paid_currency cannot be null');
        }
        $this->container['paid_currency'] = $paid_currency;

        return $this;
    }

    /**
     * Gets companyid
     *
     * @return int|null
     */
    public function getCompanyid()
    {
        return $this->container['companyid'];
    }

    /**
     * Sets companyid
     *
     * @param int|null $companyid companyid
     *
     * @return self
     */
    public function setCompanyid($companyid)
    {
        if (is_null($companyid)) {
            throw new \InvalidArgumentException('non-nullable companyid cannot be null');
        }
        $this->container['companyid'] = $companyid;

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


