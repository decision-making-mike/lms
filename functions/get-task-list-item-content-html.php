<?php
    function get_task_list_item_content_html (
        $na_task_id,
        $base_url,
        $all_tasks_list,
        $task_id,
        $path_showing
    ) {
        $query
            = http_build_query(
                [
                    'task-id' => $task_id
                ]
            );
        $task_view_url = "{$base_url}?{$query}";
        $task_view_url_html
            = htmlspecialchars_with_ent_quotes(
                $task_view_url
            );

        $task_status_html
            = $task_id === $na_task_id
                ? '(NA)'
                : htmlspecialchars_with_ent_quotes(
                    $all_tasks_list[$task_id]['status']
                );

        # Get the information whether any
        #   of the child tasks of the task
        #   is pending (recurse to the leaf
        #   tasks).
        $child_task_pending_information_html = '';
        $pending_task_id_list
            = [
                $task_id
            ];
        while (
            count($pending_task_id_list) !== 0
        ) {
            $p = array_pop($pending_task_id_list);

            $child_task_id_list = [];
            foreach (
                $all_tasks_list as $pp => $details
            ) {
                # If "pp" is a child task of "p".
                if (
                    $details['parent-task-id']
                        === $p
                ) {
                    if (
                        $details['status']
                            === 'PENDING'
                    ) {
                        $child_task_pending_information_html
                            = '(CHILD TASK PENDING) ';
                        break;
                    }

                    $child_task_id_list[] = $pp;
                }
            }
            if (
                $child_task_pending_information_html
                    !== ''
            ) {
                break;
            }
            $pending_task_id_list
                = [
                    ...$pending_task_id_list,
                    ...$child_task_id_list
                ];
        }

        return
            "({$task_status_html}) "
            . $child_task_pending_information_html
            . "<a href=\"{$task_view_url_html}\">"
            . (
                $path_showing
                    ? get_task_path_html(
                        $na_task_id,
                        $task_id,
                        $all_tasks_list
                    )
                    : htmlspecialchars_with_ent_quotes(
                        $all_tasks_list[$task_id]['name']
                    )
            )
            . '</a>';
    }
?>
