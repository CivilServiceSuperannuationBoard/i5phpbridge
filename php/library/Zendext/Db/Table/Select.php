<?php
class Zendext_Db_Table_Select extends Zend_Db_Table_Select {

    protected function _renderColumns($sql)
    {
        if (!count($this->_parts[self::COLUMNS])) {
            return null;
        }

        $columns = array();
        foreach ($this->_parts[self::COLUMNS] as $columnEntry) {
            list($correlationName, $column, $alias) = $columnEntry;
            if ($column instanceof Zend_Db_Expr) {
                $columns[] = $this->_adapter->quoteColumnAs($column, $alias, true);
            } else {
                if ($column == self::SQL_WILDCARD) {
                    $column = new Zend_Db_Expr(self::SQL_WILDCARD);
                    $alias = null;
                }
                if (empty($correlationName)) {
                    $columns[] = $this->_adapter->quoteColumnAs($column, $alias, true);
                } else {
                    /*
                     * @todo: 
                     * By: Huy Tran
                     * Date: 20080206
                     * Description: This code here is for DB2 compatibility. This allow the appendtion of the schema to the front of the column name.
                     * Eg. Select SELECT WEBSTUFF.ALIAS.ALNAME, WEBSTUFF.ALIAS.ALSID FROM WEBSTUFF.ALIAS 
                     */

                    $schema = $this->_parts[self::FROM][$correlationName]["schema"];
                    $columns[] = $this->_adapter->quoteColumnAs(array($schema.'.'.$correlationName, $column), $alias, true);                    
                }
            }
        }

        return $sql .= ' ' . implode(', ', $columns);
    }

}
?>
