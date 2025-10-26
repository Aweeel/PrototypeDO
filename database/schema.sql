-- PrototypeDO Database Schema
-- SQL Server 2019+
-- Created for Discipline Office Management System

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
    user_id INT FOREIGN KEY REFERENCES users(user_id) ON DELETE CASCADE,
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
-- 3. OFFENSE TYPES TABLE (Catalog)
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
    offense_id INT FOREIGN KEY REFERENCES offense_types(offense_id),
    case_type NVARCHAR(100) NOT NULL, -- 'Tardiness', 'Dress Code', etc.
    severity NVARCHAR(20) NOT NULL CHECK (severity IN ('Major', 'Minor')),
    status NVARCHAR(50) DEFAULT 'Pending' CHECK (status IN ('Pending', 'Under Review', 'Resolved', 'Escalated', 'Dismissed')),
    date_reported DATE NOT NULL DEFAULT CAST(GETDATE() AS DATE),
    time_reported TIME,
    location NVARCHAR(200),
    reported_by INT FOREIGN KEY REFERENCES users(user_id), -- Teacher/Guard who reported
    assigned_to INT FOREIGN KEY REFERENCES users(user_id), -- DO staff handling case
    description NVARCHAR(MAX),
    witnesses NVARCHAR(500),
    action_taken NVARCHAR(500),
    notes NVARCHAR(MAX),
    attachments NVARCHAR(MAX), -- JSON array of file paths
    next_hearing_date DATETIME,
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
    changed_by INT FOREIGN KEY REFERENCES users(user_id),
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
    finder_student_id NVARCHAR(20) FOREIGN KEY REFERENCES students(student_id),
    status NVARCHAR(20) DEFAULT 'Unclaimed' CHECK (status IN ('Unclaimed', 'Claimed', 'Disposed')),
    claimer_name NVARCHAR(100),
    claimer_student_id NVARCHAR(20) FOREIGN KEY REFERENCES students(student_id),
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
    generated_by INT FOREIGN KEY REFERENCES users(user_id),
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
    created_by INT FOREIGN KEY REFERENCES users(user_id),
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
    last_edited_by INT FOREIGN KEY REFERENCES users(user_id),
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
    added_by INT FOREIGN KEY REFERENCES users(user_id),
    added_date DATE DEFAULT CAST(GETDATE() AS DATE),
    is_active BIT DEFAULT 1,
    removed_date DATE,
    removed_by INT FOREIGN KEY REFERENCES users(user_id),
    notes NVARCHAR(MAX)
);
GO

-- ============================================
-- 14. AUDIT_LOG TABLE (System activity tracking)
-- ============================================
CREATE TABLE audit_log (
    log_id INT IDENTITY(1,1) PRIMARY KEY,
    user_id INT FOREIGN KEY REFERENCES users(user_id),
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
CREATE INDEX idx_students_status ON students(status);
CREATE INDEX idx_notifications_user ON notifications(user_id, is_read);
CREATE INDEX idx_audit_user ON audit_log(user_id);
CREATE INDEX idx_lost_found_status ON lost_found_items(status);
GO

-- ============================================
-- INSERT SAMPLE DATA
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

-- Insert sample offense types
INSERT INTO offense_types (offense_name, category, description) VALUES
('Tardiness', 'Minor', 'Late arrival to class'),
('Dress Code Violation', 'Minor', 'Inappropriate attire'),
('Classroom Disruption', 'Minor', 'Disruptive behavior during class'),
('Academic Dishonesty', 'Major', 'Cheating, plagiarism'),
('Bullying', 'Major', 'Physical or verbal harassment'),
('Vandalism', 'Major', 'Destruction of school property'),
('Attendance Issues', 'Minor', 'Excessive absences'),
('Smoking', 'Major', 'Smoking on campus'),
('Fighting', 'Major', 'Physical altercation'),
('Insubordination', 'Minor', 'Refusing to follow instructions');
GO

-- Insert sample sanctions
INSERT INTO sanctions (sanction_name, severity_level, description) VALUES
('Verbal Warning', 1, 'Official verbal warning documented'),
('Written Warning', 2, 'Written warning letter to student'),
('Parent Conference', 2, 'Meeting with parents/guardians required'),
('Community Service', 3, '8-40 hours of community service'),
('Suspension (1-3 days)', 4, 'Temporary suspension from school'),
('Suspension (4-7 days)', 5, 'Extended suspension from school'),
('Probation', 4, 'Academic probation period'),
('Behavioral Contract', 3, 'Signed agreement for behavior improvement'),
('Confiscation', 1, 'Confiscation of prohibited items'),
('Expulsion', 5, 'Permanent removal from school');
GO

-- Insert sample students
INSERT INTO students (student_id, first_name, last_name, grade_year, track_course, student_type, guardian_name, guardian_contact)
VALUES 
('02000372341', 'Alex', 'Johnson', '12', 'STEM', 'SHS', 'Mary Johnson', '09171234567'),
('02000372342', 'Maria', 'Garcia', '11', 'ABM', 'SHS', 'Jose Garcia', '09181234567'),
('02000372343', 'James', 'Smith', '2nd Year', 'BSIT', 'College', 'John Smith', '09191234567'),
('02000372344', 'Emma', 'Wilson', '1st Year', 'BSBA', 'College', 'Sarah Wilson', '09201234567'),
('02000372345', 'Daniel', 'Lee', '12', 'HUMSS', 'SHS', 'Lisa Lee', '09211234567'),
('02000372346', 'Sophia', 'Brown', '11', 'STEM', 'SHS', 'Robert Brown', '09221234567'),
('02000372347', 'Michael', 'Wang', '3rd Year', 'BSCS', 'College', 'Wei Wang', '09231234567'),
('02000372348', 'Olivia', 'Martinez', '12', 'ABM', 'SHS', 'Carlos Martinez', '09241234567');
GO

-- Insert sample cases (matching your JavaScript data)
INSERT INTO cases (case_id, student_id, offense_id, case_type, severity, status, date_reported, assigned_to, description, notes)
VALUES 
('C-1092', '02000372341', 1, 'Tardiness', 'Minor', 'Pending', '2023-10-12', 2, 
 'Student was late to class for the third time this month.', 'Parent has been contacted via email on Oct 11.'),
('C-1091', '02000372342', 2, 'Dress Code', 'Minor', 'Resolved', '2023-10-11', 2, 
 'Inappropriate attire violation.', 'Issue resolved, student complied.'),
('C-1090', '02000372343', 3, 'Classroom Disruption', 'Minor', 'Under Review', '2023-10-10', 2, 
 'Talking loudly during class.', 'Reviewing incident with student.'),
('C-1089', '02000372344', 4, 'Academic Dishonesty', 'Major', 'Escalated', '2023-10-09', 2, 
 'Cheating on exam.', 'Case escalated to principal.'),
('C-1088', '02000372345', 7, 'Attendance', 'Minor', 'Resolved', '2023-10-08', 2, 
 'Multiple absences.', 'Medical documentation provided.'),
('C-1087', '02000372346', 1, 'Tardiness', 'Minor', 'Pending', '2023-10-07', 2, 
 'Late to class.', ''),
('C-1086', '02000372347', 2, 'Dress Code', 'Minor', 'Resolved', '2023-10-06', 2, 
 'Uniform violation.', 'Corrected immediately.'),
('C-1085', '02000372348', 3, 'Classroom Disruption', 'Minor', 'Under Review', '2023-10-05', 2, 
 'Disruptive behavior.', 'Meeting scheduled with parents.');
GO

-- Insert sample lost & found items
INSERT INTO lost_found_items (item_id, item_name, category, found_location, date_found, status, description)
VALUES 
('LF-1001', 'Blue Backpack', 'Bags', 'Cafeteria', '2023-10-14', 'Unclaimed', 'Blue JanSport backpack with laptop'),
('LF-1002', 'Water Bottle', 'Personal Items', 'Gym', '2023-10-13', 'Unclaimed', 'Stainless steel water bottle'),
('LF-1003', 'Math Textbook', 'Books', 'Library', '2023-10-12', 'Claimed', 'Grade 11 Math textbook'),
('LF-1004', 'Calculator', 'Electronics', 'Room C401', '2023-10-08', 'Claimed', 'Scientific calculator Casio fx-991');
GO

PRINT 'Database schema created successfully!';
PRINT 'Default login credentials:';
PRINT 'Username: admin';
PRINT 'Password: password';
PRINT '';
PRINT 'Please change the password after first login!';
GO