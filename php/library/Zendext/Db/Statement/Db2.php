<?php
require_once 'Zend/Db/Statement.php';

class Zendext_Db_Statement_Db2 extends Zend_Db_Statement {
    /**
     * Statement resource handle.
     */
    protected $_stmt = null;

    /**
     * Column names.
     */
    protected $_keys;
    const PROPERTY_NAME   = "name";
    const PROPERTY_TYPE = "type";
    const PROPERTY_LABEL = "label";
    const PROPERTY_INDEX = "index";


    /**
     * Fetched result values.
     */
    protected $_values;

    protected $_rowCount;

    protected $_resultSet;
    /**
     * Prepare a statement handle.
     *
     * @param string $sql
     * @return void
     * @throws Zend_Db_Statement_Db2_Exception
     */
    public function _prepare($sql)
    {
        //returned connection here is AS400JDBCConnection 
        $connection = $this->_adapter->getConnection();

        $this->_stmt = @$connection->prepareStatement($sql);       

        if (!$this->_stmt) {
            require_once 'Zendext/Db/Statement/Db2/Exception.php';
            throw new Zendext_Db_Statement_Db2_Exception('Unable to prepare the statement');
        }
    }

    /**
     * Binds a parameter to the specified variable name.
     *
     * @param mixed $parameter Name the parameter, either integer or string.
     * @param mixed $variable  Reference to PHP variable containing the value.
     * @param mixed $type      OPTIONAL Datatype of SQL parameter.
     * @param mixed $length    OPTIONAL Length of SQL parameter.
     * @param mixed $options   OPTIONAL Other options.
     * @return bool
     * @throws Zend_Db_Statement_Db2_Exception
     */
    public function _bindParam($parameter, &$variable, $type = null, $length = null, $options = null)
    {
        //TODO: leave this out for now .. will come back later.  Not sure when this is call. Put in a throw statement for tracking 
        /*
           if ($type === null) {
           $type = DB2_PARAM_IN;
           }

           if (isset($options['data-type'])) {
           $datatype = $options['data-type'];
           } else {
           $datatype = DB2_CHAR;
           }

           if (!db2_bind_param($this->_stmt, $position, "variable", $type, $datatype)) {

           require_once 'Zend/Db/Statement/Db2/Exception.php';
           throw new Zend_Db_Statement_Db2_Exception(
           db2_stmt_errormsg(),
           db2_stmt_error()
           );
           }

           return true;
         */
        require_once 'Zendext/Db/Statement/Db2/Exception.php';
        throw new Zendext_Db_Statement_Db2_Exception(__FUNCTION__ . '() is not implemented');       
    }

    /**
     * Closes the cursor, allowing the statement to be executed again.
     *
     * @return bool
     */
    public function closeCursor()
    {               
        if (!$this->_stmt) {
            return false;
        }

        $this->_stmt->close();
        $this->_stmt = null;
        return true;

    }


    /**
     * Returns the number of columns in the result set.
     * Returns null if the statement has no result set metadata.
     *
     * @return int The number of columns.
     */
    public function columnCount()
    {
        if (!$this->_stmt) {
            return false;
        }
        $resultSetMetadata = $this->_stmt->getMetaData();
        return java_values($resultSetMetadata->getColumnCount());
    }

    /**
     * Retrieves the error code, if any, associated with the last operation on
     * the statement handle.
     *
     * @return string error code.
     */
    public function errorCode()
    {
        if (!$this->_stmt) {
            return false;
        }
        $sqlWarning = $this->_stmt->getWarnings();

        $error = (string)$sqlWarning->getErrorCode();
        if ($error === '') {
            return false;
        }

        return $error;
    }

    /**
     * Retrieves an array of error information, if any, associated with the
     * last operation on the statement handle.
     *
     * @return array
     */
    public function errorInfo()
    {
        $error = $this->errorCode();
        if ($error === false){
            return false;
        }
        $sqlWarning = $this->_stmt->getWarnings();
        $message = (string)$sqlWarning->getMessage();
        /*
         * Return three-valued array like PDO.  But DB2 does not distinguish
         * between SQLCODE and native RDBMS error code, so repeat the SQLCODE.
         */
        return array(
                $error,
                $error,
                $message
                );
    }


    public function _bindParameter(array $params = null)
    {
        if ($params != null){
            $parmMetaData = $this->_stmt->getParameterMetaData();

            for ($i=1; $i<= java_values($parmMetaData->getParameterCount()); $i++){
                if (java_values($parmMetaData->getParameterMode($i)) == 1){//input
                    switch (java_values($parmMetaData->getParameterTypeName($i))) {
                        case 'CHAR':
                            $this->_stmt->setString($i, $params[$i-1]);         
                            break;
                        case 'DECIMAL':
                            $paramValue = new java("java.math.BigDecimal", 
                                    new java("java.lang.Double", $params[$i-1]));
                            $this->_stmt->setBigDecimal($i, $paramValue);
                            break;
                        case 'INTEGER':
                            $paramValue = new java("java.math.BigDecimal", 
                                    new java("java.lang.Double", $params[$i-1]));
                            $this->_stmt->setBigDecimal($i, $paramValue);
                            break;                          
                        case 'NUMERIC':
                            $paramValue = new java("java.math.BigDecimal", 
                                    new java("java.lang.Double", $params[$i-1]));
                            $this->_stmt->setBigDecimal($i, $paramValue);
                            break;                          
                        case 'BLOB':
                            $this->_stmt->setBytes($i, $params[$i-1]);                              
                            break;
                        default:
                            //throws error as not sure if this is evr use by us
                            require_once 'Zendext/Db/Statement/Db2/Exception.php';
                            throw new Zendext_Db_Statement_Db2_Exception(__FUNCTION__ . '() is not implemented for parameter type: ' . java_values($parmMetaData->getParameterTypeName($i)));                           
                            break;
                    }

                }
                else{
                    //throws error as not sure if this is evr use by us
                    require_once 'Zendext/Db/Statement/Db2/Exception.php';
                    throw new Zendext_Db_Statement_Db2_Exception(__FUNCTION__ . '() is not implemented for non-input parameter mode');                  
                }
            }           
        }

    }    

    /**
     * Executes a prepared statement.
     *
     * @param array $params OPTIONAL Values to bind to parameter placeholders.
     * @return bool
     * @throws Zend_Db_Statement_Db2_Exception
     */
    public function _execute(array $params = null)
    {

        if (!$this->_stmt) {
            return false;
        }
        //get the resultset metadata from the preparestatement.  The prepareStatement can be in 2 different types. One that returns a result set and the other doesn't
        $resultSetMetadata = $this->_stmt->getMetaData();

        if (java_is_null($resultSetMetadata)){
            $this->_bindParameter($params);
            //preparestatement is a SQL INSERT, UPDATE or DELETE statement or an SQL statement that returns nothing, such as a DDL statement.
            $this->_rowCount = java_values($this->_stmt->executeUpdate());      
            $this->_adapter->closeConnection();
        }
        else{
            //preparestatement is a sql that returns the ResultSet object
            $field_num = java_cast($resultSetMetadata->getColumnCount(),"integer");
            $this->_keys = array();             
            //in addition AS400JDBCResultSet starts the resultset with index=1
            //assigning the column name to the array

            if ($field_num) {
                for ($i = 1; $i <= $field_num; $i++) {
                    $colProperty = array();
                    $colProperty[self::PROPERTY_NAME] = (string)$resultSetMetadata->getColumnName($i);
                    $colProperty[self::PROPERTY_TYPE] = (string)$resultSetMetadata->getColumnClassName($i);
                    $colProperty[self::PROPERTY_LABEL] = (string)$resultSetMetadata->getColumnLabel($i);
                    $colProperty[self::PROPERTY_INDEX] = $i - 1;
                    $this->_keys[] = $colProperty;
                }

            }   
            $this->_resultSet= $this->_stmt->executeQuery();    
        }

        return true;

    }
    //resultSet is of type AS400JDBCResultSet
    //this method ensure we call the correct method and keep the data integrity. 
    //Right now this is a trial in term of data integrity
    public function getValue($resultSet, $colName, $colType){
        //echo $colType;
        switch (strtolower($colType)) {
            case "java.lang.string":            
                return trim(java_values($resultSet->getString($colName)));
                break;
            case "java.math.bigdecimal":            
                return java_values($resultSet->getBigDecimal($colName));
                break;
            case "java.lang.integer":
                return java_values($resultSet->getInt($colName));
                break;
            case "com.ibm.as400.access.as400jdbcbloblocator":

                $blob = $resultSet->getBlob($colName);              
                $start = new java("java.lang.Long", 1);
                $holdingLength = new java("java.lang.Long", $blob->length());

                return java_values($blob->getBytes($start, $holdingLength->intValue()));
                break;              
            default:
                require_once 'Zendext/Db/Statement/Db2/Exception.php';
                throw new Zendext_Db_Statement_Db2_Exception(__FUNCTION__ . '(): Data type: ' . $colType .  ' is not implemented');                 
                break;  
        }       
    }



    //utilize existing resultset to retrieve a row of record
    private function fetch_assoc(){
        $row = array();
        foreach ($this->_keys as $keys){
            //add each column value from the result set to make a row   
            switch ($this->_adapter->getResultsetCase()){
                case Zend_Db::CASE_LOWER:
                    $key = strtolower($keys[self::PROPERTY_NAME]);
                    break;
                case Zend_Db::CASE_UPPER:
                    $key = strtoupper($keys[self::PROPERTY_NAME]);
                    break;
                case Zend_Db::CASE_NATURAL:
                    $key = ucwords($keys[self::PROPERTY_NAME]);
                    break;
                default:
                    $key = strtoupper($keys[self::PROPERTY_NAME]);
                    break;                              
            }
            $row[$key] = $this->getValue($this->_resultSet, $keys[self::PROPERTY_NAME], $keys[self::PROPERTY_TYPE]);
        }  
        return $row;        
    }

    private function fetch_num(){
        $row = array();
        foreach ($this->_keys as $keys){
            //add each column value from the result set to make a row                       
            $row[] = $this->getValue($this->_resultSet, $keys[self::PROPERTY_NAME], $keys[self::PROPERTY_TYPE]);
        }  
        return $row;    
    }

    /**
     * Fetches a row from the result set.
     *
     * @param int $style  OPTIONAL Fetch mode for this fetch operation.
     * @param int $cursor OPTIONAL Absolute, relative, or other.
     * @param int $offset OPTIONAL Number for absolute or relative cursors.
     * @return mixed Array, object, or scalar depending on fetch mode.
     * @throws Zend_Db_Statement_Db2_Exception
     */
    public function fetch($style = null, $cursor = null, $offset = null)
    {

        if (!$this->_stmt) {
            return false;
        }      
        if ($style === null) {
            $style = $this->_fetchMode;
        }
        $row = null;
        if (java_is_true($this->_resultSet->next())){
            switch ($style) {
                case Zend_Db::FETCH_NUM :
                    $row = $this->fetch_num();          
                    break;

                case Zend_Db::FETCH_ASSOC :
                    $row = $this->fetch_assoc();
                    break;
                default:

                    require_once 'Zendext/Db/Statement/Db2/Exception.php';
                    throw new Zendext_Db_Statement_Db2_Exception("Invalid fetch mode '$style' specified");
                    break;
            }           

        }
        else{
            //at the end of the resultset so close connection and return connection to pool      
            //TODO: Not sure if we want this. The logic behind this is to close the resultset right after it completes fetching.    
            $this->_stmt = null;
            $this->_resultSet->close();
            $this->_adapter->closeConnection();
        }
        return $row;
    }

    /**
     * Fetches the next row and returns it as an object.
     *
     * @param string $class  OPTIONAL Name of the class to create.
     * @param array  $config OPTIONAL Constructor arguments for the class.
     * @return mixed One object instance of the specified class.
     */
    public function fetchObject($class = 'stdClass', array $config = array())
    {
        /*
           $obj = $this->fetch(Zend_Db::FETCH_OBJ);
           return $obj;
         */
        require_once 'Zendext/Db/Statement/Db2/Exception.php';
        throw new Zendext_Db_Statement_Db2_Exception(__FUNCTION__ . '() is not implemented');       
    }

    /**
     * Retrieves the next rowset (result set) for a SQL statement that has
     * multiple result sets.  An example is a stored procedure that returns
     * the results of multiple queries.
     *
     * @return bool
     * @throws Zend_Db_Statement_Db2_Exception
     */
    public function nextRowset()
    {

        require_once 'Zendext/Db/Statement/Db2/Exception.php';
        throw new Zendext_Db_Statement_Db2_Exception(__FUNCTION__ . '() is not implemented');
    }

    /**
     * Returns the number of rows affected by the execution of the
     * last INSERT, DELETE, or UPDATE statement executed by this
     * statement object.
     *
     * @return int     The number of rows affected.
     */
    public function rowCount()
    {
        return $this->_rowCount;
    }

    /**
     * Returns an array containing all of the result set rows.
     *
     * @param int $style OPTIONAL Fetch mode.
     * @param int $col   OPTIONAL Column number, if fetch mode is by column.
     * @return array Collection of rows, each in a format by the fetch mode.
     *
     * Behaves like parent, but if limit()
     * is used, the final result removes the extra column
     * 'zend_db_rownum'
     */
    public function fetchAll($style = null, $col = null)
    {

        $data = parent::fetchAll($style, $col);
        $results = array();
        $remove = $this->_adapter->foldCase('ZEND_DB_ROWNUM');

        foreach ($data as $row) {
            if (is_array($row) && array_key_exists($remove, $row)) {
                unset($row[$remove]);
            }
            $results[] = $row;
        }
        return $results;

    }
}
?>
