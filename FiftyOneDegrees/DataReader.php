<?php

namespace FuentesWorks\FiftyOneDegreesBundle\FiftyOneDegrees;

class DataReader
{
    /*
     * The data file full system path
     */
    private $dataFilePath;

    /**
     * The resource file handle where data is read from
     * @var resource $dataFile
     */
    private $dataFile;

    /**
     * The data file headers
     * @var array $headers
     */
    private $headers;

    /**
     * Cache array
     * @var array $cache
     */
    private $cache;

    /**
     * On/Off switch for cache
     * @var boolean $cacheEnabled
     */
    private $cacheEnabled = false;

    /**
     * @param string $dataFilePath
     * @throws \RuntimeException
     */
    public function __construct($dataFilePath)
    {
        $this->dataFilePath = $dataFilePath;

        if(!file_exists($dataFilePath) || !is_readable($dataFilePath))
        {
            throw new \RuntimeException("Could not open the data file.");
        }

        $this->dataFile = fopen($this->dataFilePath, 'rb');
        $this->loadHeaders();
    }

    /**
     * Sets a pointer to the data file at the given file position.
     *
     * @param int $offset
     *   The position to set the file pointer to. Defaults to 0.
     * @throws \RuntimeException
     */
    private function setDataOffset($offset = 0) {
        if ($this->dataFile === false) {
            //die('A 51Degrees data file has not been set.');
            throw new \RuntimeException('A 51Degrees data file has not been set.');
        }

        /* OLD METHOD
        if ($offset >= 0) {
            fseek($this->dataFile, $offset);
        }
        return $this->dataFile;
        */

        fseek($this->dataFile, $offset);
    }

    /**
     * Enable the cache array
     *
     * @param boolean $enable
     */
    public function enableCache($enable)
    {
        if ($enable)
        {
            $this->cacheEnabled = true;
            $this->cache = array();
        } else {
            $this->cacheEnabled = false;
        }
    }

    /**
     * Returns a value from cache.
     *
     * This function can be modified for
     * massive speed increase if PHP plugins allowing persistent caching
     * are installed.
     *
     * @param string $key
     *   The key of the cached item.
     *
     * @return mixed
     *   The value from cache, or FALSE of the key was not present.
     */
    private function getFromCache($key)
    {
        if($this->cacheEnabled)
        {
            if(isset($this->cache[$key]))
            {
                return $this->cache[$key];
            }
        }
        return false;
    }

    /**
     * Saves a value to cache with the given key.
     *
     * If the key already exists the old value is overwritten.
     *
     * @param string $key
     *   The key to save the value with.
     * @param mixed $value
     *   The value to save.
     */
    private function saveToCache($key, $value) {
        if ($this->cacheEnabled) {
            $this->cache[$key] = $value;
        }
    }

    /**
     * Reads the headers of the data file to be used throughout the detection.
     *
     * @return array
     *   The headers of the data file.
     * @throws \RuntimeException
     */
    public function loadHeaders()
    {
        $this->headers['data_file_path'] = $this->dataFilePath;

        $this->setDataOffset(0);
        $this->headers['info'] = $this->getDataInfo();

        if (($this->headers['info']['major_version'] === 3 && $this->headers['info']['minor_version'] === 0 &&
                $this->headers['info']['build_version'] === 4) == FALSE) {
            throw new \RuntimeException('An incompatible data file has been supplied. Ensure the latest 51Degrees data and api are being used.');
        }

        $this->headers['ascii_strings_offset'] = FileReader::readInt($this->dataFile);
        $this->headers['ascii_strings_length'] = FileReader::readInt($this->dataFile);
        $this->headers['ascii_strings_count'] = FileReader::readInt($this->dataFile);

        $this->headers['component_offset'] = FileReader::readInt($this->dataFile);
        $this->headers['component_length'] = FileReader::readInt($this->dataFile);
        $this->headers['component_count'] = FileReader::readInt($this->dataFile);

        $this->headers['map_offset'] = FileReader::readInt($this->dataFile);
        $this->headers['map_length'] = FileReader::readInt($this->dataFile);
        $this->headers['map_count'] = FileReader::readInt($this->dataFile);

        $this->headers['property_offset'] = FileReader::readInt($this->dataFile);
        $this->headers['property_length'] = FileReader::readInt($this->dataFile);
        $this->headers['property_count'] = FileReader::readInt($this->dataFile);

        $this->headers['values_offset'] = FileReader::readInt($this->dataFile);
        $this->headers['values_length'] = FileReader::readInt($this->dataFile);
        $this->headers['values_count'] = FileReader::readInt($this->dataFile);

        $this->headers['profile_offset'] = FileReader::readInt($this->dataFile);
        $this->headers['profile_length'] = FileReader::readInt($this->dataFile);
        $this->headers['profile_count'] = FileReader::readInt($this->dataFile);

        $this->headers['signatures_offset'] = FileReader::readInt($this->dataFile);
        $this->headers['signatures_length'] = FileReader::readInt($this->dataFile);
        $this->headers['signatures_count'] = FileReader::readInt($this->dataFile);

        $this->headers['node_offset'] = FileReader::readInt($this->dataFile);
        $this->headers['node_length'] = FileReader::readInt($this->dataFile);
        $this->headers['node_count'] = FileReader::readInt($this->dataFile);

        $this->headers['root_node_offset'] = FileReader::readInt($this->dataFile);
        $this->headers['root_node_length'] = FileReader::readInt($this->dataFile);
        $this->headers['root_node_count'] = FileReader::readInt($this->dataFile);

        $this->headers['profile_offsets_offset'] = FileReader::readInt($this->dataFile);
        $this->headers['profile_offsets_length'] = FileReader::readInt($this->dataFile);
        $this->headers['profile_offsets_count'] = FileReader::readInt($this->dataFile);
    }


    public function getHeaders() {
        return $this->headers;
    }

    /**
     * Gets data information from the data file.
     *
     * @return array
     *   Returns data information.
     */
    public function getDataInfo() {
        $info = array();
        $info['major_version'] = FileReader::readInt($this->dataFile);
        $info['minor_version'] = FileReader::readInt($this->dataFile);;
        $info['build_version'] = FileReader::readInt($this->dataFile);
        $info['revision_version'] = FileReader::readInt($this->dataFile);

        $info['version'] = "{$info['major_version']}.{$info['minor_version']}.{$info['build_version']}.{$info['revision_version']}";

        $info['licence_id'] = array();
        for ($i = 0; $i < 16; $i++) {
            $info['licence_id'][] = FileReader::readByte($this->dataFile);
        }
        $info['copyright_offset'] = FileReader::readInt($this->dataFile);
        $info['age'] = FileReader::readShort($this->dataFile);
        $info['min_ua_count'] = FileReader::readInt($this->dataFile);
        $info['data_set_name_offset'] = FileReader::readInt($this->dataFile);
        $info['format_version_offset'] = FileReader::readInt($this->dataFile);
        $info['published_year'] = FileReader::readShort($this->dataFile);
        $info['published_month'] = FileReader::readByte($this->dataFile);
        $info['published_day'] = FileReader::readByte($this->dataFile);
        $info['next_update_year'] = FileReader::readShort($this->dataFile);
        $info['next_update_month'] = FileReader::readByte($this->dataFile);
        $info['next_update_day'] = FileReader::readByte($this->dataFile);
        $info['device_combinations'] = FileReader::readInt($this->dataFile);
        $info['max_ua_length'] = FileReader::readShort($this->dataFile);
        $info['min_ua_length'] = FileReader::readShort($this->dataFile);
        $info['lowest_character'] = FileReader::readByte($this->dataFile);
        $info['highest_character'] = FileReader::readByte($this->dataFile);
        $info['maximum_patterns'] = FileReader::readInt($this->dataFile);
        $info['signature_profiles_count'] = FileReader::readInt($this->dataFile);
        $info['signature_nodes_count'] = FileReader::readInt($this->dataFile);
        $info['max_values_count'] = FileReader::readShort($this->dataFile);
        $info['max_csv_length'] = FileReader::readInt($this->dataFile);
        $info['max_json_length'] = FileReader::readInt($this->dataFile);
        $info['max_xml_length'] = FileReader::readInt($this->dataFile);
        $info['max_signatures_closest'] = FileReader::readInt($this->dataFile);
        return $info;
    }

    /**
     * Gets a profile from the given offset.
     *
     * @param int $offset
     *   The position to look in the data file.
     *
     * @return array
     *   The profile.
     */
    public function readProfile($offset) {
        $this->setDataOffset($offset + $this->headers['profile_offset']);

        $profile = array();

        $profile['component_id'] = FileReader::readByte($this->dataFile);
        $profile['unique_id'] = FileReader::readInt($this->dataFile);
        $profile['profile_value_count'] = FileReader::readInt($this->dataFile);
        $profile['signature_count'] = FileReader::readInt($this->dataFile);
        $profile['profile_values'] = array();
        for ($i = 0; $i < $profile['profile_value_count']; $i++) {
            $profile['profile_values'][] = FileReader::readInt($this->dataFile);
        }

        return $profile;
    }

    /**
     * Gets a profile for the given id.
     *
     * @param int $profile_id
     * @param int $profile_id
     *   The profile id.
     *
     * @return array
     *   The profile, or NULL if no profile was found
     */
    public function getProfileFromId($profile_id) {
        $lower = 0;
        $upper = $this->headers['profile_offsets_count'] - 1;

        // unused variable, it's overwritten immediately
        // $middle = 0;

        while ($lower <= $upper) {
            $middle = $lower + (int) (($upper - $lower) / 2);
            $profile_offset = $this->getProfileOffsetIdFromIndex($middle);
            if ($profile_offset['profile_id'] == $profile_id)
                return $this->readProfile(
                    $profile_offset['offset'] - $this->headers['profile_offset']);
            elseif ($profile_offset['profile_id'] > $profile_id)
                $upper = $middle - 1;
            else
                $lower = $middle + 1;
        }
        return NULL;
    }

    /**
     * Gets the file offset to a profile with the given index.
     *
     * @param int $index
     *   The index of the profile
     *
     * @return array
     *   The file offset, or an empty array if no offset was found.
     */
    public function getProfileOffsetIdFromIndex($index) {
        $offset = $this->headers['profile_offsets_offset'] + ($index * 8);
        $this->setDataOffset($offset);

        $profile_offset = array();
        $profile_offset['profile_id'] = FileReader::readInt($this->dataFile);
        $profile_offset['offset'] = FileReader::readInt($this->dataFile) + $this->headers['profile_offset'];

        return $profile_offset;
    }

    /**
     * Returns the property value with the given index.
     *
     * @param int $index
     *   The index of the property value.
     *
     * @return array
     *   The property value.
     */
    public function readPropertyValue($index) {
        $offset = $this->headers['values_offset'] + ($index * 14);
        $this->setDataOffset($offset);

        $property_value = array();
        $property_value['index'] = $index;
        $property_value['property_index'] = FileReader::readShort($this->dataFile);
        $property_value['value_offset'] = FileReader::readInt($this->dataFile);
        $property_value['description_offset'] = FileReader::readInt($this->dataFile);
        $property_value['url_offset'] = FileReader::readInt($this->dataFile);

        $property_value['value'] = $this->readAscii($property_value['value_offset']);
        return $property_value;
    }

    /**
     * Returns the property with the given index.
     *
     * @param int $index
     *   The index of the property.
     *
     * @return array
     *   The property.
     */
    public function readProperty($index) {
        $cache = $this->getFromCache('property:' . $index);
        if ($cache !== false) {
            return $cache;
        }

        $offset = $this->headers['property_offset'] + ($index * 44);
        $this->setDataOffset($offset);

        $property = array();
        $property['index'] = $index;
        $property['com_index'] = FileReader::readByte($this->dataFile);
        $property['display_order'] = FileReader::readByte($this->dataFile);
        $property['mandatory'] = FileReader::readBool($this->dataFile);
        $property['list'] = FileReader::readBool($this->dataFile);
        $property['export_values'] = FileReader::readBool($this->dataFile);
        $property['is_obsolete'] = FileReader::readBool($this->dataFile);
        $property['show'] = FileReader::readBool($this->dataFile);
        $property['value_type_id'] = FileReader::readByte($this->dataFile);
        $property['default_prop_index'] = FileReader::readInt($this->dataFile);
        $property['name_offset'] = FileReader::readInt($this->dataFile);
        $property['description_offset'] = FileReader::readInt($this->dataFile);
        $property['category_offset'] = FileReader::readInt($this->dataFile);
        $property['url_offset'] = FileReader::readInt($this->dataFile);
        $property['first_value_index'] = FileReader::readInt($this->dataFile);
        $property['last_value_index'] = FileReader::readInt($this->dataFile);
        $property['map_count'] = FileReader::readInt($this->dataFile);
        $property['first_map_index'] = FileReader::readInt($this->dataFile);

        $property['name'] = $this->readAscii($property['name_offset']);
        $this->saveToCache('property:' . $index, $property);

        return $property;
    }

    /**
     * Returns the root nodes from the data file.
     *
     * @return array
     *   Array of integers containing file offsets to the root nodes.
     */
    public function readRootNodeOffsets() {
        $root_char_nodes = array();
        $this->setDataOffset($this->headers['root_node_offset']);
        for ($i = 0; $i < $this->headers['root_node_count']; $i++) {
            $root_char_nodes[] = FileReader::readInt($this->dataFile);
        }
        return $root_char_nodes;
    }

    /**
     * Reads a node from the given position in the data file.
     *
     * @param int $offset
     *   The position to get the node in the data file.
     *
     * @return array
     *   The node.
     */
    public function readNode($offset)
    {
        // This is not used anywhere else
        //global $nodes_checked;
        //$nodes_checked++;

        $cache = $this->getFromCache('node:' . $offset);
        if ($cache !== false) {
            return $cache;
        }

        $this->setDataOffset($this->headers['node_offset'] + $offset);

        $node = array();
        $node['offset'] = $offset;
        $node['position'] = FileReader::readShort($this->dataFile);
        $node['next_char_position'] = FileReader::readShort($this->dataFile);
        $node['is_complete'] = $node['next_char_position'] != -32768;
        $node['parent_offset'] = FileReader::readInt($this->dataFile);
        $node['character_offset'] = FileReader::readInt($this->dataFile);
        $node['node_index_count'] = FileReader::readShort($this->dataFile);
        $node['numeric_children_count'] = FileReader::readShort($this->dataFile);
        $node['node_signature_count'] = FileReader::readInt($this->dataFile);

        $node['node_indexes_offset'] = ftell($this->dataFile);
        $node['node_numeric_children_offset']
            = $node['node_indexes_offset']
            + ($node['node_index_count'] * (1 + 4 + 4));

        $node['node_signature_offset']
            = $node['node_numeric_children_offset']
            + ($node['numeric_children_count'] * (2 + 4));

        $this->saveToCache('node:' . $offset, $node);
        return $node;
    }

    /**
     * Reads a node index from a given node with the specified index.
     *
     * @param array $node
     *   The node to return an index for.
     * @param int $index
     *   The position of the node index to return.
     *
     * @return array
     *   The node index.
     */
    public function getNodeIndex($node, $index) {
        $offset = $node['node_indexes_offset'] + ($index * (1 + 4 + 4));
        $this->setDataOffset($offset);

        return array(
            'is_string' =>
                FileReader::readBool($this->dataFile),
            'value' =>
                FileReader::readInt($this->dataFile),
            'related_node_index' =>
                FileReader::readInt($this->dataFile),
        );
    }

    /**
     * Gets the string value of the given node index.
     *
     * @param array $node_index
     *   The node index.
     *
     * @return array
     *   The string value of the node index as a byte array.
     */
    public function getNodeIndexString($node_index) {
        if ($node_index['is_string']) {
            $characters = $this->readAsciiArray(
                $node_index['value']);
        }
        else {
            $characters = array();
            $bytes = pack('L', $node_index['value']);
            for ($i = 0; $i < 4; $i++) {
                $o = ord($bytes[$i]);
                if ($o != 0)
                    $characters[] = $o;
                else
                    break;
            }
        }
        return $characters;
    }



    /**
     * Evaluates the value of a node index against another value.
     *
     * @param array $node_index
     *   The node index to be evaluated.
     * @param int $start_position
     *   the position in the string to evaluate from.
     * @param array $value
     *   The string to evaluate with.
     *
     * @return int
     *   The difference between the strings. 0 means they were identical.
     */
    public function nodeIndexStringCompare($node_index, $start_position, $value) {
        $characters = $this->getNodeIndexString($node_index);
        $end = count($characters) - 1;
        for ($i = $end, $v = $start_position + $end; $i >= 0; $i--, $v--) {
            $difference = $characters[$i] - $value[$v];
            if ($difference != 0)
                return $difference;
        }
        return 0;
    }

    /**
     * Reads a numeric node index from a given node with the specified index.
     *
     * @param array $node
     *   The node to return an index for.
     * @param int $index
     *   The position of the numeric node index to return.
     *
     * @return array
     *   The numeric node index.
     */
    public function getNumericNodeIndex($node, $index) {
        $offset = $node['node_numeric_children_offset'] + ($index * (2 + 4));
        $this->setDataOffset($offset);

        return array(
            'value' => FileReader::readShort($this->dataFile),
            'related_node_index' => FileReader::readInt($this->dataFile),
        );
    }

    /**
     * Reads all numeric node indexes for a given node.
     *
     * @param array $node
     *   The node to return an index for.
     *
     * @return array
     *   The numeric node indexes.
     */
    public function getNumericNodeIndexes($node) {
        $this->setDataOffset($node['node_numeric_children_offset']);

        $indexes = array();
        $bytes = fread($this->dataFile, 6 * $node['numeric_children_count']);
        $byte_count = strlen($bytes);
        for ($i = 0; $i < $byte_count; $i += 6) {
            $a = ord($bytes[$i]);
            $b = ord($bytes[$i + 1]);
            $c = ord($bytes[$i + 2]);
            $d = ord($bytes[$i + 3]);
            $e = ord($bytes[$i + 4]);
            $f = ord($bytes[$i + 5]);

            $value = $a + ($b << 8);
            $related = $c + ($d << 8) + ($e << 16) + ($f << 24);
            $indexes[] = array(
                'value' => $value,
                'related_node_offset' => $related);
        }

        // for ($i = 0; $i < $node['numeric_children_count']; $i++) {
        // $indexs[] =  array(
        // 'value' => fiftyone_degrees_read_short($_fiftyone_degrees_data_file),
        // 'related_node_offset' => fiftyone_degrees_read_int($_fiftyone_degrees_data_file),
        // );
        // }
        return $indexes;
    }

    /**
     * Gets the length of the node.
     *
     * @param array $node
     *   The node to get the length of.
     *
     * @return int
     *   The length of the node.
     */
    public function getNodeLength($node) {
        $root = $this->getNodesRootNode($node['offset']);
        return $root['position'] - $node['position'];
    }

    /**
     * Gets the root node of any given node.
     *
     * @param int $node_offset
     *   The node offset to find the root for.
     *
     * @return array
     *   The root node of the supplied node. If a root node is supplied then the
     *   same node is returned.
     */
    public function getNodesRootNode($node_offset) {
        $node = $this->readNode($node_offset);
        if ($node['parent_offset'] >= 0) {
            return $this->getNodesRootNode($node['parent_offset']);
        }
        else {
            return $node;
        }
    }

    /**
     * Fills the given node with signature information.
     *
     * @param array &$node
     *   The node to fill.
     */
    public function fillNodeSignatures(&$node) {
        $this->setDataOffset($node['node_signature_offset']);
        if (!isset($node['node_signatures'])) {
            $node['node_signatures'] = array();

            $amount = $node['node_signature_count'];
            $bytes = fread($this->dataFile, 4 * $amount);
            $byte_count = strlen($bytes);
            for ($i = 0; $i < $byte_count; $i += 4) {
                $node['node_signatures'][] = unpack('l', substr($bytes, $i, 4))[1];
            }


            // for ($node_sig_index = 0; $node_sig_index < $node['node_signature_count']; $node_sig_index++) {
            // $bytes = fread($_fiftyone_degrees_data_file, 4);
            // $value = unpack('l', $bytes);

            // $node['node_signatures'][] = $value[1];
            // //$node['node_signatures'][] = fiftyone_degrees_read_int($_fiftyone_degrees_data_file);
            // }
            $this->saveToCache('node:' . $node['offset'], $node);
        }
        return $node['node_signature_count'];
    }

    /**
     * Gets the characters this node represents.
     *
     * @param array $node
     *   The node to get characters for.
     *
     * @return array
     *   The node's characters as an ascii byte array.
     */
    public function getNodeCharacters($node) {
        if ($node['character_offset'] !== -1) {
            return $this->readAsciiArray($node['character_offset']);
        }
        return NULL;
    }

    /**
     * Returns a string as a byte array from the given position in the data file.
     *
     * @param int $offset
     *   The position in the data file to read the string from.
     *
     * @return array
     *   An ascii string as a byte array.
     */
    public function readAsciiArray($offset) {
        $cache = $this->getFromCache('ascii:' . $offset);
        if ($cache !== false) {
            return $cache;
        }

        $this->setDataOffset($this->headers['ascii_strings_offset'] + $offset);
        $length = FileReader::readShort($this->dataFile);
        if ($length == 0) {
            $ascii_array = '';
        }
        else {
            $raw_string = fread($this->dataFile, $length - 1);
            $ascii_array = array_merge(unpack('C*', $raw_string));
        }
        $this->saveToCache('ascii:' . $offset, $ascii_array);
        return $ascii_array;
    }

    /**
     * Returns an ascii string from the given position in the data file.
     *
     * @param int $offset
     *   The position in the data file to read the string from.
     *
     * @return array
     *   An ascii string.
     */
    public function readAscii($offset) {
        $cache = $this->getFromCache('ascii_s:' . $offset);
        if ($cache !== false) {
            return $cache;
        }

        $this->setDataOffset($this->headers['ascii_strings_offset'] + $offset);
        $length = FileReader::readShort($this->dataFile);
        if ($length == 0) {
            $ascii_string = '';
        }
        else {
            $ascii_string = fread($this->dataFile, $length - 1);
        }
        $this->saveToCache('ascii_s:' . $offset, $ascii_string);
        return $ascii_string;
    }

    /**
     * Returns a signature with the given index.
     *
     * @param int $index
     *   The index of the signature.
     *
     * @return array
     *   The signature.
     */
    public function readSignature($index) {
        $cache = $this->getFromCache('sig:' . $index);
        if ($cache !== false) {
            return $cache;
        }

        $signature = array();
        $signature['i'] = $index;
        $signature['file_offset'] = $this->headers['signatures_offset'] +
            (($this->headers['signatures_length'] / $this->headers['signatures_count']) * $index);

        $this->setDataOffset($signature['file_offset']);

        $signature['rank'] = FileReader::readInt($this->dataFile);

        $signature['profile_indexes'] = array();
        for ($i = 0; $i < $this->headers['info']['signature_profiles_count']; $i++) {
            $profile_index = FileReader::readInt($this->dataFile);
            if ($profile_index >= 0) {
                $signature['profile_indexes'][] = $profile_index;
            }
        }

        $signature['node_indexes'] = array();
        for ($i = 0; $i < $this->headers['info']['signature_nodes_count']; $i++) {
            $node_index = FileReader::readInt($this->dataFile);
            if ($node_index >= 0) {
                $signature['node_indexes'][] = $node_index;
            }
        }
        $this->saveToCache('sig:' . $index, $signature);
        return $signature;
    }

    /**
     * Gets the number of characters in the signature.
     *
     * @param array $signature
     *   The signature to get the length of.
     *
     * @return int
     *   The signature length
     */
    public function getSignatureLength($signature) {
        $last_node_index = $signature['node_indexes'][count($signature['node_indexes'])-1];
        $last_node = $this->readNode($last_node_index);
        $last_node_length = $this->getNodeLength($last_node);
        return $last_node['position'] + $last_node_length + 1;
    }

    /**
     * Builds a string representing the nodes in the given signature.
     *
     * @param array $signature
     *   The signature to find the string of.
     *
     * @return string
     *   The signature string.
     */
    public function getSignatureString($signature) {
        $bytes = array();
        $length = $this->getSignatureLength($signature);
        for($i = 0; $i < $length; $i++) {
            $bytes[$i] = ord('_');
        }
        foreach ($signature['node_indexes'] as $node_index) {
            $node = $this->readNode($node_index);
            $node_characters = $this->getNodeCharacters($node);
            $node_char_count = count($node_characters);
            for ($i = 0; $i < $node_char_count; $i++) {
                $bytes[$node['position'] + $i + 1] = $node_characters[$i];
            }
        }
        $string = '';
        foreach($bytes as $byte) {
            $string .= chr($byte);
        }
        return $string;
    }

    /**
     * Gets the copyright notice.
     *
     * @return string
     *   The copyright notice
     */
    public function getCopyrightNotice() {
        $notice = $this->readAscii($this->headers['info']['copyright_offset']);
        return $notice;
    }

    /**
     * Gets the data set name.
     *
     * @return string
     *   The data set name
     */
    public function getDataSetName() {
        $name = $this->readAscii($this->headers['info']['data_set_name_offset']);
        return $name;
    }


}