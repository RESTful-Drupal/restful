<?php

/**
 * @file
 * Contains RestfulException
 */

class RestfulException extends Exception {

  final public function getDescription() {
    return $this->description;
  }
}
