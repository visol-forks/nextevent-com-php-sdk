<?php

namespace NextEvent\PHPSDK\Model;

/**
 * A mutable model is a model whose source attributes can be changed.
 *
 * An instance of a mutable model provides setters for any property.
 * This means you can call e.g. `$model->setFoo($val)` which will will store the value at the index `foo`
 * in the internal source array.
 *
 * You can also override the whole source at once with the {@link MutableModel::setSource()}.
 *
 * @package NextEvent\PHPSDK\Model
 */
abstract class MutableModel extends Model
{

  /**
   * Supports set-Methods for properties which are yet unknown.
   * If the source of your model contains, e.g. a property named 'my_property',
   * you can setMyProperty() to mutate it's value.
   * @param string $name
   * @param array $args
   * @return MutableModel
   */
  public function __call($name, $args)
  {
    if (strpos($name, 'set') === false) return parent::__call($name, $args);
    if (count($args) === 0) throw new \Exception('Setters are only allowed with an argument');
    $propName = substr(strtolower(preg_replace('/([A-Z])/', '_$1', $name)), 4);
    $this->source[$propName] = $args[0];
    return $this;
  }


  /**
   * Sets the source of this model instance.
   *
   * @access private
   * @param array $source The source data as received from the API.
   * @return MutableModel
   */
  public function setSource($source)
  {
    $this->source = $source;
    return $this;
  }

}
