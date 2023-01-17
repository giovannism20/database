<?php

namespace Utopia\Database\Adapter;

use Exception;
use PDO;
use PDOException;
use Swoole\Database\PDOProxy;
use Utopia\Database\Database;
use Utopia\Database\Exception\Timeout;

class MySQL extends MariaDB
{
    /**
     * Get SQL Index
     * 
     * @param string $collection
     * @param string $id
     * @param string $type
     * @param array $attributes
     * 
     * @return string
     */
    protected function getSQLIndex(string $collection, string $id, string $type, array $attributes): string
    {
        switch ($type) {
            case Database::INDEX_KEY:
                $type = 'INDEX';
                break;

            case Database::INDEX_ARRAY:
                $type = 'INDEX';

                foreach ($attributes as $key => $value) {
                    $attributes[$key] = '(CAST(' . $value . ' AS char(255) ARRAY))';
                }
                break;

            case Database::INDEX_UNIQUE:
                $type = 'UNIQUE INDEX';
                break;

            case Database::INDEX_FULLTEXT:
                $type = 'FULLTEXT INDEX';
                break;

            default:
                throw new Exception('Unknown Index Type:' . $type);
                break;
        }

        return 'CREATE '.$type.' `'.$id.'` ON `'.$this->getDefaultDatabase().'`.`'.$this->getNamespace().'_'.$collection.'` ( '.implode(', ', $attributes).' );';
    }
//
//    /**
//     * Returns Max Execution Time
//     * @param string $sql
//     * @param float $seconds
//     * @return string
//     */
//    protected function setTimeOut2(string $sql, float $seconds): string
//    {
//        $syntax = '/*+ max_execution_time(' . ($seconds * 1000) . ') */';
//        return sprintf($sql, '', $syntax);
//    }

    /**
     * Set Max Execution Time Query
     * @param PDO|PDOProxy $pdo
     * @param int $milliseconds
     */
    protected function setTimeout(PDO|PDOProxy $pdo, int $milliseconds)
    {
        $pdo->prepare('SET SESSION max_execution_time = ' . $milliseconds)->execute();
    }

    /**
     * Resets Max Execution Time Query
     * @param PDO|PDOProxy $pdo
     */
    protected function resetTimeout(PDO|PDOProxy $pdo)
    {
        $pdo->prepare('SET SESSION max_execution_time = default')->execute();
    }

    /**
     * @param PDOException $e
     * @param PDO|PDOProxy $pdo
     * @throws Timeout
     */
    protected function processException(PDOException $e, PDO|PDOProxy $pdo): void
    {
        if($e->getCode() === 'HY000' && isset($e->errorInfo[1]) && $e->errorInfo[1] === 3024){
            $this->resetTimeout($pdo);
            Throw new Timeout($e->getMessage());
        }

        // PDOProxy which who switches errorInfo
        if($e->getCode() === 3024 && isset($e->errorInfo[0]) && $e->errorInfo[0] === "HY000"){
            $this->resetTimeout($pdo);
            Throw new Timeout($e->getMessage());
        }

        throw $e;
    }

}
