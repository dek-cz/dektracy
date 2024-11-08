<?php

declare(strict_types=1);

namespace DekApps\DekTracy;

use DekApps\MssqlProcedure\Event\Event as SQLEvent;
use Tracy\Debugger as Dbg;

class MssqlAdapter
{

    private $dump = [];

    /**
     *
     * @var float 
     */
    private $time = 0;

    public function __construct()
    {
        $that = $this;
        SQLEvent::instance()->bind(SQLEvent::ON_BEFORE_PROC_EXECUTE, function($conn)
        {
            if (Dbg::$productionMode === Dbg::DEVELOPMENT) {
                Dbg::timer('mssql_execute_time');
            }
        });
        SQLEvent::instance()->bind(SQLEvent::ON_AFTER_PROC_EXECUTE, function($conn, $stmt) use($that)
        {
            if (Dbg::$productionMode === Dbg::DEVELOPMENT) {
                $casdb = (float)number_format(Dbg::timer('mssql_execute_time') * 1000, 3, '.', '');
                $connection = array(
                    'DB' => $conn->getDbName(),
                    'NAME' => $conn->getName(),
                    'IN' => $conn->getInputsConfig(),
                    'OUT' => $conn->getOutputs(),
                    'TIME' => $casdb,
                    'RESULT' => sqlsrv_num_rows($stmt)
                );
                $that->addDump($connection);
                $that->addTime($casdb);
            }
        });
        SQLEvent::instance()->bind(SQLEvent::ON_ERROR_PROC_EXECUTE, function($conn, $error) use($that)
        {
            if (Dbg::$productionMode === Dbg::DEVELOPMENT) {
                throw new \Exception($error[0]['message'], $error[0]['code']);
            }
        });
    }

    public function addDump(array $element): self
    {
        $this->dump[] = $element;
        return $this;
    }

    public function getDump(): array
    {
        return $this->dump;
    }

    public function setDump(array $dump): self
    {
        $this->dump = $dump;
        return $this;
    }

    public function getCountConn(): int
    {
        return count($this->dump);
    }

    public function addTime(float $delta): self
    {
        $this->time += $delta;
        return $this;
    }

    public function getTime(): float
    {
        return $this->time;
    }

}
