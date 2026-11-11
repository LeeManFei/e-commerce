# E-commerce

PHP e-commerce project with a MySQL database, PHPMailer email flows, and Stripe Checkout.

## Setup

1. Create a MySQL database named `e-commerce` and import `e-commerce.sql`. The SQL file contains the table structure only; personal records from the local database export were removed.
2. Configure the PHP environment variables shown in `.env.example` for your local server. PHP does not load `.env` files automatically; configure these values in Apache/PHP or your deployment environment.
3. Set `APP_BASE_PATH` in `app/_base.php`'s `BASE_URL` definition to the URL path where the `app` directory is hosted.
4. Open the `app` directory through a PHP-enabled web server.

Keep `.env` and live credentials out of version control. Email verification and Stripe Checkout need their respective SMTP and Stripe environment variables to be configured.
