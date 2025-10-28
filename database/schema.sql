-- ============================================
-- PrototypeDO Database Schema - COMPLETE VERSION
-- SQL Server 2019+
-- Discipline Office Management System
-- ============================================

USE master;
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

-- Insert default Super Admin
INSERT INTO users (username, password_hash, email, full_name, role, contact_number)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
        'admin@sti.edu', 'System Administrator', 'super_admin', '09123456789');
-- Password: 'password' (change this after first login!)

-- Insert Discipline Office account
INSERT INTO users (username, password_hash, email, full_name, role, contact_number)
VALUES ('do_staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
        'do@sti.edu', 'John Doe', 'discipline_office', '09187654321');
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
-- INSERT SAMPLE STUDENTS
-- ============================================

INSERT INTO students (student_id, first_name, last_name, grade_year, track_course, student_type, status, guardian_name, guardian_contact)
VALUES 
('02000372341', 'Alex', 'Johnson', '12', 'STEM', 'SHS', 'Good Standing', 'Mary Johnson', '09171234567'),
('02000372342', 'Maria', 'Garcia', '11', 'ABM', 'SHS', 'Good Standing', 'Jose Garcia', '09181234567'),
('02000372343', 'James', 'Smith', '2nd Year', 'BSIT', 'College', 'Good Standing', 'John Smith', '09191234567'),
('02000372344', 'Emma', 'Wilson', '1st Year', 'BSBA', 'College', 'Good Standing', 'Sarah Wilson', '09201234567'),
('02000372345', 'Daniel', 'Lee', '12', 'HUMSS', 'SHS', 'Good Standing', 'Lisa Lee', '09211234567'),
('02000372346', 'Sophia', 'Brown', '11', 'STEM', 'SHS', 'Good Standing', 'Robert Brown', '09221234567'),
('02000372347', 'Michael', 'Wang', '3rd Year', 'BSCS', 'College', 'Good Standing', 'Wei Wang', '09231234567'),
('02000372348', 'Olivia', 'Martinez', '12', 'ABM', 'SHS', 'Good Standing', 'Carlos Martinez', '09241234567');
GO

-- ============================================
-- INSERT SAMPLE CASES
-- ============================================

INSERT INTO cases (case_id, student_id, offense_id, case_type, severity, status, date_reported, assigned_to, description, notes)
VALUES 
('C-1092', '02000372341', 1, 'Non-adherence to Student Decorum', 'Minor', 'Pending', '2023-10-12', 2, 
 'Student was late to class for the third time this month.', 'Parent has been contacted via email on Oct 11.'),
('C-1091', '02000372342', 2, 'Non-wearing of School Uniform', 'Minor', 'Resolved', '2023-10-11', 2, 
 'Inappropriate attire violation.', 'Issue resolved, student complied.'),
('C-1090', '02000372343', 8, 'Classroom Disruption', 'Minor', 'Under Review', '2023-10-10', 2, 
 'Talking loudly during class.', 'Reviewing incident with student.'),
('C-1089', '02000372344', 17, 'Cheating', 'Major', 'Escalated', '2023-10-09', 2, 
 'Cheating on exam.', 'Case escalated to principal.'),
('C-1088', '02000372345', 1, 'Non-adherence to Student Decorum', 'Minor', 'Resolved', '2023-10-08', 2, 
 'Multiple absences.', 'Medical documentation provided.'),
('C-1087', '02000372346', 1, 'Non-adherence to Student Decorum', 'Minor', 'Pending', '2023-10-07', 2, 
 'Late to class.', ''),
('C-1086', '02000372347', 2, 'Non-wearing of School Uniform', 'Minor', 'Resolved', '2023-10-06', 2, 
 'Uniform violation.', 'Corrected immediately.'),
('C-1085', '02000372348', 8, 'Classroom Disruption', 'Minor', 'Under Review', '2023-10-05', 2, 
 'Disruptive behavior.', 'Meeting scheduled with parents.');
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
-- VERIFICATION QUERIES
-- ============================================

PRINT '========================================';
PRINT 'Database Schema Created Successfully!';
PRINT '========================================';
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

-- Show table counts
SELECT 'Users' as TableName, COUNT(*) as RecordCount FROM users
UNION ALL
SELECT 'Students', COUNT(*) FROM students
UNION ALL
SELECT 'Offense Types', COUNT(*) FROM offense_types
UNION ALL
SELECT 'Cases', COUNT(*) FROM cases
UNION ALL
SELECT 'Sanctions', COUNT(*) FROM sanctions
UNION ALL
SELECT 'Lost & Found Items', COUNT(*) FROM lost_found_items;
GO

PRINT '';
PRINT '✅ Database ready for use!';
GO