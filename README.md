This package is not recommended for commercial use. This is just something I built for myself, and making it public in case it's useful to somebody else.

Here's how to to get started:
Add this git repository to your Laravel Project composer.json by adding one of the following:

### Live
composer config repositories.0 '{"type": "vcs", "url": "https://github.com/nickklein/habits"}'
### For local you can use
composer config repositories.0 '{"type": "path", "url": "../habits", "options": {"symlink": true}}'

### Then
composer require nickklein/habits

1. Run the migrations using php artisan migrate + php artisan db:seed
2. Run the seeders using `php artisan run:habits-seeder`
3. Run install.sh to create a symlink for the JSX files from habits to your core Laravel

