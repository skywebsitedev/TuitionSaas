<h1 align="center">Surf 🏄‍♀️</h1>

## Introduction

Surf, the opinionated Software as a Service Starter Kit that can help you build your next great idea 💰. Surf is fork off , and is built with [Laravel](https://laravel.com), [Voyager](https://voyager.devdojo.com), [TailwindCSS](https://tailwindcss.com), and a few other awesome technologies. Here are some of the awesome features ✨:


## Deployment Issues

Surf has not been tested with DigitalOceans APP platform. If you are using Surf on DigitalOcean, don't be surprised if you experience issues. Docker has also not been tested and is not recommended.

## Installation

To install Surf, you'll want to clone or download this repo:

```
git clone https://github.com/thinkverse/surf.git project_name
```

Next, we can install Surf with these **5 simple steps**:

### 1. Create a New Database

We'll need to utilize a MySQL database during the installation. For the following stage, you'll need to create a new database and preserve the credentials.

### 2. Copy the `.env.example` file

We need to specify our Environment variables for our application. You will see a file named `.env.example`, you will need to duplicate that file and rename it to `.env`.

Then, open up the `.env` file and update your *DB_DATABASE*, *DB_USERNAME*, and *DB_PASSWORD* in the appropriate fields. You will also want to update the *APP_URL* to the URL of your application.

```bash
APP_URL=http://surf.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=surf
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Add Composer Dependencies

Following that, we'll need to install all composer dependencies through the following command:

```php
composer install
```

### 4. Run Migrations and Seeds

We must migrate our database schema into our database, which we can accomplish by running the following command:

```php
php artisan migrate
```

Finally, we will need to seed our database with the following command:

```php
php artisan db:seed
```

That's it! You will now be able to visit your URL and see your Surf application up and running. 🎉

