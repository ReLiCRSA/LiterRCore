<?php

namespace App\Helpers;

/**
 * Class Validation
 * @package App\Helpers
 */
class Validation
{
    /** @var array error bag */
    private $errorBag = [];

    /** @var \PDO DB Connection for lookups */
    private $db;

    /**
     * Validation constructor.
     */
    public function __construct()
    {
        global $dbcon;
        $this->db = $dbcon;
    }

    /**
     * Run the validation rules
     *
     * @param $rules
     * @param $inputs
     * @return $this
     */
    public function validate($rules, $inputs)
    {
        foreach ($rules as $variable => $ruleSet) {
            $ruleSets = explode("|", $ruleSet);
            array_map(function ($rule) use ($variable, $inputs) {
                $options = explode(":", $rule);
                $rule = $options[0];
                $options = empty($options[1]) ? '' : $options[1];
                switch ($rule) {
                    case 'required':
                        if (empty($inputs[$variable])) {
                            $this->errorBag[$variable][] = ucwords(strtolower($variable))." is required.";
                        }
                        break;
                    case 'email':
                        if (!empty($inputs[$variable])) {
                            if (!filter_var($inputs[$variable], FILTER_VALIDATE_EMAIL)) {
                                $this->errorBag[$variable][] = "Invalid email format";
                            }
                        }
                        break;
                    case 'string':
                        if (!empty($inputs[$variable])) {
                            if (!preg_match("/^[a-zA-Z ]*$/", $inputs[$variable])) {
                                $this->errorBag[$variable][] = ucwords(strtolower($variable))." must be a valid string.";
                            }
                        }
                        break;
                    case 'numeric':
                        if (!empty($inputs[$variable])) {
                            if (!preg_match("/^[0-9]*$/", $inputs[$variable])) {
                                $this->errorBag[$variable][] = ucwords(strtolower($variable))." must be a valid number.";
                            }
                        }
                        break;
                    case 'alphanum':
                        if (!empty($inputs[$variable])) {
                            if (!preg_match("/^[0-9a-zA-Z ]*$/", $inputs[$variable])) {
                                $this->errorBag[$variable][] = ucwords(strtolower($variable))." must be a valid string.";
                            }
                        }
                        break;
                    case 'unique':
                        if (!empty($inputs[$variable])) {
                            $details = explode(",", $options);
                            if (!$this->checkUnique($details[0], $details[1], $inputs[$variable])) {
                                $this->errorBag[$variable][] = ucwords(strtolower($variable))." already used.";
                            };
                        }
                        break;
                    case 'equals':
                        if (!empty($inputs[$variable])) {
                            if ($inputs[$variable] !== $inputs[$options]) {
                                $this->errorBag[$variable][] = ucwords(strtolower($variable))." and ".$options." needs to match.";
                            }
                        }
                        break;
                    case 'min':
                        if (!empty($inputs[$variable])) {
                            if (strlen($inputs[$variable]) < $options) {
                                $this->errorBag[$variable][] = ucwords(strtolower($variable))." needs a minimum length of ".$options.".";
                            }
                        }
                        break;
                    case 'exists':
                        if (!empty($inputs[$variable])) {
                            $details = explode(",", $options);
                            if (!$this->checkExist($details[0], $details[1], $inputs[$variable])) {
                                $this->errorBag[$variable][] = ucwords(strtolower($variable))." does not exist.";
                            };
                        }
                        break;
                }
            }, $ruleSets);
        }
        return $this;
    }

    /**
     * Check is the value is unique in the table
     *
     * @param $table
     * @param $column
     * @param $value
     * @return bool
     */
    public function checkUnique($table, $column, $value)
    {
        $sql = "SELECT COUNT(".$column.") AS count FROM ".$table." WHERE ".$column." = :value";
        $statement = $this->db->prepare($sql);
        $statement->execute(['value' => $value]);
        $result = $statement->fetch();
        if ($result['count'] == 0) {
            return true;
        };
        return false;
    }

    /**
     * Check if the value exists in the table
     *
     * @param $table
     * @param $column
     * @param $value
     * @return bool
     */
    public function checkExist($table, $column, $value)
    {
        $sql = "SELECT COUNT(".$column.") AS count FROM ".$table." WHERE ".$column." = :value";
        $statement = $this->db->prepare($sql);
        $statement->execute(['value' => $value]);
        $result = $statement->fetch();
        if ($result['count'] >= 1) {
            return true;
        };
        return false;
    }

    /**
     * Did the validations pass
     *
     * @return bool
     */
    public function passed()
    {
        if (count($this->errorBag) == 0) {
            return true;
        }
        return false;
    }

    /**
     * Get the error bag
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errorBag;
    }
}
