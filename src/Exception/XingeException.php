<?php

namespace XingeUnify\Exception;

/**
 * Class TmtException for API
 */
class XingeException extends \Exception
{
    protected $httpCode;
    protected $errorData = array();

    /**
     * @param $http_status_code
     * HTTP status code message as predefined.
     * @param $error
     * A single error code.
     * @param $error_description
     * (optional) A human-readable text providing additional information,
     * used to assist in the understanding and resolution of the error
     * occurred.
     */

    public function __construct($error, $error_description = NULL, $http_status_code = 406)
    {
        parent::__construct($error);
        $this->httpCode = $http_status_code;
        $this->errorData['error'] = $error;
        $this->errorData['error_description'] = $error_description ? $error_description : NULL;
    }

    /**
     * @return string 
     */
    public function getDescription() {
        return $this->errorData['error_description'];
    }

    /**
     * @return string
     */
    public function getforceDescription()
    {
        return $this->errorData['error_description'] ? $this->errorData['error_description'] : $this->errorData['error'];
    }

    /**
     * @return string 
     */
    public function getHttpCode() {
        return $this->httpCode;
    }
}