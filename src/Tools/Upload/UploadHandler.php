<?php

namespace Framework\Tools\Upload;

/**
 * This class handles any file upload requests
 * 
 * use Framework\Tools\Upload\UploadHandler;
 * 
 * @author  Gareth Palmer  [Github & Gitlab /projector22]
 * 
 * @since   3.6.0
 * @since   3.12.0  Moved to Framework\Tools\Upload
 */

class UploadHandler {

    /**
     * The full file name and extension of the uploaded file
     * 
     * @example     file.pdf or example.jpg
     * 
     * @var string  $file_name
     * 
     * @access  public
     * @since   3.6.0
     */

    public array|string $file_name;

    /**
     * The name of the file without the extension
     * 
     * @example     file or example
     * 
     * @var string  $name
     * 
     * @access  public
     * @since   3.6.0
     */

    public array|string $name;

    /**
     * The defined extension of the file
     * 
     * @example     pdf or jpg
     * 
     * @var string  $extension
     * 
     * @access  public
     * @since   3.6.0
     */

    public array|string $extension;

    /**
     * The type of file
     * 
     * @example     application/pdf or image/jpeg
     * 
     * @var string  $type
     * 
     * @access  public
     * @since   3.6.0
     */

    public array|string $type;

    /**
     * The temporary upload location of the file
     * 
     * @var string  $tmp_name
     * 
     * @access  public
     * @since   3.6.0
     */

    public array|string $tmp_name;

    /**
     * Any error produced
     * 
     * @var string  $error
     * 
     * @access  public
     * @since   3.6.0
     */

    public array|string $error;

    /**
     * The size of the file
     * 
     * @var string  $size
     * 
     * @access  public
     * @since   3.6.0
     */

    public array|string $size;

    /**
     * The number of files being uploaded
     * 
     * @var integer $num_of_files
     * 
     * @access  public
     * @since   3.6.0
     */

    public int $num_of_files = 0;

    /**
     * Array of indexes if multiple files are uploaded
     * 
     * @var array   $indexes
     * 
     * @access  public
     * @since   3.6.0
     */

    public array $indexes = [];

    /**
     * The location to save files once uploaded
     * 
     * @var string  $save_path
     * 
     * @access  public
     * @since   3.6.0
     */

    public string $save_path = UPLOADS_PATH;

    /**
     * The number of files that could not be uploaded
     * 
     * @var integer $error_count
     * 
     * @access  public
     * @since   3.6.0
     */

    public int $error_count = 0;

    /**
     * The new name of the file, default is the same as the old file
     * 
     * @var string  $new_name
     * 
     * @access  public
     * @since   3.6.0
     */

    public array|string $new_name;

    /**
     * Switch to allow checking of allowed file types
     * 
     * @var boolean $check_file_type
     * 
     * @access  public
     * @since   3.6.0
     */

    public bool $check_file_type = false;
    
    /**
     * Switch to allow checking if the file already exists
     * 
     * @var boolean $check_file_exists
     * 
     * @access  public
     * @since   3.6.0
     */

    public bool $check_file_exists = false;
    
    /**
     * Switch to allow checking if the file exceeds the file limit
     * 
     * @var boolean $check_size_limit
     * 
     * @access  public
     * @since   3.6.0
     */

    public bool $check_size_limit = false;

    /**
     * User defined files that are allowed when uploading
     * 
     * @var array   $allowed_file_types
     * 
     * @access  public
     * @since   3.6.0
     */

    public string|array $allowed_file_types;

    /**
     * The maximum upload file size allowed
     * 
     * @var int|float  $max_upload_size    Default: MAX_UPLOAD_SIZE
     * 
     * @access  public
     * @since   3.6.0
     */

    public int|float $max_upload_size = MAX_UPLOAD_SIZE;

    /**
     * If file uploading has failed
     * 
     * @var boolean $upload_failed
     * 
     * @access  public
     * @since   3.6.0
     */

    public bool $upload_failed = false;

    /**
     * Why file uploading failed
     * 
     * @var string  $upload_failed_reason
     * 
     * @access  public
     * @since   3.6.0
     */

    public ?string $upload_failed_reason = null;

    /**
     * Constructor method, things to do when the class is loaded
     * 
     * @access  public
     * @since   3.6.0
     */

    public function __construct() {
        if ( isset( $_FILES ) ) {
            $this->num_of_files = count( $_FILES );
            foreach ( $_FILES as $index => $file ) {
                $this->indexes[] = $index;
                if ( $this->num_of_files == 1 ) {
                    $this->file_name = $file['name'];
                    $this->name      = pathinfo( $file['name'], PATHINFO_FILENAME );
                    $this->extension = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
                    $this->type      = $file['type'];
                    $this->tmp_name  = $file['tmp_name'];
                    $this->error     = $file['error'];
                    if ( $this->error == 1 ) {
                        $this->error_count++;
                    }
                    $this->size     = $file['size'];
                    $this->new_name = $this->file_name;
                } else {
                    $this->file_name[$index] = $file['name'];
                    $this->name[$index]      = pathinfo( $file['name'], PATHINFO_FILENAME );
                    $this->extension[$index] = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
                    $this->type[$index]      = $file['type'];
                    $this->tmp_name[$index]  = $file['tmp_name'];
                    $this->error[$index]     = $file['error'];
                    if ( $this->error[$index] == 1 ) {
                        $this->error_count++;
                    }
                    $this->size[$index]     = $file['size'];
                    $this->new_name[$index] = $this->file_name[$index];
                }
            }
        } else {
            echo "No files selected";
            return;
        }
    } //__construct


    /**
     * This gracefully checks the number of errors and echos an error message. 
     * 
     * If returning true, the method should return immediately and not execute itself
     * 
     * @return  boolean     If the number of errors is greater than 0 return true, otherwise return false
     * 
     * @access  private
     * @since   3.6
     */

    public function upload_error(): bool {
        switch ( $this->error_count ) {
            case '0':
                return false;
            case '1':
                echo "$this->error_count file could not be uploaded";
                return true;
            default:
                echo "$this->error_count files could not be uploaded";
                return true;
        }
    }


    /**
     * Check the uploaded file to make sure it doesn't break any policies or requirements
     * 
     * @access  public
     * @since   3.1.0
     * @since   3.3.2   Made class public
     * @since   3.6.0   Removed all @params in favour of a more OOP implimentation
     */

    public function upload_check(): void {
        if ( $this->upload_error() ) {
            return;
        }
        $this->upload_failed = false;

        /**
         * This bit checks if the file / files are within the size limit
         * You would probably only use this if the maximum upload size needs to be smaller that the max size
         * defined in .htaccess or php.ini
         */

        if ( $this->check_size_limit ) {
            if ( $this->num_of_files > 1 ) {
                foreach ( $this->size as $size ) {
                    if ( $size > $this->max_upload_size ) {
                        $this->error_count++;
                        $this->upload_failed = true;
                        $this->upload_failed_reason = "File $this->file_name is bigger than the maximum upload size";
                        return;
                    }
                }
            } else {
                if ( $this->size > $this->max_upload_size ) {
                    $this->error_count++;
                    $this->upload_failed = true;
                    $this->upload_failed_reason = "File $this->file_name is bigger than the maximum upload size";
                    return;
                }
            }
        }

        /**
         * This bit checks if the file / files already exist on the server
         */

        if ( $this->check_file_exists ) {
            if ( $this->num_of_files > 1 ) {
                foreach ( $this->file_name as $name ) {
                    if ( file_exists( $this->save_path . $name ) ) {
                        $this->error_count++;
                        $this->upload_failed = true;
                        $this->upload_failed_reason = "File $this->save_path$name already exists and may not be overwritten";
                        return;
                    }
                }
            } else {
                if ( file_exists( $this->save_path . $this->file_name ) ) {
                    $this->error_count++;
                    $this->upload_failed = true;
                    $this->upload_failed_reason = "File $this->save_path$this->file_name already exists and may not be overwritten";
                    return;
                }
            }        
        }
        
        /**
         * This bit checks if a file matches the predefined file type limit
         */

        if ( $this->check_file_type ) {
            if ( is_string( $this->allowed_file_types ) ) {
                $this->allowed_file_types = [$this->allowed_file_types];
            }
            $hold = [];
            $test_all = false;
            foreach ( $this->allowed_file_types as $i => $type ) {
                //Strip leading '.' in the defined file extension
                if ( $type[0] == '.' ) {
                    $this->allowed_file_types[$i] = ltrim( $type, '.' );
                }
                //check if a /* MIME Type is being used - eg. image/*
                if ( str_contains( $type, '/' ) && explode( '/', $type )[1] == '*' ) {
                    $hold[] = explode( '/', $type )[0] . '/';
                }
            }
            if ( count( $hold ) > 0 ) {
                $test_all = true;
                $this->allowed_file_types = array_merge( $this->allowed_file_types, $hold );
            }

            if ( $this->num_of_files > 1 ) {
                foreach ( $this->type as $i => $type ) {
                    if ( $test_all ) {
                        if ( !in_array( explode( '/', $type )[0] . '/',  $this->allowed_file_types ) 
                        && !in_array( $type, $this->allowed_file_types ) 
                        && !in_array( $this->extension[$i], $this->allowed_file_types ) ) {
                            $this->error_count++;
                            $this->upload_failed = true;
                            $this->upload_failed_reason = "Invalid file type $type / " . $this->extension[$i] . " not allowed";
                            return;
                        }
                    } else {
                        if ( !in_array( $type, $this->allowed_file_types ) 
                        && !in_array( $this->extension[$i], $this->allowed_file_types ) ) {
                            $this->error_count++;
                            $this->upload_failed = true;
                            $this->upload_failed_reason = "Invalid file type $type / " . $this->extension[$i] . " not allowed";
                            return;
                        }
                    }
                }
            } else {
                if ( $test_all ) {
                    if ( !in_array( explode( '/', $this->type )[0] . '/',  $this->allowed_file_types ) 
                    && !in_array( $this->type, $this->allowed_file_types ) 
                    && !in_array( $this->extension, $this->allowed_file_types ) ) {
                        $this->error_count++;
                        $this->upload_failed = true;
                        $this->upload_failed_reason = "Invalid file type $this->type / $this->extension not allowed";
                        return;
                    }
                } else {
                    if ( !in_array( $this->type, $this->allowed_file_types ) 
                    && !in_array( $this->extension, $this->allowed_file_types ) ) {
                        $this->error_count++;
                        $this->upload_failed = true;
                        $this->upload_failed_reason = "Invalid file type $this->type / $this->extension not allowed";
                        return;
                    }
                }
            }
        }
    }


    /**
     * Move the uploaded file from the temporary location to the defined final resting place
     * 
     * @access  public
     * @since   3.6.0
     */

    public function place_uploaded_file(): void {
        if ( $this->upload_error() ) {
            return;
        }

        if ( $this->num_of_files > 1 ) {
            foreach ( $this->indexes as $index ) {
                if ( !move_uploaded_file( $this->tmp_name[$index], $this->save_path . $this->new_name[$index] ) ) {
                    $this->error_count++;
                    $this->upload_failed = true;
                    echo "Error";
                }
            }
        } else {
            if ( !move_uploaded_file( $this->tmp_name, $this->save_path . $this->new_name ) ) {
                $this->error_count++;
                $this->upload_failed = true;
                echo "Error";
            }
        }
    }

}