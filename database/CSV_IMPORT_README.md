# Student CSV Import - Documentation

## How to Import Students from CSV

The Student History page now supports importing students from a CSV file. This feature allows you to:
- Add new students in bulk
- Update existing student information

## CSV File Format

Your CSV file must include the following columns (in this exact order):

```
student_id,first_name,last_name,middle_name,grade_year,track_course,section,student_type,guardian_name,guardian_contact
```

### Required Fields
- `student_id` - Unique student identifier
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

## Sample CSV File

A sample template file is available at: `database/student_import_template.csv`

Example content:
```csv
student_id,first_name,last_name,middle_name,grade_year,track_course,section,student_type,guardian_name,guardian_contact
2024-001,Juan,Dela Cruz,Santos,11,STEM,A,SHS,Maria Dela Cruz,09171234567
2024-002,Maria,Garcia,Lopez,12,ABM,B,SHS,Pedro Garcia,09187654321
2024-003,Jose,Reyes,Martin,1st Year,BSIT,1A,College,Carmen Reyes,09191234567
2024-004,Ana,Santos,Cruz,2nd Year,BSCS,2B,College,Roberto Santos,09171112222
```

## How to Use

1. Click the **"Import CSV"** button on the Student History page
2. Select your CSV file (must have .csv extension)
3. Click **"Upload and Import"**
4. Wait for the import to complete
5. Review the results:
   - Number of students imported/updated
   - Number of rows skipped (if any)
   - Any errors encountered

## Important Notes

- If a student with the same `student_id` already exists, their information will be **updated**
- If a student doesn't exist, they will be **added** as a new record
- Rows with missing required fields will be skipped
- The page will automatically refresh after a successful import
- Maximum file size depends on your server configuration

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
