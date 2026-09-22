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
?>
