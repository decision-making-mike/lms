<?php
    return [
        'task-file-path'
            => getenv('LMS_TASK_FILE_PATH')
                !== false
                ? getenv('LMS_TASK_FILE_PATH')
                : 'testing/data/tasks.txt'
    ];
?>
