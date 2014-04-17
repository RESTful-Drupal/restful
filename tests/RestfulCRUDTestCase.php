<?php

/**
 * @file
 * Contains RestfulCRUDTestCase
 */

class RestfulCRUDTestCase extends DrupalWebTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Entity CRUD',
      'description' => 'Test the create, update and delete of an entity.',
      'group' => 'Restful',
    );
  }

  function setUp() {
    parent::setUp('restful_example');
  }

  /**
   * Test viewing an entity (GET method).
   */
  function testViewEntity() {
    $handler = restful_get_restful_handler('articles');

    $title = $this->randomName();

    $settings = array(
      'type' => 'article',
      'title' => $title,
    );
    $node1 = $this->drupalCreateNode($settings);
    $id = $node1->nid;

    $request['fields'] = 'id,label';
    $result = $handler->get($id, $request);
    $expected_result = array(
      'id' => $id,
      'label' => $title,
    );

    $this->assertEqual($result, $expected_result);
  }

  /**
   * Test creating an entity (POST method).
   */
  function testCreateEntity() {
    $handler = restful_get_restful_handler('articles');
    $label = $this->randomName();
    $request = array(
      'label' => $label,
    );
    $result = $handler->post('', $request);

    $node = node_load($result['id']);
    $expected_result = array(
      'id' => $node->nid,
      'label' => $node->title,
      'self' => url('node/' . $node->nid, array('absolute' => TRUE)),
    );

    $this->assertEqual($result, $expected_result, 'Entity was created.');
  }

}
