<?php

namespace NextEvent\PHPSDK\Model;

use NextEvent\PHPSDK\Exception\AccessCodeValidateException;


/**
 * The access code collection holds access code instances.
 * An instance of this collection can update the entry state of all access codes inside of it with one single API
 * request.
 *
 * @package NextEvent\PHPSDK\Model
 */
class AccessCodeCollection extends Collection
{

  /**
   * Creates a new access code collection.
   *
   * @param array $data Optional response data to initialize the collection with.
   * @param \NextEvent\PHPSDK\Rest\Client $restClient Rest client for fetching next pages.
   */
  public function __construct($data = null, $restClient = null)
  {
    parent::__construct('NextEvent\PHPSDK\Model\AccessCode', array($restClient), $data, $restClient);
  }


  /**
   * Updates the entry state of the access codes via the given device at the logged in gate.
   * You will receive validation messages for each access code which failed to update the entry state.
   *
   * @param Device $device The device on which the codes have to be validated.
   * @param int $entryState The new entry state of the code. If the mode of the gate is not <b>both</b>,
   *                        the mode of the gate will be used by default.
   * @see AccessCode::ENTRY_IN and
   * @see AccessCode::ENTRY_OUT when passing <b>$entryState</b>
   * @param string $connection Optional connection parameter. Default is 'online'.
   * @param array|null $categories Optional list of categories to validate. By default the category id of this
   *                               access code will be set.
   * @param string $processed Optional processed time. Default is the current time of the server this code runs on.
   * @throws AccessCodeValidateException If the device is not logged in.<br>
   *                                       If <b>$entryState</b> is not set but the gate mode is <b>both</b>.<br>
   *                                       If an invalid <b>$entryState</b> has been passed.<br>
   *                                       If no rest client has been set on this model.
   * @return AccessCodeCollection
   */
  public function setEntryState($device, $entryState = null, $connection = 'online', $categories = null, $processed = null)
  {
    if (!$device->getGate()) {
      throw new AccessCodeValidateException('The device is not logged in!');
    }
    if (isset($this->restClient)) {

      if ($entryState === null) {
        $gate = $device->getGate();
        $entryState = $gate->getMode();
        if ($entryState === Gate::MODE_BOTH) {
          throw new AccessCodeValidateException('The gate is in mode "both"! ' .
                                                    'You have to provide $entryState by your own!');
        }
        $entryState = $entryState === Gate::MODE_OUT ? AccessCode::ENTRY_OUT : AccessCode::ENTRY_IN;
      }

      if ($entryState !== AccessCode::ENTRY_IN && $entryState !== AccessCode::ENTRY_OUT) {
        throw new AccessCodeValidateException('An entry state has to be set.' .
                                                ' Either AccessCode::ENTRY_IN or AccessCode::ENTRY_OUT!');
      }
      $codes = array();
      if ($processed === null) {
        $processed = date(DATE_ATOM);
      }
      foreach ($this as $code) {
        $found = array_filter($codes, function($c) use ($code) { return $c['code'] === $code->getCode(); });
        if (count($found) > 0) {
          $codeData = &$found[0];
          $k = array_search($codeData, $codes, true);
          if ($k !== false) {
            $codeData = &$codes[array_search($codeData, $codes, true)];
            $cats = &$codeData['categories'];
            if ($categories === null && array_search($code->getCategoryId(), $cats, true) === false) {
              $cats[] = $code->getCategoryId();
            }
          }
        } else {
          $codeData = array(
            'code' => $code->getCode(),
            'connection' => $connection,
            'processed' => $processed,
            'entry_state' => $entryState === AccessCode::ENTRY_OUT ? 'out' : 'in',
          );
          if ($categories === null) {
            $codeData['categories'] = array($code->getCategoryId());
          }
          else if (is_array($categories)) {
            $codeData['categories'] = $categories;
          }
          $codes[] = $codeData;
        }
      }
      $data = array(
        'device' => $device->getUUID(),
        'codes' => $codes
      );
      $response = $this->restClient->post('/access/invalidate/' . $device->getGate()->getId(), $data);
      $updatedCodes = new AccessCodeCollection($response->getContent(), $this->restClient);
      foreach ($updatedCodes as $code) {
        foreach ($this as $myCode) {
          if ($code->getId() === $myCode->getId()) {
            $myCode->unserialize($code->serialize());
          }
        }
      }
      return $this;
    } else {
      throw new AccessCodeValidateException('Call setRestClient first!');
    }
  }
}
