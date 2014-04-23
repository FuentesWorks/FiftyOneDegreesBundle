<?php

/**
 * @file
 * Creates the $_51d array, for viewing properties about a device. 51Degrees is
 * also configured from here, all other aspects of the project use configuration
 * information from this file.
 */

namespace FuentesWorks\FiftyOneDegreesBundle\FiftyOneDegrees;

class FiftyOneDegrees
{
    //////// DEFAULT CONFIGURATION ////////
    // Below are global values that control aspects of 51Degrees.

    /**
     * Controls if some objects are cached in an array.
     * Objects are cached by default. Set to FALSE to disable.
     */
    private $USE_ARRAY_CACHE = true;

    /**
     * Controls if property values are set to their typed values or strings.
     * Defaults to TRUE, set to FALSE to disable.
     */
    private $RETURN_STRINGS = true;

    /**
     * Controls the file path that data is read from.
     * Defaults to 51Degrees.mobi.dat.
     * It's initialized in the constructor method
     */
    private $DATA_FILE_PATH;

    /**
     * Controls which property values should be returned from detection.
     * Greater performance can be gained from a restricted list of properties.
     * By default all values are returned.
     */
    private $NEEDED_PROPERTIES = array('IsMobile', 'HardwareModel', 'PlatformName', 'BrowserName');

    /**
     * Controls the maximum width an image can be resized too. This can be used to
     * control server load if many images are being processed.
     */
    private $MAX_IMAGE_WIDTH = 0;

    /**
     * Controls the maximum height an image can be resized too. This can be used to
     * control server load if many images are being processed.
     */
    private $MAX_IMAGE_HEIGHT = 0;

    /**
     * Specifies what the width parameter should be for an optimised image url.
     * Defaults to 'width'.
     */
    private $IMAGE_WIDTH_PARAMETER = 'width';

    /**
     * Specifies what the height parameter should be for an optimised image url.
     * Defaults to 'height'.
     */
    private $IMAGE_HEIGHT_PARAMETER = 'height';

    /**
     * Sets a factor images dimensions must have. Image sizes are rounded down to
     * nearest multiple. This can be used to control server load if many images are
     * being processed.
     */
    private $IMAGE_FACTOR = 1;

    /* DEPRECATED
     * The library will no longer be executed when included
     * since global variables have been completely removed.

    // If TRUE detection functions are not called globally by including this
    // script, they have to be called explicitly.
    private $DEFER_EXECUTION = TRUE;

    if ((isset($_fiftyone_degrees_defer_execution) &&
    $_fiftyone_degrees_defer_execution == TRUE) == FALSE) {
    global $_51d;
    $_51d = fiftyone_degrees_get_device_data($_SERVER['HTTP_USER_AGENT']);
    }
    */

    //////// END: DEFAULT CONFIGURATION ////////

    /**
     * The $_51d Device Data array
     * @param array $deviceData
     */
    private $deviceData;

    /**
     * The DataReader instance to handle the data file
     * @var DataReader $dataReader
     */
    private $dataReader;

    /**
     * Cache array for USE_CACHE_ARRAY option
     */
    private $cache;

    /**
     * Debug information
     */
    private $debugInfo = array();

    /**
     * Timing information
     */
    private $timings = array();

    /**
     * State information
     */
    private $state = array();

    /**
     * Lower score indicator
     */
    private $lowestScore;

    /**
     * @param string $dataFilePath
     * @throws \RuntimeException
     */
    public function __construct($dataFilePath=null)
    {
        /**
         * Controls the file path that data is read from.
         * Defaults to 51Degrees.mobi.dat.
         */
        $this->DATA_FILE_PATH = dirname(__FILE__) . '\51Degrees-Ultimate.dat';

        if ($dataFilePath)
        {
            $this->DATA_FILE_PATH = $dataFilePath;
        } else {
            $this->DATA_FILE_PATH = dirname(__FILE__) . '\51Degrees-Ultimate.dat';;
        }

        // Initialize the DataReader instance
        $this->dataReader = new DataReader($this->DATA_FILE_PATH);

    }

    /**
     * Set a specific configuration option
     *
     * @param string $name
     * @param mixed $value
     * @return FiftyOneDegrees|boolean
     */
    public function setOption($name, $value)
    {
        if(isset($this->$name) == false)
        {
            // Throw error, option not found.
            return false;
        }

        $this->$name = $value;

        return $this;
    }

    /**
     * Returns array of properties associated with the device.
     *
     * @param string $useragent
     *   The useragent of the device.
     *
     * @return array
     *   Array of properties and values.
     */
    public function getDeviceData($useragent) {
        $this->debugInfo = array();
        $start_time = microtime(TRUE);

        $info = array();

        if ($this->USE_ARRAY_CACHE !== FALSE) {
            $this->cache = array();
        }

        $headers = $this->dataReader->getHeaders();

        $root_char_nodes = $this->dataReader->readRootNodeOffsets($headers);

        // Unpack creates a 1 based array. array merge converts to 0 based.
        $useragent_bytes = array_merge(unpack('C*', $useragent));

        $useragent_length = count($useragent_bytes);
        $current_position = count($useragent_bytes) - 1;

        $matched_node_indexes = array();
        $this->debugInfo['root_nodes_evaluated'] = 0;
        $this->debugInfo['nodes_evaluated'] = 0;
        $this->debugInfo['string_read'] = 0;
        $this->debugInfo['signatures_read'] = 0;
        $this->debugInfo['signatures_compared'] = 0;
        $this->debugInfo['difference'] = 0;

        while ($current_position > 0) {
            $node = $this->dataReader->readNode(
                $root_char_nodes[$current_position],
                $headers);

            $this->debugInfo['root_nodes_evaluated']++;
            $node = $this->evaluateNode(
                $node,
                NULL,
                $useragent_bytes,
                $useragent_length,
                $headers);

            if ($node != NULL && $node['is_complete']) {
                // Add this node's index to the list for the match in the correct order.
                $index = $this->integerBinarySearch(
                    $matched_node_indexes,
                    $node['offset']);

                array_splice($matched_node_indexes, ~$index, 0, $node['offset']);
                // Check from the next character position to the left of this one.
                $current_position = $node['next_char_position'];
            }
            else {
                // No nodes matched at the character position.
                $current_position--;
            }
        }
        //$timings = array();
        $this->timings['node_match_time'] = microtime(TRUE) - $start_time;
        $signatures_checked = count($matched_node_indexes);
        $info['SignaturesChecked'] = $signatures_checked;
        $method = '';
        $this->timings['signature_match_time'] = microtime(TRUE);
        $matched_signature = $this->getSignature(
            $matched_node_indexes,
            $useragent_bytes,
            $method,
            $headers);

        if ($matched_signature != -1) {
            $best_signature = $this->dataReader->readSignature(
                $matched_signature,
                $headers);

            if (isset($this->lowestScore) == FALSE)
                $this->lowestScore = 0;
        }
        else {
            $this->lowestScore = PHP_INT_MAX;
            $best_signature = $this->dataReader->readSignature(0, $headers);
            $method = 'none';
        }

        $info['Method'] = $method;
        $this->timings['signature_match_time'] = microtime(TRUE) - $this->timings['signature_match_time'];
        $this->debugInfo['signature_string'] = $this->dataReader->getSignatureString($best_signature);

        //$info['UserAgent'] = $ua;
        $info['Confidence'] = $this->lowestScore;

        $profiles = array();
        $filled_components = array();

        $feature_detection_ids = $this->getFeatureDetectionProfileIds();
        foreach ($feature_detection_ids as $id) {
            $profile = $this->dataReader->getProfileFromId($id, $headers);
            // Make sure only one profile for each component can be added.
            if ($profile != NULL &&
                !in_array($profile['component_id'], $filled_components)) {
                $filled_components[] = $profile['component_id'];
                $profiles[] = $profile;
            }
        }

        $this->timings['profile_fetch_time'] = microtime(TRUE);
        foreach ($best_signature['profile_indexes'] as $profile_offset) {
            $profile = $this->dataReader->readProfile($profile_offset, $headers);
            // Check if this profile's component has already been filled.
            if (!in_array($profile['component_id'], $filled_components)) {
                $filled_components[] = $profile['component_id'];
                $profiles[] = $profile;
            }
        }
        $this->timings['profile_fetch_time'] = microtime(TRUE) - $this->timings['profile_fetch_time'];

        $this->timings['property_fetch_time'] = microtime(TRUE);
        $this->deviceData = $this->getPropertyData($profiles, $headers); // Initialize deviceData array
        $bandwidth = $this->getBandwidthData();
        if ($bandwidth != NULL) {
            foreach ($bandwidth as $k => $v) {
                $this->deviceData[$k] = $v;
            }
        }

        foreach ($info as $i_k => $i_v) {
            $this->deviceData[$i_k] = $i_v;
        }
        $this->timings['property_fetch_time'] = microtime(TRUE) - $this->timings['property_fetch_time'];
        $end_time = microtime(TRUE);
        //$duration = $end_time - $start_time;
        $this->deviceData['Time'] = $end_time - $start_time;
        $this->deviceData['debug_timings'] = $this->timings;
        $this->deviceData['debug_info'] = $this->debugInfo;

        $this->deviceData['DataFile'] = $this->DATA_FILE_PATH;
        return $this->deviceData;
    }

    public function getCompleteNumericNode(
        $node,
        $current_position,
        $useragent_bytes,
        $headers) {

        $complete_node = NULL;
        // Check to see if there's a next node which matches
        // exactly.
        $next_node = $this->getNextNodeIndex(
            $node,
            $useragent_bytes,
            $headers);
        $next_offset = 0;
        if ($next_node !== -1) {
            $next_offset = $next_node;
        }
        if ($next_node !== -1) {
            $next_node = $this->dataReader->readNode($next_node, $headers);
            $complete_node = $this->getCompleteNumericNode(
                $next_node,
                $current_position,
                $useragent_bytes,
                $headers);
        }
        //$complete_node_offset = -1;
        //if ($complete_node != NULL)
        //  $complete_node_offset = $complete_node['offset'];
        if ($complete_node == NULL && $node['numeric_children_count'] > 0) {
            // No. So try each of the numeric matches in ascending order of
            // difference.
            $target = $this->positionAsNumber($node['position'], $useragent_bytes);

            if ($target !== NULL) {
                $state = NULL;
                do {
                    $numeric_child = $this->numericNodeEnumeration(
                        $node,
                        $state,
                        $target,
                        $headers);
                    $enum_result_offset = 0;
                    if ($numeric_child !== NULL) {
                        $enum_result_offset = $numeric_child['related_node_offset'];
                    }
                    if ($numeric_child !== NULL) {
                        $enum_node = $this->dataReader->readNode(
                            $numeric_child['related_node_offset'],
                            $headers);

                        $complete_node = $this->getCompleteNumericNode(
                            $enum_node,
                            $current_position,
                            $useragent_bytes,
                            $headers);

                        if ($complete_node != NULL) {
                            $difference = abs($target - $numeric_child['value']);
                            if ($this->lowestScore == NULL)
                                $this->lowestScore = $difference;
                            else
                                $this->lowestScore += $difference;
                            break;
                        }
                    }
                } while ($this->state['has_result']);
            }
        }
        $complete_node_offset = 0;
        if ($complete_node !== NULL) {
            $complete_node_offset = $complete_node['offset'];
        }

        if ($complete_node == NULL && $node['is_complete'])
            $complete_node = $node;
        return $complete_node;
    }

    /**
     * Gets a suitable range for a target integer.
     *
     * @param int $target
     *   The target to create a range for.
     *
     * @return array
     *   The range, as an associative array with the values 'lower' and 'upper'.
     * @throws \RuntimeException
     */
    public function getRange($target) {
        $ranges = array(10000, 1000, 100, 10, 0);
        $upper = 32768;
        foreach ($ranges as $lower) {
            if ($target >= $lower && $target < $upper)
                return array('lower' => $lower, 'upper' => $upper);
            $upper = $lower;
        }
        // this should never happen
        throw new \RuntimeException('numerical target out of range.');
    }

    /**
     * Provides a pseudo enumerator for a nodes numeric children against the target.
     *
     * @param array $node
     *   A node with numeric children to iterate over.
     * param array &$state
     *   A state array to allow enumeration. Check $state['has_value'] === TRUE to
     *   see if the enumeration still has values and if this function should be
     *   called with the same $state array.
     * @param int $target
     *   The numeric value of the substring.
     * @param array $headers
     *   Header information from the data file.
     *
     * @return array
     *   The current NodeNumericIndex, or NULL if there isn't one.
     */
    public function numericNodeEnumeration($node, $target, $headers) {
        if ($this->state == NULL) {
            if ($target >= 0 && $target <= 32768) {

                // Get the range in which the comparison values need to fall.
                $this->state = $this->getRange($target);
                $this->state['numeric_children'] = $this->dataReader->getNumericNodeIndexes($node);

                $numeric_children_values = array();
                foreach ($this->state['numeric_children'] as $child) {
                    $numeric_children_values[] = $child['value'];
                }
                // Get the index in the ordered list to start at.
                $start_index = $this->integerBinarySearch(
                    $numeric_children_values,
                    $target);

                if ($start_index < 0)
                    $start_index = ~$start_index - 1;

                $low_index = $start_index;
                $high_index = $start_index + 1;

                // Determine if the low and high indexes are in range.
                $this->state['low_in_range'] = $low_index >= 0 && $low_index < $node['numeric_children_count'] &&
                    $this->state['numeric_children'][$low_index]['value'] >= $this->state['lower'] &&
                    $this->state['numeric_children'][$low_index]['value'] < $this->state['upper'];
                $this->state['high_in_range'] = $high_index < $node['numeric_children_count'] && $high_index >= 0 &&
                    $this->state['numeric_children'][$high_index]['value'] >= $this->state['lower'] &&
                    $this->state['numeric_children'][$high_index]['value'] < $this->state['upper'];

                $this->state['low_index'] = $low_index;
                $this->state['high_index'] = $high_index;
            }
            else {
                $this->state = array('has_result' => FALSE);
                return NULL;
            }
        }
        $low_index = $this->state['low_index'];
        $high_index = $this->state['high_index'];
        $result_value = NULL;
        $this->state['has_result'] = $this->state['low_in_range'] || $this->state['high_in_range'];

        if ($this->state['low_in_range'] && $this->state['high_in_range']) {
            // Get the differences between the two values.
            $low_difference
                = abs($this->state['numeric_children'][$low_index]['value'] - $target);
            $high_difference
                = abs($this->state['numeric_children'][$high_index]['value'] - $target);

            // Favour the lowest value where the differences are equal.
            if ($low_difference <= $high_difference) {
                $result_value = $this->state['numeric_children'][$low_index];

                // Move to the next low index.
                $low_index--;
                $this->state['low_in_range'] = $low_index >= 0 &&
                    $this->state['numeric_children'][$low_index]['value'] >= $this->state['lower'] &&
                    $this->state['numeric_children'][$low_index]['value'] < $this->state['upper'];
            }
            else {
                $result_value = $this->state['numeric_children'][$high_index];

                // Move to the next high index.
                $high_index++;
                $this->state['high_in_range'] = $high_index < count($this->state['numeric_children']) &&
                    $this->state['numeric_children'][$high_index]['value'] >= $this->state['lower'] &&
                    $this->state['numeric_children'][$high_index]['value'] < $this->state['upper'];
            }
        }
        elseif ($this->state['low_in_range']) {
            $result_value = $this->state['numeric_children'][$low_index];

            // Move to the next low index.
            $low_index--;
            $this->state['low_in_range'] = $low_index >= 0 &&
                $this->state['numeric_children'][$low_index]['value'] >= $this->state['lower'] &&
                $this->state['numeric_children'][$low_index]['value'] < $this->state['upper'];
        }
        elseif ($this->state['high_in_range']) {
            $result_value = $this->state['numeric_children'][$high_index];

            // Move to the next high index.
            $high_index++;
            $this->state['high_in_range'] = $high_index < count($this->state['numeric_children']) &&
                $this->state['numeric_children'][$high_index]['value'] >= $this->state['lower'] &&
                $this->state['numeric_children'][$high_index]['value'] < $this->state['upper'];
        }

        $this->state['low_index'] = $low_index;
        $this->state['high_index'] = $high_index;
        return $result_value;
    }

    /**
     * Returns the position given within the useragent as a number.
     *
     * @param int $node_position
     *   The node's position in the useragent to start looking from.
     * @param array $useragent_bytes
     *   An array of bytes representing the useragent in ascii values.
     *
     * @return array
     *   A number if one was found, or NULL.
     */
    public function positionAsNumber($node_position, $useragent_bytes) {
        $i = $node_position;
        while ($i >= 0 &&
            $useragent_bytes[$i] >= 48 &&
            $useragent_bytes[$i] <= 57)
            $i--;
        if ($i < $node_position) {
            $i++;
            return $this->getNumber(
                $useragent_bytes,
                $i,
                $node_position);
        }
        return NULL;
    }

    /**
     * Evaluates the given set of nodes numerically against the useragent.
     *
     * This function should be called if an exact match attempt has already failed.
     * This function will check for numbers in the useragent and compare them
     * against known nodes, looking for the smallest number difference. This is most
     * effective where a device's version number has changed and it is not currently
     * in the dataset.
     *
     * @param array $useragent_bytes
     *   An array of bytes representing the useragent in ascii values.
     * @param array $node_offsets
     *   A list of node offsets that have been found so far.
     * @param array $headers
     *   Header information from the data file.
     *
     * @return array
     *   A list of matching node offsets. This will also have the node offsets
     *   supplied in the parameters.
     */
    public function evaluateNumericNodes(
        $useragent_bytes,
        &$node_offsets,
        $headers) {

        $current_position = count($useragent_bytes) - 1;
        $existing_node_index = count($node_offsets) - 1;

        $this->lowestScore = NULL;

        $root_node_offsets = $this->dataReader->readRootNodeOffsets($headers);

        while ($current_position > 0) {
            // $existing_node = fiftyone_degrees_read_node($node_offsets[$existing_node_index], $headers);
            if ($existing_node_index >= 0) {
                $root_node = $this->dataReader->getNodesRootNode(
                    $node_offsets[$existing_node_index],
                    $headers);
                $root_node_position = $root_node['position'];
            }

            if ($existing_node_index < 0 || $root_node_position < $current_position) {
                $this->debugInfo['root_nodes_evaluated']++;
                $position_root = $this->dataReader->readNode(
                    $root_node_offsets[$current_position],
                    $headers);

                $node = $this->getCompleteNumericNode(
                    $position_root,
                    $current_position,
                    $useragent_bytes,
                    $headers);


                if ($node != NULL
                    && $this->getAnyNodesOverlap($node, $node_offsets, $headers)) {
                    // Insert the node and update the existing index so that
                    // it's the node to the left of this one.

                    $index = $this->integerBinarySearch(
                        $node_offsets,
                        $node['offset']);
                    array_splice($node_offsets, ~$index, 0, $node['offset']);
                    $existing_node_index = ~$index - 1;

                    // Move to the position of the node found as
                    // we can't use the next node in case there's another
                    // not part of the same signatures closer.
                    $current_position = $node['position'];
                }
                else
                    $current_position--;
            }
            else {
                // The next position to evaluate should be to the left
                // of the existing node already in the list.
                $existing_node = $this->dataReader->readNode($node_offsets[$existing_node_index], $headers);
                $current_position = $existing_node['position'];

                // Swap the existing node for the next one in the list.
                $existing_node_index--;
            }
        }
        return $node_offsets;
    }

    /**
     * Returns an array of profile id integers from feature detection.
     *
     * @return array
     *   The profile ids from feature detection, or an empty array if no ids were
     * found.
     */
    public function getFeatureDetectionProfileIds() {
        if (isset($_SESSION['51D_ProfileIds']) && strlen($_SESSION['51D_ProfileIds']) > 0) {
            $ids = explode('-', $_SESSION['51D_ProfileIds']);
            return $ids;
        }
        elseif (isset($_COOKIE['51D_ProfileIds'])) {
            $_SESSION['51D_ProfileIds'] = $_COOKIE['51D_ProfileIds'];
            $ids = explode('-', $_COOKIE['51D_ProfileIds']);
            return $ids;
        }
        return array();
    }

    /**
     * Returns the most suitable signature from a list of node indexes.
     *
     * @param array &$matched_node_indexes
     *   The array of node indexes previously matched.
     * @param array $useragent_bytes
     *   An array of bytes representing the useragent in ascii values.
     * @param string &$method
     *   Will have the method used to match. May return 'exact' or 'closest'.
     *   The supplied value has no effect on how this function is executed, and
     *   this value is always overwritten.
     * @param array $headers
     *   Header information from the data file.
     *
     * @return array
     *   The best fitting signature for the useragent from the given nodes.
     */
    public function getSignature(
        &$matched_node_indexes,
        $useragent_bytes,
        &$method,
        &$headers) {
        $matched_signature = $this->signatureBinarySearch(
            $matched_node_indexes,
            $headers);
        if ($matched_signature < 0) {
            $this->timings['numeric_match_time'] = microtime(TRUE);
            // No. So find any other nodes that match if numeric differences
            // are considered.
            $this->lowestScore = NULL;
            $matched_numeric_nodes = $this->evaluateNumericNodes(
                $useragent_bytes,
                $matched_node_indexes,
                $headers);

            // Can a precise match be found based on the nodes?
            $matched_signature = $this->signatureBinarySearch(
                $matched_numeric_nodes,
                $headers);

            $this->timings['numeric_match_time'] = microtime(TRUE) - $this->timings['numeric_match_time'];

            if ($matched_signature >= 0) {
                // Yes a precise match was found.
                $method = 'numeric';
                return $matched_signature;
            }

            if (count($matched_node_indexes) > 0) {
                $signatures = $this->getClosestSignatureIndexes(
                    $matched_node_indexes,
                    $headers);
                $signatures = array_splice($signatures, 0, $headers['info']['maximum_patterns']);

                // See Controller.EvaluateSignatures(Match match, List<Signature> signatures)
                // for .NET implementation

                // Store the score that we've got from the numeric difference
                // calculations.
                $starting_score = $this->lowestScore;

                $matched_signature = $this->evaluateSignatures(
                    $matched_numeric_nodes,
                    $signatures,
                    FALSE,
                    $useragent_bytes,
                    $headers);

                $method = 'nearest';

                if($matched_signature === NULL) {
                    $method = 'closest';

                    $matched_signature = $this->evaluateSignatures(
                        $matched_numeric_nodes,
                        $signatures,
                        TRUE,
                        $useragent_bytes,
                        $headers);
                    // Increase the lowest score by the starting value.
                    $this->lowestScore += $starting_score;
                    $this->debugInfo['difference'] = $this->lowestScore;

                    // Use default
                    /*if ($matched_signature === NULL) {

                    }*/
                }
            }
            if($matched_signature === NULL) {
                $method = 'none';
            }
        }
        else {
            $method = 'exact';
        }

        return $matched_signature;
    }

    public function evaluateSignatures(
        $matched_nodes,
        $signatures,
        $is_closest,
        $useragent_bytes,
        $headers) {

        if($is_closest === TRUE)
            $time_name = 'closest_match_evaluate_signatures';
        else
            $time_name = 'nearest_match_evaluate_signatures';
        $this->timings[$time_name] = microtime(TRUE);

        $matched_signature = NULL;

        $this->lowestScore = PHP_INT_MAX;
        $last_node_offset = $matched_nodes[count($matched_nodes) - 1];
        $last_node_root = $this->dataReader->getNodesRootNode($last_node_offset, $headers);
        $last_node_character = $last_node_root['position'];
        foreach ($signatures as $signature) {
            $result = $this->evaluateSignature(
                $matched_nodes,
                $signature,
                $useragent_bytes,
                $last_node_character,
                $is_closest,
                $headers);

            if ($result === TRUE) {
                $matched_signature = $signature;
            }
        }
        $this->timings[$time_name] = microtime(TRUE) - $this->timings[$time_name];
        return $matched_signature;
    }


    /**
     * Evaluates the signature against the target useragent.
     *
     * Compares all the characters up to the max length between the signature and
     * the target user agent.
     *
     * @param $matched_nodes
     * @param int $signature_index
     *   The index of the signature to evaluate
     * @param array $useragent_bytes
     *   The useragent as a byte array of ascii values to compare to.
     * @param $last_node_character
     * @param $is_closest
     * @param array $headers
     *   Header information from the data file.
     *
     * @return bool
     *   TRUE if the signature scores better than the supplied lowest score.
     */
    public function evaluateSignature(
        $matched_nodes,
        $signature_index,
        $useragent_bytes,
        $last_node_character,
        $is_closest,
        $headers) {
        $signature = $this->dataReader->readSignature($signature_index, $headers);

        $this->debugInfo['signatures_compared']++;

        $score = $this->getSignatureScore(
            $signature,
            $matched_nodes,
            $useragent_bytes,
            $last_node_character,
            $is_closest,
            $headers);

        if ($score < $this->lowestScore) {
            $this->lowestScore = $score;
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * Steps through the nodes of the signature comparing those that aren't
     * contained in the matched nodes to determine a score between the signature
     * and the target user agent. If that score becomes greater or equal to the
     * lowest score determined so far then stop.
     */
    public function getSignatureScore (
        $signature,
        $node_offsets,
        $useragent_bytes,
        $last_node_character,
        $is_closest,
        $headers) {

        $sig_length = $this->dataReader->getSignatureLength($signature, $headers);
        if ($is_closest === TRUE)
            $score = abs($last_node_character + 1 - $sig_length);
        else
            $score = 0;

        // We only need to check the nodes that are different. As the nodes
        // are in the same order we can simply look for those that are different.
        $match_node_index = 0;
        $signature_node_index = 0;
        while ($signature_node_index < count($signature['node_indexes'])
            && $score < $this->lowestScore) {
            $match_node_offset = $match_node_index >= count($node_offsets)
                ? PHP_INT_MAX : $node_offsets[$match_node_index];
            $signature_node_offset = $signature['node_indexes'][$signature_node_index];
            if ($match_node_offset > $signature_node_offset) {
                // The matched node is either not available, or is higher than
                // the current signature node. The signature node is not contained
                // in the match so we must score it.
                if ($is_closest) {
                    $score += $this->getScore(
                        $signature['node_indexes'][$signature_node_index],
                        $useragent_bytes,
                        $headers);
                }
                else {
                    $score += $this->getNearestScore(
                        $signature['node_indexes'][$signature_node_index],
                        $useragent_bytes,
                        $headers);
                }
                $signature_node_index++;
            }
            else if ($match_node_offset == $signature_node_offset) {
                // They both are the same so move to the next node in each.
                $match_node_index++;
                $signature_node_index++;
            }
            else if ($match_node_offset < $signature_node_offset) {
                // The match node is lower so move to the next one and see if
                // it's higher or equal to the current signature node.
                $match_node_index++;
            }
        }

        return $score;
    }

    /**
     * If the sub string is contained in the target but in a different position
     * return the difference between the two sub string positions.
     */
    public function getNearestScore(
        $node_index,
        $useragent_bytes,
        $headers) {

        $node = $this->dataReader->readNode($node_index, $headers);
        $index = $this->getNodeIndexInString($node, $useragent_bytes, $headers);
        if ($index >= 0)
            return abs($node['position'] + 1 - $index);

        // Return -1 to indicate that a score could not be calculated.
        return -1;
    }

    /**
     * Returns the start character position of the node within the target
     * user agent, or -1 if the node does not exist.
     */
    public function getNodeIndexInString($node, $useragent_bytes, $headers)
    {
        $characters = $this->dataReader->getNodeCharacters($node, $headers);
        $char_count = count($characters);
        $final_index = $char_count - 1;
        $ua_count = count($useragent_bytes);
        for ($index = 0; $index < $ua_count - $char_count; $index++) {
            for ($node_index = 0, $target_index = $index;
                 $node_index < $char_count && $target_index < $ua_count;
                 $node_index++, $target_index++) {

                if ($characters[$node_index] != $useragent_bytes[$target_index])
                    break;
                else if ($node_index == $final_index)
                    return $index;
            }
        }
        return -1;
    }


    /**
     * Calculates the score of the useragent against the given node.
     *
     * @param int $node_offset
     *   The offset of the node to score the useragent against.
     * @param array $useragent_bytes
    --uabytes-desc--
     * @param array $headers
    --headers-desc--
     *
     * @return int
     *   The score.
     */
    public function getScore(
        $node_offset,
        $useragent_bytes,
        $headers) {

        $score = 0;
        $node = $this->dataReader->readNode($node_offset, $headers);
        $node_characters = $this->dataReader->getNodeCharacters($node, $headers);
        $node_index = count($node_characters) - 1;

        $target_index
            = $node['position'] + $this->dataReader->getNodeLength($node, $headers);

        // Adjust the score and indexes if the node is too long.
        $useragent_length = count($useragent_bytes);
        if ($target_index >= $useragent_length) {
            $score = $target_index - $useragent_length;
            $node_index -= $score;
            $target_index = $useragent_length - 1;
        }

        while ($node_index >= 0 && $score < $this->lowestScore) {
            $difference = abs(
                $useragent_bytes[$target_index] - $node_characters[$node_index]);
            if ($difference != 0) {
                $numeric_difference = $this->getNumericDifference(
                    $node_characters,
                    $useragent_bytes,
                    $node_index,
                    $target_index);
                if ($numeric_difference != 0)
                    $score += $numeric_difference;
                else
                    $score += $difference;
            }
            $node_index--;
            $target_index--;
        }
        return $score;
    }

    /**
     * Checks for a numeric difference between the signature and useragent.
     *
     * @param array $node_characters
     *   An the node's characters as an ascii byte array.
     * @param array $target_characters
     *   The target user agent array.
     * @param int &$node_index
     *   The starting character to be checked in the node array.
     * @param int &$target_index">
     *   The start character position to the checked in the target array.
     *
     * @return int
     *   The numeric difference between the node and the target, or 0 if no
     *   difference was found.
     */
    public function getNumericDifference(
        $node_characters,
        $target_characters,
        &$node_index,
        &$target_index) {

        // Move right when the characters are numeric to ensure
        // the full number is considered in the difference comparison.
        $new_node_index = $node_index + 1;
        $new_target_index = $target_index + 1;
        while ($new_node_index < count($node_characters)
            && $new_target_index < count($target_characters)
            && $this->getIsNumeric($target_characters[$new_target_index])
            && $this->getIsNumeric($node_characters[$new_node_index])) {
            $new_node_index++;
            $new_target_index++;
        }
        $node_index = $new_node_index - 1;
        $target_index = $new_target_index - 1;

        // Find when the characters stop being numbers.
        $characters = 0;
        while ($node_index >= 0
            && $this->getIsNumeric($target_characters[$target_index])
            && $this->getIsNumeric($node_characters[$node_index])) {
            $node_index--;
            $target_index--;
            $characters++;
        }

        // If there is more than one character that isn't a number then
        // compare the numeric values.
        if ($characters > 1) {
            return abs(
                $this->getNumber($target_characters, $target_index + 1, $characters) -
                $this->getNumber($node_characters, $node_index + 1, $characters));
        }
        return 0;
    }

    public function getNodesOverlap($node, $compare_node, $headers) {
        $low_node = $node['position'] < $compare_node['position'] ? $node : $compare_node;
        $high_node = $low_node['position'] == $node['position'] ? $compare_node : $node;

        $low_root_node = $this->dataReader->getNodesRootNode($low_node['offset'], $headers);
        return $low_node['position'] == $high_node['position']
            || $low_root_node['position'] > $high_node;
    }

    public function getAnyNodesOverlap($node, $other_node_offsets, $headers) {
        foreach($other_node_offsets as $other_node_offset) {
            $other_node = $this->dataReader->readNode($other_node_offset, $headers);
            if ($this->getNodesOverlap($node, $other_node, $headers))
                return true;
        }
        return false;
    }

    /**
     * Checks if the strings are numerical and gets the difference between them.
     *
     * @param array $target_useragent
     *   An array of bytes representing the useragent in ascii values.
     * @param array $signature
     *   An array of bytes representing the signature in ascii values.
     * @param int $length
     *   The length to check in the string.
     * @param int &$index
     *   The position to start from.
     * @param int &$difference
     *   The numerical difference between the strings.
     */
    public function numericDifferenceCheck(
        $target_useragent,
        $signature,
        $length,
        &$index,
        &$difference) {
        // Record the start index.
        $start = $index;

        // Check that the proceeding characters are both either
        // non-numeric or numeric in each array.
        if ($index > 0 &&
            $this->getIsNumeric($target_useragent[$index - 1]) !=
            $this->getIsNumeric($signature[$index - 1]))
            return;

        // Find when the characters stop being numbers.
        while ($index < $length &&
            $this->getIsNumeric($target_useragent[$index]) &&
            $this->getIsNumeric($signature[$index]))
            $index++;

        // If there is more than one character that isn't a number then
        // compare the numeric values.
        $end = $index - 1;
        if ($end > $start) {
            $difference = abs(
                $this->getNumber($target_useragent, $start, $end) -
                $this->getNumber($signature, $start, $end));
        }
    }

    /**
     * Determines if the value is an ASCII numeric value.
     *
     * @param integer $value
     *   Byte value to be checked
     *
     * @returns bool
     *   TRUE if the value is an ASCII numeric character
     */
    function getIsNumeric($value) {
        return ($value >= ord('0') && $value <= ord('9'));
    }

    /**
     * Returns an integer representation of the characters between start and end.
     *
     * Assumes that all the characters are numeric characters.
     *
     * @param string $string
     *   Array of characters with numeric characters present between start and end.
     * @param int $start
     *   The first character to use to convert to a number
     * @param int $end
     *   The last character to use to convert to a number
     *
     * @return int
     *   The number the substring equates to
     */
    private function getNumber($string, $start, $end) {
        $value = 0;
        for ($i = $end, $p = 0; $i >= $start; $i--, $p++) {
            $value += pow(10, $p) * ($string[$i] - ord('0'));
        }
        return $value;
    }

    /**
     * Gets the properties of from all the given profiles.
     *
     * @param array $profiles
     *   The profiles to get properties for.
     * @param array $headers
     *   Header information from the data file.
     *
     * @return array
     *   Array of property values for the profiles.
     */
    public function getPropertyData($profiles, $headers) {

        $properties = array();
        for ($i = 0; $i < $headers['property_count']; $i++) {
            $property = $this->dataReader->readProperty($i, $headers);

            if ($this->isNeededProperty($property))
                $properties[] = $property;
        }

        $device_ids = array();
        $device_data = array();

        foreach ($profiles as $profile) {
            $device_ids[$profile['component_id']] = $profile['unique_id'];

            $profile_values = $this->getProfilePropertyValues(
                $profile,
                $properties,
                $headers);
            //$this->deviceData = array_merge($device_data, $profile_values);
            $device_data = array_merge($device_data, $profile_values);
        }
        ksort($device_ids);
        $device_data['DeviceId'] = implode('-', $device_ids);

        return $device_data;
    }

    public function getProfilePropertyValues($profile, $needed_properties, $headers) {
        $values = array();
        foreach ($profile['profile_values'] as $value) {
            if ($this->isNeededValue($needed_properties, $value)) {
                $property_value = $this->dataReader->readPropertyValue($value, $headers);
                $property = $this->dataReader->readProperty(
                    $property_value['property_index'],
                    $headers);

                if ($this->RETURN_STRINGS === FALSE)
                    $value = $property_value['value'];
                else
                    $value = $this->getTypedValue(
                        $property,
                        $property_value);
                if ($property['list']) {
                    if (!isset($values[$property['name']])) {
                        $values[$property['name']] = array();
                    }
                    $values[$property['name']][] = $value;
                }
                else
                    $values[$property['name']] = $value;
            }
        }
        return $values;
    }

    /**
     * Gets an array of bandwidth data.
     *
     * @return array
     *   An associative array of bandwidth data from this request and session.
     */
    public function getBandwidthData() {
        $bandwidth = NULL;
        $result = NULL;

        // Check that session and the bandwidth cookie are available.
        if (isset($_SESSION) && isset($_COOKIE['51D_Bandwidth'])) {
            $values = explode('|', $_COOKIE['51D_Bandwidth']);

            if (count($values) == 5) {
                $stats = $this->getBandwidthStats();

                if ($stats != NULL) {
                    $last_load_time = $stats['LastLoadTime'];
                }

                $load_start_time = floatval($values[1]);
                $current_time = floatval($values[2]);
                $load_complete_time = floatval($values[3]);
                $page_length = floatval($values[4]);

                $response_time = $load_complete_time - $load_start_time;
                if ($response_time == 0) {
                    $page_bandwidth = PHP_INT_MAX;
                }
                else {
                    $page_bandwidth = $page_length / $response_time;
                }

                if ($stats != NULL) {
                    $stats['LastResponseTime'] = $response_time;
                    $stats['last_completion_time'] = $load_complete_time - $last_load_time;
                    if (isset($stats['average_completion_time']))
                        $stats['average_completion_time']
                            = $this->getRollingAverage(
                            $stats['average_completion_time'],
                            $response_time,
                            $stats['Requests']);
                    else
                        $stats['average_completion_time'] = $stats['last_completion_time'];

                    $stats['AverageResponseTime']
                        = $this->getRollingAverage(
                        $stats['AverageResponseTime'],
                        $response_time,
                        $stats['Requests']);

                    $page_bandwidth = $this->getRollingAverage(
                        $stats['AverageBandwidth'],
                        $response_time,
                        $stats['Requests']);

                    $stats['AverageBandwidth'] = $page_bandwidth;
                    $stats['LastLoadTime'] = $current_time;
                    $stats['Requests']++;
                }
                else {
                    $stats = array(
                        'LastResponseTime' => $response_time,
                        'AverageResponseTime' => $response_time,
                        'AverageBandwidth' => $page_bandwidth,
                        'LastLoadTime' => $current_time,
                        'Requests' => 1,
                    );
                }
                $stats['page_length'] = $page_length;
                $this->setBandwidthStats($stats);

                if ($stats['Requests'] >= 3)
                    $result = $stats;
            }
        }

        setcookie('51D_Bandwidth', microtime(TRUE));

        return $result;
    }

    /**
     * Gets the a new average from a previous average with and new value.
     *
     * @param int $current_average
     *   The current average from previous calculations.
     * @param int $value
     *   The new value to add to the average.
     * @param int $count
     *   The number of elements in $current_average.
     * @return int
     */
    private function getRollingAverage($current_average, $value, $count) {
        return intval((($current_average * $count) + $value) / ($count + 1));
    }

    /**
     * Gets previous bandwidth stats.
     *
     * @return array
     *   An array of bandwidth stats, or NULL if none were found.
     */
    function getBandwidthStats() {
        if (isset($_SESSION['51D_stats'])) {
            return $_SESSION['51D_stats'];
        }
        return NULL;
    }

    /**
     * Stores bandwidth stats for future requests.
     *
     * @param array $stats
     *   An array of bandwidth stats to store.
     */
    function setBandwidthStats($stats) {
        if (isset($_SESSION)) {
            $_SESSION['51D_stats'] = $stats;
        }
    }

    /**
     * Returns a typed value of the given value and property.
     *
     * @param array $property
     *   The property.
     * @param array $profile_value
     *   The value of the property.
     *
     * @return mixed
     *   The value of the type string, int, double or bool.
     */
    public function getTypedValue($property, $profile_value) {
        $value_string = $profile_value['value'];
        switch ($property['value_type_id']) {
            // String and Javascript.
            case 0:
            case 4:
            default:
                return $value_string;
            // Int.
            case 1:
                return (int) $value_string;
            // Double.
            case 2:
                return (double) $value_string;
            // Bool.
            case 3:
                return (bool) $value_string;
        }
    }

    /**
     * Indicates if the given property is in the list of required properties.
     *
     * @param array $property
     *   The property to check for.
     *
     * @return bool
     *   TRUE if this property is needed.
     */
    private function isNeededProperty($property) {
        /* Unreadable:
        $is_set = isset($this->NEEDED_PROPERTIES);
        return $is_set === FALSE || ($is_set === TRUE
            && in_array($property['name'], $this->NEEDED_PROPERTIES));
        */

        /* Readable: */
        if(!isset($this->NEEDED_PROPERTIES) || !$this->NEEDED_PROPERTIES) {
            // No needed properties where specified, therefore ALL properties are needed
            return true;
        } else {
            // Needed properties is not set, then check
            return in_array($property['name'], $this->NEEDED_PROPERTIES);
        }
    }

    /**
     * Indicates if the given value index relates to a property in the list.
     *
     * @param array $properties
     *   The array of properties that values are required for.
     * @param int $value_index
     *   The index of the property value.
     *
     * @return bool
     *   TRUE if the value should be used.
     */
    private function isNeededValue($properties, $value_index) {
        if ($properties !== NULL) {
            if (isset($this->NEEDED_PROPERTIES)) {
                foreach ($properties as $prop) {
                    if ($value_index >= $prop['first_value_index']
                        && $value_index <= $prop['last_value_index'])
                        return true;
                }
                return false;
            }
        }
        return true;
    }

    /**
     * Gets a sorted array of signature indexes from the supplied nodes.
     *
     * @param array $node_indexes
     *   The array of node indexes to get signatures from.
     * @param array $headers
     *   Header information from the data file.
     *
     * @return array
     *   A sorted array of signature indexes.
     */
    public function getClosestSignatureIndexes(
        $node_indexes,
        $headers) {

        // Sort nodes in ascending order by signature count.
        $sorted_nodes = array();
        $nodes = array();

        $max_count = 1;
        $iteration = 2;

        $this->timings['closest_match_node_sort_time'] = microtime(TRUE);

        $node_count = count($node_indexes);
        for ($i = 0; $i < $node_count; $i++) {
            $node = $this->dataReader->readNode($node_indexes[$i], $headers);

            $sorted_nodes[$i] = $node['node_signature_count'];

            $nodes[] = $node;
        }
        array_multisort($sorted_nodes, SORT_DESC, $nodes);
        $nodes = array_reverse($nodes);
        $this->timings['closest_match_node_sort_time'] = microtime(TRUE) - $this->timings['closest_match_node_sort_time'];

        $this->timings['closest_match_node_fill_signatures_time'] = microtime(TRUE);
        for ($i = 0; $i < $node_count; $i++) {
            $this->dataReader->fillNodeSignatures($nodes[$i], $headers);
        }
        $this->timings['closest_match_node_fill_signatures_time'] = microtime(TRUE) - $this->timings['closest_match_node_fill_signatures_time'];

        $this->timings['closest_match_filling_linked_list_time'] = microtime(TRUE);

        // Building initial list.
        $linked_list = new LinkedList();
        if (count($nodes) > 0) {
            $node_0_signatures_count = count($nodes[0]['node_signatures']);
            for ($i = 0; $i < $node_0_signatures_count; $i++) {
                $linked_list->addLast(array($nodes[0]['node_signatures'][$i], 1));
            }
        }

        // Count the number of times each signature index occurs.
        for ($i = 1; $i < $node_count; $i++) {
            $max_count = $this->getClosestSignaturesForNode(
                $node_count,
                $nodes[$i]['node_signatures'],
                $linked_list,
                $max_count,
                $iteration);
            $iteration++;
        }
        $this->timings['closest_match_filling_linked_list_time'] = microtime(TRUE) - $this->timings['closest_match_filling_linked_list_time'];
        $this->timings['closest_match_sorting_signature_ranks'] = microtime(TRUE);

        $sig_offsets = array();
        $ranks = array();
        $linked_list->current = $linked_list->first;
        while ($linked_list->current !== -1) {
            if ($linked_list->current->value[1] == $max_count) {
                $this->debugInfo['signatures_read']++;
                $signature = $this->dataReader->readSignature($linked_list->current->value[0], $headers);
                $sig_offsets[] = $linked_list->current->value[0];
                $ranks[] = $signature['rank'];
            }
            $linked_list->moveNext();
        }
        array_multisort($ranks, SORT_ASC, $sig_offsets);

        $this->timings['closest_match_sorting_signature_ranks'] = microtime(TRUE) - $this->timings['closest_match_sorting_signature_ranks'];

        return $sig_offsets;
    }

    /**
     * Gets signatures that are most featured in the signature index list.
     *
     * This function fills a linked list of signatures depending on highly they're
     * ranked.
     *
     * @param int $nodes_found
     * @param LinkedList $linked_list
     * @param array $signature_index_list
     *   A list of signature indexes to check.
     * @param int $max_count
     *   The amount if times a signature should be seen before being excluded.
     * @param int $iteration
     *   The iteration of this function.
     * @return int
     */
    public function getClosestSignaturesForNode(
        $nodes_found,
        $signature_index_list,
        $linked_list,
        $max_count,
        $iteration) {

        $signature_index_count = count($signature_index_list);
        // If there is point adding any new signature indexes set the
        // threshold reached indicator. New signatures won't be added
        // and ones with counts lower than maxcount will be removed.
        $threshold_reached = $nodes_found - $iteration < $max_count;
        $current = $linked_list->first;
        $signature_index = 0;
        while ($signature_index < $signature_index_count && $current !== -1) {
            if ($current->value[0] > $signature_index_list[$signature_index]) {
                // The base list is higher than the target list. Add the element
                // from the target list and move to the next element in each.
                if ($threshold_reached == FALSE) {
                    $current->addBefore(
                        array($signature_index_list[$signature_index], 1));
                }
                $signature_index++;
            }
            else if ($current->value[0] < $signature_index_list[$signature_index]) {
                if ($threshold_reached) {
                    // Threshold reached so we can removed this item
                    // from the list as it's not relevant.
                    $next_item = $current->nextNode;
                    if ($current->value[1] < $max_count) {
                        $current->remove();
                    }
                    $current = $next_item;
                }
                else {
                    $current = $current->nextNode;
                }
            }
            else {
                // They're the same so increase the frequency and move to the next
                // element in each.
                $current->value[1]++;
                if ($current->value[1] > $max_count)
                    $max_count = $current->value[1];
                $signature_index++;
                $current = $current->nextNode;
            }
        }
        if ($threshold_reached === FALSE) {
            // Add any signature indexes higher than the base list to the base list.
            while ($signature_index < $signature_index_count) {
                $linked_list->addLast(
                    array($signature_index_list[$signature_index], 1));
                $signature_index++;
            }
        }
        return $max_count;
    }

    /**
     * Gets the index of the signature relating to given node indexes.
     *
     * @param array $node_indexes
     *   The array of node indexes previously matched.
     * @param array $headers
     *   Header information from the data file.
     *
     * @return int
     *   The index of the signature. Returns the ~ of position the signature
     *   should be inserted into if once cannot be found.
     */
    public function signatureBinarySearch($node_indexes, $headers){
        $lower = 0;
        $upper = $headers['signatures_count'] - 1;
        $middle = 0;

        while ($lower <= $upper) {
            $this->debugInfo['signatures_read']++;
            $middle = $lower + (int) (($upper - $lower) / 2);
            $signature = $this->dataReader->readSignature($middle, $headers);
            $comparison_result = $this->compareSignatureToNodeIndexes(
                $signature,
                $node_indexes);

            if ($comparison_result == 0)
                return $middle;
            elseif ($comparison_result > 0)
                $upper = $middle - 1;
            else
                $lower = $middle + 1;
        }
        return ~$middle;
    }

    /**
     * Compares a signature to node indexes.
     *
     * @param array $signature
     *   The signature.
     * @param array $node_indexes
     *   The node indexes.
     *
     * @return int
     *   0 if the signatures and nodes are identical.
     */
    private function compareSignatureToNodeIndexes($signature, $node_indexes) {
        $signature_node_indexes = count($signature['node_indexes']);
        $nodes_count = count($node_indexes);
        $length = min($signature_node_indexes, $nodes_count);

        for ($i = 0; $i < $length; $i++) {
            $difference = $this->integerCompareTo($signature['node_indexes'][$i], $node_indexes[$i]);
            if ($difference != 0)
                return $difference;
        }

        if ($signature_node_indexes < $nodes_count)
            return -1;
        if ($signature_node_indexes > $nodes_count)
            return 1;

        return 0;
    }

    /**
     * A generic implementation of a divide and conquer search for an integer list.
     *
     * @param array $list
     *   List of numbers.
     * @param int $value
     *   Value to search for.
     *
     * @return int
     *   The key of the found value, or a negative value if it is not present.
     */
    private function integerBinarySearch($list, $value) {
        $lower = 0;
        $upper = count($list) - 1;
        $middle = 0;

        while ($lower <= $upper) {
            // $middle = $lower + (int) (($upper - $lower) / 2);

            $d = ($upper + $lower) / 2;
            $middle = intval(floor($d)); // array index must be an int
            $comparison_result = $this->integerCompareTo(
                $list[$middle],
                $value);
            if ($comparison_result == 0)
                return $middle;
            elseif ($comparison_result > 0) {
                $upper = $middle - 1;
            }
            else {
                // Middle must be modified in this instance so the 2's complement can be
                // trusted if the value doesn't exist and this is the last iteration.
                $middle++;
                $lower = $middle;
            }
        }
        return ~$middle;
    }

    /**
     * Compares two integers.
     *
     * @param int $value
     *   A value
     * @param int $comparison
     *   Another values
     *
     * @return array
     *   0 if the values are the same, 1 if the value is greater than comparison,
     *   and -1 if value is less than comparison.
     */
    private function integerCompareTo($value, $comparison) {
        if ($value == $comparison)
            return 0;
        if ($value > $comparison)
            return 1;
        else
            return -1;
    }

    /**
     * Evaluates child nodes of the current node.
     *
     * This function runs recursively and should only be called from a root node.
     *
     * @param array $current_node
     *   The node with children to be evaluated.
     * @param array $last_node
     *   The parent of the current_node. This should be NULL when calling with a
     *   root node.
     * @param array $target_string
     *   The useragent to evaluate with, as an ascii byte array.
     * @param int $target_length
     *   The position in the target_string to process from.
     * @param array $headers
     *   Header information from the data file.
     *
     * @return array
     *   The most suitable node for the given target_string and length.
     */
    private function evaluateNode(
        $current_node,
        $last_node,
        $target_string,
        $target_length,
        $headers) {

        $next_index = $this->getNextNodeIndex(
            $current_node,
            $target_string,
            $headers);

        if ($next_index > 0) {
            $next_node = $this->dataReader->readNode($next_index, $headers);
            if ($next_node['is_complete']) {
                $last_node = $next_node;
            }

            $next_node = $this->evaluateNode(
                $next_node,
                $last_node,
                $target_string,
                $target_length,
                $headers);

            if ($next_node == NULL) {
                return $last_node;
            }
            return $next_node;
        }
        return $last_node;
    }

    /**
     * Gets the node index to process next from the given node.
     *
     * @param array $node
     *   The node.
     * @param array $value
     *   The string value to evaluate the node against.
     * @param array $headers
     *   Header information from the data file.
     *
     * @return int
     *   The index of the next node. -1 if no suitable node is found.
     */
    public function getNextNodeIndex($node, $value, $headers) {
        $result = -1;
        $upper = $node['node_index_count'] - 1;
        if ($upper >= 0) {
            $lower = 0;
            $middle = $lower + (int)(($upper - $lower) / 2);

            $node_index = $this->dataReader->getNodeIndex($node, $middle, $headers);
            $node_value = $this->dataReader->getNodeIndexString(
                $node_index,
                $headers);

            $length = count($node_value);
            $start_index = $node['position'] - $length + 1;
            while ($lower <= $upper) {
                $middle = $lower + (int)(($upper - $lower) / 2);

                $node_index = $this->dataReader->getNodeIndex($node, $middle, $headers);
                $node_index_value = $this->dataReader->getNodeIndexString(
                    $node_index,
                    $headers);

                // Increase the number of strings checked.
                if ($node_index['is_string'])
                    $this->debugInfo['string_read']++;

                $this->debugInfo['nodes_evaluated']++;

                $root_node = $this->dataReader->getNodesRootNode($node['offset'], $headers);
                $node_index_value_length = $root_node['position'] - $node['position'];

                $comparison_result = $this->valueCompare(
                    $node_index_value,
                    $value,
                    $start_index);

                if ($comparison_result == 0) {
                    $result = abs($node_index['related_node_index']);
                    break;
                }
                else if ($comparison_result > 0)
                    $upper = $middle - 1;
                else
                    $lower = $middle + 1;
            }
        }
        return $result;
    }


    /**
     * Compares a two byte array strings.
     *
     * @param array $characters
     *   Characters to compare
     * @param array $value
     *   A value to compare against
     * @param int $start_index
     *   Start position.
     *
     * @return int
     *   The difference between the values. 0 means the strings are identical.
     */
    function valueCompare($characters, $value, $start_index) {
        $char_count = count($characters);
        for ($i = $char_count - 1, $v = $start_index + $char_count - 1; $i >= 0; $i--, $v--) {
            $difference = $characters[$i] - $value[$v];
            if ($difference != 0)
                return $difference;
        }
        return 0;
    }


}



