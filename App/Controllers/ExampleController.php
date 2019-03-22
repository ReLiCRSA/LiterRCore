<?php

namespace App\Controllers;

/**
 * Class ExampleController
 * @package App\Controllers
 */
class ExampleController extends Controller
{
    /**
     * Example Controller Method
     *
     * @return string
     */
    public function example()
    {
        $exampleText = "Welcome to Lite-R Core";
        return $this->view->getPage('example', compact('exampleText'));
    }
}
