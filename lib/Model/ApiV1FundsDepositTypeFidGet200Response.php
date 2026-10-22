<?php
/**
 * ApiV1FundsDepositTypeFidGet200Response
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
 * ApiV1FundsDepositTypeFidGet200Response Class Doc Comment
 *
 * @category Class
 * @package  GoAdServerApi
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<string, mixed>
 */
class ApiV1FundsDepositTypeFidGet200Response implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
     * The original name of the model.
     *
     * @var string
     */
    protected static $openAPIModelName = '_api_v1_funds_deposit__type___fid__get_200_response';

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @var string[]
     */
    protected static $openAPITypes = [
        'view' => 'string',
        'url' => 'string',
        'deposit_id' => 'int',
        'transaction_id' => 'int',
        'money' => 'int',
        'totalmoney' => 'int',
        'used_currency' => 'string',
        'bank_details' => 'string[]',
        'payee' => 'string',
        'wallet_id' => 'string',
        'wallet_network' => 'string',
        'accepts_txn_hash' => 'bool',
        'txn_ref_kind' => 'string',
        'accepts_proof' => 'bool',
        'proof' => '\GoAdServerApi\Model\FundsDepositProof',
        'txn_claim' => '\GoAdServerApi\Model\FundsTxnClaim',
        'invoice_paid' => 'bool',
        'invoice_in_progress' => 'bool',
        'unknown_invoice' => 'bool'
    ];

    /**
     * Array of property to format mappings. Used for (de)serialization
     *
     * @var string[]
     * @phpstan-var array<string, string|null>
     * @psalm-var array<string, string|null>
     */
    protected static $openAPIFormats = [
        'view' => null,
        'url' => null,
        'deposit_id' => null,
        'transaction_id' => null,
        'money' => null,
        'totalmoney' => null,
        'used_currency' => null,
        'bank_details' => null,
        'payee' => null,
        'wallet_id' => null,
        'wallet_network' => null,
        'accepts_txn_hash' => null,
        'txn_ref_kind' => null,
        'accepts_proof' => null,
        'proof' => null,
        'txn_claim' => null,
        'invoice_paid' => null,
        'invoice_in_progress' => null,
        'unknown_invoice' => null
    ];

    /**
     * Array of nullable properties. Used for (de)serialization
     *
     * @var boolean[]
     */
    protected static array $openAPINullables = [
        'view' => false,
        'url' => false,
        'deposit_id' => false,
        'transaction_id' => false,
        'money' => false,
        'totalmoney' => false,
        'used_currency' => false,
        'bank_details' => false,
        'payee' => false,
        'wallet_id' => false,
        'wallet_network' => false,
        'accepts_txn_hash' => false,
        'txn_ref_kind' => false,
        'accepts_proof' => false,
        'proof' => false,
        'txn_claim' => false,
        'invoice_paid' => false,
        'invoice_in_progress' => false,
        'unknown_invoice' => false
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
        'view' => 'view',
        'url' => 'url',
        'deposit_id' => 'deposit_id',
        'transaction_id' => 'transaction_id',
        'money' => 'money',
        'totalmoney' => 'totalmoney',
        'used_currency' => 'used_currency',
        'bank_details' => 'bank_details',
        'payee' => 'payee',
        'wallet_id' => 'wallet_id',
        'wallet_network' => 'wallet_network',
        'accepts_txn_hash' => 'accepts_txn_hash',
        'txn_ref_kind' => 'txn_ref_kind',
        'accepts_proof' => 'accepts_proof',
        'proof' => 'proof',
        'txn_claim' => 'txn_claim',
        'invoice_paid' => 'invoice_paid',
        'invoice_in_progress' => 'invoice_in_progress',
        'unknown_invoice' => 'unknown_invoice'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'view' => 'setView',
        'url' => 'setUrl',
        'deposit_id' => 'setDepositId',
        'transaction_id' => 'setTransactionId',
        'money' => 'setMoney',
        'totalmoney' => 'setTotalmoney',
        'used_currency' => 'setUsedCurrency',
        'bank_details' => 'setBankDetails',
        'payee' => 'setPayee',
        'wallet_id' => 'setWalletId',
        'wallet_network' => 'setWalletNetwork',
        'accepts_txn_hash' => 'setAcceptsTxnHash',
        'txn_ref_kind' => 'setTxnRefKind',
        'accepts_proof' => 'setAcceptsProof',
        'proof' => 'setProof',
        'txn_claim' => 'setTxnClaim',
        'invoice_paid' => 'setInvoicePaid',
        'invoice_in_progress' => 'setInvoiceInProgress',
        'unknown_invoice' => 'setUnknownInvoice'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'view' => 'getView',
        'url' => 'getUrl',
        'deposit_id' => 'getDepositId',
        'transaction_id' => 'getTransactionId',
        'money' => 'getMoney',
        'totalmoney' => 'getTotalmoney',
        'used_currency' => 'getUsedCurrency',
        'bank_details' => 'getBankDetails',
        'payee' => 'getPayee',
        'wallet_id' => 'getWalletId',
        'wallet_network' => 'getWalletNetwork',
        'accepts_txn_hash' => 'getAcceptsTxnHash',
        'txn_ref_kind' => 'getTxnRefKind',
        'accepts_proof' => 'getAcceptsProof',
        'proof' => 'getProof',
        'txn_claim' => 'getTxnClaim',
        'invoice_paid' => 'getInvoicePaid',
        'invoice_in_progress' => 'getInvoiceInProgress',
        'unknown_invoice' => 'getUnknownInvoice'
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

    public const TXN_REF_KIND_HASH = 'hash';
    public const TXN_REF_KIND_REFERENCE = 'reference';

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getTxnRefKindAllowableValues()
    {
        return [
            self::TXN_REF_KIND_HASH,
            self::TXN_REF_KIND_REFERENCE,
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
        $this->setIfExists('view', $data ?? [], null);
        $this->setIfExists('url', $data ?? [], null);
        $this->setIfExists('deposit_id', $data ?? [], null);
        $this->setIfExists('transaction_id', $data ?? [], null);
        $this->setIfExists('money', $data ?? [], null);
        $this->setIfExists('totalmoney', $data ?? [], null);
        $this->setIfExists('used_currency', $data ?? [], null);
        $this->setIfExists('bank_details', $data ?? [], null);
        $this->setIfExists('payee', $data ?? [], null);
        $this->setIfExists('wallet_id', $data ?? [], null);
        $this->setIfExists('wallet_network', $data ?? [], null);
        $this->setIfExists('accepts_txn_hash', $data ?? [], null);
        $this->setIfExists('txn_ref_kind', $data ?? [], null);
        $this->setIfExists('accepts_proof', $data ?? [], null);
        $this->setIfExists('proof', $data ?? [], null);
        $this->setIfExists('txn_claim', $data ?? [], null);
        $this->setIfExists('invoice_paid', $data ?? [], null);
        $this->setIfExists('invoice_in_progress', $data ?? [], null);
        $this->setIfExists('unknown_invoice', $data ?? [], null);
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

        $allowedValues = $this->getTxnRefKindAllowableValues();
        if (!is_null($this->container['txn_ref_kind']) && !in_array($this->container['txn_ref_kind'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'txn_ref_kind', must be one of '%s'",
                $this->container['txn_ref_kind'],
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
     * Gets view
     *
     * @return string|null
     */
    public function getView()
    {
        return $this->container['view'];
    }

    /**
     * Sets view
     *
     * @param string|null $view view
     *
     * @return self
     */
    public function setView($view)
    {
        if (is_null($view)) {
            throw new \InvalidArgumentException('non-nullable view cannot be null');
        }
        $this->container['view'] = $view;

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
     * @param string|null $url Hosted checkout URL (gateway types).
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
     * Gets deposit_id
     *
     * @return int|null
     */
    public function getDepositId()
    {
        return $this->container['deposit_id'];
    }

    /**
     * Sets deposit_id
     *
     * @param int|null $deposit_id deposit_id
     *
     * @return self
     */
    public function setDepositId($deposit_id)
    {
        if (is_null($deposit_id)) {
            throw new \InvalidArgumentException('non-nullable deposit_id cannot be null');
        }
        $this->container['deposit_id'] = $deposit_id;

        return $this;
    }

    /**
     * Gets transaction_id
     *
     * @return int|null
     */
    public function getTransactionId()
    {
        return $this->container['transaction_id'];
    }

    /**
     * Sets transaction_id
     *
     * @param int|null $transaction_id Invoice / transfer reference.
     *
     * @return self
     */
    public function setTransactionId($transaction_id)
    {
        if (is_null($transaction_id)) {
            throw new \InvalidArgumentException('non-nullable transaction_id cannot be null');
        }
        $this->container['transaction_id'] = $transaction_id;

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
     * @param int|null $money Cents
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
     * Gets totalmoney
     *
     * @return int|null
     */
    public function getTotalmoney()
    {
        return $this->container['totalmoney'];
    }

    /**
     * Sets totalmoney
     *
     * @param int|null $totalmoney Cents to pay.
     *
     * @return self
     */
    public function setTotalmoney($totalmoney)
    {
        if (is_null($totalmoney)) {
            throw new \InvalidArgumentException('non-nullable totalmoney cannot be null');
        }
        $this->container['totalmoney'] = $totalmoney;

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
        $this->container['used_currency'] = $used_currency;

        return $this;
    }

    /**
     * Gets bank_details
     *
     * @return string[]|null
     */
    public function getBankDetails()
    {
        return $this->container['bank_details'];
    }

    /**
     * Sets bank_details
     *
     * @param string[]|null $bank_details bank_details
     *
     * @return self
     */
    public function setBankDetails($bank_details)
    {
        if (is_null($bank_details)) {
            throw new \InvalidArgumentException('non-nullable bank_details cannot be null');
        }
        $this->container['bank_details'] = $bank_details;

        return $this;
    }

    /**
     * Gets payee
     *
     * @return string|null
     */
    public function getPayee()
    {
        return $this->container['payee'];
    }

    /**
     * Sets payee
     *
     * @param string|null $payee payee
     *
     * @return self
     */
    public function setPayee($payee)
    {
        if (is_null($payee)) {
            throw new \InvalidArgumentException('non-nullable payee cannot be null');
        }
        $this->container['payee'] = $payee;

        return $this;
    }

    /**
     * Gets wallet_id
     *
     * @return string|null
     */
    public function getWalletId()
    {
        return $this->container['wallet_id'];
    }

    /**
     * Sets wallet_id
     *
     * @param string|null $wallet_id wallet_id
     *
     * @return self
     */
    public function setWalletId($wallet_id)
    {
        if (is_null($wallet_id)) {
            throw new \InvalidArgumentException('non-nullable wallet_id cannot be null');
        }
        $this->container['wallet_id'] = $wallet_id;

        return $this;
    }

    /**
     * Gets wallet_network
     *
     * @return string|null
     */
    public function getWalletNetwork()
    {
        return $this->container['wallet_network'];
    }

    /**
     * Sets wallet_network
     *
     * @param string|null $wallet_network wallet_network
     *
     * @return self
     */
    public function setWalletNetwork($wallet_network)
    {
        if (is_null($wallet_network)) {
            throw new \InvalidArgumentException('non-nullable wallet_network cannot be null');
        }
        $this->container['wallet_network'] = $wallet_network;

        return $this;
    }

    /**
     * Gets accepts_txn_hash
     *
     * @return bool|null
     */
    public function getAcceptsTxnHash()
    {
        return $this->container['accepts_txn_hash'];
    }

    /**
     * Sets accepts_txn_hash
     *
     * @param bool|null $accepts_txn_hash Manual crypto wallet or Payoneer deposit: takes the payer's transaction hash / ID (POST /api/v1/funds/deposits/{id}/txn-hash).
     *
     * @return self
     */
    public function setAcceptsTxnHash($accepts_txn_hash)
    {
        if (is_null($accepts_txn_hash)) {
            throw new \InvalidArgumentException('non-nullable accepts_txn_hash cannot be null');
        }
        $this->container['accepts_txn_hash'] = $accepts_txn_hash;

        return $this;
    }

    /**
     * Gets txn_ref_kind
     *
     * @return string|null
     */
    public function getTxnRefKind()
    {
        return $this->container['txn_ref_kind'];
    }

    /**
     * Sets txn_ref_kind
     *
     * @param string|null $txn_ref_kind `hash` (crypto, checked on the blockchain) or `reference` (Payoneer transaction ID, stored as given).
     *
     * @return self
     */
    public function setTxnRefKind($txn_ref_kind)
    {
        if (is_null($txn_ref_kind)) {
            throw new \InvalidArgumentException('non-nullable txn_ref_kind cannot be null');
        }
        $allowedValues = $this->getTxnRefKindAllowableValues();
        if (!in_array($txn_ref_kind, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'txn_ref_kind', must be one of '%s'",
                    $txn_ref_kind,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['txn_ref_kind'] = $txn_ref_kind;

        return $this;
    }

    /**
     * Gets accepts_proof
     *
     * @return bool|null
     */
    public function getAcceptsProof()
    {
        return $this->container['accepts_proof'];
    }

    /**
     * Sets accepts_proof
     *
     * @param bool|null $accepts_proof Bank / Wise deposit on a tenant that takes a proof of payment (POST /api/v1/funds/deposits/{id}/proof).
     *
     * @return self
     */
    public function setAcceptsProof($accepts_proof)
    {
        if (is_null($accepts_proof)) {
            throw new \InvalidArgumentException('non-nullable accepts_proof cannot be null');
        }
        $this->container['accepts_proof'] = $accepts_proof;

        return $this;
    }

    /**
     * Gets proof
     *
     * @return \GoAdServerApi\Model\FundsDepositProof|null
     */
    public function getProof()
    {
        return $this->container['proof'];
    }

    /**
     * Sets proof
     *
     * @param \GoAdServerApi\Model\FundsDepositProof|null $proof proof
     *
     * @return self
     */
    public function setProof($proof)
    {
        if (is_null($proof)) {
            throw new \InvalidArgumentException('non-nullable proof cannot be null');
        }
        $this->container['proof'] = $proof;

        return $this;
    }

    /**
     * Gets txn_claim
     *
     * @return \GoAdServerApi\Model\FundsTxnClaim|null
     */
    public function getTxnClaim()
    {
        return $this->container['txn_claim'];
    }

    /**
     * Sets txn_claim
     *
     * @param \GoAdServerApi\Model\FundsTxnClaim|null $txn_claim The transaction hash attached so far and its blockchain check; null when none.
     *
     * @return self
     */
    public function setTxnClaim($txn_claim)
    {
        if (is_null($txn_claim)) {
            throw new \InvalidArgumentException('non-nullable txn_claim cannot be null');
        }
        $this->container['txn_claim'] = $txn_claim;

        return $this;
    }

    /**
     * Gets invoice_paid
     *
     * @return bool|null
     */
    public function getInvoicePaid()
    {
        return $this->container['invoice_paid'];
    }

    /**
     * Sets invoice_paid
     *
     * @param bool|null $invoice_paid invoice_paid
     *
     * @return self
     */
    public function setInvoicePaid($invoice_paid)
    {
        if (is_null($invoice_paid)) {
            throw new \InvalidArgumentException('non-nullable invoice_paid cannot be null');
        }
        $this->container['invoice_paid'] = $invoice_paid;

        return $this;
    }

    /**
     * Gets invoice_in_progress
     *
     * @return bool|null
     */
    public function getInvoiceInProgress()
    {
        return $this->container['invoice_in_progress'];
    }

    /**
     * Sets invoice_in_progress
     *
     * @param bool|null $invoice_in_progress invoice_in_progress
     *
     * @return self
     */
    public function setInvoiceInProgress($invoice_in_progress)
    {
        if (is_null($invoice_in_progress)) {
            throw new \InvalidArgumentException('non-nullable invoice_in_progress cannot be null');
        }
        $this->container['invoice_in_progress'] = $invoice_in_progress;

        return $this;
    }

    /**
     * Gets unknown_invoice
     *
     * @return bool|null
     */
    public function getUnknownInvoice()
    {
        return $this->container['unknown_invoice'];
    }

    /**
     * Sets unknown_invoice
     *
     * @param bool|null $unknown_invoice unknown_invoice
     *
     * @return self
     */
    public function setUnknownInvoice($unknown_invoice)
    {
        if (is_null($unknown_invoice)) {
            throw new \InvalidArgumentException('non-nullable unknown_invoice cannot be null');
        }
        $this->container['unknown_invoice'] = $unknown_invoice;

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


