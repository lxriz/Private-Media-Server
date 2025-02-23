<?php
    function get_root()
    {
        return  "./data/";
    }

    function get_database()
    {
        return new PDO('sqlite:database.db');
    }

    function check_database_query($query)
    {
        foreach($query as $row)
        {
            return true;
        }
        return false;
    }
?>