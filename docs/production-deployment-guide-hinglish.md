# Ludo Production Build Aur Server Deployment Guide

Ye guide hamare current project ke liye hai jisme 3 main parts hain:

1. `unity` mobile app / game client
2. `backend_laravel` main API + admin panel + user panel
3. `node` socket / real-time game server

Is guide ka goal hai:

- production-safe build banana
- correct server URLs set karna
- Laravel + Node ko live server par chalana
- final smoke test karke release karna

---

## 1. Project Structure Samajh Lo

Current working folders:

- Unity client: `D:\Live-Code\games\unity`
- Laravel backend: `D:\Live-Code\games\backend_laravel`
- Node socket server: `D:\Live-Code\games\node`

Production flow:

1. user app se login karta hai
2. Unity app Laravel API ko hit karti hai
3. classic Ludo / tournament room flow me Node socket use hota hai
4. tournament/admin/user panel web side Laravel serve karta hai

---

## 2. Production Se Pehle Kya Final Check Karna Hai

Build banane se pehle ye confirm karo:

- Laravel local par properly chal raha ho
- Node socket local par properly chal raha ho
- Unity me login, homepage, classic Ludo, tournament, wallet flow chal raha ho
- admin panel me:
  - games visibility
  - classic fee tables
  - tournaments
  - user panel permissions
  sahi kaam kar rahe hon
- production log guard project me present ho

Production log guard file:

- [ProductionLogGuard.cs](D:/Live-Code/games/unity/Assets/_Project/Core/Scripts/Utilities/ProductionLogGuard.cs)

Iska matlab:

- Editor aur Development build me logs aayenge
- production build me logs mute ho jayenge

---

## 3. Unity Production Build Ke Liye Kya Change Karna Hai

Sabse important file:

- [Configuration.cs](D:/Live-Code/games/unity/Assets/_Project/Core/Configurations/Configuration.cs)

Current local values:

- `BaseUrl = "http://localhost:8000/"`
- `Website = "http://localhost:8000/"`
- `BaseSocketUrl = "http://localhost:3002"`

Production me inko live domain par change karna hoga.

Example:

```csharp
public const string BaseUrl = "https://yourdomain.com/";
public const string Website = "https://yourdomain.com/";
public const string BaseSocketUrl = "https://socket.yourdomain.com";
```

Important:

- `BaseUrl` ke end me slash `/` rehna chahiye
- `BaseSocketUrl` me normal socket server ka public URL aana chahiye
- agar socket same domain ke reverse proxy se chal raha hai to wahi URL do

Unity production config change ke baad in flows ko test karo:

1. login
2. homepage
3. Ludo game open
4. classic 2-player
5. classic 4-player
6. tournament list
7. tournament detail
8. private join
9. wallet deduction on register

---

## 4. Unity Build Kaise Banana Hai

Unity me:

1. project open karo
2. `File -> Build Settings`
3. correct platform select karo:
   - Android
   - ya iOS
4. `Development Build` unchecked rakho
5. `Script Debugging` unchecked rakho
6. release build signing sahi set karo
7. build generate karo:
   - Android APK ya AAB

Recommended:

- Play Store ke liye `AAB`
- manual testing / direct install ke liye `APK`

Android release checklist:

1. package name final ho
2. app icon final ho
3. splash image final ho
4. server URLs production wale ho
5. development build off ho
6. final build install karke live login test ho

---

## 5. Laravel Backend Production Deploy

Main backend folder:

- `D:\Live-Code\games\backend_laravel`

### 5.1 Server Requirements

Recommended stack:

- Ubuntu server
- Nginx
- PHP 8.2+
- MySQL / MariaDB
- Redis
- Composer
- Supervisor

### 5.2 Code Server Par Kaise Daalna Hai

Options:

1. Git clone karo
2. ya zip/upload karke extract karo

Recommended structure:

- `/var/www/games/backend_laravel`

### 5.3 Laravel `.env` Production Values

Base example:

```env
APP_NAME=Games
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_user
DB_PASSWORD=your_password

SESSION_DRIVER=database
QUEUE_CONNECTION=redis
CACHE_STORE=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

LUDO_SOCKET_NAMESPACE=/ludo
```

Current reference:

- [backend_laravel/.env.example](D:/Live-Code/games/backend_laravel/.env.example)

Production ke liye ye zaroor change karo:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://yourdomain.com`
- database credentials
- mail settings
- payment settings
- redis settings

### 5.4 Laravel Install Commands

Server par `backend_laravel` folder me run karo:

```powershell
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

अगर seeders required hon to:

```powershell
php artisan db:seed --force
```

### 5.5 Queue Worker Aur Scheduler

Tournament, support, automation, status updates ke liye queue/scheduler chalna chahiye.

Supervisor me queue worker:

```ini
[program:games-queue]
command=php /var/www/games/backend_laravel/artisan queue:work --sleep=3 --tries=3 --timeout=120
directory=/var/www/games/backend_laravel
autostart=true
autorestart=true
user=www-data
stdout_logfile=/var/log/games-queue.log
stderr_logfile=/var/log/games-queue-error.log
```

Cron me scheduler:

```cron
* * * * * cd /var/www/games/backend_laravel && php artisan schedule:run >> /dev/null 2>&1
```

---

## 6. Nginx Setup For Laravel

Example site config idea:

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;

    root /var/www/games/backend_laravel/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

Uske baad SSL lagao:

- Let’s Encrypt / Certbot

Final domain:

- `https://yourdomain.com`

Admin panel:

- `https://yourdomain.com/admin`

User panel:

- `https://yourdomain.com/panel`

---

## 7. Node Socket Server Production Deploy

Socket folder:

- `D:\Live-Code\games\node`

### 7.1 Node `.env`

Current important references:

- [node/.env.example](D:/Live-Code/games/node/.env.example)
- [node/.env](D:/Live-Code/games/node/.env)

Tournament services Laravel API ko yahan se hit karte hain:

- [tournamentLudoRoomService.js](D:/Live-Code/games/node/services/tournamentLudoRoomService.js)
- [tournamentMatchResultService.js](D:/Live-Code/games/node/services/tournamentMatchResultService.js)

Inme default:

- `LARAVEL_API_BASE_URL=http://127.0.0.1:8000/api`

Production `.env` example:

```env
PORT=3002
DB_HOST=127.0.0.1
DB_PORT=3306
DB_USERNAME=your_user
DB_PASSWORD=your_password
DB_NAME=your_database
LARAVEL_API_BASE_URL=https://yourdomain.com/api
```

### 7.2 Node Install Aur Start

Server par `node` folder me:

```powershell
npm install
```

PM2 recommended:

```powershell
pm2 start server.js --name games-socket
pm2 save
pm2 startup
```

### 7.3 Reverse Proxy for Socket

Recommended domain:

- `https://socket.yourdomain.com`

Nginx reverse proxy example:

```nginx
server {
    listen 80;
    server_name socket.yourdomain.com;

    location / {
        proxy_pass http://127.0.0.1:3002;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }
}
```

Then SSL lagao.

Unity me `BaseSocketUrl`:

```csharp
https://socket.yourdomain.com
```

---

## 8. Production Build Se Pehle Final Value Mapping

Ye 3 cheezein same production environment par point karni chahiye:

1. Unity `BaseUrl`
2. Unity `BaseSocketUrl`
3. Node `LARAVEL_API_BASE_URL`

Example:

- Unity `BaseUrl` -> `https://yourdomain.com/`
- Unity `BaseSocketUrl` -> `https://socket.yourdomain.com`
- Node `LARAVEL_API_BASE_URL` -> `https://yourdomain.com/api`

---

## 9. Database Aur Data Safety

Production deploy se pehle:

1. existing database backup lo
2. current `.env` ka backup lo
3. file uploads backup lo
4. deployment ke time app ko short maintenance mode me rakh sakte ho

Useful commands:

```powershell
php artisan down
php artisan migrate --force
php artisan up
```

Note:

- maintenance mode sirf tab lagao jab live database migrations risky hon
- agar small safe deploy hai to short downtime enough hai

---

## 10. Build Ke Baad Live Smoke Test

Ye sabse important section hai.

### 10.1 Web Smoke Test

Check:

1. website open ho
2. admin login ho
3. user login ho
4. admin panel dashboard open ho
5. user panel dashboard open ho
6. tournament report open ho
7. classic fee table admin page open ho

### 10.2 Unity Smoke Test

1. fresh install APK/AAB build
2. signup / login
3. homepage par sirf enabled games dikhe
4. Ludo open ho
5. classic 2-player fee table list correct ho
6. classic 4-player fee table list correct ho
7. tournament page load ho
8. tournament detail open ho
9. register ho
10. wallet deduction ho
11. my tournament history show ho

### 10.3 Socket Smoke Test

2 ya 4 real users ke saath:

1. same fee table join karo
2. room create ho
3. game start ho
4. dice turn chale
5. pawn move ho
6. finish result aaye
7. room complete ho

### 10.4 Tournament Smoke Test

1. user tournament create kare
2. admin review kare
3. approve kare
4. user register kare
5. tournament progress ho
6. report update ho
7. payout / winner result verify ho

---

## 11. Production Release Order

Best release order:

1. Laravel backend deploy
2. migrations run
3. cache clear/cache rebuild
4. Node socket deploy/restart
5. domain + SSL verify
6. Unity production URLs set
7. production APK/AAB build
8. live smoke test
9. then public release

---

## 12. Restart Commands

Laravel cache clear if needed:

```powershell
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Queue restart:

```powershell
php artisan queue:restart
```

PM2 socket restart:

```powershell
pm2 restart games-socket
```

PHP-FPM restart:

```powershell
sudo systemctl restart php8.2-fpm
```

Nginx reload:

```powershell
sudo systemctl reload nginx
```

---

## 13. Agar Build Ke Baad Problem Aaye To Kahan Check Karna Hai

### Unity side

- wrong API URL
- wrong socket URL
- development build accidentally on
- old APK install

### Laravel side

- `.env` wrong
- `APP_URL` wrong
- redis/queue off
- scheduler off
- storage link missing
- permissions issue

### Node side

- `PORT` wrong
- PM2 process down
- reverse proxy wrong
- `LARAVEL_API_BASE_URL` wrong
- DB credentials wrong

---

## 14. Final Production Checklist

Release se just pehle:

- [ ] Unity `BaseUrl` production domain par hai
- [ ] Unity `BaseSocketUrl` production socket domain par hai
- [ ] `Development Build` off hai
- [ ] Laravel `APP_ENV=production`
- [ ] Laravel `APP_DEBUG=false`
- [ ] Laravel migrations run ho chuki hain
- [ ] Laravel queue worker running hai
- [ ] Laravel scheduler running hai
- [ ] Node socket live chal raha hai
- [ ] SSL active hai
- [ ] admin login test ho gaya
- [ ] user login test ho gaya
- [ ] 2-player classic test ho gaya
- [ ] 4-player classic test ho gaya
- [ ] tournament create/register test ho gaya
- [ ] wallet deduction test ho gaya

---

## 15. Best Practical Recommendation

Live push karne se pehle 2 builds banao:

1. internal QA build
   - same production server
   - but limited testers
2. final public build

Isse agar koi URL/socket/payment issue aata hai to public users tak nahi jayega.

---

## 16. Agar Aap Mere Saath Release Karna Chaho To Order Ye Rahega

Main recommend karta hoon:

1. pehle production domain final karo
2. phir Laravel `.env` set karo
3. phir Node `.env` set karo
4. phir Unity `Configuration.cs` me URLs update karo
5. phir server deploy
6. phir QA build
7. phir smoke test
8. phir final production build

Is order me sabse kam risk hota hai.
