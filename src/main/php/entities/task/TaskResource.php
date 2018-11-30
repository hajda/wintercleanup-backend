<?php

namespace Rocksfort\WinterCleanup\entities\task;


use Rocksfort\WinterCleanup\entities\EntityResource;
use Slim\Container;

class TaskResource extends EntityResource
{
    public function __construct(Container $ci)
    {
        parent::__construct($ci, TaskRepository::class, TaskModel::class);
    }
}
