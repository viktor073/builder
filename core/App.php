<?php

namespace Core;

use App\Models\User;
use App\Models\UserChild;
use Core\Database\DB;

/**
 * App class
 */
class App
{
    protected static string $root;
	protected static string $rootConfig;

	protected string $configPath;
    protected string $configDBPath;

	public static array $config = [];
    public static array $configDB = [];

	public function __construct()
	{
		self::$root = getcwd();
		self::$rootConfig = self::$root . "/config/";

		$this->configPath = self::$rootConfig . "config.php";
        $this->configDBPath = self::$rootConfig . "configDB.php";
	}

    /**
     * @return $this
     */
	public function run(): self
    {
        return $this->init();
	}

	protected function init(): App
    {
		self::$config = include $this->configPath;
        self::$configDB = include $this->configDBPath;

        echo "<pre>";

        $user = User::find(['87']);

        $user->with([UserChild::class, 'user_id']);


        $user->deleteCascade([UserChild::class, 'user_id']);

        //var_dump($user);
        //var_dump($user->userChild);
        echo "</pre>";

		return $this;
	}
}
