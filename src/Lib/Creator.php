<?php
namespace MonitoMkr\Lib;

use \MonitoLib\App;

class Creator
{
    public function createFile($table, $objectType, $script)
    {
        $outpath = $table['output'];

        App::createPath($outpath);

        $object = explode('.', $objectType);

        // $filePath = App::createPath($outpath . 'src' . App::DS . str_replace('\\', '/', $table->namespace) . App::DS . $object[0]) . App::DS;
        $filePath = App::createPath($outpath . 'src/' . str_replace('\\', '/', $table['namespace']) . App::DS . ucfirst($object[0])) . App::DS;

        $fileName = $table['class'] . '.' . $object[1];

        // \MonitoLib\Dev::ee($filePath);

        file_put_contents($filePath . $fileName, $script);
    }
}