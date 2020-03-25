<?php
namespace MonitoMkr\Dao;

use \MonitoLib\Functions;

class MySQL extends \MonitoLib\Database\Dao\MySQL
{
    public function getDefaults()
    {
        return [
            'table' => [
                'type' => 'table'
            ],
            'column' => [
                'type'       => 'string',
                'format'     => null,
                'default'    => null,
                'maxLength'  => 0,
                'precision'  => null,
                'scale'      => null,
                'collation'  => 'utf8_general_ci',
                'charset'    => 'utf8',
                'primary'    => 0,
                'required'   => 0,
                'binary'     => 0,
                'unsigned'   => 1,
                'unique'     => 0,
                'zerofilled' => 0,
                'auto'       => 0,
                'source'     => null,
                'foreign'    => 0,
                'active'     => 1
            ],
            'exceptions' => [
                'column' => [
                    'name' => [
                        'id' => [
                            'type' => 'int'
                        ],
                        'dtalt' => [
                            'format'  => 'Y-m-d H:i:s',
                            'default' => '{CURRENT_DATETIME}'
                        ],
                        'dtinc' => [
                            'format'  => 'Y-m-d H:i:s',
                            'default' => '{CURRENT_DATETIME}'
                        ],
                        'usralt' => [
                            'default' => '{CURRENT_USER}'
                        ],
                        'usrinc' => [
                            'default' => '{CURRENT_USER}'
                        ]
                    ],
                    'type' => [
                        'date' => [
                            'format' => 'Y-m-d'
                        ]
                    ]
                ]
            ],
        ];
    }
    public function listColumns($database, $tableName, $columns)
    {
        $sql = "SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '$database' AND TABLE_NAME = '$tableName'";
        
        $columns = is_null($columns) ? [] : $columns;

        if (count($columns) > 0) {
            $sql .= ' AND COLUMN_NAME IN (';

            foreach ($columns as $column) {
                $sql .= "'$column',";
            }

            $sql = substr($sql, 0, -1) . ')';
        }

        $sth = $this->parse($sql);
        $sth->execute();

        $columns = $sth->fetchAll(\PDO::FETCH_ASSOC);
    
        $data = [];
        
        $database = new \MonitoMkr\Lib\Database();

        foreach ($columns as $c) {
            // \MonitoLib\Dev::pre($c);

            $dataType = $c['DATA_TYPE'];
            switch ($dataType) {
                case 'char':
                    $type = 'char';
                    break;
                case 'int':
                case 'bigint':
                case 'smallint':
                case 'tinyint':
                    $type = 'int';
                    break;
                case 'decimal';
                case 'double';
                case 'float';
                    $type = 'double';
                    break;
                case 'timestamp';
                    $type = 'datetime';
                    break;
                default:
                    $type = 'string';
            }
            
            $columnName       = $c['COLUMN_NAME'];
            $columnType       = $type;
            $columnFormat     = null;
            $columnLabel      = $database->labelIt($c['COLUMN_NAME']);
            $columnDataType   = $c['DATA_TYPE'];
            $columnDefault    = $c['COLUMN_DEFAULT'] == '' ? null : $c['COLUMN_DEFAULT'];
            $columnMaxLength  = is_null($c['CHARACTER_MAXIMUM_LENGTH']) ? $c['NUMERIC_PRECISION'] : $c['CHARACTER_MAXIMUM_LENGTH'];
            $columnPrecision  = $c['NUMERIC_PRECISION'];
            $columnScale      = $c['NUMERIC_SCALE'];
            $columnCollation  = $c['COLLATION_NAME'];
            $columnCharset    = $c['CHARACTER_SET_NAME'];
            $columnPrimary    = $c['COLUMN_KEY'] == 'PRI' ? 1 : 0;
            $columnRequired   = $c['IS_NULLABLE'] == 'YES' ? 0 : 1;
            $columnBinary     = strpos($c['COLLATION_NAME'], '_bin') === false ? 0 : 1;
            $columnUnsigned   = strpos($c['COLUMN_TYPE'], 'unsigned') === false ? 0 : 1;
            $columnUnique     = $c['COLUMN_KEY'] == 'UNI' ? 1 : 0;
            $columnZerofilled = strpos($c['COLUMN_TYPE'], 'zerofill') === false ? 0 : 1;
            $columnAuto       = $c['EXTRA'] == 'auto_increment' ? 1 : 0;
            $columnForeign    = $c['COLUMN_KEY'] == 'MUL' ? 1 : 0;
            $columnActive     = 1;
            $tableName        = $c['TABLE_NAME'];

            if ($type === 'datetime') {
                $columnFormat = 'Y-m-d H:i:s';
            }

            if ($type === 'time') {
                $columnFormat = 'H:i:s';
            }

            $columnDto             = new \stdClass;
            $columnDto->name       = $columnName;
            $columnDto->type       = $columnType;
            $columnDto->format     = $columnFormat;
            $columnDto->label      = $columnLabel;
            $columnDto->default    = $columnDefault;
            $columnDto->maxLength  = $columnMaxLength;
            $columnDto->precision  = $columnPrecision;
            $columnDto->scale      = $columnScale;
            $columnDto->collation  = $columnCollation;
            $columnDto->charset    = $columnCharset;
            $columnDto->primary    = $columnPrimary;
            $columnDto->required   = $columnRequired;
            $columnDto->binary     = $columnBinary;
            $columnDto->unsigned   = $columnUnsigned;
            $columnDto->unique     = $columnUnique;
            $columnDto->zerofilled = $columnZerofilled;
            $columnDto->auto       = $columnAuto;
            $columnDto->foreign    = $columnForeign;
            $columnDto->active     = $columnActive;

            $data[] = $columnDto;
        }
        
        return $data;
    }
    public function listRelations($database, $tableName = NULL)
    {
        $sql = 'SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ?';

        if (!is_null($tableName)) {
            $sql .= ' AND TABLE_NAME ';

            if (is_array($tableName)) {
                $tableName  = "'" . implode("','", $tableName) . "'";
                $sql       .= "IN ($tableName)";
                $tableName  = NULL;
            } else {
                $sql .= '= ?';
            }
        }

        $sth = $this->conn->prepare($sql);
        $sth->bindParam(1, $this->dbName);

        if (!is_null($tableName)) {
            $sth->bindParam(2, $tableName);
        }

        $sth->execute();

        $relations = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $data = [];

        foreach ($relations as $r) {
            if (!is_null($r['REFERENCED_TABLE_NAME'])) {
                $data[] = [
                    'tableNameSource'       => $r['TABLE_NAME'],
                    'columnNameSource'      => $r['COLUMN_NAME'],
                    'tableNameDestination'  => $r['REFERENCED_TABLE_NAME'],
                    'columnNameDestination' => $r['REFERENCED_COLUMN_NAME'],
                    'sequence'              => $r['ORDINAL_POSITION'],
                ];
            }
        }

        return $data;
    }
    public function listTables($databaseName, $tableName)
    {
        $sql = "SELECT * FROM information_schema.TABLES WHERE TABLE_SCHEMA <> 'information_schema' AND "
            . "TABLE_SCHEMA = '$databaseName' AND table_name = '$tableName'";

        // foreach ($databases as $databaseName => $tables) {

        //     if (count((array)$tables) > 0) {
        //         $sql .= " AND TABLE_NAME IN (";

        //         foreach ($tables as $tableName => $columns) {
        //             $sql .= "'$tableName',";
        //         }

        //         $sql = substr($sql, 0, -1) . ')';
        //     }

        //     $sql .= ' OR (';
        // }
        
        // $sql = substr($sql, 0, -5) . ')';

        // \MonitoLib\Dev::ee($sql);

        $stt = $this->parse($sql);
        $stt->execute();

        $data = [];

        $database = new \MonitoMkr\Lib\Database;

        while ($r = $stt->fetch(\PDO::FETCH_ASSOC)) {
            $data[] = $database->table($r);
        }

        // \MonitoLib\Dev::pre($data);

        return $data;
    }
    public function listTablesAndColumns($tableName = null, $columns = null)
    {
        $sql = 'SELECT * FROM information_schema.TABLES t '
            . 'INNER JOIN information_schema.COLUMNS c ON t.table_schema = c.table_schema AND t.table_name = c.table_name '
            . 'WHERE t.TABLE_SCHEMA = ? ';
        if (!is_null($tableName)) {
            $sql .= 'AND t.TABLE_NAME IN (' . \MonitoCli\Database\Helper::serialize($tableName) . ') ';
        }
        $sql .= 'ORDER BY t.TABLE_NAME, c.ORDINAL_POSITION';
        $sth = $this->connection->prepare($sql);

        // \MonitoLib\Dev::ee($sql);

        $sth->bindParam(1, $this->config->database);
        $sth->execute();

        $res = $sth->fetchAll(\PDO::FETCH_ASSOC);

        // \MonitoLib\Dev::pre($res);

        $data = [];
        $currentTable = null;

        foreach ($res as $r) {
            if ($currentTable !== $r['TABLE_NAME']) {
                $tableDto = new \MonitoCli\Database\Dto\Table;
                $tableDto->setTableName($tableName);

                if ($r['TABLE_TYPE'] === 'VIEW') {
                    $tableDto->setTableType('view');
                } else {
                    $tableDto->setTableType('table');
                }

                $data[] = \MonitoCli\Database\Helper::table($tableDto);
            }

            $columnDto = new \MonitoCli\Database\Dto\Column;
            $columnDto->setTable($r['TABLE_NAME']);
            $columnDto->setName($r['COLUMN_NAME']);
            // $columnDto->setType($column['type']);
            // $columnDto->setLabel($column['label']);
            $columnDto->setDatatype($r['DATA_TYPE']);
            $columnDto->setDefaultvalue($r['COLUMN_DEFAULT']);
            // $columnDto->setMaxlength($column['maxLength']);
            // $columnDto->setNumericprecision($column['numericPrecision']);
            // $columnDto->setNumericscale($column['numericScale']);
            // $columnDto->setCollation($column['collation']);
            // $columnDto->setCharset($column['charset']);
            $columnDto->setIsprimary($r['COLUMN_KEY'] == 'PRI' ? 1 : 0);
            $columnDto->setIsrequired($r['IS_NULLABLE'] === 'YES' ? 0 : 1);
            // $columnDto->setIsbinary($column['isBinary']);
            // $columnDto->setIsunsigned($column['isUnsigned']);
            // $columnDto->setIsunique($column['isUnique']);
            // $columnDto->setIszerofilled($column['isZerofilled']);
            $columnDto->setIsauto($r['EXTRA'] == 'auto_increment' ? 1 : 0);
            // $columnDto->setIsforeign($column['isForeign']);
            $tableDto->addColumn($columnDto);

            $currentTable = $r['TABLE_NAME'];
        }

        return $data;
    }
    public function load()
    {
        // Loads tables
        $this->loadTables();

        // Loads columns
        $this->loadColumns();

        // Loads relations
        $this->loadRelations();
    }
    private function loadColumns()
    {
        $columns = $this->listColumns($this->connection->getDbName(), $this->tables);

        $data = [];
        
        foreach ($columns as $c)
        {
            $columnName          = $c['COLUMN_NAME'];
            $columnType          = NULL;
            $columnLabel         = $this->labelIt($c['COLUMN_NAME']);
            $columnDataType      = $c['DATA_TYPE'];
            $columnDefault       = $c['COLUMN_DEFAULT'] == '' ? NULL : $c['COLUMN_DEFAULT'];
            $columnMaxLength     = is_null($c['CHARACTER_MAXIMUM_LENGTH']) ? $c['NUMERIC_PRECISION'] : $c['CHARACTER_MAXIMUM_LENGTH'];
            $columnPrecisionSize = $c['NUMERIC_PRECISION'];
            $columnScale         = $c['NUMERIC_SCALE'];
            $columnCollation     = $c['COLLATION_NAME'];
            $columnCharset       = $c['CHARACTER_SET_NAME'];
            $columnIsPrimary     = $c['COLUMN_KEY'] == 'PRI' ? 1 : 0;
            $columnIsRequired    = $c['IS_NULLABLE'] == 'YES' ? 0 : 1;
            $columnIsBinary      = strpos($c['COLLATION_NAME'], '_bin') !== FALSE ? 0 : 1;
            $columnIsUnsigned    = strpos($c['COLUMN_TYPE'], 'unsigned') !== FALSE ? 0 : 1;
            $columnIsUnique      = $c['COLUMN_KEY'] == 'UNI' ? 1 : 0;
            $columnIsZerofilled  = strpos($c['COLUMN_TYPE'], 'zerofill') !== FALSE ? 0 : 1;
            $columnIsAuto        = $c['EXTRA'] == 'auto_increment' ? 1 : 0;
            $columnIsForeign     = $c['COLUMN_KEY'] == 'MUL' ? 1 : 0;
            $columnActive        = 1;
            $tableName           = $c['TABLE_NAME'];

            //$tableDao    = \dao\Factory::createTable();
            //$tableObject = $tableDao->getByName($tableName);
    
            //if (is_null($tableObject))
            //{
            //  throw new \Exception("Table $tableName not found!");
            //}

            $columnDao = \dao\Factory::createColumn();
            $columnDto = new \model\Column;
            //$columnDto->setTableId($tableObject->getId());
            $columnDto->setName($columnName);
            $columnDto->setType($columnType);
            $columnDto->setLabel($columnLabel);
            $columnDto->setDataType($columnDataType);
            $columnDto->setDefaultValue($columnDefault);
            $columnDto->setMaxLength($columnMaxLength);
            $columnDto->setNumericPrecision($columnPrecisionSize);
            $columnDto->setNumericScale($columnScale);
            $columnDto->setCollation($columnCollation);
            $columnDto->setCharset($columnCharset);
            $columnDto->setIsPrimary($columnIsPrimary);
            $columnDto->setIsRequired($columnIsRequired);
            $columnDto->setIsBinary($columnIsBinary);
            $columnDto->setIsUnsigned($columnIsUnsigned);
            $columnDto->setIsUnique($columnIsUnique);
            $columnDto->setIsZerofilled($columnIsZerofilled);
            $columnDto->setIsAuto($columnIsAuto);
            $columnDto->setIsForeign($columnIsForeign);
            $columnDto->setActive($columnActive);

            //$columnDao    = \dao\Factory::createColumn();
            //$columnObject = $columnDao->getByName($tableObject->getId(), $columnName);
            //
            //if (is_null($columnObject))
            //{
            //  $columnDao->insert($columnModel);
            //}
            //else
            //{
            //  $columnModel->setId($columnObject->getId());
            //  $columnDao->update($columnModel);
            //}
            $data[] = $columnDto;
        }
        
        return $data;
    }
    public function listConstraints($database, $tableName, $columName = null)
    {
        $sql = 'SELECT t.table_schema, t.table_name, t.constraint_name, SUBSTR(t.constraint_type, 1, 1) AS constraint_type, c.column_name, c.ordinal_position, c.referenced_table_schema, '
            . 'c.referenced_table_name, c.referenced_column_name FROM information_schema.table_constraints t '
            . 'INNER JOIN information_schema.key_column_usage c ON t.table_schema = c.table_schema AND t.table_name = c.table_name '
            . "AND t.constraint_name = c.constraint_name WHERE t.table_schema = '{$database}' AND t.table_name = '{$tableName}' " . (is_null($columName) ? '' : "AND c.column_name = '{$columName}' ")
            . 'ORDER BY t.constraint_type, c.ordinal_position';
        // \MonitoLib\Dev::e("$sql\n\n");
        $stt = $this->connection->parse($sql);
        $stt->execute();

        $data = [];

        while ($r = $stt->fetch(\PDO::FETCH_ASSOC)) {
            $constraint = new \stdClass;
            $constraint->tableSchema            = $r['table_schema'];
            $constraint->tableName              = $r['table_name'];
            $constraint->constraintName         = $r['constraint_name'];
            $constraint->constraintType         = $r['constraint_type'];
            $constraint->columnName             = $r['column_name'];
            $constraint->ordinalPosition        = $r['ordinal_position'];
            $constraint->referencedTableSchema  = $r['referenced_table_schema'];
            $constraint->referencedTableName    = $r['referenced_table_name'];
            $constraint->referencedColumnName   = $r['referenced_column_name'];
            $constraint->referencedColumnObject = Functions::toLowerCamelCase($r['referenced_column_name']);
            $data[] = $constraint;
        }

        return $data;
    }
}