-- File: migrate_company_employer.sql
-- Purpose: Create and alter tables to separate company and employer data
-- Date Created: August 7, 2025

-- Start a transaction to ensure data consistency
START TRANSACTION;

-- 1. Create the company table
CREATE TABLE IF NOT EXISTS company (
    company_id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    company_logo VARCHAR(255),
    company_website VARCHAR(255),
    company_description TEXT,
    contact_number VARCHAR(50),
    status ENUM('Verification', 'Active', 'Suspended', 'Banned') DEFAULT 'Verification',
    document_url VARCHAR(255) NULL COMMENT 'Uploaded verification document for company legitimacy',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT unique_company_name UNIQUE (company_name)
);

-- 2. Add company_id to the employer table
ALTER TABLE employer
    ADD company_id INT;

-- 3. Make company_id NOT NULL and add foreign key, then drop old company-related columns
ALTER TABLE employer
    MODIFY company_id INT NOT NULL,
    ADD FOREIGN KEY (company_id) REFERENCES company(company_id),
    DROP COLUMN company_name,
    DROP COLUMN company_logo,
    DROP COLUMN company_website,
    DROP COLUMN company_description,
    DROP COLUMN contact_number,
    DROP COLUMN document_url,
    MODIFY status ENUM('Active', 'Suspended', 'Banned') DEFAULT 'Active';

-- 4. Add company_id to the job_posting table
ALTER TABLE job_posting
    ADD company_id INT;

-- 5. Update job_posting table: make company_id NOT NULL, make employer_id nullable, update foreign keys
ALTER TABLE job_posting
    MODIFY company_id INT NOT NULL,
    MODIFY employer_id INT NULL COMMENT 'Tracks which employer posted the job',
    DROP FOREIGN KEY job_posting_ibfk_1,
    ADD FOREIGN KEY (company_id) REFERENCES company(company_id),
    ADD FOREIGN KEY (employer_id) REFERENCES employer(employer_id);

-- 6. Update job_posting triggers
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

-- 7. Add indexes for performance
ALTER TABLE employer
    ADD INDEX idx_employer_company_id (company_id),
    ADD INDEX idx_employer_user_id (user_id);

ALTER TABLE job_posting
    ADD INDEX idx_job_posting_company_id (company_id),
    ADD INDEX idx_job_posting_employer_id (employer_id);

-- 8. Create a legacy view for backward compatibility
CREATE VIEW employer_legacy AS
SELECT 
    e.employer_id,
    e.user_id,
    c.company_name,
    e.job_title,
    c.company_logo,
    c.status,
    c.company_website,
    c.contact_number,
    c.company_description,
    e.created_at,
    e.deleted_at,
    c.document_url
FROM employer e
JOIN company c ON e.company_id = c.company_id;

-- Commit the transaction
COMMIT;








Keeping the Migration File
You’ve already compiled migrate_company_employer.sql for separating company and employer tables. Keep this file in your repository for future use if:

Your capstone scope expands to include multiple employers per company or company-level features (e.g., company profiles).
You continue the project post-capstone and need scalability.
If you apply it later, you can adapt it to support individuals by making company_id nullable, as described in Option 2 of the previous response.

Additional Considerations

Capstone Documentation: In your capstone report, explain the decision to keep the combined employer table with is_company for simplicity, but note the potential for 
separation (referencing migrate_company_employer.sql) to show awareness of normalization.
Edge Cases: If an individual posts a job, consider how to display their profile (e.g., use user.picture_file instead of company_logo). Test this in your UI (e.g., job listing pages).
Verification for Individuals: Decide whether individual posters need verification (e.g., ID upload). If so, consider adding a document_url to the user table:
sqlALTER TABLE user
    ADD document_url VARCHAR(255) NULL COMMENT 'Verification document for individual users';


Conclusion
For your capstone project, keeping the combined employer table with the proposed changes 
(is_company and posted_by_name) is the best approach. It allows individuals who don’t belong to a company to post jobs by 
skipping company fields, requires minimal schema and code changes, and fits the time and scope constraints of a capstone.
 The CREATE, ALTER, and DELIMITER queries provided above are sufficient to implement this solution. Keep migrate_company_employer.sql for future