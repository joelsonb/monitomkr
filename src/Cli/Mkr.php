<?php
namespace MonitoMkr\Cli;

use \MonitoLib\App;
use \MonitoLib\Functions;
use \MonitoLib\Exception\BadRequest;

class Mkr extends \MonitoLib\Mcl\Controller
{
    const VERSION = '1.0.0';

    // mkr:import-tables {connectionName} --to-file --tables=fer* --generate-files --columns=id,nome,active --include-required-columns
    public function importTables()
    {
        // Busca a conexão
        $connectionName = $this->request->getParam('connectionName')->getValue();
        $tables = $this->request->getOption('tables')->getValue();

        // \MonitoLib\Dev::vde($connectionName);

        // \MonitoLib\Dev::pre($this->request);

        // Conecta na conexão informada
        // $connector = \MonitoLib\Database\Connector::getInstance();
        // $connector->setConnection($connectionName);

        // Define a conexão que será usada
        $connector = \MonitoLib\Database\Connector::getInstance();
        $connector->setConnectionName($connectionName);
        $connection = $connector->getConnection();

        // \MonitoLib\Dev::pre($connection);

        $dbms = $connection->getDbms();

        $databaseName = null;

        $databaseName = $connection->getDatabase();

        // if ($dbms === 'MySQL') {
            $databaseName = $connection->getDatabase();
        // }

        $class = '\MonitoMkr\dao\\' . $dbms;

        $database  = new $class($connection);
        $tableList = $database->listTables($databaseName, $tables);

        // \MonitoLib\Dev::pre($tableList);

        $tableCount = count($tableList);

        // Conta as tabelas
        if ($tableCount > 10) {
            \MonitoLib\Dev::e($this->question("Há $tableCount tabelas na conexão! Deseja importar todas?", false));
            // if ($x = $this->question("Há $tableCount tabelas na conexão! Deseja importar todas?", false)) {
            // }
        }

        if (empty($tableList)) {
            throw new BadRequest('Nenhuma tabela encontrada!');
        }

        $path = App::getStoragePath('MonitoMkr/' . $connectionName);

        $defaults = $database->getDefaults();

        file_put_contents($path . 'default.json', json_encode($defaults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK));

        // Gera o arquivo com os padrões da conexão
        // \MonitoLib\Dev::ee("já caminho: $x\n");

        // Lista as tabelas
        foreach ($tableList as $table) {
            $tableName  = $table->tableName;
            $tableType  = $table->tableType;
            $className  = $table->className;
            $objectName = $table->objectName;

            $t = [
                'name'       => $tableName,
                'namespace'  => 'App',
                'type'       => $tableType,
                'class'      => $className,
                'object'     => $objectName,
                'url'        => "/wms/os",
                'dbms'       => $connection->getDbms(),
                'connection' => $connectionName,
            ];

            $tableDefaults  = $defaults['table'];
            $columnDefaults = $defaults['column'];

            // \MonitoLib\Dev::pr($t);
            // \MonitoLib\Dev::pre($tableDefaults);

            $json = array_diff($t, $tableDefaults);

            // \MonitoLib\Dev::pre($json);

            $columns      = null;

            $columnList = $database->listColumns($databaseName, $tableName, $columns);

            foreach ($columnList as $column) {
                $name       = $column->name;
                $type       = $column->type;
                $format     = $column->format;
                $label      = $column->label;
                $default    = $column->default;
                $maxLength  = $column->maxLength;
                $precision  = $column->precision;
                $scale      = $column->scale;
                $collation  = $column->collation;
                $charset    = $column->charset;
                $primary    = $column->primary;
                $required   = $column->required;
                $binary     = $column->binary;
                $unsigned   = $column->unsigned;
                $unique     = $column->unique;
                $zerofilled = $column->zerofilled;
                $auto       = $column->auto;
                $foreign    = $column->foreign;
                $active     = $column->active;

                // \MonitoLib\Dev::vd($column);

                $c = [
                    'name'       => $name,
                    'type'       => $type,
                    'format'     => $format,
                    'label'      => $label,
                    'default'    => $default,
                    'maxLength'  => $maxLength,
                    'precision'  => $precision,
                    'scale'      => $scale,
                    'collation'  => $collation,
                    'charset'    => $charset,
                    'primary'    => $primary,
                    'required'   => $required,
                    'binary'     => $binary,
                    'unsigned'   => $unsigned,
                    'unique'     => $unique,
                    'zerofilled' => $zerofilled,
                    'auto'       => $auto,
                    'foreign'    => $foreign,
                    'active'     => $active,
                ];

                // \MonitoLib\Dev::vd($c);
                // \MonitoLib\Dev::e("columnDefaults:\n");
                // \MonitoLib\Dev::vd($columnDefaults);

                $c = array_diff_assoc($c, $columnDefaults);

                // \MonitoLib\Dev::pre($c);

                $json['columns'][Functions::toLowerCamelCase($name)] = $c;
            }

            // \MonitoLib\Dev::pre($json);

            file_put_contents($path . $table->tableName . '.json', json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK));
            \MonitoLib\Dev::pre($table);
            if ($this->verbose()) {
                $this->write("Importando table $tableName...");

                // Busca as colunas da tabela
                $this->writeBreakline('ok');
            }
        }
    }
    public function generate()
    {
        $connectionName = $this->request->getParam('connectionName')->getValue();

        $path = App::getStoragePath('MonitoMkr/' . $connectionName);

        // Verifica se existe um arquivo de configuração padrão
        if (!file_exists($path . 'default.json')) {
            throw new BadRequest('Não há um arquivo de configuração para a conexão!');
        }

        $defaults = json_decode(file_get_contents($path . 'default.json'), true);

        $tableDefaults  = $defaults['table'];
        $columnDefaults = $defaults['column'];

        $database = new \MonitoMkr\Lib\Database();

        // Mescla os padrões da coluna do sistema com os padrão do arquivo de padrões
        $columnDefaults = Functions::arrayMergeRecursive($database->columnDefaults(), $columnDefaults);

        


        $controller = new \MonitoMkr\lib\Controller();
        $dao        = new \MonitoMkr\lib\Dao();
        $dto        = new \MonitoMkr\lib\Dto();
        $model      = new \MonitoMkr\lib\Model();
        $postman    = new \MonitoMkr\lib\Postman();
        $router     = new \MonitoMkr\lib\Router();

        $files = scandir($path);

        foreach ($files as $file) {
            if (!in_array($file, ['.', '..', 'default.json'])) {
                echo $path . '/' . $file . "\n";

                $table = json_decode(file_get_contents($path . '/' . $file), true);

                // $table->default = $defaults;
                $table = Functions::arrayMergeRecursive($tableDefaults, $table);

                // \MonitoLib\Dev::pr($table);

                // Mescla as opções das colunas com os valores padrão do arquivo default.json
                $table['columns'] = array_map(function ($item) use ($columnDefaults) {
                    return Functions::arrayMergeRecursive($columnDefaults, $item);
                }, $table['columns']);

                // \MonitoLib\Dev::pre($table);

                // $outpath = App::getDocumentRoot() . 'src/' . $table['namespace'] . App::DS;

                // \MonitoLib\Dev::pre($outpath);
                $table['output'] = App::getDocumentRoot();

                // if (($generates & 32) === 32) {
                    $controller->create($table);
                // }
                // if (($generates & 16) === 16) {
                    $dao->create($table);
                // }
                // if (($generates & 8) === 8) {
                    $dto->create($table);
                // }
                // if (($generates & 4) === 4) {
                    $model->create($table);
                // }
                // if (($generates & 2) === 2) {
                    // $router->create($table);
                // }
                // if (($generates & 1) === 1) {
                    // $postman->create($table);
                // }
            }
        }

        \MonitoLib\Dev::pre('ok');
    }
}
