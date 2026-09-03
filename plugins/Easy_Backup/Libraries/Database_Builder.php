<?php

namespace Easy_Backup\Libraries;

class Database_Builder {

    public function build_database_seed() {

        $db = db_connect('default');
        $host = $db->hostname;
        $user = $db->username;
        $pass = $db->password;
        $name = $db->database;

        $mysqli = new \mysqli($host, $user, $pass, $name);

        $output_sql = '';

        $query = "SHOW TABLES";
        $result = $mysqli->query($query);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_array()) {
                $table_name = $row[0];
                $output_sql .= $this->generate_create_table_sql($mysqli, $table_name) . ";\n\n";
                $output_sql .= $this->generate_insert_statements($mysqli, $table_name);
            }
        }

        $mysqli->close();

        return $output_sql;
    }

    private function generate_create_table_sql($mysqli, $table_name) {
        $query = "SHOW CREATE TABLE `$table_name`";
        $result = $mysqli->query($query);

        if ($result && $row = $result->fetch_assoc()) {
            $create_sql = $row['Create Table'];
            return str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $create_sql);
        }

        return null;
    }

    private function generate_insert_statements($mysqli, $table_name, $condition = null) {
        $output_sql = '';
        $condition_clause = $condition ? " WHERE $condition" : '';
        $query = "SELECT * FROM `$table_name`" . $condition_clause;

        // Fetch column names
        $columns_result = $mysqli->query("SHOW COLUMNS FROM `$table_name`");
        $columns = [];
        if ($columns_result && $columns_result->num_rows > 0) {
            while ($column = $columns_result->fetch_assoc()) {
                $columns[] = "`" . $column['Field'] . "`";
            }
        }
        $columns_list = implode(", ", $columns);

        $result = $mysqli->query($query);

        if ($result && $result->num_rows > 0) {
            $row_counter = 0;

            while ($row = $result->fetch_row()) {
                if ($row_counter % 100 == 0) {
                    $output_sql .= "INSERT INTO `$table_name` ($columns_list) VALUES ";
                }

                $values = array_map(function ($value) use ($mysqli) {
                    return $value === null ? 'NULL' : "'" . $mysqli->real_escape_string($value) . "'";
                }, $row);

                $output_sql .= "(" . implode(", ", $values) . ")";

                if ((($row_counter + 1) % 100 == 0) || ($row_counter + 1 == $result->num_rows)) {
                    $output_sql .= ";\n";
                } else {
                    $output_sql .= ",\n";
                }

                $row_counter++;
            }

            $output_sql .= "\n";
        }

        return $output_sql;
    }
}
