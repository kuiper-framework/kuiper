<?php
namespace kuiper\web\router\fixtures;

use kuiper\web\annotation\route\RoutePrefix;
use kuiper\web\annotation\route\Get;
use kuiper\web\annotation\route\Post;

/**
 * @RoutePrefix("/user")
 */
class UserController
{
    /**
     * @Get("[/]", name="user_home")
     */
    public function indexAction()
    {
    }

    public function viewAction($id)
    {
    }

    /**
     * @Post("/edit/{id:[0-9]+}", name="user_edit")
     */
    public function editAction($id)
    {
    }
}
