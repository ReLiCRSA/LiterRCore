<?php

namespace App\Controllers;

use Framework\View;
use Framework\request;

/**
 * Class Controller
 * @package App\Controllers
 */
class Controller
{
    /** @var request  */
    protected $request;

    /** @var View  */
    protected $view;

    /**
     * Controller constructor.
     */
    public function __construct()
    {
        $this->request = new Request();
        $this->view = new View();
    }

    public function checkLogin()
    {
        if (!$this->view->isSignedIn()) {
            $this->view->redirect("/");
        }
    }
}
