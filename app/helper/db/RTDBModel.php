<?php

/**
 * Description of RTDBModel
 * Base class for any Database Model like Media, Album etc.
 * 
 * @author faishal
 */
class RTDBModel {

	/**
	 *
	 * @var type 
	 * 
	 * $table_name - database table linked to the model.
	 *				All the queries will be fired on that table or with the join in this table.
	 * $meta_table_name - meta table in database for the model.
	 * $per_page - number of rows per page to be displayed
	 */
    public $table_name;
    public $meta_table_name;
    public $per_page;

    /**
     *
     * @param string $table_name Table name for model
     * @param boolean $withprefix Set true if $tablename is with prefix otherwise it will prepend wordpress prefix with "rt_"
     */
    function __construct($table_name, $withprefix = false, $per_page = 10) {
        $this->set_table_name($table_name, $withprefix);
        $this->set_per_page($per_page);
    }

    /**
     *
     * @global type $wpdb
     * @param string $table_name
     * @param type $withprefix
     */
    public function set_table_name($table_name, $withprefix = false) {
        global $wpdb;
        if (!$withprefix) {
            $table_name = $wpdb->prefix . "rt_" . $table_name;
        }
        $this->table_name = $table_name;
        $this->meta_table_name = $table_name . "_meta";
    }

    /**
     * set number of rows per page for pagination
     * @param type $per_page
     */
    public function set_per_page($per_page) {
        $this->per_page = $per_page;
    }

    /**
     * Magic Method for getting DB rows by particular column.
	 * E.g., get_by_<columnName>(params)
     * @global type $wpdb
     * @param type $name - Added get_by_<coulmname>(value,pagging=true,page_no=1)
     * @param type $arguments
     * @return type result array
     */
    function __call($name, $arguments) {
        $column_name = str_replace("get_by_", "", strtolower($name));
        $paging = false;
        $page = 1;
        if ($arguments && !empty($arguments)) {
            if (!isset($arguments[1])) {
                $paging = true;
            } else {
                $paging = $arguments[1];
            }

            if (!isset($arguments[2])) {
                $page = 1;
            } else {
                $page = $arguments[2];
            }

            $this->per_page = apply_filters("rt_db_model_per_page", $this->per_page, $this->table_name);
            $return_array = Array();
            $return_array["result"] = false;
            global $wpdb;
            $return_array["total"] = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . $this->table_name . " WHERE {$column_name} = %s", $arguments[0])));
            if ($return_array["total"] > 0) {
                $other = "";
                if ($paging) {
                    $offset = ($page - 1) * $this->per_page;
                    if ($offset <= $return_array["total"]) {
                        $other = " LIMIT " . $offset . "," . $this->per_page;
					}else{
						return false;
					}
                }
                //echo $wpdb->prepare("SELECT * FROM " . $this->table_name . " WHERE {$column_name} = %s {$other}", $arguments[0]);
                $return_array["result"] = $wpdb->get_results($wpdb->prepare("SELECT * FROM " . $this->table_name . " WHERE {$column_name} = %s {$other}", $arguments[0]), ARRAY_A);
            }
            return $return_array;
        } else {
            return false;
        }
    }

    /**
     *
     * @global type $wpdb
     * @param type $row
     * @return type
     */
    function insert($row) {
        global $wpdb;
        $wpdb->insert($this->table_name, $row);
        return $wpdb->insert_id;
    }

    /**
     *
     * @global type $wpdb
     * @param type $data
     * @param type $where
     */
    function update($data, $where) {
        global $wpdb;
        return $wpdb->update($this->table_name, $data, $where);
    }

    /**
     * Get all the rows according to the columns set in $columns parameter.
	 * offset and rows per page can also be passed for pagination.
     * @global type $wpdb
     * @param type $columns
     * @return type
     */
    function get($columns, $offset=false, $per_page=false, $order_by= 'media_id desc') {
        $select = "SELECT * FROM {$this->table_name}";
        $join = "" ;
        $where = " where 2=2 " ;
        $temp = 65;
        foreach ($columns as $colname => $colvalue) {
            if(strtolower($colname) =="meta_query"){
                foreach($colvalue as $meta_query){
                    if(!isset($meta_query["compare"])){
                        $meta_query["compare"] = "=";
                    }
                    $tbl_alias = chr($temp++);
                    $join .= " LEFT JOIN {$this->meta_table_name} {$tbl_alias} ON {$this->table_name}.media_id = {$tbl_alias}.media_id ";
                    $where .= " AND  ({$tbl_alias}.meta_key = '{$meta_query["key"]}' and  {$tbl_alias}.meta_value  {$meta_query["compare"]}  '{$meta_query["value"]}' ) ";
                }
            }else{
                $where .= " AND {$this->table_name}.{$colname} = '{$colvalue}'";
            }
        }
        $sql = $select . $join . $where ;

		$sql .= " ORDER BY {$this->table_name}.$order_by";

		if(is_integer($offset) && is_integer($per_page)) {
			$sql .= ' LIMIT ' . $offset . ',' . $per_page;
		}
        global $wpdb;
        return $wpdb->get_results($sql);
    }

	/**
	 * 
	 * @global type $wpdb
	 * @param type $media_id
	 * @return type
	 */
    function delete($media_id) {
        global $wpdb;
        return $wpdb->delete($this->table_name, array('media_id' => $media_id));
    }

}