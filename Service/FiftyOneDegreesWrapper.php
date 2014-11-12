<?php

namespace FuentesWorks\FiftyOneDegreesBundle\Service;

use FuentesWorks\FiftyOneDegreesBundle\FiftyOneDegrees\FiftyOneDegrees;

class FiftyOneDegreesWrapper extends FiftyOneDegrees
{
    public function __construct($data_file_path=null)
    {
        parent::__construct($data_file_path);
    }

}