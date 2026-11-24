-- ============================================
-- ADD STUDENT: Gerald Stephen Diel (02000249313)
-- BSCS 4th Year | 2 Major (Category A) + 2 Minor offenses
-- Email format: lastname.<last 6 digits of student ID>@sti.edu
-- Default password: 'password'
-- Case IDs 2026-1T-0280..0283 continue after the last beta case (0279).
-- ============================================

-- 1. User account
INSERT INTO users (username, password_hash, email, full_name, role_id, role, contact_number, is_active)
VALUES (
    'diel.249313@sti.edu',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'diel.249313@sti.edu',
    'Gerald Stephen Diel',
    '02000249313',
    'student',
    '09191234025',
    1
);

-- 2. Student profile
INSERT INTO students (student_id, user_id, first_name, last_name, middle_name, grade_year, track_course, section, student_type, status, guardian_name, guardian_contact)
VALUES (
    '02000249313',
    (SELECT user_id FROM users WHERE username = 'diel.249313@sti.edu'),
    'Gerald', 'Diel', 'Stephen',
    '4th Year', 'BSCS', 'CS-401', 'College',
    'Good Standing',
    'Guardian of Gerald Diel', '09191234025'   -- placeholder: replace with real guardian info
);

-- 3. Cases (offense_id: 6 = Gambling, 10 = PDA, 16 = Cheating, 13 = Smoking/Vaping)
INSERT INTO cases (case_id, student_id, offense_id, case_type, severity, offense_category, status, date_reported, time_reported, location, reported_by, assigned_to, description, witnesses, action_taken, notes, resolved_date)
VALUES
('2026-1T-0280', '02000249313', 6, 'Gambling', 'Minor', NULL, 'Recorded', '2026-10-05', '12:20:00', 'Student Lounge', 3, 2,
 'Student was seen playing a card game for money with other students during lunch break.',
 'Teacher on duty and classmates', 'Verbal warning issued and cards confiscated', 'First minor offense for this student.', '2026-10-05'),

('2026-1T-0281', '02000249313', 10, 'Public Display of Affection', 'Minor', NULL, 'Recorded', '2026-10-19', '15:40:00', 'Campus Garden', 3, 2,
 'Student was observed in prolonged inappropriate public display of affection in a common area.',
 'Teacher on duty', 'Verbal warning and counseling conducted', 'Second minor offense for this student.', '2026-10-19'),

('2026-1T-0282', '02000249313', 16, 'Cheating', 'Major', 'Category A', 'Pending', '2026-11-03', '14:00:00', 'Room A-405', 3, 2,
 'Student was caught using unauthorized notes during a Computer Science midterm exam.',
 'Exam proctor and nearby students', NULL, 'Evidence collected. Investigation pending.', NULL),

('2026-1T-0283', '02000249313', 13, 'Smoking/Vaping on Campus', 'Major', 'Category A', 'Pending', '2026-11-12', '16:10:00', 'Parking Lot Area', 4, 2,
 'Student was observed vaping in the campus parking area after classes.',
 'Security personnel', NULL, 'Vape device confiscated. Awaiting disciplinary decision.', NULL);

-- 4. Sanctions for the minor cases (Verbal/Oral Warning, sanction_id = 1)
INSERT INTO case_sanctions (case_id, sanction_id, applied_date, is_completed, completion_date, notes)
VALUES
('2026-1T-0280', 1, '2026-10-05', 1, '2026-10-05', 'Verbal warning issued and documented'),
('2026-1T-0281', 1, '2026-10-19', 1, '2026-10-19', 'Verbal warning and counseling completed');

-- 5. Update offense counts
UPDATE students
SET total_offenses = (SELECT COUNT(*) FROM cases WHERE student_id = '02000249313' AND is_archived = 0),
    major_offenses = (SELECT COUNT(*) FROM cases WHERE student_id = '02000249313' AND severity = 'Major' AND is_archived = 0),
    minor_offenses = (SELECT COUNT(*) FROM cases WHERE student_id = '02000249313' AND severity = 'Minor' AND is_archived = 0),
    last_incident_date = (SELECT MAX(date_reported) FROM cases WHERE student_id = '02000249313')
WHERE student_id = '02000249313';

-- 6. Case history
INSERT INTO case_history (case_id, changed_by, action, new_value, notes, timestamp)
SELECT case_id, 2, 'Created', CONCAT('Status: ', status),
       'Case created and logged into system',
       TIMESTAMP(date_reported, COALESCE(time_reported, '08:00:00')) + INTERVAL 5 MINUTE
FROM cases
WHERE case_id IN ('2026-1T-0280', '2026-1T-0281', '2026-1T-0282', '2026-1T-0283');