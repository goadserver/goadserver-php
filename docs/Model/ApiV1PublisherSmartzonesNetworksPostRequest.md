# ApiV1PublisherSmartzonesNetworksPostRequest

## Properties

Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**id** | **int** | Omit or 0 to create. | [optional]
**name** | **string** |  |
**prepend_name** | **string** |  | [optional]
**networkid** | **int** | Catalogue network id (&#x60;available_networks[].id&#x60;). | [optional]
**currency** | **string** | Currency the network reports earnings in. | [optional] [default to 'USD']
**is_active** | **int** |  | [optional]
**pullstats** | **int** | Pull earnings from the network&#39;s API. | [optional]
**apikey** | **string** |  | [optional]
**api_data** | **object** | Network credentials, e.g. &#x60;userid&#x60;, &#x60;username&#x60;, &#x60;userpass&#x60;, &#x60;client_id&#x60;, &#x60;client_secret&#x60;, per the network&#39;s flags. | [optional]

[[Back to Model list]](../../README.md#models) [[Back to API list]](../../README.md#endpoints) [[Back to README]](../../README.md)
