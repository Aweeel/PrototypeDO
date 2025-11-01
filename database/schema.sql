-- ============================================
-- PrototypeDO Database Schema - COMPLETE VERSION WITH 2025 CASES
-- SQL Server 2019+
-- Discipline Office Management System
-- Safe to run multiple times - prevents duplicates
-- ============================================

USE master;
GO

-- ============================================
-- UPDATE STUDENT OFFENSE COUNTS
-- ============================================

UPDATE students 
SET 
    total_offenses = (SELECT COUNT(*) FROM cases WHERE student_id = students.student_id AND is_archived = 0),
    major_offenses = (SELECT COUNT(*) FROM cases WHERE student_id = students.student_id AND severity = 'Major' AND is_archived = 0),
    minor_offenses = (SELECT COUNT(*) FROM cases WHERE student_id = students.student_id AND severity = 'Minor' AND is_archived = 0),
    last_incident_date = (SELECT MAX(date_reported) FROM cases WHERE student_id = students.student_id)
WHERE student_id IN (
    SELECT DISTINCT student_id FROM cases
);
GO

-- ============================================
-- INSERT CASE HISTORY FOR ALL CASES
-- ============================================

INSERT INTO case_history (case_id, changed_by, action, new_value, notes, timestamp)
SELECT 
    case_id,
    2, -- DO staff user
    'Created',
    'Status: ' + status,
    'Case created and logged into system',
    DATEADD(MINUTE, 5, CAST(date_reported AS DATETIME) + CAST(ISNULL(time_reported, '08:00:00') AS DATETIME))
FROM cases;
GO

-- ============================================
-- INSERT SAMPLE LOST & FOUND ITEMS
-- ============================================

INSERT INTO lost_found_items (item_id, item_name, category, found_location, date_found, status, description)
VALUES 
('LF-1001', 'Blue Backpack', 'Bags', 'Cafeteria', '2023-10-14', 'Unclaimed', 'Blue JanSport backpack with laptop'),
('LF-1002', 'Water Bottle', 'Personal Items', 'Gym', '2023-10-13', 'Unclaimed', 'Stainless steel water bottle'),
('LF-1003', 'Math Textbook', 'Books', 'Library', '2023-10-12', 'Claimed', 'Grade 11 Math textbook'),
('LF-1004', 'Calculator', 'Electronics', 'Room C401', '2023-10-08', 'Claimed', 'Scientific calculator Casio fx-991');
GO

-- ============================================
-- INSERT WATCH LIST ENTRIES
-- ============================================

-- Add student 2024002007 to watch list (has 2 major offenses)
INSERT INTO watch_list (student_id, reason, added_by, added_date, notes)
VALUES 
('2024002007', 'Multiple major offenses: Cheating incidents in 2024 and 2025. Requires close monitoring.', 2, '2025-02-15', 
 'Student requires close monitoring. Consider probation if another offense occurs.'),
 
-- Add student 2024001241 to watch list (smoking + previous violations)
('2024001241', 'Major offense: Smoking on campus. Multiple previous uniform violations.', 2, '2025-03-11', 
 'Requires behavioral intervention. Parent involvement necessary. Suspension under consideration.');
GO

-- ============================================
-- INSERT SAMPLE SANCTIONS APPLIED
-- ============================================

INSERT INTO case_sanctions (case_id, sanction_id, applied_date, is_completed, completion_date, notes)
VALUES
-- C-2025001 - Verbal Warning
('C-2025001', 1, '2025-01-15', 1, '2025-01-15', 'Student acknowledged warning and committed to improvement'),

-- C-2025002 - Written Reprimand
('C-2025002', 3, '2025-01-20', 1, '2025-01-20', 'Written reprimand issued and filed'),

-- C-2025007 - Verbal Warning + Counseling
('C-2025007', 1, '2025-03-20', 1, '2025-03-20', 'Counseling completed with both students'),

-- C-2025009 - Verbal Warning
('C-2025009', 1, '2025-04-15', 1, '2025-04-15', 'Educational discussion conducted about campus policies');
GO

-- ============================================
-- INSERT SAMPLE NOTIFICATIONS
-- ============================================

INSERT INTO notifications (user_id, title, message, type, related_id, is_read)
VALUES
(2, 'New Case Reported', 'New cyberbullying case C-2025010 requires immediate attention', 'case_update', 'C-2025010', 0),
(2, 'Case Under Review', 'Case C-2025006 (Smoking) requires decision on sanctions', 'case_update', 'C-2025006', 0),
(2, 'Escalated Case', 'Case C-2025004 has been escalated to Academic Council', 'case_update', 'C-2025004', 1);
GO

-- ============================================
-- FINAL VERIFICATION & SUMMARY
-- ============================================

PRINT '============================================';
PRINT 'Database Schema Created Successfully!';
PRINT '============================================';
PRINT '';
PRINT 'Default Login Credentials:';
PRINT 'Username: admin';
PRINT 'Password: password';
PRINT '';
PRINT 'OR';
PRINT '';
PRINT 'Username: do_staff';
PRINT 'Password: password';
PRINT '';
PRINT '⚠️  IMPORTANT: Change passwords after first login!';
PRINT '';

-- Show detailed summary
SELECT 
    'Total Students' AS metric, 
    COUNT(*) AS count 
FROM students
UNION ALL
SELECT 
    'SHS Students', 
    COUNT(*) 
FROM students 
WHERE student_type = 'SHS'
UNION ALL
SELECT 
    'College Students', 
    COUNT(*) 
FROM students 
WHERE student_type = 'College'
UNION ALL
SELECT 
    'Total Cases', 
    COUNT(*) 
FROM cases
UNION ALL
SELECT 
    'Active Cases', 
    COUNT(*) 
FROM cases 
WHERE is_archived = 0
UNION ALL
SELECT 
    '2025 Cases', 
    COUNT(*) 
FROM cases 
WHERE case_id LIKE 'C-2025%'
UNION ALL
SELECT 
    'Offense Types', 
    COUNT(*) 
FROM offense_types
UNION ALL
SELECT 
    'Sanctions', 
    COUNT(*) 
FROM sanctions
UNION ALL
SELECT 
    'Users', 
    COUNT(*) 
FROM users
UNION ALL
SELECT 
    'Lost & Found Items', 
    COUNT(*) 
FROM lost_found_items;
GO

PRINT '';
PRINT '============================================';
PRINT 'STUDENT BREAKDOWN BY TRACK/COURSE';
PRINT '============================================';

-- Count by track/course
SELECT 
    track_course,
    student_type,
    COUNT(*) AS student_count
FROM students
GROUP BY track_course, student_type
ORDER BY student_type, student_count DESC;
GO

PRINT '';
PRINT '============================================';
PRINT '2025 CASES SUMMARY';
PRINT '============================================';

SELECT 
    case_id AS 'Case ID',
    student_id AS 'Student ID',
    case_type AS 'Offense Type',
    severity AS 'Severity',
    status AS 'Status',
    date_reported AS 'Date'
FROM cases
WHERE case_id LIKE 'C-2025%'
ORDER BY date_reported;
GO

PRINT '';
PRINT '============================================';
PRINT 'STUDENTS WITH MULTIPLE OFFENSES';
PRINT '============================================';

SELECT 
    s.student_id AS 'Student ID',
    s.first_name + ' ' + s.last_name AS 'Student Name',
    s.track_course AS 'Track/Course',
    s.total_offenses AS 'Total',
    s.major_offenses AS 'Major',
    s.minor_offenses AS 'Minor',
    s.status AS 'Status'
FROM students s
WHERE s.total_offenses > 0
ORDER BY s.total_offenses DESC, s.major_offenses DESC;
GO

PRINT '';
PRINT '============================================';
PRINT 'TEST STUDENT NUMBERS FOR AUTO-FILL FEATURE';
PRINT '============================================';
PRINT '';
PRINT 'SHS Students with Cases:';
PRINT '  - 2024001234 (Juan Dela Cruz - STEM) - 2 cases';
PRINT '  - 2024001238 (Carlos Mendoza - ABM) - 2 cases';
PRINT '  - 2024001241 (Isabella Cruz - ABM - On Watch) - 2 cases';
PRINT '  - 2024001242 (Luis Fernandez - HUMSS) - 1 case';
PRINT '  - 2024001236 (Pedro Reyes - STEM) - 1 case';
PRINT '';
PRINT 'College Students with Cases:';
PRINT '  - 2024002001 (Marco Villanueva - BSIT) - 2 cases';
PRINT '  - 2024002007 (Andres Vargas - BSBA - On Watch) - 2 cases';
PRINT '  - 2024002002 (Angela Castillo - BSIT) - 1 case';
PRINT '  - 2024002006 (Valentina Romero - BSBA) - 1 case';
PRINT '  - 2024002009 (Sebastian Martinez - BSCS) - 1 case';
PRINT '';
PRINT '✅ Database ready for use!';
PRINT '✅ Includes 10 NEW 2025 cases with detailed information';
PRINT '✅ All student offense counts updated';
PRINT '✅ Watch list populated';
PRINT '✅ Case history tracked';
PRINT '============================================';
GO

-- Drop database if exists (careful in production!)
IF EXISTS (SELECT * FROM sys.databases WHERE name = 'PrototypeDO_DB')
BEGIN
    ALTER DATABASE PrototypeDO_DB SET SINGLE_USER WITH ROLLBACK IMMEDIATE;
    DROP DATABASE PrototypeDO_DB;
END
GO

-- Create database
CREATE DATABASE PrototypeDO_DB;
GO

USE PrototypeDO_DB;
GO

-- ============================================
-- 1. USERS TABLE (All account types)
-- ============================================
CREATE TABLE users (
    user_id INT IDENTITY(1,1) PRIMARY KEY,
    username NVARCHAR(50) UNIQUE NOT NULL,
    password_hash NVARCHAR(255) NOT NULL,
    email NVARCHAR(100) UNIQUE NOT NULL,
    full_name NVARCHAR(100) NOT NULL,
    role NVARCHAR(20) NOT NULL CHECK (role IN ('super_admin', 'discipline_office', 'teacher', 'security', 'student')),
    contact_number NVARCHAR(20),
    is_active BIT DEFAULT 1,
    last_login DATETIME,
    created_at DATETIME DEFAULT GETDATE(),
    updated_at DATETIME DEFAULT GETDATE()
);
GO

-- ============================================
-- 2. STUDENTS TABLE (Extended student info)
-- ============================================
CREATE TABLE students (
    student_id NVARCHAR(20) PRIMARY KEY,
    user_id INT NULL FOREIGN KEY REFERENCES users(user_id) ON DELETE SET NULL,
    first_name NVARCHAR(50) NOT NULL,
    last_name NVARCHAR(50) NOT NULL,
    middle_name NVARCHAR(50),
    grade_year NVARCHAR(20) NOT NULL, -- '11', '12', '1st Year', '2nd Year', etc.
    track_course NVARCHAR(100), -- 'STEM', 'ABM', 'BSIT', 'BSBA', etc.
    section NVARCHAR(50),
    student_type NVARCHAR(20) CHECK (student_type IN ('SHS', 'College')),
    status NVARCHAR(20) DEFAULT 'Good Standing' CHECK (status IN ('Good Standing', 'On Watch', 'On Probation')),
    total_offenses INT DEFAULT 0,
    major_offenses INT DEFAULT 0,
    minor_offenses INT DEFAULT 0,
    last_incident_date DATE,
    guardian_name NVARCHAR(100),
    guardian_contact NVARCHAR(20),
    created_at DATETIME DEFAULT GETDATE(),
    updated_at DATETIME DEFAULT GETDATE()
);
GO

-- ============================================
-- 3. OFFENSE TYPES TABLE (Catalog based on handbook)
-- ============================================
CREATE TABLE offense_types (
    offense_id INT IDENTITY(1,1) PRIMARY KEY,
    offense_name NVARCHAR(100) NOT NULL,
    category NVARCHAR(20) NOT NULL CHECK (category IN ('Major', 'Minor')),
    description NVARCHAR(500),
    is_active BIT DEFAULT 1,
    created_at DATETIME DEFAULT GETDATE()
);
GO

-- ============================================
-- 4. CASES TABLE (Main discipline cases)
-- ============================================
CREATE TABLE cases (
    case_id NVARCHAR(20) PRIMARY KEY, -- Format: C-1092
    student_id NVARCHAR(20) NOT NULL FOREIGN KEY REFERENCES students(student_id),
    offense_id INT NULL FOREIGN KEY REFERENCES offense_types(offense_id),
    case_type NVARCHAR(100) NOT NULL, -- 'Tardiness', 'Dress Code', etc.
    severity NVARCHAR(20) NOT NULL CHECK (severity IN ('Major', 'Minor')),
    offense_category NVARCHAR(50) NULL, -- Category A, B, C, D
    status NVARCHAR(50) DEFAULT 'Pending' CHECK (status IN ('Pending', 'Under Review', 'Resolved', 'Escalated', 'Dismissed')),
    date_reported DATE NOT NULL DEFAULT CAST(GETDATE() AS DATE),
    time_reported TIME,
    location NVARCHAR(200),
    reported_by INT NULL FOREIGN KEY REFERENCES users(user_id), -- Teacher/Guard who reported
    assigned_to INT NULL FOREIGN KEY REFERENCES users(user_id), -- DO staff handling case
    description NVARCHAR(MAX),
    witnesses NVARCHAR(500),
    action_taken NVARCHAR(500),
    notes NVARCHAR(MAX),
    attachments NVARCHAR(MAX), -- JSON array of file paths
    next_hearing_date DATETIME,
    resolved_date DATE NULL,
    is_archived BIT DEFAULT 0,
    manually_restored BIT DEFAULT 0,
    archived_at DATETIME,
    created_at DATETIME DEFAULT GETDATE(),
    updated_at DATETIME DEFAULT GETDATE()
);
GO

-- ============================================
-- 5. SANCTIONS TABLE (Corrective actions)
-- ============================================
CREATE TABLE sanctions (
    sanction_id INT IDENTITY(1,1) PRIMARY KEY,
    sanction_name NVARCHAR(200) NOT NULL,
    severity_level INT NOT NULL CHECK (severity_level BETWEEN 1 AND 5), -- 1=lightest, 5=severest
    description NVARCHAR(500),
    is_active BIT DEFAULT 1,
    created_at DATETIME DEFAULT GETDATE()
);
GO

-- ============================================
-- 6. CASE_SANCTIONS TABLE (Link cases to sanctions)
-- ============================================
CREATE TABLE case_sanctions (
    case_sanction_id INT IDENTITY(1,1) PRIMARY KEY,
    case_id NVARCHAR(20) FOREIGN KEY REFERENCES cases(case_id),
    sanction_id INT FOREIGN KEY REFERENCES sanctions(sanction_id),
    applied_date DATE DEFAULT CAST(GETDATE() AS DATE),
    duration_days INT NULL, -- For suspensions, etc.
    is_completed BIT DEFAULT 0,
    completion_date DATE,
    notes NVARCHAR(500),
    created_at DATETIME DEFAULT GETDATE()
);
GO

-- ============================================
-- 7. CASE_HISTORY TABLE (Track all changes)
-- ============================================
CREATE TABLE case_history (
    history_id INT IDENTITY(1,1) PRIMARY KEY,
    case_id NVARCHAR(20) FOREIGN KEY REFERENCES cases(case_id),
    changed_by INT NULL FOREIGN KEY REFERENCES users(user_id),
    action NVARCHAR(50) NOT NULL, -- 'Created', 'Updated', 'Status Changed', 'Assigned', etc.
    old_value NVARCHAR(MAX),
    new_value NVARCHAR(MAX),
    notes NVARCHAR(500),
    timestamp DATETIME DEFAULT GETDATE()
);
GO

-- ============================================
-- 8. LOST_FOUND_ITEMS TABLE
-- ============================================
CREATE TABLE lost_found_items (
    item_id NVARCHAR(20) PRIMARY KEY, -- Format: LF-1001
    item_name NVARCHAR(200) NOT NULL,
    category NVARCHAR(50) NOT NULL, -- 'Electronics', 'Clothing', 'Accessories', 'Books', 'IDs', 'Others'
    description NVARCHAR(MAX),
    found_location NVARCHAR(200) NOT NULL,
    date_found DATE NOT NULL DEFAULT CAST(GETDATE() AS DATE),
    time_found TIME,
    finder_name NVARCHAR(100),
    finder_student_id NVARCHAR(20) NULL FOREIGN KEY REFERENCES students(student_id),
    status NVARCHAR(20) DEFAULT 'Unclaimed' CHECK (status IN ('Unclaimed', 'Claimed', 'Disposed')),
    claimer_name NVARCHAR(100),
    claimer_student_id NVARCHAR(20) NULL FOREIGN KEY REFERENCES students(student_id),
    date_claimed DATE,
    image_path NVARCHAR(500),
    is_archived BIT DEFAULT 0,
    archived_at DATETIME,
    created_at DATETIME DEFAULT GETDATE(),
    updated_at DATETIME DEFAULT GETDATE()
);
GO

-- ============================================
-- 9. NOTIFICATIONS TABLE
-- ============================================
CREATE TABLE notifications (
    notification_id INT IDENTITY(1,1) PRIMARY KEY,
    user_id INT FOREIGN KEY REFERENCES users(user_id),
    title NVARCHAR(200) NOT NULL,
    message NVARCHAR(MAX) NOT NULL,
    type NVARCHAR(50) NOT NULL, -- 'case_update', 'hearing_reminder', 'system', etc.
    related_id NVARCHAR(50), -- case_id or item_id
    is_read BIT DEFAULT 0,
    read_at DATETIME,
    created_at DATETIME DEFAULT GETDATE()
);
GO

-- ============================================
-- 10. REPORTS TABLE (Generated reports history)
-- ============================================
CREATE TABLE reports (
    report_id INT IDENTITY(1,1) PRIMARY KEY,
    report_name NVARCHAR(200) NOT NULL,
    report_type NVARCHAR(50) NOT NULL, -- 'Disciplinary', 'Statistical', 'User Activity'
    format NVARCHAR(10) NOT NULL CHECK (format IN ('PDF', 'Excel', 'CSV')),
    file_path NVARCHAR(500),
    generated_by INT NULL FOREIGN KEY REFERENCES users(user_id),
    date_generated DATETIME DEFAULT GETDATE(),
    parameters NVARCHAR(MAX), -- JSON of filter parameters used
    file_size_kb INT
);
GO

-- ============================================
-- 11. CALENDAR_EVENTS TABLE
-- ============================================
CREATE TABLE calendar_events (
    event_id INT IDENTITY(1,1) PRIMARY KEY,
    event_name NVARCHAR(200) NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME,
    category NVARCHAR(50) NOT NULL CHECK (category IN ('Meeting', 'Conference', 'Deadline', 'Hearing', 'Holiday', 'Other')),
    description NVARCHAR(MAX),
    location NVARCHAR(200),
    created_by INT NULL FOREIGN KEY REFERENCES users(user_id),
    created_at DATETIME DEFAULT GETDATE(),
    updated_at DATETIME DEFAULT GETDATE()
);
GO

-- ============================================
-- 12. HANDBOOK_SECTIONS TABLE (For editing)
-- ============================================
CREATE TABLE handbook_sections (
    section_id INT IDENTITY(1,1) PRIMARY KEY,
    section_title NVARCHAR(200) NOT NULL,
    section_order INT NOT NULL,
    content NVARCHAR(MAX) NOT NULL,
    last_edited_by INT NULL FOREIGN KEY REFERENCES users(user_id),
    last_edited_at DATETIME DEFAULT GETDATE(),
    created_at DATETIME DEFAULT GETDATE()
);
GO

-- ============================================
-- 13. WATCH_LIST TABLE (Students to monitor)
-- ============================================
CREATE TABLE watch_list (
    watch_id INT IDENTITY(1,1) PRIMARY KEY,
    student_id NVARCHAR(20) FOREIGN KEY REFERENCES students(student_id),
    reason NVARCHAR(500) NOT NULL,
    added_by INT NULL FOREIGN KEY REFERENCES users(user_id),
    added_date DATE DEFAULT CAST(GETDATE() AS DATE),
    is_active BIT DEFAULT 1,
    removed_date DATE,
    removed_by INT NULL FOREIGN KEY REFERENCES users(user_id),
    notes NVARCHAR(MAX)
);
GO

-- ============================================
-- 14. AUDIT_LOG TABLE (System activity tracking)
-- ============================================
CREATE TABLE audit_log (
    log_id INT IDENTITY(1,1) PRIMARY KEY,
    user_id INT NULL FOREIGN KEY REFERENCES users(user_id),
    action NVARCHAR(100) NOT NULL,
    table_name NVARCHAR(50),
    record_id NVARCHAR(50),
    old_values NVARCHAR(MAX),
    new_values NVARCHAR(MAX),
    ip_address NVARCHAR(50),
    user_agent NVARCHAR(500),
    timestamp DATETIME DEFAULT GETDATE()
);
GO

-- ============================================
-- INDEXES for Performance
-- ============================================
CREATE INDEX idx_cases_student ON cases(student_id);
CREATE INDEX idx_cases_status ON cases(status);
CREATE INDEX idx_cases_date ON cases(date_reported);
CREATE INDEX idx_cases_archived ON cases(is_archived);
CREATE INDEX idx_students_status ON students(status);
CREATE INDEX idx_notifications_user ON notifications(user_id, is_read);
CREATE INDEX idx_audit_user ON audit_log(user_id);
CREATE INDEX idx_lost_found_status ON lost_found_items(status);
CREATE INDEX idx_case_sanctions_case ON case_sanctions(case_id);
GO

-- ============================================
-- INSERT DEFAULT USERS
-- ============================================

INSERT INTO users (username, password_hash, email, full_name, role, contact_number)
VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
 'admin@sti.edu', 'System Administrator', 'super_admin', '09123456789'),
('do_staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
 'do@sti.edu', 'John Doe', 'discipline_office', '09187654321'),
('teacher', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
 'teacher1@sti.edu', 'Maria Santos', 'teacher', '09171234567'),
('security', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
 'security1@sti.edu', 'Carlos Dela Cruz', 'security', '09184561234'),
('student', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
 'student1@sti.edu', 'Alex Reyes', 'student', '09193456781');
GO

-- ============================================
-- INSERT OFFENSE TYPES (Based on STI Handbook)
-- ============================================

-- Minor Offenses
INSERT INTO offense_types (offense_name, category, description) VALUES
('Non-adherence to Student Decorum', 'Minor', 'Discourtesy towards STI community members'),
('Non-wearing of School Uniform', 'Minor', 'Not wearing uniform or improper use of ID'),
('Inappropriate Campus Attire', 'Minor', 'Wearing inappropriate clothes on wash days'),
('Losing/Forgetting ID', 'Minor', 'Lost or forgot ID three times'),
('Disrespect to National Symbols', 'Minor', 'Disrespectful behavior to national symbols'),
('Improper Use of School Property', 'Minor', 'Irresponsible use of school property'),
('Gambling', 'Minor', 'Gambling within school premises'),
('Classroom Disruption', 'Minor', 'Disrupting classes or school activities'),
('Possession of Cigarettes/Vapes', 'Minor', 'Having cigarettes or vapes on person'),
('Bringing Pets', 'Minor', 'Bringing pets to school premises'),
('Public Display of Affection', 'Minor', 'Inappropriate displays of affection');
GO

-- Major Offenses - Category A
INSERT INTO offense_types (offense_name, category, description) VALUES
('Repeated Minor Offenses', 'Major', 'More than three minor offenses'),
('Lending/Borrowing ID', 'Major', 'Using tampered or borrowed school ID'),
('Smoking/Vaping on Campus', 'Major', 'Smoking or vaping inside campus'),
('Intoxication', 'Major', 'Entering campus intoxicated or drinking liquor'),
('Allowing Non-STI Entry', 'Major', 'Allowing unauthorized person entry'),
('Cheating', 'Major', 'Academic dishonesty in any form'),
('Plagiarism', 'Major', 'Copying work without proper attribution');
GO

-- Major Offenses - Category B
INSERT INTO offense_types (offense_name, category, description) VALUES
('Vandalism', 'Major', 'Damaging or destroying property'),
('Cyberbullying/Defamation', 'Major', 'Posting disrespectful content online'),
('Privacy Violation', 'Major', 'Recording/uploading without consent'),
('Wearing Uniform in Ill Repute Places', 'Major', 'Going to inappropriate places in uniform'),
('False Testimony', 'Major', 'Lying during official investigations'),
('Use of Profane Language', 'Major', 'Grave insult to community members');
GO

-- Major Offenses - Category C
INSERT INTO offense_types (offense_name, category, description) VALUES
('Hacking', 'Major', 'Attacking computer systems'),
('Forgery', 'Major', 'Tampering records or receipts'),
('Theft', 'Major', 'Stealing school or personal property'),
('Unauthorized Material Distribution', 'Major', 'Copying/distributing school materials'),
('Embezzlement', 'Major', 'Misuse of school or organization funds'),
('Illegal Assembly', 'Major', 'Disruptive demonstrations or boycotts'),
('Immorality', 'Major', 'Acts of immoral conduct'),
('Bullying', 'Major', 'Physical, cyber, or verbal bullying'),
('Physical Assault', 'Major', 'Fighting or inflicting physical injuries'),
('Drug Use', 'Major', 'Using prohibited drugs or chemicals'),
('False Alarms', 'Major', 'False fire alarms or bomb threats'),
('Misuse of Fire Equipment', 'Major', 'Using fire equipment inappropriately');
GO

-- Major Offenses - Category D (Expulsion-level)
INSERT INTO offense_types (offense_name, category, description) VALUES
('Drug Possession/Sale', 'Major', 'Possessing or selling prohibited drugs'),
('Repeated Drug Use', 'Major', 'Second positive drug test after intervention'),
('Weapons Possession', 'Major', 'Carrying firearms or deadly weapons'),
('Fraternity/Sorority Membership', 'Major', 'Membership in illegal organizations'),
('Hazing', 'Major', 'Participating in hazing or initiation rites'),
('Moral Turpitude', 'Major', 'Crimes like rape, murder, homicide, etc'),
('Sexual Harassment', 'Major', 'Sexual harassment as per RA 7877'),
('Subversion/Sedition', 'Major', 'Acts of subversion, sedition, or insurgency'),
('Others', 'Minor', 'Other offenses not specifically listed');
GO

-- ============================================
-- INSERT SANCTIONS (Based on STI Handbook)
-- ============================================

INSERT INTO sanctions (sanction_name, severity_level, description) VALUES
('Verbal/Oral Warning', 1, 'Verbal warning for first minor offense'),
('Written Apology', 1, 'Required to write apology letter'),
('Written Reprimand', 2, 'Formal written notice of violation'),
('Corrective Reinforcement (3 days)', 2, 'Attend classes + after-school tasks for 3 days'),
('Corrective Reinforcement (7 days)', 2, 'Attend classes + after-school tasks for 7 days'),
('Conference with Discipline Committee', 2, 'Meeting with parents/guardians required'),
('Suspension from Class (3 days)', 3, 'Cannot attend classes for 3 days'),
('Suspension from Class (7 days)', 3, 'Cannot attend classes for 7 days'),
('Suspension from Class (10 days)', 4, 'Cannot attend classes for 10 days'),
('Preventive Suspension', 3, 'Suspended during investigation period'),
('Non-readmission', 4, 'Denied enrollment for next term'),
('Exclusion', 5, 'Immediately removed from school'),
('Expulsion', 5, 'Disqualified from all Philippine institutions');
GO

-- ============================================
-- INSERT SAMPLE STUDENTS (32 Total)
-- ============================================

INSERT INTO students (student_id, first_name, last_name, grade_year, track_course, student_type, status, guardian_name, guardian_contact)
VALUES 
-- Original 8 students
('02000372341', 'Alex', 'Johnson', '12', 'STEM', 'SHS', 'Good Standing', 'Mary Johnson', '09171234567'),
('02000372342', 'Maria', 'Garcia', '11', 'ABM', 'SHS', 'Good Standing', 'Jose Garcia', '09181234567'),
('02000372343', 'James', 'Smith', '2nd Year', 'BSIT', 'College', 'Good Standing', 'John Smith', '09191234567'),
('02000372344', 'Emma', 'Wilson', '1st Year', 'BSBA', 'College', 'Good Standing', 'Sarah Wilson', '09201234567'),
('02000372345', 'Daniel', 'Lee', '12', 'HUMSS', 'SHS', 'Good Standing', 'Lisa Lee', '09211234567'),
('02000372346', 'Sophia', 'Brown', '11', 'STEM', 'SHS', 'Good Standing', 'Robert Brown', '09221234567'),
('02000372347', 'Michael', 'Wang', '3rd Year', 'BSCS', 'College', 'Good Standing', 'Wei Wang', '09231234567'),
('02000372348', 'Olivia', 'Martinez', '12', 'ABM', 'SHS', 'Good Standing', 'Carlos Martinez', '09241234567'),

-- New SHS Students - STEM Track
('2024001234', 'Juan', 'Dela Cruz', '11', 'STEM', 'SHS', 'Good Standing', 'Maria Dela Cruz', '09171234001'),
('2024001235', 'Maria', 'Santos', '11', 'STEM', 'SHS', 'Good Standing', 'Jose Santos', '09171234002'),
('2024001236', 'Pedro', 'Reyes', '12', 'STEM', 'SHS', 'Good Standing', 'Ana Reyes', '09171234003'),
('2024001237', 'Ana', 'Garcia', '12', 'STEM', 'SHS', 'Good Standing', 'Carlos Garcia', '09171234004'),

-- New SHS Students - ABM Track
('2024001238', 'Carlos', 'Mendoza', '11', 'ABM', 'SHS', 'Good Standing', 'Linda Mendoza', '09171234005'),
('2024001239', 'Sofia', 'Ramos', '11', 'ABM', 'SHS', 'Good Standing', 'Robert Ramos', '09171234006'),
('2024001240', 'Miguel', 'Torres', '12', 'ABM', 'SHS', 'Good Standing', 'Isabel Torres', '09171234007'),
('2024001241', 'Isabella', 'Cruz', '12', 'ABM', 'SHS', 'On Watch', 'Fernando Cruz', '09171234008'),

-- New SHS Students - HUMSS Track
('2024001242', 'Luis', 'Fernandez', '11', 'HUMSS', 'SHS', 'Good Standing', 'Elena Fernandez', '09171234009'),
('2024001243', 'Carmen', 'Diaz', '11', 'HUMSS', 'SHS', 'Good Standing', 'Ricardo Diaz', '09171234010'),
('2024001244', 'Diego', 'Morales', '12', 'HUMSS', 'SHS', 'Good Standing', 'Patricia Morales', '09171234011'),
('2024001245', 'Lucia', 'Gutierrez', '12', 'HUMSS', 'SHS', 'Good Standing', 'Manuel Gutierrez', '09171234012'),

-- New College Students - BSIT
('2024002001', 'Marco', 'Villanueva', '1st Year', 'BSIT', 'College', 'Good Standing', 'Rosa Villanueva', '09181234001'),
('2024002002', 'Angela', 'Castillo', '2nd Year', 'BSIT', 'College', 'Good Standing', 'Antonio Castillo', '09181234002'),
('2024002003', 'Rafael', 'Herrera', '3rd Year', 'BSIT', 'College', 'Good Standing', 'Gloria Herrera', '09181234003'),
('2024002004', 'Gabriela', 'Jimenez', '4th Year', 'BSIT', 'College', 'Good Standing', 'Alberto Jimenez', '09181234004'),

-- New College Students - BSBA
('2024002005', 'Daniel', 'Navarro', '1st Year', 'BSBA', 'College', 'Good Standing', 'Teresa Navarro', '09181234005'),
('2024002006', 'Valentina', 'Romero', '2nd Year', 'BSBA', 'College', 'Good Standing', 'Francisco Romero', '09181234006'),
('2024002007', 'Andres', 'Vargas', '3rd Year', 'BSBA', 'College', 'On Watch', 'Carmen Vargas', '09181234007'),
('2024002008', 'Camila', 'Flores', '4th Year', 'BSBA', 'College', 'Good Standing', 'Eduardo Flores', '09181234008'),

-- New College Students - BSCS
('2024002009', 'Sebastian', 'Martinez', '1st Year', 'BSCS', 'College', 'Good Standing', 'Laura Martinez', '09181234009'),
('2024002010', 'Nicole', 'Gonzalez', '2nd Year', 'BSCS', 'College', 'Good Standing', 'Jorge Gonzalez', '09181234010'),
('2024002011', 'Adrian', 'Lopez', '3rd Year', 'BSCS', 'College', 'Good Standing', 'Silvia Lopez', '09181234011'),
('2024002012', 'Bianca', 'Perez', '4th Year', 'BSCS', 'College', 'Good Standing', 'Ramon Perez', '09181234012');
GO

-- ============================================
-- INSERT SAMPLE CASES (23 Total: 13 Old + 10 New 2025)
-- ============================================

INSERT INTO cases (case_id, student_id, offense_id, case_type, severity, offense_category, status, date_reported, time_reported, location, reported_by, assigned_to, description, witnesses, action_taken, notes)
VALUES 
-- Original 2023 cases (8)
('C-1092', '02000372341', 1, 'Non-adherence to Student Decorum', 'Minor', NULL, 'Pending', '2023-10-12', NULL, NULL, NULL, 2, 
 'Student was late to class for the third time this month.', NULL, NULL, 'Parent has been contacted via email on Oct 11.'),
('C-1091', '02000372342', 2, 'Non-wearing of School Uniform', 'Minor', NULL, 'Resolved', '2023-10-11', NULL, NULL, NULL, 2, 
 'Inappropriate attire violation.', NULL, NULL, 'Issue resolved, student complied.'),
('C-1090', '02000372343', 8, 'Classroom Disruption', 'Minor', NULL, 'Under Review', '2023-10-10', NULL, NULL, NULL, 2, 
 'Talking loudly during class.', NULL, NULL, 'Reviewing incident with student.'),
('C-1089', '02000372344', 17, 'Cheating', 'Major', NULL, 'Escalated', '2023-10-09', NULL, NULL, NULL, 2, 
 'Cheating on exam.', NULL, NULL, 'Case escalated to principal.'),
('C-1088', '02000372345', 1, 'Non-adherence to Student Decorum', 'Minor', NULL, 'Resolved', '2023-10-08', NULL, NULL, NULL, 2, 
 'Multiple absences.', NULL, NULL, 'Medical documentation provided.'),
('C-1087', '02000372346', 1, 'Non-adherence to Student Decorum', 'Minor', NULL, 'Pending', '2023-10-07', NULL, NULL, NULL, 2, 
 'Late to class.', NULL, NULL, ''),
('C-1086', '02000372347', 2, 'Non-wearing of School Uniform', 'Minor', NULL, 'Resolved', '2023-10-06', NULL, NULL, NULL, 2, 
 'Uniform violation.', NULL, NULL, 'Corrected immediately.'),
('C-1085', '02000372348', 8, 'Classroom Disruption', 'Minor', NULL, 'Under Review', '2023-10-05', NULL, NULL, NULL, 2, 
 'Disruptive behavior.', NULL, NULL, 'Meeting scheduled with parents.'),

-- Additional 2024 cases (5)
('C-1093', '2024001234', 1, 'Non-adherence to Student Decorum', 'Minor', NULL, 'Pending', '2024-10-15', NULL, NULL, NULL, 2, 
 'Late to class three times this week.', NULL, NULL, 'First warning issued.'),
('C-1094', '2024001238', 2, 'Non-wearing of School Uniform', 'Minor', NULL, 'Under Review', '2024-10-16', NULL, NULL, NULL, 2, 
 'Wearing improper uniform on Monday.', NULL, NULL, 'Student explained forgot to wash uniform.'),
('C-1095', '2024002001', 8, 'Classroom Disruption', 'Minor', NULL, 'Resolved', '2024-10-17', NULL, NULL, NULL, 2, 
 'Talking during lecture.', NULL, NULL, 'Apologized to instructor.'),
('C-1096', '2024002007', 17, 'Cheating', 'Major', NULL, 'Escalated', '2024-10-18', NULL, NULL, NULL, 2, 
 'Caught with cheat sheet during exam.', NULL, NULL, 'Case forwarded to academic council.'),
('C-1097', '2024001241', 1, 'Non-adherence to Student Decorum', 'Minor', NULL, 'Pending', '2024-10-19', NULL, NULL, NULL, 2, 
 'Multiple uniform violations.', NULL, NULL, 'Student placed on watch list.'),

-- NEW 2025 CASES (10)
('C-2025001', '2024001234', 1, 'Non-adherence to Student Decorum', 'Minor', NULL, 'Resolved', '2025-01-15', '08:15:00', 'Building A - Room 201', 3, 2, 
 'Student arrived 15 minutes late to first period class without valid excuse.', 'Class teacher - Maria Santos', 'Verbal warning issued', 'First offense for this semester. Student apologized and committed to punctuality.'),
('C-2025002', '2024001238', 2, 'Non-wearing of School Uniform', 'Minor', NULL, 'Resolved', '2025-01-20', '07:45:00', 'Main Gate Entrance', 4, 2, 
 'Student entered campus wearing casual clothes (jeans and t-shirt) instead of proper uniform.', 'Security guard - Carlos Dela Cruz', 'Written reprimand issued, student changed to proper uniform', 'Student claimed uniform was being washed. Parent notified.'),
('C-2025003', '2024002001', 8, 'Classroom Disruption', 'Minor', NULL, 'Under Review', '2025-02-05', '10:30:00', 'Computer Laboratory 3', 3, 2, 
 'Student was repeatedly talking and laughing loudly during programming class, disturbing other students.', 'Teacher: Maria Santos; Classmates: 3 students', 'Student conference scheduled', 'Multiple warnings given during class. Pattern of disruptive behavior noted.'),
('C-2025004', '2024002007', 17, 'Cheating', 'Major', 'Category A', 'Escalated', '2025-02-14', '14:00:00', 'Room B-305', 3, 2, 
 'Student caught with written notes hidden in calculator case during Business Mathematics midterm exam.', 'Proctor: Maria Santos; Student seated nearby: 2 witnesses', 'Exam confiscated, case escalated to Academic Council', 'Second major offense this year. Student already on watch list. Parent conference required.'),
('C-2025005', '2024001242', 4, 'Losing/Forgetting ID', 'Minor', NULL, 'Pending', '2025-03-01', '07:30:00', 'Main Gate', 4, 2, 
 'Student forgot ID for the third time this semester. Unable to enter campus without temporary pass.', 'Security guard on duty', 'Temporary ID issued, parent notification sent', 'This is the third occurrence. Pattern of negligence. Corrective action needed.'),
('C-2025006', '2024001241', 14, 'Smoking/Vaping on Campus', 'Major', 'Category A', 'Under Review', '2025-03-10', '12:15:00', 'Behind Gymnasium', 4, 2, 
 'Student caught smoking cigarettes in restricted area behind the gymnasium during lunch break.', 'Security guard + 1 janitor', 'Student brought to DO office, cigarettes confiscated', 'Student admitted to offense. Parent contacted immediately. Suspension being considered.'),
('C-2025007', '2024002002', 11, 'Public Display of Affection', 'Minor', NULL, 'Resolved', '2025-03-20', '16:45:00', 'Student Lounge', 3, 2, 
 'Students engaged in inappropriate public display of affection (prolonged embrace and kissing) in student common area.', 'Teacher on duty + 5 students present', 'Verbal warning, counseling session conducted', 'Both students counseled on appropriate campus behavior. First offense.'),
('C-2025008', '2024001236', 19, 'Vandalism', 'Major', 'Category B', 'Under Review', '2025-04-02', '17:30:00', 'Restroom - 3rd Floor Building C', 4, 2, 
 'Student caught spray painting graffiti on restroom walls. Security footage confirmed identity.', 'Security personnel, janitor who discovered vandalism', 'Student questioned, admitted to offense. Cleanup scheduled', 'Student to pay for repainting costs and perform community service. Parent meeting scheduled.'),
('C-2025009', '2024002009', 10, 'Bringing Pets', 'Minor', NULL, 'Resolved', '2025-04-15', '08:00:00', 'Parking Area', 4, 2, 
 'Student brought a small dog to campus in backpack. Animal was discovered during routine inspection.', 'Security guard at entrance', 'Pet removed from campus, parent called to pick up animal', 'Student unaware of policy. Educational discussion conducted. No malicious intent.'),
('C-2025010', '2024002006', 20, 'Cyberbullying/Defamation', 'Major', 'Category B', 'Pending', '2025-05-01', '09:00:00', 'Reported online, investigated in DO Office', 3, 2, 
 'Student posted derogatory and insulting comments about a classmate on social media group. Screenshots provided as evidence.', 'Victim student + 3 classmates who witnessed posts', 'Investigation ongoing, social media evidence collected', 'Serious case requiring thorough investigation. Both students and parents to be called for mediation. Potential suspension.');
GO