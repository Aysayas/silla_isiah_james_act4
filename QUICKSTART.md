# LavaLust Docker - Quick Reference Guide

## ⚡ 30-Second Setup

```powershell
# 1. Clone your LavaLust project (or download this template)
git clone https://github.com/YOUR_LAVALUST_REPO.git my-app
cd my-app

# 2. Copy Docker files from template (if not already there)
# Skip if you cloned the template directly

# 3. Start everything
docker-compose up -d

# 4. Visit http://localhost ✨
```

---

## 🔗 Access Points

| Service | URL | Credentials |
|---------|-----|-------------|
| **Your App** | http://localhost | N/A |
| **phpMyAdmin** | http://localhost:8080 | root / root |
| **MySQL** | localhost:3306 | lavalust_user / lavalust_pass |
| **Vite dev server (Tailwind, HMR)** | http://localhost:5173 | N/A |

---

## 🎯 Common Tasks

### Install PHP Dependencies
```powershell
docker-compose exec php composer install
```

### Run PHP Commands
```powershell
docker-compose exec php php your-script.php
```

### Access MySQL
```powershell
docker-compose exec mysql mysql -u root -proot lavalust_db
```

### View Logs
```powershell
docker-compose logs -f php
```

### Rebuild After Config Changes
```powershell
docker-compose down
docker-compose up -d --build
```

### Reset Everything
```powershell
docker-compose down -v  # Warning: deletes database!
docker-compose up -d
```

---

## 🔐 Database Credentials

**Development (Local)**
```
Host: mysql (from inside Docker)
Host: localhost:3306 (from your computer)
User: lavalust_user
Password: lavalust_pass
Database: lavalust_db
```

**Or use root:**
```
User: root
Password: root
```

---

## 📝 LavaLust Database Config

Edit your LavaLust config file (usually `app/config/config.php`):

```php
$config['database'] = array(
    'host' => 'mysql',           // Use 'mysql' inside Docker
    'user' => 'lavalust_user',
    'password' => 'lavalust_pass',
    'database' => 'lavalust_db',
);
```

---

## 🐛 Troubleshooting

| Problem | Solution |
|---------|----------|
| **Port 80 already in use** | Change port in docker-compose.yml: `"8000:80"` instead of `"80:80"` |
| **Can't connect to database** | Wait 30 seconds for MySQL to start, then retry |
| **File permissions error** | Run: `docker-compose down -v && docker-compose up -d` |
| **Docker not found** | Install Docker Desktop first |

---

## 📚 Full Documentation

See **README.md** for complete setup and configuration guide.

---

## 🚀 Next Steps

1. ✅ Start Docker: `docker-compose up -d`
2. ⚙️ Configure LavaLust database
3. 🔧 Install dependencies: `docker-compose exec php composer install`
4. 🌐 Visit http://localhost
5. 📖 Follow LavaLust tutorials from the official docs

---

## 📞 Need Help?

- **LavaLust Docs**: https://lavalust.netlify.app/
- **YouTube Tutorials**: https://www.youtube.com/ronmarasigan
- **GitHub Issues**: https://github.com/ronmarasigan/LavaLust/issues

Happy coding! 🌋


---

## 🎨 Frontend Stack (Tailwind CSS)

The template ships with Tailwind CSS, wired together with Vite, and a `node` service in `docker-compose.yml` that runs Vite's dev server for hot module reload.

### Development (hot reload)
```powershell
# Starts php, nginx, mysql, phpmyadmin AND the vite dev server
docker-compose up -d

# Visit http://localhost — the page pulls assets live from
# http://localhost:5173 with instant hot reload as you edit
# resources/js/app.js or resources/css/app.css
```

### Production build (no dev server, static assets via nginx)
```powershell
# Build hashed, optimized assets into public/build/
docker-compose exec node npm run build

# Set APP_ENV=production in .env, then the `vite()` PHP helper
# automatically reads public/build/.vite/manifest.json instead
# of the dev server.
```

### Where things live
| Path | Purpose |
|---|---|
| `resources/js/app.js` | Vite/JS entry point (plain JS — add whatever you need) |
| `resources/css/app.css` | Tailwind entry (`@import "tailwindcss";`) |
| `app/helpers/vite_helper.php` | The `vite()` helper — auto-switches between dev server and built assets |
| `public/build/` | Production build output (gitignored) |

The default homepage (`Welcome::index` → `app/views/welcome_page.php`) is
stock LavaLust and doesn't call `vite()`. Tailwind/Vite is wired up and ready
to use, but it's opt-in — call `<?php echo vite('resources/js/app.js'); ?>`
in the head of whatever view you build to pull it in.
