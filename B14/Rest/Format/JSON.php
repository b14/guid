<?php

//
namespace B14\Rest\Format;

/**
 * Get output encoded into JSON (JavaScript Object Notation).
 *
 * Format options are passed ontp the json_encode() PHP function.
 * You can use the json_encode constant names, without the JSON_ prefix
 * so JSON_PRETTY_PRINT would just be PRETTY_PRINT.
 * You can also use the actual value of the constants so JSON_PRETTY_PRINT
 * would be 128.
 * Add more than one options by giving an array of options.
 */
class JSON extends \B14\Rest\Format\Base
{
  /** {@inheritdoc} */
  const NAME = 'json';

  /** {@inheritdoc} */
  public function getContentType() {
    return 'application/json';
  }

  /** {@inheritdoc} */
  public function out($output, $options = array()) {
    $options_long = 0;

    foreach ($options as $option) {
      if (!is_numeric($option)) {
        $option = @constant('JSON_' . $option);
      }
      $options_long = $options_long | $option;
    }

    return json_encode($output, $options_long);
  }
}
