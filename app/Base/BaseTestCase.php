<?php

namespace App\Base;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

use CodeIgniter\Session\Handlers\ArrayHandler;
use CodeIgniter\Test\Mock\MockSession;
use CodeIgniter\Log\Handlers\FileHandler;
use Config\Logger;
use CodeIgniter\Test\TestLogger;
use Faker;

class BaseTestCase extends CIUnitTestCase
{
    use ControllerTestTrait;
    use DatabaseTestTrait;
    var $base;
    protected $setUpMethods = [
        'resetServices',
        'mockSession',
    ];
    public function test()
    {
        $this->assertTrue(true);
    }

    public function call_function_controller_type($type = "post", $array_request = array(), $controller = NULL, $function = "index")
    {

        $request = new \CodeIgniter\HTTP\IncomingRequest(
            new \Config\App(),
            new \CodeIgniter\HTTP\SiteURI(new \Config\App()),
            null,
            new \CodeIgniter\HTTP\UserAgent()
        );
        $request->setGlobal($type, $array_request);
        $request->setMethod($type);

        return $this->withRequest($request)->controller($controller)->execute($function);
    }

    public function call_function_controller_type_without_function($type = "post", $array_request = array(), $controller = NULL)
    {
        $request = new \CodeIgniter\HTTP\IncomingRequest(
            new \Config\App(),
            new \CodeIgniter\HTTP\SiteURI(new \Config\App()),
            null,
            new \CodeIgniter\HTTP\UserAgent()
        );
        $request->setGlobal($type, $array_request);
        $request->setMethod($type);

        return $this->withRequest($request)->controller($controller);
    }



    public function call_listar_datatable($array_columns, $r_search, $tablename, $controller, $function, $params = "")
    {
        $request["draw"] = 1;
        $request["columns"] = array();
        $dir = array("desc", "asc");
        foreach ($array_columns as $column => $type_column) {
            $faker = Faker\Factory::create();
            array_push($request["columns"], array(
                "data" => $column,
                "name" => $column,
                "searchable" => "true",
                "orderable" => "true",
                "search" => array(
                    "value" =>
                    $type_column == "date" ?
                        ($faker->date('Y-m-d') . ";" . $faker->date('Y-m-d') . ";")
                        : (
                            $type_column == "check" ?
                            rand(0, 1)
                            : $faker->word()
                        ),
                    "regex" => "false"
                )
            ));
        }
        $request["order"] = array(
            0 => array(
                "column" => rand(1, count($array_columns)),
                "dir" => $dir[rand(0, 1)]
            )
        );
        $request["start"] = "0";
        $request["length"] = "20";
        $request["search"] = $r_search;
        $request["tablename"] = $tablename;
        if (empty($params))
            $result = $this->call_function_controller_type("post", $request, $controller, $function);
        else
            $result = $this->call_function_controller_type_without_function("post", $request, $controller)->execute($function, $params);
        $res = json_decode($result->getJSON());
        return $result;
    }



    public function get_logger($name)
    {
        $logger = new Logger();
        $logger->handlers['CodeIgniter\Log\Handlers\FileHandler']['path'] = WRITEPATH . "test_logs/$name";
        return (new TestLogger($logger));
    }



    /*protected function subir_archivos($faker, $controller, $function)
    {
        $tmp_file = tempnam(WRITEPATH . "/tmp", "tmp_");
        file_put_contents($tmp_file, $faker->paragraphs());



        $_FILES['upload'] =
            [
                'name' => $faker->word() . "." . $faker->fileExtension(),
                'type' => $faker->mimeType(),
                'tmp_name' => $tmp_file,
                'error' => 0,
                'size' => filesize($tmp_file)
            ];
        $all_post["action"] = "upload";
        $result = $this->call_function_controller_type("post", $all_post, $controller, $function);
        $res = json_decode($result->getJSON());
        return array($res, $_FILES['upload']["name"]);
    }*/

    protected function uploadtmp($faker)
    {
        $tmp_file = tempnam(WRITEPATH , "tmp_");
        file_put_contents($tmp_file, $faker->paragraphs());
        return [
                'name' => $faker->word() . "." . $faker->fileExtension(),
                'type' => $faker->mimeType(),
                'tmp_name' => $tmp_file,
                'error' => 0,
                'size' => filesize($tmp_file)
            ];
    }
}
