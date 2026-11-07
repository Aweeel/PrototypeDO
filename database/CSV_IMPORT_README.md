# CSV Import Documentation

## User Import

The live user CSV importer is available on the system manager user page and is handled by `modules/super-admin/adminUsers.php`.

Use this column order (the student columns are required for student rows):

```csv
role_id,email,teacher_subrole,program,first_name,last_name,middle_name,contact_number,role,grade_year,track_course,section,student_type,guardian_name,guardian_contact
```

Required fields are `first_name`, `last_name`, `role`, `role_id`, and `email`. Email addresses must be unique. Student rows additionally require `grade_year` and `student_type` (`SHS` or `College`). `track_course`, `section`, `guardian_name`, and `guardian_contact` may be blank.

Role ID formats are `02000xxxxxx` for students, `01000xxxxxx` for teachers, and `03000xxxxxx` for discipline-office staff.

Supported roles are `teacher`, `discipline_office`, `security`, `student`, and `system_manager`.

Teacher-specific rules:

- `teacher_subrole` may be blank or `department_head`.
- A `department_head` must have one of these programs: `BSCS`, `BSIT`, `BSCpE`, `BSMA`, `BSA`, `BSHM`, `BMMA`, `BACOMM`, `BAPsych`, `BSTM`, or `BSCRIM`.
- A regular teacher must leave `program` blank.

The importer creates new accounts with the default password `password`. Existing student IDs are updated and reactivated. After a successful import, active students whose IDs are missing from the new CSV are archived together with their linked accounts; the preview lists those students before any changes are made. Use [users_import_test.csv](test_fixtures/users_import_test.csv) for local testing. Its records are synthetic and should be removed or reset before rerunning the import.

## Student Import Schema

The following schema is documented for student imports:

```
student_id,first_name,last_name,middle_name,grade_year,track_course,section,student_type,guardian_name,guardian_contact
```

The DO student list has an archive view. System-manager user imports maintain the student archive state, and DO staff can archive or restore individual students from that view.

### Required Fields
- `student_id` - Student number/ID (must be exactly 11 digits and unique)
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
- Student IDs must contain exactly 11 numeric digits (for example: **02000000001**)
- Duplicate student IDs are skipped and reported as errors

## Email Auto-Generation

- Email addresses are automatically generated using the format: **lastname.last6digits@sti.edu**
- Example: For student "Juan Dela Cruz" with ID 02000000031, the email will be: **delacruz.000031@sti.edu**
- Spaces in last names are removed
- Last names are converted to lowercase

## User Account Creation

Student IDs must contain exactly 11 numeric digits. Duplicate IDs should be rejected by any implementation of this schema.
02000000101,Juan,Dela Cruz,Santos,11,STEM,A,SHS,Maria Dela Cruz,09171234567
