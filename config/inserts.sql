

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

