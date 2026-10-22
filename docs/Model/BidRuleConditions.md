# BidRuleConditions

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**regionids** | **int[]** | &#x60;geo2_locations.id&#x60; include-list. Visitor&#39;s lineage (continent / country / region / region2 / city) matches when **any** ancestor id is in this set. From &#x60;GET /api/v1/lookups/regions&#x60;. | [optional]
**os_id** | **int[]** | OS id include-list. From &#x60;GET /api/v1/lookups/operating-systems&#x60;. | [optional]
**browser_id** | **int[]** | Browser id include-list. From &#x60;GET /api/v1/lookups/browsers&#x60;. | [optional]
**device_id** | **int[]** | Device-type id include-list. From &#x60;GET /api/v1/lookups/device-types&#x60;. | [optional]
**zone** | **int[]** | Adzone id include-list (&#x60;publisher_adzones.id&#x60;). | [optional]
**domainids** | **int[]** | Domain id include-list (&#x60;bigdata.domains.id&#x60;). | [optional]
**zone_block** | **int[]** | Adzone id exclude-list (&#x60;publisher_adzones.id&#x60;). | [optional]
**domainids_block** | **int[]** | Domain id exclude-list (&#x60;bigdata.domains.id&#x60;). | [optional]
**carrier_id** | **int[]** | Cellular carrier id include-list (&#x60;isp_carriers.id&#x60;). Only meaningful when paired with &#x60;conn_type&#x60; containing &#x60;\&quot;cellular\&quot;&#x60;. | [optional]
**conn_type** | **string[]** | Connection-class include-list. | [optional]
**lang** | **string[]** | Two-letter ISO language code include-list (e.g. &#x60;[\&quot;en\&quot;, \&quot;de\&quot;]&#x60;). | [optional]
**hour_start** | **int** | Inclusive start hour (0..23) of the daypart window in the rule&#39;s tz. Pair with &#x60;hour_end&#x60;. When omitted, the rule runs at any hour. | [optional]
**hour_end** | **int** | Inclusive end hour (0..23) of the daypart window in the rule&#39;s tz. When &#x60;hour_end &lt; hour_start&#x60; the window wraps past midnight (e.g. 18→3 &#x3D; 18:00 through 03:00 next day). | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
