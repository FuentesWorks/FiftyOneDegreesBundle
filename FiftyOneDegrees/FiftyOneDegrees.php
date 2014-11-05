<?php

namespace FuentesWorks\FiftyOneDegreesBundle\FiftyOneDegrees;

require_once('../Library/51Degrees_data_reader.php');

class FiftyOneDegrees
{

    public function __construct()
    {
        // Configure 51Degrees operation
        global $_fiftyone_degrees_data_file_path;
        $_fiftyone_degrees_data_file_path = dirname(__FILE__) . '\51Degrees-Ultimate.dat';
    }

    public function getDeviceData($useragent)
    {
        return fiftyone_degrees_get_device_data($useragent);
    }

}