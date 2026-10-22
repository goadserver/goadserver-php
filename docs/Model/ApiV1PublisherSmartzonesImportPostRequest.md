# ApiV1PublisherSmartzonesImportPostRequest

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**smart_network_id** | **int** | Your network account id. |
**network_id** | **int** | Catalogue network id stored as the zones&#39; &#x60;networkid&#x60;. | [optional]
**network_siteid** | **string** | Site id on the network | [optional]
**siteid** | **int** | Ignored — each zone takes its ad zone&#39;s site. | [optional]
**select_type** | **int** |  | [optional]
**select_pct** | **int** |  | [optional] [default to 100]
**settings** | **object** | Zone settings object applied to every zone (stored as JSON), e.g. &#x60;{\&quot;skip_when_stats\&quot;: 1}&#x60;. | [optional]
**zones** | [**\GoAdServerApi\Model\ApiV1PublisherSmartzonesImportPostRequestZonesInner[]**](ApiV1PublisherSmartzonesImportPostRequestZonesInner.md) |  |

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
