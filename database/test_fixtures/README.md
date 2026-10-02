# CSV Import Test Fixtures

These files use synthetic records and IDs intended for local testing.

## User import

`users_import_test.csv` targets `modules/super-admin/adminUsers.php`.
It includes one row for each supported role:

- `teacher` with `department_head` and a `BSCS` program
- regular `teacher` without a program
- `discipline_office`
- `student`
- `security`
- `super_admin`

The user importer creates accounts with the default password `password`. Use a database reset or otherwise remove the synthetic records before rerunning the fixture.

## Student import schema

`students_import_test.csv` follows the documented student CSV format in `database/CSV_IMPORT_README.md` and covers SHS plus first- through fourth-year college rows. The current `modules/do/studentHistory.php` does not contain a CSV upload handler, so this fixture is for the documented schema and any restored or external student importer.
