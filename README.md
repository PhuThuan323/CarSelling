# PHP Smarty Shop Starter

A lightweight PHP shopping cart application using Smarty template engine.

## Project Structure

```
CarSelling/
├── app/
│   ├── Controllers/       # Application controllers
│   ├── Models/           # Data models
│   ├── Core/             # Core application classes
│   └── helpers.php       # Helper functions
├── database/             # Database schema and seeds
├── public/               # Web root
│   ├── index.php         # Application entry point
│   ├── router.php        # Router handler
│   └── assets/           # CSS, JS, images
├── templates/            # Smarty templates
├── storage/              # Cache and compiled templates
└── composer.json         # PHP dependencies
```

## Installation

1. Clone the repository
2. Install dependencies: `composer install`
3. Configure database in `app/Core/Database.php`
4. Run migrations: `php database/schema.sql`
5. Start development server: `composer start`

## Features

- MVC architecture
- Smarty template engine integration
- Database abstraction layer
- JSON API responses
- Simple router system

## Requirements

- PHP 7.4+
- Composer
- Database (MySQL/PostgreSQL)

## Development

Start the built-in server:
```bash
composer start
```

Visit `http://localhost:8000`
