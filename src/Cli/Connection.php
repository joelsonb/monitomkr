<?php
namespace MonitoMkr\Cli;

ini_set('max_execution_time', '0');
ini_set('memory_limit','4096M');

use \MonitoLib\App;
use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Exception\NotFound;

class Connection
{
    public function list ()
    {
        $file = App::getConfigPath() . 'database.json';

        if (!is_readable($file)) {
            throw new InternalError("Arquivo $file não encontrado ou usuário sem permissão!");
        }

        $db = json_decode(file_get_contents($file));
        
        if (is_null($db)) {
            throw new InternalError("O arquivo $file é inválido!");
        }

        \MonitoLib\Dev::pre($db);

        // $this->toTable();
    }
}
