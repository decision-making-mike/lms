<?php
    function
        get_parent_task_select_html (
            $na_task_id,
            $all_tasks_list,
            $id_of_parent_task_to_select = null
        ) {
            $parent_task_select_content_html = '';
            $parent_task_path_htmls = [];
            foreach (
                [
                    $na_task_id => null
                ]
                    + $all_tasks_list
                    as $id => $_
            ) {
                $parent_task_path_htmls[$id]
                    = get_task_path_html(
                        $na_task_id,
                        $id,
                        $all_tasks_list
                    );
            }
            asort(
                $parent_task_path_htmls,
                SORT_NATURAL | SORT_FLAG_CASE
            );
            if (
                $id_of_parent_task_to_select
                    === null
            ) {
                $parent_task_select_content_html
                    .= '<option value="null" selected>(SELECT)</option>';
            }
            foreach (
                $parent_task_path_htmls
                    as $id => $path_html
            ) {
                    $parent_task_select_content_html
                        .= '<option value="'
                            . htmlspecialchars_with_ent_quotes(
                                $id
                            )
                            . '"'
                            . (
                                $id
                                    === $id_of_parent_task_to_select
                                    ? ' selected'
                                    : ''
                            )
                            . '>'
                            . $path_html
                            . '</option>';
            }
            return "
                <select
                    id=\"new-task-parent-task-id\"
                    name=\"new-task-parent-task-id\"
                >{$parent_task_select_content_html}</select>
            ";
        }
?>
