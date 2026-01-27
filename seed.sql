USE silverwebbuzz_in_pcm;

-- Sample users (passwords: Admin@123, Sub@123, Recep@123, Patient@123)
INSERT INTO users (id, role, name, email, password_hash, security_question, security_answer_hash, active, can_view_reports) VALUES
    (1, 'admin_doctor', 'Dr. Admin', 'admin@pcm.local', '$2y$10$pTb2OfKYMgbpKkxz2cHob.b7RhjLi4H.0TW7YXJaNuzrBiyYGhGge', 'Favorite color?', '$2y$10$R6iGwcDkKHNKN6YpD51Zy.YFZCtIwDphopO41/Lcl6G/gpqGRfEKu', 1, 1),
    (2, 'sub_doctor', 'Dr. Sub', 'subdoctor@pcm.local', '$2y$10$vYoql2.7rpRX8aeQkGwcPOulSgpUAOS1f9B1QHVCB0n4.OsHpeqde', 'Favorite color?', '$2y$10$R6iGwcDkKHNKN6YpD51Zy.YFZCtIwDphopO41/Lcl6G/gpqGRfEKu', 1, 0),
    (3, 'receptionist', 'Reception Desk', 'reception@pcm.local', '$2y$10$/Us3a7FgA2.CWA1L3mBD9uUEpz7mfQiYDX.9o9yKF/C7r.H.g15YS', 'Favorite color?', '$2y$10$R6iGwcDkKHNKN6YpD51Zy.YFZCtIwDphopO41/Lcl6G/gpqGRfEKu', 1, 0),
    (4, 'patient', 'John Patient', 'patient@pcm.local', '$2y$10$gyx8Cb/Dvuap.rEx9TJzHeCxTVLMQlLX9Ig6F/mAjjnNLIkOPGSpy', 'Favorite color?', '$2y$10$R6iGwcDkKHNKN6YpD51Zy.YFZCtIwDphopO41/Lcl6G/gpqGRfEKu', 1, 0);

-- Sample patients
INSERT INTO patients (
    id, user_id, first_name, last_name, age, gender, dob, occupation, assessment_date, dominance, condition_duration,
    phone, address, emergency_contact, chief_complain, history_present_illness, past_medical_history, surgical_history,
    family_history, socio_economic_status, observation_built, observation_attitude_limb, observation_posture,
    observation_deformity, aids_applications, gait, palpation_tenderness, palpation_oedema, palpation_warmth,
    palpation_crepitus, examination_rom, muscle_power, muscle_bulk, ligament_instability, pain_type, pain_site,
    pain_nature, pain_aggravating_factor, pain_relieving_factor, pain_measurement, gait_assessment, diagnosis,
    treatment_goals, created_by
) VALUES
    (
        1, 4, 'John', 'Patient', 32, 'Male', '1993-05-12', 'Software Engineer', '2026-01-25', 'Right', '3 weeks',
        '9990001111', '12 Green St', 'Jane Patient - 9990002222',
        'Low back pain after lifting',
        'Pain began after lifting heavy box; worse with bending.',
        'No major illnesses', 'None', 'Father with back pain', 'Middle',
        'Athletic', 'Slight guarding', 'Mild anterior tilt',
        'No visible deformity', 'Lumbar belt', 'Antalgic gait',
        'L4-L5 tenderness', 'pitting', 'Mild warmth',
        'None', 'Lumbar flexion limited to 60 degrees', '4/5', 'Normal', 'Negative',
        'Mechanical', 'Lower back', 'Dull ache', 'Prolonged sitting', 'Rest and heat', 6,
        'Short stride length', 'Lumbar strain', 'Reduce pain, restore ROM', 1
    ),
    (
        2, NULL, 'Mary', 'Smith', 45, 'Female', '1980-09-10', 'Teacher', '2026-01-20', 'Right', '2 months',
        '9990003333', '88 Lake Rd', 'Tom Smith - 9990004444',
        'Knee pain while walking',
        'Gradual onset of knee pain with stairs.',
        'Hypertension', 'Appendectomy', 'Mother with OA', 'Middle',
        'Average', 'Normal', 'Upright',
        'Mild valgus', 'Knee brace', 'Slow gait',
        'Medial joint line', 'non_pitting', 'Warmth present',
        'Crepitus felt', 'Knee flexion 0-110', '4+/5', 'Mild quad wasting', 'Mild',
        'Degenerative', 'Right knee', 'Aching', 'Stairs', 'Ice and rest', 7,
        'Guarded gait', 'Knee OA', 'Pain relief and strengthen quads', 1
    );

-- Assign patient 1 to sub-doctor
INSERT INTO patient_assignments (id, patient_id, sub_doctor_id, assigned_by) VALUES
    (1, 1, 2, 1);

-- Treatment plan for patient 1
INSERT INTO treatment_plans (id, patient_id, total_sessions, start_date, status, notes, created_by) VALUES
    (1, 1, 10, '2026-01-25', 'active', 'Focus on lumbar mobility and core strength', 1);

-- Sessions for patient 1
INSERT INTO sessions (id, patient_id, treatment_plan_id, session_date, attendance, notes, created_by) VALUES
    (1, 1, 1, '2026-01-25', 'attended', 'Initial assessment and gentle mobility', 1),
    (2, 1, 1, '2026-01-27', 'attended', 'Core activation and posture education', 2);

-- Payments for patient 1
INSERT INTO payments (id, patient_id, amount, payment_date, method, notes, receipt_no, created_by) VALUES
    (1, 1, 500.00, '2026-01-25', 'Cash', 'Advance payment', 'RCPT-TEST01', 3),
    (2, 1, 300.00, '2026-01-27', 'UPI', 'Second session', 'RCPT-TEST02', 3);
