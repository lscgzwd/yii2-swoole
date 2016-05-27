<?php
namespace swoole\yii\db;

/**
 * Created by PhpStorm.
 * User: lusc
 * Date: 2016/5/23
 * Time: 20:22
 */
class Connection extends \yii\db\Connection
{
    /**
     * Creates a command for execution.
     * @param string $sql the SQL statement to be executed
     * @param array $params the parameters to be bound to the SQL statement
     * @return Command the DB command
     */
    public function createCommand($sql = null, $params = [])
    {
        $command = new Command([
            'db'  => $this,
            'sql' => $sql,
        ]);

        return $command->bindValues($params);
    }

    /**
     * Creates the PDO instance.
     * This method is called by [[open]] to establish a DB connection.
     * The default implementation will create a PHP PDO instance.
     * You may override this method if the default PDO needs to be adapted for certain DBMS.
     * @return \PDO the pdo instance
     */
    protected function createPdoInstance()
    {
        if (!is_array($this->attributes)) {
            $this->attributes = [\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION];
        } else {
            $this->attributes = [\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION] + $this->attributes;
        }

        return parent::createPdoInstance();
    }
}
