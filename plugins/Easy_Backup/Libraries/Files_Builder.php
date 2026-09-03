<?php

namespace Easy_Backup\Libraries;

class Files_Builder extends \ZipArchive {

    public function add_dir($location, $name) {
        $this->addEmptyDir($name);
        $this->add_dir_recursive($location, $name);
    }

    private function add_dir_recursive($location, $name) {
        $name .= '/';
        $location .= '/';
        $dir = opendir($location);

        if (!$dir) {
            throw new \Exception("Failed to open directory: $location");
        }

        $backup_files_directory = $this->get_backup_files_directory();

        while (($file = readdir($dir)) !== false) {
            // Skip the current directory, parent directory, and the backup files directory
            if ($file === '.' || $file === '..' || $file === $backup_files_directory) {
                continue;
            }

            // Handle hidden files and directories
            if ($file[0] === '.' && $file !== '.htaccess') {
                continue;
            }

            $file_path = $location . $file;
            $do = is_dir($file_path) ? 'add_dir' : 'addFile';

            try {
                $this->$do($file_path, $name . $file);
            } catch (\Exception $e) {
                error_log("Error adding file/directory: " . $e->getMessage());
            }
        }

        closedir($dir);
    }

    private function get_backup_files_directory() {
        $backup_file_path = get_easy_backup_setting("easy_backup_backups_file_path");
        $backup_file_path_parts = explode('/', $backup_file_path);
        $parts_count = count($backup_file_path_parts);

        if ($parts_count >= 2) {
            return $backup_file_path_parts[$parts_count - 2];
        }

        return '';
    }
}
