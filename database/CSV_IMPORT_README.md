# CSV Import Documentation

## User Import

The live user CSV importer is available on the super-admin user page and is handled by `modules/super-admin/adminUsers.php`.

Use this exact column order:

```csv
role_id,email,teacher_subrole,program,first_name,last_name,middle_name,contact_number,role
```

Required fields are `first_name`, `last_name`, `role`, and `email`. A `role_id` is also required for students, teachers, and discipline-office users.

Supported roles are `teacher`, `discipline_office`, `security`, `student`, and `super_admin`.

Teacher-specific rules:

- `teacher_subrole` may be blank or `department_head`.
- A `department_head` must have a valid `program`, such as `BSCS`, `BSIT`, or `BSTM`.
- A regular teacher must leave `program` blank.

The importer creates new accounts with the default password `password`. Use [users_import_test.csv](test_fixtures/users_import_test.csv) for local testing. Its records are synthetic and should be removed or reset before rerunning the import.

## Student Import Schema

The following schema is documented for student imports:

```
student_id,first_name,last_name,middle_name,grade_year,track_course,section,student_type,guardian_name,guardian_contact
```

The current `modules/do/studentHistory.php` does not contain a CSV upload handler, so this is not an active UI import workflow at present. The schema fixture is available at [students_import_test.csv](test_fixtures/students_import_test.csv) for a restored or external importer.

### Required Fields
- `student_id` - Student number/ID (must be unique)
- `first_name` - Student's first name
- `last_name` - Student's last name
- `grade_year` - Grade/year level (e.g., "11", "12", "1st Year", "2nd Year")

### Optional Fields
- `middle_name` - Student's middle name
- `track_course` - Track/Course (e.g., "STEM", "ABM", "BSIT", "BSCS")
- `section` - Section (e.g., "A", "B", "1A", "2B")
- `student_type` - Either "SHS" or "College"
- `guardian_name` - Guardian's full name
- `guardian_contact` - Guardian's contact number

## Student ID Requirement

- Student IDs are provided by your CSV file and are no longer auto-generated during import
- Student IDs should follow your school format (for example: **02000XXXXXX**)
- Duplicate student IDs are skipped and reported as errors

## Email Auto-Generation

- Email addresses are automatically generated using the format: **lastname.last6digits@sti.edu**
- Example: For student "Juan Dela Cruz" with ID 02000000031, the email will be: **delacruz.000031@sti.edu**
- Spaces in last names are removed
- Last names are converted to lowercase

## User Account Creation

Student IDs should use the school format `02000xxxxxx`. Duplicate IDs should be rejected by any implementation of this schema.
02000000101,Juan,Dela Cruz,Santos,11,STEM,A,SHS,Maria Dela Cruz,09171234567
