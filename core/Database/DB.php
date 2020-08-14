<?php

namespace Core\Database;

use Core\App;
use Core\Model\Collection;

/**
 * Class Dispatcher
 * @package Core\Database
 */
class DB
{
     protected array $listQueryBuilder = [];
     protected AbstractQueryBuilder $queryBuilder;

    /**
     * Query constructor.
     * @param string $table
     */
    protected function __construct(string $table)
     {
         $this->listQueryBuilder = [
             'mysql' => [MysqlQueryBuilder::class, 'table'],
             'pgsql' => [PgsqlQueryBuilder::class, 'table']
         ];

         $this->queryBuilder = call_user_func($this->listQueryBuilder[App::$config['database']], $table);
     }

    /**
     * @param string $table
     * @return $this
     */
     public static function table(string $table): DB
     {
         return new static($table);
     }

    /**
     * @param array|null $selects
     * @return $this
     */
    public function select(?array $selects = null): DB
    {
        $this->queryBuilder->select($selects);

        return $this;
    }

    /**
     * @param string $field
     * @param int|string $operator
     * @param int|string|null $value
     * @return $this
     */
    public function where(string $field, string $operator = '=', $value = null): DB
    {
        $this->queryBuilder->where($field, $operator, $value);

        return $this;
    }

    /**
     * @param string $field
     * @param int|string $operator
     * @param int|string|null $value
     * @return $this
     */
    public function whereOr(string $field, string $operator = '=', $value = null): DB
    {
        $this->queryBuilder->whereOr($field, $operator, $value);

        return $this;
    }

    /**
     * @param string $colName
     * @param string $ascDesc
     * @return $this
     */
    public function orderBy(string $colName, string $ascDesc): DB
    {
        $this->queryBuilder->orderBy($colName, $ascDesc);

        return $this;
    }

    /**
     * @param int|string $start
     * @param int|string|null $offset
     * @return $this
     */
    public function limit(string $start, ?string $offset = null): DB
    {
        $this->queryBuilder->limit($start, $offset);

        return $this;
    }

    /**
     * @param string $class
     * @return $this
     */
    public function setFetchModeClass(string $class): DB
    {
        $this->queryBuilder->setFetchModeClass($class);

        return $this;
    }

    /**
     * @param string $class
     * @return $this
     */
    public function setFetchModeArray(string $class): DB
    {
        $this->queryBuilder->setFetchModeArray();

        return $this;
    }

    /**
     * @param array $attributes
     * @return $this
     */
    public function newDate(array $attributes): DB
    {
        $this->queryBuilder->newDate($attributes);

        return $this;
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return $this->queryBuilder->get();
    }

    /**
     * @return mixed
     */
    public function getLazy()
    {
        return $this->queryBuilder->getLazy();
    }

    /**
     * @return Collection
     */
    public function all(): Collection
    {
        return new Collection($this->queryBuilder->all());
    }

    /**
     * @return int
     */
    public function set(): int
    {
        return $this->queryBuilder->set();
    }

    /**
     * @return int
     */
    public function delete(): int
    {
        return $this->queryBuilder->delete();
    }

    /**
     * @param string $className
     * @param array|null $ctor_args
     * @return mixed
     */
    public function fetchObject(string $className, ?array $ctor_args = null)
    {
        return $this->queryBuilder->fetchObject($className, $ctor_args);
    }
}