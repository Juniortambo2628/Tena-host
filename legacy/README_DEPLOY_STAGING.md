Staging deployment instructions for Tena under stayawhile-rentals.com

1. Upload the application

- Copy the entire project to `public_html/Tena` on the staging server.
- Ensure `vendor/` folder is uploaded (we preinstalled Composer deps).

FTP upload details (staging)

- FTP server: `ftp.okjtech.co.ke` (or 51.89.113.223)
- FTP port: `21` (FTP / explicit FTPS)
- FTP username: `juniortambo2628@okjtech.co.ke`
- FTP password: `Tenahost_Dev`
- Upload destination: `public_html/apps/Tena`

2. Database

- Create a MySQL database named `tena_waitlist` and a user `Tenahost_Dev` with password `Tenahost_Dev`.
- Import `database_setup_staging.sql` using phpMyAdmin or your hosting control panel.
 - On the new host use database name `zhpebukm_tena_waitlist` with user `zhpebukm_dev` and password `Tenahost_Dev`.
 - Import `database_setup_staging.sql` using phpMyAdmin or your hosting control panel.

3. Configuration

- In `config/config.php` automatic environment detection maps `stayawhile-rentals.com` to `staging` and sets sensible SMTP defaults.
- If you prefer environment variables, set the following in your hosting panel (preferred):
  - MAILER_SMTP_HOST
  - MAILER_SMTP_USER
  - MAILER_SMTP_PASS
  - MAILER_SMTP_PORT
  - MAILER_SMTP_SECURE
  - MAILER_FROM
  - MAILER_FROM_NAME

4. File permissions

- Ensure `data/` and `admin/exports/` are writable by the webserver.

5. Cron job (recommended)

- To process scheduled exports, add a cron entry to run every minute:
  php /home/youruser/public_html/apps/Tena/admin/cli/process_schedules.php

6. Testing

- Visit `https://stayawhile-rentals.com/Tena/admin/users.php` and log in with `admin`.
- Use the Users -> Export UI to test CSV and PDF exports.
