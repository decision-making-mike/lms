<?php
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
?>
