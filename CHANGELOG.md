# Change Log

All notable changes to the SDK will be documented in this file.

## 1.4.3

* New Methods
 * `Model\AccessCode::setState()` added new method to set state on AccessCode.
 * `Model\AccessCodeCollection::setState()` added new method to set state on AccessCodeCollection.
 * `Model\AccessCodeCollection::setAccessFrom()` added method to use in collection.
 * `Model\AccessCodeCollection::setAccessTo()` added method to use in collection.
## 1.4.2

* Compatibility
  * Changed warning suppression for PHP 8
  * Adjust psr/log to version ^1.0

* Changes in the client
  * Add timeout-option (timeoutHttpClient) for API requests

## 1.4.1

* Compatibility
  * Support Guzzle versions greater than 6.3.3 (e.g. newer versions 7.x)

* Changes in the client
  * `createAccessCode($accessCode)` has been added to import access codes from an external source.
  * `getAccessCode($accessCodeId)` Fetch single AccessCode.

## 1.4.0

* Improvements
  * Adapt to recent changes in backend now supporting interconnection bookings

* Changed Classes
  * Add new widget parameter `link` in `Widget::generateEmbedCode()`.
  * New method `Model\Basketitem::getEditSteps()` listing available edit options for each item.
  * Methods `Model\Basketitem::hasSeat()` and `Model\Basketitem::getSeat()` are now deprecated. Use `hasInfo()` and `getInfo()` methods instead.

* Changes in the client
  * `getOrder($orderId)` Fetches now all embeddable data from the server by setting the second parameter to `['*']`.
  * New method `getOrderDocuments($orderId)` returning a list of downloadable documents related to the given order.
    This includes the documents returned by `Client::getTicketDocuments($orderId)` which is now deprecated in favor of this new method.

## 1.3.5

* New Methods
 * `Model\AccessCode::setAccessFrom()` has been added.
 * `Model\AccessCode::setAccessTo()` has been added.

## 1.3.4

* New/Changed Classes
  * `EntityNotFoundException` has been added as common superclass of `OrderNotFoundException`, `OrderItemNotFoundException` and `ScanLogsNotFoundException`.
  * `APIResponseException::dumpAsString()` has been fixed to work with new Guzzle PSR-7 models.
  * `Model\DiscountCode` has new getters and setters for model attributes and is now a `MutableModel`.
  * `Model\DiscountGroup` has been added.

* Changes in the client
  * `getDiscountGroups($query)` has been added to list configured discount groups.
  * `getDiscountCodes($query)` has been added to list registered discount codes and their states.
  * `createDiscountCode($codes)` has been added to import new discount codes to a certain group.
  * `deleteDiscountCode($code)` has been added to delete voided discount codes.

## 1.3.3

* Improvements
  * Make event organizer information accessible via the `Event` model
  * Make date/time of an event or show accessible via the `Category` model

* Bug fixes
  * Fix Event model initialization from Webhook payload

* Changed Classes
  * New method `Model\Event::getOrganizer()` returning a model class containing organizer information
  * New method `Model\Category::getDate()` returning the date/time value of Category

## 1.3.2

* Improvements
  * Added new Capacity property to Categories because they can be set now individually on each Category
  * AvailableItems were replaced by Capacity on BaseCategories (same meaning)

* Changed Classes
  * `BaseCategory::getCapacity()` and `Category::getCapacity()`
  * `Category::getAvailableItems()`

## 1.3.1

* New Classes
  * `Model\WebhookMessage` makes usage of webhooks on the receiving server easy.

## 1.3.0

* Dependencies:
  * Updated Guzzle to 6.3.3
  * PHP >= 5.5.0

* New/Changed Classes
  * `AccessCodeCollection` contains only access codes and is able to update multiple access codes with one request.
  * `Util\Query` should now be used to set up filters and other query parameters.
  * `Utils\Filter` use instances of this class for filtering via the API.
  * `Model\Order` now has a `getExpires()` method like the `Basket` model does.

* Changes in the client
  * `getAccessCodes($query)` returns now `AccessCodeCollection` instead of `Collection`.
  * `authorizeOrder()` accepts a second argument `$options` with key `ttl` to define the expiration time of the payment authorization
  * `updateBasketExpiration($orderId, $exp)` allows changing the basket expiration time.
  * `getOrCreateDevice` has been added to provide an easy to use initialization for devices.

* Added new methods for invalidating access codes
  * `Device::login($gate)` and `Device::logout()`
  * `AccessCode::setEntryState($device)`
  * `AccessCodeCollection::setEntryState($device)`

## 1.2.1

* Added new methods to get created and changed dates of the following entities:
  * `Event::getCreatedDate()` and `Event::getChangedDate()`
  * `BaseCategory::getCreatedDate()` and `BaseCategory::getChangedDate()`
  * `BasePrice::getCreatedDate()` and `BasePrice::getChangedDate()`
  * `Category::getCreatedDate()` and `Category::getChangedDate()`
  * `Price::getCreatedDate()` and `Price::getChangedDate()`
  * `AccessCode::getCreatedDate()` and `AccessCode::getChangedDate()`
  * `ScanLog::getCreatedDate()` and `ScanLog::getChangedDate()`
  * `Device::getCreatedDate()` and `Device::getChangedDate()`
  * `Gate::getCreatedDate()` and `Gate::getChangedDate()`

* Added missing filter options in doc.

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
