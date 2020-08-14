<?php
namespace Core\Database;

class PgsqlQueryBuilder extends AbstractQueryBuilder
{

    /**
     * @return string
     */
    protected function getSqlTable(): string
    {
        return ' FROM "' . $this->table . '"';
    }

    /**
     * @return string
     */
    protected function getInsertSql(): string
    {
        return /** @lang text */ 'INSERT INTO "' . $this->table . '" (' . implode(', ', $this->into) .
            ")  VALUES  (?" . str_repeat(", ?", count($this->values)-1) . ")";
    }

    /**
     * @return string
     */
    protected function getUpdateSql(): string
    {
        $sql = "UPDATE " . $this->table . " SET " . implode(" = '?', ", $this->inserts[0]) . " = '?'";

        if (count($this->wheres)){
            $sql .= $this->getWhereSql();
        }

        if (count($this->limit)){
            $sql .= $this->getSqlLimit();
        }

        return $sql;
    }
}