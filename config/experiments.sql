


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
