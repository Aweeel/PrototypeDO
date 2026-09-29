# PrototypeDOMS

PHP discipline-office application backed by MySQL 8.0+.

Set the database credentials in the environment before running the app. The connection supports both `MYSQL_HOST`, `MYSQL_PORT`, `MYSQL_USER`, `MYSQL_PASSWORD`, and `MYSQL_DATABASE`, plus service-provided aliases `MYSQLHOST`, `MYSQLPORT`, `MYSQLUSER`, `MYSQLPASSWORD`, and `MYSQLDATABASE`. The default host is Azure MySQL at `domsdb.mysql.database.azure.com:3306`; `MYSQL_SSL_CA` can be set when the server requires a custom CA. Run `database/schema.sql` against the target MySQL server before using the app.

New case IDs use the `YYYY-NT-####` format, for example `2026-1T-0001`. The `NT` segment is derived from the configured semester dates: `1T` for the first semester and `2T` for the second semester. It defaults to `1T` when the semester dates are unavailable.
