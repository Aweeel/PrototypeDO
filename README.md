# PrototypeDOMS

PHP discipline-office application backed by MySQL 8.0+.

Set `MYSQL_HOST`, `MYSQL_PORT`, `MYSQL_USER`, `MYSQL_PASSWORD`, and `MYSQL_DATABASE` in the environment, then run `database/schema.sql` against the target MySQL server. The default local database is `PrototypeDO_DB` on `127.0.0.1:3306` using the `root` account with an empty password.

New case IDs use the `YYYY-NT-####` format, for example `2026-1T-0001`. The `NT` segment is derived from the configured semester dates: `1T` for the first semester and `2T` for the second semester. It defaults to `1T` when the semester dates are unavailable.
