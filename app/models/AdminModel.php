<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Model: AdminModel
 *
 * Handles database access for the `admins` table (login credentials only —
 * no CRUD screens exist for this table on purpose).
 */
class AdminModel extends Model {
    protected $table = 'admins';
    protected $primary_key = 'id';
    protected $fillable = [];
    protected $guarded = ['id'];

    public function __construct()
    {
        parent::__construct();
    }
}
