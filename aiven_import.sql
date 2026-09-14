-- Lab Exercise 5: Products CRUD with Authentication
-- Run this against your Aiven MySQL database (via the Aiven console's SQL
-- editor, or `mysql --host ... --port ... -u avnadmin -p defaultdb < aiven_import.sql`).
--
-- Creates the two tables this feature needs:
--   - admins   : login-only table (separate from any business-data table)
--   - products : the CRUD table, columns exactly per the handout
-- and seeds one admin login + two sample products so the deployed app has
-- something to show immediately. See PRODUCTS_CRUD_DOCS.md for the full
-- writeup of how these are used.

CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY username_unique (username)
);

CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

-- Login: username "admin", password "admin123"
-- CHANGE THIS before/after your real submission if you don't want a
-- guessable password sitting on a publicly reachable Render URL — re-hash
-- with: php -r "echo password_hash('yournewpassword', PASSWORD_DEFAULT);"
INSERT INTO admins (username, password) VALUES
    ('admin', '$2y$10$/GCaOz1Jy3DbeGeHRyIkcukQyWcPF2fnLKO0OXaF4LNvs7xaDpPFS')
ON DUPLICATE KEY UPDATE username = username;

INSERT INTO products (product_name, description, price, quantity) VALUES
    ('Wireless Mouse', 'Ergonomic wireless mouse with USB receiver', 19.99, 50),
    ('Mechanical Keyboard', 'RGB backlit mechanical keyboard', 59.99, 25);
