# Change Log

All notable changes to the SDK will be documented in this file.

## 1.2.0

* Bug fixes
  * Actually consider the value of the `$embed` argument in `Client::getOrder()`.

* Improvements
  * Implement `Serializable` interface on Model base class. This allows every model object to be serialized and stored as string.
  * Support multiple parameters for embedding widgets.
  * Renamed deprecated post message types. The old types are still supported.
  * Add getter `Event::getImage()` for teaser images per event.
  * Add models and getters for seat information on basket/order items.
  * Add models and getters for discount codes assigned to basket/order items.
  * Allow to fetch order details with items embedded.
  * Add more getters for `Order` model properties: `getOrderDate()`, `getTotal()` and `getCurrency()`
  * List sideevent items and ticket options as children of their parent basket/order items.
  * Allow re-booking via SDK using `Client::rebookOrder($orderId)` and additional widget parameters.
  * Support the cancellation of completed orders via SDK.

* Added new Classes
  * `Seat`
  * `DiscountCode`
  * `CancellationRequest`

* Added new methods to the client
  * `getOrders($filter)`
  * `rebookOrder($orderId)`
  * `requestCancellation($orderId)`
  * `settleCancellation($request)`


## 1.1.1

* Bug fixes
  * `InvalidModelDataException` was thrown but never imported in the `*::spawn` methods.
* Improvements
  * Creating multiple base categories and prices happens now in one request, rather than `/base_category` and `/base_price` requests per record.

## 1.1.0

* Added new Classes
  * `Filter`
  * `Collection`
  * Added new Models
    * `MutableModel`
    * `BaseCategory`
    * `BasePrice`
    * `AccessCode`
    * `Device`
    * `Gate`
    * `ScanLog`
* Added new Interfaces
  * `Spawnable`
* Added new methods to the client
  * `getAccessCodes($filter)`
  * `getScanLogs($filter)`
  * `getGate($gateId)`
  * `getGates($filter)`
  * `getDevice($deviceId)`
  * `getDevices($filter)`
  * `getBaseCategories($filter)`
  * `getBasePrices($filter)`
  * `getCategories($filter)`
  * `getPrices($filter)`
  * `createEvent($event)`
  * `createCategory($data)`
  * `createPrice($data)`

## 1.0.1

* Fixed caching of IAM tokens
* Differ between date-time and date objects on event date fields


## 1.0.0

Initial version with methods to book and print tickets.
