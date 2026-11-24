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
- `system_manager`

The user importer creates accounts with the default password `password`. Use a database reset or otherwise remove the synthetic records before rerunning the fixture.

## Student import schema

The documented student CSV format is described in `database/CSV_IMPORT_README.md`. There is currently no student CSV fixture or upload handler in this repository; use the documented sample row with an external or restored importer if needed.
