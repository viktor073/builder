<?php

namespace Core\Database;

use Core\App;
use PDO;
use PDOStatement;
use Generator;

/**
 *  Connect for Database
 */
class Connect
{
    public const AS_ASSOC = PDO::FETCH_ASSOC;
    public const AS_CLASS = PDO::FETCH_CLASS;

	protected PDO $pdo;

    protected array $fetchMode = [self::AS_ASSOC];

	/**
	 * [init Connect]
	 */
	public function __construct()
	{
		$config = App::$configDB[App::$config['database']];

		$dsn = "{$config['driver']}:host={$config['host']};port={$config['port']};dbname={$config['dbname']};
		    user={$config['user']};password={$config['password']}";

		$this->pdo = new PDO($dsn);
	}

    /**
     * @param mixed ...$fetchMode
     */
	public function setFetchMode(...$fetchMode): void
	{
		$this->fetchMode = $fetchMode;
	}

    /**
     * [query SELECT execution]
     * @param string $sql
     * @param array|null $parameters
     * @return PDOStatement
     */
	protected function executeGet(string $sql, ?array $parameters = null): PDOStatement
	{
		$statement = $this->pdo->prepare($sql);
		$statement->setFetchMode(...$this->fetchMode);
		$statement->execute($parameters);

		return $statement;
	}

	/**
	 * [query INSERT, UPDATE, DELETE execution]
	 * @param  string     $sql
	 * @param  array|null $parameters
	 * @return int
	 */
	protected function executeSet(string $sql, ?array $parameters = null): int
	{
		$statement = $this->pdo->prepare($sql);
		echo "connect";
var_dump($statement);
var_dump($parameters);
		$statement->execute($parameters);

		return $this->pdo->lastInsertId();
	}

    /**
     * [fetch PDOStatement]
     * @param string $sql
     * @param array $parameters
     * @return mixed
     */
	public function fetch(string $sql, array $parameters)
	{
		return $this->executeGet($sql, $parameters)->fetch();
	}

    /**
     * [fetchAll PDOStatement]
     * @param string $sql
     * @param array|null $parameters
     * @return array
     */
	public function fetchAll(string $sql, ?array $parameters = null): array
	{
		return $this->executeGet($sql, $parameters)->fetchAll();
	}

    /** fetchColumn PDOStatement
     * @param string $sql
     * @param array|null $parameters
     * @return mixed
     */
	public function fetchColumn(string $sql, ?array $parameters = null)
	{
		return $this->executeGet($sql, $parameters)->fetchColumn();
	}

    /**
     * @param string $sql
     * @param array|null $parameters
     * @param array $fetchMode
     * @return mixed
     */
	public function fetchObject(string $sql, array $fetchMode, ?array $parameters = null)
	{
		return $this->executeGet($sql, $parameters)->fetchObject(...$fetchMode);
	}

    /**
     * @param string $sql
     * @param array $parameters
     * @return \Generator
     */
    public function fetchLazy(string $sql, ?array $parameters = null): \Generator
    {
        foreach ($this->executeGet($sql, $parameters)->fetch() as $fetchLazy)
        {
            yield $fetchLazy;
        }
    }

	/**
	 * @param  string $sql
	 * @param  array  $parameters
	 * @return int
	 */
	public function insertUpdateDelete(string $sql, array $parameters): int
	{
		return $this->executeSet($sql, $parameters);
	}

}
