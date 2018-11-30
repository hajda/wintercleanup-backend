<?php

namespace Rocksfort\WinterCleanup\entities\task;

use PDO;
use Rocksfort\WinterCleanup\entities\EntityRepository;

class TaskRepository extends EntityRepository
{
    public function __construct(PDO $databaseInstance, $modelClass)
    {
        /* init parent */
        parent::__construct($databaseInstance, $modelClass);

        /*init self */
    }

    /* GET */

    function  get($entityInstance, $query = [])
    {
        $tasks = parent::get(['id' => null], $query); // TODO: Get all tasks first
        list($taskList, $rootTasks) = $this->nest($tasks, $entityInstance);

        $id = isset($entityInstance['id']) ? $entityInstance['id'] : null;
        if ($id !== null) {
            return [$taskList[$id]];
        } else {
            return $rootTasks;
        }
    }

    private function nest($tasks) {
        $taskList = [];
        for ($i = 0; $i < count($tasks); $i++) {
            $tasks[$i]->subItems = [];
            $taskList[$tasks[$i]->id] = new TaskModel($tasks[$i]);
        }

        foreach ($taskList as $task) {
            if (isset($task->parent_task_id) && $task->parent_task_id !== null) {
                $parentId = $task->parent_task_id;
                array_push($taskList[$parentId]->subItems, $task);
            }
        }

        $rootTasks = [];
        foreach ($taskList as $task) {
            if (!isset($task->parent_task_id) || $task->parent_task_id === null) {
                array_push($rootTasks, $task);
            }
        }

        return [$taskList, $rootTasks];
    }

    /* Save */

    public function save($entityInstance)
    {
        $stretchedInstances = $this->stretch($entityInstance);
        $spreadAttributes = $this->spreadEntityInstanceAttributes($stretchedInstances);

        return $this->executeStatement(TaskModel::makePreparedBulkSaveStatement(count($entityInstance)), $spreadAttributes)->rowCount();
    }

    public function delete($entityInstance)
    {
        $stretchedInstances = $this->stretch($entityInstance);

        return $this->executeStatement(TaskModel::makePreparedBulkDeleteStatement(count($entityInstance)), $stretchedInstances)->rowCount();
    }

    /* DELETE */

    private function stretch($taskTree) {
        $taskList = [];

        for ($i = 0; $i < count($taskTree); $i++) {
            $this->addToList($taskList, $taskTree[$i]);
            unset($taskTree[$i]);
        }

        return $taskList;
    }

    private function addToList(& $taskList, & $task) {
        $originalSize = count($task['subItems']);
        if ($originalSize) {
            for ($i = 0; $i < $originalSize; $i++ ) {
                $this->addToList($taskList, $task['subItems'][$i]);
                unset($task['subItems'][$i]);
            }
        }
        array_push($taskList, $task);
    }

    /**
     * @param array $stretchedInstances
     * @return array
     */
    public function spreadEntityInstanceAttributes(array $stretchedInstances)
    {
        $stretchedAttributes = [];
        for ($i = 1; $i <= count($stretchedInstances); $i++) {
            foreach ($stretchedInstances[$i - 1] as $attribute => $value) {
                array_push($stretchedAttributes, ["$attribute$i" => $value]);
            }
        }
        return $stretchedAttributes;
    }
}
