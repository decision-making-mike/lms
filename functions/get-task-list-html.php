<?php
    function get_task_list_html (
        $na_task_id,
        $base_url,
        # It is implicitly assumed
        #   that the set of the tasks
        #   in "task_list" is a subset of the set
        #   of the tasks in the union
        #   of "all_tasks_list" and an array
        #   containing only task "(NA)".
        $task_list,
        $all_tasks_list,
        $path_showing = true
    ) {
        $task_list_content_html = '';
        foreach ($task_list as $task_id => $_) {
            $task_list_item_content_html
                = get_task_list_item_content_html(
                    $na_task_id,
                    $base_url,
                    $all_tasks_list,
                    $task_id,
                    $path_showing
                );
            $task_list_content_html
                .= "<li>{$task_list_item_content_html}</li>";
        }
        return "<ul>{$task_list_content_html}</ul>";
    }
?>
