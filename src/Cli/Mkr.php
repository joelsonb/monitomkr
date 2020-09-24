<?php
namespace MonitoMkr\Cli;

use \MonitoLib\App;
use \MonitoLib\Functions;
use \MonitoLib\Exception\BadRequest;

class Mkr extends \MonitoLib\Mcl\Controller
{
    const VERSION = '1.0.0';

    public function create()
    {
        $objectName        = $this->request->getParam('objectName')->getValue();
        $connectionName    = $this->request->getOption('connection-name')->getValue();
        $tables            = $this->request->getOption('tables')->getValue() ?? [];
        $columns           = $this->request->getOption('columns')->getValue() ?? [];
        $namespace         = $this->request->getOption('namespace')->getValue() ?? 'App';
        $url               = $this->request->getOption('url')->getValue();
        $controllerMethods = $this->request->getOption('controller-methods')->getValue() ?? false;

        $options                    = new \stdClass();
        $options->connectionName    = $connectionName;
        $options->tables            = $tables;
        $options->columns           = $columns;
        $options->namespace         = $namespace;
        $options->objectName        = $objectName;
        $options->url               = $url;
        $options->controllerMethods = $controllerMethods;

        // Importa as tabelas do banco
        $options->tablesList = $this->importTables($options);

        // Gera os arquivos
        $this->generate($options);
    }

    // mkr:import-tables {connectionName} --to-file --tables=fer* --generate-files --columns=id,nome,active --include-required-columns
    public function importTables($options)
    {
        $connectionName = $options->connectionName;
        // Define a conexão que será usada
        \MonitoLib\Database\Connector::setConnectionName($options->connectionName);
        $connection = \MonitoLib\Database\Connector::getConnection();
        $tables = $options->tables;

        $databaseName = $connection->getDatabase();

        $dbms  = $connection->getType();
        $class = '\MonitoMkr\Dao\\' . $dbms;

        $tables = [];

        if (!is_null($options->tables)) {
            if (!is_array($options->tables)) {
                $tables = explode(',', $options->tables);
            }
        }

        $database  = new $class($connection);
        $tableList = $database->listTables($databaseName, $tables);

        if (empty($tableList)) {
            throw new BadRequest('Nenhuma tabela encontrada!');
        }

        $tableCount = count($tableList);

        // Conta as tabelas
        if ($tableCount > 10) {
            if (!$this->question("Foram listadas $tableCount tabelas. Deseja continuar (y/N)?", false)) {
                exit;
            }
        }

        $defaults = $database->getDefaults();

        $json = [];

        // Lista as tabelas
        foreach ($tableList as $table) {
            $tableName  = $table->tableName;
            $tableType  = $table->tableType;
            $className  = $table->className;
            $objectName = $table->objectName;

            $t = [
                'name'       => $tableName,
                'namespace'  => $options->namespace,
                'type'       => $tableType,
                'class'      => $className,
                'object'     => $objectName,
                'url'        => $options->url,
                'dbms'       => $connection->getType(),
                'connection' => $connectionName,
            ];

            $tableDefaults  = $defaults['table'];
            $columnDefaults = $defaults['column'];

            // \MonitoLib\Dev::pr($t);
            // \MonitoLib\Dev::pre($tableDefaults);

            // $t = array_diff($t, $tableDefaults);

            // \MonitoLib\Dev::pre($t);
            $columns = [];

            if (!is_null($options->columns)) {
                if (!is_array($options->columns)) {
                    $columns[] = $options->columns;
                }
            }

            $columnList = $database->listColumns($databaseName, $tableName, $columns);

            // \MonitoLib\Dev::pre($columnList);

            foreach ($columnList as $column) {
                // \MonitoLib\Dev::pre($column);
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
                $source     = $column->source;
                $foreign    = $column->foreign;
                $active     = $column->active;

                // \MonitoLib\Dev::vd($column);

                $c = [
                    'name'       => $name,
                    'object'     => Functions::toLowerCamelCase($name),
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
                    'source'     => $source,
                    'foreign'    => $foreign,
                    'active'     => $active,
                ];

                // \MonitoLib\Dev::vd($c);
                // \MonitoLib\Dev::e("columnDefaults:\n");
                // \MonitoLib\Dev::pre($columnDefaults);

                // $c = array_diff_assoc($c, $columnDefaults);

                // \MonitoLib\Dev::pre($c);

                $t['columns'][Functions::toLowerCamelCase($name)] = $c;

            }
            $json[] = $t;
        }

        // \MonitoLib\Dev::pre($json);
        return $json;
    }
    public function generate($options)
    {
        // $path = App::getStoragePath('MonitoMkr/' . $options->namespace);
        $database   = new \MonitoMkr\Lib\Database();
        $controller = new \MonitoMkr\Lib\Controller();
        $dao        = new \MonitoMkr\Lib\Dao();
        $dto        = new \MonitoMkr\Lib\Dto();
        $model      = new \MonitoMkr\Lib\Model();
        $postman    = new \MonitoMkr\Lib\Postman();
        $route      = new \MonitoMkr\Lib\Route();

        // $table->default = $defaults;
        // $table = Functions AS Functions::arrayMergeRecursive($tableDefaults, $table);

        // \MonitoLib\Dev::vde($database);
        // \MonitoLib\Dev::pre($options);

        foreach ($options->tablesList as $table) {
            echo 'gerando tabela ' . $table['name'] . '...';

            $objectName = $table['object'];
            $className  = $table['class'];
            $namespace  = $table['namespace'];

            if (is_null($table['url'])) {
                $table['url'] = strtolower(str_replace('\\', '/', $table['url'] ?? $table['namespace'])) . '/' . str_replace('_', '-', $table['name']);
            }

            // \MonitoLib\Dev::pre($table);

            $outpath = App::getDocumentRoot() . 'src/' . str_replace('\\', '/', $table['namespace']) . App::DS;

            // \MonitoLib\Dev::pre($table['name']);
            // Mescla os padrões da coluna do sistema com os padrão do arquivo de padrões
            // $columnDefaults = Functions AS Functions::arrayMergeRecursive($database->columnDefaults(), $columnDefaults);
            $columnDefaults = $database->columnDefaults();



            // Verifica se o controller será gerado
            // if ($generatesController) {
                $file   = App::createPath($outpath . 'Controller/') . $className . '.php';
                // \MonitoLib\Dev::e($file);
                // Verifica se o arquivo já existe
                if (file_exists($file)) {
                    echo "o arquivo ja existe\n";
                } else {
                    $f = $controller->create($options, $table);
                    file_put_contents($file, $f);
                }
            // }

            $string = $dao->create($table);
            $file   = App::createPath($outpath . 'Dao/') . $className . '.php';
            file_put_contents($file, $string);

            $string = $dto->create($table);
            $file   = App::createPath($outpath . 'Dto/') . $className . '.php';
            file_put_contents($file, $string);

            $string = $model->create($table);
            $file   = App::createPath($outpath . 'Model/') . $className . '.php';
            file_put_contents($file, $string);

            $string = $route->create($table);
            $file   = App::getRoutesPath() . str_replace('/', '.', $table['url']) . '.php';
            file_put_contents($file, $string);
            // \MonitoLib\Dev::ee($file);
            // \MonitoLib\Dev::ee($string);

            $string = $postman->create($table);
            $file   = App::createPath(App::getDocumentRoot() . 'test/Postman') . '/' . str_replace('\\', '_', $table['namespace']) . '_' . $className . '.json';
            // \MonitoLib\Dev::ee($file);
            file_put_contents($file, $string);
            // \MonitoLib\Dev::ee($string);

            echo "ok\n";


            // \MonitoLib\Dev::ee($f);
            // \MonitoLib\Dev::pre($table);
        }

        // Verifica se existe um arquivo de configuração padrão
        // if (!file_exists($path . 'default.json')) {
        //     throw new BadRequest('Não há um arquivo de configuração para a conexão!');
        // }

        // $defaults = json_decode(file_get_contents($path . 'default.json'), true);

        // $tableDefaults  = $defaults['table'];
        // $columnDefaults = $defaults['column'];





        // \MonitoLib\Dev::pr($table);

        // \MonitoLib\Dev::pre($table);

        // $outpath = App::getDocumentRoot() . 'src/' . $table['namespace'] . App::DS;

        // \MonitoLib\Dev::pre($outpath);
        $table['output'] = App::getDocumentRoot();




        // Verifica se o arquivo já existe
        // if (!file_exists($file)) {
        //     // $dao->create($table);
        // }

        // $dto->create($table);

        // $model->create($table);
        // }
        // if (($generates & 2) === 2) {
            // $route->create($table);
        // }
        // if (($generates & 1) === 1) {
            // $postman->create($table);
        // }


        echo "processo concluido\n";
    }
}
