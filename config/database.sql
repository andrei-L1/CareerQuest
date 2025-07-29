-- Create the database
CREATE DATABASE IF NOT EXISTS career_platform;
USE career_platform;

-- Table: role
CREATE TABLE IF NOT EXISTS role (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_title VARCHAR(255) NOT NULL,
    role_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

-- Table: course
CREATE TABLE IF NOT EXISTS course (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    course_title VARCHAR(255) NOT NULL,
    course_description TEXT,
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: student
CREATE TABLE IF NOT EXISTS student (
    stud_id INT AUTO_INCREMENT PRIMARY KEY,
    -- < currently deleted > stud_no VARCHAR(255) UNIQUE NOT NULL,
    stud_first_name VARCHAR(255),
    stud_middle_name VARCHAR(255),
    stud_last_name VARCHAR(255),
    stud_gender ENUM('Male', 'Female', 'Other'),
    stud_date_of_birth DATE,
    graduation_yr YEAR,
    course_id INT,
    bio TEXT,
    resume_file VARCHAR(255),
    profile_picture VARCHAR(255),
    stud_email VARCHAR(255) UNIQUE NOT NULL,
    stud_password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    status VARCHAR(50),
    institution VARCHAR(255),
    is_student BOOLEAN NOT NULL DEFAULT FALSE,
    edu_background ENUM('College Student', 'Graduate Student', 'Not a Student', 'Professional') DEFAULT 'College Student',
    FOREIGN KEY (course_id) REFERENCES course(course_id)
);

-- Table: user
CREATE TABLE IF NOT EXISTS user (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    user_first_name VARCHAR(255) NOT NULL,
    user_middle_name VARCHAR(255),
    user_last_name VARCHAR(255),
    user_email VARCHAR(255) UNIQUE NOT NULL,
    user_password VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    user_type VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    picture_file VARCHAR(255),
    status VARCHAR(50),
    FOREIGN KEY (role_id) REFERENCES role(role_id)
);


-- Table: employer
CREATE TABLE IF NOT EXISTS employer (
    employer_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    company_name VARCHAR(255),
    job_title VARCHAR(255),
    company_logo VARCHAR(255),  -- Added for branding
    status ENUM('Verification', 'Active', 'Suspended', 'Banned') DEFAULT 'Verification',
    company_website VARCHAR(255),
    contact_number VARCHAR(50),
    company_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    document_url VARCHAR(255) NULL COMMENT 'Uploaded verification document',
    FOREIGN KEY (user_id) REFERENCES user(user_id)
);

-- Table: professional
CREATE TABLE IF NOT EXISTS professional (
    professional_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    current_job VARCHAR(255),
    company VARCHAR(255),
    yrs_of_experience INT,
    status ENUM('Active', 'Suspended', 'Banned') DEFAULT 'Active', -- Added status column
    bio TEXT,
    resume_file VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES user(user_id)
);

-- Table: skill_masterlist
CREATE TABLE IF NOT EXISTS skill_masterlist (
    skill_id INT AUTO_INCREMENT PRIMARY KEY,
    skill_name VARCHAR(255) NOT NULL,
    category VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

-- Table: job_type
CREATE TABLE IF NOT EXISTS job_type (
    job_type_id INT AUTO_INCREMENT PRIMARY KEY,
    job_type_title VARCHAR(255) NOT NULL,
    job_type_description TEXT
);

-- Table: job_posting
CREATE TABLE IF NOT EXISTS job_posting (
    job_id INT AUTO_INCREMENT PRIMARY KEY,
    employer_id INT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    location VARCHAR(255),
    job_type_id INT,
    img_url VARCHAR(255),
    posted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL,
    moderation_status ENUM('Pending', 'Approved', 'Rejected', 'Paused') DEFAULT 'Pending',
    flagged BOOLEAN DEFAULT FALSE,
    min_salary DECIMAL(10,2) NULL COMMENT 'Minimum salary for the job, NULL if undisclosed',
    max_salary DECIMAL(10,2) NULL COMMENT 'Maximum salary for the job, NULL if undisclosed',
    salary_type ENUM('Hourly', 'Weekly', 'Monthly', 'Yearly', 'Commission', 'Negotiable') NOT NULL DEFAULT 'Yearly' COMMENT 'Type or period of compensation',
    salary_disclosure BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Whether salary is publicly disclosed',
    FOREIGN KEY (employer_id) REFERENCES employer(employer_id),
    FOREIGN KEY (job_type_id) REFERENCES job_type(job_type_id),
    CONSTRAINT check_salary_range CHECK (max_salary >= min_salary OR max_salary IS NULL OR min_salary IS NULL)
);

-- Add triggers
DELIMITER //
CREATE TRIGGER job_posting_salary_validation
BEFORE INSERT ON job_posting
FOR EACH ROW
BEGIN
    IF NEW.salary_disclosure = FALSE THEN
        SET NEW.min_salary = NULL, NEW.max_salary = NULL;
    END IF;
END //
DELIMITER ;

DELIMITER //
CREATE TRIGGER job_posting_salary_validation_update
BEFORE UPDATE ON job_posting
FOR EACH ROW
BEGIN
    IF NEW.salary_disclosure = FALSE THEN
        SET NEW.min_salary = NULL, NEW.max_salary = NULL;
    END IF;
END //
DELIMITER ;


-- Table: job_skill
CREATE TABLE IF NOT EXISTS job_skill (
    job_skills_id INT AUTO_INCREMENT PRIMARY KEY,
    skill_id INT,
    job_id INT,
    importance ENUM('Low', 'Medium', 'High'),
    group_no INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (skill_id) REFERENCES skill_masterlist(skill_id),
    FOREIGN KEY (job_id) REFERENCES job_posting(job_id)
);

-- Table: stud_skill
CREATE TABLE IF NOT EXISTS stud_skill (
    user_skills_id INT AUTO_INCREMENT PRIMARY KEY,
    stud_id INT,
    skill_id INT,
    proficiency ENUM('Beginner', 'Intermediate', 'Advanced'),
    group_no INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (stud_id) REFERENCES student(stud_id),
    FOREIGN KEY (skill_id) REFERENCES skill_masterlist(skill_id)
);

-- Table: application_tracking
CREATE TABLE IF NOT EXISTS application_tracking (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    stud_id INT,
    job_id INT,
    application_status ENUM(
        'Pending',
        'Under Review',
        'Interview Scheduled',
        'Interview',
        'Offered',
        'Accepted',
        'Rejected',
        'Withdrawn'
    ),
    parent_application_id INT,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (stud_id) REFERENCES student(stud_id),
    FOREIGN KEY (job_id) REFERENCES job_posting(job_id)
);


-- Table: skill_matching
CREATE TABLE IF NOT EXISTS skill_matching (
    match_id INT AUTO_INCREMENT PRIMARY KEY,
    user_skills_id INT,
    job_skills_id INT,
    match_score DECIMAL(5, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_skills_id) REFERENCES stud_skill(user_skills_id),
    FOREIGN KEY (job_skills_id) REFERENCES job_skill(job_skills_id)
);

-- Table: actor
CREATE TABLE IF NOT EXISTS actor (
    actor_id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type ENUM('user', 'student') NOT NULL,
    entity_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT unique_actor UNIQUE (entity_type, entity_id);
);

-- Table: forum
CREATE TABLE IF NOT EXISTS forum (
    forum_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    is_private BOOLEAN DEFAULT FALSE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (created_by) REFERENCES actor(actor_id)
);

-- Table: forum_post
CREATE TABLE IF NOT EXISTS forum_post (
    post_id INT AUTO_INCREMENT PRIMARY KEY,
    forum_id INT,
    post_title VARCHAR(255),
    poster_id INT,
    is_pinned BOOLEAN DEFAULT FALSE,
    content TEXT,
    view_count INT DEFAULT 0,
    up_count INT DEFAULT 0,
    posted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    is_announcement BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (forum_id) REFERENCES forum(forum_id),
    FOREIGN KEY (poster_id) REFERENCES actor(actor_id)
);
--CREATE INDEX idx_forum_post_is_announcement ON forum_post(is_announcement);
-- Table: forum_comment
CREATE TABLE IF NOT EXISTS forum_comment (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT,
    commenter_id INT,
    content TEXT,
    commented_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    up_count INT DEFAULT 0,
    parent_comment_id INT DEFAULT NULL, 
    FOREIGN KEY (post_id) REFERENCES forum_post(post_id),
    FOREIGN KEY (commenter_id) REFERENCES actor(actor_id),
    FOREIGN KEY (parent_comment_id) REFERENCES forum_comment(comment_id) -- 🆕 Self-reference for replies
);

-- Table: notification
CREATE TABLE IF NOT EXISTS notification (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    actor_id INT,
    message TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    notification_type VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    action_url VARCHAR(255),
    reference_type VARCHAR(255),
    reference_id INT,
    FOREIGN KEY (actor_id) REFERENCES actor(actor_id)
);

-- Table: thread
CREATE TABLE IF NOT EXISTS thread (
    thread_id INT AUTO_INCREMENT PRIMARY KEY,
    thread_group VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

-- Table: message
CREATE TABLE IF NOT EXISTS message (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    content TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    sender_id INT,
    receiver_id INT,
    thread_id INT,
    FOREIGN KEY (sender_id) REFERENCES actor(actor_id),
    FOREIGN KEY (receiver_id) REFERENCES actor(actor_id),
    FOREIGN KEY (thread_id) REFERENCES thread(thread_id)
);



CREATE TABLE IF NOT EXISTS thread_participants (
    participant_id INT AUTO_INCREMENT PRIMARY KEY,
    thread_id INT NOT NULL,
    actor_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (thread_id) REFERENCES thread(thread_id),
    FOREIGN KEY (actor_id) REFERENCES actor(actor_id),
    UNIQUE KEY (thread_id, actor_id)  -- Prevents duplicate participants in same thread
);


CREATE TABLE IF NOT EXISTS saved_jobs (
    saved_id INT AUTO_INCREMENT PRIMARY KEY,
    stud_id INT NOT NULL,
    job_id INT NOT NULL,
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_saved_jobs_student FOREIGN KEY (stud_id) REFERENCES student(stud_id) ON DELETE CASCADE,
    CONSTRAINT fk_saved_jobs_job FOREIGN KEY (job_id) REFERENCES job_posting(job_id) ON DELETE CASCADE,
    UNIQUE (stud_id, job_id) -- Prevents duplicate saves
);



CREATE TABLE IF NOT EXISTS report (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    content_type ENUM('post', 'comment', 'user') NOT NULL,
    content_id INT NOT NULL,
    reported_by INT NOT NULL,
    reason TEXT,
    status ENUM('pending', 'resolved') DEFAULT 'pending',
    resolution ENUM('approved', 'edited', 'deleted') DEFAULT NULL,
    resolved_at TIMESTAMP NULL,
    reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    moderator_id INT,
    FOREIGN KEY (reported_by) REFERENCES actor(actor_id),
    FOREIGN KEY (moderator_id) REFERENCES actor(actor_id)
);

CREATE TABLE IF NOT EXISTS interviews (
    interview_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    interview_date DATETIME NOT NULL,
    interview_mode ENUM('In-person', 'Phone', 'Video') NOT NULL,
    location_details VARCHAR(255) NOT NULL,
    additional_notes TEXT,
    scheduled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Scheduled', 'Completed', 'Cancelled', 'Rescheduled') DEFAULT 'Scheduled',
    FOREIGN KEY (application_id) REFERENCES application_tracking(application_id)
);


CREATE TABLE IF NOT EXISTS forum_membership (
    membership_id INT AUTO_INCREMENT PRIMARY KEY,
    forum_id INT NOT NULL,
    actor_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    role ENUM('Member', 'Moderator', 'Admin') DEFAULT 'Member',
    status ENUM('Pending', 'Active', 'Banned', 'Left') DEFAULT 'Pending',
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (forum_id) REFERENCES forum(forum_id) ON DELETE CASCADE,
    FOREIGN KEY (actor_id) REFERENCES actor(actor_id) ON DELETE CASCADE,
    UNIQUE KEY (forum_id, actor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



















//EXPERIMENTAL
CREATE TABLE IF NOT EXISTS company (
    company_id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    company_logo VARCHAR(255),
    company_website VARCHAR(255),
    contact_number VARCHAR(50),
    company_description TEXT,
    address VARCHAR(255),
    status ENUM('Active', 'Inactive', 'Banned') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE (company_name)
);

CREATE TABLE IF NOT EXISTS employer (
    employer_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    company_id INT NULL, -- NULL if independent employer
    job_title VARCHAR(255),
    status ENUM('Active', 'Suspended', 'Banned') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES user(user_id),
    FOREIGN KEY (company_id) REFERENCES company(company_id)
);
CREATE TABLE IF NOT EXISTS job_posting (
    job_id INT AUTO_INCREMENT PRIMARY KEY,
    employer_id INT NOT NULL,
    company_id INT NULL, -- NULL if employer is independent
    title VARCHAR(255) NOT NULL,
    description TEXT,
    location VARCHAR(255),
    job_type_id INT,
    salary DECIMAL(10, 2),
    img_url VARCHAR(255),
    posted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL,
    moderation_status ENUM('Pending', 'Approved', 'Rejected', 'Paused') DEFAULT 'Pending',
    flagged BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (employer_id) REFERENCES employer(employer_id),
    FOREIGN KEY (company_id) REFERENCES company(company_id),
    FOREIGN KEY (job_type_id) REFERENCES job_type(job_type_id)
);









INSERT QUERIES


USE career_platform;

INSERT INTO role (role_title, role_description) VALUES
('Employer', 'User who posts job listings and manages hiring.'),
('Professional', 'User who applies for jobs and showcases skills.'),
('Moderator', 'User who moderates platform discussions and content.'),
('Admin', 'User with full access to manage the platform.');

INSERT INTO course (course_id, course_title, course_description) VALUES (1,'None','No Course Selected');


-- Programming Languages
INSERT INTO skill_masterlist (skill_name, category) VALUES
('JavaScript', 'Programming Languages'),
('Python', 'Programming Languages'),
('Java', 'Programming Languages'),
('C#', 'Programming Languages'),
('C++', 'Programming Languages'),
('Ruby', 'Programming Languages'),
('PHP', 'Programming Languages'),
('Swift', 'Programming Languages'),
('Kotlin', 'Programming Languages'),
('Go', 'Programming Languages'),
('R', 'Programming Languages'),
('SQL', 'Databases'),
('TypeScript', 'Programming Languages'),
('MATLAB', 'Programming Languages'),
('Perl', 'Programming Languages');

-- Databases
INSERT INTO skill_masterlist (skill_name, category) VALUES
('MySQL', 'Databases'),
('MongoDB', 'Databases'),
('PostgreSQL', 'Databases'),
('SQLite', 'Databases'),
('Oracle DB', 'Databases'),
('Redis', 'Databases'),
('Cassandra', 'Databases'),
('MariaDB', 'Databases'),
('NoSQL', 'Databases'),
('GraphQL', 'Databases');

-- Web Development
INSERT INTO skill_masterlist (skill_name, category) VALUES
('HTML', 'Web Development'),
('CSS', 'Web Development'),
('React', 'Web Development'),
('Angular', 'Web Development'),
('Vue.js', 'Web Development'),
('Node.js', 'Web Development'),
('Express.js', 'Web Development'),
('SASS', 'Web Development'),
('Bootstrap', 'Web Development'),
('jQuery', 'Web Development'),
('WordPress', 'Web Development'),
('Django', 'Web Development'),
('Ruby on Rails', 'Web Development'),
('Laravel', 'Web Development'),
('Next.js', 'Web Development');

-- Cloud Computing
INSERT INTO skill_masterlist (skill_name, category) VALUES
('AWS', 'Cloud Computing'),
('Azure', 'Cloud Computing'),
('Google Cloud', 'Cloud Computing'),
('IBM Cloud', 'Cloud Computing'),
('Oracle Cloud', 'Cloud Computing'),
('DigitalOcean', 'Cloud Computing'),
('Heroku', 'Cloud Computing'),
('Kubernetes', 'Cloud Computing'),
('Docker', 'Cloud Computing'),
('Terraform', 'Cloud Computing');

-- DevOps
INSERT INTO skill_masterlist (skill_name, category) VALUES
('Jenkins', 'DevOps'),
('CI/CD', 'DevOps'),
('Ansible', 'DevOps'),
('Chef', 'DevOps'),
('Puppet', 'DevOps'),
('Git', 'Version Control'),
('GitHub', 'Version Control'),
('GitLab', 'Version Control'),
('Bitbucket', 'Version Control'),
('Nagios', 'DevOps'),
('Prometheus', 'DevOps');

-- Cybersecurity
INSERT INTO skill_masterlist (skill_name, category) VALUES
('Penetration Testing', 'Cybersecurity'),
('Network Security', 'Cybersecurity'),
('Ethical Hacking', 'Cybersecurity'),
('Firewall Management', 'Cybersecurity'),
('Security Auditing', 'Cybersecurity'),
('Malware Analysis', 'Cybersecurity'),
('Risk Management', 'Cybersecurity'),
('Compliance (GDPR, HIPAA)', 'Cybersecurity'),
('Vulnerability Assessment', 'Cybersecurity'),
('Cryptography', 'Cybersecurity');

-- Data Science & AI
INSERT INTO skill_masterlist (skill_name, category) VALUES
('Machine Learning', 'Data Science & AI'),
('Deep Learning', 'Data Science & AI'),
('Natural Language Processing', 'Data Science & AI'),
('TensorFlow', 'Data Science & AI'),
('PyTorch', 'Data Science & AI'),
('Data Visualization', 'Data Science & AI'),
('Python for Data Science', 'Data Science & AI'),
('Big Data', 'Data Science & AI'),
('Apache Hadoop', 'Data Science & AI'),
('Data Mining', 'Data Science & AI'),
('R for Data Science', 'Data Science & AI');

-- Business & Finance
INSERT INTO skill_masterlist (skill_name, category) VALUES
('Business Analysis', 'Business & Finance'),
('Financial Analysis', 'Business & Finance'),
('Accounting', 'Business & Finance'),
('Corporate Finance', 'Business & Finance'),
('Investment Banking', 'Business & Finance'),
('Risk Management', 'Business & Finance'),
('Project Management', 'Business & Finance'),
('Product Management', 'Business & Finance'),
('Market Research', 'Business & Finance'),
('Negotiation', 'Business & Finance'),
('Budgeting', 'Business & Finance'),
('Mergers & Acquisitions', 'Business & Finance'),
('Venture Capital', 'Business & Finance'),
('Business Strategy', 'Business & Finance'),
('Financial Modelling', 'Business & Finance');

-- Marketing
INSERT INTO skill_masterlist (skill_name, category) VALUES
('SEO', 'Marketing'),
('Content Marketing', 'Marketing'),
('Social Media Marketing', 'Marketing'),
('Google Ads', 'Marketing'),
('Facebook Ads', 'Marketing'),
('Email Marketing', 'Marketing'),
('Branding', 'Marketing'),
('Market Research', 'Marketing'),
('Affiliate Marketing', 'Marketing'),
('Digital Marketing', 'Marketing'),
('Marketing Automation', 'Marketing'),
('Copywriting', 'Marketing'),
('Public Relations', 'Marketing'),
('Influencer Marketing', 'Marketing'),
('Event Marketing', 'Marketing');

-- Sales
INSERT INTO skill_masterlist (skill_name, category) VALUES
('Sales Strategy', 'Sales'),
('Salesforce', 'Sales'),
('Lead Generation', 'Sales'),
('B2B Sales', 'Sales'),
('B2C Sales', 'Sales'),
('CRM Tools', 'Sales'),
('Negotiation Skills', 'Sales'),
('Cold Calling', 'Sales'),
('Closing Deals', 'Sales'),
('Sales Forecasting', 'Sales');

-- Graphic & UI/UX Design
INSERT INTO skill_masterlist (skill_name, category) VALUES
('Photoshop', 'Design'),
('Illustrator', 'Design'),
('Adobe XD', 'Design'),
('Figma', 'Design'),
('Sketch', 'Design'),
('Wireframing', 'Design'),
('UI Design', 'Design'),
('UX Design', 'Design'),
('Responsive Design', 'Design'),
('Branding Design', 'Design'),
('Product Design', 'Design'),
('Prototyping', 'Design'),
('UI Prototyping', 'Design'),
('Web Design', 'Design'),
('Interaction Design', 'Design');

-- Soft Skills
INSERT INTO skill_masterlist (skill_name, category) VALUES
('Communication', 'Soft Skills'),
('Leadership', 'Soft Skills'),
('Teamwork', 'Soft Skills'),
('Adaptability', 'Soft Skills'),
('Time Management', 'Soft Skills'),
('Problem Solving', 'Soft Skills'),
('Creativity', 'Soft Skills'),
('Critical Thinking', 'Soft Skills'),
('Conflict Resolution', 'Soft Skills'),
('Emotional Intelligence', 'Soft Skills'),
('Collaboration', 'Soft Skills'),
('Decision Making', 'Soft Skills'),
('Presentation Skills', 'Soft Skills'),
('Active Listening', 'Soft Skills'),
('Motivation', 'Soft Skills');

-- Healthcare
INSERT INTO skill_masterlist (skill_name, category) VALUES
('Patient Care', 'Healthcare'),
('Medical Research', 'Healthcare'),
('Clinical Trials', 'Healthcare'),
('Nursing', 'Healthcare'),
('Pharmacy', 'Healthcare'),
('Pharmacology', 'Healthcare'),
('Surgery', 'Healthcare'),
('Anesthesia', 'Healthcare'),
('Radiology', 'Healthcare'),
('Medical Coding', 'Healthcare'),
('Healthcare Management', 'Healthcare'),
('Mental Health', 'Healthcare'),
('Public Health', 'Healthcare'),
('Medical Billing', 'Healthcare'),
('Physical Therapy', 'Healthcare');

-- Legal
INSERT INTO skill_masterlist (skill_name, category) VALUES
('Contract Law', 'Legal'),
('Corporate Law', 'Legal'),
('Intellectual Property', 'Legal'),
('Litigation', 'Legal'),
('Criminal Law', 'Legal'),
('Family Law', 'Legal'),
('Mediation', 'Legal'),
('Arbitration', 'Legal'),
('Compliance', 'Legal'),
('Legal Research', 'Legal'),
('Legal Writing', 'Legal'),
('Due Diligence', 'Legal'),
('Tax Law', 'Legal'),
('Estate Planning', 'Legal'),
('Labor Law', 'Legal');

-- Education & Teaching
INSERT INTO skill_masterlist (skill_name, category) VALUES
('Curriculum Development', 'Education'),
('Teaching', 'Education'),
('Classroom Management', 'Education'),
('Educational Technology', 'Education'),
('Special Education', 'Education'),
('Tutoring', 'Education'),
('Language Teaching', 'Education'),
('Lesson Planning', 'Education'),
('Public Speaking', 'Education'),
('Student Assessment', 'Education'),
('E-Learning', 'Education'),
('Instructional Design', 'Education'),
('Teacher Training', 'Education'),
('Early Childhood Education', 'Education'),
('Higher Education', 'Education');

-- Manufacturing & Engineering
INSERT INTO skill_masterlist (skill_name, category) VALUES
('Mechanical Engineering', 'Engineering'),
('Electrical Engineering', 'Engineering'),
('Civil Engineering', 'Engineering'),
('Project Management (Engineering)', 'Engineering'),
('Manufacturing Processes', 'Engineering'),
('AutoCAD', 'Engineering'),
('PLC Programming', 'Engineering'),
('Quality Control', 'Engineering'),
('Production Planning', 'Engineering'),
('3D Modeling', 'Engineering'),
('Robotics', 'Engineering'),
('Supply Chain Management', 'Engineering'),
('Lean Manufacturing', 'Engineering'),
('Engineering Design', 'Engineering'),
('Materials Science', 'Engineering');





