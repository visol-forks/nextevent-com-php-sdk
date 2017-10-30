# Change Log

All notable changes to the SDK will be documented in this file.

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
