<!--
    Tell Lynx to not use cache
    (see https://lynx.invisible-island.net/lynx_help/Lynx_users_guide.html).
-->
<?php
    header('Cache-Control: no-cache');
?>
<?php
    # This function has been created solely
    #   to not have to remember
    #   which calls of "htmlspecialchars()" refer
    #   to user input (so to put the "ENT_QUOTES"
    #   flag only there).
    function htmlspecialchars_with_ent_quotes (
        $string
    ) {
        return
            htmlspecialchars($string, ENT_QUOTES);
    }

    function save_tasks (
        $task_file_path,
        $tasks
    ) {
        $lines = [];
        foreach ($tasks as $task => $details) {
            $line
                = implode(
                    "\t",
                    [
                        $task,
                        ...$details
                    ]
                );
            $lines[] = $line;
        }
        file_put_contents(
            $task_file_path,
            implode("\n", $lines)
        );
    }

    # This function has been created to be
    #   explicit about exiting, that is,
    #   to not spread the fact that we are to exit
    #   over loosely related lines of code.
    function set_header (
        $base_url,
        $query_parameters = []
    ) {
        $url = $base_url;
        if (count($query_parameters) !== 0) {
            $query
                = http_build_query(
                    $query_parameters
                );
            $url .= "?{$query}";
        }
        header("Location: $url");
    }

    $configuration = require_once 'config.php';
    $base_url = "http://{$_SERVER['HTTP_HOST']}";

    $tasks = [];
    # If the task file doesn't exist, issue
    #   an error and exit, otherwise get
    #   the tasks.
    if (
        !file_exists(
            $configuration['task-file-path']
        )
    ) {
        echo
            "Error: there does not exist a file at {$configuration['task-file-path']}";
        exit;
    } else {
        # Transition from the old format
        #   of the task file (without task
        #   statuses) to the new one (with task
        #   statuses). Code to be removed
        #   upon releasing version 5.0.0.
        $old = false;

        $lines
            = file(
                $configuration['task-file-path'],
                FILE_IGNORE_NEW_LINES
            );
        foreach ($lines as $_ => $line) {
            $fields = explode("\t", $line);
            $task = $fields[0];
            $details = array_slice($fields, 1);

            # Transition from the old format
            #   of the task file (without task
            #   statuses) to the new one
            #   (with task statuses). Code to be
            #   removed upon releasing version
            #   5.0.0.
            if (count($details) === 1) {
                $details[1] = '(NA)';
                $old = true;
            }

            $tasks[$task] = $details;
        }

        # Transition from the old format
        #   of the task file (without task
        #   statuses) to the new one (with task
        #   statuses). Code to be removed
        #   upon releasing version 5.0.0.
        if ($old) {
            save_tasks(
                $configuration['task-file-path'],
                $tasks
            );
        }
    }

    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'removal-decision':
                if (
                    isset(
                        $_GET['removal-confirmation-submit']
                    )
                ) {
                    set_header(
                        $base_url,
                        [
                            'action' => 'removal',
                            'task'
                                => $_GET['task']
                        ]
                    );
                    exit;
                } else if (
                    isset(
                        $_GET['removal-cancellation-submit']
                    )
                ) {
                    set_header(
                        $base_url,
                        [
                            'task'
                                => $_GET['task']
                        ]
                    );
                    exit;
                }
            case 'removal':
                # Remove the task. Observe that we
                #   don't remove the occurrences
                #   of the name of the task
                #   as parent task names. This way
                #   those occurrences remain
                #   in the file (in the views
                #   they aren't part of links).
                #   If the user need so
                #   in the future, they will
                #   possibly be able to recreate
                #   in their mind some
                #   relationships
                #   between the former child tasks
                #   and other tasks (even if only
                #   partially). This decision
                #   requires making a separate
                #   view in which the user will be
                #   able to see the former
                #   child tasks.
                unset($tasks[$_GET['task']]);

                save_tasks(
                    $configuration['task-file-path'],
                    $tasks
                );

                set_header($base_url);
                exit;
            case 'modification-addition-decision':
                if (
                    isset(
                        $_GET['modification-addition-confirmation-submit']
                    )
                ) {
                    $query_parameters = $_GET;
                    $query_parameters['action']
                        = 'modification-addition';
                    set_header(
                        $base_url,
                        $query_parameters
                    );
                    exit;
                } else if (
                    isset(
                        $_GET['modification-addition-cancellation-submit']
                    )
                ) {
                    if (
                        isset($_GET['old-task'])
                    ) {
                        # Modification case.
                        set_header(
                            $base_url,
                            [
                                'task'
                                    => $_GET['old-task']
                            ]
                        );
                        exit;
                    } else {
                        # Addition case.
                        set_header($base_url);
                        exit;
                    }
                }
            case 'modification-addition':
                $new_task = $_GET['new-task'];
                $new_parent_task
                    = $_GET['new-parent-task'];
                $new_status = $_GET['new-status'];
                $reserved_character_replacements
                    = [
                        "\t" => '\t',
                        "\n" => '\n'
                    ];

                # User input sanitization.
                foreach (
                    $reserved_character_replacements
                        as $character
                            => $replacement
                ) {
                    $new_task
                        = str_replace(
                            $character,
                            $replacement,
                            $new_task
                        );
                    $new_parent_task
                        = str_replace(
                            $character,
                            $replacement,
                            $new_parent_task
                        );
                }

                # Check if it's an attempt
                #   to add an existing task.
                if (
                    !isset($_GET['old-task'])
                        && isset(
                            $tasks[$new_task]
                        )
                ) {
                    # Reject the addition
                    #   (so don't change
                    #   the view).
                    set_header(
                        $base_url,
                        [
                            'view'
                                => 'modification-addition-form-view',
                            'new-task'
                                => $new_task,
                            'new-parent-task'
                                => $new_parent_task,
                            'new-status'
                                => $new_status
                        ]
                    );
                } else {
                    # Check if it's
                    #   the modification case.
                    if (
                        isset($_GET['old-task'])
                    ) {
                        unset(
                            $tasks[$_GET['old-task']]
                        );
                        # Modify the references
                        #   of the name
                        #   of the task as parent
                        #   task names.
                        foreach (
                            $tasks
                                as $task
                                    => $details
                        ) {
                            if (
                                $details[0]
                                    === $_GET['old-task']
                            ) {
                                $tasks[$task][0]
                                    = $new_task;
                            }
                        }
                    }
                    # Either the modification
                    #   or addition case.
                    $tasks[$new_task]
                        = [
                            $new_parent_task,
                            $new_status
                        ];

                    save_tasks(
                        $configuration['task-file-path'],
                        $tasks
                    );

                    set_header(
                        $base_url,
                        [
                            'task' => $new_task
                        ]
                    );
                }
                exit;
        }
    }
?>
<!doctype html>
<!--
    Menu.
-->
<form method="get">
    <input
        type="hidden"
        name="view"
        value="search-result-view"
    >
    <input name="target-task">
    <br>
    <input
        type="submit"
        name="search-submit"
        value="SEARCH"
    >
</form>
<?php
    $all_tasks_link_link = '';
    if (
        isset($_GET['view'])
            && $_GET['view'] === 'all-task-view'
    ) {
        $all_tasks_link = 'ALL TASKS';
    } else {
        $query
            = http_build_query(
                [
                    'view' => 'all-task-view'
                ]
            );
        $url = "{$base_url}?{$query}";
        $all_tasks_link 
            = '<a href="'
                . htmlspecialchars_with_ent_quotes(
                    $url
                )
                . '">ALL TASKS</a>';
    }
    $query
        = http_build_query(
            [
                'view'
                    => 'modification-addition-form-view'
            ]
        );
    $url = "{$base_url}?{$query}";
    echo
        '<a href="'
            . htmlspecialchars_with_ent_quotes(
                $url
            )
            . "\">ADD A TASK</a><br>{$all_tasks_link}<hr>";
?>
<?php
    if (!isset($_GET['view'])):
?>
    <!--
        Default view.
    -->
    <h1>
        <?php
            if (isset($_GET['task'])) {
                echo
                    htmlspecialchars_with_ent_quotes(
                        $_GET['task']
                    );
            } else {
                echo '(NA)';
            }
        ?>
    </h1>
    <h2>STATUS</h2>
    <?php
        if (
            isset($_GET['task'])
        ) {
            echo $tasks[$_GET['task']][1];
        } else {
            echo '(NA)';
        }
    ?>
    <h2>PARENT TASK</h2>
    <?php
        if (
            isset($_GET['task'])
                && $_GET['task'] !== '(NA)'
        ) {
            $parent_task
                = $tasks[$_GET['task']][0];
            if ($parent_task === '(NA)') {
                # This is a second-level task,
                #   so we don't include the "task"
                #   parameter in the query. This
                #   way we, so to say, unset
                #   the task (= unify
                #   its two representations).
                $url = $base_url;
                $label = '(NA)';
                echo
                    "<a href=\""
                        . htmlspecialchars_with_ent_quotes(
                            $url
                        )
                        . "\">"
                        . htmlspecialchars_with_ent_quotes(
                            $label
                        )
                        . "</a>";
            } else {
                # This is a third-or-lower-level
                #   task.
                # Check if there exists a task
                #   with the parent task name.
                if (isset($tasks[$parent_task])) {
                    $query
                        = http_build_query(
                            [
                                'task'
                                    => $parent_task
                            ]
                        );
                    $url = "{$base_url}?{$query}";
                    $label = $parent_task;
                    echo
                        "<a href=\""
                            . htmlspecialchars_with_ent_quotes(
                                $url
                            )
                            . "\">"
                            . htmlspecialchars_with_ent_quotes(
                                $label
                            )
                            . "</a>";
                } else {
                    $query
                        = http_build_query(
                            [
                                'view'
                                    => 'modification-addition-form-view',
                                'new-task'
                                    => $parent_task
                            ]
                        );
                    $url = "{$base_url}?{$query}";
                    echo
                        htmlspecialchars_with_ent_quotes(
                            $parent_task
                        )
                            . " (<a href=\""
                            . htmlspecialchars_with_ent_quotes(
                                $url
                            )
                            . "\">ADD</a>)";
                }
            }
        } else {
            echo '(NA)';
        }
    ?>
    <h2>CHILD TASKS</h2>
    <?php
        # The first task is represented
        #   as an unset task in the code
        #   and the URL. But the occurrences
        #   of its name in the file are
        #   represented there as "(NA)".
        #   Here we unify these representations.
        $task = $_GET['task'] ?? '(NA)';

        $child_tasks
            = array_filter(
                $tasks,
                fn ($details) =>
                    $details[0] === $task
            );
        if (count($child_tasks) === 0) {
            echo '(NA)';
        } else {
            $html = '';
            foreach (
                $child_tasks as $child_task => $_
            ) {
                $query
                    = http_build_query(
                        [
                            'task' => $child_task
                        ]
                    );
                $url = "{$base_url}?{$query}";
                $html
                    .= "<li><a href=\"{$url}\">"
                        . htmlspecialchars_with_ent_quotes(
                            $child_task
                        )
                        . '</a></li>';
            }
            echo "<ul>{$html}</ul>";
        }
    ?>
    <h2>ACTIONS</h2>
    <ul>
        <?php
            if (
                isset($_GET['task'])
                    && $_GET['task'] !== '(NA)'
            ):
        ?>
            <li>
                <?php
                    $query
                        = http_build_query(
                            [
                                'view'
                                    => 'modification-addition-form-view',
                                'task'
                                    => $_GET['task']
                            ]
                        );
                    $url = "{$base_url}?{$query}";
                ?>
                <a
                    href="<?php
                        echo
                            htmlspecialchars_with_ent_quotes(
                                $url
                            );
                    ?>"
                >MODIFY</a>
            </li>
            <li>
                <?php
                    $query
                        = http_build_query(
                            [
                                'view'
                                    => 'removal-confirmation-form-view',
                                'task'
                                    => $_GET['task']
                            ]
                        );
                    $url = "{$base_url}?{$query}";
                ?>
                <a
                    href="<?php
                        echo
                            htmlspecialchars_with_ent_quotes(
                                $url
                            );
                    ?>"
                >REMOVE</a>
            </li>
        <?php
            endif;
        ?>
        <li>
            <?php
                $query
                    = http_build_query(
                        [
                            'view'
                                => 'modification-addition-form-view',
                            'new-parent-task'
                                => $_GET['task'] ?? '(NA)'
                        ]
                    );
                $url = "{$base_url}?{$query}";
            ?>
            <a
                href="<?php
                    echo
                        htmlspecialchars_with_ent_quotes(
                            $url
                        );
                ?>"
            >ADD A CHILD TASK</a>
        </li>
    </ul>
<?php
    elseif (
        $_GET['view'] === 'search-result-view'
    ):
?>
<h1>SEARCH RESULT FOR "<?php
    echo $_GET['target-task'];
?>"</h1>
<?php
    $search_result = [];
    # This "if" has been created solely to prevent
    #   the PHP warning about the needle being
    #   empty.
    if ($_GET['target-task'] !== '') {
        # Check the task '(NA)' (it's not
        #   in the task file, so we need to check
        #   it separately).
        if (
            strpos(
                '(na)',
                strtolower($_GET['target-task'])
            ) !== false
        ) {
            $search_result[] = '(NA)';
        }
        $search_result
            = array_merge(
                $search_result,
                array_keys(
                    array_filter(
                        $tasks,
                        fn ($task) =>
                            strpos(
                                strtolower($task),
                                strtolower(
                                    $_GET['target-task']
                                )
                            ) !== false,
                        ARRAY_FILTER_USE_KEY
                    )
                )
            );
    }
    $html = '';
    if (count($search_result) > 0) {
        foreach ($search_result as $_ => $task) {
            $query
                = http_build_query(
                    [
                        'task' => $task
                    ]
                );
            $url = "{$base_url}?{$query}";
            $html
                .= "<li><a href=\"{$url}\">"
                    . htmlspecialchars_with_ent_quotes(
                        $task
                    )
                    . '</a></li>';
        }
        echo "<ul>{$html}</ul>";
    } else {
        echo '(NA)';
    }
?>
<?php
    elseif (
        $_GET['view']
            === 'removal-confirmation-form-view'
    ):
?>
    <h1>
        <?php
            echo
                'REMOVAL OF "'
                    . htmlspecialchars_with_ent_quotes(
                        $_GET['task']
                    )
                    . '"';
        ?>
    </h1>
    <p>Do you really want to remove "<?php
        echo
            htmlspecialchars_with_ent_quotes(
                $_GET['task']
            );
    ?>"?</p>
    <form method="get">
        <input
            type="hidden"
            name="action"
            value="removal-decision"
        >
        <input
            type="hidden"
            name="task"
            value="<?php
                echo
                    htmlspecialchars_with_ent_quotes(
                        $_GET['task']
                    );
            ?>"
        >
        <ul>
            <li>
                <input
                    type="submit"
                    name="removal-confirmation-submit"
                    value="YES"
                >
            </li>
            <li>
                <input
                    type="submit"
                    name="removal-cancellation-submit"
                    value="NO"
                >
            </li>
        </ul>
    </form>
<?php
    elseif (
        $_GET['view']
            === 'modification-addition-form-view'
    ):
?>
    <h1>
        <?php
            # Check if it's the modification case.
            if (isset($_GET['task'])) {
                echo
                    'MODIFICATION OF "'
                        . htmlspecialchars_with_ent_quotes(
                            $_GET['task']
                        )
                        . '"';
            } else {
                echo 'ADDITION';
            }
        ?>
    </h1>
    <form method="get">
        <input
            type="hidden"
            name="action"
            value="modification-addition-decision"
        >
        <?php
            # Check if it's modification.
            if (isset($_GET['task'])):
        ?>
            <input
                type="hidden"
                name="old-task"
                value="<?php
                    echo
                        htmlspecialchars_with_ent_quotes(
                            $_GET['task']
                        );
                ?>"
            >
        <?php
            endif;
        ?>
        <label for="new-task">TASK</label>
        <br>
        <input
            id="new-task"
            name="new-task"
            value="<?php
                if (isset($_GET['task'])) {
                    # Modification case.
                    echo
                        htmlspecialchars_with_ent_quotes(
                            $_GET['task']
                        );
                } else if (
                    isset($_GET['new-task'])
                ) {
                    # Either parent task adddition
                    #   case, or addition
                    #   rejection case (rejection
                    #   after attempting to add
                    #   an existing task).
                    echo
                        htmlspecialchars_with_ent_quotes(
                            $_GET['new-task']
                        );
                }
            ?>"
            size="80"
        >
        <br>
        <label
            for="new-parent-task"
        >PARENT TASK</label>
        <br>
        <input
            id="new-parent-task"
            name="new-parent-task"
            value="<?php
                if (isset($_GET['task'])) {
                    # Modification case.
                    echo
                        htmlspecialchars_with_ent_quotes(
                            $tasks[$_GET['task']][0]
                        );
                } else if (
                    isset(
                        $_GET['new-parent-task']
                    )
                ) {
                    # Addition rejection case
                    #   (rejection
                    #   after attempting to add
                    #   an existing task).
                    echo
                        htmlspecialchars_with_ent_quotes(
                            $_GET['new-parent-task']
                        );
                }
            ?>"
            size="80"
        >
        <br>
        <label for="new-status">STATUS</label>
        <br>
        <select id="new-status" name="new-status">
            <?php
                foreach (
                    [
                        '(NA)',
                        'PENDING',
                        'COMPLETED'
                    ]
                        as $status
                ) {
                    $selected = '';
                    if (
                        (
                            # Modification case.
                            isset($_GET['task'])
                                && $tasks[$_GET['task']][1]
                                    === $status
                        ) || (
                            # Either the parent
                            #   task addition
                            #   case,
                            #   or the addition
                            #   rejection case
                            #   (rejection
                            #   after attempting
                            #   to add an existing
                            #   task).
                            isset(
                                $_GET['new-status']
                            )
                                && $_GET['new-status']
                                    === $status
                        )
                    ) {
                        $selected = 'selected';
                    }
                    echo
                        "<option {$selected}>{$status}</option>";
                }
            ?>
        </select>
        <br>
        <input
            type="submit"
            name="modification-addition-confirmation-submit"
            value="Save"
        >
        <br>
        <input
            type="submit"
            name="modification-addition-cancellation-submit"
            value="Cancel"
        >
    </form>
<?php
    elseif ($_GET['view'] === 'all-task-view'):
?>
    <h1>ALL TASKS</h1>
    <ul>
        <?php
            $url = $base_url;
            $html
                = '<li><a href="'
                    . htmlspecialchars_with_ent_quotes(
                        $url
                    )
                    . '">(NA)</a></li>';
            foreach (
                $tasks as $task => $details
            ) {
                $label = $task;
                # Check if there is no task
                #   with the parent task name.
                if (
                    $details[0] !== '(NA)'
                        && !isset(
                            $tasks[$details[0]]
                        )
                ) {
                    $label
                        = "(No task with the parent task name) {$label}";
                }
                $query
                    = http_build_query(
                        [
                            'task' => $task
                        ]
                    );
                $url = "{$base_url}?{$query}";
                $html
                    .= '<li><a href="'
                        . htmlspecialchars_with_ent_quotes(
                            $url
                        )
                        . '">'
                        . htmlspecialchars_with_ent_quotes(
                            $label
                        )
                        . '</a></li>';
            }
            echo $html;
        ?>
    </ul>
<?php
    endif;
?>
