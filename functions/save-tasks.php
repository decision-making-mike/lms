<?php
    function save_tasks (
        $task_file_path,
        $task_list
    ) {
        $line_list = [];
        foreach (
            $task_list
                as $task_id => $task_details
        ) {
            $line
                = implode(
                    "\t",
                    [
                        $task_id,
                        ...array_values(
                            $task_details
                        )
                    ]
                );
            $line_list[] = $line;
        }
        file_put_contents(
            $task_file_path,
            implode("\n", $line_list)
        );
    }
?>
