<?php

namespace Framework;

/**
 * Class Model
 *
 * This will handle the DB requirements
 *
 * @package Framework
 */
abstract class Model implements \Countable
{
    /** @var string Primary Key */
    protected $primaryKey = 'id';

    /** @var string Table Name */
    protected $tableName;

    /** @var string Where clause storage */
    protected $whereClauses;

    /** @var array Where clause value store */
    protected $whereValues = [];

    /** @var bool User Soft Deletes */
    protected $useSoftDelete = true;

    /** @var string Soft Delete field to check */
    protected $softDeleteField = 'deleted_at';

    /** @var array Model Data */
    private $data = [];

    /** @var \PDO DB Connection */
    private $db;

    /** @var bool Fetched or new instance */
    private $fetched = false;

    /**
     * Model constructor.
     * @throws \ReflectionException
     */
    public function __construct()
    {
        global $dbcon;
        $this->db = $dbcon;
        if (empty($this->tableName)) {
            $this->tableName = strtolower((new \ReflectionClass($this))->getShortName())."s";
        }
    }

    /**
     * Get a data object out of the value store
     *
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->data[$name];
    }

    /**
     * Set the data value in the object
     *
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * Start the where clause off with a lookup
     *
     * @param $field
     * @param $operator
     * @param $value
     * @return $this
     */
    public function where($field, $operator, $value)
    {
        $this->whereValues = [];
        $whereCount = count($this->whereValues);
        $whereName = ":".$field.$whereCount;
        $this->whereValues[$whereName] = $value;
        $this->whereClauses .= "WHERE ".$field." ".$operator." ".$whereName;
        return $this;
    }

    /**
     * Add the and clause to the where lookup
     *
     * @param $field
     * @param $operator
     * @param $value
     * @return $this
     */
    public function whereAnd($field, $operator, $value)
    {
        $whereCount = count($this->whereValues);
        $whereName = ":".$field.$whereCount;
        $this->whereValues[$whereName] = $value;
        $this->whereClauses .= " AND ".$field." ".$operator." ".$whereName;
        return $this;
    }


    /**
     * Add the or clause to the where lookup
     *
     * @param $field
     * @param $operator
     * @param $value
     * @return $this
     */
    public function whereOr($field, $operator, $value)
    {
        $whereCount = count($this->whereValues);
        $whereName = ":".$field.$whereCount;
        $this->whereValues[$whereName] = $value;
        $this->whereClauses .= " OR ".$field." ".$operator." ".$whereName;
        $this->whereValues[] = $value;
        return $this;
    }

    /**
     * Get all the records for a specific lookup
     *
     * @param array $fields
     * @return array
     */
    public function get($fields = [])
    {
        $fieldSelect = "*";
        if (!empty($fields)) {
            $fieldSelect = implode($fields, ",");
        }

        $sql = "SELECT ".$fieldSelect." FROM ".$this->tableName;
        if (count($this->whereValues) > 0) {
            $sql .= " ".$this->whereClauses;
        }

        if ($this->useSoftDelete == true) {
            $softDelete = ' WHERE '.$this->softDeleteField.' IS NULL';
            if (count($this->whereValues) > 0) {
                $softDelete = ' AND '.$this->softDeleteField.' IS NULL';
            }
            $sql .= $softDelete;
        }
        $statement = $this->db->prepare($sql);

        $statement->execute($this->whereValues);
        $this->fetched = true;
        $results = [];
        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $this->data = $row;
            $results[] = clone($this);
        }
        return $results;
    }

    /**
     * Return the first item in the table
     *
     * @param array $fields
     * @return $this
     */
    public function first($fields = [])
    {
        $fieldSelect = "*";
        if (!empty($fields)) {
            $fieldSelect = implode($fields, ",");
        }

        $sql = "SELECT ".$fieldSelect." FROM ".$this->tableName;
        if (count($this->whereValues) > 0) {
            $sql .= " ".$this->whereClauses;
        }

        if ($this->useSoftDelete == true) {
            $softDelete = ' WHERE '.$this->softDeleteField.' IS NULL';
            if (count($this->whereValues) > 0) {
                $softDelete = ' AND '.$this->softDeleteField.' IS NULL';
            }
            $sql .= $softDelete;
        }

        $sql .= ' LIMIT 0,1';
        $statement = $this->db->prepare($sql);

        $statement->execute($this->whereValues);
        $this->fetched = true;
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        $this->data = $row;
        return $this;
    }

    /**
     * Save the model to the db
     */
    public function save()
    {
        if ($this->fetched) {
            $this->data['updated_at'] = date("Y-m-d H:i:s");
            $sql = "UPDATE ".$this->tableName." SET ";
            $updateArray = [];
            foreach ($this->data as $key => $value) {
                if ($key == $this->primaryKey) {
                    continue;
                }
                $updateArray[] = $key."=:".$key;
            }
            $updateSet = implode(",", $updateArray);
            $sql .= $updateSet;
            $sql .= " WHERE ".$this->primaryKey."=:".$this->primaryKey;
        } else {
            $this->data['created_at'] = date("Y-m-d H:i:s");
            $this->data['updated_at'] = date("Y-m-d H:i:s");
            $sql = "INSERT INTO ".$this->tableName;
            $insertKeys = [];
            $insertValues = [];
            foreach ($this->data as $key => $value) {
                if ($key == $this->primaryKey) {
                    continue;
                }
                $insertKeys[] = $key;
                $insertValues[] = ":".$key;
            }
            $insertKeys = implode(",", $insertKeys);
            $insertValues = implode(",", $insertValues);
            $sql .= " (".$insertKeys.")";
            $sql .= " VALUES (".$insertValues.")";
        }
        $statement = $this->db->prepare($sql);
        $statement->execute($this->data);
        if (!$this->fetched) {
            $theKey = $this->primaryKey;
            $this->$theKey = $this->db->lastInsertId();
            $this->refresh();
        }
    }

    /**
     * Refresh the current model and load all the fields after the insert
     */
    private function refresh()
    {
        $sql = "SELECT * FROM ".$this->tableName." WHERE ".$this->primaryKey." = ".$this->id;
        $statement = $this->db->prepare($sql);
        $statement->execute();
        $this->fetched = true;
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        $this->data = $row;
    }

    /**
     * Set the primary key and refresh the dataset
     *
     * @param $primaryKeyLookup
     * @return $this
     */
    public function find($primaryKeyLookup)
    {
        $this->id = $primaryKeyLookup;
        $this->refresh();
        return $this;
    }

    /**
     * Return the data array from the object
     *
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * Countable interface. We need to return 0 if the query failed
     *
     * @return array
     */
    public function count()
    {
        if ($this->data === false) {
            $this->data = [];
        }
        return count($this->data);
    }

    /**
     * Delete the item with either soft delete of hard delete
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function delete()
    {
        $sql = "DELETE FROM ".$this->tableName;
        $toUpdateArray[$this->primaryKey] = $this->id;
        if ($this->useSoftDelete) {
            $toUpdateArray['updated_at'] = date("Y-m-d H:i:s");
            $toUpdateArray[$this->softDeleteField] = date("Y-m-d H:i:s");
            $sql = "UPDATE ".$this->tableName." SET ";
            foreach ($toUpdateArray as $key => $value) {
                if ($key == $this->primaryKey) {
                    continue;
                }
                $updateArray[] = $key."=:".$key;
            }
            $updateSet = implode(",", $updateArray);
            $sql .= $updateSet;
        }
        $sql .= " WHERE ".$this->primaryKey."=:".$this->primaryKey;
        $statement = $this->db->prepare($sql);
        $statement->execute($toUpdateArray);
    }
}
