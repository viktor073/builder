<?php

namespace Core\Database;

/**
 * query builder MySQL extends AbstractQueryBuilder
 */
class MysqlQueryBuilder extends AbstractQueryBuilder
{

    /**
     * @return string
     */
    protected function getSqlTable(): string
    {
        return ' FROM ' . $this->table;
    }

	/**
	 * @return string
	 */
	protected function getInsertSql(): string
	{
		return /** @lang text */ 'INSERT INTO ' . $this->table . ' (' . implode(', ', array_keys($this->newDate)) .
			")  VALUES  (?" . str_repeat(", ?", count($this->newDate)-1) . ")";
	}

    /**
     * @return string
     */
    protected function getUpdateSql(): string
    {
        $sql = "UPDATE " . $this->table . " SET " . implode(' =?, ', array_keys($this->newDate)) . ' = ?';

        if (count($this->wheres)) {
            $sql .= " WHERE " . $this->getWhereSql($this->wheres, 'AND');
        }

        if (count($this->wheresOr)) {
            $sql .= " OR " . $this->getWhereSql($this->wheresOr, 'OR');
        }

        if (count($this->limit)){
            $sql .= $this->getSqlLimit();
        }

        return $sql;
    }
}