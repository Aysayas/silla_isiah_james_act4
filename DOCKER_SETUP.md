# LavaLust + Docker Complete Setup Guide

This is a **complete, ready-to-go LavaLust development environment** with Docker. Everything is pre-configured — just extract and run!

---

## 🚀 Quick Start (30 seconds)

### Windows PowerShell

```powershell
# 1. Extract the zip file
Expand-Archive lavalust-docker-template.zip
cd lavalust-docker-template

# 2. Create .env (copy from template)
Copy-Item .env.example .env

# 3. Start Docker
docker-compose up -d

# 4. Done! Visit http://localhost
```

### Or use the quick start script:
```powershell
.\start.ps1
```

---

## ✅ What's Included

✨ **Full LavaLust Framework** — Ready to use  
✨ **Docker Configuration** — PHP 8.3, Nginx, MySQL 8.0  
✨ **phpMyAdmin** — Database management on port 8080  
✨ **Pre-configured** — .env, database config, Nginx setup  
✨ **Documentation** — Setup guides and examples  

---

## 📋 Access Points

Once Docker starts:

| Service | URL | Credentials |
|---------|-----|-------------|
| **Your App** | http://localhost | — |
| **phpMyAdmin** | http://localhost:8080 | root / root |
| **MySQL** | localhost:3306 | lavalust_user / lavalust_pass |

---

## 🔧 Verify Installation

```powershell
# Check all containers are running
docker-compose ps

# Should show 4 services: php, nginx, mysql, phpmyadmin
```

---

## 📁 Project Structure

```
lavalust-docker-template/
├── app/                          # LavaLust application code
│   ├── config/                   # Routes, database, config
│   ├── controllers/              # Request handlers
│   ├── models/                   # Database models
│   ├── views/                    # HTML templates
│   └── libraries/                # Reusable classes
├── public/                        # Web root (served by Nginx)
│   └── index.php                 # Entry point
├── runtime/                       # Logs, cache, uploads
├── docker/                        # Docker configuration
│   ├── nginx/nginx.conf          # Web server config - LOCAL docker-compose (php:9000)
│   ├── nginx/nginx.render.conf   # Web server config - Render/production build (127.0.0.1:9000)
│   ├── php/php.ini               # PHP settings
│   └── mysql/init.sql            # Database schema
├── docker-compose.yml            # Container orchestration
├── Dockerfile                    # PHP image builder
├── .env                          # Environment variables (created)
└── README.md                     # LavaLust documentation
```

---

## 🎯 Next Steps

### 1. Start Building

The welcome page is already running. Modify it here:

```
app/controllers/Welcome.php   (logic)
app/views/welcome_page.php    (HTML)
```

### 2. Add Database Tables

Edit `docker/mysql/init.sql` to create your tables:

```sql
CREATE TABLE books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Then restart Docker to apply:
```powershell
docker-compose down -v
docker-compose up -d
```

### 3. Create a Model

**File:** `app/models/Book_model.php`

```php
<?php
class Book_model extends Model {
    protected $table = 'books';

    public function getAll() {
        return $this->db->table($this->table)->get()->getResult();
    }

    public function find($id) {
        return $this->db->table($this->table)
                        ->where('id', $id)
                        ->get()
                        ->getRow();
    }
}
```

### 4. Create a Controller

**File:** `app/controllers/Books.php`

```php
<?php
class Books extends Controller {
    public function index() {
        $this->call->model('Book_model');
        $data['books'] = $this->Book_model->getAll();
        $this->call->view('books/index', $data);
    }
}
```

### 5. Create Views

**File:** `app/views/books/index.php`

```html
<h1>My Books</h1>
<ul>
<?php foreach($books as $book): ?>
    <li><?= $book->title ?> by <?= $book->author ?></li>
<?php endforeach; ?>
</ul>
```

### 6. Add Routes

**File:** `app/config/routes.php`

```php
$router->get('/', 'Welcome::index');
$router->get('/books', 'Books::index');
```

---

## 📚 LavaLust Learning Resources

- **Official Docs**: https://lavalust.netlify.app/
- **GitHub Repo**: https://github.com/ronmarasigan/LavaLust
- **YouTube Tutorials**: https://www.youtube.com/ronmarasigan
- **Issue Tracker**: https://github.com/ronmarasigan/LavaLust/issues

---

## 🛠️ Docker Commands Reference

### Container Management
```powershell
# Start
docker-compose up -d

# Stop
docker-compose down

# Restart
docker-compose restart

# View status
docker-compose ps

# View logs
docker-compose logs -f php
```

### Database
```powershell
# Access MySQL CLI
docker-compose exec mysql mysql -u root -proot lavalust_db

# Backup
docker-compose exec mysql mysqldump -u root -proot lavalust_db > backup.sql

# Restore
docker exec -i lavalust_mysql mysql -u root -proot lavalust_db < backup.sql
```

### PHP
```powershell
# Composer
docker-compose exec php composer install
docker-compose exec php composer require package/name

# PHP command
docker-compose exec php php -v
```

---

## ⚙️ Configuration Files

### Database (app/config/database.php)
✅ Pre-configured to use environment variables
✅ Defaults to Docker MySQL settings
✅ Supports MySQLi and PDO drivers

### Application (app/config/config.php)
Edit base URL:
```php
$config['base_url'] = 'http://localhost/';
```

### PHP Settings (docker/php/php.ini)
Adjust memory, upload size, execution time, etc.
Changes require rebuild: `docker-compose up -d --build`

### Web Server (docker/nginx/nginx.conf + docker/nginx/nginx.render.conf)
URL rewriting is pre-configured for LavaLust. There are two nearly-identical
files because nginx and php-fpm run as separate containers locally (nginx.conf
talks to `php:9000`) but share a single container on Render (nginx.render.conf
talks to `127.0.0.1:9000`). No changes needed for basic setup - just keep any
edit to one in sync with the other, aside from that one line.

One more difference between them under the hood: the Dockerfile installs
nginx via Alpine's `apk add nginx`, whose default nginx.conf includes
`/etc/nginx/http.d/*.conf` for server blocks (not `conf.d`, which Alpine's
package reserves for root-context snippets outside `http {}`) - so
nginx.render.conf gets copied to `/etc/nginx/http.d/default.conf` in the
Dockerfile, not `conf.d`. Locally, docker-compose uses the official
`nginx:alpine` image instead, which uses the more familiar `conf.d`
convention - that's why nginx.conf is still mounted there.

---

## 🚨 Troubleshooting

**"Cannot reach http://localhost"**
- Check Docker is running: `docker-compose ps`
- Wait 15-20 seconds for containers to start
- Check Nginx logs: `docker-compose logs nginx`

**"Cannot connect to database"**
- Verify MySQL is running: `docker-compose ps`
- Check credentials in `.env` match `docker-compose.yml`
- MySQL takes 10-15 seconds to start after container begins

**"Port 80 already in use"**
- Change port in `docker-compose.yml`:
  ```yaml
  nginx:
    ports:
      - "8000:80"  # Use 8000 instead of 80
  ```
- Restart: `docker-compose up -d`

**"Permission denied on storage folder"**
- Docker handles permissions automatically
- If issues persist: `docker-compose down -v && docker-compose up -d`

---

## 💡 Pro Tips

1. **Hot Reload** — Code changes appear instantly (volumes sync automatically)
2. **Database Persistence** — Data persists between stops (stored in Docker volume)
3. **Easy Reset** — `docker-compose down -v` deletes all data and volumes
4. **Multi-Project** — Create new folders for each project, repeat the setup
5. **Keep This Template** — Save it as a reference for future projects

---

## 🔄 Workflow for New Projects

Every time you want to start a new LavaLust project:

1. Extract this template to a new folder
2. Run `docker-compose up -d`
3. Start coding in `app/` folder
4. Database auto-initializes from `docker/mysql/init.sql`
5. Changes appear instantly at http://localhost

---

## 📝 License & Credits

- **LavaLust**: MIT License by Ronald M. Marasigan
- **Docker Template**: Created for your development workflow

---

**Happy building with LavaLust! 🌋**

---

## ☁️ Deploying to Render.com

This template also ships with a `render.yaml` Blueprint for deploying to [Render](https://render.com):

1. Push this repo to GitHub.
2. In Render, choose **New > Blueprint** and point it at this repo.
3. Render has no managed MySQL — after the first deploy, set `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` in the service's Environment tab to an external MySQL host (e.g. PlanetScale, Aiven, AWS RDS).
4. `docker-compose.yml` and `docker/mysql/init.sql` are for **local development only** and are not used by Render — the `Dockerfile` builds a single container (Nginx + PHP-FPM via supervisord) that listens on Render's `$PORT`.
5. Sessions default to `SESSION_DRIVER=database` on Render (set in `render.yaml`) since the container filesystem is ephemeral on free/starter plans — file uploads that need to persist should go to an external bucket or a paid Render disk mounted at `runtime/`.
