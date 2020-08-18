<?php

namespace NextEvent\PHPSDK\Model;

/**
 * Discount code model
 *
 * Struct for a discount code record assigned to an order/basket item or a DiscountGroup relation.
 *
 * @package NextEvent\PHPSDK\Model
 */
class DiscountCode extends MutableModel implements Spawnable
{
  /**
   * @inheritdoc
   */
  public function isNew()
  {
    return empty($this->source['discount_code_id']);
  }


  /**
   * @inheritdoc
   */
  public function isValid()
  {
    return ($this->isNew() && !empty($this->source['code'])) ||
      (!$this->isNew() && !(empty($this->source['title']) && empty($this->source['discount_group_id'])));
  }


  /**
   * Get the unique identifier for this discount code
   *
   * @return int
   */
  public function getId()
  {
    return $this->source['discount_code_id'];
  }


  /**
   * Get the title/name of this category
   *
   * @return string
   */
  public function getTitle()
  {
    return $this->source['title'];
  }


  /**
   * Get the display name of this discount code
   *
   * This is basically an alias for self::getTitle()
   *
   * @return string
   */
  public function getDisplayname()
  {
    return $this->source['title'];
  }


  /**
   * Get the code entered for this discount
   *
   * @return string
   */
  public function getCode()
  {
    return $this->source['code'];
  }



  /**
   * Get the valid from date
   *
   * @return DateTime|null
   */
  public function getValidFrom()
  {
    return isset($this->source['valid_from']) ? DateTime::fromJson($this->source['valid_from']) : null;
  }


  /**
   * Get the valid until date
   *
   * @return DateTime|null
   */
  public function getValidTo()
  {
    return isset($this->source['valid_to']) ? DateTime::fromJson($this->source['valid_to']) : null;
  }


  /**
   * Sets the DiscountGroup relation
   *
   * This operation is only permitted on new discount code models
   *
   * @param DiscountGroup $group
   * @return DiscountCode
   * @throws InvalidModelDataException
   */
  public function setDiscountGroup(DiscountGroup $group)
  {
    if ($this->isNew()) {
      $this->source['discount_group_id'] = $group->getId();
    } else {
      throw new InvalidModelDataException('DiscountGroup relation cannot be changed');
    }

    return $this;
  }


  /**
   * Creates a new discount code instance with the given data
   *
   * The resulting instance will be marked as a new model
   * which has not been persisted via API.
   *
   * @param array $data Model data as hash array with the following fields
   *              <br>
   *              * `code` (string) The unique discount code
   *              * `title` (string) Optional title to be displayed (will be inherited from the discount group if omitted)
   *              * `valid_from` (DateTime) Begin of the validity period
   *              * `valid_to` (DateTime) End of the validity period
   *              * `formdata` (array) Hash array (key => value pairs) with form data to be pre-filled into a personalization form when booking.
   * @param DiscountGroup $discountGroup Discount group to assign this code to
   * @return DiscountCode
   * @throws InvalidModelDataException
   */
  public static function spawn($data, DiscountGroup $discountGroup)
  {
    // copy relevant data from discount group
    if (!isset($data['title'])) {
      $data['title'] = $discountGroup->getTitle();
    }

    // convert datetime values
    if (isset($data['valid_from']) && $data['valid_from'] instanceof \DateTime) {
      $data['valid_from'] = $data['valid_from']->format(DATE_ATOM);
    }
    if (isset($data['valid_to']) && $data['valid_to'] instanceof \DateTime) {
      $data['valid_to'] = $data['valid_to']->format(DATE_ATOM);
    }

    if (isset($data['formdata']) && is_array($data['formdata'])) {
      $formdata = $data['formdata'];
      unset($data['formdata']);
    } else {
      $formdata = [];
    }

    $model = new DiscountCode($data + $formdata);
    $model->setDiscountGroup($discountGroup);

    if (!$model->get('discount_group_id')) {
      throw new InvalidModelDataException('Rissing required discount group relation');
    }

    return $model;
  }
}
