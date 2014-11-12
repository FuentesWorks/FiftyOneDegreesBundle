<?php

namespace FuentesWorks\FiftyOneDegreesBundle\FiftyOneDegrees;

class FiftyOneDegrees
{

    public function __construct($data_file_path)
    {
        // Configure 51Degrees operation
        global $_fiftyone_degrees_data_file_path;
        $_fiftyone_degrees_data_file_path = $data_file_path.'/51Degrees-PremiumV3_1.dat';
        require_once($data_file_path.'/51Degrees_data_reader.php');


    }

    public function getDeviceData($useragent)
    {
        return fiftyone_degrees_get_device_data($useragent);
    }

}