<?php
    # Tell Lynx to not use cache
    #   (see https://lynx.invisible-island.net/lynx_help/Lynx_users_guide.html).
    header('Cache-Control: no-cache');

    function get_task_path_html (
        $na_task_id,
        $task_id,
        $all_tasks_list
    ) {
        $id = $task_id;
        $reversed_task_path_item_list = [];
        while ($id !== $na_task_id) {
            $reversed_task_path_item_list[]
                = $all_tasks_list[$id]['name'];
            $id
                = $all_tasks_list[$id]['parent-task-id'];
        }
        $reversed_task_path_item_list[] = '(NA)';
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

    function get_status_select_html (
        $status_select_name,
        $status_select_id,
        ?callable $get_selected_attribute_html
    ) {
        $option_list_html = '';
        foreach (
            [
                # The "(SELECT)" value is added
                #   to be able to search
                #   only by the task name
                #   (then you do not change
                #   this value, and it's
                #   not getting matched with any
                #   task status).
                '(SELECT)',
                '(NA)',
                'PENDING',
                'COMPLETED'
            ]
                as $option_value
        ) {
            $selected_attribute_html
                = $get_selected_attribute_html
                    !== null
                    ? $get_selected_attribute_html(
                        $option_value
                    )
                    : '';
            $option_list_html
                .= "<option {$selected_attribute_html}>{$option_value}</option>";
        }

        $id_attribute_html
            = $status_select_id !== null
                ? "id=\"$status_select_id\""
                : '';

        echo
            "<select
                {$id_attribute_html}
                name=\"{$status_select_name}\"
            >{$option_list_html}</select>";
    }

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

    function get_task_list_html (
        $na_task_id,
        $base_url,
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

    # This function has been created to be
    #   explicit about exiting, that is,
    #   to not spread the fact that we are to exit
    #   over loosely related lines of code.
    function set_header (
        $base_url,
        $query_parameters = []
    ) {
        $location_url = $base_url;
        if (count($query_parameters) !== 0) {
            $query
                = http_build_query(
                    $query_parameters
                );
            $location_url .= "?{$query}";
        }
        header("Location: $location_url");
    }

    $configuration = require_once 'config.php';
    $base_url = "http://{$_SERVER['HTTP_HOST']}";
    $na_task_id = 0;

    $all_tasks_list = [];
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
        $line_list
            = file(
                $configuration['task-file-path'],
                FILE_IGNORE_NEW_LINES
            );

        # Transition from the old formats
        #   of the task file (without task
        #   statuses or IDs) to the newest one
        #   (with task statuses and IDs).

        $first_line_fields
            = explode("\t", $line_list[0]);
        $no_task_status_format
            = count($first_line_fields) === 2;
        $no_task_id_format
            = count($first_line_fields) === 3;

        # This outside "if" has been employed
        #   to not repeat code in the now-inner
        #   "if".
        if (
            $no_task_status_format
                || $no_task_id_format
        ) {
            # ID "0" is reserved for task "(NA)".
            $task_id = 1;

            if ($no_task_status_format) {
                foreach ($line_list as $line) {
                    $fields
                        = explode("\t", $line);
                    $tmp[$fields[0]]
                        = [
                            'id' => (string)$task_id,
                            'parent-task-name'
                                => $fields[1],
                            'status' => '(NA)'
                        ];
                    ++$task_id;
                }
            } else if ($no_task_id_format) {
                foreach ($line_list as $line) {
                    $fields
                        = explode("\t", $line);
                    $tmp[$fields[0]]
                        = [
                            'id' => (string)$task_id,
                            'parent-task-name'
                                => $fields[1],
                            'status'
                                => $fields[2]
                        ];
                    ++$task_id;
                }
            }

            # Replace parent task names
            #   with parent task IDs, and place
            #   task IDs and names
            #   on the appropriate places.
            foreach (
                $tmp
                    as $task_name => $task_details
            ) {
                $all_tasks_list[$task_details['id']]
                    = [
                        'name' => $task_name,
                        'parent-task-id'
                            => (
                                $task_details['parent-task-name']
                                    === '(NA)'
                                    ? ((int)$na_task_id)
                                    : ((int)$tmp[$task_details['parent-task-name']]['id'])
                            ),
                        'status'
                            => $task_details['status']
                    ];
            }

            save_tasks(
                $configuration['task-file-path'],
                $all_tasks_list
            );
        } else {
            # Get tasks normally.
            foreach ($line_list as $line) {
                $fields = explode("\t", $line);
                $all_tasks_list[$fields[0]]
                    = [
                        'name' => $fields[1],
                        'parent-task-id'
                            => (int)$fields[2],
                        'status'
                            => $fields[3]
                    ];
            }
        }
    }

    # Action handling.
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
                            'task-id'
                                => $_GET['task-id']
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
                            'task-id'
                                => $_GET['task-id']
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
                #   they aren't part of links,
                #   but are just plain text).
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
                unset(
                    $all_tasks_list[(int)$_GET['task-id']]
                );

                save_tasks(
                    $configuration['task-file-path'],
                    $all_tasks_list
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
                        isset($_GET['old-task-id'])
                    ) {
                        # Modification case.
                        set_header(
                            $base_url,
                            [
                                'task-id'
                                    => $_GET['old-task-id']
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
                $new_task_name = $_GET['new-task-name'];
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
                    $new_task_name
                        = str_replace(
                            $character,
                            $replacement,
                            $new_task_name
                        );
                }

                # Check if the action needs to be
                #   rejected (so the view not
                #   changed).
                $same_name_sibling_task_exists
                    = false;
                foreach (
                    $all_tasks_list
                        as $_ => $task_details
                ) {
                    if (
                        # It's a sibling task.
                        $task_details['parent-task-id']
                            === ((int)$_GET['new-task-parent-task-id'])
                            && $task_details['name']
                                === $new_task_name
                    ) {
                        $same_name_sibling_task_exists
                            = true;
                        break;
                    }
                }
                if (
                    # It's an attempt to add
                    #   a task with the same
                    #   name as one of its
                    #   sibling tasks.
                    $same_name_sibling_task_exists
                        # It's an attempt to add
                        #   a task without its
                        #   status decided.
                        || (
                            $_GET['new-task-status']
                                === '(SELECT)'
                        )
                ) {
                    set_header(
                        $base_url,
                        [
                            'view'
                                => 'modification-addition-form-view',
                            'new-task-name'
                                => $new_task_name,
                            'new-task-parent-task-id'
                                => $_GET['new-task-parent-task-id'],
                            'new-task-status'
                                => $_GET['new-task-status']
                        ]
                    );
                } else {
                    $task_id
                        = isset(
                            $_GET['old-task-id']
                        )
                            # Modification case.
                            ? ((int)$_GET['old-task-id'])
                            # Addition case.
                            : max(
                                array_keys(
                                    $all_tasks_list
                                )
                            )
                                + 1;
                    $all_tasks_list[$task_id]
                        = [
                            'name'
                                => $new_task_name,
                            'parent-task-id'
                                => $_GET['new-task-parent-task-id'],
                            'status'
                                => $_GET['new-task-status']
                        ];

                    save_tasks(
                        $configuration['task-file-path'],
                        $all_tasks_list
                    );

                    set_header(
                        $base_url,
                        [
                            'task-id' => $task_id
                        ]
                    );
                }
                exit;
            case 'search':
                # Check if there's anything
                #   to search for.
                if (
                    $_GET['target-string'] !== ''
                        || $_GET['target-status']
                            !== '(SELECT)'
                ) {
                    # Change the view (and then
                    #   search).
                    set_header(
                        $base_url,
                        [
                            'view'
                                => 'search-result-view',
                            'target-string'
                                => $_GET['target-string'],
                            'target-status'
                                => $_GET['target-status']
                        ]
                    );
                    exit;
                } else {
                    # Don't change the view.
                    set_header(
                        $base_url,
                        [
                            'view'
                                => $_GET['view']
                        ]
                    );
                    exit;
                }
        }
    }
?>
<!doctype html>
<!-- Menu. -->
<!-- Search form. -->
<form method="get">
    <input
        type="hidden"
        name="action"
        value="search"
    >
    <input name="target-string">
    <br>
    <?php
        echo
            get_status_select_html(
                'target-status',
                null,
                fn ($option_value) =>
                    $option_value === '(SELECT)'
            );
    ?>
    <br>
    <input
        type="submit"
        name="search-submit"
        value="SEARCH"
    >
</form>
<!-- Menu links. -->
<?php
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
    echo "{$all_tasks_link}<hr>";
?>
<!-- View handling. -->
<?php if (!isset($_GET['view'])): ?>
    <!-- Task view (default view). -->
    <h1>
        <?php
            if (isset($_GET['task-id'])) {
                echo
                    get_task_path_html(
                        $na_task_id,
                        (int)$_GET['task-id'],
                        $all_tasks_list
                    );
            } else {
                echo '(NA)';
            }
        ?>
    </h1>
    <h2>STATUS</h2>
    <?php
        if (
            isset($_GET['task-id'])
                && ((int)$_GET['task-id'])
                    !== $na_task_id
        ) {
            echo
                htmlspecialchars_with_ent_quotes(
                    $all_tasks_list[(int)$_GET['task-id']]['status']
                );
        } else {
            echo '(NA)';
        }
    ?>
    <h2>PARENT TASK</h2>
    <?php
        if (
            isset($_GET['task-id'])
                && ((int)$_GET['task-id'])
                    !== $na_task_id
        ) {
            $parent_task_id
                = $all_tasks_list[(int)$_GET['task-id']]['parent-task-id'];
            if ($parent_task_id === $na_task_id) {
                # This is a second-level task,
                #   so we don't include
                #   the "task-id" parameter
                #   in the query. This way we, so
                #   to say, unset the task
                #   (= unify its two
                #   representations).
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
                $query
                    = http_build_query(
                        [
                            'task-id'
                                => $parent_task_id
                        ]
                    );
                $url = "{$base_url}?{$query}";
                $label
                    = $all_tasks_list[$parent_task_id]['name'];
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
            }
        } else {
            # This is task "(NA)" (the only
            #   first-level task).
            echo '(NA)';
        }
    ?>
    <h2>CHILD TASKS</h2>
    <?php
        # Task "(NA)" is represented as an unset
        #   task in the code and the URL.
        #   But the occurrences of its ID
        #   in the file are represented there
        #   as "0". Here we unify these
        #   representations.
        $task_id
            = (
                isset($_GET['task-id'])
                    ? ((int)$_GET['task-id'])
                    : $na_task_id
            );

        $child_task_list
            = array_filter(
                $all_tasks_list,
                fn ($task_details) =>
                    $task_details['parent-task-id']
                        === $task_id
            );

        if (count($child_task_list) === 0) {
            echo '(NA)';
        } else {
            echo
                get_task_list_html(
                    $na_task_id,
                    $base_url,
                    $child_task_list,
                    $all_tasks_list,
                    false
                );
        }
    ?>
    <h2>ACTIONS</h2>
    <ul>
        <?php
            # Check if it's not task "(NA)".
            if (
                isset($_GET['task-id'])
                    && ((int)$_GET['task-id'])
                        !== $na_task_id
            ):
        ?>
            <li>
                <?php
                    $query
                        = http_build_query(
                            [
                                'view'
                                    => 'modification-addition-form-view',
                                'task-id'
                                    => $_GET['task-id']
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
                                'task-id'
                                    => $_GET['task-id']
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
        <?php endif; ?>
        <li>
            <?php
                $query
                    = http_build_query(
                        [
                            'view'
                                => 'modification-addition-form-view',
                            'new-task-parent-task-id'
                                => $_GET['task-id']
                                    ?? $na_task_id
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
    <?php
        # Show the view title.
        $title_content_html
            = 'SEARCH RESULT FOR THE "';
        if ($_GET['target-string'] !== '') {
            $title_content_html
                .= htmlspecialchars_with_ent_quotes(
                        $_GET['target-string']
                )
                    . '" STRING';
            if (
                $_GET['target-status']
                    !== '(SELECT)'
            ) {
                $title_content_html
                    .= ' AND THE "';
            }
        }
        if (
            $_GET['target-status']
                !== '(SELECT)'
        ) {
            $title_content_html
                .= htmlspecialchars_with_ent_quotes(
                        $_GET['target-status']
                )
                    . '" STATUS';
        }
        echo "<h1>{$title_content_html}</h1>";

        # Search and show the result.

        $search_result = [];

        # This "if" has been created solely to prevent
        #   the PHP warning about the needle being
        #   empty.
        if ($_GET['target-string'] !== '') {
            # Check task "(NA)" (it's not
            #   in the task file, so we need
            #   to check it separately).
            # I'm not sure if the use
            #   of "mb_strpos" and "mb_strtolower"
            #   won't add to the confusion.
            #   To me and Google Gemini, this
            #   condition will work for multibyte
            #   characters even with "strpos"
            #   and "strtolower".
            if (
                strpos(
                    '(na)',
                    strtolower($_GET['target-string'])
                ) !== false
            ) $search_result[$na_task_id] = null;

            $search_result
                = $search_result
                    + array_filter(
                        $all_tasks_list,
                        fn ($task_details) =>
                            # As far as I can say,
                            #   with UTF-8
                            #   "strpos"
                            #   should correctly
                            #   report a match,
                            #   regardless
                            #   of the existence
                            #   of multibyte
                            #   characters.
                            #   It's only that
                            #   the position might
                            #   be wrong
                            #   sometimes. Google
                            #   Gemini says
                            #   that with other
                            #   encodings "strpos"
                            #   might return false
                            #   positives, so I'm
                            #   using "mb_strpos".
                            mb_strpos(
                                mb_strtolower(
                                    $task_details['name']
                                ),
                                mb_strtolower(
                                    $_GET['target-string']
                                )
                            ) !== false
                    );

            if (
                $_GET['target-status']
                    !== '(SELECT)'
            ) {
                $search_result
                    = array_filter(
                        $search_result,
                        fn ($task_details) =>
                            # Check if
                            #   "task_details"
                            #   exist. They won't
                            #   in the case
                            #   of task "(NA)".
                            $task_details !== null
                                ? $_GET['target-status']
                                    === $task_details['status']
                                # "(NA)" is
                                #   the status
                                #   of the "(NA)"
                                #   task.
                                : $_GET['target-status']
                                    === '(NA)'
                    );
            }
        } else if (
            $_GET['target-status'] !== '(SELECT)'
        ) {
            if (
                $_GET['target-status'] === '(NA)'
            ) {
                $search_result[$na_task_id]
                    = null;
            }

            $search_result
                = array_filter(
                    $all_tasks_list,
                    fn ($task_details) =>
                        $task_details['status']
                            === $_GET['target-status']
                );
        }

        if (count($search_result) > 0) {
            echo
                get_task_list_html(
                    $na_task_id,
                    $base_url,
                    $search_result,
                    $all_tasks_list
                );
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
                        $all_tasks_list[(int)$_GET['task-id']]['name']
                    )
                    . '"';
        ?>
    </h1>
    <p>Do you really want to remove "<?php
        echo
            htmlspecialchars_with_ent_quotes(
                $all_tasks_list[(int)$_GET['task-id']]['name']
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
            name="task-id"
            value="<?php
                echo
                    htmlspecialchars_with_ent_quotes(
                        $_GET['task-id']
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
            if (isset($_GET['task-id'])) {
                echo
                    'MODIFICATION OF "'
                        . htmlspecialchars_with_ent_quotes(
                            $all_tasks_list[(int)$_GET['task-id']]['name']
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
            # Check if it's the modification case.
            if (isset($_GET['task-id'])):
        ?>
            <input
                type="hidden"
                name="old-task-id"
                value="<?php
                    echo
                        htmlspecialchars_with_ent_quotes(
                            $_GET['task-id']
                        );
                ?>"
            >
        <?php endif; ?>
        <label for="new-task-name">TASK</label>
        <br>
        <input
            id="new-task-name"
            name="new-task-name"
            value="<?php
                if (isset($_GET['task-id'])) {
                    # Modification case.
                    echo
                        htmlspecialchars_with_ent_quotes(
                            $all_tasks_list[(int)$_GET['task-id']]['name']
                        );
                } else if (
                    isset($_GET['new-task-name'])
                ) {
                    # Addition rejection case.
                    echo
                        htmlspecialchars_with_ent_quotes(
                            $_GET['new-task-name']
                        );
                }
            ?>"
            size="80"
        >
        <br>
        <label
            for="new-task-parent-task-id"
        >PARENT TASK</label>
        <br>
        <select
            id="new-task-parent-task-id"
            name="new-task-parent-task-id"
        >
            <?php
                $html = '';
                foreach (
                    [
                        $na_task_id => null
                    ]
                        + $all_tasks_list
                        as $task_id => $_
                ) {
                    $id_of_parent_task_to_select
                        = isset($_GET['task-id'])
                            # Modification case.
                            ? $all_tasks_list[(int)$_GET['task-id']]['parent-task-id']
                            # Addition case.
                            : (int)$_GET['new-task-parent-task-id'];
                    $list[$task_id]
                        = get_task_path_html(
                            $na_task_id,
                            $task_id,
                            $all_tasks_list
                        );
                }
                asort(
                    $list,
                    SORT_NATURAL
                        | SORT_FLAG_CASE
                );
                foreach (
                    $list
                        as $new_task_parent_task_id
                            => $new_task_parent_task_path_html
                ) {
                    $html
                        .= '<option value="'
                            . htmlspecialchars_with_ent_quotes(
                                $new_task_parent_task_id
                            )
                            . '"'
                            . (
                                $new_task_parent_task_id
                                    === $id_of_parent_task_to_select
                                    ? ' selected'
                                    : ''
                            )
                            . '>'
                            . $new_task_parent_task_path_html
                            . '</option>';
                }
                echo $html;
            ?>
        </select>
        <br>
        <label
            for="new-task-status"
        >STATUS</label>
        <br>
        <?php
            echo
                get_status_select_html(
                    'new-task-status',
                    'new-task-status',
                    fn ($option_value) =>
                            (
                                # Modification
                                #   case.
                                isset(
                                    $_GET['task-id']
                                )
                                    && $all_tasks_list[(int)$_GET['task-id']]['status']
                                        === $option_value
                            ) || (
                                # Addition
                                #   rejection
                                #   case.
                                isset(
                                    $_GET['new-task-status']
                                )
                                    && $_GET['new-task-status']
                                        === $option_value
                            )
                                ? 'selected'
                                : ''
                );
        ?>
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
    <?php
        echo
            get_task_list_html(
                $na_task_id,
                $base_url,
                array_merge(
                    [
                        $na_task_id => null
                    ],
                    $all_tasks_list
                ),
                $all_tasks_list
            );
    ?>
<?php endif; ?>
