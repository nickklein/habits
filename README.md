This package is not recommended for commercial use. This is just something I built for myself, and making it public in case it's useful to somebody else.


To get started:

Add this git repository to your composer.json by adding 

    "repositories": [
        {
            "type": "cvs",
            "url": "https://github.com/nickklein/habits",
        }
    ],

For local you can use
    "repositories": [
        {
            "type": "path",
            "url": "../habits",
            "options": {
                "symlink": true
            }
        }
    ],

composer require nickklein/habits

Run the migrations using php artisan migrate
Run the seeders using `php artisan run:habits-seeder`
Run install.sh to create a symlink for the JSX files from habits to your core Laravel


## The package will automatically create some routes for you
