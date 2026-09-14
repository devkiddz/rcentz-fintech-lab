RCENTZ FINTECH LAB - INSTALLER MILESTONE

Overlay this package into the Laravel project root and allow overwrite.

Adds:
- /install GET + POST routes
- requirements checker
- app/database/admin setup form
- core reference-data seeder
- optional synthetic demo-data seed
- automatic migrations
- storage link attempt
- install lock at storage/app/installed
- standalone modern installer UI

After overlay:
1. php artisan optimize:clear
2. php artisan route:list --path=install
3. Visit http://127.0.0.1:8000/install

The database must already exist. The installer configures and tests the connection; it does not create MySQL databases.
