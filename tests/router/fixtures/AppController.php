<?php
namespace kuiper\web\router\fixtures;

use kuiper\web\annotation\route\RoutePrefix;
use kuiper\web\annotation\route\Route;
use kuiper\web\annotation\route\Get;

/**
 * @RoutePrefix("/app")
 * @Route("/{action}", methods={"GET"})
 */
class AppController
{
    /**
     * @Get("[/]")
     */
    public function indexAction()
    {
    }

    public function listAction($id)
    {
    }

    public function editAction($id)
    {
    }
}
