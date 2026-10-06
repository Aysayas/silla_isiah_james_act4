<?php

/**
 * Adds the columns the API login needs to `users`.
 *
 * The local `users` table was created by docker/mysql/init.sql (firstname,
 * lastname, email, username, deleted_at), so 001_create_users_table skipped it
 * and it has no password/role. A fresh database built by 001 already has
 * password/role but no deleted_at. Every column is added only when missing, so
 * this runs cleanly against either schema.
 */
class Add_auth_columns_to_users {

    private $_lava;

    public function __construct()
    {
        $this->_lava = lava_instance();
        $this->_lava->call->dbforge();
    }

    public function up()
    {
        $forge = $this->_lava->dbforge;

        if (!$forge->column_exists('users', 'password')) {
            $forge->add_column('users', [
                'password' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => TRUE,
                ],
            ]);
        }

        // Read by Api::require_jwt() to derive scopes (admin: read/write/delete).
        if (!$forge->column_exists('users', 'role')) {
            $forge->add_column('users', [
                'role' => [
                    'type'       => 'ENUM',
                    'constraint' => "'admin','editor','user'",
                    'null'       => FALSE,
                    'default'    => 'user',
                ],
            ]);
        }

        if (!$forge->column_exists('users', 'deleted_at')) {
            $forge->add_column('users', [
                'deleted_at' => [
                    'type'    => 'DATETIME',
                    'null'    => TRUE,
                    'default' => NULL,
                ],
            ]);
        }
    }

    public function down()
    {
        // Only drop what this migration may have added; on a 001-built table
        // password/role belong to 001, so leave them alone there.
        $forge = $this->_lava->dbforge;

        if ($forge->column_exists('users', 'firstname')) {
            $forge->drop_column('users', 'password');
            $forge->drop_column('users', 'role');
        } else {
            $forge->drop_column('users', 'deleted_at');
        }
    }
}
