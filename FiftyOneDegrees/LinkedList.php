<?php

/**
 * @file
 * Provides functionality for a linked list.
 */

namespace FuentesWorks\FiftyOneDegreesBundle\FiftyOneDegrees;

/**
 * Provides functionality for a linked list, allowing nodes to be added and
 * removed from an arbitrary place in the list while maintaining performance.
 */
class LinkedList {
  
  /**
   * The current node being pointed at. -1 if there are no nodes.
   * @var int|LinkedListNode $current
   */
  public $current = -1;

  /**
   * The first node in the list. -1 if there are no nodes.
   * @var int|LinkedListNode $first
   */
  public $first = -1;

  /**
   * The last node in the list. -1 if there are no nodes.
   * @var int|LinkedListNode $last
   */
  public $last = -1;
  
  public $count = 0;
  
  function __construct() {
  
  }
  
  function getCount() {
    $node = $this->first;
    $fcount = 0;
    while ($node !== -1) {
      $node = $node->nextNode;
      $fcount++;
    }
    return $fcount;
  }
  
  /**
   * Moves the current node. Sets $current to -1 if there is no node afterwards.
   *
   * @return LinkedListNode
   *   Returns the new current node.
   */
  function moveNext() {
    if ($this->current !== -1 && $this->current->nextNode !== -1) {
      $this->current = $this->current->nextNode;
    }
    else {
      $this->current = -1;
    }
    return $this->current;
  }
  
  /**
   * Moves the current node. Sets $current to -1 if there is no node before.
   *
   * @return LinkedListNode
   *   Returns the new current node.
   */
  function moveBack() {
    if ($this->current !== -1 && $this->current->lastNode !== -1) {
      $this->current = -1;
    }
    else {
      $this->current = $this->current->lastNode;
    }
    return $this->current;
  }
  
  /**
   * Adds the $value to the end of the list.
   *
   * A new node will be created and given $value. Other nodes will have their
   * pointers changed to accommodate the new node.
   *
   * @param $value
   *   The value to add to the node. This can be any variable.
   *
   * @return LinkedListNode
   *   The new node just added.
   */
  function addLast($value) {
    $this->count++;
    $newNode = new LinkedListNode($this);
    $newNode->value = $value;
    if ($this->first === -1) {
      $this->first = $newNode;
    }
    if ($this->last !== -1) {
      $this->last->nextNode = $newNode;
      $newNode->lastNode = $this->last;
    }
    $this->last = $newNode;
    if ($this->current === -1) {
      $this->current = $newNode;
    }
    return $newNode;
  }
  
  /**
   * Adds the $value to the beginning of the list.
   *
   * A new node will be created and given $value. Other nodes will have their
   * pointers changed to accommodate the new node.
   *
   * @param $value
   *   The value to add to the node. This can be any variable.
   *
   * @return LinkedListNode
   *   The new node just added.
   */
  function addFirst($value) {
    $this->count++;
    $newNode = new LinkedListNode($this);
    $newNode->value = $value;
    if ($this->first !== -1) {
      $newNode->nextNode = $this->first;
      $this->first->lastNode = $newNode;
    }
    if ($this->last === -1) {
      $this->last = $newNode;
    }
    $this->first = $newNode;
    if ($this->current === -1) {
      $this->current = $newNode;
    }
    return $newNode;
  }
}