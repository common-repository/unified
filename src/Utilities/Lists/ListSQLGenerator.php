<?php

/**
 *  List SQL generator
 */

namespace Unified\Utilities\Lists;

class ListSQLGenerator
{
    // SQL parts
    public string $select = "";
    public string $joins = "";
    public string $where = " WHERE 1=1 ";
    public string $group_by = "";
    public string $having = "";
    public string $order_by = "";
    public string $limit = "";
    // Lookups
    private array $tables_joined = [];

    /**
     *  Constructor
     */
    public function __construct()
    {
    }

    /**
     *  Put the SQL parts together and return it
     */
    public function getSQL()
    {
        return $this->select . $this->joins . $this->where . $this->group_by . $this->having . $this->order_by . $this->limit;
    }

    /**
     *  Add SQL part
     */
    public function setSelect(string $select)
    {
        $this->select = " " . $select . " ";
    }

    /**
     * Add join table
     * @param string $type Could be "INNER JOIN", "LEFT JOIN" or another join type
     */
    public function addJoin(string $type, string $table_name, string $as, string $on): bool
    {
        $type = strtoupper($type);
        $table_name = strtolower($table_name);
        $this->tables_joined[$table_name] = $as;
        $join_string = strtoupper($type) . ' ' . $table_name . ' AS ' . $as . ' ON ' . $on;
        $this->joins .= " " . $join_string . " ";

        return true;
    }

    /**
     * Check if it has joined a table
     * @return bool True if joined already or false if not
     */
    public function hasJoin(string $table_name): bool
    {
        $table_name = strtolower($table_name);
        if (isset($this->tables_joined[$table_name])) {
            return true;
        }
        return false;
    }

    /**
     * Check if it has joined a table
     * @return string Joined "as" - Empty if not present, should be checked by hasJoin() first
     */
    public function getJoinedAs(string $table_name): string
    {
        $table_name = strtolower($table_name);
        return $this->tables_joined[$table_name] ?? '';
    }

    /**
     *  Add where part
     */
    public function addWherePart(string $where_part)
    {
        $this->where .= " " . $where_part . " ";
    }

    /**
     *  Set order by
     */
    public function setOrderBy(string $order_by)
    {
        $this->order_by = " " . $order_by . " ";
    }

    /**
     *  Set limit part
     */
    public function setLimitPart(string $limit_part)
    {
        $this->limit = " " . $limit_part . " ";
    }

    /**
     *  Set group by
     */
    public function setGroupBy(string $group_by)
    {
        $this->group_by = " " . $group_by . " ";
    }

    /**
     *  Set having
     */
    public function setHaving(string $having)
    {
        $this->having = " " . $having . " ";
    }

    /**
     *  Get first column from SQL
     */
    public function getSingleColumnResultData()
    {
        $sql = $this->getSQL();
        global $wpdb;
        $result = $wpdb->get_col($sql);
        return $result;
    }
}
