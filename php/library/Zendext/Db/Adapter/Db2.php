<?php
require("java/Java.inc");
class Zendext_Db_Adapter_Db2 extends Zend_Db_Adapter_Abstract {

    const DB2_AUTOCOMMIT_ON = 1;
    const DB2_AUTOCOMMIT_OFF = 0;

    protected $_config = array(
            'persistent'   => false,
            'os'           => null,
            'schema'       => null,
            'tomcatEnv'    => null,
            'jdbcSpace'    => null
            );


    /**
     * Execution mode
     *
     * @var int execution flag (DB2_AUTOCOMMIT_ON or DB2_AUTOCOMMIT_OFF)
     */
    protected $_execute_mode = self::DB2_AUTOCOMMIT_ON;

    /**
     * Default class name for a DB statement.
     *
     * @var string
     */
    protected $_defaultStmtClass = 'Zendext_Db_Statement_Db2';
    protected $_isI5 = false;
    protected $_resultSetCase = null;

    /**
     * Keys are UPPERCASE SQL datatypes or the constants
     * Zend_Db::INT_TYPE, Zend_Db::BIGINT_TYPE, or Zend_Db::FLOAT_TYPE.
     *
     * Values are:
     * 0 = 32-bit integer
     * 1 = 64-bit integer
     * 2 = float or decimal
     *
     * @var array Associative array of datatypes to values 0, 1, or 2.
     */
    protected $_numericDataTypes = array(
            Zend_Db::INT_TYPE    => Zend_Db::INT_TYPE,
            Zend_Db::BIGINT_TYPE => Zend_Db::BIGINT_TYPE,
            Zend_Db::FLOAT_TYPE  => Zend_Db::FLOAT_TYPE,
            'INTEGER'            => Zend_Db::INT_TYPE,
            'SMALLINT'           => Zend_Db::INT_TYPE,
            'BIGINT'             => Zend_Db::BIGINT_TYPE,
            'DECIMAL'            => Zend_Db::FLOAT_TYPE,
            'NUMERIC'            => Zend_Db::FLOAT_TYPE
            );

    /**
     * Check for config options that are mandatory.
     * Throw exceptions if any are missing.
     *
     * @param array $config
     * @throws Zend_Db_Adapter_Exception
     */
    protected function _checkRequiredOptions(array $config)
    {
        // we need at least a dbname
        if (! array_key_exists('tomcatEnv', $config)) {
            /** @see Zend_Db_Adapter_Exception */
            throw new Zend_Db_Adapter_Exception("Configuration array must have a key call 'tomcatEnv' for database connection pooling");
        }

        if (! array_key_exists('jdbcSpace', $config)) {
            /**
             * @see Zend_Db_Adapter_Exception
             */
            throw new Zend_Db_Adapter_Exception("Configuration array must have a key for 'jdbcSpace' for database connection pooling");
        }
    } 

    public function getResultsetCase(){
        return $this->_resultSetCase;
    }

    public function setResultsetCase($case){

        if ($case == Zend_Db::CASE_LOWER || $case == Zend_Db::CASE_UPPER || $case == Zend_Db::CASE_NATURAL){
            $this->_resultSetCase = $case;  
        }
        else{
            $this->_resultSetCase = Zend_Db::CASE_UPPER;
        }
    }

    /**
     * Creates a connection resource.
     *
     * @return void
     */
    protected function _connect()
    {   
        if (is_object($this->_connection)) {
            // connection already exists
            return;
        }        
        $this->_determineI5();        

        /*
           if (!isset($this->_config['driver_options']['autocommit'])) {
        // set execution mode
        $this->_config['driver_options']['autocommit'] = &$this->_execute_mode;
        }

        if (isset($this->_config['options'][Zend_Db::CASE_FOLDING])) {
        $caseAttrMap = array(
        Zend_Db::CASE_NATURAL => DB2_CASE_NATURAL,
        Zend_Db::CASE_UPPER   => DB2_CASE_UPPER,
        Zend_Db::CASE_LOWER   => DB2_CASE_LOWER
        );
        $this->_config['driver_options']['DB2_ATTR_CASE'] = $caseAttrMap[$this->_config['options'][Zend_Db::CASE_FOLDING]];
        }
         */

        if (isset($this->_config['options'][Zend_Db::CASE_FOLDING]) && null == $this->getResultsetCase()) {

            $this->setResultsetCase($this->_config['options'][Zend_Db::CASE_FOLDING]);
        }
        elseif (null == $this->getResultsetCase()){
            $this->setResultsetCase(Zend_Db::CASE_UPPER);
        }

        //retrieve a connection from the pool which handles by Tomcat
        //TODO: Need to maybe integrate driver options

        if (isset($this->_config['tomcatEnv']) && isset($this->_config['jdbcSpace'])){
            try{
                $initContext = new Java("javax.naming.InitialContext");
                $ds = $initContext->lookup($this->_config['tomcatEnv'])
                    ->lookup($this->_config['jdbcSpace']);
                $this->_connection = $ds->getConnection();        
            }
            catch (Exception $e){               
            }
            // check the connection
            if (!$this->_connection) {
                throw new Zendext_Db_Adapter_Db2_Exception('Unable to retrieve connection from the connection pool');
            }           
        }
        else{
            throw new Zendext_Db_Adapter_Db2_Exception('Unable to retrieve configuration data for the connection pool');            
        }        

    }

    /**
     * Test if a connection is active
     *
     * @return boolean
     */
    public function isConnected()
    {
        return !java_is_true($this->_connection->isClosed());
    }

    /**
     * Force the connection to close.
     *
     * @return void
     */
    public function closeConnection()
    {
        if ($this->isConnected()) {
            $this->_connection->close();
        }
        $this->_connection = null;
    }

    /**
     * Returns an SQL statement for preparation.
     *
     * @param string $sql The SQL statement with placeholders.
     * @return Zend_Db_Statement_Db2
     */
    public function prepare($sql)
    {       
        $this->_connect();
        $stmtClass = $this->_defaultStmtClass;
        Zend_Loader::loadClass($stmtClass);
        $stmt = new $stmtClass($this, $sql);
        $stmt->setFetchMode($this->_fetchMode);
        return $stmt;
    }

    /**
     * Gets the execution mode
     *
     * @return int the execution mode (DB2_AUTOCOMMIT_ON or DB2_AUTOCOMMIT_OFF)
     */
    public function _getExecuteMode()
    {
        return $this->_execute_mode;
    }

    /**
     * @param integer $mode
     * @return void
     */
    public function _setExecuteMode($mode)
    {
        switch ($mode) {
            case self::DB2_AUTOCOMMIT_OFF:
                //Zend did not include the update of executing mode below which doesn't make any sense in term of what the method is trying to do
                $this->_execute_mode = $mode;
                $this->_connection->setAutoCommit($mode);   
                break;              
            case self::DB2_AUTOCOMMIT_ON:
                $this->_execute_mode = $mode;
                $this->_connection->setAutoCommit($mode);   
                break;
            default:
                throw new Zendext_Db_Adapter_Db2_Exception('execution mode not supported');             
                break;
        }
    }

    /**
     * Quote a raw string.
     *
     * @param string $value     Raw string
     * @return string           Quoted string
     */
    protected function _quote($value)
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }
        return parent::_quote($value);
    }

    /**
     * @return string
     */
    public function getQuoteIdentifierSymbol()
    {
        $this->_connect();

        $metaData = $this->_connection->getMetaData();

        return java_values($metaData->getIdentifierQuoteString());
    }

    /**
     * Returns a list of the tables in the database.
     * @param string $schema OPTIONAL
     * @return array
     */
    public function listTables($schema = null)
    {
        $this->_connect();

        if ($schema === null && $this->_config['schema'] != null) {
            $schema = $this->_config['schema'];
        }

        $tables = array();
        $tables = $this->_i5listTables($schema);

        return $tables;
    }


    /**
     * Returns the column descriptions for a table.
     *
     * The return value is an associative array keyed by the column name,
     * as returned by the RDBMS.
     *
     * The value of each array element is an associative array
     * with the following keys:
     *
     * SCHEMA_NAME      => string; name of database or schema
     * TABLE_NAME       => string;
     * COLUMN_NAME      => string; column name
     * COLUMN_POSITION  => number; ordinal position of column in table
     * DATA_TYPE        => string; SQL datatype name of column
     * DEFAULT          => string; default expression of column, null if none
     * NULLABLE         => boolean; true if column can have nulls
     * LENGTH           => number; length of CHAR/VARCHAR
     * SCALE            => number; scale of NUMERIC/DECIMAL
     * PRECISION        => number; precision of NUMERIC/DECIMAL
     * UNSIGNED         => boolean; unsigned property of an integer type
     *                     DB2 not supports UNSIGNED integer.
     * PRIMARY          => boolean; true if column is part of the primary key
     * PRIMARY_POSITION => integer; position of column in primary key
     * IDENTITY         => integer; true if column is auto-generated with unique values
     *
     * @param string $tableName
     * @param string $schemaName OPTIONAL
     * @return array
     */
    public function describeTable($tableName, $schemaName = null)
    {       
        $this->_connect();

        if ($schemaName === null && $this->_config['schema'] != null) {
            $schemaName = $this->_config['schema'];
        }

        $catalog = $this->_connection->getCatalog();
        $schemaPattern = new java("java.lang.String", $schemaName);
        $tablePattern = new java("java.lang.String", $tableName);        
        $metaData = $this->_connection->getMetaData();
        $resultSetMetaData = $metaData->getColumns($catalog,$schemaPattern,$tablePattern,null);

        while (java_is_true($resultSetMetaData->next())) {
            $schemaName = $this->foldCase(java_values($resultSetMetaData->getString('TABLE_SCHEM')));
            $tableName = $this->foldCase(java_values($resultSetMetaData->getString('TABLE_NAME')));
            $colname = $this->foldCase(java_values($resultSetMetaData->getString('COLUMN_NAME')));
            $colPosition = java_values($resultSetMetaData->getInt('ORDINAL_POSITION'));
            $dataType = java_values($resultSetMetaData->getString('TYPE_NAME'));
            $nullable = (bool) (substr(java_values($resultSetMetaData->getString('IS_NULLABLE')), 0,1) == 'Y');
            $length = java_values($resultSetMetaData->getInt('COLUMN_SIZE'));
            list ($primary, $primaryPosition, $identity, $default, $scale, $precision) = array(false, null, false, ' ', '', 0);
            /*
             * base on article http://publib.boulder.ibm.com/infocenter/iseries/v6r1m0/index.jsp?topic=/rzahh/javadoc/com/ibm/as400/access/AS400JDBCResultSetMetaData.html
             * autoincrement is not supported thus the identity field will be default to false
             * 
             */
            if ($dataType == 'DECIMAL'){
                $default = '0';
                $scale = '0';
                $precision = $length;
            }

            $desc[$colname] = array(
                    'SCHEMA_NAME'      => $schemaName,
                    'TABLE_NAME'       => $tableName,
                    'COLUMN_NAME'      => $colname,
                    'COLUMN_POSITION'  => $colPosition,
                    'DATA_TYPE'        => $dataType,
                    'DEFAULT'          => $default,
                    'NULLABLE'         => $nullable,
                    'LENGTH'           => $length,
                    'SCALE'            => $scale,
                    'PRECISION'        => $precision,
                    'UNSIGNED'         => false,
                    'PRIMARY'          => $primary,
                    'PRIMARY_POSITION' => $primaryPosition,
                    'IDENTITY'         => $identity
                    );              

        }           

        $resultSetMetaData->close();
        return $desc;
    }

    /**
     * Return the most recent value from the specified sequence in the database.
     * This is supported only on RDBMS brands that support sequences
     * (e.g. Oracle, PostgreSQL, DB2).  Other RDBMS brands return null.
     *
     * @param string $sequenceName
     * @return string
     */
    public function lastSequenceId($sequenceName)
    {
        $this->_connect();

        if (!$this->_isI5) {
            $quotedSequenceName = $this->quoteIdentifier($sequenceName, true);
            $sql = 'SELECT PREVVAL FOR ' . $quotedSequenceName . ' AS VAL FROM SYSIBM.SYSDUMMY1';
        } else {
            $quotedSequenceName = $sequenceName;
            $sql = 'SELECT PREVVAL FOR ' . $this->quoteIdentifier($sequenceName, true) . ' AS VAL FROM QSYS2.QSQPTABL';
        }

        $value = $this->fetchOne($sql);
        return (string) $value;
    }

    /**
     * Generate a new value from the specified sequence in the database, and return it.
     * This is supported only on RDBMS brands that support sequences
     * (e.g. Oracle, PostgreSQL, DB2).  Other RDBMS brands return null.
     *
     * @param string $sequenceName
     * @return string
     */
    public function nextSequenceId($sequenceName)
    {
        $this->_connect();
        $sql = 'SELECT NEXTVAL FOR '.$this->quoteIdentifier($sequenceName, true).' AS VAL FROM SYSIBM.SYSDUMMY1';
        $value = $this->fetchOne($sql);
        return (string) $value;
    }

    /**
     * Gets the last ID generated automatically by an IDENTITY/AUTOINCREMENT column.
     *
     * As a convention, on RDBMS brands that support sequences
     * (e.g. Oracle, PostgreSQL, DB2), this method forms the name of a sequence
     * from the arguments and returns the last id generated by that sequence.
     * On RDBMS brands that support IDENTITY/AUTOINCREMENT columns, this method
     * returns the last value generated for such a column, and the table name
     * argument is disregarded.
     *
     * The IDENTITY_VAL_LOCAL() function gives the last generated identity value
     * in the current process, even if it was for a GENERATED column.
     *
     * @param string $tableName OPTIONAL
     * @param string $primaryKey OPTIONAL
     * @param string $idType OPTIONAL used for i5 platform to define sequence/idenity unique value
     * @return string
     */

    public function lastInsertId($tableName = null, $primaryKey = null, $idType = null)
    {
        $this->_connect();

        if ($this->_isI5) {
            return (string) $this->_i5LastInsertId($tableName, $idType);
        }

        if ($tableName !== null) {
            $sequenceName = $tableName;
            if ($primaryKey) {
                $sequenceName .= "_$primaryKey";
            }
            $sequenceName .= '_seq';
            return $this->lastSequenceId($sequenceName);
        }

        $sql = 'SELECT IDENTITY_VAL_LOCAL() AS VAL FROM SYSIBM.SYSDUMMY1';
        $value = $this->fetchOne($sql);
        return (string) $value;
    }

    /**
     * Begin a transaction.
     *
     * @return void
     */
    protected function _beginTransaction()
    {
        $this->_setExecuteMode(self::DB2_AUTOCOMMIT_OFF);
    }

    /**
     * Commit a transaction.
     *
     * @return void
     */
    protected function _commit()
    {
        $this->_connection->commit();

        $this->_setExecuteMode(self::DB2_AUTOCOMMIT_ON);
    }

    /**
     * Rollback a transaction.
     *
     * @return void
     */
    protected function _rollBack()
    {       
        $this->_connection->rollback();
        $this->_setExecuteMode(self::DB2_AUTOCOMMIT_ON);
    }

    /**
     * Set the fetch mode.
     *
     * @param integer $mode
     * @return void
     * @throws Zend_Db_Adapter_Db2_Exception
     */
    public function setFetchMode($mode)
    {
        switch ($mode) {
            case Zend_Db::FETCH_NUM:   // seq array
            case Zend_Db::FETCH_ASSOC: // assoc array
            case Zend_Db::FETCH_BOTH:  // seq+assoc array
            case Zend_Db::FETCH_OBJ:   // object
                $this->_fetchMode = $mode;
                break;
            case Zend_Db::FETCH_BOUND:   // bound to PHP variable
                /**
                 * @see Zend_Db_Adapter_Db2_Exception
                 */
                throw new Zendext_Db_Adapter_Db2_Exception('FETCH_BOUND is not supported yet');
                break;
            default:
                /**
                 * @see Zend_Db_Adapter_Db2_Exception
                 */
                throw new Zendext_Db_Adapter_Db2_Exception("Invalid fetch mode '$mode' specified");
                break;
        }
    }

    /**
     * Adds an adapter-specific LIMIT clause to the SELECT statement.
     *
     * @param string $sql
     * @param integer $count
     * @param integer $offset OPTIONAL
     * @return string
     */
    public function limit($sql, $count, $offset = 0)
    {
        $count = intval($count);
        if ($count <= 0) {
            /**
             * @see Zend_Db_Adapter_Db2_Exception
             */
            throw new Zendext_Db_Adapter_Db2_Exception("LIMIT argument count=$count is not valid");
        }

        $offset = intval($offset);
        if ($offset < 0) {
            /**
             * @see Zend_Db_Adapter_Db2_Exception
             */
            throw new Zendext_Db_Adapter_Db2_Exception("LIMIT argument offset=$offset is not valid");
        }

        if ($offset == 0) {
            $limit_sql = $sql . " FETCH FIRST $count ROWS ONLY";
            return $limit_sql;
        }

        /**
         * DB2 does not implement the LIMIT clause as some RDBMS do.
         * We have to simulate it with subqueries and ROWNUM.
         * Unfortunately because we use the column wildcard "*",
         * this puts an extra column into the query result set.
         */
        $limit_sql = "SELECT z2.*
            FROM (
                    SELECT ROW_NUMBER() OVER() AS \"ZEND_DB_ROWNUM\", z1.*
                    FROM (
                        " . $sql . "
                        ) z1
                 ) z2
            WHERE z2.zend_db_rownum BETWEEN " . ($offset+1) . " AND " . ($offset+$count);
        return $limit_sql;
    }

    /**
     * Check if the adapter supports real SQL parameters.
     *
     * @param string $type 'positional' or 'named'
     * @return bool
     */
    public function supportsParameters($type)
    {
        if ($type == 'positional') {
            return true;
        }

        // if its 'named' or anything else
        return false;
    }

    /**
     * Retrieve server version in PHP style
     *
     * @return string
     */
    public function getServerVersion()
    {
        $this->_connect();
        $metaData = $this->_connection->getMetaData();      
        return java_values($metaData->getDatabaseProductVersion());
    }

    /**
     * Return whether or not this is running on i5
     *
     * @return bool
     */
    public function isI5()
    {
        if ($this->_isI5 === null) {
            $this->_determineI5();
        }

        return (bool) $this->_isI5;
    }

    /**
     * Check the connection parameters according to verify
     * type of used OS
     *
     *  @return void
     */
    protected function _determineI5()
    {
        // first us the compiled flag.
        $this->_isI5 = (php_uname('s') == 'OS400') ? true : false;

        // if this is set, then us it
        if (isset($this->_config['os'])){
            if (strtolower($this->_config['os']) === 'i5') {
                $this->_isI5 = true;
            } else {
                // any other value passed in, its null
                $this->_isI5 = false;
            }
        }

    }

    /**
     * Db2 On I5 specific method
     *
     * Returns a list of the tables in the database .
     * Used only for DB2/400.
     *
     * @return array
     */
    protected function _i5listTables($schema = null)
    {
        //list of i5 libraries.
        $tables = array();

        $catalog = $this->_connection->getCatalog();
        $metaData = $this->_connection->getMetaData();
        $schemaPattern = null;
        $tablePattern = null;
        $type = null;
        if ($schema){
            $schemaPattern = new java("java.lang.String", $schema);
        }

        $resultSet = $metaData->getTables($catalog,$schemaPattern,$tablePattern,$type);

        while (java_is_true($resultSet->next())) {
            $tables[] = java_values($resultSet->getString('TABLE_NAME'));           
        }           

        $resultSet->close();

        return $tables;
    }

    protected function _i5LastInsertId($objectName = null, $idType = null)
    {

        if ($objectName === null) {
            $sql = 'SELECT IDENTITY_VAL_LOCAL() AS VAL FROM QSYS2.QSQPTABL';
            $value = $this->fetchOne($sql);
            return $value;
        }

        if (strtoupper($idType) === 'S'){
            //check i5_lib option
            $sequenceName = $objectName;
            return $this->lastSequenceId($sequenceName);
        }

        $tableName = $objectName;
        return $this->fetchOne('SELECT IDENTITY_VAL_LOCAL() from ' . $this->quoteIdentifier($tableName));
    }   

}

?>
