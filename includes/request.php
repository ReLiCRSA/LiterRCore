<?php

namespace Framework;

/**
 * Class request
 * @package Framework
 * @SuppressWarnings(PHPMD.Superglobals)
 */
class Request
{
    /**
     * Request constructor.
     */
    public function __construct()
    {
        $this->serverVars();
    }

    /**
     * Retrieve all the server vars and put into this object for use
     */
    public function serverVars()
    {
        foreach ($_SERVER as $key => $value) {
            $this->{$this->convertToCamel($key)} = $value;
        }
    }

    /**
     * Get only the required fields from the input
     *
     * @param array $inputsNeeded
     * @return array
     */
    public function getOnly($inputsNeeded = [])
    {
        $fullArray = $this->getBody();
        return array_intersect_key($fullArray, array_flip($inputsNeeded));
    }

    /**
     * Get the inputs from the GET and POST arrays
     * @return array
     */
    public function getBody()
    {
        if ($this->requestMethod === "GET") {
            $body = [];
            foreach ($_GET as $key => $value) {
                $body[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
            return $body;
        }
        if ($this->requestMethod == "POST") {
            $body = [];
            foreach ($_POST as $key => $value) {
                $body[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
            return $body;
        }
    }

    /**
     * Convert provided string into camel case
     *
     * @param $string
     * @return mixed|string
     */
    private function convertToCamel($string)
    {
        $result = strtolower($string);

        preg_match_all('/_[a-z]/', $result, $matches);
        foreach ($matches[0] as $match) {
            $c = str_replace('_', '', strtoupper($match));
            $result = str_replace($match, $c, $result);
        }
        return $result;
    }

    /**
     * Get a CSRF Token to use
     *
     * @param $page
     * @return string
     */
    public function getCSRF($page)
    {
        $csrfToken = base64_encode(openssl_random_pseudo_bytes(16));
        $_SESSION['csrf_'.$page.'_'.session_id()] = $csrfToken;
        return $csrfToken;
    }

    /**
     * Check the CSRF Token
     *
     * @param $page
     * @return bool
     *
     */
    public function checkCSRF($page)
    {
        $postArray = $this->getBody();
        $returnValue = false;
        if ($_SESSION['csrf_'.$page.'_'.session_id()] == $postArray['csrf']) {
            $returnValue = true;
        }
        return $returnValue;
    }
}
