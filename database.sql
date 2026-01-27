CREATE DATABASE IF NOT EXISTS physio_clinic CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE physio_clinic;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('admin_doctor','sub_doctor','receptionist','patient') NOT NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(180) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    security_question VARCHAR(255) NOT NULL,
    security_answer_hash VARCHAR(255) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    can_view_reports TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    first_name VARCHAR(80) NOT NULL,
    last_name VARCHAR(80) NOT NULL,
    age INT NULL,
    gender VARCHAR(20),
    dob DATE NULL,
    occupation VARCHAR(120),
    assessment_date DATE NULL,
    dominance VARCHAR(50),
    condition_duration VARCHAR(100),
    phone VARCHAR(40),
    address VARCHAR(255),
    emergency_contact VARCHAR(120),
    chief_complain TEXT,
    history_present_illness TEXT,
    past_medical_history TEXT,
    surgical_history TEXT,
    family_history TEXT,
    socio_economic_status TEXT,
    observation_built TEXT,
    observation_attitude_limb TEXT,
    observation_posture TEXT,
    observation_deformity TEXT,
    aids_applications TEXT,
    gait TEXT,
    palpation_tenderness TEXT,
    palpation_oedema VARCHAR(50),
    palpation_warmth TEXT,
    palpation_crepitus TEXT,
    examination_rom TEXT,
    muscle_power TEXT,
    muscle_bulk TEXT,
    ligament_instability TEXT,
    pain_type TEXT,
    pain_site TEXT,
    pain_nature TEXT,
    pain_aggravating_factor TEXT,
    pain_relieving_factor TEXT,
    pain_measurement TINYINT NULL,
    gait_assessment TEXT,
    diagnosis TEXT,
    treatment_goals TEXT,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE treatment_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    total_sessions INT NOT NULL,
    start_date DATE,
    status VARCHAR(30) DEFAULT 'active',
    notes TEXT,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

CREATE TABLE sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    treatment_plan_id INT NOT NULL,
    session_date DATE NOT NULL,
    attendance ENUM('attended','missed','cancelled') DEFAULT 'attended',
    notes TEXT,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (treatment_plan_id) REFERENCES treatment_plans(id) ON DELETE CASCADE
);

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    method VARCHAR(50),
    notes TEXT,
    receipt_no VARCHAR(50),
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

CREATE TABLE patient_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_by INT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

CREATE TABLE patient_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    sub_doctor_id INT NOT NULL,
    assigned_by INT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (sub_doctor_id) REFERENCES users(id) ON DELETE CASCADE
);
