<?php

namespace Rocksfort\WinterCleanup\entities\task;


use Rocksfort\WinterCleanup\entities\EntityModel;

class TaskModel extends EntityModel
{
    const SELECT_SQL = '
        SELECT *
        FROM `epiz_23064535_wintercleanup`.`task`
        WHERE
            `epiz_23064535_wintercleanup`.`task`.`id` = (CASE WHEN :id IS NULL THEN `epiz_23064535_wintercleanup`.`task`.`id` ELSE :id END)
        LIMIT :offset, :limit
    ';

    const SAVE_SQL = '
        INSERT INTO `epiz_23064535_wintercleanup`.`task` (`id`, `title`, `description`, `parent_task_id`)
        VALUES    (:id, :title, :description, :parent_task_id)
        ON DUPLICATE KEY UPDATE
            `id` = :id,
            `title` = :title,
            `description` = :description,
            `parent_task_id` = :parent_task_id
    ';

    const DELETE_SQL = '
        DELETE FROM `epiz_23064535_wintercleanup`.`task` WHERE `epiz_23064535_wintercleanup`.`task`.`id` = :id LIMIT 1
    ';

    /**
     * {@see EntityModel::KEY_ATTRIBUTE_SET}
     */
    protected static $keyAttributeSet = ['id'];
    // TODO protected static $validationClass = TaskValidationException::class;

    public static function makePreparedBulkSaveStatement($dimension) {
        $dimension = $dimension > 0 ? $dimension : 1;

        $values = '(:id1, :title1, :description1, :parent_task_id1)';

        for ($i = 2; $i < $dimension + 1; $i++) {
            $values .= ", (:id$i, :title$i, :description$i, :parent_task_id$i)";
        }

        $bulkSaveSql = "
            INSERT INTO `epiz_23064535_wintercleanup`.`task` (`id`, `title`, `description`, `parent_task_id`)
            VALUES $values
            ON DUPLICATE KEY UPDATE
                `id` = VALUES(`id`),
                `title` = VALUES(`title`),
                `description` = VALUES(`description`),
                `parent_task_id` = VALUES(`parent_task_id`)
        ";
    }

    public static function makePreparedBulkDeleteStatement($dimension = 1) {
        $dimension = $dimension > 0 ? $dimension : 1;

        $substitue = '';

        for ($i = 1; $i < $dimension; $i++) {
            $substitue .= ', ?';
        }

        $return = "
            DELETE FROM `epiz_23064535_wintercleanup`.`task`
            WHERE
                `epiz_23064535_wintercleanup`.`task`.`id` IN (?$substitue)
            LIMIT $dimension
        ";
    }
}