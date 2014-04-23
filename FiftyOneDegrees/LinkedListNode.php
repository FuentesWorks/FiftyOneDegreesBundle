<?php

namespace FuentesWorks\FiftyOneDegreesBundle\FiftyOneDegrees;

/**
 * Represents a node with pointers to other nodes for the LinkedList class.
 */
class LinkedListNode {
    /**
     * The LinkedList this node is contained in.
     * @var LinkedList $linkedList
     */
    public $linkedList;

    /**
     * The value of this node. This can be any PHP variable.
     */
    public $value = -1;

    /**
     * The pointer to the next node. -1 means there is no next node.
     */
    public $nextNode = -1;

    /**
     * The pointer to the last node. -1 means there is no last node.
     */
    public $lastNode = -1;

    function __construct(LinkedList $linkedList) {
        $this->linkedList = $linkedList;
    }

    /**
     * Adds $value to a new node before this node.
     *
     * Creates a new node with $value that is inserted in the list before this
     * node. The list pointers are modified to accommodate the new node.
     *
     * @param $value
     *   The value of the new node. This can be any PHP variable.
     *
     * @return LinkedListNode
     *   The new node that was just created.
     */
    function addBefore($value) {
        $this->linkedList->count++;
        $newNode = new LinkedListNode($this->linkedList);
        $newNode->value = $value;
        $newNode->lastNode = $this->lastNode;
        $newNode->nextNode = $this;

        if ($this->lastNode !== -1) {
            $this->lastNode->nextNode = $newNode;
        }
        $this->lastNode = $newNode;

        if ($this->linkedList->first === $this) {
            $this->linkedList->first = $newNode;
        }
        return $newNode;
    }

    /**
     * Adds $value to a new node after this node.
     *
     * Creates a new node with $value that is inserted in the list after this
     * node. The list pointers are modified to accomodate the new node.
     *
     * @param $value
     *   The value of the new node. This can be any PHP variable.
     *
     * @return LinkedListNode
     *   The new node that was just created.
     */
    function addAfter($value) {
        $this->linkedList->count++;
        $newNode = new LinkedListNode($this->linkedList);
        $newNode->value = $value;
        $newNode->lastNode = $this;
        $newNode->nextNode = $this->nextNode;

        if ($this->nextNode !== -1) {
            $this->nextNode->lastNode = $newNode;
        }
        $this->nextNode = $newNode;

        if ($this->linkedList->last === $this) {
            $this->linkedList->last = $newNode;
        }
        return $newNode;
    }

    /**
     * Removes this node from the list and from other nodes referencing this one.
     *
     * This node will no longer be available from the list or any node in the
     * list.
     */
    function remove() {
        $this->linkedList->count--;
        if ($this->nextNode !== -1 && $this->lastNode !== -1) {
            $this->nextNode->lastNode = $this->lastNode;
            $this->lastNode->nextNode = $this->nextNode;
        }
        if ($this->linkedList->first === $this) {
            $this->linkedList->first = $this->nextNode;
        }
        if ($this->linkedList->last === $this) {
            $this->linkedList->last = $this->lastNode;
        }
        if ($this->linkedList->current === $this) {
            $this->linkedList->moveNext();
        }
    }
}