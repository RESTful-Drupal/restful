<?php

/**
 * @file
 * Contains RestfulFormatterHalJson.
 */

class RestfulFormatterHalXml extends \RestfulFormatterHalJson implements \RestfulFormatterInterface {

  /**
   * Content Type
   *
   * @var string
   */
  protected $contentType = 'application/xml; charset=utf-8';

  /**
   * {@inheritdoc}
   */
  public function render(array $structured_data) {
    return $this->arrayToXML($structured_data, new SimpleXMLElement('<api/>'))->asXML();
  }

  /**
   * Converts the input array into an XML formatted string.
   *
   * @param array $data
   *   The input array.
   * @param SimpleXMLElement $xml
   *   The object that will perform the conversion.
   *
   * @return SimpleXMLElement
   */
  protected function arrayToXML(array $data, SimpleXMLElement $xml) {
    foreach ($data as $key => $value) {
      if(is_array($value)) {
        if(!is_numeric($key)){
          $subnode = $xml->addChild("$key");
          $this->arrayToXML($value, $subnode);
        }
        else{
          $subnode = $xml->addChild("item$key");
          $this->arrayToXML($value, $subnode);
        }
      }
      else {
        $xml->addChild("$key", htmlspecialchars("$value"));
      }
    }
    return $xml;
  }

}

