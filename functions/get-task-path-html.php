<?php
    function get_task_path_html (
        $na_task_id,
        $task_id,
        $all_tasks_list
    ) {
        $id = $task_id;
        $reversed_task_path_item_list = [];
        while (
            $id !== $na_task_id
                && isset($all_tasks_list[$id])
        ) {
            $reversed_task_path_item_list[]
                = $all_tasks_list[$id]['name'];
            $id
                = $all_tasks_list[$id]['parent-task-id'];
        }
        $reversed_task_path_item_list[]
            = $id === $na_task_id
                # There exists the parent task
                #   of the task with the last ID
                #   found, and it is task "(NA)".
                ? '(NA)'
                # There doesn't exist the parent
                #   task of the task with the last
                #   ID found.
                : "({$id})";
        $task_path_item_list
            = array_reverse(
                $reversed_task_path_item_list
            );
        $task_path
            = implode(
                ' / ',
                $task_path_item_list
            );
        return
            htmlspecialchars_with_ent_quotes(
                $task_path
            );
    }
?>
