<?php

namespace Core\Database;


/**
 * abstract class query builder SQL
 */
abstract class AbstractQueryBuilder
{
    protected Connect $connect;

    protected string $table;
    protected array $selects = ['*'];
    protected array $wheres = [];
    protected array $wheresOr = [];
    protected array $limit = [];
    protected array $newDate = [];
    protected array $orderBy = [];
    protected array $delete = [];

    /**
     * @return string
     */
    abstract protected function getInsertSql(): string;

    /**
     * @return string
     */
    abstract protected function getSqlTable(): string;

    /**
     * @param string $table
     */
    protected function __construct(string $table)
    {
        $this->table = $table;
        $this->connect = new Connect;
    }

    /**
     * @param  string $table
     * @return AbstractQueryBuilder
     */
    public static function table(string $table): AbstractQueryBuilder
    {
        return new static($table);
    }

    /**
     * @param  array  $selects [param query SELECT]
     * @return AbstractQueryBuilder
     */
    public function select(array $selects = ['*']): AbstractQueryBuilder
    {
        $this->selects = $selects;
        return $this;
    }

    /**
     * @param array $newDate
     * @return $this
     */
    public function newDate(array $newDate): AbstractQueryBuilder
    {
        $this->newDate = $newDate;

        return $this;
    }

    /**
     * @param string $field
     * @param int|string $operator
     * @param int|string $value
     * @return $this
     */
    public function where(string $field, string $operator = '=', $value = null): AbstractQueryBuilder
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        $this->wheres[] = [$field . ' ' . $operator, $value];

        return $this;
    }

    /**
     * @param string $field
     * @param int|string $operator
     * @param int|string $value
     * @return $this
     */
    public function whereOr(string $field, string $operator = '=', $value = null): AbstractQueryBuilder
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        $this->wheresOr[] = [$field . ' ' . $operator, $value];

        return $this;
    }

    /**
     * @param int|string $start
     * @param int|string|null $offset
     * @return $this
     */
    public function limit(string $start, ?string $offset = null): AbstractQueryBuilder
    {
        $this->limit = is_null($offset) ? [$start] : [$start, $offset];

        return $this;
    }

    /**
     * @param string $colName
     * @param string $ascDesc
     * @return $this
     */
    public function orderBy(string $colName, string $ascDesc): AbstractQueryBuilder
    {
        $this->orderBy = [$colName, $ascDesc];

        return $this;
    }

    /**
     * @param $class
     * @return $this
     */
    public function setFetchModeClass(string $class): AbstractQueryBuilder
    {
        $this->connect->setFetchMode(Connect::AS_CLASS, $class);

        return $this;
    }

    /**
     * @return $this
     */
    public function setFetchModeArray(): AbstractQueryBuilder
    {
        $this->connect->setFetchMode(Connect::AS_ASSOC);

        return $this;
    }

    /**
     * @return string
     */
    protected function getSelectSql(): string
    {
        $sql = "SELECT " . implode(', ', $this->selects) . $this->getSqlTable();

        return $sql . $this->getSqlOther();
    }

    /**
     * @param array $wheres
     * @param string $operator
     * @return string
     */
    protected function getWhereSql(array $wheres, string $operator): string
    {
        if (is_array($wheres[0][1])){
            $result = implode(' '. $operator . ' ',
                    array_map(
                        fn($where) =>
                            "{$where[0]} (" . str_repeat('?, ',
                                count($where[1]) - 1) . "?)",
                        $wheres
                    ));
        }else{
            $result = implode(' '. $operator . ' ',
                array_map(
                    fn($where) =>
                        "{$where[0]} " . "?",
                    $wheres
                ));
        }

        return $result;
    }

    /**
     * @return string
     */
    protected function getDeleteSql(): string
    {
        $sql = "DELETE FROM " . $this->table;

        return $sql . $this->getSqlOther();
    }

    /**
     * @return string
     */
    protected function getSqlOther(): string
    {
        if (count($this->wheres)) {
            $sql .= " WHERE " . $this->getWhereSql($this->wheres, 'AND');
        }

        if (count($this->wheresOr)) {
            $sql .= " OR " . $this->getWhereSql($this->wheresOr, 'OR');
        }

        if (count($this->orderBy)) {
            $sql .= " ORDER BY ?";
        }

        if (count($this->limit)) {
            $sql .= $sql = " LIMIT " . $this->limit[0];
            $sql .= is_null($this->limit[1]) ? '' : " OFFSET " . $this->limit[1];
        }

        return $sql;
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return $this->connect->fetch($this->getSelectSql(), $this->getParameters());
    }

    /**
     * @return \Generator
     */
    public function getLazy()
    {
        return $this->connect->fetchLazy($this->getSelectSql(), $this->getParameters());
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->connect->fetchAll($this->getSelectSql(), $this->getParameters());
    }

    /**
     * @return int
     */
    public function set(): int
    {
        if (count($this->wheres)){
            return $this->connect->insertUpdateDelete($this->getUpdateSql(), $this->getParameters());
        }

        return $this->connect->insertUpdateDelete($this->getInsertSql(), $this->getParameters());
    }

    /**
     * @return int
     */
    public function delete(): int
    {
       return $this->connect->insertUpdateDelete($this->getDeleteSql(), $this->getParameters());
    }

    /**
     * @param array $array
     * @return array
     */
    protected function toOneDimensionalArray(array $array): array
    {
        foreach ($array as $key => &$value)
        {
            if (is_array($value)) {
                array_splice($array, 0, $key, $value);
                unset($array[$key]);
            }
        }

        return $array;
    }

    /**
     * @return array
     */
    protected function getParameters(): array
    {
        $arrayParameters = array_merge(array_values($this->newDate),
            array_values($this->delete),
            array_values($this->toOneDimensionalArray(array_column($this->wheres, 1))),
            array_values($this->toOneDimensionalArray(array_column($this->wheresOr, 1))));
        if (count($this->orderBy)) {
            $arrayParameters = array_merge($arrayParameters, [$this->orderBy[1]]);
        }
        echo "Parameter   >>>>>";
var_dump($arrayParameters);
        return $arrayParameters;
    }

/*    public function fetchObject(string $className = "stdClass", ?array $ctor_args = null)
    {
        return $this->connect->fetchObject($this->getSelectSql(),
                                        $this->toOneDimensionalArray(array_column($this->wheres, 1)),
                                            $className, $ctor_args);

    }*/
}