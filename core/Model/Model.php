<?php

namespace Core\Model;

use Core\Database\DB;
use App\Models\UserChild;
use ReflectionProperty;

abstract class Model
{
    protected array $valuesCustom = [];

    /** name table from database
     * @var string
     */
    protected string $table;

    /** primary key for table
     * @var string
     */
    protected string $primaryKey;

    protected ?array $update = null;

    protected array $original = [];

    protected array $withModels = [];
    protected array $op = [];

    /**
     * @var DB
     */
    protected DB $db;

    /**
     * Model constructor.
     */
    public function __construct()
    {
        $this->table = $this->valuesCustom['table'] ?? static::getNameForTable((new \ReflectionClass(static::class))->getShortName());

        $this->primaryKey = $this->valuesCustom['id'] ?? 'id';

        $this->db = DB::table($this->table)->setFetchModeClass(static::class);
    }

    /**
     * @param array|null $primaryKeyValues
     * @return Collection|Model
     */
    public static function find(?array $primaryKeyValues = null)
    {
        if (!is_null($primaryKeyValues)) {
            if (count($primaryKeyValues) > 1) {

                return (new static)->prepareModelsFromDB($primaryKeyValues, 'IN')->all();
            }

            return (new static)->prepareModelFromDB($primaryKeyValues, '=')->get();
        }

        return new static();
    }

    public static function all()
    {
        return DB::table(static::getNameForTable(static::class))
            ->setFetchModeClass(static::class)
            ->all();
    }

    /**
     * @param string $nameTable
     * @return string
     */
    protected static function getNameForTable(string $nameTable): string
    {
        //$nameTable = substr($nameTable, strripos($nameTable, '\\') + 1);
        $nameTable = preg_replace('~[a-z]\K(?=[A-Z])~u', '_', $nameTable);

        return strtolower($nameTable);

    }

    /**
     * @param array $primaryKeyValues
     * @return DB
     */
    protected function prepareModelsFromDB(array $primaryKeyValues, string $operator): DB
    {
        return $this->db
            ->where($this->primaryKey, $operator, $primaryKeyValues);
    }

    /**
     * @param array $primaryKeyValues
     * @return DB
     */
    protected function prepareModelFromDB(array $primaryKeyValues, string $operator): DB
    {
        return $this->db
            ->where($this->primaryKey, $operator, $primaryKeyValues)
            ->limit(1);
    }

    /**
     * @param string $field
     * @param string $operator
     * @param null $value
     * @return $this
     */
    public function where(string $field, string $operator = '=', $value = null): Model
    {
        $this->db->where($field, $operator, $value);

        return $this;
    }

    /**
     * @param string $field
     * @param string $operator
     * @param null $value
     * @return $this
     */
    public function whereOr(string $field, string $operator = '=', $value = null): Model
    {
        $this->db->whereOr($field, $operator, $value);

        return $this;
    }

    /**
     * @return $this
     */
    public function fresh(): Model
    {
        return $this->db->find([$this->{$this->primaryKey}]);
    }

    /**
     * @return void
     */
    public function refresh(): void
    {
        $this->thisUpdate(DB::table($this->table)
                            ->where($this->primaryKey, '=', $this->{$this->primaryKey})
                            ->get());
    }

    /**
     * @param string $colName
     * @param string $ascDesc
     * @return $this
     */
    public function orderBy(string $colName, string $ascDesc): Model
    {
        $this->db->orderBy($colName, $ascDesc = 'ASC');

        return $this;
    }

    /**
     * @param string $field
     * @param string $operator
     * @param null $value
     * @return $this
     */
    public function limit(string $start, ?string $offset = null): Model
    {
        $this->db->limit($start, $offset);

        return $this;
    }

    /**
     * @return \Generator
     */
    public function getLazy():\Generator
    {
        return $this->db->getLazy();
    }

    /**
     * @return Collection
     */
    public function getAny(): Collection
    {
        return $this->db->all();
    }

    /**
     * @return int
     */
    public function delete(): int
    {
        return $this->db->where($this->primaryKey, '=', $this->{$this->primaryKey})
            ->delete();
    }

    /**
     * @return bool
     */
    public function deleteCascade(...$modelsName): bool
    {
        foreach ($modelsName as $modelName) {
            $obj = call_user_func([$modelName[0], 'find']);
            $obj = $obj->where($modelName[1], '=', $this->{$this->primaryKey})
                ->getAny();

            foreach ($obj as $item) {
                $item->delete();
            }
        }

        $this->db->where($this->primaryKey, '=', $this->{$this->primaryKey})
            ->delete();

        return true;
    }

    public function with(...$modelsName): void
    {
        foreach ($modelsName as $modelName) {
            $nameWith = lcfirst((new \ReflectionClass($modelName[0]))->getShortName());
            $this->withModels[$nameWith] = call_user_func([$modelName[0], 'find'])
                ->where($modelName[1], '=', $this->{$this->primaryKey})
                ->getAny();
        }
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function __get(string $name)
    {
        $this->op[] = $name;
        return $this->withModels[$name] ?? null;
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $name = $this->getNameForObj($name);

        if ((new \ReflectionClass($this))->hasProperty($name) and !(new \ReflectionProperty(get_class($this), $name))->isPublic()){
            return false;
        }

        $this->$name = $value;


            if (!in_array($name, array_column($this->original, 0), true)){
                $this->original[$name] = $value;
            }

        return true;
    }

    public function __isset($name)
    {
        $name = $this->getNameForObj($name);
        return isset($this->$name);
    }

    protected function getNameForObj(string $name): string
    {
        return str_replace('_', '', lcfirst(ucwords($name, "_")));
    }


    /**
     * @param array $attributes
     * @return $this
     */
    public function fill(array $attributes): Model
    {
        $this->thisUpdate($attributes);

        $this->update = $attributes;

        return $this;
    }

    protected function thisUpdate(array $attributes): void
    {
        foreach ($attributes as $key => $value){
            if (isset($this->$key) & $key != $this->primaryKey) {
                $this->$key = $attributes[$key];
            }
        }
    }

    /**
     * @param mixed ...$attributes
     * @return Collection|Model
     */
    public static function create(...$attributes)
    {
        $primaryKeyValues = null;
        foreach ($attributes as $attribute){
            $primaryKeyValues[] = DB::table((new static)->table)
                ->newDate($attribute)
                ->set();
        }

        return static::find($primaryKeyValues);
    }

    /**
     * @return int|null
     */
    public function save(): ?int
    {
        foreach ($this->original as $key => $value){
            if ($value != $this->$key){
                $this->update[static::getNameForTable($key)] = $this->original[$key] = $this->$key;
            }
        }
        echo "update";
        var_dump($this->update);

        if (!is_null($this->update)){
            return DB::table($this->table)
                ->newDate($this->update)
                ->where($this->primaryKey, '=', $this->{$this->primaryKey})
                ->set();
        }

        return null;
    }

}