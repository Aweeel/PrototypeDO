-- ============================================
-- PrototypeDO Database Schema - COMPLETE VERSION WITH 2025 & 2026 CASES
-- SQL Server 2019+
-- Discipline Office Management System
-- Safe to run multiple times - prevents duplicates
-- Student ID Format: 02000 + 6 digits (e.g., 02000000001)
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
    grade_year NVARCHAR(20) NOT NULL,
    track_course NVARCHAR(100),
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
    case_id NVARCHAR(20) PRIMARY KEY,
    student_id NVARCHAR(20) NOT NULL FOREIGN KEY REFERENCES students(student_id),
    offense_id INT NULL FOREIGN KEY REFERENCES offense_types(offense_id),
    case_type NVARCHAR(100) NOT NULL,
    severity NVARCHAR(20) NOT NULL CHECK (severity IN ('Major', 'Minor')),
    offense_category NVARCHAR(50) NULL,
    status NVARCHAR(50) DEFAULT 'Pending' CHECK (status IN ('Pending', 'On Going', 'Resolved', 'Dismissed')),
    date_reported DATE NOT NULL DEFAULT CAST(GETDATE() AS DATE),
    time_reported TIME,
    location NVARCHAR(200),
    reported_by INT NULL FOREIGN KEY REFERENCES users(user_id),
    assigned_to INT NULL FOREIGN KEY REFERENCES users(user_id),
    description NVARCHAR(MAX),
    witnesses NVARCHAR(500),
    action_taken NVARCHAR(500),
    notes NVARCHAR(MAX),
    attachments NVARCHAR(MAX),
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
    severity_level INT NOT NULL CHECK (severity_level BETWEEN 1 AND 5),
    description NVARCHAR(500),
    requires_schedule BIT DEFAULT 0,
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
    duration_days INT NULL,
    is_completed BIT DEFAULT 0,
    completion_date DATE,
    notes NVARCHAR(500),
    scheduled_date DATE NULL,
    scheduled_time TIME NULL,
    schedule_notes NVARCHAR(500),
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
    action NVARCHAR(50) NOT NULL,
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
    item_id NVARCHAR(20) PRIMARY KEY,
    item_name NVARCHAR(200) NOT NULL,
    category NVARCHAR(50) NOT NULL,
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
    type NVARCHAR(50) NOT NULL,
    related_id NVARCHAR(50),
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
    report_type NVARCHAR(50) NOT NULL,
    format NVARCHAR(10) NOT NULL CHECK (format IN ('PDF', 'Excel', 'CSV')),
    file_path NVARCHAR(500),
    generated_by INT NULL FOREIGN KEY REFERENCES users(user_id),
    date_generated DATETIME DEFAULT GETDATE(),
    parameters NVARCHAR(MAX),
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

PRINT '============================================';
PRINT 'Tables created successfully!';
PRINT 'Now inserting data...';
PRINT '============================================';
GO

-- ============================================
-- INSERT DEFAULT USERS
-- ============================================
PRINT 'Inserting users...';

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

PRINT 'Users inserted: 5';
GO

-- ============================================
-- INSERT STUDENT USER ACCOUNTS (Auto-generated emails)
-- ============================================
PRINT 'Inserting student user accounts...';

-- Default password for all students: 'password'
DECLARE @defaultPassword NVARCHAR(255) = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

INSERT INTO users (username, password_hash, email, full_name, role, contact_number, is_active)
VALUES 
-- SHS Students
('delacruz.000001@sti.edu', @defaultPassword, 'delacruz.000001@sti.edu', 'Juan Santos Dela Cruz', 'student', '09171234001', 1),
('garcia.000002@sti.edu', @defaultPassword, 'garcia.000002@sti.edu', 'Maria Reyes Garcia', 'student', '09171234002', 1),
('santos.000003@sti.edu', @defaultPassword, 'santos.000003@sti.edu', 'Pedro Lopez Santos', 'student', '09171234003', 1),
('reyes.000004@sti.edu', @defaultPassword, 'reyes.000004@sti.edu', 'Ana Cruz Reyes', 'student', '09171234004', 1),
('mendoza.000005@sti.edu', @defaultPassword, 'mendoza.000005@sti.edu', 'Carlos Torres Mendoza', 'student', '09171234005', 1),
('ramos.000006@sti.edu', @defaultPassword, 'ramos.000006@sti.edu', 'Sofia Diaz Ramos', 'student', '09171234006', 1),
('torres.000007@sti.edu', @defaultPassword, 'torres.000007@sti.edu', 'Miguel Morales Torres', 'student', '09171234007', 1),
('cruz.000008@sti.edu', @defaultPassword, 'cruz.000008@sti.edu', 'Isabella Fernandez Cruz', 'student', '09171234008', 1),
('fernandez.000009@sti.edu', @defaultPassword, 'fernandez.000009@sti.edu', 'Luis Diaz Fernandez', 'student', '09171234009', 1),
('diaz.000010@sti.edu', @defaultPassword, 'diaz.000010@sti.edu', 'Carmen Gutierrez Diaz', 'student', '09171234010', 1),
('morales.000011@sti.edu', @defaultPassword, 'morales.000011@sti.edu', 'Diego Herrera Morales', 'student', '09171234011', 1),
('gutierrez.000012@sti.edu', @defaultPassword, 'gutierrez.000012@sti.edu', 'Lucia Jimenez Gutierrez', 'student', '09171234012', 1),
('johnson.000013@sti.edu', @defaultPassword, 'johnson.000013@sti.edu', 'Alex Michael Johnson', 'student', '09171234013', 1),
('wilson.000014@sti.edu', @defaultPassword, 'wilson.000014@sti.edu', 'Emma Rose Wilson', 'student', '09171234014', 1),
('lee.000015@sti.edu', @defaultPassword, 'lee.000015@sti.edu', 'Daniel James Lee', 'student', '09171234015', 1),
-- College Students
('villanueva.000016@sti.edu', @defaultPassword, 'villanueva.000016@sti.edu', 'Marco Santos Villanueva', 'student', '09181234001', 1),
('castillo.000017@sti.edu', @defaultPassword, 'castillo.000017@sti.edu', 'Angela Reyes Castillo', 'student', '09181234002', 1),
('herrera.000018@sti.edu', @defaultPassword, 'herrera.000018@sti.edu', 'Rafael Cruz Herrera', 'student', '09181234003', 1),
('jimenez.000019@sti.edu', @defaultPassword, 'jimenez.000019@sti.edu', 'Gabriela Torres Jimenez', 'student', '09181234004', 1),
('navarro.000020@sti.edu', @defaultPassword, 'navarro.000020@sti.edu', 'Daniel Mendoza Navarro', 'student', '09181234005', 1),
('romero.000021@sti.edu', @defaultPassword, 'romero.000021@sti.edu', 'Valentina Garcia Romero', 'student', '09181234006', 1),
('vargas.000022@sti.edu', @defaultPassword, 'vargas.000022@sti.edu', 'Andres Lopez Vargas', 'student', '09181234007', 1),
('flores.000023@sti.edu', @defaultPassword, 'flores.000023@sti.edu', 'Camila Diaz Flores', 'student', '09181234008', 1),
('martinez.000024@sti.edu', @defaultPassword, 'martinez.000024@sti.edu', 'Sebastian Ramos Martinez', 'student', '09181234009', 1),
('gonzalez.000025@sti.edu', @defaultPassword, 'gonzalez.000025@sti.edu', 'Nicole Morales Gonzalez', 'student', '09181234010', 1),
('lopez.000026@sti.edu', @defaultPassword, 'lopez.000026@sti.edu', 'Adrian Fernandez Lopez', 'student', '09181234011', 1),
('perez.000027@sti.edu', @defaultPassword, 'perez.000027@sti.edu', 'Bianca Gutierrez Perez', 'student', '09181234012', 1),
('smith.000028@sti.edu', @defaultPassword, 'smith.000028@sti.edu', 'James Robert Smith', 'student', '09181234013', 1),
('brown.000029@sti.edu', @defaultPassword, 'brown.000029@sti.edu', 'Sophia Anne Brown', 'student', '09181234014', 1),
('wang.000030@sti.edu', @defaultPassword, 'wang.000030@sti.edu', 'Michael Chen Wang', 'student', '09181234015', 1);

PRINT 'Student user accounts inserted: 30';
GO

-- ============================================
-- INSERT SAMPLE STUDENTS (Linked to user accounts)
-- Student ID Format: 02000 + 6 digits
-- ============================================
PRINT 'Inserting students...';

INSERT INTO students (student_id, user_id, first_name, last_name, middle_name, grade_year, track_course, section, student_type, status, guardian_name, guardian_contact)
VALUES 
-- SHS Students (user_id 6-20)
('02000000001', 6, 'Juan', 'Dela Cruz', 'Santos', '11', 'STEM', 'A', 'SHS', 'Good Standing', 'Maria Dela Cruz', '09171234001'),
('02000000002', 7, 'Maria', 'Garcia', 'Reyes', '11', 'ABM', 'B', 'SHS', 'Good Standing', 'Jose Garcia', '09171234002'),
('02000000003', 8, 'Pedro', 'Santos', 'Lopez', '12', 'STEM', 'A', 'SHS', 'Good Standing', 'Ana Santos', '09171234003'),
('02000000004', 9, 'Ana', 'Reyes', 'Cruz', '12', 'HUMSS', 'C', 'SHS', 'Good Standing', 'Carlos Reyes', '09171234004'),
('02000000005', 10, 'Carlos', 'Mendoza', 'Torres', '11', 'ABM', 'B', 'SHS', 'Good Standing', 'Linda Mendoza', '09171234005'),
('02000000006', 11, 'Sofia', 'Ramos', 'Diaz', '11', 'STEM', 'A', 'SHS', 'Good Standing', 'Robert Ramos', '09171234006'),
('02000000007', 12, 'Miguel', 'Torres', 'Morales', '12', 'ABM', 'B', 'SHS', 'Good Standing', 'Isabel Torres', '09171234007'),
('02000000008', 13, 'Isabella', 'Cruz', 'Fernandez', '12', 'HUMSS', 'C', 'SHS', 'On Watch', 'Fernando Cruz', '09171234008'),
('02000000009', 14, 'Luis', 'Fernandez', 'Diaz', '11', 'HUMSS', 'C', 'SHS', 'Good Standing', 'Elena Fernandez', '09171234009'),
('02000000010', 15, 'Carmen', 'Diaz', 'Gutierrez', '11', 'STEM', 'A', 'SHS', 'Good Standing', 'Ricardo Diaz', '09171234010'),
('02000000011', 16, 'Diego', 'Morales', 'Herrera', '12', 'ABM', 'B', 'SHS', 'Good Standing', 'Patricia Morales', '09171234011'),
('02000000012', 17, 'Lucia', 'Gutierrez', 'Jimenez', '12', 'HUMSS', 'C', 'SHS', 'Good Standing', 'Manuel Gutierrez', '09171234012'),
('02000000013', 18, 'Alex', 'Johnson', 'Michael', '12', 'STEM', 'A', 'SHS', 'Good Standing', 'Mary Johnson', '09171234013'),
('02000000014', 19, 'Emma', 'Wilson', 'Rose', '11', 'ABM', 'B', 'SHS', 'Good Standing', 'Sarah Wilson', '09171234014'),
('02000000015', 20, 'Daniel', 'Lee', 'James', '12', 'HUMSS', 'C', 'SHS', 'Good Standing', 'Lisa Lee', '09171234015'),
-- College Students (user_id 21-35)
('02000000016', 21, 'Marco', 'Villanueva', 'Santos', '1st Year', 'BSIT', 'IT-101', 'College', 'Good Standing', 'Rosa Villanueva', '09181234001'),
('02000000017', 22, 'Angela', 'Castillo', 'Reyes', '2nd Year', 'BSIT', 'IT-201', 'College', 'Good Standing', 'Antonio Castillo', '09181234002'),
('02000000018', 23, 'Rafael', 'Herrera', 'Cruz', '3rd Year', 'BSIT', 'IT-301', 'College', 'Good Standing', 'Gloria Herrera', '09181234003'),
('02000000019', 24, 'Gabriela', 'Jimenez', 'Torres', '4th Year', 'BSIT', 'IT-401', 'College', 'Good Standing', 'Alberto Jimenez', '09181234004'),
('02000000020', 25, 'Daniel', 'Navarro', 'Mendoza', '1st Year', 'BSBA', 'BA-101', 'College', 'Good Standing', 'Teresa Navarro', '09181234005'),
('02000000021', 26, 'Valentina', 'Romero', 'Garcia', '2nd Year', 'BSBA', 'BA-201', 'College', 'Good Standing', 'Francisco Romero', '09181234006'),
('02000000022', 27, 'Andres', 'Vargas', 'Lopez', '3rd Year', 'BSBA', 'BA-301', 'College', 'On Watch', 'Carmen Vargas', '09181234007'),
('02000000023', 28, 'Camila', 'Flores', 'Diaz', '4th Year', 'BSBA', 'BA-401', 'College', 'Good Standing', 'Eduardo Flores', '09181234008'),
('02000000024', 29, 'Sebastian', 'Martinez', 'Ramos', '1st Year', 'BSCS', 'CS-101', 'College', 'Good Standing', 'Laura Martinez', '09181234009'),
('02000000025', 30, 'Nicole', 'Gonzalez', 'Morales', '2nd Year', 'BSCS', 'CS-201', 'College', 'Good Standing', 'Jorge Gonzalez', '09181234010'),
('02000000026', 31, 'Adrian', 'Lopez', 'Fernandez', '3rd Year', 'BSCS', 'CS-301', 'College', 'Good Standing', 'Silvia Lopez', '09181234011'),
('02000000027', 32, 'Bianca', 'Perez', 'Gutierrez', '4th Year', 'BSCS', 'CS-401', 'College', 'Good Standing', 'Ramon Perez', '09181234012'),
('02000000028', 33, 'James', 'Smith', 'Robert', '2nd Year', 'BSIT', 'IT-201', 'College', 'Good Standing', 'John Smith', '09181234013'),
('02000000029', 34, 'Sophia', 'Brown', 'Anne', '11', 'STEM', 'A', 'SHS', 'Good Standing', 'Robert Brown', '09181234014'),
('02000000030', 35, 'Michael', 'Wang', 'Chen', '3rd Year', 'BSCS', 'CS-301', 'College', 'Good Standing', 'Wei Wang', '09181234015');

PRINT 'Students inserted: 30';
GO

-- ============================================
-- INSERT OFFENSE TYPES (Based on STI Handbook)
-- ============================================
PRINT 'Inserting offense types...';

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
('Public Display of Affection', 'Minor', 'Inappropriate displays of affection'),
('Repeated Minor Offenses', 'Major', 'More than three minor offenses'),
('Lending/Borrowing ID', 'Major', 'Using tampered or borrowed school ID'),
('Smoking/Vaping on Campus', 'Major', 'Smoking or vaping inside campus'),
('Intoxication', 'Major', 'Entering campus intoxicated or drinking liquor'),
('Allowing Non-STI Entry', 'Major', 'Allowing unauthorized person entry'),
('Cheating', 'Major', 'Academic dishonesty in any form'),
('Plagiarism', 'Major', 'Copying work without proper attribution'),
('Vandalism', 'Major', 'Damaging or destroying property'),
('Cyberbullying/Defamation', 'Major', 'Posting disrespectful content online'),
('Privacy Violation', 'Major', 'Recording/uploading without consent'),
('Wearing Uniform in Ill Repute Places', 'Major', 'Going to inappropriate places in uniform'),
('False Testimony', 'Major', 'Lying during official investigations'),
('Use of Profane Language', 'Major', 'Grave insult to community members'),
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
('Misuse of Fire Equipment', 'Major', 'Using fire equipment inappropriately'),
('Drug Possession/Sale', 'Major', 'Possessing or selling prohibited drugs'),
('Repeated Drug Use', 'Major', 'Second positive drug test after intervention'),
('Weapons Possession', 'Major', 'Carrying firearms or deadly weapons'),
('Fraternity/Sorority Membership', 'Major', 'Membership in illegal organizations'),
('Hazing', 'Major', 'Participating in hazing or initiation rites'),
('Moral Turpitude', 'Major', 'Crimes like rape, murder, homicide, etc'),
('Sexual Harassment', 'Major', 'Sexual harassment as per RA 7877'),
('Subversion/Sedition', 'Major', 'Acts of subversion, sedition, or insurgency'),
('Others', 'Minor', 'Other offenses not specifically listed');

PRINT 'Offense types inserted: 45';
GO

-- ============================================
-- INSERT SANCTIONS (Based on STI Handbook)
-- ============================================
PRINT 'Inserting sanctions...';

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

PRINT 'Sanctions inserted: 13';
GO

-- ============================================
-- INSERT SAMPLE CASES (30 Total - All 2026 Cases)
-- 10 Pending, 10 On Going, 10 Resolved
-- ============================================
PRINT 'Inserting cases...';

INSERT INTO cases (case_id, student_id, offense_id, case_type, severity, offense_category, status, date_reported, time_reported, location, reported_by, assigned_to, description, witnesses, action_taken, notes, resolved_date)
VALUES 
-- PENDING CASES (10)
('C-2026001', '02000000001', 1, 'Non-adherence to Student Decorum', 'Minor', NULL, 'Pending', '2026-01-15', '08:30:00', 'Building A - Room 202', 3, 2, 
 'Student arrived 20 minutes late to morning class without valid excuse.', 'Class teacher - Maria Santos', NULL, 'First offense this semester. Parent contact pending.', NULL),
('C-2026002', '02000000005', 2, 'Non-wearing of School Uniform', 'Minor', NULL, 'Pending', '2026-01-18', '07:45:00', 'Main Gate Entrance', 4, 2, 
 'Student wearing improper footwear (sneakers instead of black shoes).', 'Security guard - Carlos Dela Cruz', NULL, 'Violation noted. Awaiting parent conference.', NULL),
('C-2026003', '02000000010', 4, 'Losing/Forgetting ID', 'Minor', NULL, 'Pending', '2026-01-22', '07:30:00', 'Main Gate', 4, 2, 
 'Third time forgetting ID this semester. Required temporary pass to enter campus.', 'Security guard on duty', NULL, 'Pattern of negligence. Corrective action required.', NULL),
('C-2026004', '02000000015', 8, 'Classroom Disruption', 'Minor', NULL, 'Pending', '2026-01-25', '10:15:00', 'Room B-203', 3, 2, 
 'Repeatedly talking during lecture despite multiple warnings from instructor.', 'Teacher and classmates (4 students)', NULL, 'Disruptive behavior affecting class learning.', NULL),
('C-2026005', '02000000022', 17, 'Cheating', 'Major', 'Category A', 'Pending', '2026-02-01', '14:00:00', 'Room C-305', 3, 2, 
 'Student caught with unauthorized notes during Business Law quiz.', 'Exam proctor - Maria Santos', NULL, 'Evidence collected. Investigation pending.', NULL),
('C-2026006', '02000000008', 11, 'Public Display of Affection', 'Minor', NULL, 'Pending', '2026-02-05', '12:30:00', 'Canteen Area', 3, 2, 
 'Inappropriate public display of affection observed during lunch break.', 'Teacher on duty', NULL, 'Students identified. Conference scheduled.', NULL),
('C-2026007', '02000000018', 14, 'Smoking/Vaping on Campus', 'Major', 'Category A', 'Pending', '2026-02-08', '16:00:00', 'Parking Lot Area', 4, 2, 
 'Student observed vaping in campus parking area after classes.', 'Security personnel', NULL, 'Vape device confiscated. Serious violation.', NULL),
('C-2026008', '02000000025', 20, 'Cyberbullying/Defamation', 'Major', 'Category B', 'Pending', '2026-02-12', '09:00:00', 'Reported Online', 3, 2, 
 'Student posted offensive remarks about classmate on social media. Screenshots provided.', 'Victim and 3 witnesses', NULL, 'Investigation ongoing. Digital evidence collected.', NULL),
('C-2026009', '02000000012', 3, 'Inappropriate Campus Attire', 'Minor', NULL, 'Pending', '2026-02-14', '08:00:00', 'Main Building Lobby', 4, 2, 
 'Wearing inappropriate clothing on wash day (tank top and shorts).', 'Security guard', NULL, 'Dress code violation. First offense.', NULL),
('C-2026010', '02000000020', 13, 'Lending/Borrowing ID', 'Major', 'Category A', 'Pending', '2026-02-18', '07:50:00', 'Main Gate', 4, 2, 
 'Student caught using another student''s ID to enter campus.', 'Security team', NULL, 'Serious policy violation. Both students identified.', NULL),

-- ON GOING CASES (10)
('C-2026011', '02000000003', 1, 'Non-adherence to Student Decorum', 'Minor', NULL, 'On Going', '2026-01-10', '08:45:00', 'Building C - Room 301', 3, 2, 
 'Multiple tardiness incidents. Fourth occurrence this month.', 'Subject teacher', 'Parent conference scheduled for next week', 'Pattern of behavior noted. Monitoring progress.', NULL),
('C-2026012', '02000000007', 8, 'Classroom Disruption', 'Minor', NULL, 'On Going', '2026-01-14', '13:30:00', 'Computer Lab 2', 3, 2, 
 'Playing games during computer class instead of following lesson.', 'Lab instructor + student witnesses', 'Written warning issued. Counseling in progress', 'Second offense. Behavioral intervention ongoing.', NULL),
('C-2026013', '02000000016', 17, 'Cheating', 'Major', 'Category A', 'On Going', '2026-01-20', '15:00:00', 'Room A-405', 3, 2, 
 'Copying answers from classmate during Programming exam.', 'Exam proctor and nearby students', 'Exam paper confiscated. Case under review', 'Investigating full extent of academic dishonesty.', NULL),
('C-2026014', '02000000021', 19, 'Vandalism', 'Major', 'Category B', 'On Going', '2026-01-28', '17:00:00', 'Boys Restroom - 2nd Floor', 4, 2, 
 'Graffiti found on restroom walls. Security footage identified student.', 'Janitor and security personnel', 'Student admitted offense. Restitution plan being prepared', 'To pay for cleaning and perform community service.', NULL),
('C-2026015', '02000000013', 2, 'Non-wearing of School Uniform', 'Minor', NULL, 'On Going', '2026-02-02', '07:40:00', 'Main Entrance', 4, 2, 
 'Repeated uniform violations - wearing casual jacket over uniform.', 'Security guard', 'Student counseled. Monitoring compliance', 'Third violation. Escalation to parents needed.', NULL),
('C-2026016', '02000000026', 24, 'Use of Profane Language', 'Major', 'Category B', 'On Going', '2026-02-06', '11:30:00', 'Hallway - Building B', 3, 2, 
 'Student used profane and insulting language towards another student during argument.', 'Multiple students (5 witnesses)', 'Both parties interviewed. Mediation scheduled', 'Requires conflict resolution intervention.', NULL),
('C-2026017', '02000000009', 10, 'Bringing Pets', 'Minor', NULL, 'On Going', '2026-02-10', '08:15:00', 'Student Parking', 4, 2, 
 'Student brought pet cat to campus. Found in student locker area.', 'Security and students', 'Pet removed. Parent contacted to retrieve animal', 'Student claims forgot pet was in bag.', NULL),
('C-2026018', '02000000023', 18, 'Plagiarism', 'Major', 'Category A', 'On Going', '2026-02-15', '10:00:00', 'Library - Research Area', 3, 2, 
 'Major project submitted with plagiarized content. Similarity check confirmed.', 'Subject teacher', 'Student being interviewed. Sources being verified', 'Academic integrity violation under investigation.', NULL),
('C-2026019', '02000000011', 6, 'Improper Use of School Property', 'Minor', NULL, 'On Going', '2026-02-17', '14:30:00', 'Gym Equipment Room', 3, 2, 
 'Using gym equipment without authorization and leaving equipment damaged.', 'PE teacher', 'Student to repair/replace damaged equipment', 'Assessing extent of damage and responsibility.', NULL),
('C-2026020', '02000000027', 16, 'Allowing Non-STI Entry', 'Major', 'Category A', 'On Going', '2026-02-20', '12:00:00', 'Campus Gate B', 4, 2, 
 'Student allowed unauthorized person to enter campus using student ID.', 'Security personnel', 'Investigation ongoing. Reviewing security footage', 'Security breach. Determining appropriate sanction.', NULL),

-- RESOLVED CASES (10)
('C-2026021', '02000000002', 1, 'Non-adherence to Student Decorum', 'Minor', NULL, 'Resolved', '2026-01-08', '08:20:00', 'Room A-101', 3, 2, 
 'Student late to first period class.', 'Class teacher', 'Verbal warning issued', 'Student apologized. No repeat incidents.', '2026-01-08'),
('C-2026022', '02000000006', 2, 'Non-wearing of School Uniform', 'Minor', NULL, 'Resolved', '2026-01-12', '07:50:00', 'Main Gate', 4, 2, 
 'Missing school ID lanyard. Wearing ID in pocket instead.', 'Security guard', 'Written reprimand. Student complied immediately', 'Issue resolved. Student purchased new lanyard.', '2026-01-12'),
('C-2026023', '02000000014', 8, 'Classroom Disruption', 'Minor', NULL, 'Resolved', '2026-01-16', '11:00:00', 'Room B-205', 3, 2, 
 'Using mobile phone during class time.', 'Subject teacher', 'Phone confiscated and returned after class', 'Student acknowledged violation. Committed to improvement.', '2026-01-16'),
('C-2026024', '02000000019', 11, 'Public Display of Affection', 'Minor', NULL, 'Resolved', '2026-01-24', '15:30:00', 'Campus Garden', 3, 2, 
 'Holding hands and hugging in public areas beyond appropriate behavior.', 'Teacher on duty', 'Counseling session conducted with both students', 'Students understood policies. No further incidents.', '2026-01-25'),
('C-2026025', '02000000004', 5, 'Disrespect to National Symbols', 'Minor', NULL, 'Resolved', '2026-01-30', '07:00:00', 'Flag Ceremony Area', 3, 2, 
 'Not standing properly during flag ceremony. Talking during national anthem.', 'Multiple teachers', 'Student counseled on civic responsibility', 'Student apologized. Attended values education session.', '2026-01-30'),
('C-2026026', '02000000017', 1, 'Non-adherence to Student Decorum', 'Minor', NULL, 'Resolved', '2026-02-03', '09:00:00', 'Corridor Building A', 3, 2, 
 'Running in hallways during class hours.', 'Teacher on duty', 'Verbal warning given', 'Student complied. Safety rules explained.', '2026-02-03'),
('C-2026027', '02000000024', 8, 'Classroom Disruption', 'Minor', NULL, 'Resolved', '2026-02-07', '10:45:00', 'Science Laboratory', 3, 2, 
 'Horseplay during lab experiment causing minor disturbance.', 'Lab teacher and classmates', 'Student reprimanded. Safety protocols reviewed', 'No damage occurred. Behavior corrected.', '2026-02-07'),
('C-2026028', '02000000028', 2, 'Non-wearing of School Uniform', 'Minor', NULL, 'Resolved', '2026-02-11', '07:55:00', 'Campus Entrance', 4, 2, 
 'Wearing colored socks instead of regulation white socks.', 'Security guard', 'Student borrowed proper socks from office', 'Minor violation. Corrected immediately.', '2026-02-11'),
('C-2026029', '02000000029', 1, 'Non-adherence to Student Decorum', 'Minor', NULL, 'Resolved', '2026-02-16', '08:10:00', 'Room C-202', 3, 2, 
 'Sleeping during class session.', 'Subject teacher', 'Student woken and counseled', 'Medical issue ruled out. Student committed to stay alert.', '2026-02-16'),
('C-2026030', '02000000030', 6, 'Improper Use of School Property', 'Minor', NULL, 'Resolved', '2026-02-19', '13:00:00', 'Library', 3, 2, 
 'Left library books on table instead of returning to proper shelf.', 'Librarian', 'Student reminded of library rules', 'Student apologized and returned books properly.', '2026-02-19'),

-- 2025 CASES (10 - Previous Year Cases)
('C-2025001', '02000000001', 1, 'Non-adherence to Student Decorum', 'Minor', NULL, 'Resolved', '2025-03-15', '08:15:00', 'Building A - Room 201', 3, 2, 
 'Student arrived 15 minutes late to first period class without valid excuse.', 'Class teacher - Maria Santos', 'Verbal warning issued', 'First offense for this semester. Student apologized and committed to punctuality.', '2025-03-15'),
('C-2025002', '02000000005', 2, 'Non-wearing of School Uniform', 'Minor', NULL, 'Resolved', '2025-04-20', '07:45:00', 'Main Gate Entrance', 4, 2, 
 'Student entered campus wearing casual clothes (jeans and t-shirt) instead of proper uniform.', 'Security guard - Carlos Dela Cruz', 'Written reprimand issued, student changed to proper uniform', 'Student claimed uniform was being washed. Parent notified.', '2025-04-20'),
('C-2025003', '02000000016', 8, 'Classroom Disruption', 'Minor', NULL, 'Resolved', '2025-05-10', '10:30:00', 'Computer Laboratory 3', 3, 2, 
 'Student was repeatedly talking and laughing loudly during programming class, disturbing other students.', 'Teacher: Maria Santos; Classmates: 3 students', 'Student conference held. Written warning issued', 'Multiple warnings given during class. Behavior improved after conference.', '2025-05-12'),
('C-2025004', '02000000022', 17, 'Cheating', 'Major', 'Category A', 'Resolved', '2025-06-14', '14:00:00', 'Room B-305', 3, 2, 
 'Student caught with written notes hidden in calculator case during Business Mathematics midterm exam.', 'Proctor: Maria Santos; Student seated nearby: 2 witnesses', 'Exam grade forfeited. 3-day corrective reinforcement applied', 'Major offense documented. Parent conference held. Student completed sanction.', '2025-06-20'),
('C-2025005', '02000000009', 4, 'Losing/Forgetting ID', 'Minor', NULL, 'Resolved', '2025-07-01', '07:30:00', 'Main Gate', 4, 2, 
 'Student forgot ID for the third time this semester. Unable to enter campus without temporary pass.', 'Security guard on duty', 'Written warning issued. Temporary ID provided', 'Third occurrence. Student advised on responsibility. No further incidents.', '2025-07-01'),
('C-2025006', '02000000008', 14, 'Smoking/Vaping on Campus', 'Major', 'Category A', 'Resolved', '2025-08-10', '12:15:00', 'Behind Gymnasium', 4, 2, 
 'Student caught smoking cigarettes in restricted area behind the gymnasium during lunch break.', 'Security guard + 1 janitor', 'Student brought to DO office, cigarettes confiscated. 7-day suspension applied', 'Student admitted to offense. Parent contacted immediately. Suspension completed. Under watch.', '2025-08-20'),
('C-2025007', '02000000017', 11, 'Public Display of Affection', 'Minor', NULL, 'Resolved', '2025-09-20', '16:45:00', 'Student Lounge', 3, 2, 
 'Students engaged in inappropriate public display of affection (prolonged embrace and kissing) in student common area.', 'Teacher on duty + 5 students present', 'Verbal warning, counseling session conducted', 'Both students counseled on appropriate campus behavior. First offense.', '2025-09-20'),
('C-2025008', '02000000003', 19, 'Vandalism', 'Major', 'Category B', 'Resolved', '2025-10-02', '17:30:00', 'Restroom - 3rd Floor Building C', 4, 2, 
 'Student caught spray painting graffiti on restroom walls. Security footage confirmed identity.', 'Security personnel, janitor who discovered vandalism', 'Student questioned, admitted to offense. Community service completed', 'Student paid for repainting costs and performed 20 hours community service. Parent meeting held.', '2025-10-15'),
('C-2025009', '02000000024', 10, 'Bringing Pets', 'Minor', NULL, 'Resolved', '2025-11-15', '08:00:00', 'Parking Area', 4, 2, 
 'Student brought a small dog to campus in backpack. Animal was discovered during routine inspection.', 'Security guard at entrance', 'Pet removed from campus, parent called to pick up animal', 'Student unaware of policy. Educational discussion conducted. No malicious intent.', '2025-11-15'),
('C-2025010', '02000000021', 20, 'Cyberbullying/Defamation', 'Major', 'Category B', 'Resolved', '2025-12-01', '09:00:00', 'Reported online, investigated in DO Office', 3, 2, 
 'Student posted derogatory and insulting comments about a classmate on social media group. Screenshots provided as evidence.', 'Victim student + 3 classmates who witnessed posts', 'Investigation completed. Mediation held. 5-day suspension applied', 'Serious case. Both students and parents called for mediation. Student completed suspension and apologized.', '2025-12-10');

PRINT 'Cases inserted: 40';
GO

-- ============================================
-- UPDATE STUDENT OFFENSE COUNTS
-- ============================================
PRINT 'Updating student offense counts...';

UPDATE students 
SET 
    total_offenses = (SELECT COUNT(*) FROM cases WHERE student_id = students.student_id AND is_archived = 0),
    major_offenses = (SELECT COUNT(*) FROM cases WHERE student_id = students.student_id AND severity = 'Major' AND is_archived = 0),
    minor_offenses = (SELECT COUNT(*) FROM cases WHERE student_id = students.student_id AND severity = 'Minor' AND is_archived = 0),
    last_incident_date = (SELECT MAX(date_reported) FROM cases WHERE student_id = students.student_id)
WHERE student_id IN (
    SELECT DISTINCT student_id FROM cases
);

PRINT 'Student offense counts updated!';
GO

-- ============================================
-- INSERT CASE HISTORY FOR ALL CASES
-- ============================================
PRINT 'Inserting case history...';

INSERT INTO case_history (case_id, changed_by, action, new_value, notes, timestamp)
SELECT 
    case_id,
    2,
    'Created',
    'Status: ' + status,
    'Case created and logged into system',
    DATEADD(MINUTE, 5, CAST(date_reported AS DATETIME) + CAST(ISNULL(time_reported, '08:00:00') AS DATETIME))
FROM cases;

PRINT 'Case history inserted!';
GO

-- ============================================
-- INSERT SAMPLE LOST & FOUND ITEMS
-- ============================================
PRINT 'Inserting Lost & Found items...';

INSERT INTO lost_found_items (item_id, item_name, category, found_location, date_found, status, description)
VALUES 
('LF-1001', 'Backpack', 'Electronics', 'Cafeteria', '2023-10-14', 'Unclaimed', 'Blue JanSport backpack with laptop'),
('LF-1002', 'Water Bottle', 'Accessories', 'Gym', '2023-10-13', 'Unclaimed', 'Stainless steel water bottle'),
('LF-1003', 'Textbook', 'Books', 'Library', '2023-10-12', 'Claimed', 'Grade 11 Math textbook'),
('LF-1004', 'Calculator', 'Electronics', 'Room C401', '2023-10-08', 'Claimed', 'Scientific calculator Casio fx-991');

PRINT 'Lost & Found items inserted: 4';
GO

-- ============================================
-- INSERT WATCH LIST ENTRIES
-- ============================================
PRINT 'Inserting watch list entries...';

INSERT INTO watch_list (student_id, reason, added_by, added_date, notes)
VALUES 
('02000000022', 'Multiple major offenses: Cheating and other violations. Requires close monitoring.', 2, '2026-02-01', 
 'Student requires close monitoring. Consider probation if another offense occurs.'),
('02000000008', 'Major offense: Smoking on campus. Multiple previous violations. On watch list.', 2, '2026-02-05', 
 'Requires behavioral intervention. Parent involvement necessary.');

PRINT 'Watch list entries inserted: 2';
GO

-- ============================================
-- INSERT SAMPLE SANCTIONS APPLIED
-- ============================================
PRINT 'Inserting applied sanctions...';

INSERT INTO case_sanctions (case_id, sanction_id, applied_date, is_completed, completion_date, notes)
VALUES
-- 2026 Case Sanctions
('C-2026021', 1, '2026-01-08', 1, '2026-01-08', 'Student acknowledged warning and committed to improvement'),
('C-2026022', 3, '2026-01-12', 1, '2026-01-12', 'Written reprimand issued and filed'),
('C-2026024', 1, '2026-01-24', 1, '2026-01-25', 'Counseling completed with both students'),
('C-2026025', 2, '2026-01-30', 1, '2026-01-30', 'Values education session completed'),
('C-2026026', 1, '2026-02-03', 1, '2026-02-03', 'Safety rules explained and acknowledged'),
('C-2026027', 3, '2026-02-07', 1, '2026-02-07', 'Lab safety protocols reviewed'),
('C-2026030', 1, '2026-02-19', 1, '2026-02-19', 'Library rules reviewed with student'),
-- 2025 Case Sanctions
('C-2025001', 1, '2025-03-15', 1, '2025-03-15', 'Verbal warning issued. Student committed to punctuality'),
('C-2025002', 3, '2025-04-20', 1, '2025-04-20', 'Written reprimand issued and filed'),
('C-2025003', 3, '2025-05-10', 1, '2025-05-12', 'Written warning issued after conference'),
('C-2025004', 4, '2025-06-14', 1, '2025-06-20', '3-day corrective reinforcement completed'),
('C-2025005', 3, '2025-07-01', 1, '2025-07-01', 'Written warning issued for third ID violation'),
('C-2025006', 7, '2025-08-10', 1, '2025-08-17', '7-day suspension completed. Major offense documented'),
('C-2025007', 1, '2025-09-20', 1, '2025-09-20', 'Counseling session completed with both students'),
('C-2025008', 2, '2025-10-02', 1, '2025-10-15', 'Community service and restitution completed'),
('C-2025009', 1, '2025-11-15', 1, '2025-11-15', 'Educational discussion conducted about campus policies'),
('C-2025010', 8, '2025-12-01', 1, '2025-12-10', '5-day suspension completed. Mediation successful');

PRINT 'Applied sanctions inserted: 17';
GO

-- ============================================
-- INSERT SAMPLE NOTIFICATIONS
-- ============================================
PRINT 'Inserting notifications...';

INSERT INTO notifications (user_id, title, message, type, related_id, is_read)
VALUES
(2, 'New Case Reported', 'New cyberbullying case C-2026008 requires immediate attention', 'case_update', 'C-2026008', 0),
(2, 'Major Violation', 'Case C-2026007 (Vaping on Campus) requires decision on sanctions', 'case_update', 'C-2026007', 0),
(2, 'Active Investigation', 'Case C-2026005 (Cheating) is currently under investigation', 'case_update', 'C-2026005', 1),
(2, 'Pending Action', 'Case C-2026010 (ID Violation) awaits disciplinary action', 'case_update', 'C-2026010', 0);

PRINT 'Notifications inserted: 4';
GO

-- ============================================
-- FINAL VERIFICATION & SUMMARY
-- ============================================

PRINT '';
PRINT '============================================';
PRINT 'Database Created Successfully!';
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
    '2026 Cases', 
    COUNT(*) 
FROM cases 
WHERE case_id LIKE 'C-2026%'
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
FROM lost_found_items
UNION ALL
SELECT 
    'Watch List Entries', 
    COUNT(*) 
FROM watch_list;
GO

PRINT '';
PRINT '============================================';
PRINT 'STUDENT BREAKDOWN BY TRACK/COURSE';
PRINT '============================================';

SELECT 
    track_course AS 'Track/Course',
    student_type AS 'Type',
    COUNT(*) AS 'Count'
FROM students
GROUP BY track_course, student_type
ORDER BY student_type, COUNT(*) DESC;
GO

PRINT '';
PRINT '============================================';
PRINT '2026 CASES SUMMARY';
PRINT '============================================';

SELECT 
    case_id AS 'Case ID',
    student_id AS 'Student ID',
    case_type AS 'Offense Type',
    severity AS 'Severity',
    status AS 'Status',
    date_reported AS 'Date'
FROM cases
WHERE case_id LIKE 'C-2026%'
ORDER BY date_reported;
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
PRINT 'All student IDs follow format: 02000 + 6 digits';
PRINT '';
PRINT 'SHS Students with Cases:';
PRINT '  - 02000000001 (Juan Dela Cruz - STEM) - 2 cases (2025 & 2026)';
PRINT '  - 02000000003 (Pedro Santos - STEM) - 2 cases (2025 & 2026)'; 
PRINT '  - 02000000005 (Carlos Mendoza - ABM) - 2 cases (2025 & 2026)';
PRINT '  - 02000000008 (Isabella Cruz - HUMSS - On Watch) - 2 cases (2025 & 2026)';
PRINT '  - 02000000009 (Luis Fernandez - HUMSS) - 2 cases (2025 & 2026)';
PRINT '';
PRINT 'College Students with Cases:';
PRINT '  - 02000000016 (Marco Villanueva - BSIT) - 2 cases (2025 & 2026)';
PRINT '  - 02000000017 (Angela Castillo - BSIT) - 2 cases (2025 & 2026)';
PRINT '  - 02000000021 (Valentina Romero - BSBA) - 2 cases (2025 & 2026)';
PRINT '  - 02000000022 (Andres Vargas - BSBA - On Watch) - 2 cases (2025 & 2026)';
PRINT '  - 02000000024 (Sebastian Martinez - BSCS) - 2 cases (2025 & 2026)';
PRINT '';
PRINT '✅ Database ready for use!';
PRINT '✅ Total Users: 35 (5 staff + 30 students)';
PRINT '✅ All students have auto-generated emails: lastname.last6digits@sti.edu';
PRINT '✅ Default password for all students: password';
PRINT '';
PRINT 'Sample Student Login Credentials:';
PRINT '  Email: delacruz.000001@sti.edu | Password: password';
PRINT '  Email: garcia.000002@sti.edu | Password: password';
PRINT '  Email: santos.000003@sti.edu | Password: password';
PRINT '';
PRINT '✅ Includes 40 total cases:';
PRINT '   - 10 cases from 2025 (all resolved)';
PRINT '   - 30 cases from 2026 (10 Pending, 10 On Going, 10 Resolved)';
PRINT '✅ All student IDs in consistent format: 02000XXXXXX';
PRINT '✅ All student offense counts updated';
PRINT '✅ Watch list populated';
PRINT '✅ Case history tracked';
PRINT '✅ Lost & Found items working correctly';
PRINT '============================================';
GO