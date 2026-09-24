-- PrototypeDO Database Schema - MySQL 8.0+
-- Discipline Office Management System
-- Recreates the database from scratch; existing data in this database is removed.

DROP DATABASE IF EXISTS PrototypeDO_DB;
CREATE DATABASE IF NOT EXISTS PrototypeDO_DB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE PrototypeDO_DB;

CREATE TABLE system_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value LONGTEXT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
INSERT INTO system_settings (setting_key, setting_value) VALUES
('maintenance_mode', 'disabled'),
('global_banner_enabled', 'disabled'),
('global_banner_text', ''),
('archive_after_days', '30'),
('archived_case_retention_days', '365'),
('lost_found_retention_days', '365'),
('audit_log_retention_days', '730'),
('escalation_minor_count', '3');
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    teacher_id VARCHAR(20) NULL CHECK (teacher_id IS NULL OR teacher_id REGEXP '^01000[0-9]{6}$'),
    do_id VARCHAR(20) NULL CHECK (do_id IS NULL OR do_id REGEXP '^03000[0-9]{6}$'),
    teacher_subrole VARCHAR(30) NULL CHECK (teacher_subrole IS NULL OR teacher_subrole = 'department_head'),
    program VARCHAR(50) NULL CHECK (program IS NULL OR program IN ('Information Technology', 'Tourism Management', 'Criminal Justice Education', 'Hospitality Management', 'Business & Management', 'Arts & Sciences', 'Engineering')),
    role VARCHAR(20) NOT NULL CHECK (role IN ('super_admin', 'discipline_office', 'teacher', 'security', 'student')),
    contact_number VARCHAR(20),
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME,
    remember_token VARCHAR(64) NULL,
    remember_token_expiry DATETIME NULL,
    terms_accepted_version INT DEFAULT 0,
    terms_accepted_date DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- department heads must have a program; non-heads/other roles must not
    CONSTRAINT chk_department_head_program CHECK (
        (teacher_subrole = 'department_head' AND program IS NOT NULL)
        OR (teacher_subrole IS NULL AND program IS NULL)
    )
);
-- ============================================
-- 2. STUDENTS TABLE (Extended student info)
-- ============================================
CREATE TABLE students (
    student_id VARCHAR(20) PRIMARY KEY,
    user_id INT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    grade_year VARCHAR(20) NOT NULL,
    track_course VARCHAR(100),
    section VARCHAR(50),
    student_type VARCHAR(20) CHECK (student_type IN ('SHS', 'College')),
    status VARCHAR(20) DEFAULT 'Good Standing' CHECK (status IN ('Good Standing', 'On Watch', 'On Probation')),
    total_offenses INT DEFAULT 0,
    major_offenses INT DEFAULT 0,
    minor_offenses INT DEFAULT 0,
    last_incident_date DATE,
    guardian_name VARCHAR(100),
    guardian_contact VARCHAR(20),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);
-- ============================================
-- 3. OFFENSE TYPES TABLE (Catalog based on handbook)
-- ============================================
CREATE TABLE offense_types (
    offense_id INT AUTO_INCREMENT PRIMARY KEY,
    offense_name VARCHAR(100) NOT NULL,
    category VARCHAR(20) NOT NULL CHECK (category IN ('Major', 'Minor')),
    description VARCHAR(500),
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
-- ============================================
-- 4. CASES TABLE (Main discipline cases)
-- ============================================
CREATE TABLE cases (
    case_id VARCHAR(20) PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL,
    offense_id INT NULL,
    case_type VARCHAR(100) NOT NULL,
    severity VARCHAR(20) NOT NULL CHECK (severity IN ('Major', 'Minor')),
    offense_category VARCHAR(50) NULL,
    status VARCHAR(50) DEFAULT 'Pending' CHECK (status IN ('Pending', 'On Going', 'Resolved', 'Dismissed', 'Recorded', 'Unrecorded')),
    date_reported DATE NOT NULL DEFAULT CURRENT_DATE,
    time_reported TIME,
    location VARCHAR(200),
    reported_by INT NULL,
    assigned_to INT NULL,
    description LONGTEXT,
    witnesses VARCHAR(500),
    action_taken VARCHAR(500),
    notes LONGTEXT,
    attachments LONGTEXT,
    next_hearing_date DATETIME,
    resolved_date DATE NULL,
    minor_escalation_seen TINYINT(1) NOT NULL DEFAULT 0,
    is_archived TINYINT(1) DEFAULT 0,
    manually_restored TINYINT(1) DEFAULT 0,
    archived_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_cases_student FOREIGN KEY (student_id) REFERENCES students(student_id),
    CONSTRAINT fk_cases_offense FOREIGN KEY (offense_id) REFERENCES offense_types(offense_id),
    CONSTRAINT fk_cases_reported_by FOREIGN KEY (reported_by) REFERENCES users(user_id),
    CONSTRAINT fk_cases_assigned_to FOREIGN KEY (assigned_to) REFERENCES users(user_id)
);
-- ============================================
-- 5. SANCTIONS TABLE (Corrective actions)
-- ============================================
CREATE TABLE sanctions (
    sanction_id INT AUTO_INCREMENT PRIMARY KEY,
    sanction_name VARCHAR(200) NOT NULL,
    severity_level INT NOT NULL CHECK (severity_level BETWEEN 1 AND 5),
    description VARCHAR(500),
    requires_schedule TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
-- ============================================
-- 6. CASE_SANCTIONS TABLE (Link cases to sanctions)
-- ============================================
CREATE TABLE case_sanctions (
    case_sanction_id INT AUTO_INCREMENT PRIMARY KEY,
    case_id VARCHAR(20),
    sanction_id INT,
    applied_date DATE DEFAULT CURRENT_DATE,
    duration_days INT NULL,
    duration_extra_hours INT NOT NULL DEFAULT 0,
    is_completed TINYINT(1) DEFAULT 0,
    completion_date DATE,
    notes VARCHAR(500),
    scheduled_date DATE NULL,
    scheduled_time TIME NULL,
    scheduled_end_time TIME NULL,
    schedule_notes VARCHAR(500),
    deadline DATETIME NULL,
    original_duration_days INT NULL,
    days_extended INT DEFAULT 0,
    extension_count INT DEFAULT 0,
    extension_notes LONGTEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_case_sanctions_case FOREIGN KEY (case_id) REFERENCES cases(case_id),
    CONSTRAINT fk_case_sanctions_sanction FOREIGN KEY (sanction_id) REFERENCES sanctions(sanction_id)
);
-- ============================================
-- 6.5. CASE_CHECKINS TABLE (Check-in/Check-out tracking for time-based sanctions)
-- ============================================
CREATE TABLE case_checkins (
    checkin_id INT AUTO_INCREMENT PRIMARY KEY,
    case_sanction_id INT NOT NULL,
    day_number INT NOT NULL,
    check_in_time DATETIME NULL,
    check_out_time DATETIME NULL,
    check_in_date DATE NOT NULL DEFAULT CURRENT_DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_case_checkins_sanction FOREIGN KEY (case_sanction_id) REFERENCES case_sanctions(case_sanction_id)
);
-- ============================================
-- 6.6. COMMUNITY_SERVICE_SUBMISSIONS TABLE
-- ============================================
CREATE TABLE community_service_submissions (
    submission_id INT AUTO_INCREMENT PRIMARY KEY,
    case_id VARCHAR(20) NOT NULL,
    case_sanction_id INT NOT NULL,
    student_id VARCHAR(20) NOT NULL,
    uploaded_by INT NULL,
    file_name VARCHAR(255) NOT NULL,
    original_file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size_bytes BIGINT NULL,
    mime_type VARCHAR(120) NULL,
    remarks VARCHAR(1000) NULL,
    review_status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (review_status IN ('pending', 'approved', 'rejected')),
    review_notes VARCHAR(1000) NULL,
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    is_seen_by_do TINYINT(1) NOT NULL DEFAULT 0,
    seen_by_do_at DATETIME NULL,
    seen_by_do_user_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_submissions_case FOREIGN KEY (case_id) REFERENCES cases(case_id),
    CONSTRAINT fk_submissions_sanction FOREIGN KEY (case_sanction_id) REFERENCES case_sanctions(case_sanction_id),
    CONSTRAINT fk_submissions_student FOREIGN KEY (student_id) REFERENCES students(student_id),
    CONSTRAINT fk_submissions_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES users(user_id),
    CONSTRAINT fk_submissions_reviewed_by FOREIGN KEY (reviewed_by) REFERENCES users(user_id),
    CONSTRAINT fk_submissions_seen_by FOREIGN KEY (seen_by_do_user_id) REFERENCES users(user_id)
);
-- ============================================
-- 7. CASE_HISTORY TABLE (Track all changes)
-- ============================================
CREATE TABLE case_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    case_id VARCHAR(20),
    changed_by INT NULL,
    action VARCHAR(50) NOT NULL,
    old_value LONGTEXT,
    new_value LONGTEXT,
    notes VARCHAR(500),
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_case_history_case FOREIGN KEY (case_id) REFERENCES cases(case_id),
    CONSTRAINT fk_case_history_changed_by FOREIGN KEY (changed_by) REFERENCES users(user_id)
);
-- ============================================
-- 8. LOST_FOUND_ITEMS TABLE
-- ============================================
CREATE TABLE lost_found_items (
    item_id VARCHAR(20) PRIMARY KEY,
    item_name VARCHAR(200) NOT NULL,
    category VARCHAR(50) NOT NULL,
    description LONGTEXT,
    found_location VARCHAR(200) NOT NULL,
    date_found DATE NOT NULL DEFAULT CURRENT_DATE,
    time_found TIME,
    finder_name VARCHAR(100),
    finder_student_id VARCHAR(20) NULL,
    status VARCHAR(20) DEFAULT 'Unclaimed' CHECK (status IN ('Unclaimed', 'Claimed', 'Disposed')),
    claimer_name VARCHAR(100),
    claimer_student_id VARCHAR(20) NULL,
    date_claimed DATE,
    image_path VARCHAR(500),
    is_archived TINYINT(1) DEFAULT 0,
    archived_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_lost_found_finder FOREIGN KEY (finder_student_id) REFERENCES students(student_id),
    CONSTRAINT fk_lost_found_claimer FOREIGN KEY (claimer_student_id) REFERENCES students(student_id)
);
-- ============================================
-- 8.1 LOST_FOUND_CATEGORIES TABLE
-- ============================================
CREATE TABLE lost_found_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(500),
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
-- Insert default categories
INSERT INTO lost_found_categories (category_name, description) VALUES
('Electronics', 'Electronic devices, gadgets, phones, headphones, and tech accessories'),
('Books', 'Textbooks, notebooks, and reading materials'),
('Bags', 'Backpacks, lunch boxes, and bag-type containers'),
('Accessories', 'Belts, scarves, watches, hand sanitizer, and other personal accessories'),
('Clothing', 'Uniforms, jackets, shoes, caps, and apparel'),
('ID/Documents', 'School IDs, documents, and important papers'),
('Keys', 'House keys, locker keys, and car keys'),
('Sports Equipment', 'Sports gear, medals, balls, and athletic equipment'),
('Personal Items', 'Wallets, personal belongings, and miscellaneous personal effects'),
('School Supplies', 'Pens, folders, pencils, and stationery'),
('Others', 'Miscellaneous items not fitting other categories');
-- ============================================
-- 9. NOTIFICATIONS TABLE
-- ============================================
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(200) NOT NULL,
    message LONGTEXT NOT NULL,
    type VARCHAR(50) NOT NULL,
    related_id VARCHAR(50),
    is_read TINYINT(1) DEFAULT 0,
    read_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(user_id)
);
-- ============================================
-- 10. REPORTS TABLE (Generated reports history)
-- ============================================
CREATE TABLE reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    report_name VARCHAR(200) NOT NULL,
    report_type VARCHAR(50) NOT NULL,
    format VARCHAR(10) NOT NULL CHECK (format IN ('PDF', 'Excel', 'CSV')),
    file_path VARCHAR(500),
    generated_by INT NULL,
    date_generated DATETIME DEFAULT CURRENT_TIMESTAMP,
    parameters LONGTEXT,
    file_size_kb INT,
    CONSTRAINT fk_reports_generated_by FOREIGN KEY (generated_by) REFERENCES users(user_id)
);
-- ============================================
-- 11. CALENDAR_EVENTS TABLE
-- ============================================
CREATE TABLE calendar_events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(200) NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME,
    event_end_time TIME NULL,
    category VARCHAR(50) NOT NULL CHECK (category IN ('Meeting', 'Conference', 'Deadline', 'Hearing', 'Holiday', 'Other')),
    description LONGTEXT,
    location VARCHAR(200),
    created_by INT NULL,
    target_user_id INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_calendar_created_by FOREIGN KEY (created_by) REFERENCES users(user_id),
    CONSTRAINT fk_calendar_target_user FOREIGN KEY (target_user_id) REFERENCES users(user_id)
);
-- ============================================
-- 12. HANDBOOK_SECTIONS TABLE (For editing)
-- ============================================
CREATE TABLE handbook_sections (
    section_id INT AUTO_INCREMENT PRIMARY KEY,
    section_title VARCHAR(200) NOT NULL,
    section_order INT NOT NULL,
    content LONGTEXT NOT NULL,
    last_edited_by INT NULL,
    last_edited_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_handbook_last_edited_by FOREIGN KEY (last_edited_by) REFERENCES users(user_id)
);
-- ============================================
-- 13. WATCH_LIST TABLE (Students to monitor)
-- ============================================
CREATE TABLE watch_list (
    watch_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20),
    reason VARCHAR(500) NOT NULL,
    added_by INT NULL,
    added_date DATE DEFAULT CURRENT_DATE,
    is_active TINYINT(1) DEFAULT 1,
    removed_date DATE,
    removed_by INT NULL,
    notes LONGTEXT,
    CONSTRAINT fk_watch_list_student FOREIGN KEY (student_id) REFERENCES students(student_id),
    CONSTRAINT fk_watch_list_added_by FOREIGN KEY (added_by) REFERENCES users(user_id),
    CONSTRAINT fk_watch_list_removed_by FOREIGN KEY (removed_by) REFERENCES users(user_id)
);
-- ============================================
-- 14. AUDIT_LOG TABLE (System activity tracking)
-- ============================================
CREATE TABLE audit_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id VARCHAR(50),
    old_values LONGTEXT,
    new_values LONGTEXT,
    ip_address VARCHAR(50),
    user_agent VARCHAR(500),
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_log_user FOREIGN KEY (user_id) REFERENCES users(user_id)
);
-- ============================================
-- INDEXES for Performance
-- ============================================
CREATE INDEX idx_cases_student ON cases(student_id);
CREATE INDEX idx_cases_status ON cases(status);
CREATE INDEX idx_cases_severity ON cases(severity);
CREATE INDEX idx_cases_date ON cases(date_reported);
CREATE INDEX idx_cases_archived ON cases(is_archived);
CREATE INDEX idx_students_status ON students(status);
CREATE INDEX idx_notifications_user ON notifications(user_id, is_read);
CREATE INDEX idx_audit_user ON audit_log(user_id);
CREATE INDEX idx_lost_found_status ON lost_found_items(status);
CREATE INDEX idx_case_sanctions_case ON case_sanctions(case_id);
CREATE UNIQUE INDEX ux_users_teacher_id ON users(teacher_id);
CREATE UNIQUE INDEX ux_users_do_id ON users(do_id);
-- ============================================
-- TRIGGERS for Data Integrity
-- ============================================
-- Enforce sanction requirement for active cases: 'On Going' or 'Resolved' require at least one sanction
-- Note: Only enforced on UPDATE to allow initial schema data load. Application layer validates on INSERT.
DELIMITER $$

CREATE TRIGGER trg_enforce_sanction_on_active_case
BEFORE UPDATE ON cases
FOR EACH ROW
BEGIN
    IF NEW.status IN ('On Going', 'Resolved')
       AND NOT EXISTS (SELECT 1 FROM case_sanctions WHERE case_id = NEW.case_id) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot mark a case as On Going or Resolved without an applied sanction.';
    END IF;
END$$
-- Auto-set preset deadline for corrective reinforcement sanctions when missing.
-- Formula: applied_date + duration_days + 7 grace days, set to end-of-day (23:59:59).
CREATE TRIGGER trg_case_sanctions_autoset_corrective_deadline_insert
BEFORE INSERT ON case_sanctions
FOR EACH ROW
BEGIN
    IF NEW.deadline IS NULL
       AND COALESCE(NEW.duration_days, 0) > 0
       AND EXISTS (
           SELECT 1 FROM sanctions
           WHERE sanction_id = NEW.sanction_id
             AND LOWER(sanction_name) LIKE '%corrective reinforcement%'
       ) THEN
        SET NEW.deadline = TIMESTAMP(
            DATE_ADD(COALESCE(NEW.applied_date, CURRENT_DATE), INTERVAL (COALESCE(NEW.duration_days, 0) + 7) DAY),
            '23:59:59'
        );
    END IF;
END$$

CREATE TRIGGER trg_case_sanctions_autoset_corrective_deadline_update
BEFORE UPDATE ON case_sanctions
FOR EACH ROW
BEGIN
    IF NEW.deadline IS NULL
       AND COALESCE(NEW.duration_days, 0) > 0
       AND EXISTS (
           SELECT 1 FROM sanctions
           WHERE sanction_id = NEW.sanction_id
             AND LOWER(sanction_name) LIKE '%corrective reinforcement%'
       ) THEN
        SET NEW.deadline = TIMESTAMP(
            DATE_ADD(COALESCE(NEW.applied_date, CURRENT_DATE), INTERVAL (COALESCE(NEW.duration_days, 0) + 7) DAY),
            '23:59:59'
        );
    END IF;
END$$

DELIMITER ;
-- ============================================
-- INSERT DEFAULT USERS
-- ============================================
INSERT INTO users (username, password_hash, email, full_name, teacher_id, do_id, role, contact_number)
VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
 'admin@sti.edu', 'System Administrator', NULL, NULL, 'super_admin', '09123456789'),
('do_staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
 'do@sti.edu', 'John Doe', NULL, '03000000001', 'discipline_office', '09187654321'),
('teacher', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
 'teacher1@sti.edu', 'Maria Santos', '01000000001', NULL, 'teacher', '09171234567'),
('security', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
 'security1@sti.edu', 'Carlos Dela Cruz', NULL, NULL, 'security', '09184561234'),
('student', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
 'student1@sti.edu', 'Alex Reyes', NULL, NULL, 'student', '09193456781');

-- ============================================
-- INSERT ADDITIONAL STAFF (2 DO, 4 Security, 4 Teachers)
-- ============================================
-- Default password for all staff: 'password'
INSERT INTO users (username, password_hash, email, full_name, teacher_id, do_id, role, contact_number)
VALUES 
-- Discipline Office Staff (2 additional)
('sanvictores.discipline@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'sanvictores.discipline@sti.edu', 'Maria Bianca Sanvictores', NULL, '03000000002', 'discipline_office', '09189876543'),
('Balneg.discipline@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'balneg.discipline@sti.edu', 'Angelica Balneg', NULL, '03000000003', 'discipline_office', '09186543210'),
-- Security Staff (4)
('santos.security1@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'santos.security1@sti.edu', 'Robert Santos', NULL, NULL, 'security', '09184567891'),
('cruz.security2@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cruz.security2@sti.edu', 'Fernando Cruz', NULL, NULL, 'security', '09184567892'),
('diaz.security3@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'diaz.security3@sti.edu', 'Eduardo Diaz', NULL, NULL, 'security', '09184567893'),
('herrera.security4@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'herrera.security4@sti.edu', 'Manuel Herrera', NULL, NULL, 'security', '09184567894'),
-- Teachers (4)
('garcia.teacher@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'garcia.teacher@sti.edu', 'Lisa Garcia', '01000000002', NULL, 'teacher', '09173334567'),
('morales.teacher@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'morales.teacher@sti.edu', 'Vincent Morales', '01000000003', NULL, 'teacher', '09173334568'),
('gutierrez.teacher@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'gutierrez.teacher@sti.edu', 'Rachel Gutierrez', '01000000004', NULL, 'teacher', '09173334569'),
('lopez.teacher@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lopez.teacher@sti.edu', 'Francisco Lopez', '01000000005', NULL, 'teacher', '09173334570');

-- ============================================
-- INSERT STUDENT USER ACCOUNTS (Auto-generated emails)
-- ============================================
INSERT INTO users (username, password_hash, email, full_name, teacher_id, do_id, role, contact_number, is_active)
VALUES 
-- SHS Students
('delacruz.000001@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'delacruz.000001@sti.edu', 'Juan Santos Dela Cruz', NULL, NULL, 'student', '09171234001', 1),
('garcia.000002@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'garcia.000002@sti.edu', 'Maria Reyes Garcia', NULL, NULL, 'student', '09171234002', 1),
('santos.000003@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'santos.000003@sti.edu', 'Pedro Lopez Santos', NULL, NULL, 'student', '09171234003', 1),
('reyes.000004@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'reyes.000004@sti.edu', 'Ana Cruz Reyes', NULL, NULL, 'student', '09171234004', 1),
('mendoza.000005@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mendoza.000005@sti.edu', 'Carlos Torres Mendoza', NULL, NULL, 'student', '09171234005', 1),
('ramos.000006@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ramos.000006@sti.edu', 'Sofia Diaz Ramos', NULL, NULL, 'student', '09171234006', 1),
('torres.000007@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'torres.000007@sti.edu', 'Miguel Morales Torres', NULL, NULL, 'student', '09171234007', 1),
('cruz.000008@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cruz.000008@sti.edu', 'Isabella Fernandez Cruz', NULL, NULL, 'student', '09171234008', 1),
('fernandez.000009@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'fernandez.000009@sti.edu', 'Luis Diaz Fernandez', NULL, NULL, 'student', '09171234009', 1),
('diaz.000010@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'diaz.000010@sti.edu', 'Carmen Gutierrez Diaz', NULL, NULL, 'student', '09171234010', 1),
('morales.000011@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'morales.000011@sti.edu', 'Diego Herrera Morales', NULL, NULL, 'student', '09171234011', 1),
('gutierrez.000012@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'gutierrez.000012@sti.edu', 'Lucia Jimenez Gutierrez', NULL, NULL, 'student', '09171234012', 1),
('johnson.000013@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'johnson.000013@sti.edu', 'Alex Michael Johnson', NULL, NULL, 'student', '09171234013', 1),
('wilson.000014@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'wilson.000014@sti.edu', 'Emma Rose Wilson', NULL, NULL, 'student', '09171234014', 1),
('lee.000015@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lee.000015@sti.edu', 'Daniel James Lee', NULL, NULL, 'student', '09171234015', 1),
-- College Students
('villanueva.000016@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'villanueva.000016@sti.edu', 'Marco Santos Villanueva', NULL, NULL, 'student', '09181234001', 1),
('castillo.000017@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'castillo.000017@sti.edu', 'Angela Reyes Castillo', NULL, NULL, 'student', '09181234002', 1),
('herrera.000018@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'herrera.000018@sti.edu', 'Rafael Cruz Herrera', NULL, NULL, 'student', '09181234003', 1),
('jimenez.000019@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'jimenez.000019@sti.edu', 'Gabriela Torres Jimenez', NULL, NULL, 'student', '09181234004', 1),
('navarro.000020@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'navarro.000020@sti.edu', 'Daniel Mendoza Navarro', NULL, NULL, 'student', '09181234005', 1),
('romero.000021@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'romero.000021@sti.edu', 'Valentina Garcia Romero', NULL, NULL, 'student', '09181234006', 1),
('vargas.000022@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'vargas.000022@sti.edu', 'Andres Lopez Vargas', NULL, NULL, 'student', '09181234007', 1),
('flores.000023@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'flores.000023@sti.edu', 'Camila Diaz Flores', NULL, NULL, 'student', '09181234008', 1),
('martinez.000024@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'martinez.000024@sti.edu', 'Sebastian Ramos Martinez', NULL, NULL, 'student', '09181234009', 1),
('gonzalez.000025@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'gonzalez.000025@sti.edu', 'Nicole Morales Gonzalez', NULL, NULL, 'student', '09181234010', 1),
('lopez.000026@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lopez.000026@sti.edu', 'Adrian Fernandez Lopez', NULL, NULL, 'student', '09181234011', 1),
('perez.000027@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'perez.000027@sti.edu', 'Bianca Gutierrez Perez', NULL, NULL, 'student', '09181234012', 1),
('smith.000028@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'smith.000028@sti.edu', 'James Robert Smith', NULL, NULL, 'student', '09181234013', 1),
('brown.000029@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'brown.000029@sti.edu', 'Sophia Anne Brown', NULL, NULL, 'student', '09181234014', 1),
('wang.000030@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'wang.000030@sti.edu', 'Michael Chen Wang', NULL, NULL, 'student', '09181234015', 1);

-- ============================================
-- INSERT SAMPLE STUDENTS (Linked to user accounts)
-- Student IDs below are explicitly assigned sample values
-- ============================================
INSERT INTO students (student_id, user_id, first_name, last_name, middle_name, grade_year, track_course, section, student_type, status, guardian_name, guardian_contact)
VALUES 
-- SHS Students
('02000000001', (SELECT user_id FROM users WHERE username = 'delacruz.000001@sti.edu'), 'Juan', 'Dela Cruz', 'Santos', '11', 'STEM', 'A', 'SHS', 'Good Standing', 'Maria Dela Cruz', '09171234001'),
('02000000002', (SELECT user_id FROM users WHERE username = 'garcia.000002@sti.edu'), 'Maria', 'Garcia', 'Reyes', '11', 'ABM', 'B', 'SHS', 'Good Standing', 'Jose Garcia', '09171234002'),
('02000000003', (SELECT user_id FROM users WHERE username = 'santos.000003@sti.edu'), 'Pedro', 'Santos', 'Lopez', '12', 'STEM', 'A', 'SHS', 'Good Standing', 'Ana Santos', '09171234003'),
('02000000004', (SELECT user_id FROM users WHERE username = 'reyes.000004@sti.edu'), 'Ana', 'Reyes', 'Cruz', '12', 'HUMSS', 'C', 'SHS', 'Good Standing', 'Carlos Reyes', '09171234004'),
('02000000005', (SELECT user_id FROM users WHERE username = 'mendoza.000005@sti.edu'), 'Carlos', 'Mendoza', 'Torres', '11', 'ABM', 'B', 'SHS', 'Good Standing', 'Linda Mendoza', '09171234005'),
('02000000006', (SELECT user_id FROM users WHERE username = 'ramos.000006@sti.edu'), 'Sofia', 'Ramos', 'Diaz', '11', 'STEM', 'A', 'SHS', 'Good Standing', 'Robert Ramos', '09171234006'),
('02000000007', (SELECT user_id FROM users WHERE username = 'torres.000007@sti.edu'), 'Miguel', 'Torres', 'Morales', '12', 'ABM', 'B', 'SHS', 'Good Standing', 'Isabel Torres', '09171234007'),
('02000000008', (SELECT user_id FROM users WHERE username = 'cruz.000008@sti.edu'), 'Isabella', 'Cruz', 'Fernandez', '12', 'HUMSS', 'C', 'SHS', 'On Watch', 'Fernando Cruz', '09171234008'),
('02000000009', (SELECT user_id FROM users WHERE username = 'fernandez.000009@sti.edu'), 'Luis', 'Fernandez', 'Diaz', '11', 'HUMSS', 'C', 'SHS', 'Good Standing', 'Elena Fernandez', '09171234009'),
('02000000010', (SELECT user_id FROM users WHERE username = 'diaz.000010@sti.edu'), 'Carmen', 'Diaz', 'Gutierrez', '11', 'STEM', 'A', 'SHS', 'Good Standing', 'Ricardo Diaz', '09171234010'),
('02000000011', (SELECT user_id FROM users WHERE username = 'morales.000011@sti.edu'), 'Diego', 'Morales', 'Herrera', '12', 'ABM', 'B', 'SHS', 'Good Standing', 'Patricia Morales', '09171234011'),
('02000000012', (SELECT user_id FROM users WHERE username = 'gutierrez.000012@sti.edu'), 'Lucia', 'Gutierrez', 'Jimenez', '12', 'HUMSS', 'C', 'SHS', 'Good Standing', 'Manuel Gutierrez', '09171234012'),
('02000000013', (SELECT user_id FROM users WHERE username = 'johnson.000013@sti.edu'), 'Alex', 'Johnson', 'Michael', '12', 'STEM', 'A', 'SHS', 'Good Standing', 'Mary Johnson', '09171234013'),
('02000000014', (SELECT user_id FROM users WHERE username = 'wilson.000014@sti.edu'), 'Emma', 'Wilson', 'Rose', '11', 'ABM', 'B', 'SHS', 'Good Standing', 'Sarah Wilson', '09171234014'),
('02000000015', (SELECT user_id FROM users WHERE username = 'lee.000015@sti.edu'), 'Daniel', 'Lee', 'James', '12', 'HUMSS', 'C', 'SHS', 'Good Standing', 'Lisa Lee', '09171234015'),
-- College Students
('02000000016', (SELECT user_id FROM users WHERE username = 'villanueva.000016@sti.edu'), 'Marco', 'Villanueva', 'Santos', '1st Year', 'BSIT', 'IT-101', 'College', 'Good Standing', 'Rosa Villanueva', '09181234001'),
('02000000017', (SELECT user_id FROM users WHERE username = 'castillo.000017@sti.edu'), 'Angela', 'Castillo', 'Reyes', '2nd Year', 'BSIT', 'IT-201', 'College', 'Good Standing', 'Antonio Castillo', '09181234002'),
('02000000018', (SELECT user_id FROM users WHERE username = 'herrera.000018@sti.edu'), 'Rafael', 'Herrera', 'Cruz', '3rd Year', 'BSIT', 'IT-301', 'College', 'Good Standing', 'Gloria Herrera', '09181234003'),
('02000000019', (SELECT user_id FROM users WHERE username = 'jimenez.000019@sti.edu'), 'Gabriela', 'Jimenez', 'Torres', '4th Year', 'BSIT', 'IT-401', 'College', 'Good Standing', 'Alberto Jimenez', '09181234004'),
('02000000020', (SELECT user_id FROM users WHERE username = 'navarro.000020@sti.edu'), 'Daniel', 'Navarro', 'Mendoza', '1st Year', 'BSA', 'BA-101', 'College', 'Good Standing', 'Teresa Navarro', '09181234005'),
('02000000021', (SELECT user_id FROM users WHERE username = 'romero.000021@sti.edu'), 'Valentina', 'Romero', 'Garcia', '2nd Year', 'BSA', 'BA-201', 'College', 'Good Standing', 'Francisco Romero', '09181234006'),
('02000000022', (SELECT user_id FROM users WHERE username = 'vargas.000022@sti.edu'), 'Andres', 'Vargas', 'Lopez', '3rd Year', 'BSA', 'BA-301', 'College', 'On Watch', 'Carmen Vargas', '09181234007'),
('02000000023', (SELECT user_id FROM users WHERE username = 'flores.000023@sti.edu'), 'Camila', 'Flores', 'Diaz', '4th Year', 'BSA', 'BA-401', 'College', 'Good Standing', 'Eduardo Flores', '09181234008'),
('02000000024', (SELECT user_id FROM users WHERE username = 'martinez.000024@sti.edu'), 'Sebastian', 'Martinez', 'Ramos', '1st Year', 'BSCS', 'CS-101', 'College', 'Good Standing', 'Laura Martinez', '09181234009'),
('02000000025', (SELECT user_id FROM users WHERE username = 'gonzalez.000025@sti.edu'), 'Nicole', 'Gonzalez', 'Morales', '2nd Year', 'BSCS', 'CS-201', 'College', 'Good Standing', 'Jorge Gonzalez', '09181234010'),
('02000000026', (SELECT user_id FROM users WHERE username = 'lopez.000026@sti.edu'), 'Adrian', 'Lopez', 'Fernandez', '3rd Year', 'BSCS', 'CS-301', 'College', 'Good Standing', 'Silvia Lopez', '09181234011'),
('02000000027', (SELECT user_id FROM users WHERE username = 'perez.000027@sti.edu'), 'Bianca', 'Perez', 'Gutierrez', '4th Year', 'BSCS', 'CS-401', 'College', 'Good Standing', 'Ramon Perez', '09181234012'),
('02000000028', (SELECT user_id FROM users WHERE username = 'smith.000028@sti.edu'), 'James', 'Smith', 'Robert', '2nd Year', 'BSIT', 'IT-201', 'College', 'Good Standing', 'John Smith', '09181234013'),
('02000000029', (SELECT user_id FROM users WHERE username = 'brown.000029@sti.edu'), 'Sophia', 'Brown', 'Anne', '11', 'STEM', 'A', 'SHS', 'Good Standing', 'Robert Brown', '09181234014'),
('02000000030', (SELECT user_id FROM users WHERE username = 'wang.000030@sti.edu'), 'Michael', 'Wang', 'Chen', '3rd Year', 'BSCS', 'CS-301', 'College', 'Good Standing', 'Wei Wang', '09181234015');

-- ============================================
-- INSERT PROGRAM HEADS AND ADDITIONAL COLLEGE STUDENTS
-- ============================================
INSERT INTO users (username, password_hash, email, full_name, teacher_id, do_id, teacher_subrole, program, role, contact_number, is_active)
VALUES
('it.head@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'it.head@sti.edu', 'Alicia Rivera', '01000000006', NULL, 'department_head', 'Information Technology', 'teacher', '09170000006', 1),
('business.head@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business.head@sti.edu', 'Benjamin Santos', '01000000007', NULL, 'department_head', 'Business & Management', 'teacher', '09170000007', 1),
('hospitality.head@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'hospitality.head@sti.edu', 'Carla Mendoza', '01000000008', NULL, 'department_head', 'Hospitality Management', 'teacher', '09170000008', 1),
('tourism.head@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tourism.head@sti.edu', 'Daniel Reyes', '01000000009', NULL, 'department_head', 'Tourism Management', 'teacher', '09170000009', 1),
('engineering.head@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'engineering.head@sti.edu', 'Elena Cruz', '01000000010', NULL, 'department_head', 'Engineering', 'teacher', '09170000010', 1),
('arts.head@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'arts.head@sti.edu', 'Francis Lopez', '01000000011', NULL, 'department_head', 'Arts & Sciences', 'teacher', '09170000011', 1),
('cj.head@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cj.head@sti.edu', 'Grace Navarro', '01000000012', NULL, 'department_head', 'Criminal Justice Education', 'teacher', '09170000012', 1),
('navarro.000031@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'navarro.000031@sti.edu', 'Luna Navarro', NULL, NULL, NULL, NULL, 'student', '09191234016', 1),
('reyes.000032@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'reyes.000032@sti.edu', 'Marcus Reyes', NULL, NULL, NULL, NULL, 'student', '09191234017', 1),
('bautista.000033@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'bautista.000033@sti.edu', 'Sofia Bautista', NULL, NULL, NULL, NULL, 'student', '09191234018', 1),
('cruz.000034@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cruz.000034@sti.edu', 'Noah Cruz', NULL, NULL, NULL, NULL, 'student', '09191234019', 1),
('santos.000035@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'santos.000035@sti.edu', 'Ivy Santos', NULL, NULL, NULL, NULL, 'student', '09191234020', 1),
('garcia.000036@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'garcia.000036@sti.edu', 'Mikaela Garcia', NULL, NULL, NULL, NULL, 'student', '09191234021', 1),
('flores.000037@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'flores.000037@sti.edu', 'Adrian Flores', NULL, NULL, NULL, NULL, 'student', '09191234022', 1),
('diaz.000038@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'diaz.000038@sti.edu', 'Paula Diaz', NULL, NULL, NULL, NULL, 'student', '09191234023', 1),
('lim.000039@sti.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lim.000039@sti.edu', 'Jasper Lim', NULL, NULL, NULL, NULL, 'student', '09191234024', 1);

INSERT INTO students (student_id, user_id, first_name, last_name, middle_name, grade_year, track_course, section, student_type, status, guardian_name, guardian_contact)
VALUES
('02000000031', (SELECT user_id FROM users WHERE username = 'navarro.000031@sti.edu'), 'Luna', 'Navarro', 'Reyes', '1st Year', 'BSA', 'BSA-101', 'College', 'Good Standing', 'Teresa Navarro', '09191234016'),
('02000000032', (SELECT user_id FROM users WHERE username = 'reyes.000032@sti.edu'), 'Marcus', 'Reyes', 'Garcia', '2nd Year', 'BSMA', 'BSMA-201', 'College', 'Good Standing', 'Francisco Reyes', '09191234017'),
('02000000033', (SELECT user_id FROM users WHERE username = 'bautista.000033@sti.edu'), 'Sofia', 'Bautista', 'Cruz', '1st Year', 'BSHM', 'BSHM-101', 'College', 'Good Standing', 'Ramon Bautista', '09191234018'),
('02000000034', (SELECT user_id FROM users WHERE username = 'cruz.000034@sti.edu'), 'Noah', 'Cruz', 'Santos', '2nd Year', 'BSTM', 'BSTM-201', 'College', 'Good Standing', 'Luz Cruz', '09191234019'),
('02000000035', (SELECT user_id FROM users WHERE username = 'santos.000035@sti.edu'), 'Ivy', 'Santos', 'Mendoza', '1st Year', 'BSCpE', 'BSCPE-101', 'College', 'Good Standing', 'Carlo Santos', '09191234020'),
('02000000036', (SELECT user_id FROM users WHERE username = 'garcia.000036@sti.edu'), 'Mikaela', 'Garcia', 'Flores', '1st Year', 'BACOMM', 'BACOMM-101', 'College', 'Good Standing', 'Angela Garcia', '09191234021'),
('02000000037', (SELECT user_id FROM users WHERE username = 'flores.000037@sti.edu'), 'Adrian', 'Flores', 'Lim', '2nd Year', 'BMMA', 'BMMA-201', 'College', 'Good Standing', 'Eduardo Flores', '09191234022'),
('02000000038', (SELECT user_id FROM users WHERE username = 'diaz.000038@sti.edu'), 'Paula', 'Diaz', 'Morales', '1st Year', 'BAPsych', 'BAPSYCH-101', 'College', 'Good Standing', 'Carmen Diaz', '09191234023'),
('02000000039', (SELECT user_id FROM users WHERE username = 'lim.000039@sti.edu'), 'Jasper', 'Lim', 'Torres', '1st Year', 'BSCRIM', 'BSCRIM-101', 'College', 'Good Standing', 'Henry Lim', '09191234024');

-- ============================================
-- INSERT OFFENSE TYPES (Based on STI Handbook)
-- ============================================
INSERT INTO offense_types (offense_name, category, description) VALUES
('Inappropriate Campus Attire', 'Minor', 'Wearing inappropriate clothes on wash days'),
('Non-wearing of School Uniform', 'Minor', 'Not wearing uniform or improper use of ID'),
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

-- ============================================
-- INSERT SANCTIONS (Based on STI Handbook)
-- ============================================
INSERT INTO sanctions (sanction_name, severity_level, description) VALUES
('Verbal/Oral Warning', 1, 'Verbal warning for first minor offense'),
('Written Apology', 1, 'Required to write apology letter'),
('Written Reprimand', 2, 'Formal written notice of violation'),
('Corrective Reinforcement (3-7 days)', 2, 'Attend classes + after-school tasks for 3 to 7 days'),
('Conference with Discipline Committee', 2, 'Meeting with parents/guardians required'),
('Suspension from Class (3-10 days)', 3, 'Cannot attend classes for 3 to 10 days'),
('Preventive Suspension', 3, 'Suspended during investigation period'),
('Non-readmission', 4, 'Denied enrollment for next term'),
('Exclusion', 5, 'Immediately removed from school'),
('Expulsion', 5, 'Disqualified from all Philippine institutions');

-- ============================================
-- INSERT SAMPLE CASES (30 Total - All 2026 Cases)
-- 10 Pending, 10 On Going, 10 Resolved
-- ============================================
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

-- Minor cases use recording status instead of the major-case workflow statuses.
UPDATE cases
SET status = CASE WHEN status = 'Pending' THEN 'Unrecorded' ELSE 'Recorded' END
WHERE severity = 'Minor';
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
    SELECT student_id
    FROM (SELECT DISTINCT student_id FROM cases) AS temp
);

-- ============================================
-- INSERT CASE HISTORY FOR ALL CASES
-- ============================================
INSERT INTO case_history (case_id, changed_by, action, new_value, notes, timestamp)
SELECT 
    case_id,
    2,
    'Created',
    CONCAT('Status: ', status),
    'Case created and logged into system',
    TIMESTAMP(date_reported, COALESCE(time_reported, '08:00:00')) + INTERVAL 5 MINUTE
FROM cases;

-- ============================================
-- INSERT SAMPLE LOST & FOUND ITEMS
-- ============================================
INSERT INTO lost_found_items (item_id, item_name, category, found_location, date_found, status, description)
VALUES 
('LF-1001', 'Backpack', 'Bags', 'Cafeteria', '2023-10-14', 'Unclaimed', 'Blue JanSport backpack with laptop'),
('LF-1002', 'Water Bottle', 'Accessories', 'Gym', '2023-10-13', 'Unclaimed', 'Stainless steel water bottle 500ml'),
('LF-1003', 'Textbook', 'Books', 'Library', '2023-10-12', 'Claimed', 'Grade 11 Math textbook'),
('LF-1004', 'Calculator', 'Electronics', 'Room C401', '2023-10-08', 'Claimed', 'Scientific calculator Casio fx-991'),
('LF-1005', 'Mobile Phone', 'Electronics', 'Canteen', '2026-02-18', 'Unclaimed', 'iPhone 12 with black case'),
('LF-1006', 'Wallet', 'Personal Items', 'Boys Restroom', '2026-02-17', 'Claimed', 'Brown leather wallet with ID card inside'),
('LF-1007', 'Jacket', 'Clothing', 'Gym', '2026-02-16', 'Unclaimed', 'Black and red Nike windbreaker size M'),
('LF-1008', 'Headphones', 'Electronics', 'Audio Lab', '2026-02-15', 'Unclaimed', 'Sony WH-CH720N wireless headphones, black'),
('LF-1009', 'Keys', 'Personal Items', 'Parking Lot', '2026-02-14', 'Unclaimed', 'Set of 3 keys with blue keychain'),
('LF-1010', 'Pen Drive', 'Electronics', 'Computer Lab 2', '2026-02-13', 'Unclaimed', '64GB Kingston DataTraveler pen drive'),
('LF-1011', 'Scarf', 'Clothing', 'Building A Hallway', '2026-02-12', 'Claimed', 'Maroon wool scarf with STI logo'),
('LF-1012', 'Notebook', 'Books', 'Student Lounge', '2026-02-11', 'Unclaimed', 'Spiral-bound notebook with name "Maria" written inside'),
('LF-1013', 'Watch', 'Accessories', 'Cafeteria', '2026-02-10', 'Claimed', 'Casio digital watch with blue band'),
('LF-1014', 'School ID', 'Personal Items', 'Main Gate', '2026-02-09', 'Unclaimed', 'STI School ID - Student ID: 02000000015'),
('LF-1015', 'Hand Sanitizer', 'Accessories', 'Classroom Building B', '2026-02-08', 'Unclaimed', 'Pump bottle 250ml, lavender scent'),
('LF-1016', 'USB Cable', 'Electronics', 'Library - Research Area', '2026-02-07', 'Unclaimed', 'Type-C charging cable 2 meters'),
('LF-1017', 'Lunch Box', 'Bags', 'Cafeteria', '2026-02-06', 'Claimed', 'Stainless steel lunch container with handle'),
('LF-1018', 'Baseball Cap', 'Clothing', 'Sports Complex', '2026-02-05', 'Unclaimed', 'Red and white STI Esports tournament cap'),
('LF-1019', 'Earbuds', 'Electronics', 'Classroom C-305', '2026-02-04', 'Unclaimed', 'Apple AirPods with charging case'),
('LF-1020', 'Sports Medal', 'Personal Items', 'Gym', '2026-02-03', 'Unclaimed', 'Gold medal from 2026 Sports Festival');

-- ============================================
-- INSERT WATCH LIST ENTRIES
-- ============================================
INSERT INTO watch_list (student_id, reason, added_by, added_date, notes)
VALUES 
('02000000022', 'Multiple major offenses: Cheating and other violations. Requires close monitoring.', 2, '2026-02-01', 
 'Student requires close monitoring. Consider probation if another offense occurs.'),
('02000000008', 'Major offense: Smoking on campus. Multiple previous violations. On watch list.', 2, '2026-02-05', 
 'Requires behavioral intervention. Parent involvement necessary.');

-- ============================================
-- INSERT SAMPLE SANCTIONS APPLIED
-- ============================================
INSERT INTO case_sanctions (case_id, sanction_id, applied_date, is_completed, completion_date, notes)
VALUES
-- 2026 ON GOING CASES (Incomplete sanctions)
('C-2026011', 1, '2026-01-10', 0, NULL, 'Written warning issued. Counseling in progress'),
('C-2026012', 1, '2026-01-14', 0, NULL, 'Written warning issued. Behavioral intervention ongoing'),
('C-2026013', 4, '2026-01-20', 0, NULL, 'Corrective reinforcement pending. Academic dishonesty review ongoing'),
('C-2026014', 2, '2026-01-28', 0, NULL, 'Community service and restitution plan being prepared'),
('C-2026015', 1, '2026-02-02', 0, NULL, 'Written warning issued. Monitoring compliance'),
('C-2026016', 1, '2026-02-06', 0, NULL, 'Counseling and mediation being scheduled'),
('C-2026017', 1, '2026-02-10', 0, NULL, 'Written warning issued. Educational discussion pending'),
('C-2026018', 4, '2026-02-15', 0, NULL, 'Academic integrity investigation ongoing. Sanction pending'),
('C-2026019', 3, '2026-02-17', 0, NULL, 'Restitution for equipment damage pending'),
('C-2026020', 6, '2026-02-20', 0, NULL, 'Investigation ongoing. Suspension decision pending'),
-- 2026 RESOLVED CASES (Completed sanctions)
('C-2026021', 1, '2026-01-08', 1, '2026-01-08', 'Student acknowledged warning and committed to improvement'),
('C-2026022', 3, '2026-01-12', 1, '2026-01-12', 'Written reprimand issued and filed'),
('C-2026023', 1, '2026-01-16', 1, '2026-01-16', 'Verbal warning issued and documented'),
('C-2026024', 1, '2026-01-24', 1, '2026-01-25', 'Counseling completed with both students'),
('C-2026025', 2, '2026-01-30', 1, '2026-01-30', 'Values education session completed'),
('C-2026026', 1, '2026-02-03', 1, '2026-02-03', 'Safety rules explained and acknowledged'),
('C-2026027', 3, '2026-02-07', 1, '2026-02-07', 'Lab safety protocols reviewed'),
('C-2026028', 1, '2026-02-11', 1, '2026-02-11', 'Verbal warning issued. Minor violation corrected'),
('C-2026029', 1, '2026-02-16', 1, '2026-02-16', 'Student counseled. Committed to classroom attentiveness'),
('C-2026030', 1, '2026-02-19', 1, '2026-02-19', 'Library rules reviewed with student'),
-- 2025 Case Sanctions (All Resolved)
('C-2025001', 1, '2025-03-15', 1, '2025-03-15', 'Verbal warning issued. Student committed to punctuality'),
('C-2025002', 3, '2025-04-20', 1, '2025-04-20', 'Written reprimand issued and filed'),
('C-2025003', 3, '2025-05-10', 1, '2025-05-12', 'Written warning issued after conference'),
('C-2025004', 4, '2025-06-14', 1, '2025-06-20', '3-day corrective reinforcement completed'),
('C-2025005', 3, '2025-07-01', 1, '2025-07-01', 'Written warning issued for third ID violation'),
('C-2025006', 6, '2025-08-10', 1, '2025-08-17', '7-day suspension completed. Major offense documented'),
('C-2025007', 1, '2025-09-20', 1, '2025-09-20', 'Counseling session completed with both students'),
('C-2025008', 2, '2025-10-02', 1, '2025-10-15', 'Community service and restitution completed'),
('C-2025009', 1, '2025-11-15', 1, '2025-11-15', 'Educational discussion conducted about campus policies'),
('C-2025010', 6, '2025-12-01', 1, '2025-12-10', '5-day suspension completed. Mediation successful');

-- ============================================
-- INSERT SAMPLE NOTIFICATIONS
-- ============================================
INSERT INTO notifications (user_id, title, message, type, related_id, is_read)
VALUES
(2, 'New Case Reported', 'New cyberbullying case C-2026008 requires immediate attention', 'case_update', 'C-2026008', 0),
(2, 'Major Violation', 'Case C-2026007 (Vaping on Campus) requires decision on sanctions', 'case_update', 'C-2026007', 0),
(2, 'Active Investigation', 'Case C-2026005 (Cheating) is currently under investigation', 'case_update', 'C-2026005', 1),
(2, 'Pending Action', 'Case C-2026010 (ID Violation) awaits disciplinary action', 'case_update', 'C-2026010', 0);

-- ============================================
-- FINAL VERIFICATION & SUMMARY
-- ============================================

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
SELECT 
    track_course AS 'Track/Course',
    student_type AS 'Type',
    COUNT(*) AS 'Count'
FROM students
GROUP BY track_course, student_type
ORDER BY student_type, COUNT(*) DESC;
SELECT 
    case_id AS 'Case ID',
    student_id AS 'Student ID',
    case_type AS 'Offense Type',
    severity AS 'Severity',
    status AS 'Status',
    date_reported AS 'Date'
FROM cases
ORDER BY date_reported DESC;
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
SELECT 
    s.student_id AS 'Student ID',
    CONCAT(s.first_name, ' ', s.last_name) AS 'Student Name',
    s.track_course AS 'Track/Course',
    s.total_offenses AS 'Total',
    s.major_offenses AS 'Major',
    s.minor_offenses AS 'Minor',
    s.status AS 'Status'
FROM students s
WHERE s.total_offenses > 0
ORDER BY s.total_offenses DESC, s.major_offenses DESC;


