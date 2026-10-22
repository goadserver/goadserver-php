# GoAdServerApi

> Official PHP client for the REST API of [GoAdserver](https://www.goadserver.com), the hosted ad server. API documentation and how to create an API key: [GoAdserver API docs](https://www.goadserver.com/docs/api-keys/).

Public REST API for advertisers and publishers on the GoAdServer platform.

**Authentication.** All endpoints (except `/api/public/_*`) require an API
key passed as `Authorization: Bearer gas_live_…`. Create and manage keys
in your account settings — each key has its own scopes, optional resource
pinning, optional IP allowlist, and per-key rate limit.

**Apps (OAuth 2.0).** A tool other people connect uses an app token
instead: authorization code + PKCE at `/oauth/authorize`, tokens from
`/api/public/oauth/token`, passed as `Authorization: Bearer oat_…` on the
same endpoints with the same scopes. See the
[Build an integration](/api/docs/integrations) guide.

**System rules apply over API rules.** The public API never lets a key
bypass restrictions the same user faces in the panel:

- **Source names** (`stats_source_names` right). When the user lacks
  this right, advertiser stats responses return `{id, name}` with
  `name` equal to the id — no human label leaks through.
- **SubID encoding** (`modules.global.subid_encoding`). When enabled
  for the system, the user only ever sees encoded SubIDs in the panel.
  The API mirrors this both ways: `subid_excl`/`subid_incl` filter
  endpoints accept the encoded form on write (decoded server-side
  before storage) and emit the encoded form on read. A plain SubID
  sent on write is stored as sent — a value is decoded only when it is
  a genuine encoded token.
- **Module gating.** Endpoints whose module is disabled for the
  tenant don't appear in the spec or routes.txt at all.

**Rate limits.** Every response to an API key or app token carries the
caller's budget for the current one-minute window:

| Header | Meaning |
|---|---|
| `X-RateLimit-Limit` | Requests per minute for this key (its `rate_limit_rpm`, default 60; an app's per-installation limit for app tokens). |
| `X-RateLimit-Remaining` | Calls left in the current window. |
| `X-RateLimit-Reset` | Unix seconds at which the window resets. |
| `Retry-After` | On `429 rate_limited` only: seconds until a retry can succeed. |

Windows are fixed and start with the first call, so `Reset` is exact. The
MCP endpoint (`POST /api/v1/mcp`) is charged per `tools/call` against its own
bucket (default 240/min), and its response headers report that bucket; an
exhausted MCP bucket answers the tool call with an error result rather than
an HTTP 429, and the HTTP response then carries `Retry-After`.

**Idempotent writes.** Send `Idempotency-Key: <1–255 printable ASCII
characters>` (a UUID is ideal) on a `POST` or `PATCH` to make a retry safe.
For 24 hours per key (per app installation for app tokens):

- the first request runs and its response — status, body, `Content-Type`,
  `Location`, `ETag` — is stored;
- the same key with the same method, path, query and body replays that
  response without running anything, adding `Idempotent-Replayed: true`;
- the same key with a different request is `422 idempotency_key_reused`;
- the same key while the first request is still running is
  `409 idempotency_key_in_progress` (with `Retry-After: 1`).

`5xx` results are not stored — the key is released and a retry runs again.
Bodies over 32 MB cannot carry the header (`413`). An invalid key is
`400 invalid_idempotency_key`.

**Concurrency control (ETag / If-Match).** Every JSON `GET` response (up to
1 MB) carries a weak `ETag`, a hash of the body. Send it back:

- `If-None-Match: <etag>` on a `GET` — `304 Not Modified` when unchanged;
- `If-Match: <etag>` on a `PATCH`, `PUT` or `DELETE` — the server reads the
  resource's current representation (the `GET` of the same path, or of the
  nearest parent path that has a `GET`: `PATCH …/campaigns/ron/7/active` is
  checked against `GET …/campaigns/ron/7`) and answers
  `412 precondition_failed` when it no longer matches, with the current tag in
  `ETag` and `current_etag`. `If-Match: *` only requires the resource to
  exist. A resource with no readable representation is also `412`.

The check is optimistic, not a lock: it closes the read-modify-write window
two agents (or an agent and a person in the panel) would otherwise race in.
Fields that change on their own (live spend counters) change the tag too —
re-read and retry. A write response carries no new ETag; `GET` again.

**Strict fields.** Unknown body fields are a client error, not something to
ignore: a request that sends a field the endpoint does not accept gets
`422 unknown_fields` naming it. This is the default for keys created from
2026-10-12 (the key's `strict_fields` setting, shown on the key screen);
older keys keep ignoring unknown fields (except on the few endpoints that
have always refused them — the budget and on/off toggles). Override per
request with `?strict=1` (reject) or `?strict=0` (ignore, everywhere). App
tokens default to ignoring unknown fields; `?strict=1` opts in.

**Errors.** Every non-2xx response has the same shape:

```json
{ \"error\": \"validation_error\", \"message\": \"Name is required.\", \"errors\": { \"name\": [\"Name is required.\"] } }
```

`error` is a stable machine-readable code — branch on it, never on
`message`. `errors` (field → messages) appears on field-level validation
failures; some codes add their own members (`scope`, `accepted_scopes`,
`unknown_fields`, `current_etag`). Codes:

| Status | Codes |
|---|---|
| 400 | `bad_request`, `invalid_idempotency_key`, `invalid_body` |
| 401 | `unauthenticated`, `invalid_api_key`, `revoked_api_key`, `expired_api_key`, `invalid_token`, `revoked_token`, `expired_token`, `user_not_found`, `account_deleted`, `account_disabled`, `admin_key_on_user_api`, `user_key_on_admin_api` |
| 403 | `forbidden`, `missing_scope`, `forbidden_resource`, `ip_not_allowed`, `api_disabled`, `systemid_mismatch`, `endpoint_not_api_accessible`, `oauth_token_wrong_surface`, `mcp_not_enabled_for_app` |
| 404 | `not_found` (also a disabled module or missing right — see ModuleDisabled) |
| 405 | `method_not_allowed` — the `Allow` header (and the message) lists the methods the path does accept |
| 409 | `conflict`, `idempotency_key_in_progress`, and resource-specific conflicts (e.g. `email_exists`) |
| 412 | `precondition_failed` |
| 413 | `payload_too_large` |
| 422 | `validation_error`, `unknown_fields`, `idempotency_key_reused` |
| 429 | `rate_limited` |
| 5xx | `server_error`, `service_unavailable`, `bad_gateway`, `gateway_timeout` |

Endpoint-specific codes are listed on the endpoint. A response that would
otherwise lack a code gets the generic one for its status from this table.

**Client SDKs.** Each SDK is generated from this spec and published as
a public GitHub repository under the `goadserver` organization. The
same tarball is also embedded in the API server and downloadable from
`/api/public/sdk/<lang>.tgz` — useful when GitHub is unreachable.

> **Per-network host.** The default `https://up.go-adserver.com`
> baked into the published SDKs is a reference host. Every white-label
> tenant runs on its own panel domain — override the host on first use
> (`setHost()` / `Configuration.host` / `basePath` etc.). Or, fetch the
> spec from your own tenant (`/api/public/openapi.yaml` returns it with
> *your* hostname as the default) and regenerate the SDK locally — the
> regenerated client has your URL baked in.

- **PHP** — `github.com/goadserver/goadserver-php`
  (namespace `GoAdServer\\Api`, composer name `goadserver/sdk`).

  ```bash
  composer config repositories.goadserver vcs https://github.com/goadserver/goadserver-php
  composer require goadserver/sdk:dev-main
  ```

  ```php
  $cfg = GoAdServer\\Api\\Configuration::getDefaultConfiguration()
          ->setHost('https://up.go-adserver.com')
          ->setAccessToken('gas_live_…');
  $api = new GoAdServer\\Api\\Api\\AccountAPIApi(null, $cfg);
  $me  = $api->apiV1AccountGet();
  ```

- **Go** — `github.com/goadserver/goadserver-go`.

  ```bash
  go get github.com/goadserver/goadserver-go@latest
  ```

  ```go
  import gas \"github.com/goadserver/goadserver-go\"

  cfg := gas.NewConfiguration()
  cfg.Servers = gas.ServerConfigurations{{URL: \"https://up.go-adserver.com\"}}
  cli := gas.NewAPIClient(cfg)
  ctx := context.WithValue(context.Background(),
      gas.ContextAccessToken, \"gas_live_…\")
  me, _, err := cli.AccountAPI.ApiV1AccountGet(ctx).Execute()
  ```

- **Python** — `github.com/goadserver/goadserver-python` (package
  `goadserver`). Pulls `pydantic` + `urllib3` automatically.

  ```bash
  pip install git+https://github.com/goadserver/goadserver-python.git
  ```

  ```python
  from goadserver import ApiClient, Configuration
  from goadserver.api import AccountApi, CampaignsApi

  cfg = Configuration(host=\"https://up.go-adserver.com\")
  cfg.access_token = \"gas_live_…\"
  client = ApiClient(cfg)

  me = AccountApi(client).api_v1_account_get()
  camps = CampaignsApi(client).api_v1_campaigns_get(per_page=10)
  ```

- **JavaScript / TypeScript** — `github.com/goadserver/goadserver-js`
  (npm name `goadserver-sdk`, generator `typescript-fetch`).

  ```bash
  npm install github:goadserver/goadserver-js
  ```

  ```ts
  import { Configuration, AccountApi, CampaignsApi }
    from \"goadserver-sdk\";

  const cfg = new Configuration({
    basePath: \"https://up.go-adserver.com\",
    accessToken: \"gas_live_…\",
  });

  const me   = await new AccountApi(cfg).apiV1AccountGet();
  const list = await new CampaignsApi(cfg).apiV1CampaignsGet({ perPage: 10 });
  ```

  The CJS build is broadly compatible (Node + bundlers); the ESM
  build is missing trailing `.js` extensions in its imports, a known
  `typescript-fetch` quirk — most consumers use CJS.

**Offline / firewalled install.** If the customer's network can't
reach GitHub, every SDK's source is also served as a tarball from the
API host:

```bash
curl -O https://up.go-adserver.com/api/public/sdk/python.tgz
tar xzf python.tgz && pip install ./python
```

Round-trip smoke tests for all four SDKs live at `clients/_smoke/`
in the platform repo.



## Installation & Usage

### Requirements

PHP 8.1 and later.

### Composer

To install the bindings via [Composer](https://getcomposer.org/), add the following to `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/goadserver/goadserver-php.git"
    }
  ],
  "require": {
    "goadserver/goadserver-php": "*@dev"
  }
}
```

Then run `composer install`

### Manual Installation

Download the files and include `autoload.php`:

```php
<?php
require_once('/path/to/GoAdServerApi/vendor/autoload.php');
```

## Getting Started

Please follow the [installation procedure](#installation--usage) and then run the following:

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

## API Endpoints

All URIs are relative to *https://up.go-adserver.com*

Class | Method | HTTP request | Description
------------ | ------------- | ------------- | -------------
*AIAssistantApi* | [**apiV1McpPost**](docs/Api/AIAssistantApi.md#apiv1mcppost) | **POST** /api/v1/mcp | MCP endpoint — connect an AI assistant to this account.
*AccountApi* | [**apiV1AccountFeaturesGet**](docs/Api/AccountApi.md#apiv1accountfeaturesget) | **GET** /api/v1/account/features | Self-service features and their state.
*AccountApi* | [**apiV1AccountGet**](docs/Api/AccountApi.md#apiv1accountget) | **GET** /api/v1/account | Identity check — returns the authenticated user + scopes of the calling key.
*AccountApi* | [**apiV1AccountMembershipsGet**](docs/Api/AccountApi.md#apiv1accountmembershipsget) | **GET** /api/v1/account/memberships | Accounts this login can act on.
*AccountApi* | [**apiV1AccountNoticesGet**](docs/Api/AccountApi.md#apiv1accountnoticesget) | **GET** /api/v1/account/notices | Account notices.
*AccountApi* | [**apiV1AccountPendingChangesGet**](docs/Api/AccountApi.md#apiv1accountpendingchangesget) | **GET** /api/v1/account/pending-changes | Profile changes awaiting verification.
*AccountApi* | [**apiV1AccountPreferencesGet**](docs/Api/AccountApi.md#apiv1accountpreferencesget) | **GET** /api/v1/account/preferences | Per-member preferences.
*AccountApi* | [**apiV1AccountPreferencesKeyPut**](docs/Api/AccountApi.md#apiv1accountpreferenceskeyput) | **PUT** /api/v1/account/preferences/{key} | Set one preference.
*AccountApi* | [**apiV1AccountProfileGet**](docs/Api/AccountApi.md#apiv1accountprofileget) | **GET** /api/v1/account/profile | The account&#39;s contact and company details.
*AccountApi* | [**apiV1AccountSalesReferralsGet**](docs/Api/AccountApi.md#apiv1accountsalesreferralsget) | **GET** /api/v1/account/sales-referrals | Your sales referrals — clients assigned to you, their deposits and your commission.
*AccountApi* | [**apiV1AccountSettingsGet**](docs/Api/AccountApi.md#apiv1accountsettingsget) | **GET** /api/v1/account/settings | Account settings.
*AccountApi* | [**apiV1AccountUiLayoutsGet**](docs/Api/AccountApi.md#apiv1accountuilayoutsget) | **GET** /api/v1/account/ui-layouts | Saved menu layouts.
*AccountApi* | [**apiV1AccountUiLayoutsScopeDelete**](docs/Api/AccountApi.md#apiv1accountuilayoutsscopedelete) | **DELETE** /api/v1/account/ui-layouts/{scope} | Reset a menu layout.
*AccountApi* | [**apiV1AccountUiLayoutsScopePut**](docs/Api/AccountApi.md#apiv1accountuilayoutsscopeput) | **PUT** /api/v1/account/ui-layouts/{scope} | Save a menu layout.
*AccountApi* | [**apiV1AccountUsageGet**](docs/Api/AccountApi.md#apiv1accountusageget) | **GET** /api/v1/account/usage | Metered usage for a period.
*AccountApi* | [**apiV1AccountUsageSummaryGet**](docs/Api/AccountApi.md#apiv1accountusagesummaryget) | **GET** /api/v1/account/usage-summary | Inventory counts at a glance.
*AccountBillingApi* | [**apiV1AccountBillingAffiliatedPaymentsGet**](docs/Api/AccountBillingApi.md#apiv1accountbillingaffiliatedpaymentsget) | **GET** /api/v1/account/billing/affiliated-payments | Payments made to affiliated accounts.
*AccountBillingApi* | [**apiV1AccountBillingAveragesGet**](docs/Api/AccountBillingApi.md#apiv1accountbillingaveragesget) | **GET** /api/v1/account/billing/averages | Average costs and overcharges.
*AccountBillingApi* | [**apiV1AccountBillingInvoicesGet**](docs/Api/AccountBillingApi.md#apiv1accountbillinginvoicesget) | **GET** /api/v1/account/billing/invoices | List invoices.
*AccountBillingApi* | [**apiV1AccountBillingInvoicesIdPdfGet**](docs/Api/AccountBillingApi.md#apiv1accountbillinginvoicesidpdfget) | **GET** /api/v1/account/billing/invoices/{id}/pdf | Download an invoice PDF.
*AccountBillingApi* | [**apiV1AccountBillingSettingsGet**](docs/Api/AccountBillingApi.md#apiv1accountbillingsettingsget) | **GET** /api/v1/account/billing/settings | The account&#39;s billing plan.
*AccountNotificationsApi* | [**apiV1AccountNotificationDestinationsGet**](docs/Api/AccountNotificationsApi.md#apiv1accountnotificationdestinationsget) | **GET** /api/v1/account/notification-destinations | The account&#39;s notification destinations.
*AccountNotificationsApi* | [**apiV1AccountNotificationDestinationsIdDelete**](docs/Api/AccountNotificationsApi.md#apiv1accountnotificationdestinationsiddelete) | **DELETE** /api/v1/account/notification-destinations/{id} | Delete a notification destination.
*AccountNotificationsApi* | [**apiV1AccountNotificationDestinationsIdStatusGet**](docs/Api/AccountNotificationsApi.md#apiv1accountnotificationdestinationsidstatusget) | **GET** /api/v1/account/notification-destinations/{id}/status | Whether a destination is linked.
*AccountNotificationsApi* | [**apiV1AccountNotificationDestinationsIdTestPost**](docs/Api/AccountNotificationsApi.md#apiv1accountnotificationdestinationsidtestpost) | **POST** /api/v1/account/notification-destinations/{id}/test | Send a test message to a destination.
*AccountNotificationsApi* | [**apiV1AccountNotificationDestinationsPost**](docs/Api/AccountNotificationsApi.md#apiv1accountnotificationdestinationspost) | **POST** /api/v1/account/notification-destinations | Add a notification destination.
*AccountNotificationsApi* | [**apiV1AccountNotificationPreferencesGet**](docs/Api/AccountNotificationsApi.md#apiv1accountnotificationpreferencesget) | **GET** /api/v1/account/notification-preferences | Per-event channel preferences.
*AccountNotificationsApi* | [**apiV1AccountNotificationPreferencesPut**](docs/Api/AccountNotificationsApi.md#apiv1accountnotificationpreferencesput) | **PUT** /api/v1/account/notification-preferences | Save channel preferences.
*AccountNotificationsApi* | [**apiV1AccountNotificationsGet**](docs/Api/AccountNotificationsApi.md#apiv1accountnotificationsget) | **GET** /api/v1/account/notifications | The in-app notification feed.
*AccountNotificationsApi* | [**apiV1AccountNotificationsReadPost**](docs/Api/AccountNotificationsApi.md#apiv1accountnotificationsreadpost) | **POST** /api/v1/account/notifications/read | Mark notifications read.
*AccountNotificationsApi* | [**apiV1AccountNotificationsUnreadCountGet**](docs/Api/AccountNotificationsApi.md#apiv1accountnotificationsunreadcountget) | **GET** /api/v1/account/notifications/unread-count | Unread notification count.
*AdScannerApi* | [**apiV1AdScannerGet**](docs/Api/AdScannerApi.md#apiv1adscannerget) | **GET** /api/v1/ad-scanner | List scan tasks.
*AdScannerApi* | [**apiV1AdScannerIdDelete**](docs/Api/AdScannerApi.md#apiv1adscanneriddelete) | **DELETE** /api/v1/ad-scanner/{id} | Delete a scan task.
*AdScannerApi* | [**apiV1AdScannerIdIgnoreDomainPut**](docs/Api/AdScannerApi.md#apiv1adscanneridignoredomainput) | **PUT** /api/v1/ad-scanner/{id}/ignore-domain | Ignore (or un-ignore) a flagged domain on a task.
*AdScannerApi* | [**apiV1AdScannerIdManualItemsGet**](docs/Api/AdScannerApi.md#apiv1adscanneridmanualitemsget) | **GET** /api/v1/ad-scanner/{id}/manual-items | List a manual task&#39;s items.
*AdScannerApi* | [**apiV1AdScannerIdManualItemsItemIdStatePut**](docs/Api/AdScannerApi.md#apiv1adscanneridmanualitemsitemidstateput) | **PUT** /api/v1/ad-scanner/{id}/manual-items/{itemId}/state | Pause, resume or delete a manual item.
*AdScannerApi* | [**apiV1AdScannerIdManualItemsPost**](docs/Api/AdScannerApi.md#apiv1adscanneridmanualitemspost) | **POST** /api/v1/ad-scanner/{id}/manual-items | Add or edit a manual item.
*AdScannerApi* | [**apiV1AdScannerIdPut**](docs/Api/AdScannerApi.md#apiv1adscanneridput) | **PUT** /api/v1/ad-scanner/{id} | Replace a task&#39;s name, tags and config.
*AdScannerApi* | [**apiV1AdScannerIdToggleActivePut**](docs/Api/AdScannerApi.md#apiv1adscanneridtoggleactiveput) | **PUT** /api/v1/ad-scanner/{id}/toggle-active | Start or stop a scan task.
*AdScannerApi* | [**apiV1AdScannerPost**](docs/Api/AdScannerApi.md#apiv1adscannerpost) | **POST** /api/v1/ad-scanner | Create a scan task.
*AdScannerApi* | [**apiV1AdScannerReportHashGet**](docs/Api/AdScannerApi.md#apiv1adscannerreporthashget) | **GET** /api/v1/ad-scanner/report/{hash} | One scan&#39;s full report.
*AdScannerApi* | [**apiV1AdScannerReportsGet**](docs/Api/AdScannerApi.md#apiv1adscannerreportsget) | **GET** /api/v1/ad-scanner/reports | List scan results.
*AdScannerApi* | [**apiV1AdScannerStatsGet**](docs/Api/AdScannerApi.md#apiv1adscannerstatsget) | **GET** /api/v1/ad-scanner/stats | Scan volume and proxy bandwidth.
*AdScannerApi* | [**apiV1AdScannerTagsGet**](docs/Api/AdScannerApi.md#apiv1adscannertagsget) | **GET** /api/v1/ad-scanner/tags | Tags used on the account&#39;s scan tasks.
*AdShopApi* | [**apiV1AdShopCartGet**](docs/Api/AdShopApi.md#apiv1adshopcartget) | **GET** /api/v1/ad-shop/cart | Read the draft cart.
*AdShopApi* | [**apiV1AdShopCartPost**](docs/Api/AdShopApi.md#apiv1adshopcartpost) | **POST** /api/v1/ad-shop/cart | Replace the draft cart.
*AdShopApi* | [**apiV1AdShopConfirmPost**](docs/Api/AdShopApi.md#apiv1adshopconfirmpost) | **POST** /api/v1/ad-shop/confirm | Check out the cart — creates the campaigns.
*AdShopApi* | [**apiV1AdShopFilterOptionsGet**](docs/Api/AdShopApi.md#apiv1adshopfilteroptionsget) | **GET** /api/v1/ad-shop/filter-options | Filter values present in the current snapshot.
*AdShopApi* | [**apiV1AdShopGroupSummaryPost**](docs/Api/AdShopApi.md#apiv1adshopgroupsummarypost) | **POST** /api/v1/ad-shop/group-summary | Group the catalog by targeting dimensions.
*AdShopApi* | [**apiV1AdShopOpportunitiesGet**](docs/Api/AdShopApi.md#apiv1adshopopportunitiesget) | **GET** /api/v1/ad-shop/opportunities | Browse Ad Shop opportunities.
*AdShopApi* | [**apiV1AdShopPlanGet**](docs/Api/AdShopApi.md#apiv1adshopplanget) | **GET** /api/v1/ad-shop/plan | Preview how the cart becomes campaigns.
*AdsApi* | [**apiV1CampaignsTypeCampaignIdAdsMassUndeletePost**](docs/Api/AdsApi.md#apiv1campaignstypecampaignidadsmassundeletepost) | **POST** /api/v1/campaigns/{type}/{campaignId}/ads/mass-undelete | Restore archived ads of a campaign.
*AdspacesApi* | [**apiV1AdspacesAddMultiplePost**](docs/Api/AdspacesApi.md#apiv1adspacesaddmultiplepost) | **POST** /api/v1/adspaces/add-multiple | Create several ad zones of one type on one site.
*AdspacesApi* | [**apiV1AdspacesExportCodesGet**](docs/Api/AdspacesApi.md#apiv1adspacesexportcodesget) | **GET** /api/v1/adspaces/export-codes | Embed codes for many ad zones at once, grouped by site.
*AdspacesApi* | [**apiV1AdspacesGet**](docs/Api/AdspacesApi.md#apiv1adspacesget) | **GET** /api/v1/adspaces | List ad zones owned by the caller.
*AdspacesApi* | [**apiV1AdspacesIdBlockedAdvertisersGet**](docs/Api/AdspacesApi.md#apiv1adspacesidblockedadvertisersget) | **GET** /api/v1/adspaces/{id}/blocked-advertisers | Advertiser block filters that exclude this ad zone.
*AdspacesApi* | [**apiV1AdspacesIdChildrenGet**](docs/Api/AdspacesApi.md#apiv1adspacesidchildrenget) | **GET** /api/v1/adspaces/{id}/children | List an ad zone&#39;s child zones.
*AdspacesApi* | [**apiV1AdspacesIdClonePost**](docs/Api/AdspacesApi.md#apiv1adspacesidclonepost) | **POST** /api/v1/adspaces/{id}/clone | Duplicate an ad zone.
*AdspacesApi* | [**apiV1AdspacesIdCodeGet**](docs/Api/AdspacesApi.md#apiv1adspacesidcodeget) | **GET** /api/v1/adspaces/{id}/code | Embed-code snippets for an adspace.
*AdspacesApi* | [**apiV1AdspacesIdDelete**](docs/Api/AdspacesApi.md#apiv1adspacesiddelete) | **DELETE** /api/v1/adspaces/{id} | Soft-delete an adspace.
*AdspacesApi* | [**apiV1AdspacesIdGet**](docs/Api/AdspacesApi.md#apiv1adspacesidget) | **GET** /api/v1/adspaces/{id} | Get a single adspace.
*AdspacesApi* | [**apiV1AdspacesIdNetworkBindingDelete**](docs/Api/AdspacesApi.md#apiv1adspacesidnetworkbindingdelete) | **DELETE** /api/v1/adspaces/{id}/network-binding | Remove the zone&#39;s network binding.
*AdspacesApi* | [**apiV1AdspacesIdNetworkBindingPut**](docs/Api/AdspacesApi.md#apiv1adspacesidnetworkbindingput) | **PUT** /api/v1/adspaces/{id}/network-binding | Bind the zone to a campaign on an external ad network (the Source tab).
*AdspacesApi* | [**apiV1AdspacesIdOwnAdsAdIdDelete**](docs/Api/AdspacesApi.md#apiv1adspacesidownadsadiddelete) | **DELETE** /api/v1/adspaces/{id}/own-ads/{adId} | Delete an own ad.
*AdspacesApi* | [**apiV1AdspacesIdOwnAdsAdIdGet**](docs/Api/AdspacesApi.md#apiv1adspacesidownadsadidget) | **GET** /api/v1/adspaces/{id}/own-ads/{adId} | Get one own ad.
*AdspacesApi* | [**apiV1AdspacesIdOwnAdsAdIdPut**](docs/Api/AdspacesApi.md#apiv1adspacesidownadsadidput) | **PUT** /api/v1/adspaces/{id}/own-ads/{adId} | Update an own ad.
*AdspacesApi* | [**apiV1AdspacesIdOwnAdsAdIdTogglePut**](docs/Api/AdspacesApi.md#apiv1adspacesidownadsadidtoggleput) | **PUT** /api/v1/adspaces/{id}/own-ads/{adId}/toggle | Switch an own ad on/off.
*AdspacesApi* | [**apiV1AdspacesIdOwnAdsGet**](docs/Api/AdspacesApi.md#apiv1adspacesidownadsget) | **GET** /api/v1/adspaces/{id}/own-ads | List the zone&#39;s own (house / fallback) ads.
*AdspacesApi* | [**apiV1AdspacesIdOwnAdsMassActionPost**](docs/Api/AdspacesApi.md#apiv1adspacesidownadsmassactionpost) | **POST** /api/v1/adspaces/{id}/own-ads/mass-action | Activate, deactivate or delete several own ads.
*AdspacesApi* | [**apiV1AdspacesIdOwnAdsPost**](docs/Api/AdspacesApi.md#apiv1adspacesidownadspost) | **POST** /api/v1/adspaces/{id}/own-ads | Add an own ad.
*AdspacesApi* | [**apiV1AdspacesIdPatch**](docs/Api/AdspacesApi.md#apiv1adspacesidpatch) | **PATCH** /api/v1/adspaces/{id} | Update an existing adspace.
*AdspacesApi* | [**apiV1AdspacesIdPricesTypeGet**](docs/Api/AdspacesApi.md#apiv1adspacesidpricestypeget) | **GET** /api/v1/adspaces/{id}/prices/{type} | Minimum (floor) prices per country for one device type.
*AdspacesApi* | [**apiV1AdspacesIdPricesTypePost**](docs/Api/AdspacesApi.md#apiv1adspacesidpricestypepost) | **POST** /api/v1/adspaces/{id}/prices/{type} | Replace the floor prices for one device type.
*AdspacesApi* | [**apiV1AdspacesIdRecoverPost**](docs/Api/AdspacesApi.md#apiv1adspacesidrecoverpost) | **POST** /api/v1/adspaces/{id}/recover | Reverse a soft-delete on an adspace.
*AdspacesApi* | [**apiV1AdspacesIdWlCampaignsGet**](docs/Api/AdspacesApi.md#apiv1adspacesidwlcampaignsget) | **GET** /api/v1/adspaces/{id}/wl-campaigns | Campaigns that can be whitelisted on this zone, and the current whitelist.
*AdspacesApi* | [**apiV1AdspacesIdWlCampaignsPut**](docs/Api/AdspacesApi.md#apiv1adspacesidwlcampaignsput) | **PUT** /api/v1/adspaces/{id}/wl-campaigns | Replace the zone&#39;s whitelisted campaigns.
*AdspacesApi* | [**apiV1AdspacesMassDeletePost**](docs/Api/AdspacesApi.md#apiv1adspacesmassdeletepost) | **POST** /api/v1/adspaces/mass-delete | Soft-delete several ad zones.
*AdspacesApi* | [**apiV1AdspacesMassRecoverPost**](docs/Api/AdspacesApi.md#apiv1adspacesmassrecoverpost) | **POST** /api/v1/adspaces/mass-recover | Recover several soft-deleted ad zones.
*AdspacesApi* | [**apiV1AdspacesTemplatesGet**](docs/Api/AdspacesApi.md#apiv1adspacestemplatesget) | **GET** /api/v1/adspaces/templates | List your saved ad zone templates.
*AdspacesApi* | [**apiV1AdspacesTemplatesIdDelete**](docs/Api/AdspacesApi.md#apiv1adspacestemplatesiddelete) | **DELETE** /api/v1/adspaces/templates/{id} | Delete a template.
*AdspacesApi* | [**apiV1AdspacesTemplatesIdPut**](docs/Api/AdspacesApi.md#apiv1adspacestemplatesidput) | **PUT** /api/v1/adspaces/templates/{id} | Replace a template&#39;s data.
*AdspacesApi* | [**apiV1AdspacesTemplatesPost**](docs/Api/AdspacesApi.md#apiv1adspacestemplatespost) | **POST** /api/v1/adspaces/templates | Save an ad zone template.
*AdspacesApi* | [**apiV1AdspacesTypePost**](docs/Api/AdspacesApi.md#apiv1adspacestypepost) | **POST** /api/v1/adspaces/{type} | Create a new adspace.
*AdspacesApi* | [**apiV1AdspacesTypeSchemaGet**](docs/Api/AdspacesApi.md#apiv1adspacestypeschemaget) | **GET** /api/v1/adspaces/{type}/_schema | Field metadata for create/update of one adspace type.
*AgenticMediaBuyingApi* | [**apiV1AdspacesIdMediaBuyingActionsActionIdDecidePost**](docs/Api/AgenticMediaBuyingApi.md#apiv1adspacesidmediabuyingactionsactioniddecidepost) | **POST** /api/v1/adspaces/{id}/media-buying/actions/{actionId}/decide | Approve or reject a proposal.
*AgenticMediaBuyingApi* | [**apiV1AdspacesIdMediaBuyingActionsGet**](docs/Api/AgenticMediaBuyingApi.md#apiv1adspacesidmediabuyingactionsget) | **GET** /api/v1/adspaces/{id}/media-buying/actions | Activity log: every block, bid, budget and other change, and open proposals.
*AgenticMediaBuyingApi* | [**apiV1AdspacesIdMediaBuyingAgentRunPost**](docs/Api/AgenticMediaBuyingApi.md#apiv1adspacesidmediabuyingagentrunpost) | **POST** /api/v1/adspaces/{id}/media-buying/agent/run | Run the buying agent now.
*AgenticMediaBuyingApi* | [**apiV1AdspacesIdMediaBuyingAgentRunsGet**](docs/Api/AgenticMediaBuyingApi.md#apiv1adspacesidmediabuyingagentrunsget) | **GET** /api/v1/adspaces/{id}/media-buying/agent/runs | The buying agent&#39;s recent runs with their summaries and proposals.
*AgenticMediaBuyingApi* | [**apiV1AdspacesIdMediaBuyingBlockPost**](docs/Api/AgenticMediaBuyingApi.md#apiv1adspacesidmediabuyingblockpost) | **POST** /api/v1/adspaces/{id}/media-buying/block | Block or unblock one source zone at the network.
*AgenticMediaBuyingApi* | [**apiV1AdspacesIdMediaBuyingCompareGet**](docs/Api/AgenticMediaBuyingApi.md#apiv1adspacesidmediabuyingcompareget) | **GET** /api/v1/adspaces/{id}/media-buying/compare | Compare with another buy (A/B, e.g. against a control left to the network&#39;s own optimiser).
*AgenticMediaBuyingApi* | [**apiV1AdspacesIdMediaBuyingGet**](docs/Api/AgenticMediaBuyingApi.md#apiv1adspacesidmediabuyingget) | **GET** /api/v1/adspaces/{id}/media-buying | Overview: the connected network, what it supports, the policy and open proposals.
*AgenticMediaBuyingApi* | [**apiV1AdspacesIdMediaBuyingIpsGet**](docs/Api/AgenticMediaBuyingApi.md#apiv1adspacesidmediabuyingipsget) | **GET** /api/v1/adspaces/{id}/media-buying/ips | The IP preblock list: ranges on the network&#39;s exclusion list and proposals.
*AgenticMediaBuyingApi* | [**apiV1AdspacesIdMediaBuyingIpsPost**](docs/Api/AgenticMediaBuyingApi.md#apiv1adspacesidmediabuyingipspost) | **POST** /api/v1/adspaces/{id}/media-buying/ips | Change the IP preblock list.
*AgenticMediaBuyingApi* | [**apiV1AdspacesIdMediaBuyingLaunchGet**](docs/Api/AgenticMediaBuyingApi.md#apiv1adspacesidmediabuyinglaunchget) | **GET** /api/v1/adspaces/{id}/media-buying/launch | What a new campaign can be launched with (unconnected ad zones).
*AgenticMediaBuyingApi* | [**apiV1AdspacesIdMediaBuyingLaunchPost**](docs/Api/AgenticMediaBuyingApi.md#apiv1adspacesidmediabuyinglaunchpost) | **POST** /api/v1/adspaces/{id}/media-buying/launch | Launch a new campaign at the network for this ad zone.
*AgenticMediaBuyingApi* | [**apiV1AdspacesIdMediaBuyingLaunchTemplatesGet**](docs/Api/AgenticMediaBuyingApi.md#apiv1adspacesidmediabuyinglaunchtemplatesget) | **GET** /api/v1/adspaces/{id}/media-buying/launch/templates | Existing campaigns that can be cloned for a launch.
*AgenticMediaBuyingApi* | [**apiV1AdspacesIdMediaBuyingNetworkBudgetGet**](docs/Api/AgenticMediaBuyingApi.md#apiv1adspacesidmediabuyingnetworkbudgetget) | **GET** /api/v1/adspaces/{id}/media-buying/network-budget | The campaign&#39;s daily budget at the network.
*AgenticMediaBuyingApi* | [**apiV1AdspacesIdMediaBuyingPolicyPut**](docs/Api/AgenticMediaBuyingApi.md#apiv1adspacesidmediabuyingpolicyput) | **PUT** /api/v1/adspaces/{id}/media-buying/policy | Save the buying policy (any subset of its fields).
*AgenticMediaBuyingApi* | [**apiV1AdspacesIdMediaBuyingPolicySuggestGet**](docs/Api/AgenticMediaBuyingApi.md#apiv1adspacesidmediabuyingpolicysuggestget) | **GET** /api/v1/adspaces/{id}/media-buying/policy/suggest | Suggested policy from the zone&#39;s own traffic, with a reason per field.
*AgenticMediaBuyingApi* | [**apiV1AdspacesIdMediaBuyingReportPost**](docs/Api/AgenticMediaBuyingApi.md#apiv1adspacesidmediabuyingreportpost) | **POST** /api/v1/adspaces/{id}/media-buying/report | Send the media buying report now.
*AgenticMediaBuyingApi* | [**apiV1AdspacesIdMediaBuyingSegmentsGet**](docs/Api/AgenticMediaBuyingApi.md#apiv1adspacesidmediabuyingsegmentsget) | **GET** /api/v1/adspaces/{id}/media-buying/segments | Cost and revenue per segment: country, OS, hour, day of week, browser, region, carrier, language.
*AgenticMediaBuyingApi* | [**apiV1AdspacesIdMediaBuyingSourceBidGet**](docs/Api/AgenticMediaBuyingApi.md#apiv1adspacesidmediabuyingsourcebidget) | **GET** /api/v1/adspaces/{id}/media-buying/source/bid | The campaign&#39;s current bid at the network.
*AgenticMediaBuyingApi* | [**apiV1AdspacesIdMediaBuyingSourceBidPut**](docs/Api/AgenticMediaBuyingApi.md#apiv1adspacesidmediabuyingsourcebidput) | **PUT** /api/v1/adspaces/{id}/media-buying/source/bid | Set the campaign&#39;s bid at the network.
*AgenticMediaBuyingApi* | [**apiV1AdspacesIdMediaBuyingSourceRetryPost**](docs/Api/AgenticMediaBuyingApi.md#apiv1adspacesidmediabuyingsourceretrypost) | **POST** /api/v1/adspaces/{id}/media-buying/source/retry | Retry the connection to the source campaign.
*AgenticMediaBuyingApi* | [**apiV1AdspacesIdMediaBuyingTargetingGet**](docs/Api/AgenticMediaBuyingApi.md#apiv1adspacesidmediabuyingtargetingget) | **GET** /api/v1/adspaces/{id}/media-buying/targeting | The campaign&#39;s placement targeting (all, or an allow-list).
*AgenticMediaBuyingApi* | [**apiV1AdspacesIdMediaBuyingTargetingPut**](docs/Api/AgenticMediaBuyingApi.md#apiv1adspacesidmediabuyingtargetingput) | **PUT** /api/v1/adspaces/{id}/media-buying/targeting | Switch to / edit the allow-list, or back to all placements.
*AgenticMediaBuyingApi* | [**apiV1AdspacesIdMediaBuyingTargetingSuggestGet**](docs/Api/AgenticMediaBuyingApi.md#apiv1adspacesidmediabuyingtargetingsuggestget) | **GET** /api/v1/adspaces/{id}/media-buying/targeting/suggest | Suggested allow-list: the profitable placements of the policy window.
*AgenticMediaBuyingApi* | [**apiV1AdspacesIdMediaBuyingTrackRecordGet**](docs/Api/AgenticMediaBuyingApi.md#apiv1adspacesidmediabuyingtrackrecordget) | **GET** /api/v1/adspaces/{id}/media-buying/track-record | Track record: did each change do what it was meant to?
*AgenticMediaBuyingApi* | [**apiV1AdspacesIdMediaBuyingZonesGet**](docs/Api/AgenticMediaBuyingApi.md#apiv1adspacesidmediabuyingzonesget) | **GET** /api/v1/adspaces/{id}/media-buying/zones | Source-zone report: revenue against cost per placement.
*AgenticMediaBuyingApi* | [**apiV1MediaBuyingPortfolioGet**](docs/Api/AgenticMediaBuyingApi.md#apiv1mediabuyingportfolioget) | **GET** /api/v1/media-buying/portfolio | Every buy side by side: margin against target, spend, proposals, the agent&#39;s latest summary.
*AgenticMediaBuyingApi* | [**apiV1TrackerMediaBuyingPortfolioGet**](docs/Api/AgenticMediaBuyingApi.md#apiv1trackermediabuyingportfolioget) | **GET** /api/v1/tracker/media-buying/portfolio | Every buy side by side (tracker accounts — the same overview): margin against target, spend, proposals, the agent&#39;s latest summary.
*AgenticMediaBuyingApi* | [**apiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePost**](docs/Api/AgenticMediaBuyingApi.md#apiv1trackertrackinglinksidmediabuyingactionsactioniddecidepost) | **POST** /api/v1/tracker/tracking-links/{id}/media-buying/actions/{actionId}/decide | Approve or reject a proposal.
*AgenticMediaBuyingApi* | [**apiV1TrackerTrackingLinksIdMediaBuyingActionsGet**](docs/Api/AgenticMediaBuyingApi.md#apiv1trackertrackinglinksidmediabuyingactionsget) | **GET** /api/v1/tracker/tracking-links/{id}/media-buying/actions | Activity log: every block, bid, budget and other change, and open proposals.
*AgenticMediaBuyingApi* | [**apiV1TrackerTrackingLinksIdMediaBuyingAgentRunPost**](docs/Api/AgenticMediaBuyingApi.md#apiv1trackertrackinglinksidmediabuyingagentrunpost) | **POST** /api/v1/tracker/tracking-links/{id}/media-buying/agent/run | Run the buying agent now.
*AgenticMediaBuyingApi* | [**apiV1TrackerTrackingLinksIdMediaBuyingAgentRunsGet**](docs/Api/AgenticMediaBuyingApi.md#apiv1trackertrackinglinksidmediabuyingagentrunsget) | **GET** /api/v1/tracker/tracking-links/{id}/media-buying/agent/runs | The buying agent&#39;s recent runs with their summaries and proposals.
*AgenticMediaBuyingApi* | [**apiV1TrackerTrackingLinksIdMediaBuyingBlockPost**](docs/Api/AgenticMediaBuyingApi.md#apiv1trackertrackinglinksidmediabuyingblockpost) | **POST** /api/v1/tracker/tracking-links/{id}/media-buying/block | Block or unblock one source zone at the network.
*AgenticMediaBuyingApi* | [**apiV1TrackerTrackingLinksIdMediaBuyingCompareGet**](docs/Api/AgenticMediaBuyingApi.md#apiv1trackertrackinglinksidmediabuyingcompareget) | **GET** /api/v1/tracker/tracking-links/{id}/media-buying/compare | Compare with another buy (A/B, e.g. against a control left to the network&#39;s own optimiser).
*AgenticMediaBuyingApi* | [**apiV1TrackerTrackingLinksIdMediaBuyingGet**](docs/Api/AgenticMediaBuyingApi.md#apiv1trackertrackinglinksidmediabuyingget) | **GET** /api/v1/tracker/tracking-links/{id}/media-buying | Overview: the connected network, what it supports, the policy and open proposals.
*AgenticMediaBuyingApi* | [**apiV1TrackerTrackingLinksIdMediaBuyingIpsGet**](docs/Api/AgenticMediaBuyingApi.md#apiv1trackertrackinglinksidmediabuyingipsget) | **GET** /api/v1/tracker/tracking-links/{id}/media-buying/ips | The IP preblock list: ranges on the network&#39;s exclusion list and proposals.
*AgenticMediaBuyingApi* | [**apiV1TrackerTrackingLinksIdMediaBuyingIpsPost**](docs/Api/AgenticMediaBuyingApi.md#apiv1trackertrackinglinksidmediabuyingipspost) | **POST** /api/v1/tracker/tracking-links/{id}/media-buying/ips | Change the IP preblock list.
*AgenticMediaBuyingApi* | [**apiV1TrackerTrackingLinksIdMediaBuyingNetworkBudgetGet**](docs/Api/AgenticMediaBuyingApi.md#apiv1trackertrackinglinksidmediabuyingnetworkbudgetget) | **GET** /api/v1/tracker/tracking-links/{id}/media-buying/network-budget | The campaign&#39;s daily budget at the network.
*AgenticMediaBuyingApi* | [**apiV1TrackerTrackingLinksIdMediaBuyingPolicyPut**](docs/Api/AgenticMediaBuyingApi.md#apiv1trackertrackinglinksidmediabuyingpolicyput) | **PUT** /api/v1/tracker/tracking-links/{id}/media-buying/policy | Save the buying policy (any subset of its fields).
*AgenticMediaBuyingApi* | [**apiV1TrackerTrackingLinksIdMediaBuyingPolicySuggestGet**](docs/Api/AgenticMediaBuyingApi.md#apiv1trackertrackinglinksidmediabuyingpolicysuggestget) | **GET** /api/v1/tracker/tracking-links/{id}/media-buying/policy/suggest | Suggested policy from the zone&#39;s own traffic, with a reason per field.
*AgenticMediaBuyingApi* | [**apiV1TrackerTrackingLinksIdMediaBuyingReportPost**](docs/Api/AgenticMediaBuyingApi.md#apiv1trackertrackinglinksidmediabuyingreportpost) | **POST** /api/v1/tracker/tracking-links/{id}/media-buying/report | Send the media buying report now.
*AgenticMediaBuyingApi* | [**apiV1TrackerTrackingLinksIdMediaBuyingSegmentsGet**](docs/Api/AgenticMediaBuyingApi.md#apiv1trackertrackinglinksidmediabuyingsegmentsget) | **GET** /api/v1/tracker/tracking-links/{id}/media-buying/segments | Cost and revenue per segment: country, OS, hour, day of week, browser, region, carrier, language.
*AgenticMediaBuyingApi* | [**apiV1TrackerTrackingLinksIdMediaBuyingSourceBidGet**](docs/Api/AgenticMediaBuyingApi.md#apiv1trackertrackinglinksidmediabuyingsourcebidget) | **GET** /api/v1/tracker/tracking-links/{id}/media-buying/source/bid | The campaign&#39;s current bid at the network.
*AgenticMediaBuyingApi* | [**apiV1TrackerTrackingLinksIdMediaBuyingSourceBidPut**](docs/Api/AgenticMediaBuyingApi.md#apiv1trackertrackinglinksidmediabuyingsourcebidput) | **PUT** /api/v1/tracker/tracking-links/{id}/media-buying/source/bid | Set the campaign&#39;s bid at the network.
*AgenticMediaBuyingApi* | [**apiV1TrackerTrackingLinksIdMediaBuyingSourceRetryPost**](docs/Api/AgenticMediaBuyingApi.md#apiv1trackertrackinglinksidmediabuyingsourceretrypost) | **POST** /api/v1/tracker/tracking-links/{id}/media-buying/source/retry | Retry the connection to the source campaign.
*AgenticMediaBuyingApi* | [**apiV1TrackerTrackingLinksIdMediaBuyingTargetingGet**](docs/Api/AgenticMediaBuyingApi.md#apiv1trackertrackinglinksidmediabuyingtargetingget) | **GET** /api/v1/tracker/tracking-links/{id}/media-buying/targeting | The campaign&#39;s placement targeting (all, or an allow-list).
*AgenticMediaBuyingApi* | [**apiV1TrackerTrackingLinksIdMediaBuyingTargetingPut**](docs/Api/AgenticMediaBuyingApi.md#apiv1trackertrackinglinksidmediabuyingtargetingput) | **PUT** /api/v1/tracker/tracking-links/{id}/media-buying/targeting | Switch to / edit the allow-list, or back to all placements.
*AgenticMediaBuyingApi* | [**apiV1TrackerTrackingLinksIdMediaBuyingTargetingSuggestGet**](docs/Api/AgenticMediaBuyingApi.md#apiv1trackertrackinglinksidmediabuyingtargetingsuggestget) | **GET** /api/v1/tracker/tracking-links/{id}/media-buying/targeting/suggest | Suggested allow-list: the profitable placements of the policy window.
*AgenticMediaBuyingApi* | [**apiV1TrackerTrackingLinksIdMediaBuyingTrackRecordGet**](docs/Api/AgenticMediaBuyingApi.md#apiv1trackertrackinglinksidmediabuyingtrackrecordget) | **GET** /api/v1/tracker/tracking-links/{id}/media-buying/track-record | Track record: did each change do what it was meant to?
*AgenticMediaBuyingApi* | [**apiV1TrackerTrackingLinksIdMediaBuyingZonesGet**](docs/Api/AgenticMediaBuyingApi.md#apiv1trackertrackinglinksidmediabuyingzonesget) | **GET** /api/v1/tracker/tracking-links/{id}/media-buying/zones | Source-zone report: revenue against cost per placement.
*BidRulesetsApi* | [**apiV1BidRulesetsGet**](docs/Api/BidRulesetsApi.md#apiv1bidrulesetsget) | **GET** /api/v1/bid-rulesets | List the caller&#39;s bid rulesets (lightweight rows).
*BidRulesetsApi* | [**apiV1BidRulesetsIdClonePost**](docs/Api/BidRulesetsApi.md#apiv1bidrulesetsidclonepost) | **POST** /api/v1/bid-rulesets/{id}/clone | Clone a ruleset with all its rules.
*BidRulesetsApi* | [**apiV1BidRulesetsIdDelete**](docs/Api/BidRulesetsApi.md#apiv1bidrulesetsiddelete) | **DELETE** /api/v1/bid-rulesets/{id} | Soft-delete a ruleset.
*BidRulesetsApi* | [**apiV1BidRulesetsIdGet**](docs/Api/BidRulesetsApi.md#apiv1bidrulesetsidget) | **GET** /api/v1/bid-rulesets/{id} | Show one ruleset, including all rules.
*BidRulesetsApi* | [**apiV1BidRulesetsIdPatch**](docs/Api/BidRulesetsApi.md#apiv1bidrulesetsidpatch) | **PATCH** /api/v1/bid-rulesets/{id} | Update a ruleset&#39;s metadata and/or replace its rules.
*BidRulesetsApi* | [**apiV1BidRulesetsPost**](docs/Api/BidRulesetsApi.md#apiv1bidrulesetspost) | **POST** /api/v1/bid-rulesets | Create a new bid ruleset (with rules in one shot).
*CampaignsApi* | [**apiV1CampaignsCpmCampaignIdSpotsSpaceIdDelete**](docs/Api/CampaignsApi.md#apiv1campaignscpmcampaignidspotsspaceiddelete) | **DELETE** /api/v1/campaigns/cpm/{campaignId}/spots/{spaceId} | Remove an ad zone from a CPM campaign.
*CampaignsApi* | [**apiV1CampaignsCpmCampaignIdSpotsSpaceIdPost**](docs/Api/CampaignsApi.md#apiv1campaignscpmcampaignidspotsspaceidpost) | **POST** /api/v1/campaigns/cpm/{campaignId}/spots/{spaceId} | Add an ad zone to a CPM campaign (or re-price it).
*CampaignsApi* | [**apiV1CampaignsCpmCampaignIdSpotsSpaceIdPut**](docs/Api/CampaignsApi.md#apiv1campaignscpmcampaignidspotsspaceidput) | **PUT** /api/v1/campaigns/cpm/{campaignId}/spots/{spaceId} | Switch a CPM campaign&#39;s zone spot on or off.
*CampaignsApi* | [**apiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePut**](docs/Api/CampaignsApi.md#apiv1campaignscpmcampaignidspotsspaceidtypeput) | **PUT** /api/v1/campaigns/cpm/{campaignId}/spots/{spaceId}/{type} | Set a CPM zone spot&#39;s bid or state.
*CampaignsApi* | [**apiV1CampaignsGet**](docs/Api/CampaignsApi.md#apiv1campaignsget) | **GET** /api/v1/campaigns | List the caller&#39;s campaigns across all types (or one type).
*CampaignsApi* | [**apiV1CampaignsMassDeletePost**](docs/Api/CampaignsApi.md#apiv1campaignsmassdeletepost) | **POST** /api/v1/campaigns/mass-delete | Soft-delete many campaigns.
*CampaignsApi* | [**apiV1CampaignsMassTogglePut**](docs/Api/CampaignsApi.md#apiv1campaignsmasstoggleput) | **PUT** /api/v1/campaigns/mass-toggle | Pause or resume many campaigns at once.
*CampaignsApi* | [**apiV1CampaignsMassUndeletePost**](docs/Api/CampaignsApi.md#apiv1campaignsmassundeletepost) | **POST** /api/v1/campaigns/mass-undelete | Restore soft-deleted campaigns.
*CampaignsApi* | [**apiV1CampaignsPurgePost**](docs/Api/CampaignsApi.md#apiv1campaignspurgepost) | **POST** /api/v1/campaigns/purge | Permanently delete soft-deleted campaigns and their ads.
*CampaignsApi* | [**apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet**](docs/Api/CampaignsApi.md#apiv1campaignsroncampaignidadsadidzonebidsget) | **GET** /api/v1/campaigns/ron/{campaignId}/ads/{adId}/zone-bids | List per-zone bid overrides for a RON ad.
*CampaignsApi* | [**apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut**](docs/Api/CampaignsApi.md#apiv1campaignsroncampaignidadsadidzonebidsput) | **PUT** /api/v1/campaigns/ron/{campaignId}/ads/{adId}/zone-bids | Replace the per-zone bid overrides on a RON ad.
*CampaignsApi* | [**apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete**](docs/Api/CampaignsApi.md#apiv1campaignsroncampaignidadsadidzonebidszoneiddelete) | **DELETE** /api/v1/campaigns/ron/{campaignId}/ads/{adId}/zone-bids/{zoneId} | Clear a single per-zone bid override.
*CampaignsApi* | [**apiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPut**](docs/Api/CampaignsApi.md#apiv1campaignsroncampaignidadsadidzonebidszonesonlyput) | **PUT** /api/v1/campaigns/ron/{campaignId}/ads/{adId}/zone-bids/zones-only | Switch \&quot;only these zones\&quot; mode for a RON ad&#39;s per-zone bids.
*CampaignsApi* | [**apiV1CampaignsRonIdSiteListGet**](docs/Api/CampaignsApi.md#apiv1campaignsronidsitelistget) | **GET** /api/v1/campaigns/ron/{id}/site-list | The Premium-sites box of a RON campaign.
*CampaignsApi* | [**apiV1CampaignsRonIdSiteListSiteIdPut**](docs/Api/CampaignsApi.md#apiv1campaignsronidsitelistsiteidput) | **PUT** /api/v1/campaigns/ron/{id}/site-list/{siteId} | Set one site of the Premium-sites box active or blocked.
*CampaignsApi* | [**apiV1CampaignsRonIdZonesGet**](docs/Api/CampaignsApi.md#apiv1campaignsronidzonesget) | **GET** /api/v1/campaigns/ron/{id}/zones | List the zones a RON campaign is linked to.
*CampaignsApi* | [**apiV1CampaignsRtbIdTestRtbGet**](docs/Api/CampaignsApi.md#apiv1campaignsrtbidtestrtbget) | **GET** /api/v1/campaigns/rtb/{id}/test-rtb | Context for testing an RTB campaign&#39;s endpoint.
*CampaignsApi* | [**apiV1CampaignsRtbIdTestRtbPost**](docs/Api/CampaignsApi.md#apiv1campaignsrtbidtestrtbpost) | **POST** /api/v1/campaigns/rtb/{id}/test-rtb | Fire a test bid request at an RTB campaign&#39;s endpoint.
*CampaignsApi* | [**apiV1CampaignsSaveCategoriesPut**](docs/Api/CampaignsApi.md#apiv1campaignssavecategoriesput) | **PUT** /api/v1/campaigns/save-categories | Assign categories to many campaigns.
*CampaignsApi* | [**apiV1CampaignsSaveTagsPut**](docs/Api/CampaignsApi.md#apiv1campaignssavetagsput) | **PUT** /api/v1/campaigns/save-tags | Assign tags (campaign groups) to many campaigns.
*CampaignsApi* | [**apiV1CampaignsTypeCampaignIdAdsAdIdActivePatch**](docs/Api/CampaignsApi.md#apiv1campaignstypecampaignidadsadidactivepatch) | **PATCH** /api/v1/campaigns/{type}/{campaignId}/ads/{adId}/active | Pause or unpause a single ad.
*CampaignsApi* | [**apiV1CampaignsTypeCampaignIdAdsAdIdBidPatch**](docs/Api/CampaignsApi.md#apiv1campaignstypecampaignidadsadidbidpatch) | **PATCH** /api/v1/campaigns/{type}/{campaignId}/ads/{adId}/bid | Set a single ad&#39;s bid.
*CampaignsApi* | [**apiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPut**](docs/Api/CampaignsApi.md#apiv1campaignstypecampaignidadsadidbidspergeoput) | **PUT** /api/v1/campaigns/{type}/{campaignId}/ads/{adId}/bids-per-geo | Set per-country bids for a RON ad.
*CampaignsApi* | [**apiV1CampaignsTypeCampaignIdAdsAdIdClonePost**](docs/Api/CampaignsApi.md#apiv1campaignstypecampaignidadsadidclonepost) | **POST** /api/v1/campaigns/{type}/{campaignId}/ads/{adId}/clone | Clone a single ad within a campaign.
*CampaignsApi* | [**apiV1CampaignsTypeCampaignIdAdsAdIdCountriesPut**](docs/Api/CampaignsApi.md#apiv1campaignstypecampaignidadsadidcountriesput) | **PUT** /api/v1/campaigns/{type}/{campaignId}/ads/{adId}/countries | Set per-ad country targeting for a RON ad.
*CampaignsApi* | [**apiV1CampaignsTypeCampaignIdAdsAdIdDelete**](docs/Api/CampaignsApi.md#apiv1campaignstypecampaignidadsadiddelete) | **DELETE** /api/v1/campaigns/{type}/{campaignId}/ads/{adId} | Soft-delete an ad.
*CampaignsApi* | [**apiV1CampaignsTypeCampaignIdAdsAdIdGet**](docs/Api/CampaignsApi.md#apiv1campaignstypecampaignidadsadidget) | **GET** /api/v1/campaigns/{type}/{campaignId}/ads/{adId} | Show one ad.
*CampaignsApi* | [**apiV1CampaignsTypeCampaignIdAdsAdIdOverlayStatsGet**](docs/Api/CampaignsApi.md#apiv1campaignstypecampaignidadsadidoverlaystatsget) | **GET** /api/v1/campaigns/{type}/{campaignId}/ads/{adId}/overlay-stats | Shoppable video — shown and clicks per overlay.
*CampaignsApi* | [**apiV1CampaignsTypeCampaignIdAdsAdIdPut**](docs/Api/CampaignsApi.md#apiv1campaignstypecampaignidadsadidput) | **PUT** /api/v1/campaigns/{type}/{campaignId}/ads/{adId} | Update an ad.
*CampaignsApi* | [**apiV1CampaignsTypeCampaignIdAdsAdIdRestorePut**](docs/Api/CampaignsApi.md#apiv1campaignstypecampaignidadsadidrestoreput) | **PUT** /api/v1/campaigns/{type}/{campaignId}/ads/{adId}/restore | Restore a deleted RON ad.
*CampaignsApi* | [**apiV1CampaignsTypeCampaignIdAdsCopyTargetsGet**](docs/Api/CampaignsApi.md#apiv1campaignstypecampaignidadscopytargetsget) | **GET** /api/v1/campaigns/{type}/{campaignId}/ads/copy-targets | Campaigns this campaign&#39;s ads can be copied into.
*CampaignsApi* | [**apiV1CampaignsTypeCampaignIdAdsCsvImportPost**](docs/Api/CampaignsApi.md#apiv1campaignstypecampaignidadscsvimportpost) | **POST** /api/v1/campaigns/{type}/{campaignId}/ads/csv-import | Import native ads from a CSV — one ad per row, or one ad per group.
*CampaignsApi* | [**apiV1CampaignsTypeCampaignIdAdsFromPoolPost**](docs/Api/CampaignsApi.md#apiv1campaignstypecampaignidadsfrompoolpost) | **POST** /api/v1/campaigns/{type}/{campaignId}/ads/from-pool | Create ads from a creative pool.
*CampaignsApi* | [**apiV1CampaignsTypeCampaignIdAdsGet**](docs/Api/CampaignsApi.md#apiv1campaignstypecampaignidadsget) | **GET** /api/v1/campaigns/{type}/{campaignId}/ads | List ads on a campaign.
*CampaignsApi* | [**apiV1CampaignsTypeCampaignIdAdsMassClonePost**](docs/Api/CampaignsApi.md#apiv1campaignstypecampaignidadsmassclonepost) | **POST** /api/v1/campaigns/{type}/{campaignId}/ads/mass-clone | Copy many ads of a campaign.
*CampaignsApi* | [**apiV1CampaignsTypeCampaignIdAdsMassDeletePost**](docs/Api/CampaignsApi.md#apiv1campaignstypecampaignidadsmassdeletepost) | **POST** /api/v1/campaigns/{type}/{campaignId}/ads/mass-delete | Soft-delete many ads of a campaign.
*CampaignsApi* | [**apiV1CampaignsTypeCampaignIdAdsMassTogglePut**](docs/Api/CampaignsApi.md#apiv1campaignstypecampaignidadsmasstoggleput) | **PUT** /api/v1/campaigns/{type}/{campaignId}/ads/mass-toggle | Activate or pause many ads of a campaign at once.
*CampaignsApi* | [**apiV1CampaignsTypeCampaignIdAdsMultiUploadPost**](docs/Api/CampaignsApi.md#apiv1campaignstypecampaignidadsmultiuploadpost) | **POST** /api/v1/campaigns/{type}/{campaignId}/ads/multi-upload | Bulk-upload ads — one ad per creative file.
*CampaignsApi* | [**apiV1CampaignsTypeCampaignIdAdsPost**](docs/Api/CampaignsApi.md#apiv1campaignstypecampaignidadspost) | **POST** /api/v1/campaigns/{type}/{campaignId}/ads | Create an ad on a campaign (smart-default).
*CampaignsApi* | [**apiV1CampaignsTypeCampaignIdAdsUpdatePoolPut**](docs/Api/CampaignsApi.md#apiv1campaignstypecampaignidadsupdatepoolput) | **PUT** /api/v1/campaigns/{type}/{campaignId}/ads/update-pool | Point many ads at a different creative pool.
*CampaignsApi* | [**apiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPut**](docs/Api/CampaignsApi.md#apiv1campaignstypecampaignidadsupdateurlpoolput) | **PUT** /api/v1/campaigns/{type}/{campaignId}/ads/update-url-pool | Point many ads at a URL pool, or detach it.
*CampaignsApi* | [**apiV1CampaignsTypeCampaignIdAdsUpdateUrlPut**](docs/Api/CampaignsApi.md#apiv1campaignstypecampaignidadsupdateurlput) | **PUT** /api/v1/campaigns/{type}/{campaignId}/ads/update-url | Rewrite the destination URL of many ads at once.
*CampaignsApi* | [**apiV1CampaignsTypeCampaignIdAdsUpdateWeightPut**](docs/Api/CampaignsApi.md#apiv1campaignstypecampaignidadsupdateweightput) | **PUT** /api/v1/campaigns/{type}/{campaignId}/ads/update-weight | Set the rotation weight of one or more RON ads.
*CampaignsApi* | [**apiV1CampaignsTypeCampaignIdSchemaAdsGet**](docs/Api/CampaignsApi.md#apiv1campaignstypecampaignidschemaadsget) | **GET** /api/v1/campaigns/{type}/{campaignId}/_schema/ads | Field metadata for ad creation, scoped to a campaign.
*CampaignsApi* | [**apiV1CampaignsTypeIdActivePatch**](docs/Api/CampaignsApi.md#apiv1campaignstypeidactivepatch) | **PATCH** /api/v1/campaigns/{type}/{id}/active | Pause or unpause a campaign.
*CampaignsApi* | [**apiV1CampaignsTypeIdAvailableZonesGet**](docs/Api/CampaignsApi.md#apiv1campaignstypeidavailablezonesget) | **GET** /api/v1/campaigns/{type}/{id}/available-zones | List your ad zones that can be attached to a campaign.
*CampaignsApi* | [**apiV1CampaignsTypeIdBidsPatch**](docs/Api/CampaignsApi.md#apiv1campaignstypeidbidspatch) | **PATCH** /api/v1/campaigns/{type}/{id}/bids | Mass update bids on every ad in a campaign.
*CampaignsApi* | [**apiV1CampaignsTypeIdBudgetPatch**](docs/Api/CampaignsApi.md#apiv1campaignstypeidbudgetpatch) | **PATCH** /api/v1/campaigns/{type}/{id}/budget | Update a campaign&#39;s total and/or daily budget.
*CampaignsApi* | [**apiV1CampaignsTypeIdClonePost**](docs/Api/CampaignsApi.md#apiv1campaignstypeidclonepost) | **POST** /api/v1/campaigns/{type}/{id}/clone | Clone a campaign — everything, or the parts you pick.
*CampaignsApi* | [**apiV1CampaignsTypeIdDelete**](docs/Api/CampaignsApi.md#apiv1campaignstypeiddelete) | **DELETE** /api/v1/campaigns/{type}/{id} | Soft-delete a campaign.
*CampaignsApi* | [**apiV1CampaignsTypeIdFiltersFilterTypeFilterIdDelete**](docs/Api/CampaignsApi.md#apiv1campaignstypeidfiltersfiltertypefilteriddelete) | **DELETE** /api/v1/campaigns/{type}/{id}/filters/{filter_type}/{filter_id} | Detach a filter from this campaign.
*CampaignsApi* | [**apiV1CampaignsTypeIdFiltersGet**](docs/Api/CampaignsApi.md#apiv1campaignstypeidfiltersget) | **GET** /api/v1/campaigns/{type}/{id}/filters | List filters attached to this campaign.
*CampaignsApi* | [**apiV1CampaignsTypeIdFiltersPost**](docs/Api/CampaignsApi.md#apiv1campaignstypeidfilterspost) | **POST** /api/v1/campaigns/{type}/{id}/filters | Attach a filter to this campaign.
*CampaignsApi* | [**apiV1CampaignsTypeIdGet**](docs/Api/CampaignsApi.md#apiv1campaignstypeidget) | **GET** /api/v1/campaigns/{type}/{id} | Get a single campaign.
*CampaignsApi* | [**apiV1CampaignsTypeIdMinBidGet**](docs/Api/CampaignsApi.md#apiv1campaignstypeidminbidget) | **GET** /api/v1/campaigns/{type}/{id}/min-bid | Get the campaign&#39;s minimum acceptable CPC bid.
*CampaignsApi* | [**apiV1CampaignsTypeIdPatch**](docs/Api/CampaignsApi.md#apiv1campaignstypeidpatch) | **PATCH** /api/v1/campaigns/{type}/{id} | Partially update a campaign.
*CampaignsApi* | [**apiV1CampaignsTypeIdResetMaxBudgetPut**](docs/Api/CampaignsApi.md#apiv1campaignstypeidresetmaxbudgetput) | **PUT** /api/v1/campaigns/{type}/{id}/reset-max-budget | Reset a campaign&#39;s lifetime-budget spend.
*CampaignsApi* | [**apiV1CampaignsTypeIdSitesGet**](docs/Api/CampaignsApi.md#apiv1campaignstypeidsitesget) | **GET** /api/v1/campaigns/{type}/{id}/sites | Your sites that have zones attachable to a campaign.
*CampaignsApi* | [**apiV1CampaignsTypeIdWhitelistZonesGet**](docs/Api/CampaignsApi.md#apiv1campaignstypeidwhitelistzonesget) | **GET** /api/v1/campaigns/{type}/{id}/whitelist-zones | List the ad zones attached to a campaign.
*CampaignsApi* | [**apiV1CampaignsTypeIdZonesPost**](docs/Api/CampaignsApi.md#apiv1campaignstypeidzonespost) | **POST** /api/v1/campaigns/{type}/{id}/zones | Attach ad zones to a campaign.
*CampaignsApi* | [**apiV1CampaignsTypeIdZonesZoneIdDelete**](docs/Api/CampaignsApi.md#apiv1campaignstypeidzoneszoneiddelete) | **DELETE** /api/v1/campaigns/{type}/{id}/zones/{zoneId} | Detach an ad zone from a campaign.
*CampaignsApi* | [**apiV1CampaignsTypeIdZonesZoneIdWeightPut**](docs/Api/CampaignsApi.md#apiv1campaignstypeidzoneszoneidweightput) | **PUT** /api/v1/campaigns/{type}/{id}/zones/{zoneId}/weight | Set an attached zone&#39;s weight for this campaign.
*CampaignsApi* | [**apiV1CampaignsTypePost**](docs/Api/CampaignsApi.md#apiv1campaignstypepost) | **POST** /api/v1/campaigns/{type} | Create a new campaign (smart-default).
*CampaignsApi* | [**apiV1CampaignsTypeSchemaAdsGet**](docs/Api/CampaignsApi.md#apiv1campaignstypeschemaadsget) | **GET** /api/v1/campaigns/{type}/_schema/ads | Field metadata for ad creation.
*CampaignsApi* | [**apiV1CampaignsTypeSchemaGet**](docs/Api/CampaignsApi.md#apiv1campaignstypeschemaget) | **GET** /api/v1/campaigns/{type}/_schema | Field metadata for create/update.
*CampaignsApi* | [**apiV1RtbTestRtbContextGet**](docs/Api/CampaignsApi.md#apiv1rtbtestrtbcontextget) | **GET** /api/v1/rtb/test-rtb/context | Context for testing an endpoint before the campaign exists.
*CampaignsApi* | [**apiV1RtbTestRtbPost**](docs/Api/CampaignsApi.md#apiv1rtbtestrtbpost) | **POST** /api/v1/rtb/test-rtb | Fire a test bid request at any endpoint URL.
*CampaignsApi* | [**apiV1ToolsReplaceDomainPut**](docs/Api/CampaignsApi.md#apiv1toolsreplacedomainput) | **PUT** /api/v1/tools/replace-domain | Replace the landing domain across your RON ads.
*CleanupApi* | [**apiV1CleanupAdvertiserArchivedGet**](docs/Api/CleanupApi.md#apiv1cleanupadvertiserarchivedget) | **GET** /api/v1/cleanup/advertiser/archived | Archived advertiser items that can be purged.
*CleanupApi* | [**apiV1CleanupAdvertiserExportGet**](docs/Api/CleanupApi.md#apiv1cleanupadvertiserexportget) | **GET** /api/v1/cleanup/advertiser/export | Download the advertiser cleanup candidates.
*CleanupApi* | [**apiV1CleanupAdvertiserGet**](docs/Api/CleanupApi.md#apiv1cleanupadvertiserget) | **GET** /api/v1/cleanup/advertiser | List advertiser items that no longer serve.
*CleanupApi* | [**apiV1CleanupAdvertiserLinkedGet**](docs/Api/CleanupApi.md#apiv1cleanupadvertiserlinkedget) | **GET** /api/v1/cleanup/advertiser/linked | What still uses advertiser cleanup rows.
*CleanupApi* | [**apiV1CleanupAdvertiserPurgePost**](docs/Api/CleanupApi.md#apiv1cleanupadvertiserpurgepost) | **POST** /api/v1/cleanup/advertiser/purge | Purge archived advertiser items for good.
*CleanupApi* | [**apiV1CleanupAdvertiserRunsGet**](docs/Api/CleanupApi.md#apiv1cleanupadvertiserrunsget) | **GET** /api/v1/cleanup/advertiser/runs | Recent advertiser cleanup runs.
*CleanupApi* | [**apiV1CleanupAdvertiserRunsIdGet**](docs/Api/CleanupApi.md#apiv1cleanupadvertiserrunsidget) | **GET** /api/v1/cleanup/advertiser/runs/{id} | One advertiser cleanup run with its items.
*CleanupApi* | [**apiV1CleanupAdvertiserRunsIdUndonePost**](docs/Api/CleanupApi.md#apiv1cleanupadvertiserrunsidundonepost) | **POST** /api/v1/cleanup/advertiser/runs/{id}/undone | Mark a advertiser cleanup run as undone.
*CleanupApi* | [**apiV1CleanupAdvertiserRunsPost**](docs/Api/CleanupApi.md#apiv1cleanupadvertiserrunspost) | **POST** /api/v1/cleanup/advertiser/runs | Record a advertiser cleanup run.
*CleanupApi* | [**apiV1CleanupAdvertiserSummaryGet**](docs/Api/CleanupApi.md#apiv1cleanupadvertisersummaryget) | **GET** /api/v1/cleanup/advertiser/summary | Count the advertiser cleanup candidates per kind.
*CleanupApi* | [**apiV1CleanupPublisherArchivedGet**](docs/Api/CleanupApi.md#apiv1cleanuppublisherarchivedget) | **GET** /api/v1/cleanup/publisher/archived | Archived publisher items that can be purged.
*CleanupApi* | [**apiV1CleanupPublisherExportGet**](docs/Api/CleanupApi.md#apiv1cleanuppublisherexportget) | **GET** /api/v1/cleanup/publisher/export | Download the publisher cleanup candidates.
*CleanupApi* | [**apiV1CleanupPublisherGet**](docs/Api/CleanupApi.md#apiv1cleanuppublisherget) | **GET** /api/v1/cleanup/publisher | List publisher items that no longer serve.
*CleanupApi* | [**apiV1CleanupPublisherLinkedGet**](docs/Api/CleanupApi.md#apiv1cleanuppublisherlinkedget) | **GET** /api/v1/cleanup/publisher/linked | What still uses publisher cleanup rows.
*CleanupApi* | [**apiV1CleanupPublisherPurgePost**](docs/Api/CleanupApi.md#apiv1cleanuppublisherpurgepost) | **POST** /api/v1/cleanup/publisher/purge | Purge archived publisher items for good.
*CleanupApi* | [**apiV1CleanupPublisherRunsGet**](docs/Api/CleanupApi.md#apiv1cleanuppublisherrunsget) | **GET** /api/v1/cleanup/publisher/runs | Recent publisher cleanup runs.
*CleanupApi* | [**apiV1CleanupPublisherRunsIdGet**](docs/Api/CleanupApi.md#apiv1cleanuppublisherrunsidget) | **GET** /api/v1/cleanup/publisher/runs/{id} | One publisher cleanup run with its items.
*CleanupApi* | [**apiV1CleanupPublisherRunsIdUndonePost**](docs/Api/CleanupApi.md#apiv1cleanuppublisherrunsidundonepost) | **POST** /api/v1/cleanup/publisher/runs/{id}/undone | Mark a publisher cleanup run as undone.
*CleanupApi* | [**apiV1CleanupPublisherRunsPost**](docs/Api/CleanupApi.md#apiv1cleanuppublisherrunspost) | **POST** /api/v1/cleanup/publisher/runs | Record a publisher cleanup run.
*CleanupApi* | [**apiV1CleanupPublisherSummaryGet**](docs/Api/CleanupApi.md#apiv1cleanuppublishersummaryget) | **GET** /api/v1/cleanup/publisher/summary | Count the publisher cleanup candidates per kind.
*CouponsApi* | [**apiV1CouponsSetsGet**](docs/Api/CouponsApi.md#apiv1couponssetsget) | **GET** /api/v1/coupons/sets | List coupon sets.
*CouponsApi* | [**apiV1CouponsSetsIdDelete**](docs/Api/CouponsApi.md#apiv1couponssetsiddelete) | **DELETE** /api/v1/coupons/sets/{id} | Delete a set and all its codes.
*CouponsApi* | [**apiV1CouponsSetsIdExportGet**](docs/Api/CouponsApi.md#apiv1couponssetsidexportget) | **GET** /api/v1/coupons/sets/{id}/export | Export a set&#39;s codes as CSV.
*CouponsApi* | [**apiV1CouponsSetsIdGeneratePost**](docs/Api/CouponsApi.md#apiv1couponssetsidgeneratepost) | **POST** /api/v1/coupons/sets/{id}/generate | Generate codes in a set.
*CouponsApi* | [**apiV1CouponsSetsIdPut**](docs/Api/CouponsApi.md#apiv1couponssetsidput) | **PUT** /api/v1/coupons/sets/{id} | Rename a set or move its end date.
*CouponsApi* | [**apiV1CouponsSetsPost**](docs/Api/CouponsApi.md#apiv1couponssetspost) | **POST** /api/v1/coupons/sets | Create a coupon set.
*CouponsApi* | [**apiV1CouponsSetsSetIdCouponsGet**](docs/Api/CouponsApi.md#apiv1couponssetssetidcouponsget) | **GET** /api/v1/coupons/sets/{setId}/coupons | List the codes in a set.
*CreativePoolsApi* | [**apiV1CreativePoolsGet**](docs/Api/CreativePoolsApi.md#apiv1creativepoolsget) | **GET** /api/v1/creative-pools | List creative pools.
*CreativePoolsApi* | [**apiV1CreativePoolsIdAddCreativesPost**](docs/Api/CreativePoolsApi.md#apiv1creativepoolsidaddcreativespost) | **POST** /api/v1/creative-pools/{id}/add-creatives | Add media-bank creatives to a pool.
*CreativePoolsApi* | [**apiV1CreativePoolsIdAddUrlsPost**](docs/Api/CreativePoolsApi.md#apiv1creativepoolsidaddurlspost) | **POST** /api/v1/creative-pools/{id}/add-urls | Add hotlinked image / video / HTML URLs to a pool.
*CreativePoolsApi* | [**apiV1CreativePoolsIdClonePost**](docs/Api/CreativePoolsApi.md#apiv1creativepoolsidclonepost) | **POST** /api/v1/creative-pools/{id}/clone | Clone a creative pool with all its items.
*CreativePoolsApi* | [**apiV1CreativePoolsIdDelete**](docs/Api/CreativePoolsApi.md#apiv1creativepoolsiddelete) | **DELETE** /api/v1/creative-pools/{id} | Soft-delete a creative pool.
*CreativePoolsApi* | [**apiV1CreativePoolsIdGet**](docs/Api/CreativePoolsApi.md#apiv1creativepoolsidget) | **GET** /api/v1/creative-pools/{id} | Show a single creative pool.
*CreativePoolsApi* | [**apiV1CreativePoolsIdItemsExportGet**](docs/Api/CreativePoolsApi.md#apiv1creativepoolsiditemsexportget) | **GET** /api/v1/creative-pools/{id}/items/export | Download pool items&#39; creative files as a ZIP.
*CreativePoolsApi* | [**apiV1CreativePoolsIdItemsGet**](docs/Api/CreativePoolsApi.md#apiv1creativepoolsiditemsget) | **GET** /api/v1/creative-pools/{id}/items | List items in a creative pool.
*CreativePoolsApi* | [**apiV1CreativePoolsIdItemsItemIdDelete**](docs/Api/CreativePoolsApi.md#apiv1creativepoolsiditemsitemiddelete) | **DELETE** /api/v1/creative-pools/{id}/items/{itemId} | Soft-delete a single pool item.
*CreativePoolsApi* | [**apiV1CreativePoolsIdItemsItemIdPut**](docs/Api/CreativePoolsApi.md#apiv1creativepoolsiditemsitemidput) | **PUT** /api/v1/creative-pools/{id}/items/{itemId} | Update a single pool item.
*CreativePoolsApi* | [**apiV1CreativePoolsIdItemsItemIdTogglePut**](docs/Api/CreativePoolsApi.md#apiv1creativepoolsiditemsitemidtoggleput) | **PUT** /api/v1/creative-pools/{id}/items/{itemId}/toggle | Toggle a pool item on / off.
*CreativePoolsApi* | [**apiV1CreativePoolsIdItemsMassActionPut**](docs/Api/CreativePoolsApi.md#apiv1creativepoolsiditemsmassactionput) | **PUT** /api/v1/creative-pools/{id}/items/mass-action | Bulk-change items of a creative pool.
*CreativePoolsApi* | [**apiV1CreativePoolsIdItemsPost**](docs/Api/CreativePoolsApi.md#apiv1creativepoolsiditemspost) | **POST** /api/v1/creative-pools/{id}/items | Add an item to a creative pool.
*CreativePoolsApi* | [**apiV1CreativePoolsIdItemsRecoverPost**](docs/Api/CreativePoolsApi.md#apiv1creativepoolsiditemsrecoverpost) | **POST** /api/v1/creative-pools/{id}/items/recover | Put removed items back into a creative pool.
*CreativePoolsApi* | [**apiV1CreativePoolsIdPut**](docs/Api/CreativePoolsApi.md#apiv1creativepoolsidput) | **PUT** /api/v1/creative-pools/{id} | Update creative pool meta.
*CreativePoolsApi* | [**apiV1CreativePoolsIdRecoverPost**](docs/Api/CreativePoolsApi.md#apiv1creativepoolsidrecoverpost) | **POST** /api/v1/creative-pools/{id}/recover | Restore an archived creative pool.
*CreativePoolsApi* | [**apiV1CreativePoolsIdUsedInGet**](docs/Api/CreativePoolsApi.md#apiv1creativepoolsidusedinget) | **GET** /api/v1/creative-pools/{id}/used-in | List campaigns / ads that reference this pool.
*CreativePoolsApi* | [**apiV1CreativePoolsPost**](docs/Api/CreativePoolsApi.md#apiv1creativepoolspost) | **POST** /api/v1/creative-pools | Create a creative pool.
*CreativePoolsApi* | [**apiV1CreativesBulkDeletePost**](docs/Api/CreativePoolsApi.md#apiv1creativesbulkdeletepost) | **POST** /api/v1/creatives/bulk-delete | Soft-delete many media-bank creatives.
*CreativePoolsApi* | [**apiV1CreativesBulkUndeletePost**](docs/Api/CreativePoolsApi.md#apiv1creativesbulkundeletepost) | **POST** /api/v1/creatives/bulk-undelete | Restore many deleted creatives.
*CreativePoolsApi* | [**apiV1CreativesIdUndeletePut**](docs/Api/CreativePoolsApi.md#apiv1creativesidundeleteput) | **PUT** /api/v1/creatives/{id}/undelete | Restore one deleted creative.
*CreativesApi* | [**apiV1CreativeFoldersGet**](docs/Api/CreativesApi.md#apiv1creativefoldersget) | **GET** /api/v1/creative-folders | List media-bank folders.
*CreativesApi* | [**apiV1CreativeFoldersIdDelete**](docs/Api/CreativesApi.md#apiv1creativefoldersiddelete) | **DELETE** /api/v1/creative-folders/{id} | Delete a folder.
*CreativesApi* | [**apiV1CreativeFoldersIdPut**](docs/Api/CreativesApi.md#apiv1creativefoldersidput) | **PUT** /api/v1/creative-folders/{id} | Rename or move a folder.
*CreativesApi* | [**apiV1CreativeFoldersPost**](docs/Api/CreativesApi.md#apiv1creativefolderspost) | **POST** /api/v1/creative-folders | Create a folder.
*CreativesApi* | [**apiV1CreativesBulkMovePost**](docs/Api/CreativesApi.md#apiv1creativesbulkmovepost) | **POST** /api/v1/creatives/bulk-move | File creatives into a folder.
*CreativesApi* | [**apiV1CreativesGet**](docs/Api/CreativesApi.md#apiv1creativesget) | **GET** /api/v1/creatives | List the creative library.
*CreativesApi* | [**apiV1CreativesIdDelete**](docs/Api/CreativesApi.md#apiv1creativesiddelete) | **DELETE** /api/v1/creatives/{id} | Delete a creative.
*CreativesApi* | [**apiV1CreativesIdGet**](docs/Api/CreativesApi.md#apiv1creativesidget) | **GET** /api/v1/creatives/{id} | Show one creative.
*CreativesApi* | [**apiV1CreativesIdNamePut**](docs/Api/CreativesApi.md#apiv1creativesidnameput) | **PUT** /api/v1/creatives/{id}/name | Rename a creative (panel inline edit).
*CreativesApi* | [**apiV1CreativesIdPut**](docs/Api/CreativesApi.md#apiv1creativesidput) | **PUT** /api/v1/creatives/{id} | Rename and/or re-tag a creative.
*CreativesApi* | [**apiV1CreativesIdTagsPut**](docs/Api/CreativesApi.md#apiv1creativesidtagsput) | **PUT** /api/v1/creatives/{id}/tags | Replace a creative&#39;s tags (panel inline edit).
*CreativesApi* | [**apiV1CreativesIdUsedInGet**](docs/Api/CreativesApi.md#apiv1creativesidusedinget) | **GET** /api/v1/creatives/{id}/used-in | List ads / pools referencing this creative.
*CreativesApi* | [**apiV1CreativesUploadPost**](docs/Api/CreativesApi.md#apiv1creativesuploadpost) | **POST** /api/v1/creatives/upload | Upload creative files.
*DirectDealsApi* | [**apiV1CampaignsDirectdealIdCancelPut**](docs/Api/DirectDealsApi.md#apiv1campaignsdirectdealidcancelput) | **PUT** /api/v1/campaigns/directdeal/{id}/cancel | Cancel a direct deal.
*DirectDealsApi* | [**apiV1DirectDealsAdzonesGet**](docs/Api/DirectDealsApi.md#apiv1directdealsadzonesget) | **GET** /api/v1/direct-deals/adzones | Ad zones a direct deal can be booked on.
*DirectDealsApi* | [**apiV1DirectDealsGet**](docs/Api/DirectDealsApi.md#apiv1directdealsget) | **GET** /api/v1/direct-deals | List direct deals with their derived status.
*EventLogApi* | [**apiV1EventLogExportsGet**](docs/Api/EventLogApi.md#apiv1eventlogexportsget) | **GET** /api/v1/event-log/exports | Your event log exports, newest first.
*EventLogApi* | [**apiV1EventLogExportsIdDownloadGet**](docs/Api/EventLogApi.md#apiv1eventlogexportsiddownloadget) | **GET** /api/v1/event-log/exports/{id}/download | Download a finished export (.csv.gz).
*EventLogApi* | [**apiV1EventLogExportsPost**](docs/Api/EventLogApi.md#apiv1eventlogexportspost) | **POST** /api/v1/event-log/exports | Queue a CSV export of your events.
*EventLogApi* | [**apiV1EventLogGet**](docs/Api/EventLogApi.md#apiv1eventlogget) | **GET** /api/v1/event-log | Your raw events — impressions and clicks with IP and user agent (90 days).
*FiltersApi* | [**apiV1FiltersGet**](docs/Api/FiltersApi.md#apiv1filtersget) | **GET** /api/v1/filters | List all filters across types.
*FiltersApi* | [**apiV1FiltersIpIdRangesRangeIdDelete**](docs/Api/FiltersApi.md#apiv1filtersipidrangesrangeiddelete) | **DELETE** /api/v1/filters/ip/{id}/ranges/{rangeId} | Delete one range from an IP pool.
*FiltersApi* | [**apiV1FiltersIpIdRangesRangeIdTogglePut**](docs/Api/FiltersApi.md#apiv1filtersipidrangesrangeidtoggleput) | **PUT** /api/v1/filters/ip/{id}/ranges/{rangeId}/toggle | Pause / resume one range of an IP pool.
*FiltersApi* | [**apiV1FiltersMassActionPost**](docs/Api/FiltersApi.md#apiv1filtersmassactionpost) | **POST** /api/v1/filters/mass-action | Activate, deactivate or delete many filters.
*FiltersApi* | [**apiV1FiltersPost**](docs/Api/FiltersApi.md#apiv1filterspost) | **POST** /api/v1/filters | Create a filter and populate it with items.
*FiltersApi* | [**apiV1FiltersTypeIdActivePatch**](docs/Api/FiltersApi.md#apiv1filterstypeidactivepatch) | **PATCH** /api/v1/filters/{type}/{id}/active | Activate or deactivate a filter (exclusion types only).
*FiltersApi* | [**apiV1FiltersTypeIdDelete**](docs/Api/FiltersApi.md#apiv1filterstypeiddelete) | **DELETE** /api/v1/filters/{type}/{id} | Delete a filter (and its items, for IP pools).
*FiltersApi* | [**apiV1FiltersTypeIdGet**](docs/Api/FiltersApi.md#apiv1filterstypeidget) | **GET** /api/v1/filters/{type}/{id} | Get a single filter with its items.
*FiltersApi* | [**apiV1FiltersTypeIdItemsItemDelete**](docs/Api/FiltersApi.md#apiv1filterstypeiditemsitemdelete) | **DELETE** /api/v1/filters/{type}/{id}/items/{item} | Remove a single item from a filter.
*FiltersApi* | [**apiV1FiltersTypeIdItemsPost**](docs/Api/FiltersApi.md#apiv1filterstypeiditemspost) | **POST** /api/v1/filters/{type}/{id}/items | Add items to an existing filter.
*FiltersApi* | [**apiV1FiltersTypeIdPut**](docs/Api/FiltersApi.md#apiv1filterstypeidput) | **PUT** /api/v1/filters/{type}/{id} | Edit a filter (rename, replace items, change scope).
*FundsApi* | [**apiV1FundsBalanceSettingsPut**](docs/Api/FundsApi.md#apiv1fundsbalancesettingsput) | **PUT** /api/v1/funds/balance-settings | Set the low-balance warning.
*FundsApi* | [**apiV1FundsDepositTypeFidGet**](docs/Api/FundsApi.md#apiv1fundsdeposittypefidget) | **GET** /api/v1/funds/deposit/{type}/{fid} | Checkout data for an open deposit — or its paid state.
*FundsApi* | [**apiV1FundsDepositTypePost**](docs/Api/FundsApi.md#apiv1fundsdeposittypepost) | **POST** /api/v1/funds/deposit/{type} | Open a deposit (or redeem a bonus / coupon code).
*FundsApi* | [**apiV1FundsDepositsIdDelete**](docs/Api/FundsApi.md#apiv1fundsdepositsiddelete) | **DELETE** /api/v1/funds/deposits/{id} | Cancel an unpaid deposit.
*FundsApi* | [**apiV1FundsDepositsIdProofGet**](docs/Api/FundsApi.md#apiv1fundsdepositsidproofget) | **GET** /api/v1/funds/deposits/{id}/proof | Download the proof of payment you uploaded on a deposit.
*FundsApi* | [**apiV1FundsDepositsIdProofPost**](docs/Api/FundsApi.md#apiv1fundsdepositsidproofpost) | **POST** /api/v1/funds/deposits/{id}/proof | Upload the proof of payment of an open bank or Wise deposit.
*FundsApi* | [**apiV1FundsDepositsIdTxnHashPost**](docs/Api/FundsApi.md#apiv1fundsdepositsidtxnhashpost) | **POST** /api/v1/funds/deposits/{id}/txn-hash | Attach the transaction hash of a manual crypto deposit, or the transaction ID of a Payoneer deposit.
*FundsApi* | [**apiV1FundsGet**](docs/Api/FundsApi.md#apiv1fundsget) | **GET** /api/v1/funds | Current advertiser balance + pending deposits.
*FundsApi* | [**apiV1FundsPayTypesGet**](docs/Api/FundsApi.md#apiv1fundspaytypesget) | **GET** /api/v1/funds/pay-types | Payment methods this account can deposit with.
*FundsApi* | [**apiV1FundsTransactionsGet**](docs/Api/FundsApi.md#apiv1fundstransactionsget) | **GET** /api/v1/funds/transactions | Funds ledger — deposits, bonuses, transfers, refunds.
*FundsApi* | [**apiV1FundsTransferPost**](docs/Api/FundsApi.md#apiv1fundstransferpost) | **POST** /api/v1/funds/transfer | Move balance to a connected advertiser account.
*LookupsApi* | [**apiV1LookupsAdspaceTypesGet**](docs/Api/LookupsApi.md#apiv1lookupsadspacetypesget) | **GET** /api/v1/lookups/adspace-types | Adspace types the caller can publish on this tenant.
*LookupsApi* | [**apiV1LookupsAdzoneLocationsGet**](docs/Api/LookupsApi.md#apiv1lookupsadzonelocationsget) | **GET** /api/v1/lookups/adzone-locations | Adzone locations for the tenant.
*LookupsApi* | [**apiV1LookupsAdzonesGet**](docs/Api/LookupsApi.md#apiv1lookupsadzonesget) | **GET** /api/v1/lookups/adzones | Find adzone ids (for adzone filters).
*LookupsApi* | [**apiV1LookupsBannerSizesGet**](docs/Api/LookupsApi.md#apiv1lookupsbannersizesget) | **GET** /api/v1/lookups/banner-sizes | Banner sizes for banner-flavored adspaces.
*LookupsApi* | [**apiV1LookupsBrowsersGet**](docs/Api/LookupsApi.md#apiv1lookupsbrowsersget) | **GET** /api/v1/lookups/browsers | Browser ids (optionally scoped by device type).
*LookupsApi* | [**apiV1LookupsCarriersGet**](docs/Api/LookupsApi.md#apiv1lookupscarriersget) | **GET** /api/v1/lookups/carriers | Mobile carriers (ids for carrier targeting).
*LookupsApi* | [**apiV1LookupsCategoriesGet**](docs/Api/LookupsApi.md#apiv1lookupscategoriesget) | **GET** /api/v1/lookups/categories | Active sub-categories (optionally scoped to a main).
*LookupsApi* | [**apiV1LookupsDeviceTypesGet**](docs/Api/LookupsApi.md#apiv1lookupsdevicetypesget) | **GET** /api/v1/lookups/device-types | Active device types for this tenant.
*LookupsApi* | [**apiV1LookupsDomainsGet**](docs/Api/LookupsApi.md#apiv1lookupsdomainsget) | **GET** /api/v1/lookups/domains | Resolve domains to the ids domain filters store.
*LookupsApi* | [**apiV1LookupsIspsGet**](docs/Api/LookupsApi.md#apiv1lookupsispsget) | **GET** /api/v1/lookups/isps | Find ISP ids (for ISP filters).
*LookupsApi* | [**apiV1LookupsLinkTypesGet**](docs/Api/LookupsApi.md#apiv1lookupslinktypesget) | **GET** /api/v1/lookups/link-types | Active link types for the tenant.
*LookupsApi* | [**apiV1LookupsMainCategoriesGet**](docs/Api/LookupsApi.md#apiv1lookupsmaincategoriesget) | **GET** /api/v1/lookups/main-categories | Active main categories for the tenant.
*LookupsApi* | [**apiV1LookupsOperatingSystemsGet**](docs/Api/LookupsApi.md#apiv1lookupsoperatingsystemsget) | **GET** /api/v1/lookups/operating-systems | Operating-system ids (optionally scoped by device type).
*LookupsApi* | [**apiV1LookupsPublishersGet**](docs/Api/LookupsApi.md#apiv1lookupspublishersget) | **GET** /api/v1/lookups/publishers | Find publisher ids (for publisher_excl filters).
*LookupsApi* | [**apiV1LookupsRatingsGet**](docs/Api/LookupsApi.md#apiv1lookupsratingsget) | **GET** /api/v1/lookups/ratings | Active content ratings for the tenant.
*LookupsApi* | [**apiV1LookupsReelTypesGet**](docs/Api/LookupsApi.md#apiv1lookupsreeltypesget) | **GET** /api/v1/lookups/reel-types | Active reel types for the tenant.
*LookupsApi* | [**apiV1LookupsRegionsGet**](docs/Api/LookupsApi.md#apiv1lookupsregionsget) | **GET** /api/v1/lookups/regions | Walk the geo region tree (one level per call).
*LookupsApi* | [**apiV1LookupsWebsiteLanguagesGet**](docs/Api/LookupsApi.md#apiv1lookupswebsitelanguagesget) | **GET** /api/v1/lookups/website-languages | Supported website languages (ISO codes).
*LookupsApi* | [**apiV1LookupsWebsiteTypesGet**](docs/Api/LookupsApi.md#apiv1lookupswebsitetypesget) | **GET** /api/v1/lookups/website-types | Active website types for the tenant.
*MarketDataApi* | [**apiV1PricingFiltersGet**](docs/Api/MarketDataApi.md#apiv1pricingfiltersget) | **GET** /api/v1/pricing/filters | Labels for the pricing table&#39;s ids.
*MarketDataApi* | [**apiV1PricingGet**](docs/Api/MarketDataApi.md#apiv1pricingget) | **GET** /api/v1/pricing | Network floor prices (minimum bids).
*MarketDataApi* | [**apiV1SmartNetworksGet**](docs/Api/MarketDataApi.md#apiv1smartnetworksget) | **GET** /api/v1/smart-networks | List the network&#39;s active smart networks.
*MarketDataApi* | [**apiV1SmartNetworksIdGet**](docs/Api/MarketDataApi.md#apiv1smartnetworksidget) | **GET** /api/v1/smart-networks/{id} | One smart network.
*MarketDataApi* | [**apiV1VolumesFiltersGet**](docs/Api/MarketDataApi.md#apiv1volumesfiltersget) | **GET** /api/v1/volumes/filters | Option lists for the volume query.
*MarketDataApi* | [**apiV1VolumesGet**](docs/Api/MarketDataApi.md#apiv1volumesget) | **GET** /api/v1/volumes | Available traffic volume per country (traffic estimate).
*MarketplaceApi* | [**apiV1MarketplaceFiltersGet**](docs/Api/MarketplaceApi.md#apiv1marketplacefiltersget) | **GET** /api/v1/marketplace/filters | Filter vocabulary for the marketplace.
*MarketplaceApi* | [**apiV1MarketplaceFlatRequestsGet**](docs/Api/MarketplaceApi.md#apiv1marketplaceflatrequestsget) | **GET** /api/v1/marketplace/flat-requests | List my flat-deal requests.
*MarketplaceApi* | [**apiV1MarketplaceFlatRequestsIdCancelPost**](docs/Api/MarketplaceApi.md#apiv1marketplaceflatrequestsidcancelpost) | **POST** /api/v1/marketplace/flat-requests/{id}/cancel | Withdraw a pending flat-deal request.
*MarketplaceApi* | [**apiV1MarketplaceFlatRequestsRenewPost**](docs/Api/MarketplaceApi.md#apiv1marketplaceflatrequestsrenewpost) | **POST** /api/v1/marketplace/flat-requests/renew | Propose one more flat deal on a live flat campaign.
*MarketplaceApi* | [**apiV1MarketplaceGet**](docs/Api/MarketplaceApi.md#apiv1marketplaceget) | **GET** /api/v1/marketplace | List marketplace sites.
*MarketplaceApi* | [**apiV1MarketplaceIdGet**](docs/Api/MarketplaceApi.md#apiv1marketplaceidget) | **GET** /api/v1/marketplace/{id} | Ad zones of one marketplace site, with starting prices.
*OAuthApi* | [**apiPublicIntegrationsHooksTokenPost**](docs/Api/OAuthApi.md#apipublicintegrationshookstokenpost) | **POST** /api/public/integrations/hooks/{token} | Incoming webhook — create a lead, contact or task from JSON.
*OAuthApi* | [**apiPublicOauthIntrospectPost**](docs/Api/OAuthApi.md#apipublicoauthintrospectpost) | **POST** /api/public/oauth/introspect | Introspect one of the calling app&#39;s tokens (RFC 7662).
*OAuthApi* | [**apiPublicOauthMetadataGet**](docs/Api/OAuthApi.md#apipublicoauthmetadataget) | **GET** /api/public/oauth/metadata | Authorization server metadata (RFC 8414) for this host.
*OAuthApi* | [**apiPublicOauthRevokePost**](docs/Api/OAuthApi.md#apipublicoauthrevokepost) | **POST** /api/public/oauth/revoke | Revoke an access or refresh token (RFC 7009).
*OAuthApi* | [**apiPublicOauthTokenPost**](docs/Api/OAuthApi.md#apipublicoauthtokenpost) | **POST** /api/public/oauth/token | Exchange an authorization code, or rotate a refresh token.
*OffersApi* | [**apiV1OffersGet**](docs/Api/OffersApi.md#apiv1offersget) | **GET** /api/v1/offers | List available offers.
*PayoutsApi* | [**apiV1PublisherFinanceEarningsGet**](docs/Api/PayoutsApi.md#apiv1publisherfinanceearningsget) | **GET** /api/v1/publisher/finance/earnings | Earnings per day, week or month.
*PayoutsApi* | [**apiV1PublisherFinanceGet**](docs/Api/PayoutsApi.md#apiv1publisherfinanceget) | **GET** /api/v1/publisher/finance | Publisher balance and payout rules.
*PayoutsApi* | [**apiV1PublisherFinanceMonthEarningsGet**](docs/Api/PayoutsApi.md#apiv1publisherfinancemonthearningsget) | **GET** /api/v1/publisher/finance/month-earnings | Monthly earnings statements.
*PayoutsApi* | [**apiV1PublisherFinancePayoutRequestPost**](docs/Api/PayoutsApi.md#apiv1publisherfinancepayoutrequestpost) | **POST** /api/v1/publisher/finance/payout-request | Request a payout of the balance.
*PayoutsApi* | [**apiV1PublisherFinancePayoutTypesGet**](docs/Api/PayoutsApi.md#apiv1publisherfinancepayouttypesget) | **GET** /api/v1/publisher/finance/payout-types | Payout methods this account may choose.
*PayoutsApi* | [**apiV1PublisherFinancePayoutsGet**](docs/Api/PayoutsApi.md#apiv1publisherfinancepayoutsget) | **GET** /api/v1/publisher/finance/payouts | List payout requests and payouts.
*PayoutsApi* | [**apiV1PublisherFinanceSettingsGet**](docs/Api/PayoutsApi.md#apiv1publisherfinancesettingsget) | **GET** /api/v1/publisher/finance/settings | Payout method and payment details.
*PayoutsApi* | [**apiV1PublisherFinanceSettingsPut**](docs/Api/PayoutsApi.md#apiv1publisherfinancesettingsput) | **PUT** /api/v1/publisher/finance/settings | Change the payout method or payment details.
*PayoutsApi* | [**apiV1PublisherFinanceTransactionsGet**](docs/Api/PayoutsApi.md#apiv1publisherfinancetransactionsget) | **GET** /api/v1/publisher/finance/transactions | List payout transactions (with USD totals).
*PayoutsApi* | [**apiV1PublisherFinanceTransactionsIdGet**](docs/Api/PayoutsApi.md#apiv1publisherfinancetransactionsidget) | **GET** /api/v1/publisher/finance/transactions/{id} | One payout transaction.
*PayoutsApi* | [**apiV1PublisherFinanceTransactionsIdInvoicePdfGet**](docs/Api/PayoutsApi.md#apiv1publisherfinancetransactionsidinvoicepdfget) | **GET** /api/v1/publisher/finance/transactions/{id}/invoice-pdf | Download the self-billing invoice PDF of a paid payout.
*PayoutsApi* | [**apiV1PublisherFinanceTransferToAdvertiserPost**](docs/Api/PayoutsApi.md#apiv1publisherfinancetransfertoadvertiserpost) | **POST** /api/v1/publisher/finance/transfer-to-advertiser | Move publisher balance to the advertiser budget.
*ProductFeedsApi* | [**apiV1ProductFeedsGet**](docs/Api/ProductFeedsApi.md#apiv1productfeedsget) | **GET** /api/v1/product-feeds | List product feeds.
*ProductFeedsApi* | [**apiV1ProductFeedsIdDelete**](docs/Api/ProductFeedsApi.md#apiv1productfeedsiddelete) | **DELETE** /api/v1/product-feeds/{id} | Delete a product feed.
*ProductFeedsApi* | [**apiV1ProductFeedsIdGet**](docs/Api/ProductFeedsApi.md#apiv1productfeedsidget) | **GET** /api/v1/product-feeds/{id} | Show a product feed.
*ProductFeedsApi* | [**apiV1ProductFeedsIdItemsGet**](docs/Api/ProductFeedsApi.md#apiv1productfeedsiditemsget) | **GET** /api/v1/product-feeds/{id}/items | The feed&#39;s products and their ads.
*ProductFeedsApi* | [**apiV1ProductFeedsIdPatch**](docs/Api/ProductFeedsApi.md#apiv1productfeedsidpatch) | **PATCH** /api/v1/product-feeds/{id} | Change a product feed.
*ProductFeedsApi* | [**apiV1ProductFeedsIdSyncPost**](docs/Api/ProductFeedsApi.md#apiv1productfeedsidsyncpost) | **POST** /api/v1/product-feeds/{id}/sync | Sync a product feed now.
*ProductFeedsApi* | [**apiV1ProductFeedsPost**](docs/Api/ProductFeedsApi.md#apiv1productfeedspost) | **POST** /api/v1/product-feeds | Add a product feed.
*ProductFeedsApi* | [**apiV1ProductFeedsPreviewPost**](docs/Api/ProductFeedsApi.md#apiv1productfeedspreviewpost) | **POST** /api/v1/product-feeds/preview | Preview a dynamic banner.
*PublicApi* | [**apiPublicDemoGet**](docs/Api/PublicApi.md#apipublicdemoget) | **GET** /api/public/demo | The network&#39;s ad format demo pages.
*PublicApi* | [**apiPublicDemoSlugGet**](docs/Api/PublicApi.md#apipublicdemoslugget) | **GET** /api/public/demo/{slug} | One ad format demo page (HTML).
*PublicApi* | [**apiPublicOfferwallIdGet**](docs/Api/PublicApi.md#apipublicofferwallidget) | **GET** /api/public/offerwall/{id} | Offerwall list of an offer zone.
*PublicApi* | [**apiPublicOpenapiJsonGet**](docs/Api/PublicApi.md#apipublicopenapijsonget) | **GET** /api/public/openapi.json | OpenAPI spec as JSON.
*PublicApi* | [**apiPublicOpenapiYamlGet**](docs/Api/PublicApi.md#apipublicopenapiyamlget) | **GET** /api/public/openapi.yaml | Raw OpenAPI spec (this document).
*PublicApi* | [**apiPublicRoutesTxtGet**](docs/Api/PublicApi.md#apipublicroutestxtget) | **GET** /api/public/routes.txt | Flat list of public routes — agent-friendly.
*PublisherFiltersApi* | [**apiV1PublisherFiltersGet**](docs/Api/PublisherFiltersApi.md#apiv1publisherfiltersget) | **GET** /api/v1/publisher/filters | List the publisher&#39;s URL block list.
*PublisherFiltersApi* | [**apiV1PublisherFiltersUrlsDelete**](docs/Api/PublisherFiltersApi.md#apiv1publisherfiltersurlsdelete) | **DELETE** /api/v1/publisher/filters/urls | Remove one or more URLs from the block list.
*PublisherFiltersApi* | [**apiV1PublisherFiltersUrlsPost**](docs/Api/PublisherFiltersApi.md#apiv1publisherfiltersurlspost) | **POST** /api/v1/publisher/filters/urls | Add one or more URLs to the block list.
*PushAudienceApi* | [**apiV1PublisherPushDeliveryRulesGet**](docs/Api/PushAudienceApi.md#apiv1publisherpushdeliveryrulesget) | **GET** /api/v1/publisher/push-delivery-rules | List standing delivery rules.
*PushAudienceApi* | [**apiV1PublisherPushDeliveryRulesIdDelete**](docs/Api/PushAudienceApi.md#apiv1publisherpushdeliveryrulesiddelete) | **DELETE** /api/v1/publisher/push-delivery-rules/{id} | Delete a delivery rule.
*PushAudienceApi* | [**apiV1PublisherPushDeliveryRulesIdPut**](docs/Api/PushAudienceApi.md#apiv1publisherpushdeliveryrulesidput) | **PUT** /api/v1/publisher/push-delivery-rules/{id} | Update a delivery rule.
*PushAudienceApi* | [**apiV1PublisherPushDeliveryRulesPost**](docs/Api/PushAudienceApi.md#apiv1publisherpushdeliveryrulespost) | **POST** /api/v1/publisher/push-delivery-rules | Create a delivery rule.
*PushAudienceApi* | [**apiV1PublisherPushSegmentsGet**](docs/Api/PushAudienceApi.md#apiv1publisherpushsegmentsget) | **GET** /api/v1/publisher/push-segments | List saved audience segments.
*PushAudienceApi* | [**apiV1PublisherPushSegmentsIdDelete**](docs/Api/PushAudienceApi.md#apiv1publisherpushsegmentsiddelete) | **DELETE** /api/v1/publisher/push-segments/{id} | Delete an audience segment.
*PushAudienceApi* | [**apiV1PublisherPushSegmentsIdPut**](docs/Api/PushAudienceApi.md#apiv1publisherpushsegmentsidput) | **PUT** /api/v1/publisher/push-segments/{id} | Update an audience segment.
*PushAudienceApi* | [**apiV1PublisherPushSegmentsPost**](docs/Api/PushAudienceApi.md#apiv1publisherpushsegmentspost) | **POST** /api/v1/publisher/push-segments | Create an audience segment.
*PushAudienceApi* | [**apiV1PublisherPushSetupGet**](docs/Api/PushAudienceApi.md#apiv1publisherpushsetupget) | **GET** /api/v1/publisher/push-setup | Collector setup facts for the opt-in script.
*PushAudienceApi* | [**apiV1PublisherPushStatsGet**](docs/Api/PushAudienceApi.md#apiv1publisherpushstatsget) | **GET** /api/v1/publisher/push-stats | Push delivery performance.
*PushAudienceApi* | [**apiV1PublisherPushSubscribersActionPost**](docs/Api/PushAudienceApi.md#apiv1publisherpushsubscribersactionpost) | **POST** /api/v1/publisher/push-subscribers/action | Activate, deactivate or delete subscribers in bulk.
*PushAudienceApi* | [**apiV1PublisherPushSubscribersGet**](docs/Api/PushAudienceApi.md#apiv1publisherpushsubscribersget) | **GET** /api/v1/publisher/push-subscribers | List captured push subscribers.
*PushCampaignsApi* | [**apiV1PublisherPushCampaignsGet**](docs/Api/PushCampaignsApi.md#apiv1publisherpushcampaignsget) | **GET** /api/v1/publisher/push-campaigns | List the caller&#39;s push campaigns.
*PushCampaignsApi* | [**apiV1PublisherPushCampaignsIdDelete**](docs/Api/PushCampaignsApi.md#apiv1publisherpushcampaignsiddelete) | **DELETE** /api/v1/publisher/push-campaigns/{id} | Delete a push campaign.
*PushCampaignsApi* | [**apiV1PublisherPushCampaignsIdPut**](docs/Api/PushCampaignsApi.md#apiv1publisherpushcampaignsidput) | **PUT** /api/v1/publisher/push-campaigns/{id} | Update a push campaign&#39;s notification content.
*PushCampaignsApi* | [**apiV1PublisherPushCampaignsIdSendPost**](docs/Api/PushCampaignsApi.md#apiv1publisherpushcampaignsidsendpost) | **POST** /api/v1/publisher/push-campaigns/{id}/send | Queue a push campaign for delivery.
*PushCampaignsApi* | [**apiV1PublisherPushCampaignsPost**](docs/Api/PushCampaignsApi.md#apiv1publisherpushcampaignspost) | **POST** /api/v1/publisher/push-campaigns | Create a push campaign.
*RetargetingApi* | [**apiV1RetargetingPixelsGet**](docs/Api/RetargetingApi.md#apiv1retargetingpixelsget) | **GET** /api/v1/retargeting/pixels | List the caller&#39;s retargeting pixels.
*RetargetingApi* | [**apiV1RetargetingPixelsIdActivePatch**](docs/Api/RetargetingApi.md#apiv1retargetingpixelsidactivepatch) | **PATCH** /api/v1/retargeting/pixels/{id}/active | Activate or deactivate a retargeting pixel.
*RetargetingApi* | [**apiV1RetargetingPixelsIdDelete**](docs/Api/RetargetingApi.md#apiv1retargetingpixelsiddelete) | **DELETE** /api/v1/retargeting/pixels/{id} | Soft-delete a retargeting pixel and clear its captured visitors.
*RetargetingApi* | [**apiV1RetargetingPixelsIdGet**](docs/Api/RetargetingApi.md#apiv1retargetingpixelsidget) | **GET** /api/v1/retargeting/pixels/{id} | Get one retargeting pixel.
*RetargetingApi* | [**apiV1RetargetingPixelsIdPut**](docs/Api/RetargetingApi.md#apiv1retargetingpixelsidput) | **PUT** /api/v1/retargeting/pixels/{id} | Update a retargeting pixel (name and visitor windows).
*RetargetingApi* | [**apiV1RetargetingPixelsPost**](docs/Api/RetargetingApi.md#apiv1retargetingpixelspost) | **POST** /api/v1/retargeting/pixels | Create a retargeting pixel.
*SSPCampaignsApi* | [**apiV1PublisherSspCampaignsGet**](docs/Api/SSPCampaignsApi.md#apiv1publishersspcampaignsget) | **GET** /api/v1/publisher/ssp/campaigns | List SSP campaigns (feeds).
*SSPCampaignsApi* | [**apiV1PublisherSspCampaignsIdClonePost**](docs/Api/SSPCampaignsApi.md#apiv1publishersspcampaignsidclonepost) | **POST** /api/v1/publisher/ssp/campaigns/{id}/clone | Clone an SSP campaign.
*SSPCampaignsApi* | [**apiV1PublisherSspCampaignsIdGet**](docs/Api/SSPCampaignsApi.md#apiv1publishersspcampaignsidget) | **GET** /api/v1/publisher/ssp/campaigns/{id} | Get one SSP campaign.
*SSPCampaignsApi* | [**apiV1PublisherSspCampaignsIdPut**](docs/Api/SSPCampaignsApi.md#apiv1publishersspcampaignsidput) | **PUT** /api/v1/publisher/ssp/campaigns/{id} | Update an SSP campaign.
*SSPCampaignsApi* | [**apiV1PublisherSspCampaignsIdTestRtbGet**](docs/Api/SSPCampaignsApi.md#apiv1publishersspcampaignsidtestrtbget) | **GET** /api/v1/publisher/ssp/campaigns/{id}/test-rtb | Context for an RTB test request.
*SSPCampaignsApi* | [**apiV1PublisherSspCampaignsIdTestRtbPost**](docs/Api/SSPCampaignsApi.md#apiv1publishersspcampaignsidtestrtbpost) | **POST** /api/v1/publisher/ssp/campaigns/{id}/test-rtb | Send one test bid request to the feed&#39;s endpoint.
*SSPCampaignsApi* | [**apiV1PublisherSspCampaignsIdToggleDeletePut**](docs/Api/SSPCampaignsApi.md#apiv1publishersspcampaignsidtoggledeleteput) | **PUT** /api/v1/publisher/ssp/campaigns/{id}/toggle-delete | Delete or restore an SSP campaign.
*SSPCampaignsApi* | [**apiV1PublisherSspCampaignsIdTogglePausePut**](docs/Api/SSPCampaignsApi.md#apiv1publishersspcampaignsidtogglepauseput) | **PUT** /api/v1/publisher/ssp/campaigns/{id}/toggle-pause | Pause or resume an SSP campaign.
*SSPCampaignsApi* | [**apiV1PublisherSspCampaignsIdWlRonCampaignsGet**](docs/Api/SSPCampaignsApi.md#apiv1publishersspcampaignsidwlroncampaignsget) | **GET** /api/v1/publisher/ssp/campaigns/{id}/wl-ron-campaigns | RON campaigns that can be whitelisted on a feed.
*SSPCampaignsApi* | [**apiV1PublisherSspCampaignsPost**](docs/Api/SSPCampaignsApi.md#apiv1publishersspcampaignspost) | **POST** /api/v1/publisher/ssp/campaigns | Create an SSP campaign.
*SSPCampaignsApi* | [**apiV1PublisherSspCampaignsSetupDataGet**](docs/Api/SSPCampaignsApi.md#apiv1publishersspcampaignssetupdataget) | **GET** /api/v1/publisher/ssp/campaigns/setup-data | Lookups for the SSP campaign form.
*SSPCampaignsApi* | [**apiV1PublisherSspFiltersGet**](docs/Api/SSPCampaignsApi.md#apiv1publishersspfiltersget) | **GET** /api/v1/publisher/ssp/filters | Filter values for the SSP campaign list.
*SSPCampaignsApi* | [**apiV1PublisherSspNetworksGet**](docs/Api/SSPCampaignsApi.md#apiv1publishersspnetworksget) | **GET** /api/v1/publisher/ssp/networks | RTB networks your SSP campaigns use.
*SSPCampaignsApi* | [**apiV1PublisherSspSellerDisclosurePut**](docs/Api/SSPCampaignsApi.md#apiv1publishersspsellerdisclosureput) | **PUT** /api/v1/publisher/ssp/seller-disclosure | Publish or hide your name in sellers.json.
*SSPCampaignsApi* | [**apiV1PublisherSspSitesIdAdsTxtCheckPost**](docs/Api/SSPCampaignsApi.md#apiv1publishersspsitesidadstxtcheckpost) | **POST** /api/v1/publisher/ssp/sites/{id}/ads-txt-check | Re-check a site&#39;s ads.txt now.
*SSPCampaignsApi* | [**apiV1PublisherSspSitesIdAdsTxtStatusGet**](docs/Api/SSPCampaignsApi.md#apiv1publishersspsitesidadstxtstatusget) | **GET** /api/v1/publisher/ssp/sites/{id}/ads-txt-status | Last stored ads.txt check for a site.
*SharedReportsApi* | [**apiV1SharedReportsGet**](docs/Api/SharedReportsApi.md#apiv1sharedreportsget) | **GET** /api/v1/shared-reports | List shared reports.
*SharedReportsApi* | [**apiV1SharedReportsIdDelete**](docs/Api/SharedReportsApi.md#apiv1sharedreportsiddelete) | **DELETE** /api/v1/shared-reports/{id} | Delete a shared report.
*SharedReportsApi* | [**apiV1SharedReportsIdGet**](docs/Api/SharedReportsApi.md#apiv1sharedreportsidget) | **GET** /api/v1/shared-reports/{id} | Show a shared report.
*SharedReportsApi* | [**apiV1SharedReportsIdPut**](docs/Api/SharedReportsApi.md#apiv1sharedreportsidput) | **PUT** /api/v1/shared-reports/{id} | Update a shared report.
*SharedReportsApi* | [**apiV1SharedReportsIdToggleActivePut**](docs/Api/SharedReportsApi.md#apiv1sharedreportsidtoggleactiveput) | **PUT** /api/v1/shared-reports/{id}/toggle-active | Switch a report&#39;s link on or off.
*SharedReportsApi* | [**apiV1SharedReportsPost**](docs/Api/SharedReportsApi.md#apiv1sharedreportspost) | **POST** /api/v1/shared-reports | Create a shared report.
*SharedReportsApi* | [**apiV1SharedReportsSetupDataGet**](docs/Api/SharedReportsApi.md#apiv1sharedreportssetupdataget) | **GET** /api/v1/shared-reports/setup-data | Campaigns and sites a report can cover.
*SitesApi* | [**apiV1DomainsGet**](docs/Api/SitesApi.md#apiv1domainsget) | **GET** /api/v1/domains | List your custom domains (Domain Manager).
*SitesApi* | [**apiV1DomainsIdDelete**](docs/Api/SitesApi.md#apiv1domainsiddelete) | **DELETE** /api/v1/domains/{id} | Delete a custom domain.
*SitesApi* | [**apiV1DomainsIdGet**](docs/Api/SitesApi.md#apiv1domainsidget) | **GET** /api/v1/domains/{id} | Show one custom domain.
*SitesApi* | [**apiV1DomainsIdNotesPut**](docs/Api/SitesApi.md#apiv1domainsidnotesput) | **PUT** /api/v1/domains/{id}/notes | Set your notes on a domain.
*SitesApi* | [**apiV1DomainsIdRedoPost**](docs/Api/SitesApi.md#apiv1domainsidredopost) | **POST** /api/v1/domains/{id}/redo | Retry setting up a domain (DNS / certificate check again).
*SitesApi* | [**apiV1DomainsIdToggleActivePost**](docs/Api/SitesApi.md#apiv1domainsidtoggleactivepost) | **POST** /api/v1/domains/{id}/toggle-active | Switch a custom domain on or off.
*SitesApi* | [**apiV1DomainsPost**](docs/Api/SitesApi.md#apiv1domainspost) | **POST** /api/v1/domains | Add a custom domain.
*SitesApi* | [**apiV1SitesGet**](docs/Api/SitesApi.md#apiv1sitesget) | **GET** /api/v1/sites | List the caller&#39;s websites.
*SitesApi* | [**apiV1SitesIdClonePost**](docs/Api/SitesApi.md#apiv1sitesidclonepost) | **POST** /api/v1/sites/{id}/clone | Duplicate a site under a new name and URL, with its ad zones.
*SitesApi* | [**apiV1SitesIdDelete**](docs/Api/SitesApi.md#apiv1sitesiddelete) | **DELETE** /api/v1/sites/{id} | Soft-delete a site.
*SitesApi* | [**apiV1SitesIdGet**](docs/Api/SitesApi.md#apiv1sitesidget) | **GET** /api/v1/sites/{id} | Get a single site.
*SitesApi* | [**apiV1SitesIdPatch**](docs/Api/SitesApi.md#apiv1sitesidpatch) | **PATCH** /api/v1/sites/{id} | Update a site.
*SitesApi* | [**apiV1SitesIdRecoverPost**](docs/Api/SitesApi.md#apiv1sitesidrecoverpost) | **POST** /api/v1/sites/{id}/recover | Reverse a soft-delete on a site.
*SitesApi* | [**apiV1SitesIdSnippetsGet**](docs/Api/SitesApi.md#apiv1sitesidsnippetsget) | **GET** /api/v1/sites/{id}/snippets | List the site&#39;s HTML snippets.
*SitesApi* | [**apiV1SitesIdSnippetsPost**](docs/Api/SitesApi.md#apiv1sitesidsnippetspost) | **POST** /api/v1/sites/{id}/snippets | Add an HTML snippet.
*SitesApi* | [**apiV1SitesIdSnippetsSnippetIdDelete**](docs/Api/SitesApi.md#apiv1sitesidsnippetssnippetiddelete) | **DELETE** /api/v1/sites/{id}/snippets/{snippetId} | Delete an HTML snippet.
*SitesApi* | [**apiV1SitesIdSnippetsSnippetIdPut**](docs/Api/SitesApi.md#apiv1sitesidsnippetssnippetidput) | **PUT** /api/v1/sites/{id}/snippets/{snippetId} | Replace an HTML snippet.
*SitesApi* | [**apiV1SitesIdVerifyPost**](docs/Api/SitesApi.md#apiv1sitesidverifypost) | **POST** /api/v1/sites/{id}/verify | Trigger HTTP re-check of the site verification meta tag.
*SitesApi* | [**apiV1SitesPost**](docs/Api/SitesApi.md#apiv1sitespost) | **POST** /api/v1/sites | Create a new website.
*SitesApi* | [**apiV1SitesSchemaGet**](docs/Api/SitesApi.md#apiv1sitesschemaget) | **GET** /api/v1/sites/_schema | Field metadata for site create/update.
*SmartZonesApi* | [**apiV1PublisherSmartzonesDistributionAdspacesIdGet**](docs/Api/SmartZonesApi.md#apiv1publishersmartzonesdistributionadspacesidget) | **GET** /api/v1/publisher/smartzones/distribution/adspaces/{id} | Network split for one ad zone.
*SmartZonesApi* | [**apiV1PublisherSmartzonesDistributionPerSiteGet**](docs/Api/SmartZonesApi.md#apiv1publishersmartzonesdistributionpersiteget) | **GET** /api/v1/publisher/smartzones/distribution/per-site | Traffic and earnings split across networks for one site.
*SmartZonesApi* | [**apiV1PublisherSmartzonesDistributionSitesGet**](docs/Api/SmartZonesApi.md#apiv1publishersmartzonesdistributionsitesget) | **GET** /api/v1/publisher/smartzones/distribution/sites | Sites that carry smart zones.
*SmartZonesApi* | [**apiV1PublisherSmartzonesGet**](docs/Api/SmartZonesApi.md#apiv1publishersmartzonesget) | **GET** /api/v1/publisher/smartzones | List smart zones.
*SmartZonesApi* | [**apiV1PublisherSmartzonesIdDelete**](docs/Api/SmartZonesApi.md#apiv1publishersmartzonesiddelete) | **DELETE** /api/v1/publisher/smartzones/{id} | Delete a smart zone.
*SmartZonesApi* | [**apiV1PublisherSmartzonesIdGet**](docs/Api/SmartZonesApi.md#apiv1publishersmartzonesidget) | **GET** /api/v1/publisher/smartzones/{id} | Get one smart zone.
*SmartZonesApi* | [**apiV1PublisherSmartzonesIdPut**](docs/Api/SmartZonesApi.md#apiv1publishersmartzonesidput) | **PUT** /api/v1/publisher/smartzones/{id} | Update a smart zone.
*SmartZonesApi* | [**apiV1PublisherSmartzonesImportPost**](docs/Api/SmartZonesApi.md#apiv1publishersmartzonesimportpost) | **POST** /api/v1/publisher/smartzones/import | Bulk import smart zones.
*SmartZonesApi* | [**apiV1PublisherSmartzonesNetworksGet**](docs/Api/SmartZonesApi.md#apiv1publishersmartzonesnetworksget) | **GET** /api/v1/publisher/smartzones/networks | List your smart network accounts.
*SmartZonesApi* | [**apiV1PublisherSmartzonesNetworksIdDelete**](docs/Api/SmartZonesApi.md#apiv1publishersmartzonesnetworksiddelete) | **DELETE** /api/v1/publisher/smartzones/networks/{id} | Remove a smart network account.
*SmartZonesApi* | [**apiV1PublisherSmartzonesNetworksPost**](docs/Api/SmartZonesApi.md#apiv1publishersmartzonesnetworkspost) | **POST** /api/v1/publisher/smartzones/networks | Add or update a smart network account.
*SmartZonesApi* | [**apiV1PublisherSmartzonesPost**](docs/Api/SmartZonesApi.md#apiv1publishersmartzonespost) | **POST** /api/v1/publisher/smartzones | Create a smart zone.
*SmartZonesApi* | [**apiV1PublisherSmartzonesSetupDataGet**](docs/Api/SmartZonesApi.md#apiv1publishersmartzonessetupdataget) | **GET** /api/v1/publisher/smartzones/setup-data | Lookups for the smart-zone form.
*SmartZonesApi* | [**apiV1PublisherSmartzonesSspCampaignsGet**](docs/Api/SmartZonesApi.md#apiv1publishersmartzonessspcampaignsget) | **GET** /api/v1/publisher/smartzones/ssp-campaigns | SSP campaigns usable as a smart zone&#39;s RTB feed.
*SourceOptimizersApi* | [**apiV1CpaOptimizersGet**](docs/Api/SourceOptimizersApi.md#apiv1cpaoptimizersget) | **GET** /api/v1/cpa-optimizers | List CPA optimizers.
*SourceOptimizersApi* | [**apiV1CpaOptimizersIdDelete**](docs/Api/SourceOptimizersApi.md#apiv1cpaoptimizersiddelete) | **DELETE** /api/v1/cpa-optimizers/{id} | Delete a CPA optimizer.
*SourceOptimizersApi* | [**apiV1CpaOptimizersIdGet**](docs/Api/SourceOptimizersApi.md#apiv1cpaoptimizersidget) | **GET** /api/v1/cpa-optimizers/{id} | Show a CPA optimizer.
*SourceOptimizersApi* | [**apiV1CpaOptimizersIdPut**](docs/Api/SourceOptimizersApi.md#apiv1cpaoptimizersidput) | **PUT** /api/v1/cpa-optimizers/{id} | Replace a CPA optimizer.
*SourceOptimizersApi* | [**apiV1CpaOptimizersPost**](docs/Api/SourceOptimizersApi.md#apiv1cpaoptimizerspost) | **POST** /api/v1/cpa-optimizers | Create a CPA optimizer.
*SourceOptimizersApi* | [**apiV1CpmOptimizersActiveGet**](docs/Api/SourceOptimizersApi.md#apiv1cpmoptimizersactiveget) | **GET** /api/v1/cpm-optimizers/active | Does the account have any CPM optimizer?
*SourceOptimizersApi* | [**apiV1CpmOptimizersGet**](docs/Api/SourceOptimizersApi.md#apiv1cpmoptimizersget) | **GET** /api/v1/cpm-optimizers | List CPM optimizers.
*SourceOptimizersApi* | [**apiV1CpmOptimizersIdDelete**](docs/Api/SourceOptimizersApi.md#apiv1cpmoptimizersiddelete) | **DELETE** /api/v1/cpm-optimizers/{id} | Delete a CPM optimizer.
*SourceOptimizersApi* | [**apiV1CpmOptimizersIdGet**](docs/Api/SourceOptimizersApi.md#apiv1cpmoptimizersidget) | **GET** /api/v1/cpm-optimizers/{id} | Show a CPM optimizer.
*SourceOptimizersApi* | [**apiV1CpmOptimizersIdPut**](docs/Api/SourceOptimizersApi.md#apiv1cpmoptimizersidput) | **PUT** /api/v1/cpm-optimizers/{id} | Replace a CPM optimizer.
*SourceOptimizersApi* | [**apiV1CpmOptimizersPost**](docs/Api/SourceOptimizersApi.md#apiv1cpmoptimizerspost) | **POST** /api/v1/cpm-optimizers | Create a CPM optimizer.
*SourceOptimizersApi* | [**apiV1SourceOptimizerConfigGet**](docs/Api/SourceOptimizersApi.md#apiv1sourceoptimizerconfigget) | **GET** /api/v1/source-optimizer/config | Option values for the optimizer forms.
*StatsAdvertiserApi* | [**apiV1AdvertiserStatsGet**](docs/Api/StatsAdvertiserApi.md#apiv1advertiserstatsget) | **GET** /api/v1/advertiser/stats | Display, native and CPM/RON campaign stats.
*StatsAdvertiserOffersApi* | [**apiV1AdvertiserOffersStatsGet**](docs/Api/StatsAdvertiserOffersApi.md#apiv1advertiseroffersstatsget) | **GET** /api/v1/advertiser/offers/stats | CPA / affiliate offer performance — advertiser side.
*StatsAdvertiserRTBApi* | [**apiV1AdvertiserRtbStatsGet**](docs/Api/StatsAdvertiserRTBApi.md#apiv1advertiserrtbstatsget) | **GET** /api/v1/advertiser/rtb/stats | DSP / RTB-side stats.
*StatsAffiliateApi* | [**apiV1PublisherOffersPostbackLogGet**](docs/Api/StatsAffiliateApi.md#apiv1publisherofferspostbacklogget) | **GET** /api/v1/publisher/offers/postback-log | Postback log — the conversion postbacks fired to your URLs.
*StatsAffiliateApi* | [**apiV1PublisherOffersStatsGet**](docs/Api/StatsAffiliateApi.md#apiv1publisheroffersstatsget) | **GET** /api/v1/publisher/offers/stats | Affiliate / smartlink offer performance — publisher side.
*StatsPublisherApi* | [**apiV1PublisherStatsGet**](docs/Api/StatsPublisherApi.md#apiv1publisherstatsget) | **GET** /api/v1/publisher/stats | Publisher earnings (sites / adzones).
*StatsPublisherSSPApi* | [**apiV1PublisherSspStatsDetailsGet**](docs/Api/StatsPublisherSSPApi.md#apiv1publishersspstatsdetailsget) | **GET** /api/v1/publisher/ssp/stats/details | SSP report broken down by one dimension.
*StatsPublisherSSPApi* | [**apiV1PublisherSspStatsFiltersGet**](docs/Api/StatsPublisherSSPApi.md#apiv1publishersspstatsfiltersget) | **GET** /api/v1/publisher/ssp/stats/filters | Filter values for the SSP reports.
*StatsPublisherSSPApi* | [**apiV1PublisherSspStatsFlatGet**](docs/Api/StatsPublisherSSPApi.md#apiv1publishersspstatsflatget) | **GET** /api/v1/publisher/ssp/stats/flat | SSP report grouped by several dimensions.
*StatsPublisherSSPApi* | [**apiV1PublisherSspStatsGet**](docs/Api/StatsPublisherSSPApi.md#apiv1publishersspstatsget) | **GET** /api/v1/publisher/ssp/stats | SSP / smart-campaign earnings.
*StatsPublisherSmartZonesApi* | [**apiV1PublisherSmartzonesStatsFiltersGet**](docs/Api/StatsPublisherSmartZonesApi.md#apiv1publishersmartzonesstatsfiltersget) | **GET** /api/v1/publisher/smartzones/stats/filters | Filter values for smart-zone stats.
*StatsPublisherSmartZonesApi* | [**apiV1PublisherSmartzonesStatsGet**](docs/Api/StatsPublisherSmartZonesApi.md#apiv1publishersmartzonesstatsget) | **GET** /api/v1/publisher/smartzones/stats | SmartZone (waterfall) earnings.
*TagsApi* | [**apiV1TagsGet**](docs/Api/TagsApi.md#apiv1tagsget) | **GET** /api/v1/tags | List tags.
*TagsApi* | [**apiV1TagsIdDelete**](docs/Api/TagsApi.md#apiv1tagsiddelete) | **DELETE** /api/v1/tags/{id} | Delete a tag.
*TagsApi* | [**apiV1TagsIdGet**](docs/Api/TagsApi.md#apiv1tagsidget) | **GET** /api/v1/tags/{id} | Show a tag.
*TagsApi* | [**apiV1TagsIdPut**](docs/Api/TagsApi.md#apiv1tagsidput) | **PUT** /api/v1/tags/{id} | Rename or move a tag.
*TagsApi* | [**apiV1TagsMassDeletePost**](docs/Api/TagsApi.md#apiv1tagsmassdeletepost) | **POST** /api/v1/tags/mass-delete | Delete several tags.
*TagsApi* | [**apiV1TagsPost**](docs/Api/TagsApi.md#apiv1tagspost) | **POST** /api/v1/tags | Create a tag.
*TrackerAutomationApi* | [**apiV1TrackerExportTypeGet**](docs/Api/TrackerAutomationApi.md#apiv1trackerexporttypeget) | **GET** /api/v1/tracker/export/{type} | Export tracker entities as CSV.
*TrackerAutomationApi* | [**apiV1TrackerImportTypePost**](docs/Api/TrackerAutomationApi.md#apiv1trackerimporttypepost) | **POST** /api/v1/tracker/import/{type} | Bulk-import offers from CSV.
*TrackerAutomationApi* | [**apiV1TrackerReportSchedulesGet**](docs/Api/TrackerAutomationApi.md#apiv1trackerreportschedulesget) | **GET** /api/v1/tracker/report-schedules | List scheduled report deliveries.
*TrackerAutomationApi* | [**apiV1TrackerReportSchedulesIdDelete**](docs/Api/TrackerAutomationApi.md#apiv1trackerreportschedulesiddelete) | **DELETE** /api/v1/tracker/report-schedules/{id} | Delete a report schedule.
*TrackerAutomationApi* | [**apiV1TrackerReportSchedulesIdGet**](docs/Api/TrackerAutomationApi.md#apiv1trackerreportschedulesidget) | **GET** /api/v1/tracker/report-schedules/{id} | Get one report schedule.
*TrackerAutomationApi* | [**apiV1TrackerReportSchedulesIdPut**](docs/Api/TrackerAutomationApi.md#apiv1trackerreportschedulesidput) | **PUT** /api/v1/tracker/report-schedules/{id} | Update a report schedule.
*TrackerAutomationApi* | [**apiV1TrackerReportSchedulesPost**](docs/Api/TrackerAutomationApi.md#apiv1trackerreportschedulespost) | **POST** /api/v1/tracker/report-schedules | Schedule a recurring report.
*TrackerAutomationApi* | [**apiV1TrackerRulesGet**](docs/Api/TrackerAutomationApi.md#apiv1trackerrulesget) | **GET** /api/v1/tracker/rules | List automation rules.
*TrackerAutomationApi* | [**apiV1TrackerRulesIdDelete**](docs/Api/TrackerAutomationApi.md#apiv1trackerrulesiddelete) | **DELETE** /api/v1/tracker/rules/{id} | Delete a rule.
*TrackerAutomationApi* | [**apiV1TrackerRulesIdExecutionsGet**](docs/Api/TrackerAutomationApi.md#apiv1trackerrulesidexecutionsget) | **GET** /api/v1/tracker/rules/{id}/executions | Execution history for one rule.
*TrackerAutomationApi* | [**apiV1TrackerRulesIdGet**](docs/Api/TrackerAutomationApi.md#apiv1trackerrulesidget) | **GET** /api/v1/tracker/rules/{id} | Get one rule.
*TrackerAutomationApi* | [**apiV1TrackerRulesIdPut**](docs/Api/TrackerAutomationApi.md#apiv1trackerrulesidput) | **PUT** /api/v1/tracker/rules/{id} | Update a rule.
*TrackerAutomationApi* | [**apiV1TrackerRulesIdRunPost**](docs/Api/TrackerAutomationApi.md#apiv1trackerrulesidrunpost) | **POST** /api/v1/tracker/rules/{id}/run | Run a rule now.
*TrackerAutomationApi* | [**apiV1TrackerRulesPost**](docs/Api/TrackerAutomationApi.md#apiv1trackerrulespost) | **POST** /api/v1/tracker/rules | Create an automation rule.
*TrackerConversionsApi* | [**apiV1TrackerConversionLoggingGet**](docs/Api/TrackerConversionsApi.md#apiv1trackerconversionloggingget) | **GET** /api/v1/tracker/conversion-logging | Get conversion-logging settings.
*TrackerConversionsApi* | [**apiV1TrackerConversionLoggingPut**](docs/Api/TrackerConversionsApi.md#apiv1trackerconversionloggingput) | **PUT** /api/v1/tracker/conversion-logging | Change conversion-logging settings.
*TrackerConversionsApi* | [**apiV1TrackerConversionTypesGet**](docs/Api/TrackerConversionsApi.md#apiv1trackerconversiontypesget) | **GET** /api/v1/tracker/conversion-types | Get the conversion-type labels.
*TrackerConversionsApi* | [**apiV1TrackerConversionTypesPut**](docs/Api/TrackerConversionsApi.md#apiv1trackerconversiontypesput) | **PUT** /api/v1/tracker/conversion-types | Rename the conversion types.
*TrackerConversionsApi* | [**apiV1TrackerConversionsClickidPostbacksGet**](docs/Api/TrackerConversionsApi.md#apiv1trackerconversionsclickidpostbacksget) | **GET** /api/v1/tracker/conversions/{clickid}/postbacks | Outbound postbacks fired for one conversion.
*TrackerConversionsApi* | [**apiV1TrackerConversionsGet**](docs/Api/TrackerConversionsApi.md#apiv1trackerconversionsget) | **GET** /api/v1/tracker/conversions | List incoming conversions.
*TrackerConversionsApi* | [**apiV1TrackerConversionsIdStatePut**](docs/Api/TrackerConversionsApi.md#apiv1trackerconversionsidstateput) | **PUT** /api/v1/tracker/conversions/{id}/state | Approve or reject one conversion.
*TrackerConversionsApi* | [**apiV1TrackerConversionsRejectedGet**](docs/Api/TrackerConversionsApi.md#apiv1trackerconversionsrejectedget) | **GET** /api/v1/tracker/conversions/rejected | List refused inbound postbacks.
*TrackerNetworksApi* | [**apiV1TrackerAdNetworksDeleteReportsPut**](docs/Api/TrackerNetworksApi.md#apiv1trackeradnetworksdeletereportsput) | **PUT** /api/v1/tracker/ad-networks/delete-reports | Delete pulled spend reports.
*TrackerNetworksApi* | [**apiV1TrackerAdNetworksGet**](docs/Api/TrackerNetworksApi.md#apiv1trackeradnetworksget) | **GET** /api/v1/tracker/ad-networks | List ad networks.
*TrackerNetworksApi* | [**apiV1TrackerAdNetworksIdCampaignsGet**](docs/Api/TrackerNetworksApi.md#apiv1trackeradnetworksidcampaignsget) | **GET** /api/v1/tracker/ad-networks/{id}/campaigns | Campaigns known on one ad network.
*TrackerNetworksApi* | [**apiV1TrackerAdNetworksIdDelete**](docs/Api/TrackerNetworksApi.md#apiv1trackeradnetworksiddelete) | **DELETE** /api/v1/tracker/ad-networks/{id} | Delete an ad network.
*TrackerNetworksApi* | [**apiV1TrackerAdNetworksIdGet**](docs/Api/TrackerNetworksApi.md#apiv1trackeradnetworksidget) | **GET** /api/v1/tracker/ad-networks/{id} | Get one ad network.
*TrackerNetworksApi* | [**apiV1TrackerAdNetworksIdPut**](docs/Api/TrackerNetworksApi.md#apiv1trackeradnetworksidput) | **PUT** /api/v1/tracker/ad-networks/{id} | Update an ad network.
*TrackerNetworksApi* | [**apiV1TrackerAdNetworksPost**](docs/Api/TrackerNetworksApi.md#apiv1trackeradnetworkspost) | **POST** /api/v1/tracker/ad-networks | Create an ad network.
*TrackerNetworksApi* | [**apiV1TrackerAdNetworksReportsGet**](docs/Api/TrackerNetworksApi.md#apiv1trackeradnetworksreportsget) | **GET** /api/v1/tracker/ad-networks/reports | Spend reports pulled from the ad networks.
*TrackerNetworksApi* | [**apiV1TrackerAffiliateNetworkTemplatesGet**](docs/Api/TrackerNetworksApi.md#apiv1trackeraffiliatenetworktemplatesget) | **GET** /api/v1/tracker/affiliate-network-templates | List affiliate-network templates.
*TrackerNetworksApi* | [**apiV1TrackerAffiliateNetworksGet**](docs/Api/TrackerNetworksApi.md#apiv1trackeraffiliatenetworksget) | **GET** /api/v1/tracker/affiliate-networks | List affiliate networks.
*TrackerNetworksApi* | [**apiV1TrackerAffiliateNetworksIdDelete**](docs/Api/TrackerNetworksApi.md#apiv1trackeraffiliatenetworksiddelete) | **DELETE** /api/v1/tracker/affiliate-networks/{id} | Delete an affiliate network.
*TrackerNetworksApi* | [**apiV1TrackerAffiliateNetworksIdPut**](docs/Api/TrackerNetworksApi.md#apiv1trackeraffiliatenetworksidput) | **PUT** /api/v1/tracker/affiliate-networks/{id} | Update an affiliate network.
*TrackerNetworksApi* | [**apiV1TrackerAffiliateNetworksPost**](docs/Api/TrackerNetworksApi.md#apiv1trackeraffiliatenetworkspost) | **POST** /api/v1/tracker/affiliate-networks | Create an affiliate network.
*TrackerOffersApi* | [**apiV1TrackerOffersBulkPost**](docs/Api/TrackerOffersApi.md#apiv1trackeroffersbulkpost) | **POST** /api/v1/tracker/offers/bulk | Pause, activate or delete many offers.
*TrackerOffersApi* | [**apiV1TrackerOffersGet**](docs/Api/TrackerOffersApi.md#apiv1trackeroffersget) | **GET** /api/v1/tracker/offers | List tracker offers.
*TrackerOffersApi* | [**apiV1TrackerOffersIdDelete**](docs/Api/TrackerOffersApi.md#apiv1trackeroffersiddelete) | **DELETE** /api/v1/tracker/offers/{id} | Delete an offer.
*TrackerOffersApi* | [**apiV1TrackerOffersIdGet**](docs/Api/TrackerOffersApi.md#apiv1trackeroffersidget) | **GET** /api/v1/tracker/offers/{id} | Get one offer.
*TrackerOffersApi* | [**apiV1TrackerOffersIdPut**](docs/Api/TrackerOffersApi.md#apiv1trackeroffersidput) | **PUT** /api/v1/tracker/offers/{id} | Update an offer.
*TrackerOffersApi* | [**apiV1TrackerOffersIdUrlsPost**](docs/Api/TrackerOffersApi.md#apiv1trackeroffersidurlspost) | **POST** /api/v1/tracker/offers/{id}/urls | Add a landing URL to an offer.
*TrackerOffersApi* | [**apiV1TrackerOffersIdUrlsUrlIdDelete**](docs/Api/TrackerOffersApi.md#apiv1trackeroffersidurlsurliddelete) | **DELETE** /api/v1/tracker/offers/{id}/urls/{urlId} | Delete one landing URL.
*TrackerOffersApi* | [**apiV1TrackerOffersIdUrlsUrlIdPut**](docs/Api/TrackerOffersApi.md#apiv1trackeroffersidurlsurlidput) | **PUT** /api/v1/tracker/offers/{id}/urls/{urlId} | Update one landing URL.
*TrackerOffersApi* | [**apiV1TrackerOffersPost**](docs/Api/TrackerOffersApi.md#apiv1trackerofferspost) | **POST** /api/v1/tracker/offers | Create an offer.
*TrackerReportsApi* | [**apiV1TrackerCostSummaryGet**](docs/Api/TrackerReportsApi.md#apiv1trackercostsummaryget) | **GET** /api/v1/tracker/cost-summary | Spend against revenue.
*TrackerReportsApi* | [**apiV1TrackerDailyStatsGet**](docs/Api/TrackerReportsApi.md#apiv1trackerdailystatsget) | **GET** /api/v1/tracker/daily-stats | Daily performance, filtered.
*TrackerReportsApi* | [**apiV1TrackerInlineStatsGet**](docs/Api/TrackerReportsApi.md#apiv1trackerinlinestatsget) | **GET** /api/v1/tracker/inline-stats | Per-row performance for a listing.
*TrackerReportsApi* | [**apiV1TrackerPlayerLtvGet**](docs/Api/TrackerReportsApi.md#apiv1trackerplayerltvget) | **GET** /api/v1/tracker/player-ltv | Lifetime value by cohort.
*TrackerReportsApi* | [**apiV1TrackerPostbacksStatsGet**](docs/Api/TrackerReportsApi.md#apiv1trackerpostbacksstatsget) | **GET** /api/v1/tracker/postbacks/stats | Outbound postback firing log.
*TrackerReportsApi* | [**apiV1TrackerRecentClicksGet**](docs/Api/TrackerReportsApi.md#apiv1trackerrecentclicksget) | **GET** /api/v1/tracker/recent-clicks | The most recent clicks.
*TrackerSettingsApi* | [**apiV1TrackerCountriesGet**](docs/Api/TrackerSettingsApi.md#apiv1trackercountriesget) | **GET** /api/v1/tracker/countries | List countries seen in the caller&#39;s traffic.
*TrackerSettingsApi* | [**apiV1TrackerCustomParamsGet**](docs/Api/TrackerSettingsApi.md#apiv1trackercustomparamsget) | **GET** /api/v1/tracker/custom-params | Get the custom-parameter slots.
*TrackerSettingsApi* | [**apiV1TrackerCustomParamsPut**](docs/Api/TrackerSettingsApi.md#apiv1trackercustomparamsput) | **PUT** /api/v1/tracker/custom-params | Name the custom-parameter slots.
*TrackerSettingsApi* | [**apiV1TrackerSettingsGet**](docs/Api/TrackerSettingsApi.md#apiv1trackersettingsget) | **GET** /api/v1/tracker/settings | Tracker settings for this account.
*TrackerSettingsApi* | [**apiV1TrackerSourceTemplatesGet**](docs/Api/TrackerSettingsApi.md#apiv1trackersourcetemplatesget) | **GET** /api/v1/tracker/source-templates | List traffic-source templates.
*TrackerSettingsApi* | [**apiV1TrackerTokensGet**](docs/Api/TrackerSettingsApi.md#apiv1trackertokensget) | **GET** /api/v1/tracker/tokens | List the macro tokens.
*TrackerSourcesApi* | [**apiV1TrackerCostDriversGet**](docs/Api/TrackerSourcesApi.md#apiv1trackercostdriversget) | **GET** /api/v1/tracker/cost-drivers | List available cost drivers.
*TrackerSourcesApi* | [**apiV1TrackerSourceAccountsGet**](docs/Api/TrackerSourcesApi.md#apiv1trackersourceaccountsget) | **GET** /api/v1/tracker/source-accounts | List connected ad accounts.
*TrackerSourcesApi* | [**apiV1TrackerSourceAccountsIdCampaignsGet**](docs/Api/TrackerSourcesApi.md#apiv1trackersourceaccountsidcampaignsget) | **GET** /api/v1/tracker/source-accounts/{id}/campaigns | Campaigns visible in a connected ad account.
*TrackerSourcesApi* | [**apiV1TrackerSourceAccountsIdDelete**](docs/Api/TrackerSourcesApi.md#apiv1trackersourceaccountsiddelete) | **DELETE** /api/v1/tracker/source-accounts/{id} | Disconnect an ad account.
*TrackerSourcesApi* | [**apiV1TrackerSourceAccountsIdGet**](docs/Api/TrackerSourcesApi.md#apiv1trackersourceaccountsidget) | **GET** /api/v1/tracker/source-accounts/{id} | Get one connected ad account.
*TrackerSourcesApi* | [**apiV1TrackerSourceAccountsIdPut**](docs/Api/TrackerSourcesApi.md#apiv1trackersourceaccountsidput) | **PUT** /api/v1/tracker/source-accounts/{id} | Update a connected ad account.
*TrackerSourcesApi* | [**apiV1TrackerSourceAccountsIdSyncPost**](docs/Api/TrackerSourcesApi.md#apiv1trackersourceaccountsidsyncpost) | **POST** /api/v1/tracker/source-accounts/{id}/sync | Pull spend from a connected ad account now.
*TrackerSourcesApi* | [**apiV1TrackerSourceAccountsPost**](docs/Api/TrackerSourcesApi.md#apiv1trackersourceaccountspost) | **POST** /api/v1/tracker/source-accounts | Connect an ad account.
*TrackerSourcesApi* | [**apiV1TrackerSourcesGet**](docs/Api/TrackerSourcesApi.md#apiv1trackersourcesget) | **GET** /api/v1/tracker/sources | List traffic sources.
*TrackerSourcesApi* | [**apiV1TrackerSourcesIdDelete**](docs/Api/TrackerSourcesApi.md#apiv1trackersourcesiddelete) | **DELETE** /api/v1/tracker/sources/{id} | Delete a traffic source.
*TrackerSourcesApi* | [**apiV1TrackerSourcesIdPut**](docs/Api/TrackerSourcesApi.md#apiv1trackersourcesidput) | **PUT** /api/v1/tracker/sources/{id} | Update a traffic source.
*TrackerSourcesApi* | [**apiV1TrackerSourcesPost**](docs/Api/TrackerSourcesApi.md#apiv1trackersourcespost) | **POST** /api/v1/tracker/sources | Create a traffic source.
*TrackerSourcesApi* | [**apiV1TrackerSourcesSiteidCampaignsGet**](docs/Api/TrackerSourcesApi.md#apiv1trackersourcessiteidcampaignsget) | **GET** /api/v1/tracker/sources/{siteid}/campaigns | Campaigns reported by a source&#39;s connected ad account.
*TrackerTrackingLinksApi* | [**apiV1TrackerTrackingLinksBulkPost**](docs/Api/TrackerTrackingLinksApi.md#apiv1trackertrackinglinksbulkpost) | **POST** /api/v1/tracker/tracking-links/bulk | Delete many tracking links.
*TrackerTrackingLinksApi* | [**apiV1TrackerTrackingLinksGet**](docs/Api/TrackerTrackingLinksApi.md#apiv1trackertrackinglinksget) | **GET** /api/v1/tracker/tracking-links | List tracking links.
*TrackerTrackingLinksApi* | [**apiV1TrackerTrackingLinksIdBlocklistGet**](docs/Api/TrackerTrackingLinksApi.md#apiv1trackertrackinglinksidblocklistget) | **GET** /api/v1/tracker/tracking-links/{id}/blocklist | List zones blocked on one link.
*TrackerTrackingLinksApi* | [**apiV1TrackerTrackingLinksIdBlocklistPost**](docs/Api/TrackerTrackingLinksApi.md#apiv1trackertrackinglinksidblocklistpost) | **POST** /api/v1/tracker/tracking-links/{id}/blocklist | Block a zone on one link.
*TrackerTrackingLinksApi* | [**apiV1TrackerTrackingLinksIdBlocklistZoneIdDelete**](docs/Api/TrackerTrackingLinksApi.md#apiv1trackertrackinglinksidblocklistzoneiddelete) | **DELETE** /api/v1/tracker/tracking-links/{id}/blocklist/{zoneId} | Unblock a zone on one link.
*TrackerTrackingLinksApi* | [**apiV1TrackerTrackingLinksIdDelete**](docs/Api/TrackerTrackingLinksApi.md#apiv1trackertrackinglinksiddelete) | **DELETE** /api/v1/tracker/tracking-links/{id} | Delete a tracking link.
*TrackerTrackingLinksApi* | [**apiV1TrackerTrackingLinksIdNetworkBindingDelete**](docs/Api/TrackerTrackingLinksApi.md#apiv1trackertrackinglinksidnetworkbindingdelete) | **DELETE** /api/v1/tracker/tracking-links/{id}/network-binding | Unbind a tracking link from its ad-network campaign.
*TrackerTrackingLinksApi* | [**apiV1TrackerTrackingLinksIdNetworkBindingPut**](docs/Api/TrackerTrackingLinksApi.md#apiv1trackertrackinglinksidnetworkbindingput) | **PUT** /api/v1/tracker/tracking-links/{id}/network-binding | Bind a tracking link to an ad-network campaign.
*TrackerTrackingLinksApi* | [**apiV1TrackerTrackingLinksIdPut**](docs/Api/TrackerTrackingLinksApi.md#apiv1trackertrackinglinksidput) | **PUT** /api/v1/tracker/tracking-links/{id} | Update a tracking link.
*TrackerTrackingLinksApi* | [**apiV1TrackerTrackingLinksIdToggleActivePost**](docs/Api/TrackerTrackingLinksApi.md#apiv1trackertrackinglinksidtoggleactivepost) | **POST** /api/v1/tracker/tracking-links/{id}/toggle-active | Pause or resume the bound network campaign.
*TrackerTrackingLinksApi* | [**apiV1TrackerTrackingLinksIdZoneCostsGet**](docs/Api/TrackerTrackingLinksApi.md#apiv1trackertrackinglinksidzonecostsget) | **GET** /api/v1/tracker/tracking-links/{id}/zone-costs | Per-zone cost and performance for one link.
*TrackerTrackingLinksApi* | [**apiV1TrackerTrackingLinksPost**](docs/Api/TrackerTrackingLinksApi.md#apiv1trackertrackinglinkspost) | **POST** /api/v1/tracker/tracking-links | Create a tracking link.
*TrackingApi* | [**apiV1TrackingConversionTypesGet**](docs/Api/TrackingApi.md#apiv1trackingconversiontypesget) | **GET** /api/v1/tracking/conversion-types | Available conversion types for the caller&#39;s account.
*TrackingApi* | [**apiV1TrackingConversionTypesPut**](docs/Api/TrackingApi.md#apiv1trackingconversiontypesput) | **PUT** /api/v1/tracking/conversion-types | Name conversion-type slots and set the click-id dedup opt-outs.
*TrackingApi* | [**apiV1TrackingPixelsGet**](docs/Api/TrackingApi.md#apiv1trackingpixelsget) | **GET** /api/v1/tracking/pixels | Deprecated: List the caller&#39;s conversion-tracking pixels.
*TrackingApi* | [**apiV1TrackingPixelsIdDelete**](docs/Api/TrackingApi.md#apiv1trackingpixelsiddelete) | **DELETE** /api/v1/tracking/pixels/{id} | Deprecated: Delete a pixel.
*TrackingApi* | [**apiV1TrackingPixelsIdGet**](docs/Api/TrackingApi.md#apiv1trackingpixelsidget) | **GET** /api/v1/tracking/pixels/{id} | Deprecated: Get one pixel.
*TrackingApi* | [**apiV1TrackingPixelsIdPut**](docs/Api/TrackingApi.md#apiv1trackingpixelsidput) | **PUT** /api/v1/tracking/pixels/{id} | Deprecated: Update a pixel&#39;s name or conversion type.
*TrackingApi* | [**apiV1TrackingPixelsPost**](docs/Api/TrackingApi.md#apiv1trackingpixelspost) | **POST** /api/v1/tracking/pixels | Deprecated: Create a new tracking pixel.
*URLPoolsApi* | [**apiV1UrlPoolsGet**](docs/Api/URLPoolsApi.md#apiv1urlpoolsget) | **GET** /api/v1/url-pools | List URL pools.
*URLPoolsApi* | [**apiV1UrlPoolsIdClonePost**](docs/Api/URLPoolsApi.md#apiv1urlpoolsidclonepost) | **POST** /api/v1/url-pools/{id}/clone | Clone a URL pool with all its URLs.
*URLPoolsApi* | [**apiV1UrlPoolsIdDelete**](docs/Api/URLPoolsApi.md#apiv1urlpoolsiddelete) | **DELETE** /api/v1/url-pools/{id} | Soft-delete a URL pool.
*URLPoolsApi* | [**apiV1UrlPoolsIdGet**](docs/Api/URLPoolsApi.md#apiv1urlpoolsidget) | **GET** /api/v1/url-pools/{id} | Show one URL pool.
*URLPoolsApi* | [**apiV1UrlPoolsIdPut**](docs/Api/URLPoolsApi.md#apiv1urlpoolsidput) | **PUT** /api/v1/url-pools/{id} | Update URL pool meta.
*URLPoolsApi* | [**apiV1UrlPoolsIdRecoverPost**](docs/Api/URLPoolsApi.md#apiv1urlpoolsidrecoverpost) | **POST** /api/v1/url-pools/{id}/recover | Restore an archived url pool.
*URLPoolsApi* | [**apiV1UrlPoolsIdUrlsGet**](docs/Api/URLPoolsApi.md#apiv1urlpoolsidurlsget) | **GET** /api/v1/url-pools/{id}/urls | List URLs in a pool.
*URLPoolsApi* | [**apiV1UrlPoolsIdUrlsPost**](docs/Api/URLPoolsApi.md#apiv1urlpoolsidurlspost) | **POST** /api/v1/url-pools/{id}/urls | Add a URL to a pool.
*URLPoolsApi* | [**apiV1UrlPoolsIdUrlsUrlIdClonePost**](docs/Api/URLPoolsApi.md#apiv1urlpoolsidurlsurlidclonepost) | **POST** /api/v1/url-pools/{id}/urls/{urlId}/clone | Clone a single URL within the same pool.
*URLPoolsApi* | [**apiV1UrlPoolsIdUrlsUrlIdDelete**](docs/Api/URLPoolsApi.md#apiv1urlpoolsidurlsurliddelete) | **DELETE** /api/v1/url-pools/{id}/urls/{urlId} | Soft-delete a single URL.
*URLPoolsApi* | [**apiV1UrlPoolsIdUrlsUrlIdGet**](docs/Api/URLPoolsApi.md#apiv1urlpoolsidurlsurlidget) | **GET** /api/v1/url-pools/{id}/urls/{urlId} | Show a single URL.
*URLPoolsApi* | [**apiV1UrlPoolsIdUrlsUrlIdPut**](docs/Api/URLPoolsApi.md#apiv1urlpoolsidurlsurlidput) | **PUT** /api/v1/url-pools/{id}/urls/{urlId} | Update a single URL.
*URLPoolsApi* | [**apiV1UrlPoolsIdUrlsUrlIdTogglePut**](docs/Api/URLPoolsApi.md#apiv1urlpoolsidurlsurlidtoggleput) | **PUT** /api/v1/url-pools/{id}/urls/{urlId}/toggle | Toggle a URL on / off.
*URLPoolsApi* | [**apiV1UrlPoolsIdUrlsUrlIdWeightPut**](docs/Api/URLPoolsApi.md#apiv1urlpoolsidurlsurlidweightput) | **PUT** /api/v1/url-pools/{id}/urls/{urlId}/weight | Set a URL&#39;s rotation weight.
*URLPoolsApi* | [**apiV1UrlPoolsIdUsedInGet**](docs/Api/URLPoolsApi.md#apiv1urlpoolsidusedinget) | **GET** /api/v1/url-pools/{id}/used-in | List campaigns / ads that reference this URL pool.
*URLPoolsApi* | [**apiV1UrlPoolsPost**](docs/Api/URLPoolsApi.md#apiv1urlpoolspost) | **POST** /api/v1/url-pools | Create a URL pool.
*URLPoolsApi* | [**apiV1UrlPoolsReplaceUrlPut**](docs/Api/URLPoolsApi.md#apiv1urlpoolsreplaceurlput) | **PUT** /api/v1/url-pools/replace-url | Search &amp; replace text across your URL-pool URLs.
*WebhooksEventsApi* | [**apiV1EventsGet**](docs/Api/WebhooksEventsApi.md#apiv1eventsget) | **GET** /api/v1/events | Read the account&#39;s event feed.
*WebhooksEventsApi* | [**apiV1WebhooksGet**](docs/Api/WebhooksEventsApi.md#apiv1webhooksget) | **GET** /api/v1/webhooks | List webhooks.
*WebhooksEventsApi* | [**apiV1WebhooksIdDelete**](docs/Api/WebhooksEventsApi.md#apiv1webhooksiddelete) | **DELETE** /api/v1/webhooks/{id} | Remove a webhook.
*WebhooksEventsApi* | [**apiV1WebhooksIdDeliveriesDeliveryRedeliverPost**](docs/Api/WebhooksEventsApi.md#apiv1webhooksiddeliveriesdeliveryredeliverpost) | **POST** /api/v1/webhooks/{id}/deliveries/{delivery}/redeliver | Send a delivery again.
*WebhooksEventsApi* | [**apiV1WebhooksIdDeliveriesGet**](docs/Api/WebhooksEventsApi.md#apiv1webhooksiddeliveriesget) | **GET** /api/v1/webhooks/{id}/deliveries | List a webhook&#39;s deliveries.
*WebhooksEventsApi* | [**apiV1WebhooksIdGet**](docs/Api/WebhooksEventsApi.md#apiv1webhooksidget) | **GET** /api/v1/webhooks/{id} | Get one webhook.
*WebhooksEventsApi* | [**apiV1WebhooksIdPatch**](docs/Api/WebhooksEventsApi.md#apiv1webhooksidpatch) | **PATCH** /api/v1/webhooks/{id} | Edit, pause or resume a webhook.
*WebhooksEventsApi* | [**apiV1WebhooksIdRotateSecretPost**](docs/Api/WebhooksEventsApi.md#apiv1webhooksidrotatesecretpost) | **POST** /api/v1/webhooks/{id}/rotate-secret | Issue a new signing secret.
*WebhooksEventsApi* | [**apiV1WebhooksIdTestPost**](docs/Api/WebhooksEventsApi.md#apiv1webhooksidtestpost) | **POST** /api/v1/webhooks/{id}/test | Send a test ping.
*WebhooksEventsApi* | [**apiV1WebhooksPost**](docs/Api/WebhooksEventsApi.md#apiv1webhookspost) | **POST** /api/v1/webhooks | Create a webhook.
*XMLFeedsApi* | [**apiV1XmlFeedsGet**](docs/Api/XMLFeedsApi.md#apiv1xmlfeedsget) | **GET** /api/v1/xml-feeds | List XML feeds.
*XMLFeedsApi* | [**apiV1XmlFeedsIdClonePost**](docs/Api/XMLFeedsApi.md#apiv1xmlfeedsidclonepost) | **POST** /api/v1/xml-feeds/{id}/clone | Clone an XML feed.
*XMLFeedsApi* | [**apiV1XmlFeedsIdDelete**](docs/Api/XMLFeedsApi.md#apiv1xmlfeedsiddelete) | **DELETE** /api/v1/xml-feeds/{id} | Delete an XML feed.
*XMLFeedsApi* | [**apiV1XmlFeedsIdGet**](docs/Api/XMLFeedsApi.md#apiv1xmlfeedsidget) | **GET** /api/v1/xml-feeds/{id} | Show an XML feed.
*XMLFeedsApi* | [**apiV1XmlFeedsIdPut**](docs/Api/XMLFeedsApi.md#apiv1xmlfeedsidput) | **PUT** /api/v1/xml-feeds/{id} | Update an XML feed.
*XMLFeedsApi* | [**apiV1XmlFeedsIdTogglePut**](docs/Api/XMLFeedsApi.md#apiv1xmlfeedsidtoggleput) | **PUT** /api/v1/xml-feeds/{id}/toggle | Flip an XML feed active / paused.
*XMLFeedsApi* | [**apiV1XmlFeedsPost**](docs/Api/XMLFeedsApi.md#apiv1xmlfeedspost) | **POST** /api/v1/xml-feeds | Create an XML feed.

## Models

- [Adspace](docs/Model/Adspace.md)
- [AdspaceCreate](docs/Model/AdspaceCreate.md)
- [AdspaceCreateBannerSettings](docs/Model/AdspaceCreateBannerSettings.md)
- [AdspaceCreateBotSettings](docs/Model/AdspaceCreateBotSettings.md)
- [AdspaceCreateEmailSettings](docs/Model/AdspaceCreateEmailSettings.md)
- [AdspaceCreateScreenSettings](docs/Model/AdspaceCreateScreenSettings.md)
- [AdspaceCreateSearchSettings](docs/Model/AdspaceCreateSearchSettings.md)
- [AdspaceCreateSearchSettingsBlockedTerms](docs/Model/AdspaceCreateSearchSettingsBlockedTerms.md)
- [AdspaceFieldMeta](docs/Model/AdspaceFieldMeta.md)
- [AdspaceNetworkBinding](docs/Model/AdspaceNetworkBinding.md)
- [AdspaceSchema](docs/Model/AdspaceSchema.md)
- [AdspaceSettingsBlock](docs/Model/AdspaceSettingsBlock.md)
- [AdspaceSite](docs/Model/AdspaceSite.md)
- [AdspaceTypeItem](docs/Model/AdspaceTypeItem.md)
- [AdspaceTypeOption](docs/Model/AdspaceTypeOption.md)
- [ApiKeyDetails](docs/Model/ApiKeyDetails.md)
- [ApiKeyDetailsCreated](docs/Model/ApiKeyDetailsCreated.md)
- [ApiPublicDemoGet200Response](docs/Model/ApiPublicDemoGet200Response.md)
- [ApiPublicDemoGet200ResponseDataInner](docs/Model/ApiPublicDemoGet200ResponseDataInner.md)
- [ApiPublicIntegrationsHooksTokenPost200Response](docs/Model/ApiPublicIntegrationsHooksTokenPost200Response.md)
- [ApiPublicOauthIntrospectPost200Response](docs/Model/ApiPublicOauthIntrospectPost200Response.md)
- [ApiPublicOauthTokenPost200Response](docs/Model/ApiPublicOauthTokenPost200Response.md)
- [ApiPublicOfferwallIdGet200Response](docs/Model/ApiPublicOfferwallIdGet200Response.md)
- [ApiPublicOfferwallIdGet200ResponseOffersInner](docs/Model/ApiPublicOfferwallIdGet200ResponseOffersInner.md)
- [ApiV1AccountBillingAveragesGet200Response](docs/Model/ApiV1AccountBillingAveragesGet200Response.md)
- [ApiV1AccountBillingAveragesGet200ResponseModulesInner](docs/Model/ApiV1AccountBillingAveragesGet200ResponseModulesInner.md)
- [ApiV1AccountBillingAveragesGet200ResponseOverchargesValue](docs/Model/ApiV1AccountBillingAveragesGet200ResponseOverchargesValue.md)
- [ApiV1AccountBillingInvoicesGet200Response](docs/Model/ApiV1AccountBillingInvoicesGet200Response.md)
- [ApiV1AccountBillingInvoicesGet200ResponseResultsInner](docs/Model/ApiV1AccountBillingInvoicesGet200ResponseResultsInner.md)
- [ApiV1AccountGet200Response](docs/Model/ApiV1AccountGet200Response.md)
- [ApiV1AccountGet200ResponseApiKey](docs/Model/ApiV1AccountGet200ResponseApiKey.md)
- [ApiV1AccountNotificationDestinationsPostRequest](docs/Model/ApiV1AccountNotificationDestinationsPostRequest.md)
- [ApiV1AccountNotificationPreferencesPutRequest](docs/Model/ApiV1AccountNotificationPreferencesPutRequest.md)
- [ApiV1AccountNotificationsReadPostRequest](docs/Model/ApiV1AccountNotificationsReadPostRequest.md)
- [ApiV1AccountPreferencesKeyPutRequest](docs/Model/ApiV1AccountPreferencesKeyPutRequest.md)
- [ApiV1AccountUiLayoutsScopePutRequest](docs/Model/ApiV1AccountUiLayoutsScopePutRequest.md)
- [ApiV1AccountUiLayoutsScopePutRequestData](docs/Model/ApiV1AccountUiLayoutsScopePutRequestData.md)
- [ApiV1AccountUiLayoutsScopePutRequestDataPatchesInner](docs/Model/ApiV1AccountUiLayoutsScopePutRequestDataPatchesInner.md)
- [ApiV1AdScannerIdIgnoreDomainPutRequest](docs/Model/ApiV1AdScannerIdIgnoreDomainPutRequest.md)
- [ApiV1AdScannerIdManualItemsItemIdStatePutRequest](docs/Model/ApiV1AdScannerIdManualItemsItemIdStatePutRequest.md)
- [ApiV1AdScannerIdManualItemsPostRequest](docs/Model/ApiV1AdScannerIdManualItemsPostRequest.md)
- [ApiV1AdScannerIdPutRequest](docs/Model/ApiV1AdScannerIdPutRequest.md)
- [ApiV1AdScannerIdToggleActivePutRequest](docs/Model/ApiV1AdScannerIdToggleActivePutRequest.md)
- [ApiV1AdScannerPostRequest](docs/Model/ApiV1AdScannerPostRequest.md)
- [ApiV1AdScannerPostRequestTags](docs/Model/ApiV1AdScannerPostRequestTags.md)
- [ApiV1AdShopCartPostRequest](docs/Model/ApiV1AdShopCartPostRequest.md)
- [ApiV1AdShopConfirmPostRequest](docs/Model/ApiV1AdShopConfirmPostRequest.md)
- [ApiV1AdShopGroupSummaryPostRequest](docs/Model/ApiV1AdShopGroupSummaryPostRequest.md)
- [ApiV1AdShopGroupSummaryPostRequestFilters](docs/Model/ApiV1AdShopGroupSummaryPostRequestFilters.md)
- [ApiV1AdspacesAddMultiplePostRequest](docs/Model/ApiV1AdspacesAddMultiplePostRequest.md)
- [ApiV1AdspacesExportCodesGet200ResponseInner](docs/Model/ApiV1AdspacesExportCodesGet200ResponseInner.md)
- [ApiV1AdspacesExportCodesGet200ResponseInnerCodesInner](docs/Model/ApiV1AdspacesExportCodesGet200ResponseInnerCodesInner.md)
- [ApiV1AdspacesGet200Response](docs/Model/ApiV1AdspacesGet200Response.md)
- [ApiV1AdspacesGet200ResponseDataInner](docs/Model/ApiV1AdspacesGet200ResponseDataInner.md)
- [ApiV1AdspacesIdClonePostRequest](docs/Model/ApiV1AdspacesIdClonePostRequest.md)
- [ApiV1AdspacesIdCodeGet200Response](docs/Model/ApiV1AdspacesIdCodeGet200Response.md)
- [ApiV1AdspacesIdCodeGet200ResponseAmp](docs/Model/ApiV1AdspacesIdCodeGet200ResponseAmp.md)
- [ApiV1AdspacesIdCodeGet200ResponseCtv](docs/Model/ApiV1AdspacesIdCodeGet200ResponseCtv.md)
- [ApiV1AdspacesIdCodeGet200ResponseLayoutsInner](docs/Model/ApiV1AdspacesIdCodeGet200ResponseLayoutsInner.md)
- [ApiV1AdspacesIdCodeGet200ResponseScreen](docs/Model/ApiV1AdspacesIdCodeGet200ResponseScreen.md)
- [ApiV1AdspacesIdCodeGet200ResponseSite](docs/Model/ApiV1AdspacesIdCodeGet200ResponseSite.md)
- [ApiV1AdspacesIdMediaBuyingActionsActionIdDecidePostRequest](docs/Model/ApiV1AdspacesIdMediaBuyingActionsActionIdDecidePostRequest.md)
- [ApiV1AdspacesIdMediaBuyingBlockPostRequest](docs/Model/ApiV1AdspacesIdMediaBuyingBlockPostRequest.md)
- [ApiV1AdspacesIdMediaBuyingIpsPostRequest](docs/Model/ApiV1AdspacesIdMediaBuyingIpsPostRequest.md)
- [ApiV1AdspacesIdMediaBuyingLaunchPostRequest](docs/Model/ApiV1AdspacesIdMediaBuyingLaunchPostRequest.md)
- [ApiV1AdspacesIdMediaBuyingReportPostRequest](docs/Model/ApiV1AdspacesIdMediaBuyingReportPostRequest.md)
- [ApiV1AdspacesIdMediaBuyingSourceBidPutRequest](docs/Model/ApiV1AdspacesIdMediaBuyingSourceBidPutRequest.md)
- [ApiV1AdspacesIdMediaBuyingTargetingPutRequest](docs/Model/ApiV1AdspacesIdMediaBuyingTargetingPutRequest.md)
- [ApiV1AdspacesIdMediaBuyingTargetingPutRequestZonesInner](docs/Model/ApiV1AdspacesIdMediaBuyingTargetingPutRequestZonesInner.md)
- [ApiV1AdspacesIdNetworkBindingPutRequest](docs/Model/ApiV1AdspacesIdNetworkBindingPutRequest.md)
- [ApiV1AdspacesIdOwnAdsMassActionPostRequest](docs/Model/ApiV1AdspacesIdOwnAdsMassActionPostRequest.md)
- [ApiV1AdspacesIdPatch200Response](docs/Model/ApiV1AdspacesIdPatch200Response.md)
- [ApiV1AdspacesIdPricesTypePostRequest](docs/Model/ApiV1AdspacesIdPricesTypePostRequest.md)
- [ApiV1AdspacesIdPricesTypePostRequestCpmPriceInner](docs/Model/ApiV1AdspacesIdPricesTypePostRequestCpmPriceInner.md)
- [ApiV1AdspacesIdWlCampaignsPutRequest](docs/Model/ApiV1AdspacesIdWlCampaignsPutRequest.md)
- [ApiV1AdspacesIdWlCampaignsPutRequestCampaignsInner](docs/Model/ApiV1AdspacesIdWlCampaignsPutRequestCampaignsInner.md)
- [ApiV1AdspacesMassDeletePostRequest](docs/Model/ApiV1AdspacesMassDeletePostRequest.md)
- [ApiV1AdspacesMassRecoverPostRequest](docs/Model/ApiV1AdspacesMassRecoverPostRequest.md)
- [ApiV1AdspacesTemplatesGet200ResponseInner](docs/Model/ApiV1AdspacesTemplatesGet200ResponseInner.md)
- [ApiV1AdspacesTemplatesIdPutRequest](docs/Model/ApiV1AdspacesTemplatesIdPutRequest.md)
- [ApiV1AdspacesTemplatesPostRequest](docs/Model/ApiV1AdspacesTemplatesPostRequest.md)
- [ApiV1BidRulesetsGet200Response](docs/Model/ApiV1BidRulesetsGet200Response.md)
- [ApiV1BidRulesetsIdClonePostRequest](docs/Model/ApiV1BidRulesetsIdClonePostRequest.md)
- [ApiV1BidRulesetsIdDelete200Response](docs/Model/ApiV1BidRulesetsIdDelete200Response.md)
- [ApiV1BidRulesetsIdDelete422Response](docs/Model/ApiV1BidRulesetsIdDelete422Response.md)
- [ApiV1BidRulesetsIdPatchRequest](docs/Model/ApiV1BidRulesetsIdPatchRequest.md)
- [ApiV1BidRulesetsPostRequest](docs/Model/ApiV1BidRulesetsPostRequest.md)
- [ApiV1CampaignsCpmCampaignIdSpotsSpaceIdPostRequest](docs/Model/ApiV1CampaignsCpmCampaignIdSpotsSpaceIdPostRequest.md)
- [ApiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePutRequest](docs/Model/ApiV1CampaignsCpmCampaignIdSpotsSpaceIdTypePutRequest.md)
- [ApiV1CampaignsGet200Response](docs/Model/ApiV1CampaignsGet200Response.md)
- [ApiV1CampaignsGet200ResponseDataInner](docs/Model/ApiV1CampaignsGet200ResponseDataInner.md)
- [ApiV1CampaignsMassDeletePostRequest](docs/Model/ApiV1CampaignsMassDeletePostRequest.md)
- [ApiV1CampaignsMassTogglePutRequest](docs/Model/ApiV1CampaignsMassTogglePutRequest.md)
- [ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet200Response](docs/Model/ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet200Response.md)
- [ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet200ResponseZoneBidsInner](docs/Model/ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsGet200ResponseZoneBidsInner.md)
- [ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut200Response](docs/Model/ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPut200Response.md)
- [ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPutRequest](docs/Model/ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPutRequest.md)
- [ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPutRequestZoneBidsInner](docs/Model/ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsPutRequestZoneBidsInner.md)
- [ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete200Response](docs/Model/ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZoneIdDelete200Response.md)
- [ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPutRequest](docs/Model/ApiV1CampaignsRonCampaignIdAdsAdIdZoneBidsZonesOnlyPutRequest.md)
- [ApiV1CampaignsRonIdSiteListSiteIdPutRequest](docs/Model/ApiV1CampaignsRonIdSiteListSiteIdPutRequest.md)
- [ApiV1CampaignsRonIdZonesGet200Response](docs/Model/ApiV1CampaignsRonIdZonesGet200Response.md)
- [ApiV1CampaignsRonIdZonesGet200ResponseDataInner](docs/Model/ApiV1CampaignsRonIdZonesGet200ResponseDataInner.md)
- [ApiV1CampaignsSaveCategoriesPutRequest](docs/Model/ApiV1CampaignsSaveCategoriesPutRequest.md)
- [ApiV1CampaignsSaveTagsPutRequest](docs/Model/ApiV1CampaignsSaveTagsPutRequest.md)
- [ApiV1CampaignsTypeCampaignIdAdsAdIdActivePatch200Response](docs/Model/ApiV1CampaignsTypeCampaignIdAdsAdIdActivePatch200Response.md)
- [ApiV1CampaignsTypeCampaignIdAdsAdIdBidPatch200Response](docs/Model/ApiV1CampaignsTypeCampaignIdAdsAdIdBidPatch200Response.md)
- [ApiV1CampaignsTypeCampaignIdAdsAdIdBidPatchRequest](docs/Model/ApiV1CampaignsTypeCampaignIdAdsAdIdBidPatchRequest.md)
- [ApiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPutRequest](docs/Model/ApiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPutRequest.md)
- [ApiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPutRequestGeoBidsInner](docs/Model/ApiV1CampaignsTypeCampaignIdAdsAdIdBidsPerGeoPutRequestGeoBidsInner.md)
- [ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response](docs/Model/ApiV1CampaignsTypeCampaignIdAdsAdIdClonePost200Response.md)
- [ApiV1CampaignsTypeCampaignIdAdsAdIdCountriesPutRequest](docs/Model/ApiV1CampaignsTypeCampaignIdAdsAdIdCountriesPutRequest.md)
- [ApiV1CampaignsTypeCampaignIdAdsAdIdPutRequest](docs/Model/ApiV1CampaignsTypeCampaignIdAdsAdIdPutRequest.md)
- [ApiV1CampaignsTypeCampaignIdAdsCsvImportPost200Response](docs/Model/ApiV1CampaignsTypeCampaignIdAdsCsvImportPost200Response.md)
- [ApiV1CampaignsTypeCampaignIdAdsFromPoolPostRequest](docs/Model/ApiV1CampaignsTypeCampaignIdAdsFromPoolPostRequest.md)
- [ApiV1CampaignsTypeCampaignIdAdsGet200Response](docs/Model/ApiV1CampaignsTypeCampaignIdAdsGet200Response.md)
- [ApiV1CampaignsTypeCampaignIdAdsGet200ResponseMeta](docs/Model/ApiV1CampaignsTypeCampaignIdAdsGet200ResponseMeta.md)
- [ApiV1CampaignsTypeCampaignIdAdsMassClonePostRequest](docs/Model/ApiV1CampaignsTypeCampaignIdAdsMassClonePostRequest.md)
- [ApiV1CampaignsTypeCampaignIdAdsMassDeletePostRequest](docs/Model/ApiV1CampaignsTypeCampaignIdAdsMassDeletePostRequest.md)
- [ApiV1CampaignsTypeCampaignIdAdsMassTogglePutRequest](docs/Model/ApiV1CampaignsTypeCampaignIdAdsMassTogglePutRequest.md)
- [ApiV1CampaignsTypeCampaignIdAdsMassUndeletePostRequest](docs/Model/ApiV1CampaignsTypeCampaignIdAdsMassUndeletePostRequest.md)
- [ApiV1CampaignsTypeCampaignIdAdsPostRequest](docs/Model/ApiV1CampaignsTypeCampaignIdAdsPostRequest.md)
- [ApiV1CampaignsTypeCampaignIdAdsPostRequestVideoOverlaysInner](docs/Model/ApiV1CampaignsTypeCampaignIdAdsPostRequestVideoOverlaysInner.md)
- [ApiV1CampaignsTypeCampaignIdAdsUpdatePoolPutRequest](docs/Model/ApiV1CampaignsTypeCampaignIdAdsUpdatePoolPutRequest.md)
- [ApiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPutRequest](docs/Model/ApiV1CampaignsTypeCampaignIdAdsUpdateUrlPoolPutRequest.md)
- [ApiV1CampaignsTypeCampaignIdAdsUpdateUrlPutRequest](docs/Model/ApiV1CampaignsTypeCampaignIdAdsUpdateUrlPutRequest.md)
- [ApiV1CampaignsTypeCampaignIdAdsUpdateWeightPutRequest](docs/Model/ApiV1CampaignsTypeCampaignIdAdsUpdateWeightPutRequest.md)
- [ApiV1CampaignsTypeIdActivePatch200Response](docs/Model/ApiV1CampaignsTypeIdActivePatch200Response.md)
- [ApiV1CampaignsTypeIdActivePatchRequest](docs/Model/ApiV1CampaignsTypeIdActivePatchRequest.md)
- [ApiV1CampaignsTypeIdBidsPatch200Response](docs/Model/ApiV1CampaignsTypeIdBidsPatch200Response.md)
- [ApiV1CampaignsTypeIdBidsPatchRequest](docs/Model/ApiV1CampaignsTypeIdBidsPatchRequest.md)
- [ApiV1CampaignsTypeIdBudgetPatch200Response](docs/Model/ApiV1CampaignsTypeIdBudgetPatch200Response.md)
- [ApiV1CampaignsTypeIdBudgetPatchRequest](docs/Model/ApiV1CampaignsTypeIdBudgetPatchRequest.md)
- [ApiV1CampaignsTypeIdClonePost201Response](docs/Model/ApiV1CampaignsTypeIdClonePost201Response.md)
- [ApiV1CampaignsTypeIdClonePost201ResponseCopied](docs/Model/ApiV1CampaignsTypeIdClonePost201ResponseCopied.md)
- [ApiV1CampaignsTypeIdClonePostRequest](docs/Model/ApiV1CampaignsTypeIdClonePostRequest.md)
- [ApiV1CampaignsTypeIdDelete200Response](docs/Model/ApiV1CampaignsTypeIdDelete200Response.md)
- [ApiV1CampaignsTypeIdFiltersGet200Response](docs/Model/ApiV1CampaignsTypeIdFiltersGet200Response.md)
- [ApiV1CampaignsTypeIdFiltersGet200ResponseFiltersInner](docs/Model/ApiV1CampaignsTypeIdFiltersGet200ResponseFiltersInner.md)
- [ApiV1CampaignsTypeIdFiltersPost200Response](docs/Model/ApiV1CampaignsTypeIdFiltersPost200Response.md)
- [ApiV1CampaignsTypeIdFiltersPostRequest](docs/Model/ApiV1CampaignsTypeIdFiltersPostRequest.md)
- [ApiV1CampaignsTypeIdMinBidGet200Response](docs/Model/ApiV1CampaignsTypeIdMinBidGet200Response.md)
- [ApiV1CampaignsTypeIdPatchRequest](docs/Model/ApiV1CampaignsTypeIdPatchRequest.md)
- [ApiV1CampaignsTypeIdPatchRequestAllOfBannerSizeIds](docs/Model/ApiV1CampaignsTypeIdPatchRequestAllOfBannerSizeIds.md)
- [ApiV1CampaignsTypeIdZonesPostRequest](docs/Model/ApiV1CampaignsTypeIdZonesPostRequest.md)
- [ApiV1CampaignsTypeIdZonesZoneIdWeightPutRequest](docs/Model/ApiV1CampaignsTypeIdZonesZoneIdWeightPutRequest.md)
- [ApiV1CampaignsTypePostRequest](docs/Model/ApiV1CampaignsTypePostRequest.md)
- [ApiV1CampaignsTypePostRequestAllOfBannerSizeIds](docs/Model/ApiV1CampaignsTypePostRequestAllOfBannerSizeIds.md)
- [ApiV1CampaignsTypePostRequestAllOfPriority](docs/Model/ApiV1CampaignsTypePostRequestAllOfPriority.md)
- [ApiV1CampaignsTypeSchemaAdsGet200Response](docs/Model/ApiV1CampaignsTypeSchemaAdsGet200Response.md)
- [ApiV1CampaignsTypeSchemaGet200Response](docs/Model/ApiV1CampaignsTypeSchemaGet200Response.md)
- [ApiV1CampaignsTypeSchemaGet200ResponseFieldsInner](docs/Model/ApiV1CampaignsTypeSchemaGet200ResponseFieldsInner.md)
- [ApiV1CouponsSetsIdGeneratePostRequest](docs/Model/ApiV1CouponsSetsIdGeneratePostRequest.md)
- [ApiV1CouponsSetsIdPutRequest](docs/Model/ApiV1CouponsSetsIdPutRequest.md)
- [ApiV1CouponsSetsPostRequest](docs/Model/ApiV1CouponsSetsPostRequest.md)
- [ApiV1CreativeFoldersIdPutRequest](docs/Model/ApiV1CreativeFoldersIdPutRequest.md)
- [ApiV1CreativeFoldersPostRequest](docs/Model/ApiV1CreativeFoldersPostRequest.md)
- [ApiV1CreativePoolsGet200Response](docs/Model/ApiV1CreativePoolsGet200Response.md)
- [ApiV1CreativePoolsGet200ResponseDataInner](docs/Model/ApiV1CreativePoolsGet200ResponseDataInner.md)
- [ApiV1CreativePoolsGetPerPageParameter](docs/Model/ApiV1CreativePoolsGetPerPageParameter.md)
- [ApiV1CreativePoolsIdAddCreativesPostRequest](docs/Model/ApiV1CreativePoolsIdAddCreativesPostRequest.md)
- [ApiV1CreativePoolsIdAddUrlsPostRequest](docs/Model/ApiV1CreativePoolsIdAddUrlsPostRequest.md)
- [ApiV1CreativePoolsIdClonePost201Response](docs/Model/ApiV1CreativePoolsIdClonePost201Response.md)
- [ApiV1CreativePoolsIdDelete200Response](docs/Model/ApiV1CreativePoolsIdDelete200Response.md)
- [ApiV1CreativePoolsIdDelete409Response](docs/Model/ApiV1CreativePoolsIdDelete409Response.md)
- [ApiV1CreativePoolsIdGet200Response](docs/Model/ApiV1CreativePoolsIdGet200Response.md)
- [ApiV1CreativePoolsIdItemsItemIdPut409Response](docs/Model/ApiV1CreativePoolsIdItemsItemIdPut409Response.md)
- [ApiV1CreativePoolsIdItemsItemIdPutRequest](docs/Model/ApiV1CreativePoolsIdItemsItemIdPutRequest.md)
- [ApiV1CreativePoolsIdItemsItemIdPutRequestEndAt](docs/Model/ApiV1CreativePoolsIdItemsItemIdPutRequestEndAt.md)
- [ApiV1CreativePoolsIdItemsItemIdPutRequestStartAt](docs/Model/ApiV1CreativePoolsIdItemsItemIdPutRequestStartAt.md)
- [ApiV1CreativePoolsIdItemsMassActionPutRequest](docs/Model/ApiV1CreativePoolsIdItemsMassActionPutRequest.md)
- [ApiV1CreativePoolsIdItemsPostRequest](docs/Model/ApiV1CreativePoolsIdItemsPostRequest.md)
- [ApiV1CreativePoolsIdItemsRecoverPostRequest](docs/Model/ApiV1CreativePoolsIdItemsRecoverPostRequest.md)
- [ApiV1CreativePoolsIdPutRequest](docs/Model/ApiV1CreativePoolsIdPutRequest.md)
- [ApiV1CreativePoolsIdUsedInGet200Response](docs/Model/ApiV1CreativePoolsIdUsedInGet200Response.md)
- [ApiV1CreativePoolsPostRequest](docs/Model/ApiV1CreativePoolsPostRequest.md)
- [ApiV1CreativesBulkDeletePostRequest](docs/Model/ApiV1CreativesBulkDeletePostRequest.md)
- [ApiV1CreativesBulkMovePostRequest](docs/Model/ApiV1CreativesBulkMovePostRequest.md)
- [ApiV1CreativesBulkUndeletePostRequest](docs/Model/ApiV1CreativesBulkUndeletePostRequest.md)
- [ApiV1CreativesGet200Response](docs/Model/ApiV1CreativesGet200Response.md)
- [ApiV1CreativesGetPerPageParameter](docs/Model/ApiV1CreativesGetPerPageParameter.md)
- [ApiV1CreativesIdNamePutRequest](docs/Model/ApiV1CreativesIdNamePutRequest.md)
- [ApiV1CreativesIdPutRequest](docs/Model/ApiV1CreativesIdPutRequest.md)
- [ApiV1CreativesIdPutRequestTags](docs/Model/ApiV1CreativesIdPutRequestTags.md)
- [ApiV1CreativesIdTagsPutRequest](docs/Model/ApiV1CreativesIdTagsPutRequest.md)
- [ApiV1DomainsIdNotesPutRequest](docs/Model/ApiV1DomainsIdNotesPutRequest.md)
- [ApiV1DomainsPostRequest](docs/Model/ApiV1DomainsPostRequest.md)
- [ApiV1EventLogExportsPostRequest](docs/Model/ApiV1EventLogExportsPostRequest.md)
- [ApiV1EventsGet200Response](docs/Model/ApiV1EventsGet200Response.md)
- [ApiV1FiltersGet200Response](docs/Model/ApiV1FiltersGet200Response.md)
- [ApiV1FiltersMassActionPostRequest](docs/Model/ApiV1FiltersMassActionPostRequest.md)
- [ApiV1FiltersMassActionPostRequestFiltersInner](docs/Model/ApiV1FiltersMassActionPostRequestFiltersInner.md)
- [ApiV1FiltersPostRequest](docs/Model/ApiV1FiltersPostRequest.md)
- [ApiV1FiltersTypeIdActivePatch200Response](docs/Model/ApiV1FiltersTypeIdActivePatch200Response.md)
- [ApiV1FiltersTypeIdItemsItemDelete200Response](docs/Model/ApiV1FiltersTypeIdItemsItemDelete200Response.md)
- [ApiV1FiltersTypeIdItemsPost200Response](docs/Model/ApiV1FiltersTypeIdItemsPost200Response.md)
- [ApiV1FiltersTypeIdItemsPostRequest](docs/Model/ApiV1FiltersTypeIdItemsPostRequest.md)
- [ApiV1FiltersTypeIdPutRequest](docs/Model/ApiV1FiltersTypeIdPutRequest.md)
- [ApiV1FundsBalanceSettingsPutRequest](docs/Model/ApiV1FundsBalanceSettingsPutRequest.md)
- [ApiV1FundsDepositTypeFidGet200Response](docs/Model/ApiV1FundsDepositTypeFidGet200Response.md)
- [ApiV1FundsDepositTypePost200Response](docs/Model/ApiV1FundsDepositTypePost200Response.md)
- [ApiV1FundsDepositTypePostRequest](docs/Model/ApiV1FundsDepositTypePostRequest.md)
- [ApiV1FundsDepositsIdProofPost200Response](docs/Model/ApiV1FundsDepositsIdProofPost200Response.md)
- [ApiV1FundsDepositsIdTxnHashPost200Response](docs/Model/ApiV1FundsDepositsIdTxnHashPost200Response.md)
- [ApiV1FundsDepositsIdTxnHashPostRequest](docs/Model/ApiV1FundsDepositsIdTxnHashPostRequest.md)
- [ApiV1FundsGet200Response](docs/Model/ApiV1FundsGet200Response.md)
- [ApiV1FundsPayTypesGet200Response](docs/Model/ApiV1FundsPayTypesGet200Response.md)
- [ApiV1FundsPayTypesGet200ResponsePayTypesInner](docs/Model/ApiV1FundsPayTypesGet200ResponsePayTypesInner.md)
- [ApiV1FundsTransactionsGet200Response](docs/Model/ApiV1FundsTransactionsGet200Response.md)
- [ApiV1FundsTransactionsGet200ResponseDataInner](docs/Model/ApiV1FundsTransactionsGet200ResponseDataInner.md)
- [ApiV1FundsTransferPostRequest](docs/Model/ApiV1FundsTransferPostRequest.md)
- [ApiV1LookupsAdspaceTypesGet200Response](docs/Model/ApiV1LookupsAdspaceTypesGet200Response.md)
- [ApiV1LookupsBannerSizesGet200Response](docs/Model/ApiV1LookupsBannerSizesGet200Response.md)
- [ApiV1LookupsBannerSizesGet200ResponseItemsInner](docs/Model/ApiV1LookupsBannerSizesGet200ResponseItemsInner.md)
- [ApiV1LookupsBrowsersGet200Response](docs/Model/ApiV1LookupsBrowsersGet200Response.md)
- [ApiV1LookupsCarriersGet200Response](docs/Model/ApiV1LookupsCarriersGet200Response.md)
- [ApiV1LookupsCarriersGet200ResponseDataInner](docs/Model/ApiV1LookupsCarriersGet200ResponseDataInner.md)
- [ApiV1LookupsCategoriesGet200Response](docs/Model/ApiV1LookupsCategoriesGet200Response.md)
- [ApiV1LookupsCategoriesGet200ResponseItemsInner](docs/Model/ApiV1LookupsCategoriesGet200ResponseItemsInner.md)
- [ApiV1LookupsDeviceTypesGet200Response](docs/Model/ApiV1LookupsDeviceTypesGet200Response.md)
- [ApiV1LookupsRegionsGet200Response](docs/Model/ApiV1LookupsRegionsGet200Response.md)
- [ApiV1LookupsRegionsGet200ResponseRegionsInner](docs/Model/ApiV1LookupsRegionsGet200ResponseRegionsInner.md)
- [ApiV1LookupsWebsiteLanguagesGet200Response](docs/Model/ApiV1LookupsWebsiteLanguagesGet200Response.md)
- [ApiV1LookupsWebsiteLanguagesGet200ResponseItemsInner](docs/Model/ApiV1LookupsWebsiteLanguagesGet200ResponseItemsInner.md)
- [ApiV1MarketplaceFlatRequestsRenewPostRequest](docs/Model/ApiV1MarketplaceFlatRequestsRenewPostRequest.md)
- [ApiV1McpPost200Response](docs/Model/ApiV1McpPost200Response.md)
- [ApiV1McpPost200ResponseError](docs/Model/ApiV1McpPost200ResponseError.md)
- [ApiV1McpPost200ResponseId](docs/Model/ApiV1McpPost200ResponseId.md)
- [ApiV1McpPostRequest](docs/Model/ApiV1McpPostRequest.md)
- [ApiV1McpPostRequestId](docs/Model/ApiV1McpPostRequestId.md)
- [ApiV1OffersGet200Response](docs/Model/ApiV1OffersGet200Response.md)
- [ApiV1OffersGet200ResponseDataInner](docs/Model/ApiV1OffersGet200ResponseDataInner.md)
- [ApiV1ProductFeedsPostRequest](docs/Model/ApiV1ProductFeedsPostRequest.md)
- [ApiV1ProductFeedsPreviewPostRequest](docs/Model/ApiV1ProductFeedsPreviewPostRequest.md)
- [ApiV1ProductFeedsPreviewPostRequestProduct](docs/Model/ApiV1ProductFeedsPreviewPostRequestProduct.md)
- [ApiV1PublisherFiltersGet200Response](docs/Model/ApiV1PublisherFiltersGet200Response.md)
- [ApiV1PublisherFiltersGet200ResponseCategoriesInner](docs/Model/ApiV1PublisherFiltersGet200ResponseCategoriesInner.md)
- [ApiV1PublisherFiltersGet200ResponseUrlsInner](docs/Model/ApiV1PublisherFiltersGet200ResponseUrlsInner.md)
- [ApiV1PublisherFiltersUrlsDelete200Response](docs/Model/ApiV1PublisherFiltersUrlsDelete200Response.md)
- [ApiV1PublisherFiltersUrlsDeleteRequest](docs/Model/ApiV1PublisherFiltersUrlsDeleteRequest.md)
- [ApiV1PublisherFiltersUrlsPost201Response](docs/Model/ApiV1PublisherFiltersUrlsPost201Response.md)
- [ApiV1PublisherFiltersUrlsPostRequest](docs/Model/ApiV1PublisherFiltersUrlsPostRequest.md)
- [ApiV1PublisherFinanceEarningsGet200Response](docs/Model/ApiV1PublisherFinanceEarningsGet200Response.md)
- [ApiV1PublisherFinanceEarningsGet200ResponseEarningsInner](docs/Model/ApiV1PublisherFinanceEarningsGet200ResponseEarningsInner.md)
- [ApiV1PublisherFinanceEarningsGet200ResponsePeriod](docs/Model/ApiV1PublisherFinanceEarningsGet200ResponsePeriod.md)
- [ApiV1PublisherFinanceEarningsGet200ResponseSummary](docs/Model/ApiV1PublisherFinanceEarningsGet200ResponseSummary.md)
- [ApiV1PublisherFinanceGet200Response](docs/Model/ApiV1PublisherFinanceGet200Response.md)
- [ApiV1PublisherFinanceGet200ResponseBalance](docs/Model/ApiV1PublisherFinanceGet200ResponseBalance.md)
- [ApiV1PublisherFinanceMonthEarningsGet200ResponseInner](docs/Model/ApiV1PublisherFinanceMonthEarningsGet200ResponseInner.md)
- [ApiV1PublisherFinancePayoutRequestPost201Response](docs/Model/ApiV1PublisherFinancePayoutRequestPost201Response.md)
- [ApiV1PublisherFinancePayoutRequestPost201ResponsePayout](docs/Model/ApiV1PublisherFinancePayoutRequestPost201ResponsePayout.md)
- [ApiV1PublisherFinancePayoutRequestPostRequest](docs/Model/ApiV1PublisherFinancePayoutRequestPostRequest.md)
- [ApiV1PublisherFinancePayoutTypesGet200ResponseInner](docs/Model/ApiV1PublisherFinancePayoutTypesGet200ResponseInner.md)
- [ApiV1PublisherFinancePayoutsGet200Response](docs/Model/ApiV1PublisherFinancePayoutsGet200Response.md)
- [ApiV1PublisherFinancePayoutsGet200ResponseDataInner](docs/Model/ApiV1PublisherFinancePayoutsGet200ResponseDataInner.md)
- [ApiV1PublisherFinancePayoutsGet200ResponseMeta](docs/Model/ApiV1PublisherFinancePayoutsGet200ResponseMeta.md)
- [ApiV1PublisherFinanceSettingsGet200Response](docs/Model/ApiV1PublisherFinanceSettingsGet200Response.md)
- [ApiV1PublisherFinanceSettingsGet200ResponseData](docs/Model/ApiV1PublisherFinanceSettingsGet200ResponseData.md)
- [ApiV1PublisherFinanceSettingsPutRequest](docs/Model/ApiV1PublisherFinanceSettingsPutRequest.md)
- [ApiV1PublisherFinanceTransactionsGet200Response](docs/Model/ApiV1PublisherFinanceTransactionsGet200Response.md)
- [ApiV1PublisherFinanceTransactionsGet200ResponseDataInner](docs/Model/ApiV1PublisherFinanceTransactionsGet200ResponseDataInner.md)
- [ApiV1PublisherFinanceTransactionsIdGet200Response](docs/Model/ApiV1PublisherFinanceTransactionsIdGet200Response.md)
- [ApiV1PublisherFinanceTransferToAdvertiserPost200Response](docs/Model/ApiV1PublisherFinanceTransferToAdvertiserPost200Response.md)
- [ApiV1PublisherFinanceTransferToAdvertiserPostRequest](docs/Model/ApiV1PublisherFinanceTransferToAdvertiserPostRequest.md)
- [ApiV1PublisherOffersPostbackLogGet200Response](docs/Model/ApiV1PublisherOffersPostbackLogGet200Response.md)
- [ApiV1PublisherOffersPostbackLogGet200ResponseDataInner](docs/Model/ApiV1PublisherOffersPostbackLogGet200ResponseDataInner.md)
- [ApiV1PublisherPushCampaignsGet200Response](docs/Model/ApiV1PublisherPushCampaignsGet200Response.md)
- [ApiV1PublisherPushCampaignsIdDelete200Response](docs/Model/ApiV1PublisherPushCampaignsIdDelete200Response.md)
- [ApiV1PublisherPushCampaignsIdDelete200ResponseData](docs/Model/ApiV1PublisherPushCampaignsIdDelete200ResponseData.md)
- [ApiV1PublisherPushCampaignsIdSendPost200Response](docs/Model/ApiV1PublisherPushCampaignsIdSendPost200Response.md)
- [ApiV1PublisherPushCampaignsIdSendPost200ResponseData](docs/Model/ApiV1PublisherPushCampaignsIdSendPost200ResponseData.md)
- [ApiV1PublisherPushCampaignsPost200Response](docs/Model/ApiV1PublisherPushCampaignsPost200Response.md)
- [ApiV1PublisherPushCampaignsPostRequest](docs/Model/ApiV1PublisherPushCampaignsPostRequest.md)
- [ApiV1PublisherPushDeliveryRulesGet200Response](docs/Model/ApiV1PublisherPushDeliveryRulesGet200Response.md)
- [ApiV1PublisherPushDeliveryRulesIdPutRequest](docs/Model/ApiV1PublisherPushDeliveryRulesIdPutRequest.md)
- [ApiV1PublisherPushDeliveryRulesPostRequest](docs/Model/ApiV1PublisherPushDeliveryRulesPostRequest.md)
- [ApiV1PublisherPushSegmentsGet200Response](docs/Model/ApiV1PublisherPushSegmentsGet200Response.md)
- [ApiV1PublisherPushSegmentsIdPutRequest](docs/Model/ApiV1PublisherPushSegmentsIdPutRequest.md)
- [ApiV1PublisherPushSegmentsPostRequest](docs/Model/ApiV1PublisherPushSegmentsPostRequest.md)
- [ApiV1PublisherPushSetupGet200Response](docs/Model/ApiV1PublisherPushSetupGet200Response.md)
- [ApiV1PublisherPushSetupGet200ResponseData](docs/Model/ApiV1PublisherPushSetupGet200ResponseData.md)
- [ApiV1PublisherPushSetupGet200ResponseDataAdzonesInner](docs/Model/ApiV1PublisherPushSetupGet200ResponseDataAdzonesInner.md)
- [ApiV1PublisherPushStatsGet200Response](docs/Model/ApiV1PublisherPushStatsGet200Response.md)
- [ApiV1PublisherPushStatsGet200ResponseData](docs/Model/ApiV1PublisherPushStatsGet200ResponseData.md)
- [ApiV1PublisherPushStatsGet200ResponseDataDailyInner](docs/Model/ApiV1PublisherPushStatsGet200ResponseDataDailyInner.md)
- [ApiV1PublisherPushStatsGet200ResponseDataGrowthInner](docs/Model/ApiV1PublisherPushStatsGet200ResponseDataGrowthInner.md)
- [ApiV1PublisherPushStatsGet200ResponseDataSummary](docs/Model/ApiV1PublisherPushStatsGet200ResponseDataSummary.md)
- [ApiV1PublisherPushSubscribersActionPost200Response](docs/Model/ApiV1PublisherPushSubscribersActionPost200Response.md)
- [ApiV1PublisherPushSubscribersActionPost200ResponseData](docs/Model/ApiV1PublisherPushSubscribersActionPost200ResponseData.md)
- [ApiV1PublisherPushSubscribersActionPostRequest](docs/Model/ApiV1PublisherPushSubscribersActionPostRequest.md)
- [ApiV1PublisherPushSubscribersGet200Response](docs/Model/ApiV1PublisherPushSubscribersGet200Response.md)
- [ApiV1PublisherPushSubscribersGet200ResponseData](docs/Model/ApiV1PublisherPushSubscribersGet200ResponseData.md)
- [ApiV1PublisherSmartzonesIdPutRequest](docs/Model/ApiV1PublisherSmartzonesIdPutRequest.md)
- [ApiV1PublisherSmartzonesIdPutRequestIsActive](docs/Model/ApiV1PublisherSmartzonesIdPutRequestIsActive.md)
- [ApiV1PublisherSmartzonesIdPutRequestUniqueOnly](docs/Model/ApiV1PublisherSmartzonesIdPutRequestUniqueOnly.md)
- [ApiV1PublisherSmartzonesImportPostRequest](docs/Model/ApiV1PublisherSmartzonesImportPostRequest.md)
- [ApiV1PublisherSmartzonesImportPostRequestZonesInner](docs/Model/ApiV1PublisherSmartzonesImportPostRequestZonesInner.md)
- [ApiV1PublisherSmartzonesNetworksPostRequest](docs/Model/ApiV1PublisherSmartzonesNetworksPostRequest.md)
- [ApiV1PublisherSmartzonesPostRequest](docs/Model/ApiV1PublisherSmartzonesPostRequest.md)
- [ApiV1PublisherSspCampaignsIdTestRtbPostRequest](docs/Model/ApiV1PublisherSspCampaignsIdTestRtbPostRequest.md)
- [ApiV1PublisherSspCampaignsIdToggleDeletePutRequest](docs/Model/ApiV1PublisherSspCampaignsIdToggleDeletePutRequest.md)
- [ApiV1PublisherSspCampaignsIdTogglePausePutRequest](docs/Model/ApiV1PublisherSspCampaignsIdTogglePausePutRequest.md)
- [ApiV1PublisherSspSellerDisclosurePutRequest](docs/Model/ApiV1PublisherSspSellerDisclosurePutRequest.md)
- [ApiV1RetargetingPixelsGet200Response](docs/Model/ApiV1RetargetingPixelsGet200Response.md)
- [ApiV1RetargetingPixelsIdActivePatch200Response](docs/Model/ApiV1RetargetingPixelsIdActivePatch200Response.md)
- [ApiV1RetargetingPixelsIdDelete200Response](docs/Model/ApiV1RetargetingPixelsIdDelete200Response.md)
- [ApiV1RtbTestRtbPostRequest](docs/Model/ApiV1RtbTestRtbPostRequest.md)
- [ApiV1SharedReportsIdPutRequest](docs/Model/ApiV1SharedReportsIdPutRequest.md)
- [ApiV1SharedReportsIdPutRequestCampaignsInner](docs/Model/ApiV1SharedReportsIdPutRequestCampaignsInner.md)
- [ApiV1SharedReportsIdPutRequestSitesInner](docs/Model/ApiV1SharedReportsIdPutRequestSitesInner.md)
- [ApiV1SitesGet200Response](docs/Model/ApiV1SitesGet200Response.md)
- [ApiV1SitesGet200ResponseDataInner](docs/Model/ApiV1SitesGet200ResponseDataInner.md)
- [ApiV1SitesIdClonePostRequest](docs/Model/ApiV1SitesIdClonePostRequest.md)
- [ApiV1SitesSchemaGet200Response](docs/Model/ApiV1SitesSchemaGet200Response.md)
- [ApiV1SitesSchemaGet200ResponseFieldsInner](docs/Model/ApiV1SitesSchemaGet200ResponseFieldsInner.md)
- [ApiV1TagsIdPutRequest](docs/Model/ApiV1TagsIdPutRequest.md)
- [ApiV1TagsMassDeletePostRequest](docs/Model/ApiV1TagsMassDeletePostRequest.md)
- [ApiV1TagsPostRequest](docs/Model/ApiV1TagsPostRequest.md)
- [ApiV1ToolsReplaceDomainPutRequest](docs/Model/ApiV1ToolsReplaceDomainPutRequest.md)
- [ApiV1TrackerAdNetworksDeleteReportsPutRequest](docs/Model/ApiV1TrackerAdNetworksDeleteReportsPutRequest.md)
- [ApiV1TrackerAdNetworksIdPutRequest](docs/Model/ApiV1TrackerAdNetworksIdPutRequest.md)
- [ApiV1TrackerAdNetworksPostRequest](docs/Model/ApiV1TrackerAdNetworksPostRequest.md)
- [ApiV1TrackerAffiliateNetworksGet200Response](docs/Model/ApiV1TrackerAffiliateNetworksGet200Response.md)
- [ApiV1TrackerAffiliateNetworksIdPutRequest](docs/Model/ApiV1TrackerAffiliateNetworksIdPutRequest.md)
- [ApiV1TrackerAffiliateNetworksPostRequest](docs/Model/ApiV1TrackerAffiliateNetworksPostRequest.md)
- [ApiV1TrackerConversionLoggingPutRequest](docs/Model/ApiV1TrackerConversionLoggingPutRequest.md)
- [ApiV1TrackerConversionTypesPutRequest](docs/Model/ApiV1TrackerConversionTypesPutRequest.md)
- [ApiV1TrackerConversionsGet200Response](docs/Model/ApiV1TrackerConversionsGet200Response.md)
- [ApiV1TrackerConversionsGet200ResponseSummary](docs/Model/ApiV1TrackerConversionsGet200ResponseSummary.md)
- [ApiV1TrackerConversionsIdStatePutRequest](docs/Model/ApiV1TrackerConversionsIdStatePutRequest.md)
- [ApiV1TrackerCustomParamsPutRequest](docs/Model/ApiV1TrackerCustomParamsPutRequest.md)
- [ApiV1TrackerCustomParamsPutRequestSlotsInner](docs/Model/ApiV1TrackerCustomParamsPutRequestSlotsInner.md)
- [ApiV1TrackerOffersBulkPostRequest](docs/Model/ApiV1TrackerOffersBulkPostRequest.md)
- [ApiV1TrackerOffersGet200Response](docs/Model/ApiV1TrackerOffersGet200Response.md)
- [ApiV1TrackerOffersIdGet200Response](docs/Model/ApiV1TrackerOffersIdGet200Response.md)
- [ApiV1TrackerOffersIdPutRequest](docs/Model/ApiV1TrackerOffersIdPutRequest.md)
- [ApiV1TrackerOffersIdUrlsPostRequest](docs/Model/ApiV1TrackerOffersIdUrlsPostRequest.md)
- [ApiV1TrackerOffersIdUrlsUrlIdPutRequest](docs/Model/ApiV1TrackerOffersIdUrlsUrlIdPutRequest.md)
- [ApiV1TrackerOffersPostRequest](docs/Model/ApiV1TrackerOffersPostRequest.md)
- [ApiV1TrackerOffersPostRequestUrlsInner](docs/Model/ApiV1TrackerOffersPostRequestUrlsInner.md)
- [ApiV1TrackerReportSchedulesGet200Response](docs/Model/ApiV1TrackerReportSchedulesGet200Response.md)
- [ApiV1TrackerReportSchedulesIdGet200Response](docs/Model/ApiV1TrackerReportSchedulesIdGet200Response.md)
- [ApiV1TrackerReportSchedulesIdPutRequest](docs/Model/ApiV1TrackerReportSchedulesIdPutRequest.md)
- [ApiV1TrackerReportSchedulesPostRequest](docs/Model/ApiV1TrackerReportSchedulesPostRequest.md)
- [ApiV1TrackerRulesGet200Response](docs/Model/ApiV1TrackerRulesGet200Response.md)
- [ApiV1TrackerRulesIdGet200Response](docs/Model/ApiV1TrackerRulesIdGet200Response.md)
- [ApiV1TrackerRulesIdPutRequest](docs/Model/ApiV1TrackerRulesIdPutRequest.md)
- [ApiV1TrackerRulesPostRequest](docs/Model/ApiV1TrackerRulesPostRequest.md)
- [ApiV1TrackerSourceAccountsGet200Response](docs/Model/ApiV1TrackerSourceAccountsGet200Response.md)
- [ApiV1TrackerSourceAccountsIdGet200Response](docs/Model/ApiV1TrackerSourceAccountsIdGet200Response.md)
- [ApiV1TrackerSourceAccountsIdPutRequest](docs/Model/ApiV1TrackerSourceAccountsIdPutRequest.md)
- [ApiV1TrackerSourceAccountsPostRequest](docs/Model/ApiV1TrackerSourceAccountsPostRequest.md)
- [ApiV1TrackerSourcesGet200Response](docs/Model/ApiV1TrackerSourcesGet200Response.md)
- [ApiV1TrackerSourcesIdPutRequest](docs/Model/ApiV1TrackerSourcesIdPutRequest.md)
- [ApiV1TrackerSourcesPostRequest](docs/Model/ApiV1TrackerSourcesPostRequest.md)
- [ApiV1TrackerTrackingLinksBulkPostRequest](docs/Model/ApiV1TrackerTrackingLinksBulkPostRequest.md)
- [ApiV1TrackerTrackingLinksGet200Response](docs/Model/ApiV1TrackerTrackingLinksGet200Response.md)
- [ApiV1TrackerTrackingLinksIdBlocklistPostRequest](docs/Model/ApiV1TrackerTrackingLinksIdBlocklistPostRequest.md)
- [ApiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePostRequest](docs/Model/ApiV1TrackerTrackingLinksIdMediaBuyingActionsActionIdDecidePostRequest.md)
- [ApiV1TrackerTrackingLinksIdMediaBuyingBlockPostRequest](docs/Model/ApiV1TrackerTrackingLinksIdMediaBuyingBlockPostRequest.md)
- [ApiV1TrackerTrackingLinksIdMediaBuyingIpsPostRequest](docs/Model/ApiV1TrackerTrackingLinksIdMediaBuyingIpsPostRequest.md)
- [ApiV1TrackerTrackingLinksIdMediaBuyingReportPostRequest](docs/Model/ApiV1TrackerTrackingLinksIdMediaBuyingReportPostRequest.md)
- [ApiV1TrackerTrackingLinksIdMediaBuyingSourceBidPutRequest](docs/Model/ApiV1TrackerTrackingLinksIdMediaBuyingSourceBidPutRequest.md)
- [ApiV1TrackerTrackingLinksIdMediaBuyingTargetingPutRequest](docs/Model/ApiV1TrackerTrackingLinksIdMediaBuyingTargetingPutRequest.md)
- [ApiV1TrackerTrackingLinksIdNetworkBindingPutRequest](docs/Model/ApiV1TrackerTrackingLinksIdNetworkBindingPutRequest.md)
- [ApiV1TrackerTrackingLinksIdPutRequest](docs/Model/ApiV1TrackerTrackingLinksIdPutRequest.md)
- [ApiV1TrackerTrackingLinksIdToggleActivePost200Response](docs/Model/ApiV1TrackerTrackingLinksIdToggleActivePost200Response.md)
- [ApiV1TrackerTrackingLinksIdToggleActivePost200ResponseData](docs/Model/ApiV1TrackerTrackingLinksIdToggleActivePost200ResponseData.md)
- [ApiV1TrackerTrackingLinksIdZoneCostsGet200Response](docs/Model/ApiV1TrackerTrackingLinksIdZoneCostsGet200Response.md)
- [ApiV1TrackerTrackingLinksPostRequest](docs/Model/ApiV1TrackerTrackingLinksPostRequest.md)
- [ApiV1TrackingConversionTypesGet200Response](docs/Model/ApiV1TrackingConversionTypesGet200Response.md)
- [ApiV1TrackingConversionTypesGet200ResponseConversionTypesInner](docs/Model/ApiV1TrackingConversionTypesGet200ResponseConversionTypesInner.md)
- [ApiV1TrackingConversionTypesPut200Response](docs/Model/ApiV1TrackingConversionTypesPut200Response.md)
- [ApiV1TrackingConversionTypesPut200ResponseConversionTypesInner](docs/Model/ApiV1TrackingConversionTypesPut200ResponseConversionTypesInner.md)
- [ApiV1TrackingConversionTypesPutRequest](docs/Model/ApiV1TrackingConversionTypesPutRequest.md)
- [ApiV1TrackingConversionTypesPutRequestTypesInner](docs/Model/ApiV1TrackingConversionTypesPutRequestTypesInner.md)
- [ApiV1TrackingPixelsGet200Response](docs/Model/ApiV1TrackingPixelsGet200Response.md)
- [ApiV1TrackingPixelsIdPutRequest](docs/Model/ApiV1TrackingPixelsIdPutRequest.md)
- [ApiV1TrackingPixelsPostRequest](docs/Model/ApiV1TrackingPixelsPostRequest.md)
- [ApiV1UrlPoolsIdPutRequest](docs/Model/ApiV1UrlPoolsIdPutRequest.md)
- [ApiV1UrlPoolsIdUrlsPostRequest](docs/Model/ApiV1UrlPoolsIdUrlsPostRequest.md)
- [ApiV1UrlPoolsIdUrlsUrlIdPutRequest](docs/Model/ApiV1UrlPoolsIdUrlsUrlIdPutRequest.md)
- [ApiV1UrlPoolsIdUrlsUrlIdWeightPutRequest](docs/Model/ApiV1UrlPoolsIdUrlsUrlIdWeightPutRequest.md)
- [ApiV1UrlPoolsPostRequest](docs/Model/ApiV1UrlPoolsPostRequest.md)
- [ApiV1UrlPoolsReplaceUrlPutRequest](docs/Model/ApiV1UrlPoolsReplaceUrlPutRequest.md)
- [ApiV1WebhooksGet200Response](docs/Model/ApiV1WebhooksGet200Response.md)
- [ApiV1WebhooksIdDelete200Response](docs/Model/ApiV1WebhooksIdDelete200Response.md)
- [ApiV1WebhooksIdDeliveriesGet200Response](docs/Model/ApiV1WebhooksIdDeliveriesGet200Response.md)
- [ApiV1WebhooksIdDeliveriesGet200ResponseDataInner](docs/Model/ApiV1WebhooksIdDeliveriesGet200ResponseDataInner.md)
- [ApiV1WebhooksIdPatchRequest](docs/Model/ApiV1WebhooksIdPatchRequest.md)
- [ApiV1WebhooksIdRotateSecretPostRequest](docs/Model/ApiV1WebhooksIdRotateSecretPostRequest.md)
- [ApiV1WebhooksIdTestPost200Response](docs/Model/ApiV1WebhooksIdTestPost200Response.md)
- [ApiV1WebhooksIdTestPost200ResponseData](docs/Model/ApiV1WebhooksIdTestPost200ResponseData.md)
- [ApiV1WebhooksPost201Response](docs/Model/ApiV1WebhooksPost201Response.md)
- [ApiV1WebhooksPostRequest](docs/Model/ApiV1WebhooksPostRequest.md)
- [ApiV1XmlFeedsIdPutRequest](docs/Model/ApiV1XmlFeedsIdPutRequest.md)
- [ApiV1XmlFeedsPostRequest](docs/Model/ApiV1XmlFeedsPostRequest.md)
- [BidRule](docs/Model/BidRule.md)
- [BidRuleConditions](docs/Model/BidRuleConditions.md)
- [BidRuleset](docs/Model/BidRuleset.md)
- [BidRulesetListItem](docs/Model/BidRulesetListItem.md)
- [CampaignPanelFields](docs/Model/CampaignPanelFields.md)
- [CampaignPanelFieldsAboutSettings](docs/Model/CampaignPanelFieldsAboutSettings.md)
- [CampaignPanelFieldsAvCheck](docs/Model/CampaignPanelFieldsAvCheck.md)
- [CampaignPanelFieldsBoosterSettings](docs/Model/CampaignPanelFieldsBoosterSettings.md)
- [CampaignPanelFieldsConversionCapping](docs/Model/CampaignPanelFieldsConversionCapping.md)
- [CampaignPanelFieldsDayParting](docs/Model/CampaignPanelFieldsDayParting.md)
- [CampaignPanelFieldsDomainMask](docs/Model/CampaignPanelFieldsDomainMask.md)
- [CampaignPanelFieldsFeedSettings](docs/Model/CampaignPanelFieldsFeedSettings.md)
- [CampaignPanelFieldsPriority](docs/Model/CampaignPanelFieldsPriority.md)
- [CampaignPanelFieldsScreens](docs/Model/CampaignPanelFieldsScreens.md)
- [CampaignPanelFieldsSuggestions](docs/Model/CampaignPanelFieldsSuggestions.md)
- [CpaOptimizerBody](docs/Model/CpaOptimizerBody.md)
- [CpmOptimizerBody](docs/Model/CpmOptimizerBody.md)
- [Error](docs/Model/Error.md)
- [FilterDetail](docs/Model/FilterDetail.md)
- [FilterDetailAllOfRanges](docs/Model/FilterDetailAllOfRanges.md)
- [FilterSummary](docs/Model/FilterSummary.md)
- [FilterType](docs/Model/FilterType.md)
- [FundsDepositProof](docs/Model/FundsDepositProof.md)
- [FundsTxnClaim](docs/Model/FundsTxnClaim.md)
- [ListMeta](docs/Model/ListMeta.md)
- [MediaBuyingPolicy](docs/Model/MediaBuyingPolicy.md)
- [MediaBuyingPolicyTestPhase](docs/Model/MediaBuyingPolicyTestPhase.md)
- [OwnAdWrite](docs/Model/OwnAdWrite.md)
- [PushCampaign](docs/Model/PushCampaign.md)
- [PushDeliveryRule](docs/Model/PushDeliveryRule.md)
- [PushSegment](docs/Model/PushSegment.md)
- [PushSubscriber](docs/Model/PushSubscriber.md)
- [ResourceFilter](docs/Model/ResourceFilter.md)
- [RetargetingPixel](docs/Model/RetargetingPixel.md)
- [RetargetingPixelWrite](docs/Model/RetargetingPixelWrite.md)
- [RtbTestRequest](docs/Model/RtbTestRequest.md)
- [RtbTestResult](docs/Model/RtbTestResult.md)
- [SharedReportAdjust](docs/Model/SharedReportAdjust.md)
- [SharedReportBody](docs/Model/SharedReportBody.md)
- [SharedReportBodyCampaignsInner](docs/Model/SharedReportBodyCampaignsInner.md)
- [SharedReportBodyOptions](docs/Model/SharedReportBodyOptions.md)
- [SharedReportBodySettings](docs/Model/SharedReportBodySettings.md)
- [Site](docs/Model/Site.md)
- [SiteCreate](docs/Model/SiteCreate.md)
- [SiteSnippetWrite](docs/Model/SiteSnippetWrite.md)
- [SiteSnippetWriteConditions](docs/Model/SiteSnippetWriteConditions.md)
- [SspCampaignWrite](docs/Model/SspCampaignWrite.md)
- [SspCampaignWriteSlotsInner](docs/Model/SspCampaignWriteSlotsInner.md)
- [SspCampaignWriteZoneurlsInner](docs/Model/SspCampaignWriteZoneurlsInner.md)
- [StatsResponse](docs/Model/StatsResponse.md)
- [TrackerAffiliateNetwork](docs/Model/TrackerAffiliateNetwork.md)
- [TrackerConversion](docs/Model/TrackerConversion.md)
- [TrackerOffer](docs/Model/TrackerOffer.md)
- [TrackerReportSchedule](docs/Model/TrackerReportSchedule.md)
- [TrackerRule](docs/Model/TrackerRule.md)
- [TrackerSource](docs/Model/TrackerSource.md)
- [TrackerSourceAccount](docs/Model/TrackerSourceAccount.md)
- [TrackingLink](docs/Model/TrackingLink.md)
- [TrackingPixel](docs/Model/TrackingPixel.md)
- [UserEvent](docs/Model/UserEvent.md)
- [UserEventType](docs/Model/UserEventType.md)
- [UserWebhook](docs/Model/UserWebhook.md)

## Authorization

Authentication schemes defined for the API:
### apiKey

- **Type**: Bearer authentication (gas_live_<32 chars>)

### oauth2

- **Type**: `OAuth`
- **Flow**: `accessCode`
- **Authorization URL**: `/oauth/authorize`
- **Scopes**: 
    - **campaigns.read**: View campaigns
    - **campaigns.write**: View and change campaigns
    - **stats.read**: View stats
    - **sites.read**: View sites
    - **adspaces.read**: View adspaces
    - **payouts.read**: View publisher earnings and payouts
    - **billing.read**: View billing
    - **webhooks.read**: View webhooks and the event feed
    - **webhooks.write**: Manage webhooks

## Tests

To run the tests, use:

```bash
composer install
vendor/bin/phpunit
```

## Author



## About this package

This PHP package is automatically generated by the [OpenAPI Generator](https://openapi-generator.tech) project:

- API version: `1.0.0`
    - Package version: `1.1.0`
    - Generator version: `7.21.0`
- Build package: `org.openapitools.codegen.languages.PhpClientCodegen`
