# Student CSV Import - Documentation

## How to Import Students from CSV

The Student History page now supports importing students from a CSV file. This feature allows you to:
- Add new students in bulk
- Student numbers are automatically generated in the format: 02000XXXXXX
- **User accounts are automatically created** for each student
- **Email addresses are auto-generated** in the format: lastname.last6digits@sti.edu

## CSV File Format

Your CSV file must include the following columns (in this exact order):

```
first_name,last_name,middle_name,grade_year,track_course,section,student_type,guardian_name,guardian_contact
```

**Note:** Student IDs and email addresses are auto-generated and should NOT be included in your CSV file.

### Required Fields
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

## Student ID Auto-Generation

- Student IDs are automatically generated in the format: **02000XXXXXX** (e.g., 02000000001, 02000000002, etc.)
- The system finds the most recent student ID and increments it by 1
- If no students exist, the first ID will be **02000000001**

## Email Auto-Generation

- Email addresses are automatically generated using the format: **lastname.last6digits@sti.edu**
- Example: For student "Juan Dela Cruz" with ID 02000000031, the email will be: **delacruz.000031@sti.edu**
- Spaces in last names are removed
- Last names are converted to lowercase

## User Account Creation

When a student is imported via CSV:
- A user account is automatically created in the system
- **Username**: The auto-generated email address
- **Password**: `password` (default password for all new students)
- **Role**: student
- Students can log in using their email and default password
- **Important**: Students should change their password after first login

## Sample CSV File

A sample template file is available at: `database/student_import_template.csv`

Example content:
```csv
first_name,last_name,middle_name,grade_year,track_course,section,student_type,guardian_name,guardian_contact
Juan,Dela Cruz,Santos,11,STEM,A,SHS,Maria Dela Cruz,09171234567
Maria,Garcia,Lopez,12,ABM,B,SHS,Pedro Garcia,09187654321
Jose,Reyes,Martin,1st Year,BSIT,1A,College,Carmen Reyes,09191234567
Ana,Santos,Cruz,2nd Year,BSCS,2B,College,Roberto Santos,09171112222
```

## How to Use

1. Click the **"Import CSV"** button on the Student History page
2. Select your CSV file (must have .csv extension)
3. Click **"Upload and Import"**
4. Wait for the import to complete
5. Review the results:
   - Number of students imported
   - Number of rows skipped (if any)
   - Any errors encountered

## Important Notes

- Student IDs are **automatically generated** - do NOT include them in your CSV file
- New students are added with auto-generated IDs in format: 02000XXXXXX
- Rows with missing required fields (first_name, last_name, grade_year) will be skipped
- The page will automatically refresh after a successful import
- Maximum file size depends on your server configuration
- Each import creates new students - duplicates are not automatically detected by name

## Filters

The Student History page includes the following filters:

### Search Filter
- Search by student ID, first name, or last name
- Updates results in real-time

### Grade Filter
Available options:
- **Senior High School**: Grade 11, Grade 12
- **College**: 1st Year, 2nd Year, 3rd Year, 4th Year
- **All Levels**: Shows all students

All filters work together to help you find specific students quickly.

## Removed Features

The "Add Note" button has been removed from this page. To add notes or comments about students, please use the case management system.
