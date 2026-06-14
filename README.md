# SecureVote - Online Voting System with 2FA

## Overview
A secure starter for large-scale online elections (10k+ voters) with TOTP 2FA.

## Tech Stack
- Frontend: HTML5, CSS3, Vanilla JS
- Backend: PHP 8+
- DB: MySQL/MariaDB
- 2FA: Pure PHP TOTP (compatible with Google Authenticator)

## Setup Instructions

1. **Database**
   - Create DB `online_voting`
   - Import `sql/schema.sql`
   - Update `config/database.php` credentials

2. **PHP Environment**
   - PHP 8+ with PDO MySQL
   - Place project in web root (e.g., `/var/www/online-voting`)

3. **2FA Setup**
   - After login, admins/voters can generate secret in profile (extend `api/2fa_setup.php`)
   - Use Google Authenticator app to scan QR (add QR generation with GD library)

4. **Security Notes**
   - Passwords use Argon2
   - Prepared statements everywhere
   - CSRF, rate limiting, session regen
   - Audit logs
   - Production: Use HTTPS, proper env vars, WAF, rate limit with Redis

5. **Extend**
   - Add `api/elections.php`, `api/results.php`, candidate management
   - Implement admin CRUD for elections/candidates
   - Add email verification

## Testing
- Register voter
- Login, setup 2FA
- Admin login (admin@example.com / password)
- Cast votes

**For production scale: Add caching, horizontal scaling, blockchain for immutability if needed.**

Security first! Review all code before deployment.