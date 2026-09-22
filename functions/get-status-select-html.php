<?php
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
?>
